<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'shop_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die('<div style="font-family:sans-serif;padding:40px;color:#c00;">
        <h2>Ошибка подключения к базе данных</h2>
        <p>' . htmlspecialchars($e->getMessage()) . '</p>
        <p>Убедитесь что XAMPP запущен и база данных <strong>shop_db</strong> существует.</p>
    </div>');
}
