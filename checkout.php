/**
 * Файл: checkout.php
 * Описание: Страница сайта
 * @version 1.0
 */

<?php
/**
 * Страница оформления заказа (Checkout)
 * 
 * Позволяет пользователю ввести данные доставки и оплаты,
 * создаёт новый заказ в базе данных.
 */

// Подключение модуля аутентификации
require_once __DIR__ . '/includes/auth.php';
requireLogin(); // Требуется авторизация

$userId = $_SESSION['user_id'];
$user = getCurrentUser(); // Получаем данные текущего пользователя

/**
 * Получаем товары из корзины пользователя
 * Если корзина пуста - перенаправляем на страницу корзины
 */
$sql = "SELECT c.*, p.name, p.price, p.main_image 
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ?";
        
$stmt = $pdo->prepare($sql);
$stmt->execute([$userId]);
$cartItems = $stmt->fetchAll();

// Проверяем, есть ли товары в корзине
if (empty($cartItems)) {
    // Перенаправление пользователя
header('Location: /shop/cart.php');
    exit;
}

// Считаем общую сумму заказа
$orderTotal = array_sum(array_map(function($item) {
    return $item['price'] * $item['quantity'];
}, $cartItems));

// Переменные для обработки формы
$errors = [];
$success = false;
$orderId = null;

/**
 * Обработка отправки формы оформления заказа
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получаем и очищаем данные из формы
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $paymentMethod = $_POST['payment_method'] ?? 'card';

    // Валидация данных
    if (empty($fullName)) {
        $errors[] = 'Введите ФИО получателя';
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Введите корректный email адрес';
    }
    
    if (empty($address)) {
        $errors[] = 'Укажите адрес доставки';
    }

    // Если ошибок нет - создаём заказ
    if (empty($errors)) {
        try {
            // Начинаем транзакцию для надёжности
            $pdo->beginTransaction();
            
            // Создаём запись заказа в БД
            $insertOrder = $pdo->prepare("
                INSERT INTO orders (user_id, full_name, email, phone, address, payment_method, total) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $insertOrder->execute([
                $userId, 
                $fullName, 
                $email, 
                $phone, 
                $address, 
                $paymentMethod, 
                $orderTotal
            ]);
            
            $orderId = $pdo->lastInsertId();

            // Добавляем товары в заказ (позиции заказа)
            $insertItem = $pdo->prepare("
                INSERT INTO order_items (order_id, product_id, quantity, price) 
                VALUES (?, ?, ?, ?)
            ");
            
            foreach ($cartItems as $item) {
                $insertItem->execute([
                    $orderId, 
                    $item['product_id'], 
                    $item['quantity'], 
                    $item['price']
                ]);
            }

            // Очищаем корзину пользователя после успешного создания заказа
            $clearCart = // SQL Запрос: удаление данных
    $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
            $clearCart->execute([$userId]);
            
            // Фиксируем транзакцию
            $pdo->commit();

            // Формируем и отправляем письмо с подтверждением заказа
            $emailSubject = "Заказ #$orderId подтверждён — Магазин";
            $emailMessage = "Здравствуйте, $fullName!\n\n";
            $emailMessage .= "Ваш заказ #$orderId успешно оформлен.\n\n";
            $emailMessage .= "Состав заказа:\n";
            
            foreach ($cartItems as $item) {
                $itemSum = $item['price'] * $item['quantity'];
                $emailMessage .= "- {$item['name']} × {$item['quantity']} = " 
                               . number_format($itemSum, 0, '', ' ') . " ₽\n";
            }
            
            $emailMessage .= "\nИтого: " . number_format($orderTotal, 0, '', ' ') . " ₽\n";
            $emailMessage .= "Адрес доставки: $address\n\n";
            $emailMessage .= "Спасибо за покупку!\nМагазин";
            
            // Отправка письма (используем стандартную функцию mail)
            @mail(
                $email, 
                $emailSubject, 
                $emailMessage, 
                "From: noreply@magazin.ru\r\nContent-Type: text/plain; charset=UTF-8"
            );

            $success = true;
            
        } catch (Exception $e) {
            // При ошибке откатываем транзакцию
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = 'Произошла ошибка при создании заказа. Попробуйте позже.';
        }
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
  // ── Настройки платёжной системы FreeKassa ──────────────────────
  // Замените эти значения на ваши реальные данные из кабинета продавца
  define('FK_MERCHANT_ID', 'ВАШЕ_ID_МАГАЗИНА');
  define('FK_SECRET1',     'ВАШ_СЕКРЕТНЫЙ_КЛЮЧ_1');
  define('FK_CURRENCY',    'RUB'); // Доступные валюты: RUB, USD, EUR и др.

  // Формируем подпись для формы оплаты
  // Алгоритм: MD5(MERCHANT_ID:AMOUNT:SECRET1:CURRENCY:MERCHANT_ORDER_ID)
  $fkAmount = number_format($orderTotal, 2, '.', '');
  $fkSign   = md5(FK_MERCHANT_ID . ':' . $fkAmount . ':' . FK_SECRET1 . ':' . FK_CURRENCY . ':' . $orderId);
  ?>

  <!-- Карточка успешного оформления заказа -->
  <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);padding:48px 40px;text-align:center;max-width:580px;margin:0 auto;position:relative;overflow:hidden">
    <!-- Декоративный элемент фона -->
    <div style="position:absolute;top:-60px;left:50%;transform:translateX(-50%);width:220px;height:220px;border-radius:50%;background:radial-gradient(circle,rgba(0,214,143,0.12),transparent 70%);pointer-events:none"></div>

    <!-- Иконка успеха -->
    <div style="width:76px;height:76px;border-radius:50%;background:rgba(0,214,143,0.1);border:2px solid rgba(0,214,143,0.25);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:2rem">✅</div>

    <h2 style="font-family:var(--font-d);font-size:1.3rem;font-weight:900;margin-bottom:10px">Заказ #<?= $orderId ?> оформлен!</h2>
    <p style="color:var(--text2);margin-bottom:28px;font-size:0.9rem">
      Для завершения покупки оплатите заказ.<br>
      После оплаты мы начнём его обработку.
    </p>

    <!-- Блок с суммой к оплате -->
    <div style="background:var(--surface2);border:1px solid var(--border);border-radius:var(--radius-md);padding:16px 20px;margin-bottom:24px;display:flex;justify-content:space-between;align-items:center">
      <span style="font-size:0.85rem;color:var(--text3)">Сумма к оплате</span>
      <span style="font-family:var(--font-d);font-size:1.3rem;font-weight:900;color:var(--accent)"><?= number_format($orderTotal, 0, '', ' ') ?> ₽</span>
    </div>

    <!-- Форма оплаты через FreeKassa -->
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

    <!-- Кнопки навигации -->
    <div style="display:flex;gap:10px;margin-top:20px">
      <a href="/shop/index.php" class="btn-secondary" style="flex:1;font-size:0.82rem">На главную</a>
      <a href="/shop/profile.php#orders" class="btn-secondary" style="flex:1;font-size:0.82rem">Мои заказы</a>
    </div>
  </div>
  <?php else: ?>

  <!-- Вывод ошибок валидации формы -->
  <?php foreach ($errors as $e): ?>
  <div class="alert alert-error"><?= htmlspecialchars($e) ?></div>
  <?php endforeach; ?>

  <!-- Основная разметка страницы: форма слева, итог справа -->
  <div style="display:grid;grid-template-columns:1fr 380px;gap:24px">
    
    <!-- Левая колонка: форма оформления заказа -->
    <form method="POST" action="">
      
      <!-- Блок: Данные покупателя -->
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

      <!-- Блок: Адрес доставки -->
      <div class="admin-form-card" style="margin-bottom:16px">
        <h3 style="font-weight:800;margin-bottom:16px">🚚 Адрес доставки</h3>
        <div class="form-group">
          <label>Адрес <span class="required">*</span></label>
          <textarea name="address" class="form-control" rows="3" 
                    placeholder="Город, улица, дом, квартира" required></textarea>
        </div>
      </div>

      <!-- Блок: Способ оплаты -->
      <div class="admin-form-card">
        <h3 style="font-weight:800;margin-bottom:16px">💳 Способ оплаты</h3>
        <div style="display:flex;flex-direction:column;gap:12px">
          
          <label style="display:flex;align-items:center;gap:12px;padding:14px;border:2px solid var(--border);border-radius:var(--radius-sm);cursor:pointer;transition:border-color 0.2s">
            <input type="radio" name="payment_method" value="card" checked>
            <span>💳 Банковская карта</span>
          </label>
          
          <label style="display:flex;align-items:center;gap:12px;padding:14px;border:2px solid var(--border);border-radius:var(--radius-sm);cursor:pointer;transition:border-color 0.2s">
            <input type="radio" name="payment_method" value="online">
            <span>📱 Онлайн-оплата</span>
          </label>
          
          <label style="display:flex;align-items:center;gap:12px;padding:14px;border:2px solid var(--border);border-radius:var(--radius-sm);cursor:pointer;transition:border-color 0.2s">
            <input type="radio" name="payment_method" value="cash">
            <span>💵 Наличными при получении</span>
          </label>
          
        </div>
      </div>

      <!-- Кнопка отправки формы -->
      <button type="submit" class="btn-primary" style="margin-top:20px;width:100%">
        ✅ Подтвердить заказ
      </button>
    </form>

    <!-- Правая колонка: итоговая информация о заказе -->
    <aside class="cart-summary" style="position:sticky;top:120px;height:fit-content">
      <h3>Ваш заказ</h3>
      
      <!-- Список товаров -->
      <?php foreach ($cartItems as $item): ?>
      <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border);font-size:0.88rem">
        <span><?= htmlspecialchars(mb_substr($item['name'], 0, 30)) ?> ×<?= $item['quantity'] ?></span>
        <span><?= number_format($item['price'] * $item['quantity'], 0, '', ' ') ?> ₽</span>
      </div>
      <?php endforeach; ?>
      
      <!-- Доставка -->
      <div class="summary-row" style="margin-top:8px">
        <span>Доставка</span>
        <span style="color:var(--success)">Бесплатно</span>
      </div>
      
      <!-- Итоговая сумма -->
      <div class="summary-row total">
        <span>Итого</span>
        <span><?= number_format($orderTotal, 0, '', ' ') ?> ₽</span>
      </div>
    </aside>
    
  </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
