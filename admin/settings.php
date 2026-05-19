<?php require_once __DIR__ . '/header.php';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Основные настройки
    $keys = ['site_name','site_description','contact_phone','contact_email','contact_address','social_vk','social_tg','map_lat','map_lng'];
    foreach ($keys as $k) {
        $v = trim($_POST[$k] ?? '');
        $pdo->prepare("UPDATE settings SET setting_value=? WHERE setting_key=?")->execute([$v, $k]);
    }
    
    // Настройки бегущей строки
    $ticker_keys = ['ticker_enabled','ticker_text','ticker_speed','ticker_color','ticker_bg_color'];
    foreach ($ticker_keys as $k) {
        $v = trim($_POST[$k] ?? '');
        $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=?")
            ->execute([$k, $v, $v]);
    }
    
    $success = true;
}

$s = getSettings();

// Значения бегущей строки по умолчанию
$tickerEnabled = $s['ticker_enabled'] ?? '1';
$tickerText = $s['ticker_text'] ?? '★ Бесплатная доставка от 3 000 ₽ ★ Гарантия 1 год на всю технику ★ Работаем 24/7 ★';
$tickerSpeed = $s['ticker_speed'] ?? '30';
$tickerColor = $s['ticker_color'] ?? '#ffffff';
$tickerBgColor = $s['ticker_bg_color'] ?? '#e63946';
?>
<h1>⚙ Настройки магазина</h1>

<?php if ($success): ?>
    <div class="alert alert-success">Настройки сохранены ✅</div>
<?php endif; ?>

<form method="POST" action="">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
        <!-- Основное -->
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
        
        <!-- Контакты -->
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
        
        <!-- Бегущая строка -->
        <div class="admin-form-card" style="border:2px solid var(--accent)">
            <h3 style="font-weight:700;margin-bottom:16px;color:var(--accent)">📢 Бегущая строка</h3>
            
            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;margin-bottom:16px">
                <input type="checkbox" name="ticker_enabled" value="1" <?= $tickerEnabled ? 'checked' : '' ?>>
                <span style="font-weight:600">Включить бегущую строку</span>
            </label>
            
            <div class="form-group">
                <label>Текст строки</label>
                <input type="text" name="ticker_text" class="form-control" value="<?= htmlspecialchars($tickerText) ?>" 
                       placeholder="★ Акция ★ Скидки ★ Доставка ★">
                <p style="font-size:0.78rem;color:var(--text-muted);margin-top:4px">
                    Используйте ★ или ● для разделения сообщений
                </p>
            </div>
            
            <div class="form-group">
                <label>Скорость (секунды на прокрутку)</label>
                <input type="number" name="ticker_speed" class="form-control" value="<?= htmlspecialchars($tickerSpeed) ?>" min="10" max="120">
            </div>
            
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div class="form-group">
                    <label>Цвет текста</label>
                    <input type="color" name="ticker_color" class="form-control" value="<?= htmlspecialchars($tickerColor) ?>" style="height:40px;padding:2px">
                </div>
                <div class="form-group">
                    <label>Цвет фона</label>
                    <input type="color" name="ticker_bg_color" class="form-control" value="<?= htmlspecialchars($tickerBgColor) ?>" style="height:40px;padding:2px">
                </div>
            </div>
            
            <!-- Предпросмотр -->
            <div style="margin-top:16px;padding:12px;background:<?= htmlspecialchars($tickerBgColor) ?>;border-radius:6px;overflow:hidden">
                <div style="white-space:nowrap;animation:ticker-preview <?= htmlspecialchars($tickerSpeed) ?>s linear infinite" 
                     id="ticker-preview">
                    <span style="color:<?= htmlspecialchars($tickerColor) ?>;font-weight:600;font-size:0.85rem">
                        <?= htmlspecialchars($tickerText) ?>
                    </span>
                </div>
            </div>
            <p style="font-size:0.75rem;color:var(--text-muted);margin-top:8px">
                👆 Предпросмотр бегущей строки
            </p>
        </div>
        
        <!-- Соцсети -->
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
        
        <!-- Карта -->
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

<style>
@keyframes ticker-preview {
    0% { transform: translateX(100%); }
    100% { transform: translateX(-100%); }
}
</style>

<?php require_once __DIR__ . '/footer.php'; ?>