<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

// Получаем текущего пользователя
if (isLoggedIn()) {
    $user = getCurrentUser();
    
    // Делаем его админом
    $pdo->prepare("UPDATE users SET role = 'admin' WHERE id = ?")
        ->execute([$user['id']]);
    
    echo "✅ Пользователь #{$user['id']} ({$user['login']}) теперь АДМИНИСТРАТОР!";
    echo "<br><a href='/admin/users.php'>Перейти в админку</a>";
} else {
    echo "❌ Сначала войдите в систему";
    echo "<br><a href='/login.php'>Войти</a>";
}