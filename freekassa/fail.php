<?php
/**
 * FreeKassa — Fail URL
 * Пользователь попадает сюда если оплата не прошла / отменена.
 */

require_once __DIR__ . '/../includes/auth.php';

$orderId = (int)($_GET['MERCHANT_ORDER_ID'] ?? 0);

// Загружаем заказ
$order = null;
if ($orderId) {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
}

// Если заказ pending — оставляем его, пользователь может попробовать снова
// Не меняем статус на cancelled автоматически

$settings = getSettings();
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width:660px;padding:60px 20px">

  <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);padding:52px 40px;text-align:center;position:relative;overflow:hidden">

    <!-- Декоративное свечение -->
    <div style="position:absolute;top:-60px;left:50%;transform:translateX(-50%);width:240px;height:240px;border-radius:50%;background:radial-gradient(circle,rgba(255,45,59,0.12),transparent 70%);pointer-events:none"></div>

    <!-- Иконка ошибки -->
    <div style="width:80px;height:80px;border-radius:50%;background:rgba(255,45,59,0.1);border:2px solid rgba(255,45,59,0.25);display:flex;align-items:center;justify-content:center;margin:0 auto 24px;font-size:2.2rem;animation:fadeUp 0.4s ease">
      ❌
    </div>

    <h1 style="font-family:var(--font-d);font-size:1.5rem;font-weight:900;letter-spacing:-0.03em;margin-bottom:10px;color:var(--accent)">
      Оплата не прошла
    </h1>

    <p style="color:var(--text2);font-size:0.92rem;margin-bottom:28px">
      К сожалению, платёж был отклонён или отменён.<br>
      Ваш заказ сохранён — вы можете попробовать оплатить снова.
    </p>

    <?php if ($order): ?>
    <!-- Детали заказа -->
    <div style="background:var(--surface2);border:1px solid var(--border);border-radius:var(--radius-md);padding:20px;text-align:left;margin-bottom:28px">
      <div style="font-family:var(--font-d);font-size:0.68rem;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:var(--text3);margin-bottom:14px">
        Информация о заказе
      </div>
      <div style="display:flex;flex-direction:column;gap:10px;font-size:0.88rem">
        <div style="display:flex;justify-content:space-between">
          <span style="color:var(--text3)">Номер заказа</span>
          <strong>#<?= $orderId ?></strong>
        </div>
        <div style="display:flex;justify-content:space-between">
          <span style="color:var(--text3)">Сумма к оплате</span>
          <strong style="font-family:var(--font-d)"><?= number_format($order['total'], 0, '', ' ') ?> ₽</strong>
        </div>
        <div style="display:flex;justify-content:space-between">
          <span style="color:var(--text3)">Статус</span>
          <span style="background:rgba(255,45,59,0.1);color:var(--accent);padding:3px 10px;border-radius:var(--radius);font-size:0.78rem;font-weight:700">✗ Не оплачен</span>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Возможные причины -->
    <div style="background:rgba(255,170,0,0.06);border:1px solid rgba(255,170,0,0.18);border-radius:var(--radius-md);padding:16px 20px;text-align:left;margin-bottom:28px">
      <div style="font-size:0.8rem;font-weight:700;color:var(--warning);margin-bottom:10px">⚠️ Возможные причины:</div>
      <ul style="list-style:none;display:flex;flex-direction:column;gap:6px">
        <?php foreach ([
          'Недостаточно средств на счёте',
          'Карта заблокирована или истёк срок действия',
          'Оплата отклонена банком',
          'Превышен лимит операций',
          'Платёж был отменён вами',
        ] as $reason): ?>
        <li style="font-size:0.82rem;color:var(--text2);display:flex;align-items:flex-start;gap:8px">
          <span style="color:var(--text3);flex-shrink:0">—</span><?= $reason ?>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>

    <!-- Кнопки -->
    <div style="display:flex;flex-direction:column;gap:10px">
      <?php if ($orderId): ?>
      <a href="/shop/checkout.php?retry=<?= $orderId ?>" class="btn-primary">
        🔄 Попробовать снова
      </a>
      <?php endif; ?>
      <?php if (isLoggedIn()): ?>
      <a href="/shop/profile.php#orders" class="btn-secondary">
        📦 Мои заказы
      </a>
      <?php endif; ?>
      <a href="/shop/cart.php" class="btn-secondary" style="color:var(--text3)">
        🛒 Вернуться в корзину
      </a>
    </div>

    <!-- Поддержка -->
    <div style="margin-top:32px;padding-top:24px;border-top:1px solid var(--border);font-size:0.8rem;color:var(--text3)">
      Нужна помощь? Позвоните нам:
      <a href="tel:<?= htmlspecialchars($settings['contact_phone']??'') ?>"
         style="color:var(--accent);font-weight:700;margin-left:4px">
        <?= htmlspecialchars($settings['contact_phone']??'') ?>
      </a>
      <br>или напишите на
      <a href="mailto:<?= htmlspecialchars($settings['contact_email']??'') ?>"
         style="color:var(--accent2);font-weight:700;margin-left:4px">
        <?= htmlspecialchars($settings['contact_email']??'') ?>
      </a>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
