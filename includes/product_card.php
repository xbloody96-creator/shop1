<?php
$hasDiscount = !empty($product['old_price']) && $product['old_price'] > $product['price'];
$isNew = isset($product['created_at']) && (time() - strtotime($product['created_at'])) < 14 * 24 * 3600;
$pct   = $hasDiscount ? round(100 - ($product['price'] / $product['old_price'] * 100)) : 0;
?>
<div class="product-card" data-product-id="<?= $product['id'] ?>" style="position:relative;z-index:1">
    <?php if ($hasDiscount): ?>
    <span class="badge badge-sale" style="pointer-events:none;z-index:5">-<?= $pct ?>%</span>
    <?php elseif ($isNew): ?>
    <span class="badge badge-new" style="pointer-events:none;z-index:5">NEW</span>
    <?php elseif (!empty($product['is_popular'])): ?>
    <span class="badge badge-popular" style="pointer-events:none;z-index:5">ХИТ</span>
    <?php endif; ?>

    <!-- Quick actions - ИСПРАВЛЕНО: высокий z-index -->
    <div class="product-card-actions" style="position:absolute;top:10px;right:10px;z-index:10;display:flex;gap:6px">
        <button class="quick-act js-add-fav" 
                data-product-id="<?= $product['id'] ?>" 
                title="В избранное"
                style="pointer-events:auto;cursor:pointer;z-index:11;position:relative;background:none;border:none;font-size:1.2rem"
                type="button">
            ❤️
        </button>
        <a href="product.php?id=<?= $product['id'] ?>" 
           class="quick-act" 
           title="Просмотр"
           style="pointer-events:auto;cursor:pointer;z-index:11;position:relative;background:none;border:none;font-size:1.2rem;text-decoration:none">
            👁
        </a>
    </div>

    <!-- Изображение - ИСПРАВЛЕНО: pointer-events:none чтобы не блокировало кнопки -->
    <a href="product.php?id=<?= $product['id'] ?>" 
       class="product-card-img-wrap" 
       style="display:block;text-decoration:none;position:relative;z-index:1"
       onclick="event.stopPropagation();">
        <?php if (!empty($product['main_image'])): ?>
        <img src="uploads/<?= htmlspecialchars($product['main_image']) ?>"
             alt="<?= htmlspecialchars($product['name']) ?>" 
             class="product-card-img"
             loading="lazy"
             style="pointer-events:none;width:100%;display:block">
        <?php else: ?>
        <div class="product-card-img-placeholder" style="pointer-events:none;width:100%;height:200px;background:var(--surface2);display:flex;align-items:center;justify-content:center;font-size:4rem">📦</div>
        <?php endif; ?>
    </a>

    <!-- Информация о товаре - ИСПРАВЛЕНО -->
    <div class="product-card-body" 
         style="pointer-events:none;padding:16px"
         onclick="event.stopPropagation();">
        <a href="product.php?id=<?= $product['id'] ?>" 
           class="product-card-name" 
           style="pointer-events:auto;text-decoration:none;color:var(--text);font-weight:700;font-size:0.95rem;display:block;margin-bottom:8px"
           onclick="event.stopPropagation();">
            <?= htmlspecialchars($product['name']) ?>
        </a>
        <div style="display:flex;align-items:baseline;gap:6px;margin-top:auto">
            <span class="product-card-price" style="font-weight:800;font-size:1.1rem;color:var(--accent)"><?= number_format($product['price'], 0, '', ' ') ?> ₽</span>
            <?php if ($hasDiscount): ?>
            <span class="product-card-old" style="font-size:0.85rem;color:var(--text-muted);text-decoration:line-through"><?= number_format($product['old_price'], 0, '', ' ') ?> ₽</span>
            <?php endif; ?>
        </div>
        <div class="product-card-stock" style="pointer-events:none;margin-top:8px;font-size:0.85rem">
            <?php if ($product['stock'] > 0): ?>
            <span class="in-stock" style="color:var(--success)">✓ В наличии</span>
            <?php else: ?>
            <span class="out-stock" style="color:var(--error)">✗ Нет в наличии</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Кнопки - ИСПРАВЛЕНО: максимальный z-index и pointer-events -->
    <div class="product-card-footer" 
         style="position:relative;z-index:20;margin-top:12px;padding:0 16px 16px"
         onclick="event.stopPropagation();">
        <button class="btn-buy js-add-cart" 
                data-product-id="<?= $product['id'] ?>" 
                type="button"
                style="pointer-events:auto;cursor:pointer;z-index:21;position:relative;width:100%;padding:12px;background:var(--accent);color:#fff;border:none;border-radius:var(--radius-sm);font-weight:700;font-size:0.88rem"
                onclick="event.stopPropagation();">
            <?= $product['stock'] > 0 ? '🛒 В корзину' : 'Уведомить' ?>
        </button>
        <button class="btn-fav js-add-fav" 
                data-product-id="<?= $product['id'] ?>" 
                title="Избранное"
                type="button"
                style="pointer-events:auto;cursor:pointer;z-index:21;position:relative;background:none;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:1.2rem;padding:8px 12px;margin-top:8px"
                onclick="event.stopPropagation();">
            🤍
        </button>
    </div>
</div>