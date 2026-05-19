<?php require_once __DIR__ . '/header.php';

// Удаление пользователя
if (isset($_GET['delete']) && (int)$_GET['delete']) {
    $uid = (int)$_GET['delete'];
    if ($uid !== (int)$_SESSION['user_id']) {
        $pdo->prepare("DELETE FROM users WHERE id=? AND role='user'")->execute([$uid]);
    }
    header('Location: /admin/users.php?deleted=1'); exit;
}

// Очистка истории заказов пользователя
if (isset($_GET['clear_history']) && (int)$_GET['clear_history']) {
    $uid = (int)$_GET['clear_history'];
    $pdo->prepare("DELETE FROM orders WHERE user_id=?")->execute([$uid]);
    header('Location: /admin/users.php?history_cleared=1'); exit;
}

// Быстрая блокировка/разблокировка
if (isset($_GET['toggle_ban']) && (int)$_GET['toggle_ban']) {
    $uid = (int)$_GET['toggle_ban'];
    $ban = (int)$_GET['ban'];
    if ($uid !== (int)$_SESSION['user_id']) {
        $pdo->prepare("UPDATE users SET is_banned=?, ban_reason=? WHERE id=?")
            ->execute([$ban, $ban ? 'Заблокирован администратором' : null, $uid]);
    }
    header('Location: /admin/users.php?ban_toggled=1'); exit;
}

$users = $pdo->query("
    SELECT u.*, 
           (SELECT COUNT(*) FROM orders WHERE user_id=u.id) as orders_count,
           (SELECT COALESCE(SUM(total),0) FROM orders WHERE user_id=u.id AND status!='cancelled') as total_spent,
           (SELECT COUNT(*) FROM reviews WHERE user_id=u.id) as reviews_count
    FROM users u 
    ORDER BY u.created_at DESC
")->fetchAll();
?>
<h1>👥 Пользователи (<?= count($users) ?>)</h1>

<?php if (isset($_GET['deleted'])): ?>
    <div class="alert alert-success">Пользователь удалён ✅</div>
<?php endif; ?>

<?php if (isset($_GET['history_cleared'])): ?>
    <div class="alert alert-success">История заказов очищена ✅</div>
<?php endif; ?>

<?php if (isset($_GET['ban_toggled'])): ?>
    <div class="alert alert-success">Статус блокировки изменён ✅</div>
<?php endif; ?>

<table class="admin-table">
<thead>
<tr>
    <th>ID</th>
    <th>Аватар</th>
    <th>ФИО</th>
    <th>Email</th>
    <th>Логин</th>
    <th>Роль</th>
    <th>Заказов</th>
    <th>Потрачено</th>
    <th>Отзывов</th>
    <th>Статус</th>
    <th>Дата рег.</th>
    <th>Действия</th>
</tr>
</thead>
<tbody>
<?php foreach ($users as $u): ?>
<tr>
    <td><?= $u['id'] ?></td>
    <td>
        <?php
        $avt = ($u['avatar'] && $u['avatar'] !== 'default_avatar.png') 
            ? '/uploads/'.$u['avatar'] 
            : 'https://ui-avatars.com/api/?name='.urlencode($u['full_name']).'&size=40&background=e63946&color=fff';
        ?>
        <img src="<?= htmlspecialchars($avt) ?>" style="width:36px;height:36px;border-radius:50%;object-fit:cover">
    </td>
    <td><strong><?= htmlspecialchars($u['full_name']) ?></strong></td>
    <td><?= htmlspecialchars($u['email']) ?></td>
    <td style="color:var(--text-muted)">@<?= htmlspecialchars($u['login']) ?></td>
    <td>
        <?php if ($u['role']==='admin'): ?>
            <span style="background:#fef3c7;color:#d97706;padding:3px 10px;border-radius:4px;font-size:0.78rem;font-weight:700">⭐ Админ</span>
        <?php else: ?>
            <span style="background:#f0fdf4;color:#16a34a;padding:3px 10px;border-radius:4px;font-size:0.78rem;font-weight:700">👤 Юзер</span>
        <?php endif; ?>
    </td>
    <td>
        <span style="font-weight:600"><?= $u['orders_count'] ?></span>
    </td>
    <td>
        <span style="color:var(--accent);font-weight:600"><?= number_format($u['total_spent'], 0, '', ' ') ?> ₽</span>
    </td>
    <td>
        <span style="font-weight:600"><?= $u['reviews_count'] ?></span>
    </td>
    <td>
        <?php if ($u['is_banned']): ?>
            <span style="background:#fee2e2;color:#dc2626;padding:3px 10px;border-radius:4px;font-size:0.78rem;font-weight:700">🚫 Забанен</span>
        <?php elseif ($u['two_factor_enabled']): ?>
            <span style="background:#fef3c7;color:#d97706;padding:3px 10px;border-radius:4px;font-size:0.78rem;font-weight:700">🔐 2FA</span>
        <?php else: ?>
            <span style="background:#f0fdf4;color:#16a34a;padding:3px 10px;border-radius:4px;font-size:0.78rem;font-weight:700">✅ Активен</span>
        <?php endif; ?>
    </td>
    <td style="font-size:0.82rem"><?= date('d.m.Y', strtotime($u['created_at'])) ?></td>
    <td style="white-space:nowrap">
        <!-- Кнопка редактирования -->
        <a href="/admin/user_edit.php?id=<?= $u['id'] ?>" 
           class="btn-sm btn-edit" 
           style="background:#2563eb;color:#fff"
           title="Редактировать пользователя">
            ✏ Ред.
        </a>
        
        <!-- Кнопка блокировки/разблокировки -->
        <?php if ($u['role'] !== 'admin' && $u['id'] !== (int)$_SESSION['user_id']): ?>
            <a href="?toggle_ban=<?= $u['id'] ?>&ban=<?= $u['is_banned'] ? 0 : 1 ?>" 
               class="btn-sm btn-edit" 
               style="background:<?= $u['is_banned'] ? '#f0fdf4' : '#fee2e2' ?>;color:<?= $u['is_banned'] ? '#16a34a' : '#dc2626' ?>"
               onclick="return confirm('⚠️ Вы уверены?\n\n<?= $u['is_banned'] ? 'Разблокировать' : 'Заблокировать' ?> пользователя <?= htmlspecialchars($u['full_name']) ?>?')"
               title="<?= $u['is_banned'] ? 'Разблокировать' : 'Заблокировать' ?>">
                <?= $u['is_banned'] ? '🔓' : '🔒' ?>
            </a>
        <?php endif; ?>
        
        <!-- Кнопка очистки истории -->
        <?php if ($u['orders_count'] > 0): ?>
            <a href="?clear_history=<?= $u['id'] ?>" 
               class="btn-sm btn-edit" 
               style="background:#fef3c7;color:#d97706"
               onclick="return confirm('⚠️ Вы уверены?\n\nЭто удалит ВСЕ заказы пользователя #<?= $u['id'] ?> (<?= $u['orders_count'] ?> шт.)\n\nЭто действие нельзя отменить!')"
               title="Очистить историю заказов">
                🗑 История
            </a>
        <?php endif; ?>
        
        <!-- Кнопка удаления пользователя -->
        <?php if ($u['role'] !== 'admin' && $u['id'] !== (int)$_SESSION['user_id']): ?>
            <a href="?delete=<?= $u['id'] ?>" 
               class="btn-sm btn-delete js-confirm-delete"
               onclick="return confirm('⚠️ Вы уверены?\n\nПользователь <?= htmlspecialchars($u['full_name']) ?> будет удалён безвозвратно!\n\nВсе заказы и отзывы также будут удалены!')"
               title="Удалить пользователя">
                🗑
            </a>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<div style="margin-top:20px;padding:16px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius)">
    <h4 style="margin-bottom:8px;font-size:0.95rem">ℹ️ Информация</h4>
    <p style="font-size:0.85rem;color:var(--text-muted);margin:0">
        <strong>✏ Ред.</strong> — редактирование данных пользователя (email, логин, пароль, 2FA, блокировка)<br>
        <strong>🔒/🔓</strong> — быстрая блокировка/разблокировка пользователя<br>
        <strong>🗑 История</strong> — удаляет все заказы пользователя (для очистки при тестировании)<br>
        <strong>🗑 Пользователь</strong> — удаляет аккаунт пользователя полностью
    </p>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>