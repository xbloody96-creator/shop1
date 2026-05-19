<?php
require_once __DIR__ . '/includes/auth.php';

// Если уже авторизован и прошёл 2FA — редирект
if (isLoggedIn() && is2FAVerified()) {
    header('Location: /profile.php');
    exit;
}

// Если нет пользователя в сессии — на логин
if (!isLoggedIn() && !isset($_SESSION['2fa_pending_user_id'])) {
    header('Location: /login.php');
    exit;
}

$userId = $_SESSION['2fa_pending_user_id'] ?? $_SESSION['user_id'] ?? 0;
$errors = [];
$success = false;

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code'] ?? '');
    
    if (empty($code) || !ctype_digit($code) || strlen($code) !== 6) {
        $errors[] = 'Введите 6-значный код';
    } elseif (verify2FACode($code, $userId)) {
        // Код верный — завершаем вход
        $_SESSION['2fa_verified'] = true;
        unset($_SESSION['2fa_pending_user_id']);
        
        // Редирект на профиль или туда, откуда пришёл
        $redirect = $_SESSION['login_redirect'] ?? '/profile.php';
        unset($_SESSION['login_redirect']);
        header('Location: ' . $redirect);
        exit;
    } else {
        $errors[] = 'Неверный или просроченный код';
    }
    
    // Отправить код повторно
    if (isset($_POST['resend']) && $userId) {
        $user = getCurrentUser() ?? $pdo->prepare("SELECT email FROM users WHERE id = ?")->execute([$userId])->fetch();
        if ($user) {
            send2FACode($user['email'], $userId);
            $success = true;
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>
<div class="container" style="max-width:420px;padding:60px 20px">
<div class="form-card">
<h1 style="text-align:center">🔐 Подтверждение входа</h1>
<p style="text-align:center;color:var(--text2);margin-bottom:24px">
Мы отправили 6-значный код на ваш email.<br>
Введите его для завершения входа.
</p>

<?php foreach ($errors as $e): ?>
<div class="alert alert-error"><?= htmlspecialchars($e) ?></div>
<?php endforeach; ?>

<?php if ($success): ?>
<div class="alert alert-success">✅ Код отправлен повторно</div>
<?php endif; ?>

<form method="POST">
<div class="form-group">
<label>Код из письма <span class="required">*</span></label>
<input type="text" name="code" class="form-control" 
placeholder="000000" 
pattern="\d{6}" maxlength="6" 
style="text-align:center;font-size:1.3rem;letter-spacing:8px;font-family:monospace"
autocomplete="one-time-code" required autofocus>
</div>

<button type="submit" class="btn-primary" style="margin-top:16px">
🔓 Подтвердить
</button>

<div style="text-align:center;margin-top:16px">
<button type="submit" name="resend" value="1" class="btn-secondary" style="width:auto;padding:8px 16px;font-size:0.8rem">
📧 Отправить код снова
</button>
</div>
</form>

<div style="text-align:center;margin-top:24px;font-size:0.8rem;color:var(--text3)">
Не получили письмо? Проверьте папку «Спам».<br>
<a href="/logout.php" style="color:var(--accent)">Выйти из аккаунта</a>
</div>
</div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>