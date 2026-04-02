<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function isAdmin(): bool {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function getCurrentUser(): ?array {
    if (!isLoggedIn()) return null;
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: /shop/login.php');
        exit;
    }
}

function requireAdmin(): void {
    if (!isAdmin()) {
        header('Location: /shop/index.php');
        exit;
    }
}

function loginUser(array $user): void {
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_name'] = $user['full_name'];
    session_regenerate_id(true);
}

function logoutUser(): void {
    session_unset();
    session_destroy();
}

function getSettings(): array {
    global $pdo;
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    $rows = $stmt->fetchAll();
    $out = [];
    foreach ($rows as $row) {
        $out[$row['setting_key']] = $row['setting_value'];
    }
    return $out;
}

function cartCount(): int {
    if (!isLoggedIn()) return 0;
    global $pdo;
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(quantity),0) FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return (int)$stmt->fetchColumn();
}
