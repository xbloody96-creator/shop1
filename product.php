<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/check_2fa.php';
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: /catalog.php'); exit; }

$stmt = $pdo->prepare("SELECT p.*, c.name as cat_name, c.id as cat_id FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ? AND p.is_active = 1");
$stmt->execute([$id]);
$product = $stmt->fetch();
if (!$product) { header('Location: /catalog.php'); exit; }

// Галерея
$images = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order");
$images->execute([$id]);
$gallery = $images->fetchAll();

// Характеристики
$specs = $pdo->prepare("SELECT * FROM product_specs WHERE product_id = ? ORDER BY id");
$specs->execute([$id]);
$specsList = $specs->fetchAll();

// Отзывы
$reviewsStmt = $pdo->prepare("SELECT r.*, u.full_name, u.nickname, u.avatar FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.product_id = ? AND r.is_approved = 1 ORDER BY r.created_at DESC");
$reviewsStmt->execute([$id]);
$reviews = $reviewsStmt->fetchAll();
$avgRating = count($reviews) ? round(array_sum(array_column($reviews, 'rating')) / count($reviews), 1) : 0;

// Похожие товары
$similar = $pdo->prepare("SELECT * FROM products WHERE category_id = ? AND id != ? AND is_active = 1 LIMIT 4");
$similar->execute([$product['cat_id'], $id]);
$similarProducts = $similar->fetchAll();

// История
if (isLoggedIn()) {
    require2FAVerified();
    $pdo->prepare("DELETE FROM view_history WHERE user_id = ? AND product_id = ?")->execute([$_SESSION['user_id'], $id]);
    $pdo->prepare("INSERT INTO view_history (user_id, product_id) VALUES (?,?)")->execute([$_SESSION['user_id'], $id]);
}

// Отзыв
$reviewError = '';
$reviewSuccess = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_text'])) {
    if (!isLoggedIn()) {
        $reviewError = 'Войдите, чтобы оставить отзыв';
    } else {
        require2FAVerified();
        $rating = min(5, max(1, (int)($_POST['rating'] ?? 5)));
        $text = trim($_POST['review_text'] ?? '');
        if (empty($text)) {
            $reviewError = 'Введите текст отзыва';
        } else {
            $pdo->prepare("INSERT INTO reviews (product_id, user_id, rating, text, is_approved) VALUES (?,?,?,?,0)")->execute([$id, $_SESSION['user_id'], $rating, $text]);
            $reviewSuccess = 'Отзыв отправлен на модерацию!';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <!-- Breadcrumbs -->
    <div class="breadcrumbs">
        <a href="/index.php">Главная</a> /
        <a href="/catalog.php">Каталог</a>
        <?php if ($product['cat_name']): ?>
            / <a href="/catalog.php?category=<?= $product['cat_id'] ?>"><?= htmlspecialchars($product['cat_name']) ?></a>
        <?php endif; ?>
        / <span><?= htmlspecialchars($product['name']) ?></span>
    </div>

    <div class="product-layout">
        <!-- Галерея -->
        <div class="product-gallery">
            <?php
            $mainImg = $product['main_image'] ? '/uploads/' . $product['main_image'] : '';
            $firstGallery = !empty($gallery) ? '/uploads/' . $gallery[0]['image'] : $mainImg;
            ?>
            
            <!-- Обертка для корректного отображения (соответствует CSS) -->
            <div class="gallery-main-box">
                <?php if ($firstGallery): ?>
                    <img src="<?= htmlspecialchars($firstGallery) ?>"
                         alt="<?= htmlspecialchars($product['name']) ?>"
                         class="gallery-main" id="gallery-main">
                <?php else: ?>
                    <div style="font-size:6rem;color:var(--text3)">📦</div>
                <?php endif; ?>
            </div>

            <?php if (!empty($gallery)): ?>
                <div class="gallery-thumbs">
                    <?php foreach ($gallery as $i => $img): ?>
                        <img src="/uploads/<?= htmlspecialchars($img['image']) ?>"
                             alt=""
                             class="gallery-thumb <?= $i===0 ? 'active' : '' ?>"
                             onclick="document.getElementById('gallery-main').src=this.src;
                                      document.querySelectorAll('.gallery-thumb').forEach(t=>t.classList.remove('active'));
                                      this.classList.add('active')">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Информация -->
        <div class="product-info">
            <div class="product-info-card">
                <h1 style="font-size:1.3rem;font-weight:800;margin-bottom:12px"><?= htmlspecialchars($product['name']) ?></h1>
                
                <!-- Рейтинг -->
                <?php if ($avgRating > 0): ?>
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px">
                        <span class="stars"><?= str_repeat('★', (int)$avgRating) ?><?= str_repeat('☆', 5 - (int)$avgRating) ?></span>
                        <span style="font-size:0.88rem;color:var(--text-muted)"><?= $avgRating ?> (<?= count($reviews) ?> отзывов)</span>
                    </div>
                <?php endif; ?>

                <!-- Цена -->
                <div style="margin-bottom:16px">
                    <span class="product-price"><?= number_format($product['price'], 0, '', ' ') ?> ₽</span>
                    <?php if ($product['old_price']): ?>
                        <span class="product-old-price"><?= number_format($product['old_price'], 0, '', ' ') ?> ₽</span>
                        <?php if ($product['old_price'] > $product['price']): ?>
                            <span class="badge badge-sale" style="position:static;margin-left:8px">
                                -<?= round(100 - ($product['price']/$product['old_price']*100)) ?>%
                            </span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <!-- Наличие -->
                <div class="product-stock">
                    <?php if ($product['stock'] > 0): ?>
                        <span class="in-stock">✓ В наличии (<?= $product['stock'] ?> шт.)</span>
                    <?php else: ?>
                        <span class="out-stock">✗ Нет в наличии</span>
                    <?php endif; ?>
                </div>

                <!-- Кнопки -->
                <div style="margin-top:20px;display:flex;flex-direction:column;gap:10px">
                    <?php if ($product['stock'] > 0): ?>
                        <button class="btn-primary js-add-cart" data-product-id="<?= $product['id'] ?>">🛒 Добавить в корзину</button>
                    <?php else: ?>
                        <button class="btn-primary" disabled style="opacity:0.5;cursor:not-allowed">Нет в наличии</button>
                    <?php endif; ?>
                    <button class="btn-secondary js-add-fav" data-product-id="<?= $product['id'] ?>">❤️ В избранное</button>
                </div>
            </div>

            <!-- Описание -->
            <?php if (!empty($product['description'])): ?>
                <div class="product-info-card">
                    <h3 style="font-weight:700;margin-bottom:12px">Описание</h3>
                    <p style="color:var(--text-muted);line-height:1.8;font-size:0.9rem"><?= nl2br(htmlspecialchars($product['description'])) ?></p>
                </div>
            <?php endif; ?>

            <!-- Характеристики -->
            <?php if (!empty($specsList)): ?>
                <div class="product-info-card">
                    <h3 style="font-weight:700;margin-bottom:12px">Характеристики</h3>
                    <table class="specs-table">
                        <?php foreach ($specsList as $spec): ?>
                            <tr>
                                <td><?= htmlspecialchars($spec['spec_key']) ?></td>
                                <td><?= htmlspecialchars($spec['spec_value']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Отзывы -->
    <section class="section">
        <h2 class="section-title">💬 Отзывы (<?= count($reviews) ?>)</h2>
        
        <?php if ($reviewError): ?> <div class="alert alert-error"><?= $reviewError ?></div> <?php endif; ?>
        <?php if ($reviewSuccess): ?> <div class="alert alert-success"><?= $reviewSuccess ?></div> <?php endif; ?>

        <!-- Форма -->
        <?php if (isLoggedIn()): ?>
            <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:24px;margin-bottom:24px">
                <h3 style="font-weight:700;margin-bottom:16px">Написать отзыв</h3>
                <form method="POST">
                    <div class="form-group">
                        <label>Оценка</label>
                        <select name="rating" class="form-control" style="max-width:200px">
                            <?php for ($i=5;$i>=1;$i--): ?>
                                <option value="<?= $i ?>"><?= str_repeat('★',$i) ?> (<?= $i ?>)</option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Текст отзыва</label>
                        <textarea name="review_text" class="form-control" rows="4" placeholder="Поделитесь впечатлениями..." required></textarea>
                    </div>
                    <button type="submit" class="btn-primary" style="max-width:200px">Отправить</button>
                </form>
            </div>
        <?php else: ?>
            <div class="alert alert-info"><a href="/login.php">Войдите</a>, чтобы оставить отзыв</div>
        <?php endif; ?>

        <!-- Список -->
        <?php if (empty($reviews)): ?>
            <div class="empty-state"><p>Пока нет отзывов. Будьте первым!</p></div>
        <?php else: ?>
            <?php foreach ($reviews as $review): ?>
                <div class="review-card">
                    <div class="review-header">
                        <div style="display:flex;align-items:center;gap:10px">
                            <?php $avt = $review['avatar'] && $review['avatar'] !== 'default_avatar.png' ? '/uploads/' . $review['avatar'] : ''; ?>
                            <?php if ($avt): ?><img src="<?= htmlspecialchars($avt) ?>" style="width:36px;height:36px;border-radius:50%;object-fit:cover"><?php endif; ?>
                            <div>
                                <div class="review-author"><?= htmlspecialchars($review['full_name']) ?></div>
                                <div class="review-date"><?= date('d.m.Y', strtotime($review['created_at'])) ?></div>
                            </div>
                        </div>
                        <div class="stars"><?= str_repeat('★', $review['rating']) ?><?= str_repeat('☆', 5-$review['rating']) ?></div>
                    </div>
                    <p class="review-text"><?= nl2br(htmlspecialchars($review['text'])) ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <!-- Похожие -->
    <?php if (!empty($similarProducts)): ?>
        <section class="section">
            <h2 class="section-title">Похожие товары</h2>
            <div class="products-grid">
                <?php foreach ($similarProducts as $p): ?>
                    <?php include __DIR__ . '/includes/product_card.php'; ?>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>