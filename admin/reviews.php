/**
 * Файл: reviews.php
 * Описание: Страница сайта
 * @version 1.0
 */

<?php require_once __DIR__ . '/header.php';

// Одобрить / удалить
if (isset($_GET['approve'])) {
    // SQL Запрос: обновление данных
    $pdo->prepare("UPDATE reviews SET is_approved=1 WHERE id=?")->execute([(int)$_GET['approve']]);
    // Перенаправление пользователя
header('Location: /shop/admin/reviews.php'); exit;
}
if (isset($_GET['delete'])) {
    // SQL Запрос: удаление данных
    $pdo->prepare("DELETE FROM reviews WHERE id=?")->execute([(int)$_GET['delete']]);
    // Перенаправление пользователя
header('Location: /shop/admin/reviews.php'); exit;
}

$filter = isset($_GET['pending']) ? 'WHERE r.is_approved=0' : 'WHERE 1';
$reviews = $pdo->query("SELECT r.*, u.full_name, p.name as product_name, p.id as product_id FROM reviews r JOIN users u ON r.user_id=u.id JOIN products p ON r.product_id=p.id $filter ORDER BY r.created_at DESC")->fetchAll();
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
  <h1>💬 Отзывы (<?= count($reviews) ?>)</h1>
  <div style="display:flex;gap:8px">
    <a href="?pending=1" style="padding:8px 16px;border-radius:6px;font-size:0.85rem;font-weight:600;background:<?= isset($_GET['pending'])?'var(--accent)':'var(--surface2)' ?>;color:<?= isset($_GET['pending'])?'#fff':'var(--text)' ?>">На модерации</a>
    <a href="?" style="padding:8px 16px;border-radius:6px;font-size:0.85rem;font-weight:600;background:<?= !isset($_GET['pending'])?'var(--accent)':'var(--surface2)' ?>;color:<?= !isset($_GET['pending'])?'#fff':'var(--text)' ?>">Все</a>
  </div>
</div>

<table class="admin-table">
  <thead>
    <tr><th>ID</th><th>Пользователь</th><th>Товар</th><th>Оценка</th><th>Текст</th><th>Дата</th><th>Статус</th><th>Действия</th></tr>
  </thead>
  <tbody>
    <?php if (empty($reviews)): ?>
    <tr><td colspan="8" style="text-align:center;padding:30px;color:var(--text-muted)">Отзывов нет</td></tr>
    <?php endif; ?>
    <?php foreach ($reviews as $r): ?>
    <tr>
      <td><?= $r['id'] ?></td>
      <td><?= htmlspecialchars($r['full_name']) ?></td>
      <td><a href="/shop/product.php?id=<?= $r['product_id'] ?>" target="_blank" style="color:var(--accent2)"><?= htmlspecialchars(mb_substr($r['product_name'],0,30)) ?></a></td>
      <td><span style="color:#f59e0b"><?= str_repeat('★',$r['rating']) ?><?= str_repeat('☆',5-$r['rating']) ?></span></td>
      <td style="max-width:200px;font-size:0.85rem"><?= htmlspecialchars(mb_substr($r['text'],0,80)) ?>...</td>
      <td style="font-size:0.82rem"><?= date('d.m.Y', strtotime($r['created_at'])) ?></td>
      <td><?= $r['is_approved'] ? '<span style="color:var(--success);font-weight:600">✅ Одобрен</span>' : '<span style="color:#d97706;font-weight:600">⏳ Ожидает</span>' ?></td>
      <td style="white-space:nowrap">
        <?php if (!$r['is_approved']): ?>
        <a href="?approve=<?= $r['id'] ?>" class="btn-sm btn-edit">✅ Одобрить</a>
        <?php endif; ?>
        <a href="?delete=<?= $r['id'] ?>" class="btn-sm btn-delete js-confirm-delete">🗑</a>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php require_once __DIR__ . '/footer.php'; ?>
