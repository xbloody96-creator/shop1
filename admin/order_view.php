<?php require_once __DIR__ . '/header.php';
$id = (int)($_GET['id']??0);

// 🔧 ИСПРАВЛЕНО: phone берём из orders (o.phone), а не из users
$stmt = $pdo->prepare("SELECT o.*, u.full_name as uname, u.email as uemail, u.login as nickname 
                       FROM orders o 
                       JOIN users u ON o.user_id = u.id 
                       WHERE o.id = ?");
$stmt->execute([$id]);
$order = $stmt->fetch();

if (!$order) { 
    header('Location: /admin/orders.php'); 
    exit; 
}

$items = $pdo->prepare("SELECT oi.*, p.name, p.main_image 
                        FROM order_items oi 
                        JOIN products p ON oi.product_id = p.id 
                        WHERE oi.order_id = ?");
$items->execute([$id]);
$orderItems = $items->fetchAll();

$statusLabels = [
    'pending'    => ['⏳ Ожидает',    '#d97706'],
    'processing' => ['🔄 Обработка',  '#2563eb'],
    'shipped'    => ['🚚 Отправлен',  '#7c3aed'],
    'delivered'  => ['✅ Доставлен',  '#16a34a'],
    'cancelled'  => ['❌ Отменён',    '#dc2626'],
];

// Обработка формы
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['status'])) {
    $status = $_POST['status'];
    $cancelReason = trim($_POST['cancel_reason'] ?? '');
    
    if ($status === 'cancelled') {
        $pdo->prepare("UPDATE orders SET status=?, cancel_reason=?, cancelled_at=NOW() WHERE id=?")
            ->execute([$status, $cancelReason, $id]);
    } else {
        $pdo->prepare("UPDATE orders SET status=?, cancel_reason=NULL WHERE id=?")
            ->execute([$status, $id]);
    }
    header("Location: /admin/order_view.php?id=$id&saved=1"); 
    exit;
}

$st = $statusLabels[$order['status']] ?? ['—','#888'];
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
    <h1>🛒 Заказ #<?= $order['id'] ?></h1>
    <a href="/admin/orders.php" style="color:var(--text-muted)">← К заказам</a>
</div>

<?php if (isset($_GET['saved'])): ?>
    <div class="alert alert-success">Статус обновлён ✅</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 320px;gap:20px">
    <!-- Товары -->
    <div>
        <div class="admin-form-card" style="margin-bottom:16px">
            <h3 style="font-weight:700;margin-bottom:16px">📦 Состав заказа</h3>
            <table class="admin-table">
                <thead>
                    <tr><th>Фото</th><th>Товар</th><th>Цена</th><th>Кол-во</th><th>Итого</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($orderItems as $item): ?>
                    <tr>
                        <td>
                            <?php if(!empty($item['main_image'])): ?>
                                <img src="/uploads/<?= htmlspecialchars($item['main_image']) ?>" alt="" style="width:40px;height:40px;object-fit:cover;border-radius:4px">
                            <?php else: ?>
                                <span style="font-size:1.5rem">📦</span>
                            <?php endif; ?>
                        </td>
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
            <!-- 🔧 ИСПРАВЛЕНО: o.phone вместо u.phone -->
            <?php if (!empty($order['phone'])): ?>
                <p>📞 <?= htmlspecialchars($order['phone']) ?></p>
            <?php endif; ?>
            <p style="margin-top:8px;font-size:0.85rem;color:var(--text-muted)">Аккаунт: @<?= htmlspecialchars($order['nickname'] ?? '—') ?></p>
        </div>
        
        <div class="admin-form-card">
            <h3 style="font-weight:700;margin-bottom:14px">📍 Доставка</h3>
            <p><?= htmlspecialchars($order['address']) ?></p>
            <p style="margin-top:8px;color:var(--text-muted)">
                Оплата: <?= ['card'=>'💳 Карта','online'=>'📱 Онлайн','cash'=>'💵 Нал'][$order['payment_method']] ?? '—' ?>
            </p>
        </div>
        
        <div class="admin-form-card">
            <h3 style="font-weight:700;margin-bottom:14px">📊 Статус</h3>
            <div style="margin-bottom:12px">
                <span style="font-size:1.1rem;font-weight:700;color:<?= $st[1] ?>"><?= $st[0] ?></span>
            </div>
            
            <form method="POST">
                <label style="display:block;margin-bottom:8px;font-size:0.85rem;color:var(--text-muted)">Изменить статус:</label>
                <select name="status" id="status-select" class="form-control" style="margin-bottom:10px">
                    <?php foreach ($statusLabels as $k => $v): ?>
                        <option value="<?= $k ?>" <?= $order['status'] === $k ? 'selected' : '' ?>><?= $v[0] ?></option>
                    <?php endforeach; ?>
                </select>
                
                <!-- Поле причины отказа -->
                <div id="cancel-reason-block" style="display:none;margin-bottom:10px">
                    <label style="display:block;margin-bottom:8px;font-size:0.85rem;color:var(--text-muted)">Причина отказа:</label>
                    <textarea name="cancel_reason" class="form-control" rows="3" placeholder="Укажите причину отмены заказа..."></textarea>
                </div>
                
                <button type="submit" class="btn-primary" style="width:100%">Обновить статус</button>
            </form>
            
            <?php if (!empty($order['cancel_reason'])): ?>
                <div style="margin-top:16px;padding:12px;background:#fee2e2;border:1px solid #fca5a5;border-radius:6px">
                    <strong style="color:#dc2626;font-size:0.85rem">❌ Причина отмены:</strong>
                    <p style="margin:4px 0 0;font-size:0.85rem;color:#991b1b"><?= htmlspecialchars($order['cancel_reason']) ?></p>
                    <?php if (!empty($order['cancelled_at'])): ?>
                        <p style="margin:4px 0 0;font-size:0.75rem;color:#991b1b">
                            📅 <?= date('d.m.Y H:i', strtotime($order['cancelled_at'])) ?>
                        </p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <p style="margin-top:12px;font-size:0.82rem;color:var(--text-muted)">
                Оформлен: <?= date('d.m.Y H:i', strtotime($order['created_at'])) ?>
            </p>
        </div>
    </div>
</div>

<script>
const statusSelect = document.getElementById('status-select');
const cancelReasonBlock = document.getElementById('cancel-reason-block');

statusSelect?.addEventListener('change', function() {
    if (this.value === 'cancelled') {
        cancelReasonBlock.style.display = 'block';
    } else {
        cancelReasonBlock.style.display = 'none';
    }
});

// Инициализация при загрузке (если уже отменён)
<?php if ($order['status'] === 'cancelled'): ?>
    cancelReasonBlock.style.display = 'block';
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/footer.php'; ?>