<?php
require_once __DIR__ . '/auth.php';
$settings = getSettings();
$cartQty  = cartCount();
$user     = getCurrentUser();
$cats     = $pdo->query("SELECT * FROM categories WHERE parent_id IS NULL ORDER BY sort_order")->fetchAll();
$catIcons = ['📱','💻','📺','🎧','⌨️','🖥️','📷','🎮','🔋','🖨️'];
?>
<!DOCTYPE html>
<html lang="ru" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($settings['site_name'] ?? 'Магазин') ?> — Лучшие цены на технику</title>
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<!-- Topbar -->
<div class="header-topbar">
  <div class="topbar-inner">
    <div style="overflow:hidden;flex:1">
      <div class="topbar-scroll">
        <span>Бесплатная доставка от 3 000 ₽</span>
        <span>Гарантия 1 год на всю технику</span>
        <span>Более 5 000 товаров в наличии</span>
        <span>Работаем 24/7</span>
        <span>Бесплатная доставка от 3 000 ₽</span>
        <span>Гарантия 1 год на всю технику</span>
        <span>Более 5 000 товаров в наличии</span>
        <span>Работаем 24/7</span>
      </div>
    </div>
    <div class="topbar-right">
      <a href="tel:<?= htmlspecialchars($settings['contact_phone'] ?? '') ?>">
        📞 <?= htmlspecialchars($settings['contact_phone'] ?? '') ?>
      </a>
      <a href="/news.php">Новости</a>
    </div>
  </div>
</div>

<!-- Header -->
<header class="site-header">
  <div class="header-inner container">

    <!-- Logo -->
    <a href="/index.php" class="logo">
      <div class="logo-mark">🛒</div>
      <div class="logo-text">
        <?php
        $name = htmlspecialchars($settings['site_name'] ?? 'Магазин');
        // первая буква красная
        echo '<em>' . mb_substr($name, 0, 1) . '</em>' . mb_substr($name, 1);
        ?>
      </div>
    </a>

    <!-- Search -->
    <form class="header-search" action="/search.php" method="GET" autocomplete="off">
      <input type="text" name="q" id="header-search-input"
             placeholder="Поиск: смартфоны, ноутбуки, телевизоры..."
             value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
      <button type="submit">🔍</button>
      <div class="search-suggestions" id="search-suggestions"></div>
    </form>

    <!-- Actions -->
    <nav class="header-actions">
      <?php if (isLoggedIn()): ?>
        <a href="/profile.php" class="btn-icon">
          <span class="icon">👤</span>
          <small><?= htmlspecialchars(mb_substr($user['nickname'] ?? 'Кабинет', 0, 8)) ?></small>
        </a>
      <?php else: ?>
        <a href="/login.php" class="btn-icon">
          <span class="icon">🔑</span><small>Войти</small>
        </a>
        <a href="/register.php" class="btn-icon">
          <span class="icon">📝</span><small>Регистрация</small>
        </a>
      <?php endif; ?>

      <a href="/favorites.php" class="btn-icon">
        <span class="icon">❤️</span><small>Избранное</small>
      </a>

      <a href="/cart.php" class="btn-icon cart-btn">
        <span class="icon">🛒</span>
        <?php if ($cartQty > 0): ?>
          <sup class="cart-badge"><?= $cartQty ?></sup>
        <?php endif; ?>
        <small>Корзина</small>
      </a>

      <button class="btn-icon" id="themeToggle" title="Тема">
        <span class="icon" id="themeIcon">☀️</span><small>Тема</small>
      </button>

      <button class="btn-icon" id="accessibilityBtn" title="Слабовидящим">
        <span class="icon">🔎</span><small>A+</small>
      </button>
    </nav>
  </div>

  <!-- Nav bar -->
  <nav class="header-nav">
    <div class="nav-inner container">

      <!-- Catalog dropdown -->
      <div class="catalog-dropdown">
        <button class="catalog-btn">
          <div class="lines">
            <span></span><span></span><span></span>
          </div>
          Каталог
        </button>
        <div class="catalog-menu">
          <?php foreach ($cats as $i => $cat): ?>
          <a href="/catalog.php?category=<?= $cat['id'] ?>">
            <span><?= $catIcons[$i % count($catIcons)] ?></span>
            <?= htmlspecialchars($cat['name']) ?>
          </a>
          <?php endforeach; ?>
          <div style="height:1px;background:var(--border);margin:6px 0"></div>
          <a href="/catalog.php"><span>📦</span>Все товары</a>
        </div>
      </div>

      <div class="nav-links">
        <a href="/index.php#about">О нас</a>
        <a href="/index.php#promotions">Акции</a>
        <a href="/news.php">Новости</a>
        <a href="/index.php#contacts">Контакты</a>
        <?php if (isAdmin()): ?>
        <a href="/admin/index.php" class="admin-link">⚙ Админка</a>
        <?php endif; ?>
      </div>
    </div>
  </nav>
</header>

<!-- Accessibility bar -->
<div class="accessibility-bar" id="accessibilityBar">
  <div class="container">
    <span>👁 Слабовидящим:</span>
    <button onclick="changeFont(-1)">A−</button>
    <button onclick="changeFont(1)">A+</button>
    <button onclick="toggleContrast()">Контраст</button>
    <button onclick="document.getElementById('accessibilityBar').style.display='none'" style="margin-left:auto">✕</button>
  </div>
</div>

<main class="site-main">
