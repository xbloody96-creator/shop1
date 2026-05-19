/**
 * Файл: news.php
 * Описание: Страница сайта
 * @version 1.0
 */

<?php
// Подключение модуля аутентификации
require_once __DIR__ . '/includes/auth.php';

$page = max(1,(int)($_GET['page']??1));
$per  = 9; $off = ($page-1)*$per;

$total = (int)$pdo->query("SELECT COUNT(*) FROM news WHERE is_active=1")->fetchColumn();
$stmt  = // SQL Запрос: выборка данных
    $pdo->prepare("SELECT * FROM news WHERE is_active=1 ORDER BY created_at DESC LIMIT :l OFFSET :o");
$stmt->bindValue(':l',$per,PDO::PARAM_INT);
$stmt->bindValue(':o',$off,PDO::PARAM_INT);
$stmt->execute();
$newsList   = $stmt->fetchAll();
$totalPages = (int)ceil($total/$per);

require_once __DIR__ . '/includes/header.php';
?>
<div class="container">
  <div class="breadcrumbs"><a href="/shop/index.php">Главная</a> / <span>Новости</span></div>
  <h1 style="font-size:1.6rem;font-weight:800;margin:16px 0 24px">📰 Новости</h1>

  <?php if (empty($newsList)): ?>
  <div class="empty-state"><div class="empty-icon">📰</div><h3>Новостей пока нет</h3></div>
  <?php else: ?>
  <div class="news-grid">
    <?php foreach ($newsList as $n): ?>
    <div class="news-card">
      <?php if ($n['image']): ?>
      <img src="/shop/uploads/<?= htmlspecialchars($n['image']) ?>" alt="">
      <?php else: ?>
      <div style="height:180px;background:linear-gradient(135deg,var(--accent2),var(--accent));display:flex;align-items:center;justify-content:center;font-size:3rem">📰</div>
      <?php endif; ?>
      <div class="news-card-body">
        <div class="news-card-date"><?= date('d.m.Y', strtotime($n['created_at'])) ?></div>
        <h3 class="news-card-title"><?= htmlspecialchars($n['title']) ?></h3>
        <p class="news-card-text"><?= htmlspecialchars(mb_substr(strip_tags($n['content']??''),0,120)) ?>...</p>
        <a href="/shop/news_detail.php?id=<?= $n['id'] ?>" style="display:inline-block;margin-top:10px;color:var(--accent);font-weight:700;font-size:0.88rem">Читать →</a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php if ($totalPages > 1): ?>
  <div class="pagination">
    <?php if ($page>1): ?><a href="?page=<?=$page-1?>">‹</a><?php endif; ?>
    <?php for($i=max(1,$page-2);$i<=min($totalPages,$page+2);$i++): ?>
      <?php if($i===$page): ?><span class="current"><?=$i?></span>
      <?php else: ?><a href="?page=<?=$i?>"><?=$i?></a><?php endif; ?>
    <?php endfor; ?>
    <?php if($page<$totalPages): ?><a href="?page=<?=$page+1?>">›</a><?php endif; ?>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
