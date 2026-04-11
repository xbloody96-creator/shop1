<?php
/**
 * Страница корзины покупок
 * 
 * Отображает товары, добавленные пользователем в корзину,
 * позволяет изменять количество и удалять позиции.
 */

require_once __DIR__ . '/includes/auth.php';
requireLogin(); // Проверяем, что пользователь авторизован

// Получаем ID текущего пользователя из сессии
$userId = $_SESSION['user_id'];

/**
 * Получаем все товары из корзины пользователя
 * Присоединяем таблицу продуктов для получения полной информации
 */
$sql = "SELECT c.id as cart_id, c.quantity, p.* 
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ? 
        ORDER BY c.added_at DESC";
        
$stmt = $pdo->prepare($sql);
$stmt->execute([$userId]);
$cartItems = $stmt->fetchAll();

// Считаем общую сумму и размер скидки
$totalSum = 0;
$totalDiscount = 0;

foreach ($cartItems as $item) {
    // Сумма по текущей цене
    $totalSum += $item['price'] * $item['quantity'];
    
    // Если есть старая цена - считаем скидку
    if ($item['old_price']) {
        $discountPerItem = max(0, $item['old_price'] - $item['price']);
        $totalDiscount += $discountPerItem * $item['quantity'];
    }
}

/**
 * Обработка удаления товара из корзины
 * Удаляем только если запрос POST и передан ID элемента корзины
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_cart_id'])) {
    $cartItemId = (int)$_POST['remove_cart_id'];
    
    $deleteStmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
    $deleteStmt->execute([$cartItemId, $userId]);
    
    // Перенаправляем на эту же страницу, чтобы обновить данные
    header('Location: /shop/cart.php');
    exit;
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
  <div class="breadcrumbs">
    <a href="/shop/index.php">Главная</a> / <span>Корзина</span>
  </div>
  <h1 style="font-size:1.6rem;font-weight:800;margin:12px 0 24px">🛒 Корзина</h1>

  <?php if (empty($cartItems)): ?>
  <!-- Состояние: корзина пуста -->
  <div class="empty-state">
    <div class="empty-icon">🛒</div>
    <h3>Корзина пуста</h3>
    <p>Добавьте товары из каталога, чтобы оформить заказ</p>
    <a href="/shop/catalog.php" class="btn-primary" style="display:inline-block;margin-top:20px;max-width:200px">Перейти в каталог</a>
  </div>
  <?php else: ?>
  
  <div class="cart-layout">
    <!-- Левая колонка: список товаров -->
    <div class="cart-items-list">
      <?php foreach ($cartItems as $item): ?>
      <div class="cart-item" data-cart-id="<?= $item['cart_id'] ?>" data-cart-price="<?= $item['price'] ?>">
        
        <!-- Изображение товара -->
        <a href="/shop/product.php?id=<?= $item['id'] ?>" class="cart-item-image">
          <?php if ($item['main_image']): ?>
            <img src="/shop/uploads/<?= htmlspecialchars($item['main_image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
          <?php else: ?>
            <div style="width:90px;height:90px;background:var(--surface2);border-radius:var(--radius-sm);display:flex;align-items:center;justify-content:center;font-size:2rem">📦</div>
          <?php endif; ?>
        </a>
        
        <!-- Информация о товаре -->
        <div class="cart-item-info">
          <a href="/shop/product.php?id=<?= $item['id'] ?>" class="cart-item-name"><?= htmlspecialchars($item['name']) ?></a>
          
          <div class="cart-item-price">
            <?= number_format($item['price'], 0, '', ' ') ?> ₽
          </div>
          
          <!-- Старая цена (если есть скидка) -->
          <?php if ($item['old_price']): ?>
            <div style="font-size:0.82rem;color:var(--text-muted);text-decoration:line-through">
              <?= number_format($item['old_price'], 0, '', ' ') ?> ₽
            </div>
          <?php endif; ?>
          
          <!-- Управление количеством -->
          <div class="qty-control">
            <button class="qty-minus" aria-label="Уменьшить количество">-</button>
            <span class="qty-value"><?= $item['quantity'] ?></span>
            <button class="qty-plus" aria-label="Увеличить количество">+</button>
            
            <span style="margin-left:12px;font-size:0.85rem;color:var(--text-muted)">
              Итого: <strong><?= number_format($item['price'] * $item['quantity'], 0, '', ' ') ?> ₽</strong>
            </span>
          </div>
        </div>
        
        <!-- Кнопка удаления -->
        <form method="POST" action="" class="cart-item-remove">
          <input type="hidden" name="remove_cart_id" value="<?= $item['cart_id'] ?>">
          <button type="submit" class="btn-remove" aria-label="Удалить товар из корзины">✕ Удалить</button>
        </form>
        
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Правая колонка: итоговая информация -->
    <aside class="cart-summary">
      <h3>Итого по заказу</h3>
      
      <div class="summary-row">
        <span>Товаров (<?= count($cartItems) ?>)</span>
        <span><?= number_format($totalSum + $totalDiscount, 0, '', ' ') ?> ₽</span>
      </div>
      
      <!-- Отображаем скидку, если она есть -->
      <?php if ($totalDiscount > 0): ?>
      <div class="summary-row" style="color:var(--success)">
        <span>Скидка</span>
        <span>-<?= number_format($totalDiscount, 0, '', ' ') ?> ₽</span>
      </div>
      <?php endif; ?>
      
      <div class="summary-row" style="color:var(--text-muted)">
        <span>Доставка</span>
        <span>Бесплатно</span>
      </div>
      
      <!-- Общая сумма к оплате -->
      <div class="summary-row total">
        <span>К оплате</span>
        <span id="cart-total"><?= number_format($totalSum, 0, '', ' ') ?> ₽</span>
      </div>
      
      <a href="/shop/checkout.php" class="btn-primary" style="display:block;margin-top:20px">
        Оформить заказ →
      </a>
      
      <a href="/shop/catalog.php" style="display:block;text-align:center;margin-top:12px;font-size:0.85rem;color:var(--text-muted)">
        ← Продолжить покупки
      </a>
    </aside>
  </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
