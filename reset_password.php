/**
 * Файл: reset_password.php
 * Описание: Страница сайта
 * @version 1.0
 */

<?php
/**
 * Страница установки нового пароля
 * Проверяет токен из письма и позволяет установить новый пароль
 */
// Подключение модуля аутентификации
require_once __DIR__ . '/includes/auth.php';

// Если пользователь уже авторизован — перенаправляем в профиль
if (isLoggedIn()) {
    // Перенаправление пользователя
header('Location: /shop/profile.php');
    exit;
}

// Получаем токен из URL
$resetToken = $_GET['token'] ?? '';
$isValid = false;
$userId = null;
$errors = [];
$success = false;

// Проверяем токен из сессии (должен совпадать с тем, что в ссылке)
if ($resetToken && isset($_SESSION['reset_token'], $_SESSION['reset_expires'], $_SESSION['reset_user_id'])) {
    // Проверяем совпадение токена и не истёк ли срок действия
    if ($resetToken === $_SESSION['reset_token'] 
        && strtotime($_SESSION['reset_expires']) > time()) {
        
        $isValid = true;
        $userId = $_SESSION['reset_user_id'];
    }
}

// Обработка формы установки нового пароля
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isValid) {
    $newPassword = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    // Валидация пароля
    if (strlen($newPassword) < 6) {
        $errors[] = 'Пароль должен содержать минимум 6 символов';
    }
    if ($newPassword !== $passwordConfirm) {
        $errors[] = 'Пароли не совпадают';
    }

    // Если ошибок нет — обновляем пароль
    if (empty($errors)) {
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        // SQL Запрос: обновление данных
    $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")
            ->execute([$passwordHash, $userId]);

        // Очищаем данные сброса из сессии
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
            <!-- Пароль успешно изменён -->
            <div class="alert alert-success">
                Пароль успешно изменён! Теперь вы можете войти с новым паролем.
            </div>
            <a href="/shop/login.php" class="btn-primary" 
               style="display:block;text-align:center;margin-top:16px;text-decoration:none">
                Войти в аккаунт
            </a>

        <?php elseif (!$isValid): ?>
            <!-- Токен недействителен или истёк -->
            <div class="alert alert-error">
                Ссылка недействительна или устарела. Срок действия ссылки — 1 час.
                Запросите новую ссылку для восстановления пароля.
            </div>
            <a href="/shop/forgot_password.php" class="btn-secondary" 
               style="display:block;text-align:center;margin-top:12px;text-decoration:none">
                Запросить новую ссылку
            </a>

        <?php else: ?>
            <!-- Форма установки нового пароля -->
            <?php foreach ($errors as $errorItem): ?>
                <div class="alert alert-error"><?= htmlspecialchars($errorItem) ?></div>
            <?php endforeach; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label>Новый пароль <span class="required">*</span></label>
                    <input type="password" name="password" class="form-control" 
                           placeholder="Минимум 6 символов" required minlength="6">
                </div>
                
                <div class="form-group">
                    <label>Повторите пароль <span class="required">*</span></label>
                    <input type="password" name="password_confirm" class="form-control" 
                           placeholder="Повторите пароль" required>
                    <span class="field-error"></span>
                </div>
                
                <button type="submit" class="btn-primary">Сохранить новый пароль</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
