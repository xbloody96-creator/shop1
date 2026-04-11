<?php
/**
 * Страница входа пользователя
 * 
 * @package Shop
 */

require_once __DIR__ . '/includes/auth.php';

// Если пользователь уже авторизован — перенаправляем в профиль
if (isLoggedIn()) {
    header('Location: /shop/profile.php');
    exit;
}

$errors = [];
$email  = '';

// Обработка формы входа
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Валидация входных данных
    if (empty($email)) {
        $errors[] = 'Введите email или логин';
    }
    if (empty($password)) {
        $errors[] = 'Введите пароль';
    }

    // Если ошибок нет, пытаемся авторизовать
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR login = ? LIMIT 1");
        $stmt->execute([$email, $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            loginUser($user);
            
            // Сохраняем информацию о сессии в БД для аудита
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $token = bin2hex(random_bytes(32));
            
            $stmt = $pdo->prepare("INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent) VALUES (?,?,?,?)");
            $stmt->execute([$user['id'], $token, $ip, $ua]);

            // Перенаправление на страницу, с которой пришёл пользователь, или на главную
            $redirect = $_GET['redirect'] ?? '/shop/index.php';
            header('Location: ' . $redirect);
            exit;
        } else {
            $errors[] = 'Неверный email/логин или пароль';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
  <div class="form-card">
    <h1>🔑 Вход в аккаунт</h1>

    <?php foreach ($errors as $e): ?>
    <div class="alert alert-error"><?= htmlspecialchars($e) ?></div>
    <?php endforeach; ?>

    <?php if (isset($_GET['registered'])): ?>
    <div class="alert alert-success">Регистрация прошла успешно! Теперь вы можете войти.</div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="form-group">
        <label>Email или логин <span class="required">*</span></label>
        <input type="text" name="email" class="form-control <?= !empty($errors) ? 'error' : '' ?>"
               value="<?= htmlspecialchars($email) ?>" placeholder="your@email.com" required>
      </div>

      <div class="form-group">
        <label>Пароль <span class="required">*</span></label>
        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
      </div>

      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:0.88rem">
          <input type="checkbox" name="remember"> Запомнить меня
        </label>
        <a href="/shop/forgot_password.php" style="font-size:0.88rem;color:var(--accent2)">Забыли пароль?</a>
      </div>

      <button type="submit" class="btn-primary">Войти</button>
    </form>

    <p style="text-align:center;margin-top:20px;font-size:0.9rem;color:var(--text-muted)">
      Нет аккаунта? 
      <a href="/shop/register.php" style="color:var(--accent2);font-weight:700">Зарегистрироваться</a>
    </p>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
