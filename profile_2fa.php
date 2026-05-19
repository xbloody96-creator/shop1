<?php
// В profile.php добавьте после проверки авторизации:

// Обработка включения/выключения 2FA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_2fa'])) {
    $action = $_POST['toggle_2fa'];
    $userId = $_SESSION['user_id'];
    
    if ($action === 'enable') {
        // Включаем 2FA
        $pdo->prepare("UPDATE users SET two_factor_enabled = 1 WHERE id = ?")->execute([$userId]);
        $message = '✅ Двухфакторная аутентификация включена';
    } elseif ($action === 'disable') {
        // Для отключения можно запросить пароль
        $password = $_POST['password'] ?? '';
        $user = getCurrentUser();
        if (password_verify($password, $user['password'])) {
            $pdo->prepare("UPDATE users SET two_factor_enabled = 0 WHERE id = ?")->execute([$userId]);
            $message = '✅ Двухфакторная аутентификация отключена';
        } else {
            $error = '❌ Неверный пароль';
        }
    }
}

// Получаем статус 2FA
$user = getCurrentUser();
$twoFactorEnabled = $user['two_factor_enabled'] ?? 0;
?>

<!-- Секция 2FA в профиле -->
<div class="profile-section">
<h3>🔐 Двухфакторная аутентификация</h3>
<p style="font-size:0.85rem;color:var(--text3);margin-bottom:16px">
Дополнительная защита аккаунта. При входе потребуется код из email.
</p>

<?php if (isset($message)): ?>
<div class="alert alert-success"><?= $message ?></div>
<?php endif; ?>
<?php if (isset($error)): ?>
<div class="alert alert-error"><?= $error ?></div>
<?php endif; ?>

<form method="POST">
<?php if ($twoFactorEnabled): ?>
<div style="display:flex;align-items:center;gap:12px;margin-bottom:16px">
<span style="color:var(--success);font-weight:600">✓ Включено</span>
</div>
<div class="form-group">
<label>Подтвердите пароль для отключения</label>
<input type="password" name="password" class="form-control" placeholder="••••••••" required>
</div>
<button type="submit" name="toggle_2fa" value="disable" class="btn-secondary" style="width:auto">
🔓 Отключить 2FA
</button>
<?php else: ?>
<div style="display:flex;align-items:center;gap:12px;margin-bottom:16px">
<span style="color:var(--text3)">✗ Выключено</span>
</div>
<button type="submit" name="toggle_2fa" value="enable" class="btn-primary" style="width:auto">
🔐 Включить 2FA
</button>
<?php endif; ?>
</form>
</div>