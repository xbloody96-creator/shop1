<?php
require_once __DIR__ . '/includes/auth.php';
if (isLoggedIn()) { header('Location: /profile.php'); exit; }

$token   = $_GET['token'] ?? '';
$valid   = false;
$userId  = null;
$errors  = [];
$success = false;

// Проверяем токен из сессии
if ($token && isset($_SESSION['reset_token'], $_SESSION['reset_expires'], $_SESSION['reset_user_id'])) {
    if ($token === $_SESSION['reset_token'] && strtotime($_SESSION['reset_expires']) > time()) {
        $valid  = true;
        $userId = $_SESSION['reset_user_id'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid) {
    $password  = $_POST['password'] ?? '';
    $password2 = $_POST['password_confirm'] ?? '';

    if (strlen($password) < 6) $errors[] = 'Пароль минимум 6 символов';
    if ($password !== $password2) $errors[] = 'Пароли не совпадают';

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([$hash, $userId]);

        // Сбрасываем токен
        unset($_SESSION['reset_token'], $_SESSION['reset_user_id'], $_SESSION['reset_expires']);
        $success = true;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
  <div class="form-card">
    <h1>🔑 Новый пароль</h1>

    <?php if ($success): ?>
    <div class="alert alert-success">Пароль успешно изменён!</div>
    <a href="/login.php" class="btn-primary" style="display:block;text-align:center;margin-top:16px">Войти</a>

    <?php elseif (!$valid): ?>
    <div class="alert alert-error">
      Ссылка недействительна или устарела. Запросите новую ссылку для восстановления.
    </div>
    <a href="/forgot_password.php" class="btn-secondary" style="display:block;text-align:center;margin-top:12px">Запросить снова</a>

    <?php else: ?>
    <?php foreach ($errors as $e): ?><div class="alert alert-error"><?= htmlspecialchars($e) ?></div><?php endforeach; ?>

    <form method="POST" action="">
      <div class="form-group">
        <label>Новый пароль <span class="required">*</span></label>
        <input type="password" name="password" class="form-control" placeholder="Минимум 6 символов" required minlength="6">
      </div>
      <div class="form-group">
        <label>Повторите пароль <span class="required">*</span></label>
        <input type="password" name="password_confirm" class="form-control" placeholder="Повторите пароль" required>
        <span class="field-error"></span>
      </div>
      <button type="submit" class="btn-primary">Сохранить пароль</button>
    </form>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
