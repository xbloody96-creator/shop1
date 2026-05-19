<?php
/**
 * Проверка прохождения 2FA для пользователей с включенной двухфакторной аутентификацией
 * Подключать в начале всех защищенных страниц после auth.php
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require2FAVerified(): void {
    // Если пользователь не авторизован - редирект на логин
    if (!isset($_SESSION['user_id'])) {
        header('Location: /login.php');
        exit;
    }
    
    // Проверяем, нужна ли пользователю 2FA
    $userId = $_SESSION['user_id'];
    
    // Если есть pending_user_id, значит 2FA еще не пройдена
    if (isset($_SESSION['2fa_pending_user_id'])) {
        header('Location: /2fa_verify.php');
        exit;
    }
    
    // Проверяем, включена ли 2FA у пользователя
    global $pdo;
    if (!$pdo) {
        require_once __DIR__ . '/db.php';
    }
    
    $stmt = $pdo->prepare("SELECT two_factor_enabled FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetch();
    
    // Если 2FA включена, но не верифицирована - редирект на страницу ввода кода
    if ($result && $result['two_factor_enabled'] == 1) {
        if (!isset($_SESSION['2fa_verified']) || $_SESSION['2fa_verified'] !== true) {
            // Сохраняем текущую страницу для редиректа после успешной 2FA
            $_SESSION['login_redirect'] = $_SERVER['REQUEST_URI'];
            header('Location: /2fa_verify.php');
            exit;
        }
    }
}
