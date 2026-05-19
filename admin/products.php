/**
 * Файл: products.php
 * Описание: Страница сайта
 * @version 1.0
 */

<?php require_once __DIR__ . '/header.php';

// Удаление
if (isset($_GET['delete']) && (int)$_GET['delete']) {
    $productId = (int)$_GET['delete'];
    // Удаляем изображение
    // SQL Запрос: выборка данных
    $imageFile = $pdo->prepare("SELECT main_image FROM products WHERE id=?");
    $imageFile->execute([$productId]);
    $record = $imageFile->fetch();
    if ($record['main_image'] && file_exists(__DIR__ . '/../uploads/' . $record['main_image'])) {
        unlink(__DIR__ . '/../uploads/' . $record['main_image']);
    }
    // SQL Запрос: удаление данных
    $pdo->prepare("DELETE FROM products WHERE id=?")->execute([$productId]);
    // Перенаправление пользователя
header('Location: /shop/admin/products.php?deleted=1'); exit;
}

$page = max(1,(int)($_GET['page']??1));
$per  = 20; $off = ($page-1)*$per;
$search = trim($_GET['s']??'');

$where  = 'WHERE 1';
$params = [];
if ($search) {
    $where .= ' AND p.name LIKE :s';
    $params[':s'] = '%'.$search.'%';
}

$total = (int)// SQL Запрос: выборка данных
    $pdo->prepare("SELECT COUNT(*) FROM products p $where")->execute($params) ? // SQL Запрос: выборка данных
    $pdo->prepare("SELECT COUNT(*) FROM products p $where") : 0;
$countStmt = // SQL Запрос: выборка данных
    $pdo->prepare("SELECT COUNT(*) FROM products p $where");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$totalPages = (int)ceil($total/$per);

$stmt = // SQL Запрос: выборка данных
    $pdo->prepare("SELECT p.*, c.name as cat FROM products p LEFT JOIN categories c ON p.category_id=c.id $where ORDER BY p.id DESC LIMIT :l OFFSET :o");
foreach ($params as $k=>$v) $stmt->bindValue($k,$v);
$stmt->bindValue(':l',$per,PDO::PARAM_INT);
$stmt->bindValue(':o',$off,PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
  <h1>📦 Товары (<?= $total ?>)</h1>
  <a href="/shop/admin/product_edit.php" class="btn-add">➕ Добавить товар</a>
</div>

<?php if (isset($_GET['deleted'])): ?><div class="alert alert-success">Товар удалён</div><?php endif; ?>
<?php if (isset($_GET['saved'])): ?><div class="alert alert-success">Товар сохранён</div><?php endif; ?>

<form method="GET" action="" style="margin-bottom:16px;display:flex;gap:8px">
  <input type="text" name="s" value="<?= htmlspecialchars($search) ?>" class="form-control" placeholder="Поиск по названию..." style="max-width:300px">
  <button type="submit" class="btn-add" style="margin:0">Найти</button>
  <?php if ($search): ?><a href="/shop/admin/products.php" style="align-self:center;color:var(--text-muted);font-size:0.85rem">✕ Сбросить</a><?php endif; ?>
</form>

<table class="admin-table">
  <thead>
    <tr>
      <th>ID</th><th>Фото</th><th>Название</th><th>Категория</th>
      <th>Цена</th><th>Остаток</th><th>Активен</th><th>Действия</th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($products)): ?>
    <tr><td colspan="8" style="text-align:center;color:var(--text-muted);padding:30px">Товаров не найдено</td></tr>
    <?php endif; ?>
    <?php foreach ($products as $p): ?>
    <tr>
      <td><?= $p['id'] ?></td>
      <td>
        <?php if ($p['main_image']): ?>
        <img src="/shop/uploads/<?= htmlspecialchars($p['main_image']) ?>" alt="">
        <?php else: ?><span style="font-size:1.5rem">📦</span><?php endif; ?>
      </td>
      <td>
        <strong><?= htmlspecialchars($p['name']) ?></strong>
        <?php if ($p['is_popular']): ?><span class="badge badge-popular" style="position:static;margin-left:4px;font-size:0.65rem">Хит</span><?php endif; ?>
      </td>
      <td><?= htmlspecialchars($p['cat']??'—') ?></td>
      <td>
        <strong><?= number_format($p['price'],0,'', ' ') ?> ₽</strong>
        <?php if ($p['old_price']): ?><br><small style="color:var(--text-muted);text-decoration:line-through"><?= number_format($p['old_price'],0,'', ' ') ?> ₽</small><?php endif; ?>
      </td>
      <td>
        <span style="color:<?= $p['stock']>5?'var(--success)':($p['stock']>0?'#d97706':'var(--accent)') ?>;font-weight:700">
          <?= $p['stock'] ?> шт.
        </span>
      </td>
      <td>
        <?php
        $active = (bool)$p['is_active'];
        $toggleUrl = "/shop/admin/products.php?toggle={$p['id']}&val=" . ($active?0:1);
        ?>
        <a href="<?= $toggleUrl ?>" style="font-size:1.2rem" title="<?= $active?'Скрыть':'Показать' ?>">
          <?= $active ? '✅' : '🚫' ?>
        </a>
      </td>
      <td style="white-space:nowrap">
        <a href="/shop/admin/product_edit.php?id=<?= $p['id'] ?>" class="btn-sm btn-edit">✏ Ред.</a>
        <a href="/shop/product.php?id=<?= $p['id'] ?>" target="_blank" class="btn-sm btn-edit" style="background:#f0fdf4;color:#166534">👁</a>
        <a href="/shop/admin/products.php?delete=<?= $p['id'] ?>" class="btn-sm btn-delete js-confirm-delete">🗑</a>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php
// Toggle active
if (isset($_GET['toggle']) && isset($_GET['val'])) {
    // SQL Запрос: обновление данных
    $pdo->prepare("UPDATE products SET is_active=? WHERE id=?")->execute([(int)$_GET['val'], (int)$_GET['toggle']]);
    // Перенаправление пользователя
header('Location: /shop/admin/products.php'); exit;
}
?>

<?php if ($totalPages > 1): ?>
<div class="pagination" style="margin-top:20px">
  <?php if ($page>1): ?><a href="?s=<?= urlencode($search) ?>&page=<?=$page-1?>">‹</a><?php endif; ?>
  <?php for($i=max(1,$page-2);$i<=min($totalPages,$page+2);$i++): ?>
    <?php if($i===$page): ?><span class="current"><?=$i?></span>
    <?php else: ?><a href="?s=<?= urlencode($search) ?>&page=<?=$i?>"><?=$i?></a><?php endif; ?>
  <?php endfor; ?>
  <?php if($page<$totalPages): ?><a href="?s=<?= urlencode($search) ?>&page=<?=$page+1?>">›</a><?php endif; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
