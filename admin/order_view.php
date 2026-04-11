/**
 * Файл: order_view.php
 * Описание: Страница сайта
 * @version 1.0
 */

<?php require_once __DIR__ . '/header.php';

$id = (int)($_GET['id']??0);
$stmt = // SQL Запрос: выборка данных
    $pdo->prepare("SELECT o.*, u.full_name as uname, u.email as uemail, u.nickname FROM orders o JOIN users u ON o.user_id=u.id WHERE o.id=?");
$stmt->execute([$id]);
$order = $stmt->fetch();
if (!$order) { // Перенаправление пользователя
header('Location: /shop/admin/orders.php'); exit; }

$items = // SQL Запрос: выборка данных
    $pdo->prepare("SELECT oi.*, p.name, p.main_image FROM order_items oi JOIN products p ON oi.product_id=p.id WHERE oi.order_id=?");
$items->execute([$id]);
$orderItems = $items->fetchAll();

$statusLabels = [
    'pending'    => ['⏳ Ожидает',    '#d97706'],
    'processing' => ['🔄 Обработка',  '#2563eb'],
    'shipped'    => ['🚚 Отправлен',  '#7c3aed'],
    'delivered'  => ['✅ Доставлен',  '#16a34a'],
    'cancelled'  => ['❌ Отменён',    '#dc2626'],
];

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['status'])) {
    // SQL Запрос: обновление данных
    $pdo->prepare("UPDATE orders SET status=? WHERE id=?")->execute([$_POST['status'], $id]);
    header("Location: /shop/admin/order_view.php?id=$id&saved=1"); exit;
}

$st = $statusLabels[$order['status']] ?? ['—','#888'];
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
  <h1>🛒 Заказ #<?= $order['id'] ?></h1>
  <a href="/shop/admin/orders.php" style="color:var(--text-muted)">← К заказам</a>
</div>

<?php if (isset($_GET['saved'])): ?><div class="alert alert-success">Статус обновлён</div><?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 320px;gap:20px">
  <!-- Товары -->
  <div>
    <div class="admin-form-card" style="margin-bottom:16px">
      <h3 style="font-weight:700;margin-bottom:16px">📦 Состав заказа</h3>
      <table class="admin-table">
        <thead><tr><th>Фото</th><th>Товар</th><th>Цена</th><th>Кол-во</th><th>Итого</th></tr></thead>
        <tbody>
          <?php foreach ($orderItems as $item): ?>
          <tr>
            <td><?php if($item['main_image']): ?><img src="/shop/uploads/<?= htmlspecialchars($item['main_image']) ?>" alt=""><?php endif; ?></td>
            <td><?= htmlspecialchars($item['name']) ?></td>
            <td><?= number_format($item['price'],0,'', ' ') ?> ₽</td>
            <td><?= $item['quantity'] ?></td>
            <td><strong><?= number_format($item['price']*$item['quantity'],0,'', ' ') ?> ₽</strong></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <div style="text-align:right;margin-top:12px;font-size:1.1rem;font-weight:800;color:var(--accent)">
        Итого: <?= number_format($order['total'],0,'', ' ') ?> ₽
      </div>
    </div>
  </div>

  <!-- Детали -->
  <div style="display:flex;flex-direction:column;gap:16px">
    <div class="admin-form-card">
      <h3 style="font-weight:700;margin-bottom:14px">👤 Покупатель</h3>
      <p><strong><?= htmlspecialchars($order['full_name']) ?></strong></p>
      <p style="color:var(--text-muted)"><?= htmlspecialchars($order['email']) ?></p>
      <?php if ($order['phone']): ?>
      <p><?= htmlspecialchars($order['phone']) ?></p>
      <?php endif; ?>
      <p style="margin-top:8px;font-size:0.85rem;color:var(--text-muted)">Аккаунт: @<?= htmlspecialchars($order['nickname']) ?></p>
    </div>

    <div class="admin-form-card">
      <h3 style="font-weight:700;margin-bottom:14px">📍 Доставка</h3>
      <p><?= htmlspecialchars($order['address']) ?></p>
      <p style="margin-top:8px;color:var(--text-muted)">
        Оплата: <?= ['card'=>'💳 Карта','online'=>'📱 Онлайн','cash'=>'💵 Нал'][$order['payment_method']]??'—' ?>
      </p>
    </div>

    <div class="admin-form-card">
      <h3 style="font-weight:700;margin-bottom:14px">📊 Статус</h3>
      <div style="margin-bottom:12px">
        <span style="font-size:1.1rem;font-weight:700;color:<?= $st[1] ?>"><?= $st[0] ?></span>
      </div>
      <form method="POST">
        <select name="status" class="form-control" style="margin-bottom:10px">
          <?php foreach ($statusLabels as $k=>$v): ?>
          <option value="<?= $k ?>" <?= $order['status']===$k?'selected':'' ?>><?= $v[0] ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="btn-primary">Обновить статус</button>
      </form>
      <p style="margin-top:12px;font-size:0.82rem;color:var(--text-muted)">
        Оформлен: <?= date('d.m.Y H:i', strtotime($order['created_at'])) ?>
      </p>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
