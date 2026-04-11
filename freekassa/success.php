/**
 * Файл: success.php
 * Описание: Страница сайта
 * @version 1.0
 */

<?php
/**
 * FreeKassa — Success URL
 * Пользователь попадает сюда после успешной оплаты.
 * FreeKassa передаёт GET-параметры: MERCHANT_ORDER_ID, AMOUNT, intid и др.
 */

require_once __DIR__ . '/../includes/auth.php';

define('FK_MERCHANT_ID', 'ВАШЕ_ID_МАГАЗИНА');
define('FK_SECRET2',     'ВАШ_СЕКРЕТНЫЙ_КЛЮЧ_2'); // Секретное слово 2 (для Success URL)

$orderId = (int)($_GET['MERCHANT_ORDER_ID'] ?? 0);
$amount  = $_GET['AMOUNT'] ?? '';
$sign    = $_GET['SIGN']   ?? '';

// Проверяем подпись (опционально, но рекомендуется)
// Формат Success: MD5(MERCHANT_ID:AMOUNT:SECRET2:MERCHANT_ORDER_ID)
$valid = false;
if ($orderId && $amount && $sign) {
    $expected = strtoupper(md5(FK_MERCHANT_ID . ':' . $amount . ':' . FK_SECRET2 . ':' . $orderId));
    $valid = (strtoupper($sign) === $expected);
}

// Загружаем заказ
$order = null;
if ($orderId) {
    $stmt = // SQL Запрос: выборка данных
    $pdo->prepare("SELECT o.*, u.full_name FROM orders o LEFT JOIN users u ON o.user_id=u.id WHERE o.id=?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
}

$settings = getSettings();
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width:660px;padding:60px 20px">

  <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);padding:52px 40px;text-align:center;position:relative;overflow:hidden">

    <!-- Декоративное свечение -->
    <div style="position:absolute;top:-60px;left:50%;transform:translateX(-50%);width:240px;height:240px;border-radius:50%;background:radial-gradient(circle,rgba(0,214,143,0.15),transparent 70%);pointer-events:none"></div>

    <!-- Иконка успеха -->
    <div style="width:80px;height:80px;border-radius:50%;background:rgba(0,214,143,0.12);border:2px solid rgba(0,214,143,0.3);display:flex;align-items:center;justify-content:center;margin:0 auto 24px;font-size:2.2rem;animation:fadeUp 0.4s ease">
      ✅
    </div>

    <h1 style="font-family:var(--font-d);font-size:1.5rem;font-weight:900;letter-spacing:-0.03em;margin-bottom:10px;color:var(--success)">
      Оплата прошла успешно!
    </h1>

    <?php if ($order): ?>
    <p style="color:var(--text2);font-size:0.92rem;margin-bottom:28px">
      Заказ <strong style="color:var(--text)">#<?= $orderId ?></strong> оплачен.<br>
      Мы уже начали его обработку и скоро свяжемся с вами.
    </p>

    <!-- Детали заказа -->
    <div style="background:var(--surface2);border:1px solid var(--border);border-radius:var(--radius-md);padding:20px;text-align:left;margin-bottom:28px">
      <div style="font-family:var(--font-d);font-size:0.68rem;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:var(--text3);margin-bottom:14px">
        Детали заказа
      </div>
      <div style="display:flex;flex-direction:column;gap:10px;font-size:0.88rem">
        <div style="display:flex;justify-content:space-between">
          <span style="color:var(--text3)">Номер заказа</span>
          <strong>#<?= $orderId ?></strong>
        </div>
        <div style="display:flex;justify-content:space-between">
          <span style="color:var(--text3)">Сумма оплаты</span>
          <strong style="color:var(--success);font-family:var(--font-d)"><?= $amount ? number_format((float)$amount, 0, '', ' ') . ' ₽' : number_format($order['total'], 0, '', ' ') . ' ₽' ?></strong>
        </div>
        <div style="display:flex;justify-content:space-between">
          <span style="color:var(--text3)">Статус</span>
          <span style="background:rgba(0,214,143,0.12);color:var(--success);padding:3px 10px;border-radius:var(--radius);font-size:0.78rem;font-weight:700">✓ Оплачен</span>
        </div>
        <div style="display:flex;justify-content:space-between">
          <span style="color:var(--text3)">Email</span>
          <span><?= htmlspecialchars($order['email']) ?></span>
        </div>
      </div>
    </div>

    <p style="font-size:0.82rem;color:var(--text3);margin-bottom:28px">
      📧 Подтверждение отправлено на <strong><?= htmlspecialchars($order['email']) ?></strong>
    </p>

    <?php else: ?>
    <p style="color:var(--text2);font-size:0.92rem;margin-bottom:28px">
      Ваш платёж успешно обработан. Спасибо за покупку!
    </p>
    <?php endif; ?>

    <!-- Кнопки -->
    <div style="display:flex;flex-direction:column;gap:10px">
      <?php if (isLoggedIn()): ?>
      <a href="/shop/profile.php#orders" class="btn-primary">
        📦 Мои заказы
      </a>
      <?php endif; ?>
      <a href="/shop/index.php" class="btn-secondary">
        🛒 Продолжить покупки
      </a>
    </div>

    <!-- Контакты поддержки -->
    <div style="margin-top:32px;padding-top:24px;border-top:1px solid var(--border);font-size:0.8rem;color:var(--text3)">
      Возникли вопросы? Позвоните нам:
      <a href="tel:<?= htmlspecialchars($settings['contact_phone']??'') ?>"
         style="color:var(--accent);font-weight:700;margin-left:4px">
        <?= htmlspecialchars($settings['contact_phone']??'') ?>
      </a>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
