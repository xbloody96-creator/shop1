<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$stmt = $pdo->prepare("SELECT p.* FROM favorites f JOIN products p ON f.product_id = p.id WHERE f.user_id = ? ORDER BY f.added_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$favorites = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>
<div class="container">
  <div class="breadcrumbs"><a href="/shop/index.php">Главная</a> / <span>Избранное</span></div>
  <h1 style="font-size:1.6rem;font-weight:800;margin:16px 0 24px">❤️ Избранное</h1>
  <?php if (empty($favorites)): ?>
  <div class="empty-state">
    <div class="empty-icon">❤️</div>
    <h3>Список избранного пуст</h3>
    <p>Добавляйте понравившиеся товары в избранное</p>
    <a href="/shop/catalog.php" class="btn-primary" style="display:inline-block;margin-top:20px;max-width:200px">В каталог</a>
  </div>
  <?php else: ?>
  <div class="products-grid">
    <?php foreach ($favorites as $product): ?>
    <?php include __DIR__ . '/includes/product_card.php'; ?>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
