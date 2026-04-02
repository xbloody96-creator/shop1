<?php
require_once __DIR__ . '/includes/auth.php';
if (isLoggedIn()) { header('Location: /shop/profile.php'); exit; }

$success = false;
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Введите корректный email';
    } else {
        $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $token   = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600); // 1 час

            // Сохраняем токен в сессии (упрощённо; в реальном проекте — отдельная таблица)
            $_SESSION['reset_token']   = $token;
            $_SESSION['reset_user_id'] = $user['id'];
            $_SESSION['reset_expires'] = $expires;

            $link    = "http://localhost/shop/reset_password.php?token=$token";
            $subject = 'Восстановление пароля — Магазин';
            $message = "Здравствуйте, {$user['full_name']}!\n\nДля сброса пароля перейдите по ссылке:\n$link\n\nСсылка действует 1 час.\n\nЕсли вы не запрашивали сброс, проигнорируйте это письмо.";
            @mail($email, $subject, $message, "From: noreply@magazin.ru\r\nContent-Type: text/plain; charset=UTF-8");
        }

        // Всегда показываем успех (чтобы не раскрывать наличие email)
        $success = true;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
  <div class="form-card">
    <h1>🔒 Забыли пароль?</h1>

    <?php if ($success): ?>
    <div class="alert alert-success">
      Если указанный email зарегистрирован, на него отправлено письмо с инструкцией по восстановлению пароля.
    </div>
    <p style="text-align:center;margin-top:16px">
      <a href="/shop/login.php" style="color:var(--accent2);font-weight:700">← Вернуться к входу</a>
    </p>

    <?php else: ?>
    <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <p style="color:var(--text-muted);margin-bottom:20px;font-size:0.9rem">
      Введите email, привязанный к вашему аккаунту. Мы отправим ссылку для сброса пароля.
    </p>

    <form method="POST" action="">
      <div class="form-group">
        <label>Email <span class="required">*</span></label>
        <input type="email" name="email" class="form-control" placeholder="your@email.com" required>
      </div>
      <button type="submit" class="btn-primary">Отправить ссылку</button>
    </form>

    <p style="text-align:center;margin-top:16px;font-size:0.88rem">
      <a href="/shop/login.php" style="color:var(--text-muted)">← Вернуться к входу</a>
    </p>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
