<?php
require_once __DIR__ . '/includes/auth.php';

$q    = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$per  = 20;
$off  = ($page - 1) * $per;

$products   = [];
$totalFound = 0;

if (strlen($q) >= 2) {
    $like = '%' . $q . '%';
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE (name LIKE ? OR description LIKE ?) AND is_active = 1");
    $countStmt->execute([$like, $like]);
    $totalFound = (int)$countStmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT * FROM products WHERE (name LIKE :like1 OR description LIKE :like2) AND is_active = 1 ORDER BY is_popular DESC, name ASC LIMIT :lim OFFSET :off");
    $stmt->bindValue(':like1', $like);
    $stmt->bindValue(':like2', $like);
    $stmt->bindValue(':lim', $per, PDO::PARAM_INT);
    $stmt->bindValue(':off', $off, PDO::PARAM_INT);
    $stmt->execute();
    $products = $stmt->fetchAll();
}

$totalPages = (int)ceil($totalFound / $per);

require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
  <div class="breadcrumbs">
    <a href="/shop/index.php">Главная</a> / <span>Поиск</span>
  </div>

  <h1 style="font-size:1.4rem;font-weight:800;margin:16px 0">
    🔍 Поиск: «<?= htmlspecialchars($q) ?>»
    <?php if ($q): ?>
    <span style="font-size:0.9rem;color:var(--text-muted);font-weight:400">— найдено <?= $totalFound ?></span>
    <?php endif; ?>
  </h1>

  <!-- Форма поиска -->
  <form action="" method="GET" style="max-width:600px;display:flex;gap:8px;margin-bottom:32px">
    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>"
           class="form-control" placeholder="Введите название товара..." style="flex:1">
    <button type="submit" class="btn-primary" style="width:auto;padding:10px 24px">Найти</button>
  </form>

  <?php if (empty($q)): ?>
    <div class="empty-state">
      <div class="empty-icon">🔍</div>
      <h3>Введите запрос</h3>
      <p>Начните вводить название товара для поиска</p>
    </div>
  <?php elseif (empty($products)): ?>
    <div class="empty-state">
      <div class="empty-icon">😔</div>
      <h3>Ничего не найдено</h3>
      <p>По запросу «<?= htmlspecialchars($q) ?>» товаров не найдено. Попробуйте другой запрос.</p>
      <a href="/shop/catalog.php" class="btn-primary" style="display:inline-block;margin-top:20px;max-width:200px">Весь каталог</a>
    </div>
  <?php else: ?>
    <div class="products-grid">
      <?php foreach ($products as $product): ?>
      <?php include __DIR__ . '/includes/product_card.php'; ?>
      <?php endforeach; ?>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="pagination">
      <?php if ($page > 1): ?><a href="?q=<?= urlencode($q) ?>&page=<?= $page-1 ?>">‹</a><?php endif; ?>
      <?php for ($i = max(1,$page-2); $i <= min($totalPages,$page+2); $i++): ?>
        <?php if ($i === $page): ?><span class="current"><?= $i ?></span>
        <?php else: ?><a href="?q=<?= urlencode($q) ?>&page=<?= $i ?>"><?= $i ?></a><?php endif; ?>
      <?php endfor; ?>
      <?php if ($page < $totalPages): ?><a href="?q=<?= urlencode($q) ?>&page=<?= $page+1 ?>">›</a><?php endif; ?>
    </div>
    <?php endif; ?>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
