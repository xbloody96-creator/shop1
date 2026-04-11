<?php
/**
 * Страница регистрации нового пользователя
 * 
 * @package Shop
 */

require_once __DIR__ . '/includes/auth.php';

// Если пользователь уже авторизован — перенаправляем в профиль
if (isLoggedIn()) {
    header('Location: /shop/profile.php');
    exit;
}

$errors = [];
$fields = [
    'email'      => '',
    'login'      => '',
    'full_name'  => '',
    'nickname'   => '',
    'birthdate'  => '',
    'gender'     => ''
];

// Обработка формы регистрации
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получаем и очищаем данные формы
    foreach ($fields as $key => $value) {
        $fields[$key] = trim($_POST[$key] ?? '');
    }
    
    $password     = $_POST['password'] ?? '';
    $passwordConf = $_POST['password_confirm'] ?? '';

    // Валидация полей
    if (!filter_var($fields['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Некорректный email';
    }
    
    if (strlen($fields['login']) < 3) {
        $errors['login'] = 'Логин должен содержать минимум 3 символа';
    }
    
    if (empty($fields['full_name'])) {
        $errors['full_name'] = 'Введите ФИО';
    }
    
    if (strlen($fields['nickname']) < 2) {
        $errors['nickname'] = 'Никнейм должен содержать минимум 2 символа';
    }

    // Проверка даты рождения
    if (!empty($fields['birthdate'])) {
        $bdTimestamp = strtotime($fields['birthdate']);
        if (!$bdTimestamp || $bdTimestamp > time()) {
            $errors['birthdate'] = 'Некорректная дата рождения';
        } elseif ($bdTimestamp < strtotime('1940-01-01')) {
            $errors['birthdate'] = 'Дата рождения должна быть не ранее 1940 года';
        }
    } else {
        $errors['birthdate'] = 'Введите дату рождения';
    }

    if (empty($fields['gender'])) {
        $errors['gender'] = 'Выберите пол';
    }
    
    if (strlen($password) < 6) {
        $errors['password'] = 'Пароль должен содержать минимум 6 символов';
    }
    
    if ($password !== $passwordConf) {
        $errors['password_confirm'] = 'Пароли не совпадают';
    }

    // Проверка уникальности email, логина и никнейма
    if (empty($errors['email'])) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$fields['email']]);
        if ($stmt->fetch()) {
            $errors['email'] = 'Этот email уже зарегистрирован';
        }
    }
    
    if (empty($errors['login'])) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE login = ?");
        $stmt->execute([$fields['login']]);
        if ($stmt->fetch()) {
            $errors['login'] = 'Этот логин уже занят';
        }
    }
    
    if (empty($errors['nickname'])) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE nickname = ?");
        $stmt->execute([$fields['nickname']]);
        if ($stmt->fetch()) {
            $errors['nickname'] = 'Этот никнейм уже занят';
        }
    }

    // Загрузка аватара
    $avatarName = 'default_avatar.png';
    if (!empty($_FILES['avatar']['name'])) {
        $fileExtension = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (!in_array($fileExtension, $allowedExtensions)) {
            $errors['avatar'] = 'Разрешены только изображения (jpg, png, gif, webp)';
        } elseif ($_FILES['avatar']['size'] > 2 * 1024 * 1024) {
            $errors['avatar'] = 'Размер файла не должен превышать 2 МБ';
        } else {
            $avatarName = 'avatar_' . uniqid() . '.' . $fileExtension;
            $uploadDir  = __DIR__ . '/uploads/avatars/';
            
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadDir . $avatarName);
            $avatarName = 'avatars/' . $avatarName;
        }
    }

    // Если ошибок нет — создаём пользователя
    if (empty($errors)) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO users (email, login, full_name, nickname, birthdate, gender, password, avatar) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([
            $fields['email'],
            $fields['login'],
            $fields['full_name'],
            $fields['nickname'],
            $fields['birthdate'],
            $fields['gender'],
            $passwordHash,
            $avatarName
        ]);
        
        header('Location: /shop/login.php?registered=1');
        exit;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
  <div class="form-card" style="max-width:640px">
    <h1>📝 Регистрация</h1>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-error">Пожалуйста, исправьте ошибки в форме</div>
    <?php endif; ?>

    <form method="POST" action="" enctype="multipart/form-data" id="register-form">

      <div class="form-row">
        <div class="form-group">
          <label>Email <span class="required">*</span></label>
          <input type="email" name="email" class="form-control <?= isset($errors['email']) ? 'error' : '' ?>"
                 value="<?= htmlspecialchars($fields['email']) ?>" placeholder="your@email.com" required>
          <?php if (isset($errors['email'])): ?>
          <span class="field-error"><?= $errors['email'] ?></span>
          <?php endif; ?>
        </div>
        <div class="form-group">
          <label>Логин <span class="required">*</span></label>
          <input type="text" name="login" class="form-control <?= isset($errors['login']) ? 'error' : '' ?>"
                 value="<?= htmlspecialchars($fields['login']) ?>" placeholder="mylogin123" required minlength="3">
          <?php if (isset($errors['login'])): ?>
          <span class="field-error"><?= $errors['login'] ?></span>
          <?php endif; ?>
        </div>
      </div>

      <div class="form-group">
        <label>ФИО <span class="required">*</span></label>
        <input type="text" name="full_name" class="form-control <?= isset($errors['full_name']) ? 'error' : '' ?>"
               value="<?= htmlspecialchars($fields['full_name']) ?>" placeholder="Иванов Иван Иванович" required>
        <?php if (isset($errors['full_name'])): ?>
        <span class="field-error"><?= $errors['full_name'] ?></span>
        <?php endif; ?>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Никнейм <span class="required">*</span></label>
          <input type="text" name="nickname" class="form-control <?= isset($errors['nickname']) ? 'error' : '' ?>"
                 value="<?= htmlspecialchars($fields['nickname']) ?>" placeholder="@nickname" required>
          <?php if (isset($errors['nickname'])): ?>
          <span class="field-error"><?= $errors['nickname'] ?></span>
          <?php endif; ?>
        </div>
        <div class="form-group">
          <label>Дата рождения <span class="required">*</span></label>
          <input type="date" name="birthdate" class="form-control <?= isset($errors['birthdate']) ? 'error' : '' ?>"
                 value="<?= htmlspecialchars($fields['birthdate']) ?>" 
                 min="1940-01-01" max="<?= date('Y-m-d') ?>" required>
          <?php if (isset($errors['birthdate'])): ?>
          <span class="field-error"><?= $errors['birthdate'] ?></span>
          <?php endif; ?>
        </div>
      </div>

      <div class="form-group">
        <label>Пол <span class="required">*</span></label>
        <select name="gender" class="form-control <?= isset($errors['gender']) ? 'error' : '' ?>" required>
          <option value="">— Выберите пол —</option>
          <option value="male"   <?= $fields['gender']==='male'   ? 'selected' : '' ?>>Мужской</option>
          <option value="female" <?= $fields['gender']==='female' ? 'selected' : '' ?>>Женский</option>
          <option value="other"  <?= $fields['gender']==='other'  ? 'selected' : '' ?>>Другой</option>
        </select>
        <?php if (isset($errors['gender'])): ?>
        <span class="field-error"><?= $errors['gender'] ?></span>
        <?php endif; ?>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Пароль <span class="required">*</span></label>
          <input type="password" name="password" class="form-control <?= isset($errors['password']) ? 'error' : '' ?>"
                 placeholder="Минимум 6 символов" required minlength="6">
          <?php if (isset($errors['password'])): ?>
          <span class="field-error"><?= $errors['password'] ?></span>
          <?php endif; ?>
        </div>
        <div class="form-group">
          <label>Повтор пароля <span class="required">*</span></label>
          <input type="password" name="password_confirm" class="form-control <?= isset($errors['password_confirm']) ? 'error' : '' ?>"
                 placeholder="Повторите пароль" required>
          <span class="field-error"><?= $errors['password_confirm'] ?? '' ?></span>
        </div>
      </div>

      <!-- Аватар -->
      <div class="form-group">
        <label>Аватар</label>
        <label class="avatar-upload" for="avatar-input">
          <img id="avatar-preview" class="avatar-preview" src="" alt="Предпросмотр">
          <div>
            <div style="font-size:2rem;margin-bottom:8px">📷</div>
            <p>Нажмите чтобы выбрать фото</p>
            <p style="font-size:0.78rem;margin-top:4px">PNG, JPG, GIF до 2 МБ</p>
          </div>
          <input type="file" name="avatar" id="avatar-input" accept="image/*">
        </label>
        <?php if (isset($errors['avatar'])): ?>
        <span class="field-error"><?= $errors['avatar'] ?></span>
        <?php endif; ?>
      </div>

      <button type="submit" class="btn-primary">Зарегистрироваться</button>
    </form>

    <p style="text-align:center;margin-top:20px;font-size:0.9rem;color:var(--text-muted)">
      Уже есть аккаунт? 
      <a href="/shop/login.php" style="color:var(--accent2);font-weight:700">Войти</a>
    </p>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
