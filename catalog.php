<?php
require_once __DIR__ . '/includes/auth.php';


// Параметры фильтрации
$categoryId = (int)($_GET['category'] ?? 0);
$sortBy     = $_GET['sort'] ?? 'popular';

// ✅ ИСПРАВЛЕНО: правильная обработка пустых значений цены
$minPrice   = isset($_GET['min_price']) && $_GET['min_price'] !== '' 
              ? (float)$_GET['min_price'] 
              : 0;
$maxPrice   = isset($_GET['max_price']) && $_GET['max_price'] !== '' && $_GET['max_price'] > 0
              ? (float)$_GET['max_price'] 
              : 999999;

$inStock    = isset($_GET['in_stock']);
$page       = max(1, (int)($_GET['page'] ?? 1));
$perPage    = 20;
$offset     = ($page - 1) * $perPage;

// Построение запроса
$where  = ['p.is_active = 1'];
$params = [];

if ($categoryId > 0) {
    $where[]  = 'p.category_id = :cat';
    $params[':cat'] = $categoryId;
}
if ($minPrice > 0) {
    $where[]  = 'p.price >= :min';
    $params[':min'] = $minPrice;
}
if ($maxPrice < 999999) {
    $where[]  = 'p.price <= :max';
    $params[':max'] = $maxPrice;
}
if ($inStock) {
    $where[]  = 'p.stock > 0';
}

$orderClause = match($sortBy) {
    'price_asc'  => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'new'        => 'p.created_at DESC',
    'name'       => 'p.name ASC',
    default      => 'p.is_popular DESC, p.created_at DESC',
};

$whereStr = implode(' AND ', $where);

// Общее количество
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM products p WHERE $whereStr");
$countStmt->execute($params);
$totalProducts = (int)$countStmt->fetchColumn();
$totalPages    = (int)ceil($totalProducts / $perPage);

// Товары — ИСПРАВЛЕНО
// LIMIT и OFFSET подставляем напрямую (безопасно, т.к. это (int))
$stmt = $pdo->prepare("SELECT p.*, c.name as cat_name 
                       FROM products p 
                       LEFT JOIN categories c ON p.category_id = c.id 
                       WHERE $whereStr 
                       ORDER BY $orderClause 
                       LIMIT $perPage OFFSET $offset");

// Привязываем только параметры WHERE
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}

$stmt->execute();
$products = $stmt->fetchAll();

// Категории
$categories = $pdo->query("SELECT * FROM categories WHERE parent_id IS NULL ORDER BY sort_order")->fetchAll();

// Текущая категория
$currentCat = null;
if ($categoryId > 0) {
    $s = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $s->execute([$categoryId]);
    $currentCat = $s->fetch();
}

// Диапазон цен
$priceRange = $pdo->query("SELECT MIN(price) as min, MAX(price) as max FROM products WHERE is_active=1")->fetch();

require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
  <!-- Breadcrumbs -->
  <div class="breadcrumbs">
    <a href="/index.php">Главная</a> /
    <a href="/catalog.php">Каталог</a>
    <?php if ($currentCat): ?>
    / <span><?= htmlspecialchars($currentCat['name']) ?></span>
    <?php endif; ?>
  </div>

  <div class="catalog-layout">
    <!-- Сайдбар с фильтрами -->
    <aside class="catalog-sidebar">
      <h3 class="sidebar-title">Фильтры</h3>
      <form id="catalog-filter-form" method="GET" action="">

        <!-- Категории -->
        <div class="filter-group">
          <div class="sidebar-title" style="font-size:0.88rem;margin-bottom:10px">Категория</div>
          <label>
            <input type="radio" name="category" value="0" <?= $categoryId===0 ? 'checked' : '' ?>> Все категории
          </label>
          <?php foreach ($categories as $cat): ?>
          <label>
            <input type="radio" name="category" value="<?= $cat['id'] ?>" <?= $categoryId===$cat['id'] ? 'checked' : '' ?>>
            <?= htmlspecialchars($cat['name']) ?>
          </label>
          <?php endforeach; ?>
        </div>

        <!-- Цена -->
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

        <!-- Наличие -->
        <div class="filter-group">
          <label>
            <input type="checkbox" name="in_stock" <?= $inStock ? 'checked' : '' ?>>
            Только в наличии
          </label>
        </div>

        <!-- Сортировка -->
        <div class="filter-group">
          <div class="sidebar-title" style="font-size:0.88rem;margin-bottom:10px">Сортировка</div>
          <select name="sort" style="width:100%;padding:8px;border:1px solid var(--border);border-radius:var(--radius-sm);background:var(--surface2);color:var(--text)">
            <option value="popular"    <?= $sortBy==='popular'    ? 'selected' : '' ?>>По популярности</option>
            <option value="new"        <?= $sortBy==='new'        ? 'selected' : '' ?>>Сначала новые</option>
            <option value="price_asc"  <?= $sortBy==='price_asc'  ? 'selected' : '' ?>>Цена: по возрастанию</option>
            <option value="price_desc" <?= $sortBy==='price_desc' ? 'selected' : '' ?>>Цена: по убыванию</option>
            <option value="name"       <?= $sortBy==='name'       ? 'selected' : '' ?>>По названию</option>
          </select>
        </div>

        <button type="submit" class="btn-primary" style="margin-top:8px">Применить</button>
        <a href="/catalog.php" style="display:block;text-align:center;margin-top:8px;font-size:0.85rem;color:var(--text-muted)">Сбросить фильтры</a>
      </form>
    </aside>

    <!-- Товары -->
    <div>
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
        <h1 style="font-size:1.3rem;font-weight:800">
          <?= $currentCat ? htmlspecialchars($currentCat['name']) : 'Все товары' ?>
          <span style="font-size:0.85rem;color:var(--text-muted);font-weight:400">(<?= $totalProducts ?>)</span>
        </h1>
      </div>

      <?php if (empty($products)): ?>
      <div class="empty-state">
        <div class="empty-icon">🔍</div>
        <h3>Товары не найдены</h3>
        <p>Попробуйте изменить параметры фильтрации</p>
      </div>
      <?php else: ?>
      <div class="products-grid">
        <?php foreach ($products as $product): ?>
        <?php include __DIR__ . '/includes/product_card.php'; ?>
        <?php endforeach; ?>
      </div>

      <!-- Пагинация -->
      <?php if ($totalPages > 1): ?>
      <div class="pagination">
        <?php if ($page > 1): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">‹</a>
        <?php endif; ?>
        <?php for ($i = max(1,$page-2); $i <= min($totalPages,$page+2); $i++): ?>
        <?php if ($i === $page): ?>
        <span class="current"><?= $i ?></span>
        <?php else: ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
        <?php endif; ?>
        <?php endfor; ?>
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
