<?php
/**
 * Страница каталога товаров
 * 
 * Обрабатывает фильтрацию, сортировку и пагинацию товаров.
 * Поддерживает фильтры по категории, цене, наличию и различные варианты сортировки.
 */

// Подключение модуля аутентификации
require_once __DIR__ . '/includes/auth.php';

// ============================================================================
// 1. Получение и обработка параметров запроса
// ============================================================================

// Категория товара (0 = все категории)
$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;

// Тип сортировки
$sortBy = $_GET['sort'] ?? 'popular';

// Фильтр по цене
$minPrice = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$maxPrice = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 999999;

// Фильтр "только в наличии"
$inStock = isset($_GET['in_stock']);

// Пагинация: текущая страница (минимум 1)
$page = max(1, isset($_GET['page']) ? (int)$_GET['page'] : 1);
$perPage = 20; // Товаров на страницу
$offset = ($page - 1) * $perPage;

// ============================================================================
// 2. Построение условий для SQL-запроса
// ============================================================================

$whereConditions = [];
$sqlParams = [];

// Условие: активные товары
$whereConditions[] = 'p.is_active = 1';

// Фильтр по категории
if ($categoryId > 0) {
    $whereConditions[] = 'p.category_id = :cat';
    $sqlParams[':cat'] = $categoryId;
}

// Фильтр по минимальной цене
if ($minPrice > 0) {
    $whereConditions[] = 'p.price >= :min';
    $sqlParams[':min'] = $minPrice;
}

// Фильтр по максимальной цене
if ($maxPrice < 999999) {
    $whereConditions[] = 'p.price <= :max';
    $sqlParams[':max'] = $maxPrice;
}

// Фильтр по наличию
if ($inStock) {
    $whereConditions[] = 'p.stock > 0';
}

// Формирование строки WHERE
$whereClause = implode(' AND ', $whereConditions);

// Определение порядка сортировки
switch ($sortBy) {
    case 'price_asc':
        $orderClause = 'p.price ASC';
        break;
    case 'price_desc':
        $orderClause = 'p.price DESC';
        break;
    case 'new':
        $orderClause = 'p.created_at DESC';
        break;
    case 'name':
        $orderClause = 'p.name ASC';
        break;
    case 'popular':
    default:
        // Сначала популярные, потом новые
        $orderClause = 'p.is_popular DESC, p.created_at DESC';
        break;
}

// ============================================================================
// 3. Получение данных из базы
// ============================================================================

// Подсчет общего количества товаров
$countSql = "SELECT COUNT(*) FROM products p WHERE $whereClause";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($sqlParams);
$totalProducts = (int)$countStmt->fetchColumn();
$totalPages = ceil($totalProducts / $perPage);

// Получение списка товаров с информацией о категории
$productsSql = "
    SELECT p.*, c.name as cat_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE $whereClause 
    ORDER BY $orderClause 
    LIMIT :limit OFFSET :offset
";
$stmt = $pdo->prepare($productsSql);

// Привязка параметров
foreach ($sqlParams as $paramName => $paramValue) {
    $stmt->bindValue($paramName, $paramValue);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();

// Получение списка корневых категорий для фильтра
$categoriesSql = "SELECT * FROM categories WHERE parent_id IS NULL ORDER BY sort_order";
$categories = $pdo->query($categoriesSql)->fetchAll();

// Информация о текущей категории (если выбрана)
$currentCategory = null;
if ($categoryId > 0) {
    $catStmt = // SQL Запрос: выборка данных
    $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $catStmt->execute([$categoryId]);
    $currentCategory = $catStmt->fetch();
}

// Диапазон цен для отображения в фильтре
$priceRangeSql = "SELECT MIN(price) as min, MAX(price) as max FROM products WHERE is_active = 1";
$priceRange = $pdo->query($priceRangeSql)->fetch();

// Подключение шаблона заголовка
require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
  <!-- Хлебные крошки (навигационная цепочка) -->
  <div class="breadcrumbs">
    <a href="/shop/index.php">Главная</a> /
    <a href="/shop/catalog.php">Каталог</a>
    <?php if ($currentCategory): ?>
    / <span><?= htmlspecialchars($currentCategory['name']) ?></span>
    <?php endif; ?>
  </div>

  <div class="catalog-layout">
    <!-- Боковая панель с фильтрами -->
    <aside class="catalog-sidebar">
      <h3 class="sidebar-title">Фильтры</h3>
      <form id="catalog-filter-form" method="GET" action="">

        <!-- Фильтр по категориям -->
        <div class="filter-group">
          <div class="sidebar-title" style="font-size:0.88rem;margin-bottom:10px">Категория</div>
          <label>
            <input type="radio" name="category" value="0" <?= $categoryId === 0 ? 'checked' : '' ?>> Все категории
          </label>
          <?php foreach ($categories as $cat): ?>
          <label>
            <input type="radio" name="category" value="<?= $cat['id'] ?>" <?= $categoryId === $cat['id'] ? 'checked' : '' ?>>
            <?= htmlspecialchars($cat['name']) ?>
          </label>
          <?php endforeach; ?>
        </div>

        <!-- Фильтр по цене -->
        <div class="filter-group">
          <div class="sidebar-title" style="font-size:0.88rem;margin-bottom:10px">Цена, ₽</div>
          <div class="price-range">
            <input type="number" name="min_price" placeholder="<?= (int)$priceRange['min'] ?>"
                   value="<?= $minPrice > 0 ? (int)$minPrice : '' ?>">
            <span>—</span>
            <input type="number" name="max_price" placeholder="<?= (int)$priceRange['max'] ?>"
                   value="<?= $maxPrice < 999999 ? (int)$maxPrice : '' ?>">
          </div>
        </div>

        <!-- Фильтр по наличию -->
        <div class="filter-group">
          <label>
            <input type="checkbox" name="in_stock" <?= $inStock ? 'checked' : '' ?>>
            Только в наличии
          </label>
        </div>

        <!-- Сортировка товаров -->
        <div class="filter-group">
          <div class="sidebar-title" style="font-size:0.88rem;margin-bottom:10px">Сортировка</div>
          <select name="sort" style="width:100%;padding:8px;border:1px solid var(--border);border-radius:var(--radius-sm);background:var(--surface2);color:var(--text)">
            <option value="popular"    <?= $sortBy === 'popular' ? 'selected' : '' ?>>По популярности</option>
            <option value="new"        <?= $sortBy === 'new' ? 'selected' : '' ?>>Сначала новые</option>
            <option value="price_asc"  <?= $sortBy === 'price_asc' ? 'selected' : '' ?>>Цена: по возрастанию</option>
            <option value="price_desc" <?= $sortBy === 'price_desc' ? 'selected' : '' ?>>Цена: по убыванию</option>
            <option value="name"       <?= $sortBy === 'name' ? 'selected' : '' ?>>По названию</option>
          </select>
        </div>

        <button type="submit" class="btn-primary" style="margin-top:8px">Применить</button>
        <a href="/shop/catalog.php" style="display:block;text-align:center;margin-top:8px;font-size:0.85rem;color:var(--text-muted)">Сбросить фильтры</a>
      </form>
    </aside>

    <!-- Список товаров -->
    <div>
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
        <h1 style="font-size:1.3rem;font-weight:800">
          <?= $currentCategory ? htmlspecialchars($currentCategory['name']) : 'Все товары' ?>
          <span style="font-size:0.85rem;color:var(--text-muted);font-weight:400">(<?= $totalProducts ?>)</span>
        </h1>
      </div>

      <?php if (empty($products)): ?>
      <!-- Сообщение, если товары не найдены -->
      <div class="empty-state">
        <div class="empty-icon">🔍</div>
        <h3>Товары не найдены</h3>
        <p>Попробуйте изменить параметры фильтрации</p>
      </div>
      <?php else: ?>
      <!-- Сетка товаров -->
      <div class="products-grid">
        <?php foreach ($products as $product): ?>
        <?php include __DIR__ . '/includes/product_card.php'; ?>
        <?php endforeach; ?>
      </div>

      <!-- Постраничная навигация -->
      <?php if ($totalPages > 1): ?>
      <div class="pagination">
        <!-- Кнопка "Назад" -->
        <?php if ($page > 1): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">‹</a>
        <?php endif; ?>
        
        <!-- Номера страниц -->
        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
        <?php if ($i === $page): ?>
        <span class="current"><?= $i ?></span>
        <?php else: ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
        <?php endif; ?>
        <?php endfor; ?>
        
        <!-- Кнопка "Вперед" -->
        <?php if ($page < $totalPages): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">›</a>
        <?php endif; ?>
      </div>
      <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
