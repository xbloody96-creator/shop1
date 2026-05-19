/**
 * Файл: settings.php
 * Описание: Страница сайта
 * @version 1.0
 */

<?php require_once __DIR__ . '/header.php';

$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $keys = ['site_name','site_description','contact_phone','contact_email','contact_address','social_vk','social_tg','map_lat','map_lng'];
    foreach ($keys as $k) {
        $v = trim($_POST[$k] ?? '');
        // SQL Запрос: обновление данных
    $pdo->prepare("UPDATE settings SET setting_value=? WHERE setting_key=?")->execute([$v, $k]);
    }
    $success = true;
}

$s = getSettings();
?>

<h1>⚙ Настройки магазина</h1>
<?php if ($success): ?><div class="alert alert-success">Настройки сохранены ✅</div><?php endif; ?>

<form method="POST" action="">
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">

    <div class="admin-form-card">
      <h3 style="font-weight:700;margin-bottom:16px">🏪 Основное</h3>
      <div class="form-group">
        <label>Название сайта</label>
        <input type="text" name="site_name" class="form-control" value="<?= htmlspecialchars($s['site_name']??'') ?>">
      </div>
      <div class="form-group">
        <label>Описание</label>
        <textarea name="site_description" class="form-control" rows="3"><?= htmlspecialchars($s['site_description']??'') ?></textarea>
      </div>
    </div>

    <div class="admin-form-card">
      <h3 style="font-weight:700;margin-bottom:16px">📞 Контакты</h3>
      <div class="form-group">
        <label>Телефон</label>
        <input type="text" name="contact_phone" class="form-control" value="<?= htmlspecialchars($s['contact_phone']??'') ?>">
      </div>
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="contact_email" class="form-control" value="<?= htmlspecialchars($s['contact_email']??'') ?>">
      </div>
      <div class="form-group">
        <label>Адрес</label>
        <input type="text" name="contact_address" class="form-control" value="<?= htmlspecialchars($s['contact_address']??'') ?>">
      </div>
    </div>

    <div class="admin-form-card">
      <h3 style="font-weight:700;margin-bottom:16px">🌐 Соцсети</h3>
      <div class="form-group">
        <label>ВКонтакте (ссылка)</label>
        <input type="url" name="social_vk" class="form-control" value="<?= htmlspecialchars($s['social_vk']??'') ?>">
      </div>
      <div class="form-group">
        <label>Telegram (ссылка)</label>
        <input type="url" name="social_tg" class="form-control" value="<?= htmlspecialchars($s['social_tg']??'') ?>">
      </div>
    </div>

    <div class="admin-form-card">
      <h3 style="font-weight:700;margin-bottom:16px">🗺 Карта</h3>
      <div class="form-group">
        <label>Широта (lat)</label>
        <input type="text" name="map_lat" class="form-control" value="<?= htmlspecialchars($s['map_lat']??'') ?>">
      </div>
      <div class="form-group">
        <label>Долгота (lng)</label>
        <input type="text" name="map_lng" class="form-control" value="<?= htmlspecialchars($s['map_lng']??'') ?>">
      </div>
    </div>
  </div>

  <button type="submit" class="btn-primary" style="margin-top:20px;max-width:240px">💾 Сохранить настройки</button>
</form>

<?php require_once __DIR__ . '/footer.php'; ?>
