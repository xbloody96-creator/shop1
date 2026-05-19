/**
 * Файл: promotions.php
 * Описание: Страница сайта
 * @version 1.0
 */

<?php require_once __DIR__ . '/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'delete') {
        // SQL Запрос: удаление данных
    $pdo->prepare("DELETE FROM promotions WHERE id=?")->execute([(int)$_POST['id']]);
    } elseif ($_POST['action'] === 'save') {
        $title   = trim($_POST['title']??'');
        $desc    = trim($_POST['description']??'');
        $discount    = (int)($_POST['discount_pct']??0);
        $active  = isset($_POST['is_active']) ? 1 : 0;
        $productId     = (int)($_POST['promo_id']??0);

        $image = null;
        if (!empty($_FILES['image']['name'])) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext,['jpg','jpeg','png','webp','gif'])) {
                $dir = __DIR__ . '/../uploads/promos/';
                if (!is_dir($dir)) mkdir($dir,0755,true);
                $fname = 'promo_'.uniqid().'.'.$ext;
                move_uploaded_file($_FILES['image']['tmp_name'], $dir.$fname);
                $image = 'promos/'.$fname;
            }
        }

        if ($productId) {
            $q = $image ? "UPDATE promotions SET title=?,description=?,discount_pct=?,is_active=?,image=? WHERE id=?" : "UPDATE promotions SET title=?,description=?,discount_pct=?,is_active=? WHERE id=?";
            $params = $image ? [$title,$desc,$discount,$active,$image,$productId] : [$title,$desc,$discount,$active,$productId];
            $pdo->prepare($q)->execute($params);
        } else {
            // SQL Запрос: вставка данных
    $pdo->prepare("INSERT INTO promotions (title,description,discount_pct,is_active,image) VALUES (?,?,?,?,?)")->execute([$title,$desc,$discount,$active,$image]);
        }
    }
    // Перенаправление пользователя
header('Location: /shop/admin/promotions.php?saved=1'); exit;
}

$promos = $pdo->query("SELECT * FROM promotions ORDER BY id DESC")->fetchAll();
$editId = (int)($_GET['edit']??0);
$editPromo = null;
if ($editId) {
    foreach ($promos as $p) { if ($p['id']===$editId) { $editPromo=$p; break; } }
}
?>

<h1>🎁 Акции (<?= count($promos) ?>)</h1>
<?php if (isset($_GET['saved'])): ?><div class="alert alert-success">Сохранено ✅</div><?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 380px;gap:20px">
  <!-- Список -->
  <table class="admin-table">
    <thead><tr><th>Название</th><th>Скидка</th><th>Активна</th><th>Действия</th></tr></thead>
    <tbody>
      <?php if (empty($promos)): ?>
      <tr><td colspan="4" style="text-align:center;padding:20px;color:var(--text-muted)">Акций нет</td></tr>
      <?php endif; ?>
      <?php foreach ($promos as $p): ?>
      <tr>
        <td><strong><?= htmlspecialchars($p['title']) ?></strong></td>
        <td><?= $p['discount_pct'] > 0 ? '-'.$p['discount_pct'].'%' : '—' ?></td>
        <td><?= $p['is_active'] ? '✅' : '🚫' ?></td>
        <td style="white-space:nowrap">
          <a href="?edit=<?= $p['id'] ?>" class="btn-sm btn-edit">✏ Ред.</a>
          <form method="POST" style="display:inline">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= $p['id'] ?>">
            <button type="submit" class="btn-sm btn-delete js-confirm-delete">🗑</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <!-- Форма добавления/редактирования -->
  <div class="admin-form-card" style="height:fit-content">
    <h3 style="font-weight:700;margin-bottom:16px"><?= $editPromo ? '✏ Редактировать' : '➕ Новая акция' ?></h3>
    <form method="POST" action="" enctype="multipart/form-data">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="promo_id" value="<?= $editPromo['id']??0 ?>">
      <div class="form-group">
        <label>Название <span class="required">*</span></label>
        <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($editPromo['title']??'') ?>" required>
      </div>
      <div class="form-group">
        <label>Описание</label>
        <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($editPromo['description']??'') ?></textarea>
      </div>
      <div class="form-group">
        <label>Скидка, %</label>
        <input type="number" name="discount_pct" class="form-control" value="<?= $editPromo['discount_pct']??0 ?>" min="0" max="100">
      </div>
      <div class="form-group">
        <label>Изображение</label>
        <input type="file" name="image" class="form-control" accept="image/*">
      </div>
      <label style="display:flex;align-items:center;gap:10px;margin-bottom:16px;cursor:pointer">
        <input type="checkbox" name="is_active" <?= ($editPromo['is_active']??1)?'checked':'' ?>>
        <span>Активная</span>
      </label>
      <button type="submit" class="btn-primary"><?= $editPromo ? '💾 Сохранить' : '➕ Добавить' ?></button>
      <?php if ($editPromo): ?><a href="?" style="display:block;text-align:center;margin-top:8px;font-size:0.85rem;color:var(--text-muted)">Отмена</a><?php endif; ?>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
