<?php require_once __DIR__ . '/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $name = trim($_POST['name']??'');
    $slug = preg_replace('/-+/','-',mb_strtolower(preg_replace('/[^a-zA-Zа-яёА-ЯЁ0-9]/u','-',$name)));
    $parentId = (int)($_POST['parent_id']??0) ?: null;
    $sortOrder = (int)($_POST['sort_order']??0);
    $catId = (int)($_POST['cat_id']??0);

    if ($_POST['action']==='delete') {
        $pdo->prepare("DELETE FROM categories WHERE id=?")->execute([(int)$_POST['cat_id']]);
    } elseif ($name) {
        if ($catId) {
            $pdo->prepare("UPDATE categories SET name=?,slug=?,parent_id=?,sort_order=? WHERE id=?")->execute([$name,$slug,$parentId,$sortOrder,$catId]);
        } else {
            $pdo->prepare("INSERT INTO categories (name,slug,parent_id,sort_order) VALUES (?,?,?,?)")->execute([$name,$slug,$parentId,$sortOrder]);
        }
    }
    header('Location: /shop/admin/categories.php?saved=1'); exit;
}

$cats    = $pdo->query("SELECT c.*, p.name as parent_name, (SELECT COUNT(*) FROM products WHERE category_id=c.id) as cnt FROM categories c LEFT JOIN categories p ON c.parent_id=p.id ORDER BY c.sort_order,c.name")->fetchAll();
$editId  = (int)($_GET['edit']??0);
$editCat = null;
if ($editId) foreach ($cats as $c) { if ($c['id']===$editId) { $editCat=$c; break; } }
?>

<h1>🗂 Категории (<?= count($cats) ?>)</h1>
<?php if (isset($_GET['saved'])): ?><div class="alert alert-success">Сохранено ✅</div><?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 360px;gap:20px">
  <table class="admin-table">
    <thead><tr><th>ID</th><th>Название</th><th>Родитель</th><th>Порядок</th><th>Товаров</th><th>Действия</th></tr></thead>
    <tbody>
      <?php foreach ($cats as $c): ?>
      <tr>
        <td><?= $c['id'] ?></td>
        <td><strong><?= htmlspecialchars($c['name']) ?></strong></td>
        <td><?= htmlspecialchars($c['parent_name']??'—') ?></td>
        <td><?= $c['sort_order'] ?></td>
        <td><?= $c['cnt'] ?></td>
        <td style="white-space:nowrap">
          <a href="?edit=<?= $c['id'] ?>" class="btn-sm btn-edit">✏</a>
          <form method="POST" style="display:inline">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="cat_id" value="<?= $c['id'] ?>">
            <button type="submit" class="btn-sm btn-delete js-confirm-delete">🗑</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="admin-form-card" style="height:fit-content">
    <h3 style="font-weight:700;margin-bottom:16px"><?= $editCat ? '✏ Редактировать' : '➕ Новая категория' ?></h3>
    <form method="POST">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="cat_id" value="<?= $editCat['id']??0 ?>">
      <div class="form-group">
        <label>Название <span class="required">*</span></label>
        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($editCat['name']??'') ?>" required>
      </div>
      <div class="form-group">
        <label>Родительская категория</label>
        <select name="parent_id" class="form-control">
          <option value="">— Корневая —</option>
          <?php foreach ($cats as $c): if ($c['id']===($editCat['id']??0)) continue; ?>
          <option value="<?= $c['id'] ?>" <?= ($editCat['parent_id']??'')==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Порядок сортировки</label>
        <input type="number" name="sort_order" class="form-control" value="<?= $editCat['sort_order']??0 ?>" min="0">
      </div>
      <button type="submit" class="btn-primary"><?= $editCat ? '💾 Сохранить' : '➕ Добавить' ?></button>
      <?php if ($editCat): ?><a href="?" style="display:block;text-align:center;margin-top:8px;font-size:0.85rem;color:var(--text-muted)">Отмена</a><?php endif; ?>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
