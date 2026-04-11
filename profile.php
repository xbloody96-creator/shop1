/**
 * Файл: profile.php
 * Описание: Страница сайта
 * @version 1.0
 */

<?php
// Подключение модуля аутентификации
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$userId = $_SESSION['user_id'];
$user   = getCurrentUser();

// Заказы
$orders = // SQL Запрос: выборка данных
    $pdo->prepare("SELECT o.*, (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) as items_count FROM orders o WHERE o.user_id = ? ORDER BY o.created_at DESC LIMIT 10");
$orders->execute([$userId]);
$orderList = $orders->fetchAll();

// История просмотров
$history = // SQL Запрос: выборка данных
    $pdo->prepare("SELECT p.*, vh.viewed_at FROM view_history vh JOIN products p ON vh.product_id = p.id WHERE vh.user_id = ? ORDER BY vh.viewed_at DESC LIMIT 8");
$history->execute([$userId]);
$viewHistory = $history->fetchAll();

// Избранное
$favs = // SQL Запрос: выборка данных
    $pdo->prepare("SELECT p.* FROM favorites f JOIN products p ON f.product_id = p.id WHERE f.user_id = ? ORDER BY f.added_at DESC LIMIT 8");
$favs->execute([$userId]);
$favorites = $favs->fetchAll();

// Сессии
$sessions = // SQL Запрос: выборка данных
    $pdo->prepare("SELECT * FROM user_sessions WHERE user_id = ? ORDER BY last_active DESC LIMIT 5");
$sessions->execute([$userId]);
$sessionList = $sessions->fetchAll();

// Выход
if (isset($_GET['logout'])) {
    logoutUser();
    // Перенаправление пользователя
header('Location: /shop/login.php');
    exit;
}

$statusLabels = [
    'pending'    => ['⏳ Ожидает', '#d97706'],
    'processing' => ['🔄 В обработке', '#2563eb'],
    'shipped'    => ['🚚 Отправлен', '#7c3aed'],
    'delivered'  => ['✅ Доставлен', '#16a34a'],
    'cancelled'  => ['❌ Отменён', '#dc2626'],
];

require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
  <div class="breadcrumbs">
    <a href="/shop/index.php">Главная</a> / <span>Личный кабинет</span>
  </div>

  <div class="profile-layout">
    <!-- Карточка пользователя -->
    <aside>
      <div class="profile-card">
        <?php
        $avatarPath = ($user['avatar'] && $user['avatar'] !== 'default_avatar.png')
            ? '/shop/uploads/' . $user['avatar']
            : 'https://ui-avatars.com/api/?name=' . urlencode($user['full_name']) . '&size=100&background=e63946&color=fff';
        ?>
        <img src="<?= htmlspecialchars($avatarPath) ?>" alt="" class="profile-avatar">
        <div class="profile-name"><?= htmlspecialchars($user['full_name']) ?></div>
        <div class="profile-nick">@<?= htmlspecialchars($user['nickname']) ?></div>
        <div class="profile-email">✉️ <?= htmlspecialchars($user['email']) ?></div>
        <a href="/shop/profile.php?logout=1" class="btn-secondary" style="display:block">Выйти из аккаунта</a>
      </div>

      <!-- Навигация -->
      <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;margin-top:12px">
        <a href="#orders" style="display:flex;align-items:center;gap:10px;padding:14px 20px;border-bottom:1px solid var(--border);font-weight:600;font-size:0.9rem;transition:background 0.2s">📦 Мои заказы (<?= count($orderList) ?>)</a>
        <a href="#favorites" style="display:flex;align-items:center;gap:10px;padding:14px 20px;border-bottom:1px solid var(--border);font-weight:600;font-size:0.9rem;transition:background 0.2s">❤️ Избранное (<?= count($favorites) ?>)</a>
        <a href="#history" style="display:flex;align-items:center;gap:10px;padding:14px 20px;border-bottom:1px solid var(--border);font-weight:600;font-size:0.9rem;transition:background 0.2s">👁 Просмотренные</a>
        <a href="#sessions" style="display:flex;align-items:center;gap:10px;padding:14px 20px;font-weight:600;font-size:0.9rem;transition:background 0.2s">🔐 Сессии</a>
      </div>
    </aside>

    <!-- Основной контент -->
    <div class="profile-sections">

      <!-- Заказы -->
      <div class="profile-section" id="orders">
        <h3>📦 Мои заказы</h3>
        <?php if (empty($orderList)): ?>
        <div class="empty-state" style="padding:20px"><p>Заказов ещё нет</p></div>
        <?php else: ?>
        <div style="overflow-x:auto">
          <table class="admin-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Дата</th>
                <th>Товаров</th>
                <th>Сумма</th>
                <th>Статус</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($orderList as $order):
                $st = $statusLabels[$order['status']] ?? ['Неизвестно', '#888'];
              ?>
              <tr>
                <td><strong>#<?= $order['id'] ?></strong></td>
                <td><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></td>
                <td><?= $order['items_count'] ?> шт.</td>
                <td><strong><?= number_format($order['total'], 0, '', ' ') ?> ₽</strong></td>
                <td><span style="color:<?= $st[1] ?>;font-weight:600"><?= $st[0] ?></span></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>

      <!-- Избранное -->
      <div class="profile-section" id="favorites">
        <h3>❤️ Избранное</h3>
        <?php if (empty($favorites)): ?>
        <div class="empty-state" style="padding:20px"><p>Нет избранных товаров</p></div>
        <?php else: ?>
        <div class="products-grid">
          <?php foreach ($favorites as $product): ?>
          <?php include __DIR__ . '/includes/product_card.php'; ?>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- История просмотров -->
      <div class="profile-section" id="history">
        <h3>👁 Недавно просмотренные</h3>
        <?php if (empty($viewHistory)): ?>
        <div class="empty-state" style="padding:20px"><p>История просмотров пуста</p></div>
        <?php else: ?>
        <div style="display:flex;gap:12px;flex-wrap:wrap">
          <?php foreach ($viewHistory as $item): ?>
          <a href="/shop/product.php?id=<?= $item['id'] ?>" 
             style="display:flex;align-items:center;gap:10px;padding:10px 14px;background:var(--surface2);border-radius:var(--radius-sm);border:1px solid var(--border);max-width:280px;transition:box-shadow 0.2s">
            <?php if ($item['main_image']): ?>
            <img src="/shop/uploads/<?= htmlspecialchars($item['main_image']) ?>" 
                 style="width:48px;height:48px;object-fit:contain;border-radius:4px" alt="">
            <?php endif; ?>
            <div>
              <div style="font-size:0.85rem;font-weight:600;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">
                <?= htmlspecialchars($item['name']) ?>
              </div>
              <div style="font-size:0.78rem;color:var(--accent);margin-top:2px"><?= number_format($item['price'], 0, '', ' ') ?> ₽</div>
            </div>
          </a>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- Сессии -->
      <div class="profile-section" id="sessions">
        <h3>🔐 Активные сессии</h3>
        <?php if (empty($sessionList)): ?>
        <p style="color:var(--text-muted);font-size:0.88rem">Нет данных о сессиях</p>
        <?php else: ?>
        <?php foreach ($sessionList as $sess): ?>
        <div style="padding:12px;background:var(--surface2);border-radius:var(--radius-sm);margin-bottom:8px;font-size:0.85rem">
          <div style="font-weight:600">🌐 IP: <?= htmlspecialchars($sess['ip_address']) ?></div>
          <div style="color:var(--text-muted);margin-top:4px">
            Создана: <?= date('d.m.Y H:i', strtotime($sess['created_at'])) ?><br>
            Активность: <?= date('d.m.Y H:i', strtotime($sess['last_active'])) ?>
          </div>
          <div style="color:var(--text-muted);margin-top:4px;font-size:0.78rem">
            <?= htmlspecialchars(mb_substr($sess['user_agent'] ?? '', 0, 80)) ?>...
          </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>

    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
