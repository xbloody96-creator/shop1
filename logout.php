/**
 * Файл: logout.php
 * Описание: Страница сайта
 * @version 1.0
 */

<?php
// Подключение модуля аутентификации
require_once __DIR__ . '/includes/auth.php';
logoutUser();
// Перенаправление пользователя
header('Location: /shop/index.php');
exit;
