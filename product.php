/**
 * Файл: product.php
 * Описание: Страница сайта
 * @version 1.0
 */

<?php
/**
 * Страница отдельного товара
 * 
 * Отображает полную информацию о товаре: галерею, характеристики, отзывы.
 * Позволяет добавить товар в корзину, избранное и оставить отзыв.
 */

// Подключение модуля аутентификации
require_once __DIR__ . '/includes/auth.php';

// ============================================================================
// 1. Получение ID товара и проверка существования
// ============================================================================

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Если ID не передан — перенаправляем в каталог
if ($productId === 0) {
    // Перенаправление пользователя
header('Location: /shop/catalog.php');
    exit;
}

// Получаем информацию о товаре с названием категории
$productSql = "
    SELECT p.*, c.name as cat_name, c.id as cat_id 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.id = ? AND p.is_active = 1
";
$stmt = $pdo->prepare($productSql);
$stmt->execute([$productId]);
$product = $stmt->fetch();

// Если товар не найден или не активен — перенаправляем в каталог
if (!$product) {
    // Перенаправление пользователя
header('Location: /shop/catalog.php');
    exit;
}

// ============================================================================
// 2. Загрузка дополнительных данных о товаре
// ============================================================================

// Галерея изображений товара
$gallerySql = "SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order";
$galleryStmt = $pdo->prepare($gallerySql);
$galleryStmt->execute([$productId]);
$gallery = $galleryStmt->fetchAll();

// Характеристики товара
$specsSql = "SELECT * FROM product_specs WHERE product_id = ? ORDER BY id";
$specsStmt = $pdo->prepare($specsSql);
$specsStmt->execute([$productId]);
$specsList = $specsStmt->fetchAll();

// Отзывы: только одобренные, с данными пользователей
$reviewsSql = "
    SELECT r.*, u.full_name, u.nickname, u.avatar 
    FROM reviews r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.product_id = ? AND r.is_approved = 1 
    ORDER BY r.created_at DESC
";
$reviewsStmt = $pdo->prepare($reviewsSql);
$reviewsStmt->execute([$productId]);
$reviews = $reviewsStmt->fetchAll();

// Расчет среднего рейтинга
if (count($reviews) > 0) {
    $ratings = array_column($reviews, 'rating');
    $avgRating = round(array_sum($ratings) / count($reviews), 1);
} else {
    $avgRating = 0;
}

// Похожие товары из той же категории (максимум 4)
$similarSql = "
    SELECT * FROM products 
    WHERE category_id = ? AND id != ? AND is_active = 1 
    LIMIT 4
";
$similarStmt = $pdo->prepare($similarSql);
$similarStmt->execute([$product['cat_id'], $productId]);
$similarProducts = $similarStmt->fetchAll();

// ============================================================================
// 3. История просмотров (для авторизованных пользователей)
// ============================================================================

if (isLoggedIn()) {
    $userId = $_SESSION['user_id'];
    
    // Удаляем старую запись об этом просмотре (если есть)
    $deleteSql = "DELETE FROM view_history WHERE user_id = ? AND product_id = ?";
    $pdo->prepare($deleteSql)->execute([$userId, $productId]);
    
    // Добавляем новую запись
    $insertSql = "INSERT INTO view_history (user_id, product_id) VALUES (?, ?)";
    $pdo->prepare($insertSql)->execute([$userId, $productId]);
}

// ============================================================================
// 4. Обработка формы отправки отзыва
// ============================================================================

$reviewError = '';
$reviewSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_text'])) {
    
    // Проверка авторизации
    if (!isLoggedIn()) {
        $reviewError = 'Необходимо войти, чтобы оставить отзыв';
    } else {
        // Получаем и валидируем данные
        $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 5;
        $rating = min(5, max(1, $rating)); // Ограничиваем от 1 до 5
        
        $reviewText = isset($_POST['review_text']) ? trim($_POST['review_text']) : '';
        
        if ($reviewText === '') {
            $reviewError = 'Введите текст отзыва';
        } else {
            // Сохраняем отзыв (статус: на модерации)
            $insertReviewSql = "
                INSERT INTO reviews (product_id, user_id, rating, text, is_approved) 
                VALUES (?, ?, ?, ?, 0)
            ";
            $pdo->prepare($insertReviewSql)->execute([
                $productId, 
                $_SESSION['user_id'], 
                $rating, 
                $reviewText
            ]);
            
            $reviewSuccess = 'Отзыв отправлен на модерацию. Спасибо!';
        }
    }
}

// Подключаем шапку сайта
require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
  <!-- Хлебные крошки (навигационная цепочка) -->
  <div class="breadcrumbs">
    <a href="/shop/index.php">Главная</a> /
    <a href="/shop/catalog.php">Каталог</a>
    <?php if ($product['cat_name']): ?>
    / <a href="/shop/catalog.php?category=<?= $product['cat_id'] ?>"><?= htmlspecialchars($product['cat_name']) ?></a>
    <?php endif; ?>
    / <span><?= htmlspecialchars($product['name']) ?></span>
  </div>

  <div class="product-layout">
    <!-- Галерея изображений товара -->
    <div class="product-gallery">
      <?php
      // Определяем основное изображение
      $mainImage = $product['main_image'] ? '/shop/uploads/' . $product['main_image'] : '';
      $firstGalleryImage = !empty($gallery) ? '/shop/uploads/' . $gallery[0]['image'] : $mainImage;
      ?>
      
      <!-- Основное изображение -->
      <img src="<?= htmlspecialchars($firstGalleryImage ?: '') ?>" 
           alt="<?= htmlspecialchars($product['name']) ?>" 
           class="gallery-main" id="gallery-main"
           onerror="this.style.display='none'">
      
      <!-- Заглушка, если изображений нет -->
      <?php if (!$firstGalleryImage): ?>
      <div style="height:360px;background:var(--surface2);display:flex;align-items:center;justify-content:center;font-size:6rem;border-radius:var(--radius)">📦</div>
      <?php endif; ?>

      <!-- Миниатюры галереи -->
      <?php if (!empty($gallery)): ?>
      <div class="gallery-thumbs">
        <?php foreach ($gallery as $index => $image): ?>
        <img src="/shop/uploads/<?= htmlspecialchars($image['image']) ?>"
             alt="" class="gallery-thumb <?= $index === 0 ? 'active' : '' ?>">
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- Информация о товаре -->
    <div class="product-info">
      <div class="product-info-card">
        <h1 style="font-size:1.3rem;font-weight:800;margin-bottom:12px"><?= htmlspecialchars($product['name']) ?></h1>

        <!-- Рейтинг и количество отзывов -->
        <?php if ($avgRating > 0): ?>
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px">
          <span class="stars"><?= str_repeat('★', (int)$avgRating) ?><?= str_repeat('☆', 5 - (int)$avgRating) ?></span>
          <span style="font-size:0.88rem;color:var(--text-muted)"><?= $avgRating ?> (<?= count($reviews) ?> отзывов)</span>
        </div>
        <?php endif; ?>

        <!-- Цена товара -->
        <div style="margin-bottom:16px">
          <span class="product-price"><?= number_format($product['price'], 0, '', ' ') ?> ₽</span>
          <?php if ($product['old_price']): ?>
          <span class="product-old-price"><?= number_format($product['old_price'], 0, '', ' ') ?> ₽</span>
          <?php if ($product['old_price'] > $product['price']): ?>
          <span class="badge badge-sale" style="position:static;margin-left:8px">
            -<?= round(100 - ($product['price'] / $product['old_price'] * 100)) ?>%
          </span>
          <?php endif; ?>
          <?php endif; ?>
        </div>

        <!-- Наличие на складе -->
        <div class="product-stock">
          <?php if ($product['stock'] > 0): ?>
          <span class="in-stock">✓ В наличии (<?= $product['stock'] ?> шт.)</span>
          <?php else: ?>
          <span class="out-stock">✗ Нет в наличии</span>
          <?php endif; ?>
        </div>

        <!-- Кнопки действий -->
        <div style="margin-top:20px;display:flex;flex-direction:column;gap:10px">
          <?php if ($product['stock'] > 0): ?>
          <button class="btn-primary js-add-cart" data-product-id="<?= $product['id'] ?>">
            🛒 Добавить в корзину
          </button>
          <?php else: ?>
          <button class="btn-primary" disabled style="opacity:0.5;cursor:not-allowed">Нет в наличии</button>
          <?php endif; ?>
          <button class="btn-secondary js-add-fav" data-product-id="<?= $product['id'] ?>">❤️ В избранное</button>
        </div>
      </div>

      <!-- Описание товара -->
      <?php if (!empty($product['description'])): ?>
      <div class="product-info-card">
        <h3 style="font-weight:700;margin-bottom:12px">Описание</h3>
        <p style="color:var(--text-muted);line-height:1.8;font-size:0.9rem"><?= nl2br(htmlspecialchars($product['description'])) ?></p>
      </div>
      <?php endif; ?>

      <!-- Характеристики товара -->
      <?php if (!empty($specsList)): ?>
      <div class="product-info-card">
        <h3 style="font-weight:700;margin-bottom:12px">Характеристики</h3>
        <table class="specs-table">
          <?php foreach ($specsList as $spec): ?>
          <tr>
            <td><?= htmlspecialchars($spec['spec_key']) ?></td>
            <td><?= htmlspecialchars($spec['spec_value']) ?></td>
          </tr>
          <?php endforeach; ?>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Секция отзывов -->
  <section class="section">
    <h2 class="section-title">💬 Отзывы (<?= count($reviews) ?>)</h2>

    <!-- Вывод сообщений об ошибках/успехе -->
    <?php if ($reviewError): ?>
    <div class="alert alert-error"><?= $reviewError ?></div>
    <?php endif; ?>
    <?php if ($reviewSuccess): ?>
    <div class="alert alert-success"><?= $reviewSuccess ?></div>
    <?php endif; ?>

    <!-- Форма отправки отзыва (только для авторизованных) -->
    <?php if (isLoggedIn()): ?>
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:24px;margin-bottom:24px">
      <h3 style="font-weight:700;margin-bottom:16px">Написать отзыв</h3>
      <form method="POST" action="">
        <div class="form-group">
          <label>Оценка</label>
          <select name="rating" class="form-control" style="max-width:200px">
            <?php for ($i = 5; $i >= 1; $i--): ?>
            <option value="<?= $i ?>"><?= str_repeat('★', $i) ?> (<?= $i ?>)</option>
            <?php endfor; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Ваш отзыв</label>
          <textarea name="review_text" class="form-control" rows="4" placeholder="Поделитесь впечатлениями о товаре..." required></textarea>
        </div>
        <button type="submit" class="btn-primary" style="max-width:200px">Отправить отзыв</button>
      </form>
    </div>
    <?php else: ?>
    <div class="alert alert-info">
      <a href="/shop/login.php">Войдите</a>, чтобы оставить отзыв
    </div>
    <?php endif; ?>

    <!-- Список отзывов -->
    <?php if (empty($reviews)): ?>
    <div class="empty-state" style="padding:30px">
      <p>Пока нет отзывов. Будьте первым!</p>
    </div>
    <?php else: ?>
    <?php foreach ($reviews as $review): ?>
    <div class="review-card">
      <div class="review-header">
        <div style="display:flex;align-items:center;gap:10px">
          <?php 
          $avatarPath = $review['avatar'] && $review['avatar'] !== 'default_avatar.png' ? '/shop/uploads/' . $review['avatar'] : '';
          ?>
          <?php if ($avatarPath): ?>
          <img src="<?= htmlspecialchars($avatarPath) ?>" style="width:36px;height:36px;border-radius:50%;object-fit:cover" alt="">
          <?php endif; ?>
          <div>
            <div class="review-author"><?= htmlspecialchars($review['full_name']) ?></div>
            <div class="review-date"><?= date('d.m.Y', strtotime($review['created_at'])) ?></div>
          </div>
        </div>
        <div class="stars"><?= str_repeat('★', $review['rating']) ?><?= str_repeat('☆', 5 - $review['rating']) ?></div>
      </div>
      <p class="review-text"><?= nl2br(htmlspecialchars($review['text'])) ?></p>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </section>

  <!-- Блок похожих товаров -->
  <?php if (!empty($similarProducts)): ?>
  <section class="section">
    <h2 class="section-title">Похожие товары</h2>
    <div class="products-grid">
      <?php foreach ($similarProducts as $product): ?>
      <?php include __DIR__ . '/includes/product_card.php'; ?>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
