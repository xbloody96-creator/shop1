<?php require_once __DIR__ . '/header.php';

$id      = (int)($_GET['id']??0);
$product = null;
$specs   = [];
$gallery = [];

if ($id) {
    $s = $pdo->prepare("SELECT * FROM products WHERE id=?");
    $s->execute([$id]);
    $product = $s->fetch();
    if (!$product) { header('Location: /admin/products.php'); exit; }

    $sp = $pdo->prepare("SELECT * FROM product_specs WHERE product_id=? ORDER BY id");
    $sp->execute([$id]);
    $specs = $sp->fetchAll();

    $gi = $pdo->prepare("SELECT * FROM product_images WHERE product_id=? ORDER BY sort_order");
    $gi->execute([$id]);
    $gallery = $gi->fetchAll();
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name']??'');
    $description = trim($_POST['description']??'');
    $price       = (float)($_POST['price']??0);
    $oldPrice    = !empty($_POST['old_price']) ? (float)$_POST['old_price'] : null;
    $stock       = (int)($_POST['stock']??0);
    $categoryId  = (int)($_POST['category_id']??0) ?: null;
    $isPopular   = isset($_POST['is_popular']) ? 1 : 0;
    $isActive    = isset($_POST['is_active'])  ? 1 : 0;

    if (empty($name))  $errors[] = 'Введите название товара';
    if ($price <= 0)   $errors[] = 'Введите корректную цену';

    // Slug
    $slug = mb_strtolower(trim(preg_replace('/[^a-zA-Z0-9а-яёА-ЯЁ\-]/u', '-', $name), '-'));
    $slug = preg_replace('/-+/', '-', $slug);
    if (empty($slug)) $slug = 'product-' . time();

    // Загрузка главного изображения
    $mainImage = $product['main_image'] ?? null;
    if (!empty($_FILES['main_image']['name'])) {
        $ext = strtolower(pathinfo($_FILES['main_image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','webp','gif'])) {
            $uploadDir = __DIR__ . '/../uploads/products/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $fname = 'prod_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['main_image']['tmp_name'], $uploadDir . $fname)) {
                if ($mainImage && file_exists(__DIR__ . '/../uploads/' . $mainImage)) unlink(__DIR__ . '/../uploads/' . $mainImage);
                $mainImage = 'products/' . $fname;
            }
        } else {
            $errors[] = 'Недопустимый формат изображения';
        }
    }

    if (empty($errors)) {
        if ($id) {
            $pdo->prepare("UPDATE products SET name=?,slug=?,description=?,price=?,old_price=?,stock=?,category_id=?,main_image=?,is_popular=?,is_active=? WHERE id=?")
                ->execute([$name,$slug,$description,$price,$oldPrice,$stock,$categoryId,$mainImage,$isPopular,$isActive,$id]);
        } else {
            $pdo->prepare("INSERT INTO products (name,slug,description,price,old_price,stock,category_id,main_image,is_popular,is_active) VALUES (?,?,?,?,?,?,?,?,?,?)")
                ->execute([$name,$slug,$description,$price,$oldPrice,$stock,$categoryId,$mainImage,$isPopular,$isActive]);
            $id = (int)$pdo->lastInsertId();
        }

        // Характеристики — удаляем старые и вставляем новые
        $pdo->prepare("DELETE FROM product_specs WHERE product_id=?")->execute([$id]);
        $specKeys = $_POST['spec_key']   ?? [];
        $specVals = $_POST['spec_value'] ?? [];
        foreach ($specKeys as $i => $k) {
            $k = trim($k); $v = trim($specVals[$i]??'');
            if ($k && $v) {
                $pdo->prepare("INSERT INTO product_specs (product_id,spec_key,spec_value) VALUES (?,?,?)")->execute([$id,$k,$v]);
            }
        }

        // Галерея
        if (!empty($_FILES['gallery']['name'][0])) {
            $uploadDir = __DIR__ . '/../uploads/products/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            foreach ($_FILES['gallery']['tmp_name'] as $idx => $tmp) {
                if (empty($tmp)) continue;
                $ext  = strtolower(pathinfo($_FILES['gallery']['name'][$idx], PATHINFO_EXTENSION));
                if (!in_array($ext,['jpg','jpeg','png','webp','gif'])) continue;
                $fname = 'gal_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($tmp, $uploadDir . $fname)) {
                    $pdo->prepare("INSERT INTO product_images (product_id,image,sort_order) VALUES (?,?,?)")->execute([$id,'products/'.$fname,$idx]);
                }
            }
        }

        header("Location: /admin/products.php?saved=1"); exit;
    }
}
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
  <h1><?= $id ? '✏ Редактировать товар' : '➕ Добавить товар' ?></h1>
  <a href="/admin/products.php" style="color:var(--text-muted);font-size:0.9rem">← Назад к списку</a>
</div>

<?php foreach($errors as $e): ?><div class="alert alert-error"><?= htmlspecialchars($e) ?></div><?php endforeach; ?>

<form method="POST" action="" enctype="multipart/form-data">
  <div style="display:grid;grid-template-columns:1fr 340px;gap:20px">

    <!-- Левая колонка -->
    <div style="display:flex;flex-direction:column;gap:16px">

      <!-- Основная информация -->
      <div class="admin-form-card">
        <h3 style="font-weight:700;margin-bottom:16px">📋 Основная информация</h3>
        <div class="form-group">
          <label>Название <span class="required">*</span></label>
          <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($product['name']??'') ?>" required>
        </div>
        <div class="form-group">
          <label>Описание</label>
          <textarea name="description" class="form-control" rows="6"><?= htmlspecialchars($product['description']??'') ?></textarea>
        </div>
        <div class="form-group">
          <label>Категория</label>
          <select name="category_id" class="form-control">
            <option value="">— Без категории —</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= ($product['category_id']??'')==$cat['id']?'selected':'' ?>>
              <?= htmlspecialchars($cat['name']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <!-- Характеристики -->
      <div class="admin-form-card">
        <h3 style="font-weight:700;margin-bottom:16px">📊 Характеристики</h3>
        <div id="specs-container">
          <?php if (!empty($specs)): ?>
          <?php foreach ($specs as $spec): ?>
          <div style="display:grid;grid-template-columns:1fr 1fr auto;gap:8px;margin-bottom:8px" class="spec-row">
            <input type="text" name="spec_key[]" class="form-control" placeholder="Характеристика" value="<?= htmlspecialchars($spec['spec_key']) ?>">
            <input type="text" name="spec_value[]" class="form-control" placeholder="Значение" value="<?= htmlspecialchars($spec['spec_value']) ?>">
            <button type="button" onclick="this.closest('.spec-row').remove()" style="padding:8px 12px;background:#fee2e2;color:#dc2626;border-radius:6px;border:none;cursor:pointer">✕</button>
          </div>
          <?php endforeach; ?>
          <?php else: ?>
          <div style="display:grid;grid-template-columns:1fr 1fr auto;gap:8px;margin-bottom:8px" class="spec-row">
            <input type="text" name="spec_key[]" class="form-control" placeholder="Например: Процессор">
            <input type="text" name="spec_value[]" class="form-control" placeholder="Например: Intel i7">
            <button type="button" onclick="this.closest('.spec-row').remove()" style="padding:8px 12px;background:#fee2e2;color:#dc2626;border-radius:6px;border:none;cursor:pointer">✕</button>
          </div>
          <?php endif; ?>
        </div>
        <button type="button" id="add-spec" style="margin-top:8px;padding:8px 16px;border:2px dashed var(--border);border-radius:6px;background:transparent;cursor:pointer;color:var(--text-muted);font-size:0.88rem">
          ➕ Добавить характеристику
        </button>
      </div>

      <!-- Галерея -->
      <div class="admin-form-card">
        <h3 style="font-weight:700;margin-bottom:16px">🖼 Галерея</h3>
        <?php if (!empty($gallery)): ?>
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px">
          <?php foreach ($gallery as $img): ?>
          <div style="position:relative">
            <img src="/uploads/<?= htmlspecialchars($img['image']) ?>" style="width:80px;height:80px;object-fit:contain;border:1px solid var(--border);border-radius:6px;padding:4px">
            <a href="/admin/product_edit.php?id=<?= $id ?>&del_img=<?= $img['id'] ?>" 
               style="position:absolute;top:-4px;right:-4px;background:#dc2626;color:#fff;border-radius:50%;width:18px;height:18px;display:flex;align-items:center;justify-content:center;font-size:0.7rem;text-decoration:none">✕</a>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <input type="file" name="gallery[]" multiple accept="image/*" class="form-control">
        <p style="font-size:0.78rem;color:var(--text-muted);margin-top:4px">Можно выбрать несколько фото</p>
      </div>
    </div>

    <!-- Правая колонка -->
    <div style="display:flex;flex-direction:column;gap:16px">

      <!-- Цена и остаток -->
      <div class="admin-form-card">
        <h3 style="font-weight:700;margin-bottom:16px">💰 Цена и остаток</h3>
        <div class="form-group">
          <label>Цена, ₽ <span class="required">*</span></label>
          <input type="number" name="price" class="form-control" value="<?= $product['price']??'' ?>" step="0.01" min="0" required>
        </div>
        <div class="form-group">
          <label>Старая цена, ₽ <small style="color:var(--text-muted)">(для зачёркнутой)</small></label>
          <input type="number" name="old_price" class="form-control" value="<?= $product['old_price']??'' ?>" step="0.01" min="0">
        </div>
        <div class="form-group">
          <label>Остаток, шт.</label>
          <input type="number" name="stock" class="form-control" value="<?= $product['stock']??0 ?>" min="0">
        </div>
      </div>

      <!-- Главное изображение -->
      <div class="admin-form-card">
        <h3 style="font-weight:700;margin-bottom:16px">🖼 Главное фото</h3>
        <?php if (!empty($product['main_image'])): ?>
        <img src="/uploads/<?= htmlspecialchars($product['main_image']) ?>" id="admin-img-preview"
             style="width:100%;max-height:200px;object-fit:contain;border:1px solid var(--border);border-radius:8px;padding:8px;margin-bottom:12px;background:var(--surface2)">
        <?php else: ?>
        <img id="admin-img-preview" style="display:none;width:100%;max-height:200px;object-fit:contain;border:1px solid var(--border);border-radius:8px;padding:8px;margin-bottom:12px">
        <?php endif; ?>
        <input type="file" name="main_image" id="admin-img-input" accept="image/*" class="form-control">
      </div>

      <!-- Настройки -->
      <div class="admin-form-card">
        <h3 style="font-weight:700;margin-bottom:16px">⚙ Настройки</h3>
        <label style="display:flex;align-items:center;gap:10px;cursor:pointer;padding:8px 0">
          <input type="checkbox" name="is_active" <?= ($product['is_active']??1) ? 'checked' : '' ?>>
          <span>Активный (отображается на сайте)</span>
        </label>
        <label style="display:flex;align-items:center;gap:10px;cursor:pointer;padding:8px 0">
          <input type="checkbox" name="is_popular" <?= ($product['is_popular']??0) ? 'checked' : '' ?>>
          <span>Хит продаж (в слайдере)</span>
        </label>
      </div>

      <button type="submit" class="btn-primary">
        <?= $id ? '💾 Сохранить изменения' : '➕ Добавить товар' ?>
      </button>
      <?php if ($id): ?>
      <a href="/product.php?id=<?= $id ?>" target="_blank" class="btn-secondary" style="text-align:center">👁 Просмотр на сайте</a>
      <?php endif; ?>
    </div>
  </div>
</form>

<script>
document.getElementById('add-spec')?.addEventListener('click', () => {
  const c = document.getElementById('specs-container');
  const row = document.createElement('div');
  row.className = 'spec-row';
  row.style.cssText = 'display:grid;grid-template-columns:1fr 1fr auto;gap:8px;margin-bottom:8px';
  row.innerHTML = `
    <input type="text" name="spec_key[]" class="form-control" placeholder="Характеристика">
    <input type="text" name="spec_value[]" class="form-control" placeholder="Значение">
    <button type="button" onclick="this.closest('.spec-row').remove()" style="padding:8px 12px;background:#fee2e2;color:#dc2626;border-radius:6px;border:none;cursor:pointer">✕</button>
  `;
  c.appendChild(row);
});
</script>

<?php
// Удаление фото из галереи
if (isset($_GET['del_img'])) {
    $imgId = (int)$_GET['del_img'];
    $imgRow = $pdo->prepare("SELECT * FROM product_images WHERE id=? AND product_id=?");
    $imgRow->execute([$imgId, $id]);
    $imgData = $imgRow->fetch();
    if ($imgData) {
        if (file_exists(__DIR__ . '/../uploads/' . $imgData['image'])) unlink(__DIR__ . '/../uploads/' . $imgData['image']);
        $pdo->prepare("DELETE FROM product_images WHERE id=?")->execute([$imgId]);
    }
    header("Location: /admin/product_edit.php?id=$id"); exit;
}
?>

<?php require_once __DIR__ . '/footer.php'; ?>
