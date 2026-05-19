<?php require_once __DIR__ . '/header.php'; ?>

<h1>📊 Статистика магазина</h1>

<!-- Карточки статистики -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-label">Всего заказов</div>
    <div class="stat-value"><?= $stats['orders'] ?></div>
    <div class="stat-icon">🛒</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Выручка</div>
    <div class="stat-value"><?= number_format($stats['revenue'], 0, '', ' ') ?> ₽</div>
    <div class="stat-icon">💰</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Товаров</div>
    <div class="stat-value"><?= $stats['products'] ?></div>
    <div class="stat-icon">📦</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Пользователей</div>
    <div class="stat-value"><?= $stats['users'] ?></div>
    <div class="stat-icon">👥</div>
  </div>
</div>

<!-- Дополнительная статистика -->
<?php
$pending   = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn();
$lowStock  = $pdo->query("SELECT * FROM products WHERE stock > 0 AND stock <= 5 AND is_active=1 ORDER BY stock ASC LIMIT 5")->fetchAll();
$newReviews= (int)$pdo->query("SELECT COUNT(*) FROM reviews WHERE is_approved=0")->fetchColumn();
$recentOrders = $pdo->query("SELECT o.*, u.full_name FROM orders o JOIN users u ON o.user_id=u.id ORDER BY o.created_at DESC LIMIT 8")->fetchAll();

$statusLabels = [
    'pending'    => ['⏳ Ожидает',    '#d97706'],
    'processing' => ['🔄 Обработка',  '#2563eb'],
    'shipped'    => ['🚚 Отправлен',  '#7c3aed'],
    'delivered'  => ['✅ Доставлен',  '#16a34a'],
    'cancelled'  => ['❌ Отменён',    '#dc2626'],
];
?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px">
  <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:16px">
    <h3 style="font-weight:700;margin-bottom:12px">⚡ Быстрые действия</h3>
    <div style="display:flex;flex-direction:column;gap:8px">
      <a href="/admin/product_edit.php" class="btn-add" style="justify-content:center">➕ Добавить товар</a>
      <a href="/admin/news_edit.php"    class="btn-add" style="background:var(--accent2);justify-content:center">➕ Добавить новость</a>
      <a href="/admin/promotions.php"   class="btn-add" style="background:#16a34a;justify-content:center">➕ Добавить акцию</a>
    </div>
  </div>

  <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:16px">
    <h3 style="font-weight:700;margin-bottom:12px">🔔 Уведомления</h3>
    <?php if ($pending > 0): ?>
    <a href="/admin/orders.php?status=pending" style="display:flex;align-items:center;gap:8px;padding:8px 0;border-bottom:1px solid var(--border);color:#d97706;font-weight:600;font-size:0.9rem">
      ⏳ Новых заказов: <?= $pending ?>
    </a>
    <?php endif; ?>
    <?php if ($newReviews > 0): ?>
    <a href="/admin/reviews.php?pending=1" style="display:flex;align-items:center;gap:8px;padding:8px 0;border-bottom:1px solid var(--border);color:#2563eb;font-weight:600;font-size:0.9rem">
      💬 Отзывов на модерации: <?= $newReviews ?>
    </a>
    <?php endif; ?>
    <?php if (!empty($lowStock)): ?>
    <div style="padding:8px 0;color:#dc2626;font-weight:600;font-size:0.9rem">
      ⚠️ Заканчиваются товары: <?= count($lowStock) ?> позиций
    </div>
    <?php endif; ?>
    <?php if (!$pending && !$newReviews && empty($lowStock)): ?>
    <p style="color:var(--text-muted);font-size:0.88rem">Всё в порядке ✅</p>
    <?php endif; ?>
  </div>
</div>

<!-- Товары с низким остатком -->
<?php if (!empty($lowStock)): ?>
<div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:20px;margin-bottom:24px">
  <h3 style="font-weight:700;margin-bottom:12px">⚠️ Заканчиваются товары</h3>
  <table class="admin-table">
    <thead><tr><th>Фото</th><th>Товар</th><th>Остаток</th><th>Действие</th></tr></thead>
    <tbody>
      <?php foreach ($lowStock as $p): ?>
      <tr>
        <td><?php if($p['main_image']): ?><img src="/uploads/<?= htmlspecialchars($p['main_image']) ?>" alt=""><?php endif; ?></td>
        <td><?= htmlspecialchars($p['name']) ?></td>
        <td><strong style="color:#dc2626"><?= $p['stock'] ?> шт.</strong></td>
        <td><a href="/admin/product_edit.php?id=<?= $p['id'] ?>" class="btn-sm btn-edit">Редактировать</a></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<!-- Последние заказы -->
<div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:20px">
  <h3 style="font-weight:700;margin-bottom:12px">🛒 Последние заказы</h3>
  <?php if (empty($recentOrders)): ?>
  <p style="color:var(--text-muted)">Заказов ещё нет</p>
  <?php else: ?>
  <table class="admin-table">
    <thead><tr><th>#</th><th>Клиент</th><th>Сумма</th><th>Статус</th><th>Дата</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($recentOrders as $o):
        $st = $statusLabels[$o['status']] ?? ['—', '#888']; ?>
      <tr>
        <td><strong>#<?= $o['id'] ?></strong></td>
        <td><?= htmlspecialchars($o['full_name']) ?></td>
        <td><strong><?= number_format($o['total'],0,'', ' ') ?> ₽</strong></td>
        <td><span style="color:<?= $st[1] ?>;font-weight:600"><?= $st[0] ?></span></td>
        <td><?= date('d.m.Y H:i', strtotime($o['created_at'])) ?></td>
        <td><a href="/admin/order_view.php?id=<?= $o['id'] ?>" class="btn-sm btn-edit">Открыть</a></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
