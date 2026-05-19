<?php
/**
 * Подключение к базе данных
 * 
 * @package Shop
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'shop_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // В продакшене лучше логировать ошибку в файл, а пользователю показывать общее сообщение
    error_log('Database connection failed: ' . $e->getMessage());
    die('<div style="font-family:sans-serif;padding:40px;color:#c00;">
        <h2>Ошибка подключения к базе данных</h2>
        <p>Попробуйте обновить страницу или обратиться в службу поддержки.</p>
    </div>');
}
