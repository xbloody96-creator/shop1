<?php
require_once __DIR__ . '/includes/auth.php';
$id = (int)($_GET['id']??0);
$stmt = $pdo->prepare("SELECT * FROM news WHERE id=? AND is_active=1");
$stmt->execute([$id]);
$news = $stmt->fetch();
if (!$news) { header('Location: /shop/news.php'); exit; }

require_once __DIR__ . '/includes/header.php';
?>
<div class="container" style="max-width:860px">
  <div class="breadcrumbs">
    <a href="/shop/index.php">Главная</a> /
    <a href="/shop/news.php">Новости</a> /
    <span><?= htmlspecialchars($news['title']) ?></span>
  </div>
  <article style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:40px;margin-top:16px">
    <div style="font-size:0.85rem;color:var(--text-muted);margin-bottom:12px">
      📅 <?= date('d.m.Y', strtotime($news['created_at'])) ?>
    </div>
    <h1 style="font-size:1.8rem;font-weight:800;margin-bottom:20px"><?= htmlspecialchars($news['title']) ?></h1>
    <?php if ($news['image']): ?>
    <img src="/shop/uploads/<?= htmlspecialchars($news['image']) ?>" alt="" style="width:100%;max-height:400px;object-fit:cover;border-radius:var(--radius-sm);margin-bottom:24px">
    <?php endif; ?>
    <div style="line-height:1.9;color:var(--text-muted);font-size:0.95rem">
      <?= nl2br(htmlspecialchars($news['content']??'')) ?>
    </div>
  </article>
  <div style="margin-top:20px">
    <a href="/shop/news.php" style="color:var(--accent2);font-weight:600">← Все новости</a>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
