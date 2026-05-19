<?php require_once __DIR__ . '/header.php';

$id   = (int)($_GET['id']??0);
$news = null;
if ($id) {
    $s = $pdo->prepare("SELECT * FROM news WHERE id=?");
    $s->execute([$id]);
    $news = $s->fetch();
    if (!$news) { header('Location: /admin/news.php'); exit; }
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title    = trim($_POST['title']??'');
    $content  = trim($_POST['content']??'');
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $slug     = preg_replace('/-+/','-',mb_strtolower(preg_replace('/[^a-zA-Zа-яёА-ЯЁ0-9]/u','-',$title)));

    if (empty($title)) $errors[] = 'Введите заголовок';

    $image = $news['image'] ?? null;
    if (!empty($_FILES['image']['name'])) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext,['jpg','jpeg','png','webp','gif'])) {
            $dir = __DIR__ . '/../uploads/news/';
            if (!is_dir($dir)) mkdir($dir,0755,true);
            $fname = 'news_'.uniqid().'.'.$ext;
            move_uploaded_file($_FILES['image']['tmp_name'], $dir.$fname);
            $image = 'news/'.$fname;
        }
    }

    if (empty($errors)) {
        if ($id) {
            $pdo->prepare("UPDATE news SET title=?,slug=?,content=?,image=?,is_active=? WHERE id=?")->execute([$title,$slug,$content,$image,$isActive,$id]);
        } else {
            $pdo->prepare("INSERT INTO news (title,slug,content,image,is_active) VALUES (?,?,?,?,?)")->execute([$title,$slug,$content,$image,$isActive]);
        }
        header('Location: /admin/news.php?saved=1'); exit;
    }
}
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
  <h1><?= $id ? '✏ Редактировать новость' : '➕ Добавить новость' ?></h1>
  <a href="/admin/news.php" style="color:var(--text-muted)">← Назад</a>
</div>

<?php foreach($errors as $e): ?><div class="alert alert-error"><?= htmlspecialchars($e) ?></div><?php endforeach; ?>

<form method="POST" action="" enctype="multipart/form-data">
  <div class="admin-form-card">
    <div class="form-group">
      <label>Заголовок <span class="required">*</span></label>
      <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($news['title']??'') ?>" required>
    </div>
    <div class="form-group">
      <label>Содержание</label>
      <textarea name="content" class="form-control" rows="10"><?= htmlspecialchars($news['content']??'') ?></textarea>
    </div>
    <div class="form-group">
      <label>Изображение</label>
      <?php if (!empty($news['image'])): ?>
      <img src="/uploads/<?= htmlspecialchars($news['image']) ?>" style="max-height:160px;border-radius:8px;margin-bottom:8px;display:block">
      <?php endif; ?>
      <input type="file" name="image" class="form-control" accept="image/*">
    </div>
    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;margin-bottom:16px">
      <input type="checkbox" name="is_active" <?= ($news['is_active']??1) ? 'checked' : '' ?>>
      <span>Активная (отображается на сайте)</span>
    </label>
    <button type="submit" class="btn-primary" style="max-width:220px"><?= $id ? '💾 Сохранить' : '➕ Добавить' ?></button>
  </div>
</form>

<?php require_once __DIR__ . '/footer.php'; ?>
