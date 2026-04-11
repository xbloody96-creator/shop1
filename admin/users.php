/**
 * Файл: users.php
 * Описание: Страница сайта
 * @version 1.0
 */

<?php require_once __DIR__ . '/header.php';

if (isset($_GET['delete']) && (int)$_GET['delete']) {
    $userId = (int)$_GET['delete'];
    if ($userId !== (int)$_SESSION['user_id']) { // нельзя удалить себя
        // SQL Запрос: удаление данных
    $pdo->prepare("DELETE FROM users WHERE id=? AND role='user'")->execute([$userId]);
    }
    // Перенаправление пользователя
header('Location: /shop/admin/users.php?deleted=1'); exit;
}

$users = $pdo->query("SELECT u.*, (SELECT COUNT(*) FROM orders WHERE user_id=u.id) as orders_count FROM users u ORDER BY u.created_at DESC")->fetchAll();
?>

<h1>👥 Пользователи (<?= count($users) ?>)</h1>
<?php if (isset($_GET['deleted'])): ?><div class="alert alert-success">Пользователь удалён</div><?php endif; ?>

<table class="admin-table">
  <thead>
    <tr><th>ID</th><th>Аватар</th><th>ФИО</th><th>Email</th><th>Логин</th><th>Роль</th><th>Заказов</th><th>Дата рег.</th><th></th></tr>
  </thead>
  <tbody>
    <?php foreach ($users as $u): ?>
    <tr>
      <td><?= $u['id'] ?></td>
      <td>
        <?php
        $avt = ($u['avatar'] && $u['avatar'] !== 'default_avatar.png') ? '/shop/uploads/'.$u['avatar'] : 'https://ui-avatars.com/api/?name='.urlencode($u['full_name']).'&size=40&background=e63946&color=fff';
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
      <td><?= $u['orders_count'] ?></td>
      <td style="font-size:0.82rem"><?= date('d.m.Y', strtotime($u['created_at'])) ?></td>
      <td>
        <?php if ($u['role'] !== 'admin' && $u['id'] !== (int)$_SESSION['user_id']): ?>
        <a href="?delete=<?= $u['id'] ?>" class="btn-sm btn-delete js-confirm-delete">🗑</a>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php require_once __DIR__ . '/footer.php'; ?>
