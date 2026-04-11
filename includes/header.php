<?php
/**
 * Шапка сайта (Header)
 * 
 * Подключает авторизацию, загружает настройки, корзину и категории
 * для отображения верхнего меню и навигации.
 */

require_once __DIR__ . '/auth.php';

// Получаем основные данные для отображения в шапке
$settings = getSettings();       // Настройки сайта (название, телефон и т.д.)
$cartQty  = cartCount();         // Количество товаров в корзине
$user     = getCurrentUser();    // Данные текущего пользователя

// Загружаем корневые категории для меню каталога
$cats = $pdo->query("SELECT * FROM categories WHERE parent_id IS NULL ORDER BY sort_order")->fetchAll();

// Иконки для категорий (циклически повторяются если категорий больше 10)
$catIcons = ['📱','💻','📺','🎧','⌨️','🖥️','📷','🎮','🔋','🖨️'];
?>
<!DOCTYPE html>
<html lang="ru" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($settings['site_name'] ?? 'Магазин') ?> — Лучшие цены на технику</title>
    <link rel="stylesheet" href="/shop/assets/css/style.css">
</head>
<body>

<!-- ============================================
     ВЕРХНЯЯ ПАНЕЛЬ (Topbar)
     Содержит бегущую строку с преимуществами
     и контактный телефон
============================================= -->
<div class="header-topbar">
    <div class="topbar-inner">
        <!-- Бегущая строка с информацией для клиентов -->
        <div style="overflow:hidden;flex:1">
            <div class="topbar-scroll">
                <span>🚚 Бесплатная доставка от 3 000 ₽</span>
                <span>🛡️ Гарантия 1 год на всю технику</span>
                <span>📦 Более 5 000 товаров в наличии</span>
                <span>⏰ Работаем 24/7</span>
                <!-- Дублируем для бесшовной прокрутки -->
                <span>🚚 Бесплатная доставка от 3 000 ₽</span>
                <span>🛡️ Гарантия 1 год на всю технику</span>
                <span>📦 Более 5 000 товаров в наличии</span>
                <span>⏰ Работаем 24/7</span>
            </div>
        </div>
        
        <!-- Правая часть: телефон и ссылка на новости -->
        <div class="topbar-right">
            <a href="tel:<?= htmlspecialchars($settings['contact_phone'] ?? '') ?>">
                📞 <?= htmlspecialchars($settings['contact_phone'] ?? '') ?>
            </a>
            <a href="/shop/news.php">📰 Новости</a>
        </div>
    </div>
</div>

<!-- ============================================
     ОСНОВНАЯ ШАПКА (Header)
     Логотип, поиск, кнопки действий пользователя
============================================= -->
<header class="site-header">
    <div class="header-inner container">

        <!-- Логотип магазина -->
        <a href="/shop/index.php" class="logo">
            <div class="logo-mark">🛒</div>
            <div class="logo-text">
                <?php
                $name = htmlspecialchars($settings['site_name'] ?? 'Магазин');
                // Выделяем первую букву названия красным цветом
                $firstChar = mb_substr($name, 0, 1);
                $restChars = mb_substr($name, 1);
                echo '<em>' . $firstChar . '</em>' . $restChars;
                ?>
            </div>
        </a>

        <!-- Форма поиска по товарам -->
        <form class="header-search" action="/shop/search.php" method="GET" autocomplete="off">
            <input 
                type="text" 
                name="q" 
                id="header-search-input"
                placeholder="Поиск: смартфоны, ноутбуки, телевизоры..."
                value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
            >
            <button type="submit" title="Найти">🔍</button>
            <!-- Контейнер для выпадающих подсказок -->
            <div class="search-suggestions" id="search-suggestions"></div>
        </form>

        <!-- Навигация пользователя: вход, корзина, избранное -->
        <nav class="header-actions">
            <?php if (isLoggedIn()): ?>
                <!-- Пользователь авторизован: показываем ссылку на профиль -->
                <a href="/shop/profile.php" class="btn-icon">
                    <span class="icon">👤</span>
                    <small><?= htmlspecialchars(mb_substr($user['nickname'] ?? 'Кабинет', 0, 8)) ?></small>
                </a>
            <?php else: ?>
                <!-- Пользователь не вошёл: кнопки входа и регистрации -->
                <a href="/shop/login.php" class="btn-icon">
                    <span class="icon">🔑</span><small>Войти</small>
                </a>
                <a href="/shop/register.php" class="btn-icon">
                    <span class="icon">📝</span><small>Регистрация</small>
                </a>
            <?php endif; ?>

            <!-- Избранные товары -->
            <a href="/shop/favorites.php" class="btn-icon">
                <span class="icon">❤️</span><small>Избранное</small>
            </a>

            <!-- Корзина с индикатором количества -->
            <a href="/shop/cart.php" class="btn-icon cart-btn">
                <span class="icon">🛒</span>
                <?php if ($cartQty > 0): ?>
                    <sup class="cart-badge"><?= $cartQty ?></sup>
                <?php endif; ?>
                <small>Корзина</small>
            </a>

            <!-- Переключатель темы (светлая/тёмная) -->
            <button class="btn-icon" id="themeToggle" title="Переключить тему">
                <span class="icon" id="themeIcon">☀️</span><small>Тема</small>
            </button>

            <!-- Режим для слабовидящих -->
            <button class="btn-icon" id="accessibilityBtn" title="Версия для слабовидящих">
                <span class="icon">🔎</span><small>A+</small>
            </button>
        </nav>
    </div>

    <!-- ============================================
         НИЖНЯЯ ПАНЕЛЬ НАВИГАЦИИ
         Каталог и основные разделы сайта
    ============================================= -->
    <nav class="header-nav">
        <div class="nav-inner container">

            <!-- Выпадающее меню каталога -->
            <div class="catalog-dropdown">
                <button class="catalog-btn">
                    <div class="lines">
                        <span></span><span></span><span></span>
                    </div>
                    Каталог
                </button>
                <div class="catalog-menu">
                    <?php foreach ($cats as $i => $cat): ?>
                    <a href="/shop/catalog.php?category=<?= $cat['id'] ?>">
                        <span><?= $catIcons[$i % count($catIcons)] ?></span>
                        <?= htmlspecialchars($cat['name']) ?>
                    </a>
                    <?php endforeach; ?>
                    
                    <!-- Разделитель и ссылка на все товары -->
                    <div style="height:1px;background:var(--border);margin:6px 0"></div>
                    <a href="/shop/catalog.php"><span>📦</span>Все товары</a>
                </div>
            </div>

            <!-- Основные ссылки: О нас, Акции, Новости, Контакты -->
            <div class="nav-links">
                <a href="/shop/index.php#about">О нас</a>
                <a href="/shop/index.php#promotions">Акции</a>
                <a href="/shop/news.php">Новости</a>
                <a href="/shop/index.php#contacts">Контакты</a>
                
                <!-- Ссылка на админ-панель (только для администраторов) -->
                <?php if (isAdmin()): ?>
                <a href="/shop/admin/index.php" class="admin-link">⚙ Админка</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
</header>

<!-- ============================================
         ПАНЕЛЬ ДЛЯ СЛАБОВИДЯЩИХ
         Появляется при нажатии кнопки A+
============================================= -->
<div class="accessibility-bar" id="accessibilityBar" style="display:none;">
    <div class="container">
        <span>👁 Режим для слабовидящих:</span>
        <button onclick="changeFont(-1)" title="Уменьшить шрифт">A−</button>
        <button onclick="changeFont(1)" title="Увеличить шрифт">A+</button>
        <button onclick="toggleContrast()" title="Инвертировать цвета">Контраст</button>
        <button onclick="document.getElementById('accessibilityBar').style.display='none'" style="margin-left:auto" title="Закрыть">✕</button>
    </div>
</div>

<!-- Основной контент страницы -->
<main class="site-main">
