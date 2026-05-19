<?php
/**
 * Система аутентификации пользователей
 * 
 * @package Shop
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

/**
 * Проверка авторизации пользователя
 * @return bool
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

/**
 * Проверка прав администратора
 * @return bool
 */
function isAdmin(): bool {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Получение данных текущего пользователя
 * @return array|null
 */
function getCurrentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }
    
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    return $user ?: null;
}

/**
 * Требует авторизацию для доступа к странице
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: /shop/login.php');
        exit;
    }
}

/**
 * Требует права администратора
 */
function requireAdmin(): void {
    if (!isAdmin()) {
        header('Location: /shop/index.php');
        exit;
    }
}

/**
 * Вход пользователя в систему
 * @param array $user Данные пользователя
 */
function loginUser(array $user): void {
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_name'] = $user['full_name'];
    
    // Регенерируем ID сессии для безопасности
    session_regenerate_id(true);
}

/**
 * Выход из системы
 */
function logoutUser(): void {
    session_unset();
    session_destroy();
}

/**
 * Получение настроек сайта
 * @return array
 */
function getSettings(): array {
    global $pdo;
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    $rows = $stmt->fetchAll();
    
    $settings = [];
    foreach ($rows as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    
    return $settings;
}

/**
 * Подсчёт товаров в корзине пользователя
 * @return int
 */
function cartCount(): int {
    if (!isLoggedIn()) {
        return 0;
    }
    
    global $pdo;
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(quantity), 0) FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    
    return (int) $stmt->fetchColumn();
}
