<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$userId = $_SESSION['user_id'];
$user   = getCurrentUser();

// Проверка что корзина не пуста
$stmt = $pdo->prepare("SELECT c.*, p.name, p.price, p.main_image FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
$stmt->execute([$userId]);
$cartItems = $stmt->fetchAll();

if (empty($cartItems)) {
    header('Location: /shop/cart.php');
    exit;
}

$total = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $cartItems));

$errors  = [];
$success = false;
$orderId = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName      = trim($_POST['full_name'] ?? '');
    $email         = trim($_POST['email'] ?? '');
    $phone         = trim($_POST['phone'] ?? '');
    $address       = trim($_POST['address'] ?? '');
    $paymentMethod = $_POST['payment_method'] ?? 'card';

    if (empty($fullName)) $errors[] = 'Введите ФИО';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Некорректный email';
    if (empty($address)) $errors[] = 'Введите адрес доставки';

    if (empty($errors)) {
        // Создаём заказ
        $pdo->prepare("INSERT INTO orders (user_id, full_name, email, phone, address, payment_method, total) VALUES (?,?,?,?,?,?,?)")
            ->execute([$userId, $fullName, $email, $phone, $address, $paymentMethod, $total]);
        $orderId = $pdo->lastInsertId();

        // Добавляем позиции заказа
        foreach ($cartItems as $item) {
            $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?,?,?,?)")
                ->execute([$orderId, $item['product_id'], $item['quantity'], $item['price']]);
        }

        // Очищаем корзину
        $pdo->prepare("DELETE FROM cart WHERE user_id = ?")->execute([$userId]);

        // Отправляем email (phpmailer или mail())
        $subject = "Заказ #$orderId подтверждён — Магазин";
        $msg = "Здравствуйте, $fullName!\n\n";
        $msg .= "Ваш заказ #$orderId успешно оформлен.\n\n";
        $msg .= "Состав заказа:\n";
        foreach ($cartItems as $item) {
            $msg .= "- {$item['name']} × {$item['quantity']} = " . number_format($item['price'] * $item['quantity'], 0, '', ' ') . " ₽\n";
        }
        $msg .= "\nИтого: " . number_format($total, 0, '', ' ') . " ₽\n";
        $msg .= "Адрес доставки: $address\n\n";
        $msg .= "Спасибо за покупку!\nМагазин";
        @mail($email, $subject, $msg, "From: noreply@magazin.ru\r\nContent-Type: text/plain; charset=UTF-8");

        $success = true;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
  <div class="breadcrumbs">
    <a href="/shop/index.php">Главная</a> / <a href="/shop/cart.php">Корзина</a> / <span>Оформление заказа</span>
  </div>
  <h1 style="font-size:1.6rem;font-weight:800;margin:12px 0 24px">📦 Оформление заказа</h1>

  <?php if ($success): ?>
  <?php
  // ── FreeKassa настройки ──────────────────────
  define('FK_MERCHANT_ID', 'ВАШЕ_ID_МАГАЗИНА');
  define('FK_SECRET1',     'ВАШ_СЕКРЕТНЫЙ_КЛЮЧ_1');
  define('FK_CURRENCY',    'RUB'); // RUB, USD, EUR и др.

  // Формируем подпись для формы оплаты
  // Формат: MD5(MERCHANT_ID:AMOUNT:SECRET1:CURRENCY:MERCHANT_ORDER_ID)
  $fkAmount = number_format($total, 2, '.', '');
  $fkSign   = md5(FK_MERCHANT_ID . ':' . $fkAmount . ':' . FK_SECRET1 . ':' . FK_CURRENCY . ':' . $orderId);
  ?>

  <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);padding:48px 40px;text-align:center;max-width:580px;margin:0 auto;position:relative;overflow:hidden">
    <div style="position:absolute;top:-60px;left:50%;transform:translateX(-50%);width:220px;height:220px;border-radius:50%;background:radial-gradient(circle,rgba(0,214,143,0.12),transparent 70%);pointer-events:none"></div>

    <div style="width:76px;height:76px;border-radius:50%;background:rgba(0,214,143,0.1);border:2px solid rgba(0,214,143,0.25);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:2rem">✅</div>

    <h2 style="font-family:var(--font-d);font-size:1.3rem;font-weight:900;margin-bottom:10px">Заказ #<?= $orderId ?> оформлен!</h2>
    <p style="color:var(--text2);margin-bottom:28px;font-size:0.9rem">
      Для завершения покупки оплатите заказ.<br>
      После оплаты мы начнём его обработку.
    </p>

    <!-- Сумма к оплате -->
    <div style="background:var(--surface2);border:1px solid var(--border);border-radius:var(--radius-md);padding:16px 20px;margin-bottom:24px;display:flex;justify-content:space-between;align-items:center">
      <span style="font-size:0.85rem;color:var(--text3)">Сумма к оплате</span>
      <span style="font-family:var(--font-d);font-size:1.3rem;font-weight:900;color:var(--accent)"><?= number_format($total, 0, '', ' ') ?> ₽</span>
    </div>

    <!-- Форма FreeKassa -->
    <form action="https://pay.fk.ru/" method="POST">
      <input type="hidden" name="m"   value="<?= FK_MERCHANT_ID ?>">
      <input type="hidden" name="oa"  value="<?= $fkAmount ?>">
      <input type="hidden" name="currency" value="<?= FK_CURRENCY ?>">
      <input type="hidden" name="o"   value="<?= $orderId ?>">
      <input type="hidden" name="s"   value="<?= $fkSign ?>">
      <input type="hidden" name="em"  value="<?= htmlspecialchars($user['email'] ?? '') ?>">
      <input type="hidden" name="lang" value="ru">

      <button type="submit" class="btn-primary" style="font-size:0.88rem;padding:15px">
        💳 Оплатить через FreeKassa
      </button>
    </form>

    <p style="margin-top:14px;font-size:0.78rem;color:var(--text3)">
      Вы будете перенаправлены на защищённую страницу оплаты
    </p>

    <div style="display:flex;gap:10px;margin-top:20px">
      <a href="/shop/index.php" class="btn-secondary" style="flex:1;font-size:0.82rem">На главную</a>
      <a href="/shop/profile.php#orders" class="btn-secondary" style="flex:1;font-size:0.82rem">Мои заказы</a>
    </div>
  </div>
  <?php else: ?>

  <?php foreach ($errors as $e): ?>
  <div class="alert alert-error"><?= htmlspecialchars($e) ?></div>
  <?php endforeach; ?>

  <div style="display:grid;grid-template-columns:1fr 380px;gap:24px">
    <form method="POST" action="">
      <!-- Данные покупателя -->
      <div class="admin-form-card" style="margin-bottom:16px">
        <h3 style="font-weight:800;margin-bottom:16px">👤 Данные покупателя</h3>
        <div class="form-row">
          <div class="form-group">
            <label>ФИО <span class="required">*</span></label>
            <input type="text" name="full_name" class="form-control" 
                   value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" required>
          </div>
          <div class="form-group">
            <label>Email <span class="required">*</span></label>
            <input type="email" name="email" class="form-control"
                   value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
          </div>
        </div>
        <div class="form-group">
          <label>Телефон</label>
          <input type="tel" name="phone" class="form-control" placeholder="+7 (___) ___-__-__">
        </div>
      </div>

      <!-- Доставка -->
      <div class="admin-form-card" style="margin-bottom:16px">
        <h3 style="font-weight:800;margin-bottom:16px">🚚 Адрес доставки</h3>
        <div class="form-group">
          <label>Адрес <span class="required">*</span></label>
          <textarea name="address" class="form-control" rows="3" 
                    placeholder="Город, улица, дом, квартира" required></textarea>
        </div>
      </div>

      <!-- Способ оплаты -->
      <div class="admin-form-card">
        <h3 style="font-weight:800;margin-bottom:16px">💳 Способ оплаты</h3>
        <div style="display:flex;flex-direction:column;gap:12px">
          <label style="display:flex;align-items:center;gap:12px;padding:14px;border:2px solid var(--border);border-radius:var(--radius-sm);cursor:pointer">
            <input type="radio" name="payment_method" value="card" checked>
            <span>💳 Банковская карта</span>
          </label>
          <label style="display:flex;align-items:center;gap:12px;padding:14px;border:2px solid var(--border);border-radius:var(--radius-sm);cursor:pointer">
            <input type="radio" name="payment_method" value="online">
            <span>📱 Онлайн-оплата</span>
          </label>
          <label style="display:flex;align-items:center;gap:12px;padding:14px;border:2px solid var(--border);border-radius:var(--radius-sm);cursor:pointer">
            <input type="radio" name="payment_method" value="cash">
            <span>💵 Наличными при получении</span>
          </label>
        </div>
      </div>

      <button type="submit" class="btn-primary" style="margin-top:20px">✅ Подтвердить заказ</button>
    </form>

    <!-- Итог -->
    <div class="cart-summary" style="position:sticky;top:120px;height:fit-content">
      <h3>Ваш заказ</h3>
      <?php foreach ($cartItems as $item): ?>
      <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border);font-size:0.88rem">
        <span><?= htmlspecialchars(mb_substr($item['name'],0,30)) ?> ×<?= $item['quantity'] ?></span>
        <span><?= number_format($item['price'] * $item['quantity'], 0, '', ' ') ?> ₽</span>
      </div>
      <?php endforeach; ?>
      <div class="summary-row" style="margin-top:8px">
        <span>Доставка</span>
        <span style="color:var(--success)">Бесплатно</span>
      </div>
      <div class="summary-row total">
        <span>Итого</span>
        <span><?= number_format($total, 0, '', ' ') ?> ₽</span>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
