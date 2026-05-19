<?php require_once __DIR__ . '/header.php';
$statusFilter = $_GET['status'] ?? '';
$where  = '1';
$params = [];
if ($statusFilter) { $where = 'o.status=:st'; $params[':st'] = $statusFilter; }
$stmt = $pdo->prepare("SELECT o.*, u.full_name, u.email as user_email FROM orders o JOIN users u ON o.user_id=u.id WHERE $where ORDER BY o.created_at DESC");
$stmt->execute($params);
$orders = $stmt->fetchAll();

$statusLabels = [
    'pending'    => ['⏳ Ожидает',    '#d97706', '#fef3c7'],
    'processing' => ['🔄 Обработка',  '#2563eb', '#dbeafe'],
    'shipped'    => ['🚚 Отправлен',  '#7c3aed', '#ede9fe'],
    'delivered'  => ['✅ Доставлен',  '#16a34a', '#dcfce7'],
    'cancelled'  => ['❌ Отменён',    '#dc2626', '#fee2e2'],
];

// Обновление статуса
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $status = $_POST['status'];
    $cancelReason = trim($_POST['cancel_reason'] ?? '');
    
    if ($status === 'cancelled') {
        $pdo->prepare("UPDATE orders SET status=?, cancel_reason=?, cancelled_at=NOW() WHERE id=?")
            ->execute([$status, $cancelReason, (int)$_POST['order_id']]);
    } else {
        $pdo->prepare("UPDATE orders SET status=?, cancel_reason=NULL WHERE id=?")
            ->execute([$status, (int)$_POST['order_id']]);
    }
    
    header('Location: /admin/orders.php?saved=1'); exit;
}
?>
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
    <h1>🛒 Заказы (<?= count($orders) ?>)</h1>
    <div style="display:flex;gap:8px">
        <?php foreach ($statusLabels as $k => $v): ?>
            <a href="?status=<?= $k ?>" style="padding:6px 14px;border-radius:6px;font-size:0.82rem;font-weight:600;background:<?= $statusFilter===$k?$v[2]:'var(--surface2)' ?>;color:<?= $statusFilter===$k?$v[1]:'var(--text-muted)' ?>;border:1px solid var(--border)"><?= $v[0] ?></a>
        <?php endforeach; ?>
        <a href="?" style="padding:6px 14px;border-radius:6px;font-size:0.82rem;font-weight:600;background:<?= !$statusFilter?'var(--accent)':'var(--surface2)' ?>;color:<?= !$statusFilter?'#fff':'var(--text-muted)' ?>">Все</a>
    </div>
</div>

<?php if (isset($_GET['saved'])): ?>
    <div class="alert alert-success">Статус обновлён ✅</div>
<?php endif; ?>

<table class="admin-table">
    <thead>
        <tr><th>#</th><th>Клиент</th><th>Сумма</th><th>Адрес</th><th>Оплата</th><th>Дата</th><th>Статус</th><th></th></tr>
    </thead>
    <tbody>
        <?php if (empty($orders)): ?>
            <tr><td colspan="8" style="text-align:center;padding:30px;color:var(--text-muted)">Заказов нет</td></tr>
        <?php endif; ?>
        <?php foreach ($orders as $o):
            $st = $statusLabels[$o['status']] ?? ['—','#888','#eee'];
        ?>
        <tr>
            <td><strong>#<?= $o['id'] ?></strong></td>
            <td>
                <strong><?= htmlspecialchars($o['full_name']) ?></strong><br>
                <small style="color:var(--text-muted)"><?= htmlspecialchars($o['user_email']) ?></small>
            </td>
            <td><strong><?= number_format($o['total'],0,'', ' ') ?> ₽</strong></td>
            <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;font-size:0.82rem"><?= htmlspecialchars($o['address']) ?></td>
            <td style="font-size:0.82rem"><?= ['card'=>'💳 Карта','online'=>'📱 Онлайн','cash'=>'💵 Нал'][$o['payment_method']] ?? '—' ?></td>
            <td style="font-size:0.82rem"><?= date('d.m.Y H:i', strtotime($o['created_at'])) ?></td>
            <td>
                <span style="color:<?= $st[1] ?>;font-weight:600"><?= $st[0] ?></span>
                <?php if ($o['status'] === 'cancelled' && $o['cancel_reason']): ?>
                    <br><small style="color:#dc2626;font-size:0.75rem">⚠️ <?= htmlspecialchars(mb_substr($o['cancel_reason'], 0, 30)) ?>...</small>
                <?php endif; ?>
            </td>
            <td><a href="/admin/order_view.php?id=<?= $o['id'] ?>" class="btn-sm btn-edit">Открыть</a></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require_once __DIR__ . '/footer.php'; ?>