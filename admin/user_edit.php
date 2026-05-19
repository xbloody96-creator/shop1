<?php require_once __DIR__ . '/header.php';

$userId = (int)($_GET['id'] ?? 0);
if (!$userId) {
    header('Location: /admin/users.php');
    exit;
}

// Получаем данные пользователя
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: /admin/users.php');
    exit;
}

$success = false;
$errors = [];

// Стандартные причины блокировки
$banReasons = [
    'Нарушение правил магазина',
    'Мошеннические действия',
    'Спам в отзывах',
    'Злоупотребление скидками',
    'Некорректное поведение с поддержкой',
    'По требованию пользователя',
    'Другая причина',
];

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName   = trim($_POST['full_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $login      = trim($_POST['login'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $role       = $_POST['role'] ?? 'user';
    $password   = trim($_POST['password'] ?? '');
    $password2  = trim($_POST['password2'] ?? '');
    $disable2fa = isset($_POST['disable_2fa']) ? 1 : 0;
    $isBanned   = isset($_POST['is_banned']) ? 1 : 0;
    $banReason  = trim($_POST['ban_reason'] ?? '');
    
    // Проверки
    if (empty($fullName)) $errors[] = 'Введите ФИО';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Некорректный email';
    if (empty($login)) $errors[] = 'Введите логин';
    
    // Проверка уникальности email (кроме текущего пользователя)
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $userId]);
    if ($stmt->fetch()) {
        $errors[] = 'Email уже используется другим пользователем';
    }
    
    // Проверка уникальности логина
    $stmt = $pdo->prepare("SELECT id FROM users WHERE login = ? AND id != ?");
    $stmt->execute([$login, $userId]);
    if ($stmt->fetch()) {
        $errors[] = 'Логин уже используется другим пользователем';
    }
    
    // Проверка паролей
    if (!empty($password)) {
        if (strlen($password) < 6) {
            $errors[] = 'Пароль должен быть не менее 6 символов';
        }
        if ($password !== $password2) {
            $errors[] = 'Пароли не совпадают';
        }
    }
    
    if (empty($errors)) {
        // Обновление данных
        $updateData = [
            $fullName,
            $email,
            $login,
            $phone,
            $role,
            $isBanned,
            $banReason,
            $userId
        ];
        
        $sql = "UPDATE users SET full_name=?, email=?, login=?, phone=?, role=?, is_banned=?, ban_reason=? WHERE id=?";
        
        // Если указан новый пароль — добавляем хэш
        if (!empty($password)) {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET full_name=?, email=?, login=?, phone=?, role=?, is_banned=?, ban_reason=?, password=? WHERE id=?";
            $updateData = [
                $fullName,
                $email,
                $login,
                $phone,
                $role,
                $isBanned,
                $banReason,
                $passwordHash,
                $userId
            ];
        }
        
        // Если отключаем 2FA
        if ($disable2fa) {
            $sql = str_replace('WHERE id=?', ', two_factor_enabled=0 WHERE id=?', $sql);
        }
        
        $pdo->prepare($sql)->execute($updateData);
        
        $success = true;
        
        // Обновляем данные пользователя для отображения
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
    }
}

// Получаем статистику пользователя
$userStats = $pdo->prepare("SELECT 
    (SELECT COUNT(*) FROM orders WHERE user_id=?) as orders_count,
    (SELECT COALESCE(SUM(total),0) FROM orders WHERE user_id=? AND status!='cancelled') as total_spent,
    (SELECT COUNT(*) FROM reviews WHERE user_id=?) as reviews_count
")->execute([$userId, $userId, $userId]);
$userStats = $pdo->prepare("SELECT 
    (SELECT COUNT(*) FROM orders WHERE user_id=?) as orders_count,
    (SELECT COALESCE(SUM(total),0) FROM orders WHERE user_id=? AND status!='cancelled') as total_spent,
    (SELECT COUNT(*) FROM reviews WHERE user_id=?) as reviews_count
")->fetch();
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
    <h1>👤 Редактирование пользователя #<?= $user['id'] ?></h1>
    <a href="/admin/users.php" style="color:var(--text-muted)">← К пользователям</a>
</div>

<?php if ($success): ?>
    <div class="alert alert-success">Данные пользователя обновлены ✅</div>
<?php endif; ?>

<?php foreach ($errors as $e): ?>
    <div class="alert alert-error"><?= htmlspecialchars($e) ?></div>
<?php endforeach; ?>

<form method="POST" action="">
    <div style="display:grid;grid-template-columns:1fr 340px;gap:20px">
        <!-- Основная информация -->
        <div style="display:flex;flex-direction:column;gap:16px">
            <div class="admin-form-card">
                <h3 style="font-weight:700;margin-bottom:16px">📋 Основная информация</h3>
                
                <div class="form-group">
                    <label>ФИО <span class="required">*</span></label>
                    <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                </div>
                
                <div class="form-row" style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div class="form-group">
                        <label>Email <span class="required">*</span></label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Логин <span class="required">*</span></label>
                        <input type="text" name="login" class="form-control" value="<?= htmlspecialchars($user['login']) ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Телефон</label>
                    <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="+7 (___) ___-__-__">
                </div>
                
                <div class="form-group">
                    <label>Роль</label>
                    <select name="role" class="form-control">
                        <option value="user" <?= $user['role']==='user'?'selected':'' ?>>👤 Пользователь</option>
                        <option value="admin" <?= $user['role']==='admin'?'selected':'' ?>>⭐ Администратор</option>
                    </select>
                </div>
            </div>
            
            <!-- Безопасность -->
            <div class="admin-form-card">
                <h3 style="font-weight:700;margin-bottom:16px">🔐 Безопасность</h3>
                
                <div class="form-group">
                    <label>Новый пароль <small style="color:var(--text-muted)">(оставьте пустым, чтобы не менять)</small></label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" autocomplete="new-password">
                </div>
                
                <div class="form-group">
                    <label>Подтверждение пароля</label>
                    <input type="password" name="password2" class="form-control" placeholder="••••••••" autocomplete="new-password">
                </div>
                
                <label style="display:flex;align-items:center;gap:10px;cursor:pointer;padding:12px 0;border-top:1px solid var(--border);margin-top:12px">
                    <input type="checkbox" name="disable_2fa" value="1" <?= !$user['two_factor_enabled'] ? 'disabled' : '' ?>>
                    <span>Отключить двухфакторную аутентификацию</span>
                    <?php if ($user['two_factor_enabled']): ?>
                        <span style="background:#fef3c7;color:#d97706;padding:2px 8px;border-radius:4px;font-size:0.75rem;margin-left:8px">Включена</span>
                    <?php else: ?>
                        <span style="background:#f0fdf4;color:#16a34a;padding:2px 8px;border-radius:4px;font-size:0.75rem;margin-left:8px">Отключена</span>
                    <?php endif; ?>
                </label>
            </div>
            
            <!-- Блокировка -->
            <div class="admin-form-card" style="border:2px solid <?= $user['is_banned'] ? '#dc2626' : 'var(--border)' ?>">
                <h3 style="font-weight:700;margin-bottom:16px;color:<?= $user['is_banned'] ? '#dc2626' : 'var(--text)' ?>">🚫 Блокировка</h3>
                
                <label style="display:flex;align-items:center;gap:10px;cursor:pointer;margin-bottom:16px">
                    <input type="checkbox" name="is_banned" value="1" <?= $user['is_banned'] ? 'checked' : '' ?>>
                    <span style="font-weight:600">Заблокировать пользователя</span>
                </label>
                
                <div style="<?= !$user['is_banned'] ? 'display:none' : '' ?>" id="ban-reason-block">
                    <div class="form-group">
                        <label>Причина блокировки</label>
                        <select id="ban-reason-select" class="form-control" onchange="handleBanReason(this.value)">
                            <option value="">— Выберите причину —</option>
                            <?php foreach ($banReasons as $reason): ?>
                                <option value="<?= htmlspecialchars($reason) ?>" <?= $user['ban_reason'] === $reason ? 'selected' : '' ?>><?= htmlspecialchars($reason) ?></option>
                            <?php endforeach; ?>
                            <option value="custom">✏️ Своя причина...</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <textarea name="ban_reason" id="ban-reason-text" class="form-control" rows="3" placeholder="Укажите причину блокировки..."><?= htmlspecialchars($user['ban_reason'] ?? '') ?></textarea>
                    </div>
                    <p style="font-size:0.78rem;color:var(--text-muted)">
                        Причина будет показана пользователю при попытке входа
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Правая колонка -->
        <div style="display:flex;flex-direction:column;gap:16px">
            <!-- Аватар -->
            <div class="admin-form-card">
                <h3 style="font-weight:700;margin-bottom:16px">🖼 Аватар</h3>
                <?php
                $avt = ($user['avatar'] && $user['avatar'] !== 'default_avatar.png') 
                    ? '/uploads/'.$user['avatar'] 
                    : 'https://ui-avatars.com/api/?name='.urlencode($user['full_name']).'&size=120&background=e63946&color=fff';
                ?>
                <img src="<?= htmlspecialchars($avt) ?>" style="width:120px;height:120px;border-radius:50%;object-fit:cover;margin:0 auto 16px;display:block;border:3px solid var(--border)">
                <a href="/profile.php?id=<?= $user['id'] ?>" target="_blank" class="btn-secondary" style="text-align:center;font-size:0.85rem">👁 Профиль на сайте</a>
            </div>
            
            <!-- Статистика -->
            <div class="admin-form-card">
                <h3 style="font-weight:700;margin-bottom:16px">📊 Статистика</h3>
                
                <div style="display:flex;flex-direction:column;gap:12px">
                    <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border)">
                        <span style="color:var(--text-muted)">Заказов</span>
                        <strong><?= $userStats['orders_count'] ?? 0 ?></strong>
                    </div>
                    <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border)">
                        <span style="color:var(--text-muted)">Потрачено</span>
                        <strong style="color:var(--accent)"><?= number_format($userStats['total_spent'] ?? 0, 0, '', ' ') ?> ₽</strong>
                    </div>
                    <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border)">
                        <span style="color:var(--text-muted)">Отзывов</span>
                        <strong><?= $userStats['reviews_count'] ?? 0 ?></strong>
                    </div>
                    <div style="display:flex;justify-content:space-between;padding:8px 0">
                        <span style="color:var(--text-muted)">Дата регистрации</span>
                        <strong style="font-size:0.85rem"><?= date('d.m.Y', strtotime($user['created_at'])) ?></strong>
                    </div>
                </div>
            </div>
            
            <!-- Действия -->
            <div class="admin-form-card">
                <h3 style="font-weight:700;margin-bottom:16px">⚡ Действия</h3>
                
                <button type="submit" class="btn-primary" style="width:100%;margin-bottom:8px">
                    💾 Сохранить изменения
                </button>
                
                <a href="/admin/orders.php?s=<?= urlencode($user['email']) ?>" class="btn-secondary" style="width:100%;text-align:center;display:block;margin-bottom:8px;font-size:0.85rem">
                    🛒 Заказы пользователя
                </a>
                
                <a href="/admin/reviews.php?s=<?= urlencode($user['email']) ?>" class="btn-secondary" style="width:100%;text-align:center;display:block;font-size:0.85rem">
                    💬 Отзывы пользователя
                </a>
            </div>
        </div>
    </div>
</form>

<script>
// Показ/скрытие поля причины блокировки
const banCheckbox = document.querySelector('input[name="is_banned"]');
const banReasonBlock = document.getElementById('ban-reason-block');
const banReasonSelect = document.getElementById('ban-reason-select');
const banReasonText = document.getElementById('ban-reason-text');

banCheckbox?.addEventListener('change', function() {
    banReasonBlock.style.display = this.checked ? 'block' : 'none';
});

function handleBanReason(value) {
    if (value === 'custom') {
        banReasonText.style.display = 'block';
        banReasonText.value = '';
        banReasonText.focus();
    } else {
        banReasonText.value = value;
    }
}

// Инициализация
<?php if ($user['is_banned']): ?>
    banReasonBlock.style.display = 'block';
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/footer.php'; ?>