<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$userId = $_SESSION['user_id'];

// Товары корзины
$stmt = $pdo->prepare("SELECT c.id as cart_id, c.quantity, p.* FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ? ORDER BY c.added_at DESC");
$stmt->execute([$userId]);
$cartItems = $stmt->fetchAll();

$total    = 0;
$discount = 0;
foreach ($cartItems as $item) {
    $total    += $item['price'] * $item['quantity'];
    $discount += $item['old_price'] ? max(0, ($item['old_price'] - $item['price']) * $item['quantity']) : 0;
}

// Удаление товара
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_cart_id'])) {
    $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?")->execute([(int)$_POST['remove_cart_id'], $userId]);
    header('Location: /cart.php');
    exit;
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
  <div class="breadcrumbs">
    <a href="/index.php">Главная</a> / <span>Корзина</span>
  </div>
  <h1 style="font-size:1.6rem;font-weight:800;margin:12px 0 24px">🛒 Корзина</h1>

  <?php if (empty($cartItems)): ?>
  <div class="empty-state">
    <div class="empty-icon">🛒</div>
    <h3>Корзина пуста</h3>
    <p>Добавьте товары из каталога</p>
    <a href="/catalog.php" class="btn-primary" style="display:inline-block;margin-top:20px;max-width:200px">Перейти в каталог</a>
  </div>
  <?php else: ?>
  <div class="cart-layout">
    <!-- Список товаров -->
    <div>
      <?php foreach ($cartItems as $item): ?>
      <div class="cart-item" data-cart-id="<?= $item['cart_id'] ?>" data-cart-price="<?= $item['price'] ?>">
        <a href="/product.php?id=<?= $item['id'] ?>">
          <?php if ($item['main_image']): ?>
          <img src="/uploads/<?= htmlspecialchars($item['main_image']) ?>" alt="">
          <?php else: ?>
          <div style="width:90px;height:90px;background:var(--surface2);border-radius:var(--radius-sm);display:flex;align-items:center;justify-content:center;font-size:2rem">📦</div>
          <?php endif; ?>
        </a>
        <div class="cart-item-info">
          <a href="/product.php?id=<?= $item['id'] ?>" class="cart-item-name"><?= htmlspecialchars($item['name']) ?></a>
          <div class="cart-item-price"><?= number_format($item['price'], 0, '', ' ') ?> ₽</div>
          <?php if ($item['old_price']): ?>
          <div style="font-size:0.82rem;color:var(--text-muted);text-decoration:line-through"><?= number_format($item['old_price'], 0, '', ' ') ?> ₽</div>
          <?php endif; ?>
          <div class="qty-control">
            <button class="qty-minus">-</button>
            <span class="qty-value"><?= $item['quantity'] ?></span>
            <button class="qty-plus">+</button>
            <span style="margin-left:12px;font-size:0.85rem;color:var(--text-muted)">
              Итого: <strong><?= number_format($item['price'] * $item['quantity'], 0, '', ' ') ?> ₽</strong>
            </span>
          </div>
        </div>
        <form method="POST" action="">
          <input type="hidden" name="remove_cart_id" value="<?= $item['cart_id'] ?>">
          <button type="submit" class="btn-remove">✕ Удалить</button>
        </form>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Итог -->
    <div class="cart-summary">
      <h3>Итого по заказу</h3>
      <div class="summary-row">
        <span>Товаров (<?= count($cartItems) ?>)</span>
        <span><?= number_format($total + $discount, 0, '', ' ') ?> ₽</span>
      </div>
      <?php if ($discount > 0): ?>
      <div class="summary-row" style="color:var(--success)">
        <span>Скидка</span>
        <span>-<?= number_format($discount, 0, '', ' ') ?> ₽</span>
      </div>
      <?php endif; ?>
      <div class="summary-row" style="color:var(--text-muted)">
        <span>Доставка</span>
        <span>Бесплатно</span>
      </div>
      <div class="summary-row total">
        <span>К оплате</span>
        <span id="cart-total"><?= number_format($total, 0, '', ' ') ?> ₽</span>
      </div>
      <a href="/checkout.php" class="btn-primary" style="display:block;margin-top:20px">
        Оформить заказ →
      </a>
      <a href="/catalog.php" style="display:block;text-align:center;margin-top:12px;font-size:0.85rem;color:var(--text-muted)">
        ← Продолжить покупки
      </a>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
