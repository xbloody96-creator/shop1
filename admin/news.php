<?php require_once __DIR__ . '/header.php';

if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM news WHERE id=?")->execute([(int)$_GET['delete']]);
    header('Location: /admin/news.php?deleted=1'); exit;
}

$newsList = $pdo->query("SELECT * FROM news ORDER BY created_at DESC")->fetchAll();
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
  <h1>📰 Новости (<?= count($newsList) ?>)</h1>
  <a href="/admin/news_edit.php" class="btn-add">➕ Добавить новость</a>
</div>
<?php if (isset($_GET['deleted'])): ?><div class="alert alert-success">Новость удалена</div><?php endif; ?>
<?php if (isset($_GET['saved'])): ?><div class="alert alert-success">Новость сохранена</div><?php endif; ?>

<table class="admin-table">
  <thead><tr><th>ID</th><th>Заголовок</th><th>Дата</th><th>Активна</th><th>Действия</th></tr></thead>
  <tbody>
    <?php if (empty($newsList)): ?>
    <tr><td colspan="5" style="text-align:center;padding:30px;color:var(--text-muted)">Новостей нет</td></tr>
    <?php endif; ?>
    <?php foreach ($newsList as $n): ?>
    <tr>
      <td><?= $n['id'] ?></td>
      <td><strong><?= htmlspecialchars($n['title']) ?></strong></td>
      <td><?= date('d.m.Y', strtotime($n['created_at'])) ?></td>
      <td><?= $n['is_active'] ? '✅' : '🚫' ?></td>
      <td style="white-space:nowrap">
        <a href="/admin/news_edit.php?id=<?= $n['id'] ?>" class="btn-sm btn-edit">✏ Ред.</a>
        <a href="/news_detail.php?id=<?= $n['id'] ?>" target="_blank" class="btn-sm btn-edit" style="background:#f0fdf4;color:#166534">👁</a>
        <a href="?delete=<?= $n['id'] ?>" class="btn-sm btn-delete js-confirm-delete">🗑</a>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php require_once __DIR__ . '/footer.php'; ?>
