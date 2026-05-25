<?php
require_once __DIR__ . '/includes/auth.php';
$settings = getSettings();
$sliderProducts  = $pdo->query("SELECT * FROM products WHERE is_popular=1 AND is_active=1 LIMIT 5")->fetchAll();
$popularProducts = $pdo->query("SELECT * FROM products WHERE is_popular=1 AND is_active=1 LIMIT 8")->fetchAll();
$newProducts     = $pdo->query("SELECT * FROM products WHERE is_active=1 ORDER BY created_at DESC LIMIT 8")->fetchAll();
$categories      = $pdo->query("SELECT * FROM categories WHERE parent_id IS NULL ORDER BY sort_order LIMIT 8")->fetchAll();
$promotions      = $pdo->query("SELECT * FROM promotions WHERE is_active=1 LIMIT 3")->fetchAll();
$latestNews      = $pdo->query("SELECT * FROM news WHERE is_active=1 ORDER BY created_at DESC LIMIT 3")->fetchAll();
$catIcons = ['📱','💻','📺','🎧','⌨️','🖥️','📷',''];
require_once __DIR__ . '/includes/header.php';

// Товары для бегущей строки (берём больше для плавности)
$marqueeProducts = $pdo->query("SELECT * FROM products WHERE is_active=1 ORDER BY RAND() LIMIT 12")->fetchAll();
?>

<!-- ─── ТОВАРЫ С БЕГУЩЕЙ СТРОКОЙ (MARQUEE) ─── -->
<?php if (!empty($marqueeProducts)): ?>
<section class="products-marquee-section">
    <div class="products-marquee-track" id="productsMarquee">
        <!-- Дублируем товары для бесконечной прокрутки -->
        <?php foreach ($marqueeProducts as $product): ?>
        <a href="product.php?id=<?= $product['id'] ?>" class="marquee-product-card">
            <div class="marquee-product-image">
                <?php if ($product['main_image']): ?>
                <img src="uploads/<?= htmlspecialchars($product['main_image']) ?>" 
                     alt="<?= htmlspecialchars($product['name']) ?>"
                     onerror="this.style.display='none'; this.parentElement.querySelector('.marquee-product-placeholder').style.display='flex'">
                <?php endif; ?>
                <div class="marquee-product-placeholder">📦</div>
            </div>
            <div class="marquee-product-info">
                <h3 class="marquee-product-title"><?= htmlspecialchars($product['name']) ?></h3>
                <div class="marquee-product-price">
                    <span class="price-current"><?= number_format($product['price'], 0, '', ' ') ?> ₽</span>
                    <?php if ($product['old_price'] && $product['old_price'] > $product['price']): ?>
                    <span class="price-old"><?= number_format($product['old_price'], 0, '', ' ') ?> ₽</span>
                    <?php endif; ?>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
        <!-- Дубликат для бесшовной прокрутки -->
        <?php foreach ($marqueeProducts as $product): ?>
        <a href="product.php?id=<?= $product['id'] ?>" class="marquee-product-card">
            <div class="marquee-product-image">
                <?php if ($product['main_image']): ?>
                <img src="uploads/<?= htmlspecialchars($product['main_image']) ?>" 
                     alt="<?= htmlspecialchars($product['name']) ?>"
                     onerror="this.style.display='none'; this.parentElement.querySelector('.marquee-product-placeholder').style.display='flex'">
                <?php endif; ?>
                <div class="marquee-product-placeholder">📦</div>
            </div>
            <div class="marquee-product-info">
                <h3 class="marquee-product-title"><?= htmlspecialchars($product['name']) ?></h3>
                <div class="marquee-product-price">
                    <span class="price-current"><?= number_format($product['price'], 0, '', ' ') ?> ₽</span>
                    <?php if ($product['old_price'] && $product['old_price'] > $product['price']): ?>
                    <span class="price-old"><?= number_format($product['old_price'], 0, '', ' ') ?> ₽</span>
                    <?php endif; ?>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- ─── HERO SLIDER ─── -->
<?php if (!empty($sliderProducts)): ?>
<div class="hero-slider">
    <div class="slider-container">
        <?php foreach ($sliderProducts as $i => $item): ?>
        <div class="slide <?= $i===0?'active':'' ?>">
            <div class="slide-bg slide-bg-<?= ($i % 3) + 1 ?>"></div>
            <div class="slide-grid"></div>
            <div class="slide-content">
                <div class="slide-tag">🔥 Хит продаж</div>
                <h2 class="slide-title"><?= htmlspecialchars($item['name']) ?></h2>
                <p class="slide-description"><?= htmlspecialchars(mb_substr($item['description'] ?? '', 0, 120)) ?></p>
                <div class="slide-prices">
                    <span class="slide-price"><?= number_format($item['price'], 0, '', ' ') ?> ₽</span>
                    <?php if ($item['old_price'] && $item['old_price'] > $item['price']): ?>
                    <span class="slide-old"><?= number_format($item['old_price'], 0, '', ' ') ?> ₽</span>
                    <span class="slide-pct">-<?= round((1 - $item['price'] / $item['old_price']) * 100) ?>%</span>
                    <?php endif; ?>
                </div>
                <a href="product.php?id=<?= $item['id'] ?>" class="slide-btn">
                    <span>Подробнее</span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </a>
            </div>
            <?php if ($item['main_image']): ?>
            <div class="slide-image-wrap">
                <img src="uploads/<?= htmlspecialchars($item['main_image']) ?>"
                     alt="<?= htmlspecialchars($item['name']) ?>" 
                     class="slide-image"
                     onerror="this.style.display='none'">
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <button class="slider-prev" aria-label="Предыдущий слайд">‹</button>
    <button class="slider-next" aria-label="Следующий слайд">›</button>
    <div class="slider-controls">
        <?php foreach ($sliderProducts as $i => $_): ?>
        <button class="slider-dot <?= $i===0?'active':'' ?>" data-index="<?= $i ?>"></button>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div class="container">
    <!-- ─── КАТЕГОРИИ ─── -->
    <?php if (!empty($categories)): ?>
    <section style="padding:28px 0 0">
        <div class="categories-strip">
            <?php foreach ($categories as $i => $cat): ?>
            <!-- ИСПРАВЛЕНО: убран / -->
            <a href="catalog.php?category=<?= $cat['id'] ?>" class="cat-pill">
                <span class="cat-icon"><?= $catIcons[$i % count($catIcons)] ?></span>
                <span><?= htmlspecialchars($cat['name']) ?></span>
            </a>
            <?php endforeach; ?>
            <a href="catalog.php" class="cat-pill">
                <span class="cat-icon">📦</span>
                <span>Все товары</span>
            </a>
        </div>
    </section>
    <?php endif; ?>

    <!-- ─── ПРОМО-БАННЕРЫ ─── -->
    <?php if (!empty($promotions)): ?>
    <section style="padding:20px 0 0" id="promotions">
        <div class="promo-strip">
            <?php foreach ($promotions as $i => $promo): $n = $i+1; ?>
            <!-- ИСПРАВЛЕНО: убран / -->
            <a href="catalog.php" class="promo-card promo-card-<?= $n <= 3 ? $n : 1 ?>" style="text-decoration:none">
                <div class="promo-orb"></div>
                <?php if ($promo['discount_pct'] > 0): ?>
                <div class="promo-num">-<?= $promo['discount_pct'] ?>%</div>
                <?php endif; ?>
                <div class="promo-title"><?= htmlspecialchars($promo['title']) ?></div>
                <div class="promo-sub"><?= htmlspecialchars(mb_substr($promo['description']??'',0,60)) ?></div>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- ─── ПОИСК ─── -->
    <section id="search" style="padding:28px 0 0">
        <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md);padding:28px;display:flex;align-items:center;gap:20px;flex-wrap:wrap">
            <div style="flex:1;min-width:280px">
                <div style="font-family:var(--font-d);font-size:0.85rem;font-weight:700;margin-bottom:6px">🔍 Быстрый поиск</div>
                <p style="font-size:0.8rem;color:var(--text3)">Найдите нужный товар по названию или категории</p>
            </div>
            <!-- ИСПРАВЛЕНО: убран / -->
            <form action="search.php" method="GET" style="display:flex;gap:8px;flex:1;min-width:280px;position:relative">
                <input type="text" name="q" placeholder="Введите название товара..."
                       style="flex:1;padding:11px 16px;background:var(--surface2);border:1.5px solid var(--border2);border-radius:var(--radius);color:var(--text);font-size:0.88rem"
                       id="hero-search">
                <button type="submit" class="btn-primary" style="width:auto;padding:11px 20px;white-space:nowrap">Найти</button>
            </form>
        </div>
    </section>

    <!-- ─── ПОПУЛЯРНЫЕ ТОВАРЫ ─── -->
    <?php if (!empty($popularProducts)): ?>
    <section class="section">
        <div class="section-header">
            <h2 class="section-title"><span class="dot"></span> Хиты продаж</h2>
            <!-- ИСПРАВЛЕНО: убран / -->
            <a href="catalog.php?sort=popular" class="section-more">Смотреть все</a>
        </div>
        <div class="products-grid">
            <?php foreach ($popularProducts as $product): ?>
            <?php include __DIR__ . '/includes/product_card.php'; ?>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- ─── НОВИНКИ ─── -->
    <?php if (!empty($newProducts)): ?>
    <section class="section" style="padding-top:0">
        <div class="section-header">
            <h2 class="section-title"><span class="dot" style="background:var(--success);box-shadow:0 0 12px var(--success)"></span> Новинки</h2>
            <!-- ИСПРАВЛЕНО: убран / -->
            <a href="catalog.php?sort=new" class="section-more">Смотреть все</a>
        </div>
        <div class="products-grid">
            <?php foreach ($newProducts as $product): ?>
            <?php include __DIR__ . '/includes/product_card.php'; ?>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- ─── О НАС ─── -->
    <section class="section" id="about" style="padding-top:0">
        <div class="about-section">
            <div class="about-text">
                <h2>О нашем <span>магазине</span></h2>
                <p>Мы — современный онлайн-магазин электроники с широким ассортиментом товаров. Работаем с 2020 года и за это время завоевали доверие тысяч покупателей.</p>
                <p>Наша миссия — предоставить каждому покупателю качественные товары по честным ценам с быстрой доставкой и надёжной гарантией.</p>
            </div>
            <div class="about-stats">
                <div class="about-stat"><strong>10 000+</strong><span>Довольных клиентов</span></div>
                <div class="about-stat"><strong>5 000+</strong><span>Товаров в наличии</span></div>
                <div class="about-stat"><strong>24/7</strong><span>Служба поддержки</span></div>
                <div class="about-stat"><strong>1 год</strong><span>Гарантия на всё</span></div>
            </div>
        </div>
    </section>

    <!-- ─── НОВОСТИ ─── -->
    <?php if (!empty($latestNews)): ?>
    <section class="section" style="padding-top:0">
        <div class="section-header">
            <h2 class="section-title"><span class="dot" style="background:var(--accent2);box-shadow:var(--glow-blue)"></span> Новости</h2>
            <!-- ИСПРАВЛЕНО: убран / -->
            <a href="news.php" class="section-more">Все новости</a>
        </div>
        <div class="news-grid">
            <?php foreach ($latestNews as $n): ?>
            <div class="news-card">
                <?php if ($n['image']): ?>
                <div class="news-card-thumb">
                    <!-- ИСПРАВЛЕНО: убран / -->
                    <img src="uploads/<?= htmlspecialchars($n['image']) ?>" alt="">
                </div>
                <?php else: ?>
                <div class="news-card-thumb-ph">📰</div>
                <?php endif; ?>
                <div class="news-card-body">
                    <div class="news-card-date"><?= date('d.m.Y', strtotime($n['created_at'])) ?></div>
                    <h3 class="news-card-title"><?= htmlspecialchars($n['title']) ?></h3>
                    <p class="news-card-text"><?= htmlspecialchars(mb_substr(strip_tags($n['content']??''),0,120)) ?>...</p>
                    <!-- ИСПРАВЛЕНО: убран / -->
                    <a href="news_detail.php?id=<?= $n['id'] ?>" class="news-card-link">Читать далее</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
</div><!-- /container -->

<?php require_once __DIR__ . '/includes/footer.php'; ?>