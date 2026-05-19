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
            <div style="margin-top:16px">
                <a href="contact.php" class="btn-secondary" style="font-size:0.8rem;padding:8px 16px">Написать нам</a>
            </div>
        </div>
    </div>
    
    <!-- Разделитель -->
    <div style="border-top:1px solid var(--border);margin:24px 0"></div>
    
    <!-- Нижняя часть с юридическими ссылками -->
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px">
        <div style="font-size:0.8rem;color:var(--text-muted)">
            © <?= date('Y') ?> Protech-no.ru — Все права защищены
        </div>
        <div style="display:flex;gap:24px;flex-wrap:wrap">
            <a href="privacy.php" style="color:var(--text-muted);font-size:0.8rem;text-decoration:none">🔒 Политика конфиденциальности</a>
            <a href="terms.php" style="color:var(--text-muted);font-size:0.8rem;text-decoration:none">📋 Пользовательское соглашение</a>
            <a href="contact.php" style="color:var(--text-muted);font-size:0.8rem;text-decoration:none">📞 Поддержка</a>
        </div>
    </div>
</div>

<!-- Карта внизу страницы -->
<div style="margin-top:40px;border-radius:var(--radius-md);overflow:hidden;border:1px solid var(--border);box-shadow:var(--shadow)">
    <div id="map" style="width:100%;height:400px"></div>
</div>
</footer>

<div id="modal-overlay" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);z-index:9999"></div>

<!-- ─── ПОДКЛЮЧЕНИЕ MAIN.JS (ТЕМА + ДОСТУПНОСТЬ) ─── -->
<script src="/assets/js/main.js"></script>

<!-- ─── ЯНДЕКС КАРТА ─── -->
<script src="https://api-maps.yandex.ru/2.1/?apikey=your-api-key&lang=ru_RU" type="text/javascript"></script>
<script>
ymaps.ready(init);
function init(){
    var myMap = new ymaps.Map("map", {
        center: [55.751574, 37.573856], // Москва
        zoom: 14,
        controls: ['zoomControl', 'fullscreenControl']
    });
    
    var myPlacemark = new ymaps.Placemark([55.751574, 37.573856], {
        hintContent: 'Protech-no.ru',
        balloonContent: 'г. Москва, ул. Примерная, 1'
    }, {
        preset: 'islands#redStoreIcon'
    });
    
    myMap.geoObjects.add(myPlacemark);
    myMap.behaviors.disable('scrollZoom');
}
</script>

<!-- ─── СКРИПТЫ КОРЗИНЫ ─── -->
<script>
(function() {
'use strict';
console.log('🔥 ShopJS initializing...');
// ✅ ИСПРАВЛЕНО: сайт в корне
const basePath = '';
console.log('📁 Base path:', basePath || '(root)');
window.shopCart = {
add: function(productId, btn) {
console.log('🛒 [SHOP_CART.ADD] Starting, product ID:', productId);
if (!productId) { alert('Ошибка: нет ID товара'); return; }
const originalText = btn.innerHTML;
btn.innerHTML = '⏳ ...';
btn.disabled = true;
const url = basePath + '/api/cart_add.php';
const params = new URLSearchParams();
params.append('product_id', parseInt(productId));
params.append('quantity', '1');
fetch(url, {
method: 'POST',
credentials: 'include',
headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
body: params
})
.then(r => r.json())
.then(data => {
if (data.success) {
btn.innerHTML = '✅ В корзине';
btn.style.background = 'var(--success)';
const counter = document.querySelector('.cart-count');
if (counter && data.cart_count !== undefined) {
counter.textContent = data.cart_count;
counter.style.transform = 'scale(1.3)';
setTimeout(() => counter.style.transform = 'scale(1)', 200);
}
setTimeout(() => { btn.innerHTML = originalText; btn.disabled = false; }, 1500);
} else if (data.redirect) {
window.location.href = basePath + data.redirect.replace('/shop', '');
} else {
alert('Ошибка: ' + (data.error || data.message));
btn.innerHTML = originalText; btn.disabled = false;
}
})
.catch(e => {
alert('Ошибка соединения: ' + e.message);
btn.innerHTML = originalText; btn.disabled = false;
});
},
toggleFavorite: function(productId, btn) {
if (!productId) return;
const url = basePath + '/api/fav_toggle.php';
const params = new URLSearchParams();
params.append('product_id', parseInt(productId));
fetch(url, { method: 'POST', credentials: 'include', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: params })
.then(r => r.json())
.then(data => {
if (data.success) {
alert(data.added ? '❤️ Добавлено в избранное' : '🤍 Удалено из избранного');
if (btn.classList.contains('btn-fav')) btn.innerHTML = data.added ? '❤️' : '🤍';
} else if (data.redirect) {
window.location.href = basePath + data.redirect.replace('/shop', '');
}
}).catch(e => alert('Ошибка: ' + e.message));
}
};
document.addEventListener('DOMContentLoaded', function() {
console.log('✅ [INIT] DOM Ready');
document.querySelectorAll('.js-add-cart').forEach((btn, i) => {
const pid = btn.dataset.productId;
const newBtn = btn.cloneNode(true);
btn.parentNode.replaceChild(newBtn, btn);
newBtn.addEventListener('click', e => { e.preventDefault(); e.stopPropagation(); window.shopCart.add(pid, newBtn); });
});
document.querySelectorAll('.js-add-fav').forEach((btn, i) => {
const pid = btn.dataset.productId;
const newBtn = btn.cloneNode(true);
btn.parentNode.replaceChild(newBtn, btn);
newBtn.addEventListener('click', e => { e.preventDefault(); e.stopPropagation(); window.shopCart.toggleFavorite(pid, newBtn); });
});
});
})();
</script>
</body>
</html>