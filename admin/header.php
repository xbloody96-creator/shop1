<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
$settings = getSettings();

// Статистика для всех страниц
$stats = [
    'orders'   => (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'products' => (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn(),
    'users'    => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn(),
    'revenue'  => (float)$pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status != 'cancelled'")->fetchColumn(),
];

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="ru" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Админ-панель — <?= htmlspecialchars($settings['site_name']??'Магазин') ?></title>
<link rel="stylesheet" href="/shop/assets/css/style.css">
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
body { margin: 0; }
.admin-layout { display: grid; grid-template-columns: 240px 1fr; min-height: 100vh; }
</style>
</head>
<body>

<div class="admin-layout">
<!-- Сайдбар -->
<aside class="admin-sidebar">
  <div class="admin-brand">⚙ Админ-панель</div>
  <nav class="admin-nav">
    <a href="/shop/admin/index.php"      class="<?= $currentPage==='index'      ?'active':'' ?>">📊 Статистика</a>
    <a href="/shop/admin/products.php"   class="<?= $currentPage==='products'   ?'active':'' ?>">📦 Товары</a>
    <a href="/shop/admin/categories.php" class="<?= $currentPage==='categories' ?'active':'' ?>">🗂 Категории</a>
    <a href="/shop/admin/orders.php"     class="<?= $currentPage==='orders'     ?'active':'' ?>">🛒 Заказы</a>
    <a href="/shop/admin/users.php"      class="<?= $currentPage==='users'      ?'active':'' ?>">👥 Пользователи</a>
    <a href="/shop/admin/reviews.php"    class="<?= $currentPage==='reviews'    ?'active':'' ?>">💬 Отзывы</a>
    <a href="/shop/admin/news.php"       class="<?= $currentPage==='news'       ?'active':'' ?>">📰 Новости</a>
    <a href="/shop/admin/promotions.php" class="<?= $currentPage==='promotions' ?'active':'' ?>">🎁 Акции</a>
    <a href="/shop/admin/settings.php"   class="<?= $currentPage==='settings'   ?'active':'' ?>">⚙ Настройки</a>
    <a href="/shop/index.php" style="margin-top:20px;border-top:1px solid #2d3142;padding-top:16px">🌐 На сайт</a>
    <a href="/shop/profile.php?logout=1" style="color:#dc2626">🚪 Выйти</a>
  </nav>
</aside>

<!-- Основной контент -->
<main class="admin-content">
