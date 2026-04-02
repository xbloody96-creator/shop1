<!-- Футер сайта -->
<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-col">
                <h4>🏪 Магазин</h4>
                <p style="color:var(--text-muted);font-size:0.85rem;line-height:1.6">
                    Современный магазин электроники с быстрой доставкой и гарантией качества.
                </p>
                <div style="margin-top:16px;display:flex;gap:12px">
                    <a href="#" style="color:var(--text-muted);font-size:1.2rem">📱</a>
                    <a href="#" style="color:var(--text-muted);font-size:1.2rem">📷</a>
                    <a href="#" style="color:var(--text-muted);font-size:1.2rem">✈️</a>
                </div>
            </div>
            
            <div class="footer-col">
                <h4>Покупателям</h4>
                <ul style="list-style:none;padding:0;margin:0">
                    <li style="margin-bottom:8px"><a href="catalog.php" style="color:var(--text-muted);text-decoration:none;font-size:0.85rem">Каталог</a></li>
                    <li style="margin-bottom:8px"><a href="cart.php" style="color:var(--text-muted);text-decoration:none;font-size:0.85rem">Корзина</a></li>
                    <li style="margin-bottom:8px"><a href="favorites.php" style="color:var(--text-muted);text-decoration:none;font-size:0.85rem">Избранное</a></li>
                    <li style="margin-bottom:8px"><a href="#" style="color:var(--text-muted);text-decoration:none;font-size:0.85rem">Доставка</a></li>
                </ul>
            </div>
            
            <div class="footer-col">
                <h4>Контакты</h4>
                <ul style="list-style:none;padding:0;margin:0">
                    <li style="margin-bottom:8px;color:var(--text-muted);font-size:0.85rem">📞 +7 (999) 000-00-00</li>
                    <li style="margin-bottom:8px;color:var(--text-muted);font-size:0.85rem">✉️ info@shop.ru</li>
                    <li style="margin-bottom:8px;color:var(--text-muted);font-size:0.85rem">📍 г. Москва, ул. Примерная, 1</li>
                </ul>
            </div>
        </div>
        
        <div style="border-top:1px solid var(--border);margin-top:32px;padding-top:20px;text-align:center;color:var(--text-muted);font-size:0.8rem">
            © <?= date('Y') ?> Магазин электроники. Все права защищены.
        </div>
    </div>
</footer>

<div id="modal-overlay" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);z-index:9999"></div>

<!-- ─── СКРИПТЫ ─── -->
<script>
(function() {
    'use strict';
    
    console.log('🔥 ShopJS initializing...');
    
    // Определяем basePath из URL
    const path = window.location.pathname;
    const basePath = path.includes('/shop/') ? '/shop' : '';
    console.log('📁 Base path:', basePath || '(root)');
    
    // Глобальные функции
    window.shopCart = {
        add: function(productId, btn) {
            console.log('🛒 [SHOP_CART.ADD] Starting, product ID:', productId);
            console.log('🛒 [SHOP_CART.ADD] Button element:', btn);
            
            if (!productId) {
                console.error('❌ [SHOP_CART.ADD] No product ID!');
                alert('Ошибка: нет ID товара');
                return;
            }
            
            const originalText = btn.innerHTML;
            const originalBg = btn.style.background;
            btn.innerHTML = '⏳ ...';
            btn.disabled = true;
            
            const url = basePath + '/api/cart_add.php';
            console.log('📡 [SHOP_CART.ADD] URL:', url);
            console.log('📡 [SHOP_CART.ADD] Body:', JSON.stringify({product_id: parseInt(productId), quantity: 1}));
            
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    product_id: parseInt(productId),
                    quantity: 1
                })
            })
            .then(response => {
                console.log('📥 [SHOP_CART.ADD] Response status:', response.status);
                console.log('📥 [SHOP_CART.ADD] Response OK:', response.ok);
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                console.log('✅ [SHOP_CART.ADD] Response data:', data);
                
                if (data.ok) {
                    console.log('✔️ [SHOP_CART.ADD] Success!');
                    btn.innerHTML = '✅ В корзине';
                    btn.style.background = 'var(--success)';
                    
                    const counter = document.querySelector('.cart-count');
                    if (counter && data.cart_count !== undefined) {
                        counter.textContent = data.cart_count;
                        counter.style.transform = 'scale(1.3)';
                        setTimeout(() => counter.style.transform = 'scale(1)', 200);
                    }
                    
                    setTimeout(() => {
                        btn.innerHTML = originalText;
                        btn.style.background = originalBg;
                        btn.disabled = false;
                    }, 1500);
                } else if (data.redirect) {
                    console.log('🔀 [SHOP_CART.ADD] Redirect to:', data.redirect);
                    window.location.href = basePath + data.redirect.replace('/shop', '');
                } else {
                    console.error('❌ [SHOP_CART.ADD] Error:', data.message);
                    alert('Ошибка: ' + (data.message || 'Не удалось добавить'));
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            })
            .catch(error => {
                console.error('💥 [SHOP_CART.ADD] Error:', error);
                console.error('💥 [SHOP_CART.ADD] Error name:', error.name);
                console.error('💥 [SHOP_CART.ADD] Error message:', error.message);
                alert('Ошибка соединения: ' + error.message);
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        },
        
        toggleFavorite: function(productId, btn) {
            console.log('❤️ [SHOP_CART.FAV] Starting, product ID:', productId);
            
            if (!productId) {
                console.error('❌ [SHOP_CART.FAV] No product ID!');
                return;
            }
            
            const url = basePath + '/api/fav_toggle.php';
            console.log('📡 [SHOP_CART.FAV] URL:', url);
            
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    product_id: parseInt(productId)
                })
            })
            .then(response => {
                console.log('📥 [SHOP_CART.FAV] Response status:', response.status);
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                console.log('✅ [SHOP_CART.FAV] Response:', data);
                
                if (data.ok) {
                    const message = data.added ? '❤️ Добавлено в избранное' : '🤍 Удалено из избранного';
                    alert(message);
                    
                    if (btn.classList.contains('btn-fav')) {
                        btn.innerHTML = data.added ? '❤️' : '🤍';
                    }
                } else if (data.redirect) {
                    window.location.href = basePath + data.redirect.replace('/shop', '');
                }
            })
            .catch(error => {
                console.error('💥 [SHOP_CART.FAV] Error:', error);
                alert('Ошибка: ' + error.message);
            });
        }
    };
    
    // Инициализация после загрузки DOM
    document.addEventListener('DOMContentLoaded', function() {
        console.log('✅ [INIT] DOM Ready');
        
        // Находим кнопки
        const cartButtons = document.querySelectorAll('.js-add-cart');
        const favButtons = document.querySelectorAll('.js-add-fav');
        
        console.log('🛒 [INIT] Found cart buttons:', cartButtons.length);
        console.log('❤️ [INIT] Found fav buttons:', favButtons.length);
        
        // Вешаем обработчики НАПРЯМУЮ
        cartButtons.forEach((btn, index) => {
            const productId = btn.dataset.productId;
            console.log(`  🛒 Button #${index}: ID=${productId}, classes="${btn.className}"`);
            console.log(`  🛒 Button #${index}: disabled=${btn.disabled}, type=${btn.type}`);
            
            // Удаляем любые существующие обработчики (клонированием)
            const newBtn = btn.cloneNode(true);
            btn.parentNode.replaceChild(newBtn, btn);
            
            newBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log(`🖱️ [CLICK] Cart button #${index} clicked!`);
                console.log(`🖱️ [CLICK] Product ID:`, productId);
                console.log(`🖱️ [CLICK] This:`, this);
                window.shopCart.add(productId, this);
            });
            
            console.log(`  ✅ Button #${index} listener attached`);
        });
        
        favButtons.forEach((btn, index) => {
            const productId = btn.dataset.productId;
            console.log(`  ❤️ Fav Button #${index}: ID=${productId}, classes="${btn.className}"`);
            
            const newBtn = btn.cloneNode(true);
            btn.parentNode.replaceChild(newBtn, btn);
            
            newBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log(`🖱️ [CLICK] Fav button #${index} clicked!`);
                window.shopCart.toggleFavorite(productId, this);
            });
            
            console.log(`  ✅ Fav Button #${index} listener attached`);
        });
        
        console.log('✅ [INIT] All buttons initialized!');
        
        // Тест
        setTimeout(() => {
            const testBtn = document.querySelector('.js-add-cart');
            if (testBtn) {
                console.log('✅ [TEST] First cart button exists:', testBtn !== null);
                console.log('✅ [TEST] First cart button type:', testBtn.type);
                console.log('✅ [TEST] First cart button disabled:', testBtn.disabled);
            }
        }, 500);
    });
    
    console.log('✅ ShopJS loaded successfully');
})();
</script>

</body>
</html>