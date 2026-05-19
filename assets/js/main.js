/* ═══════════════════════════════════════════
   МАГАЗИН — Enhanced JavaScript (ИСПРАВЛЕННЫЙ)
   ═══════════════════════════════════════════ */

document.addEventListener('DOMContentLoaded', () => {

  // ── Theme ──────────────────────────────────
  const html       = document.documentElement;
  const themeBtn   = document.getElementById('themeToggle');
  const themeIcon  = document.getElementById('themeIcon');
  const saved      = localStorage.getItem('theme') || 'dark';
  html.setAttribute('data-theme', saved);
  if (themeIcon) themeIcon.textContent = saved === 'dark' ? '☀️' : '🌙';

  themeBtn?.addEventListener('click', () => {
    const next = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    html.setAttribute('data-theme', next);
    localStorage.setItem('theme', next);
    if (themeIcon) themeIcon.textContent = next === 'dark' ? '☀️' : '🌙';
  });

  // ── Accessibility ──────────────────────────
  const accBtn = document.getElementById('accessibilityBtn');
  const accBar = document.getElementById('accessibilityBar');
  let fontSize  = parseInt(localStorage.getItem('fontSize') || '15');
  html.style.fontSize = fontSize + 'px';

  accBtn?.addEventListener('click', () => {
    if (accBar) accBar.style.display = accBar.style.display === 'flex' ? 'none' : 'flex';
  });

  // ── Hero Slider ────────────────────────────
  const sliderContainer = document.querySelector('.slider-container');
  const slides         = document.querySelectorAll('.slide');
  const dots           = document.querySelectorAll('.slider-dot');
  const prevBtn        = document.querySelector('.slider-prev');
  const nextBtn        = document.querySelector('.slider-next');
  let   cur            = 0;
  let   autoPlay       = null;
  let   isTouch        = false;
  let   touchX         = 0;

  function goTo(idx) {
    if (!sliderContainer || slides.length === 0) return;
    const total = slides.length;
    cur = ((idx % total) + total) % total;
    
    // Update slides visibility
    slides.forEach((slide, i) => {
      slide.classList.toggle('active', i === cur);
    });
    
    // Update dots
    dots.forEach((d, i) => d.classList.toggle('active', i === cur));
  }

  if (slides.length > 1) {
    goTo(0);
    dots.forEach((d, i) => d.addEventListener('click', () => { goTo(i); resetAuto(); }));
    prevBtn?.addEventListener('click', () => { goTo(cur - 1); resetAuto(); });
    nextBtn?.addEventListener('click', () => { goTo(cur + 1); resetAuto(); });

    // Touch swipe
    sliderContainer.addEventListener('touchstart', e => { isTouch = true; touchX = e.touches[0].clientX; }, {passive: true});
    sliderContainer.addEventListener('touchend',   e => {
      if (!isTouch) return;
      const diff = touchX - e.changedTouches[0].clientX;
      if (Math.abs(diff) > 40) { diff > 0 ? goTo(cur + 1) : goTo(cur - 1); resetAuto(); }
      isTouch = false;
    }, {passive: true});

    function startAuto() { autoPlay = setInterval(() => goTo(cur + 1), 5000); }
    function resetAuto() { clearInterval(autoPlay); startAuto(); }
    startAuto();
  }

  // ── Search Suggestions ─────────────────────
  const searchIn  = document.getElementById('header-search-input');
  const suggBox   = document.getElementById('search-suggestions');
  let   debTimer  = null;

  if (searchIn && suggBox) {
    searchIn.addEventListener('input', () => {
      clearTimeout(debTimer);
      const q = searchIn.value.trim();
      if (q.length < 2) { suggBox.classList.remove('open'); return; }
      debTimer = setTimeout(async () => {
        try {
          const res  = await fetch(`/api/search_suggest.php?q=${encodeURIComponent(q)}`);
          const data = await res.json();
          if (!data.length) { suggBox.classList.remove('open'); return; }
          suggBox.innerHTML = data.map(item => `
            <a class="suggestion-item" href="/product.php?id=${item.id}">
              ${item.main_image
                ? `<img src="/uploads/${item.main_image}" alt="" loading="lazy">`
                : `<div style="width:38px;height:38px;background:var(--surface3);border-radius:4px;display:flex;align-items:center;justify-content:center;font-size:1.2rem">📦</div>`}
              <span>${escHtml(item.name)}</span>
              <strong>${fmtPrice(item.price)} ₽</strong>
            </a>
          `).join('');
          suggBox.classList.add('open');
        } catch(e) {}
      }, 280);
    });
    document.addEventListener('click', e => {
      if (!searchIn.contains(e.target) && !suggBox.contains(e.target)) suggBox.classList.remove('open');
    });
  }

  // ── Gallery Thumbnails ─────────────────────
  const mainImg = document.querySelector('.gallery-main');
  document.querySelectorAll('.gallery-thumb').forEach(th => {
    th.addEventListener('click', () => {
      if (mainImg) {
        mainImg.style.opacity = '0';
        setTimeout(() => { mainImg.src = th.src; mainImg.style.opacity = '1'; }, 120);
      }
      document.querySelectorAll('.gallery-thumb').forEach(t => t.classList.remove('active'));
      th.classList.add('active');
    });
  });
  if (mainImg) mainImg.style.transition = 'opacity 0.12s ease';

  // ═══════════════════════════════════════════
  // ── Cart Add (AJAX) — ИСПРАВЛЕНО ───────────
  // ═══════════════════════════════════════════
  document.querySelectorAll('.js-add-cart').forEach(btn => {
    btn.addEventListener('click', async e => {
      e.preventDefault();
      const id = btn.dataset.productId;
      const orig = btn.textContent;
      btn.textContent = '...';
      btn.disabled = true;

      try {
        // ✅ Отправляем как form-data (совместимо с PHP $_POST)
        const params = new URLSearchParams();
        params.append('product_id', id);
        params.append('quantity', '1');

        const res = await fetch('/api/cart_add.php', {
          method: 'POST',
          credentials: 'include', // ✅ Передаёт PHPSESSID для авторизации
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: params
        });

        const data = await res.json();

        // ✅ Проверяем data.success (а не data.ok)
        if (data.success) {
          btn.textContent = '✓ Добавлено';
          btn.style.background = 'var(--success)';
          if (typeof updateCartBadge === 'function') updateCartBadge(data.cart_count);
          toast('Товар добавлен в корзину 🛒', 'success');
          setTimeout(() => {
            btn.textContent = orig;
            btn.style.background = '';
            btn.disabled = false;
          }, 1800);
        } else if (data.redirect) {
          window.location.href = data.redirect;
        } else {
          btn.textContent = orig;
          btn.disabled = false;
          toast(data.error || data.message || 'Ошибка', 'error');
        }
      } catch (e) {
        btn.textContent = orig;
        btn.disabled = false;
        toast('Ошибка соединения', 'error');
        console.error('Cart AJAX error:', e);
      }
    });
  });

  // ═══════════════════════════════════════════
  // ── Favorites (AJAX) — ИСПРАВЛЕНО ──────────
  // ═══════════════════════════════════════════
  document.querySelectorAll('.js-add-fav').forEach(btn => {
    btn.addEventListener('click', async e => {
      e.preventDefault();
      const id = btn.dataset.productId;

      try {
        const params = new URLSearchParams();
        params.append('product_id', id);

        const res = await fetch('/api/fav_toggle.php', {
          method: 'POST',
          credentials: 'include',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: params
        });

        const data = await res.json();

        // ✅ Проверяем data.success (а не data.ok)
        if (data.success) {
          document.querySelectorAll(`.js-add-fav[data-product-id="${id}"]`).forEach(b => {
            b.textContent = data.added ? '❤️' : '🤍';
            if (data.added) b.classList.add('active'); else b.classList.remove('active');
          });
          toast(data.added ? 'Добавлено в избранное ❤️' : 'Убрано из избранного', 'success');
        } else if (data.redirect) {
          window.location.href = data.redirect;
        }
      } catch (e) {
        console.error('Favorites error:', e);
      }
    });
  });

  // ═══════════════════════════════════════════
  // ── Cart Qty Update — ИСПРАВЛЕНО ───────────
  // ═══════════════════════════════════════════
  document.querySelectorAll('.qty-minus').forEach(b => b.addEventListener('click', () => changeQty(b, -1)));
  document.querySelectorAll('.qty-plus').forEach(b  => b.addEventListener('click', () => changeQty(b, +1)));

  async function changeQty(btn, delta) {
    const item = btn.closest('[data-cart-id]');
    if (!item) return;
    const cartId  = item.dataset.cartId;
    const qtyEl   = item.querySelector('.qty-value');
    let qty = parseInt(qtyEl?.textContent || '1') + delta;
    if (qty < 1) qty = 1;

    try {
      const params = new URLSearchParams();
      params.append('cart_id', cartId);
      params.append('quantity', qty);

      const res = await fetch('/api/cart_update.php', {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: params
      });

      const data = await res.json();

      // ✅ Проверяем data.success
      if (data.success && qtyEl) {
        qtyEl.textContent = qty;
        recalcTotal();
        if (typeof updateCartBadge === 'function') updateCartBadge(data.cart_count);
      }
    } catch (e) {
      console.error('Cart qty error:', e);
    }
  }

  function recalcTotal() {
    let total = 0;
    document.querySelectorAll('[data-cart-price]').forEach(el => {
      const item = el.closest('[data-cart-id]');
      const qty  = parseInt(item?.querySelector('.qty-value')?.textContent || '1');
      total += parseFloat(el.dataset.cartPrice) * qty;
    });
    const el = document.getElementById('cart-total');
    if (el) el.textContent = fmtPrice(total) + ' ₽';
  }

  // ── Cart Badge ─────────────────────────────
  function updateCartBadge(count) {
    let badge   = document.querySelector('.cart-badge');
    const cartB = document.querySelector('.cart-btn');
    if (!cartB) return;
    if (count > 0) {
      if (!badge) {
        badge = document.createElement('sup');
        badge.className = 'cart-badge';
        cartB.querySelector('.icon')?.after(badge);
      }
      badge.textContent = count;
    } else { badge?.remove(); }
  }

  // ── Toast ──────────────────────────────────
  function toast(msg, type = 'info') {
    const el = document.createElement('div');
    el.className = `_toast _toast-${type}`;
    el.innerHTML = `<span>${msg}</span>`;
    document.body.appendChild(el);
    requestAnimationFrame(() => { requestAnimationFrame(() => el.classList.add('_show')); });
    setTimeout(() => {
      el.classList.remove('_show');
      setTimeout(() => el.remove(), 320);
    }, 3200);
  }

  // Inject toast styles once
  if (!document.getElementById('_toast-styles')) {
    const s = document.createElement('style');
    s.id = '_toast-styles';
    s.textContent = `
      ._toast {
        position: fixed; bottom: 24px; right: 24px; z-index: 9999;
        padding: 13px 20px; border-radius: 6px; font-family: var(--font-b);
        font-size: 0.85rem; font-weight: 600; box-shadow: 0 8px 32px rgba(0,0,0,0.4);
        transform: translateY(16px) scale(0.96); opacity: 0;
        transition: all 0.3s cubic-bezier(0.34,1.56,0.64,1);
        max-width: 320px; pointer-events: none;
      }
      ._toast._show { transform: translateY(0) scale(1); opacity: 1; }
      ._toast-success { background: #00d68f; color: #003322; }
      ._toast-error   { background: #ff2d3b; color: #fff; }
      ._toast-info    { background: #3b82f6; color: #fff; }
    `;
    document.head.appendChild(s);
  }

  // ── Admin Image Preview ────────────────────
  const adminImg   = document.getElementById('admin-img-input');
  const adminPrev  = document.getElementById('admin-img-preview');
  adminImg?.addEventListener('change', () => {
    const f = adminImg.files[0];
    if (f && adminPrev) {
      const r = new FileReader();
      r.onload = e => { adminPrev.src = e.target.result; adminPrev.style.display='block'; };
      r.readAsDataURL(f);
    }
  });

  // ── Avatar Preview ─────────────────────────
  const avInput  = document.getElementById('avatar-input');
  const avPrev   = document.getElementById('avatar-preview');
  avInput?.addEventListener('change', () => {
    const f = avInput.files[0];
    if (f && avPrev) {
      const r = new FileReader();
      r.onload = e => { avPrev.src = e.target.result; avPrev.style.display='block'; };
      r.readAsDataURL(f);
    }
  });

  // ── Register password confirm ──────────────
  const form  = document.getElementById('register-form');
  const pass  = form?.querySelector('[name="password"]');
  const pass2 = form?.querySelector('[name="password_confirm"]');
  pass2?.addEventListener('input', () => {
    const err = pass2.nextElementSibling;
    if (pass2.value && pass2.value !== pass?.value) {
      pass2.classList.add('error');
      if (err?.classList.contains('field-error')) err.textContent = 'Пароли не совпадают';
    } else {
      pass2.classList.remove('error');
      if (err?.classList.contains('field-error')) err.textContent = '';
    }
  });

  // ── Confirm delete ─────────────────────────
  document.querySelectorAll('.js-confirm-delete').forEach(btn => {
    btn.addEventListener('click', e => {
      if (!confirm('Удалить этот элемент? Действие необратимо.')) e.preventDefault();
    });
  });

  // ── Auto-submit filters ────────────────────
  const filterForm = document.getElementById('catalog-filter-form');
  filterForm?.querySelectorAll('input[type="checkbox"], input[type="radio"]').forEach(el => {
    el.addEventListener('change', () => filterForm.submit());
  });

  // ── Scroll reveal ──────────────────────────
  if ('IntersectionObserver' in window) {
    const obs = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.style.animation = 'fadeUp 0.4s ease both';
          obs.unobserve(entry.target);
        }
      });
    }, { threshold: 0.08 });
    document.querySelectorAll('.product-card, .news-card, .about-stat, .stat-card').forEach(el => {
      el.style.opacity = '0';
      obs.observe(el);
    });
  }

  // ── Helpers ────────────────────────────────
  function fmtPrice(n) { return new Intl.NumberFormat('ru-RU').format(parseFloat(n)); }
  function escHtml(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

});

// ── Global accessibility fns ──────────────────
function changeFont(delta) {
  let size = parseInt(getComputedStyle(document.documentElement).fontSize) + delta * 2;
  size = Math.min(24, Math.max(13, size));
  document.documentElement.style.fontSize = size + 'px';
  localStorage.setItem('fontSize', size);
}
function toggleContrast() { document.body.classList.toggle('high-contrast'); }