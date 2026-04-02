<?php
/**
 * FreeKassa — Result URL (обработчик уведомлений)
 * Этот скрипт вызывается сервером FreeKassa автоматически после оплаты.
 * Должен вернуть строку "YES" при успехе.
 */

require_once __DIR__ . '/includes/db.php';

// ── Ваши данные из личного кабинета FreeKassa ──
define('FK_MERCHANT_ID', 'ВАШЕ_ID_МАГАЗИНА');   // m (ID магазина)
define('FK_SECRET1',     'ВАШ_СЕКРЕТНЫЙ_КЛЮЧ_1'); // Секретное слово 1

// ── Логирование (опционально) ──────────────────
function fk_log(string $msg): void {
    $dir = __DIR__ . '/logs';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    file_put_contents(
        $dir . '/freekassa_' . date('Y-m-d') . '.log',
        '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL,
        FILE_APPEND | LOCK_EX
    );
}

// ── Получаем POST/GET параметры от FreeKassa ───
$merchantId  = $_POST['MERCHANT_ID']    ?? $_GET['MERCHANT_ID']    ?? '';
$amount      = $_POST['AMOUNT']         ?? $_GET['AMOUNT']         ?? '';
$intid       = $_POST['intid']          ?? $_GET['intid']          ?? ''; // ID транзакции FK
$merchantOrderId = $_POST['MERCHANT_ORDER_ID'] ?? $_GET['MERCHANT_ORDER_ID'] ?? '';
$sign        = $_POST['SIGN']           ?? $_GET['SIGN']           ?? '';
$paymentId   = $_POST['P_PHONE']        ?? $_POST['P_EMAIL']       ?? ''; // Дополнительно

fk_log("Получен запрос: MERCHANT_ID=$merchantId AMOUNT=$amount ORDER_ID=$merchantOrderId intid=$intid");

// ── Проверка обязательных параметров ──────────
if (empty($merchantId) || empty($amount) || empty($merchantOrderId) || empty($sign)) {
    fk_log("ОШИБКА: Не все параметры получены");
    http_response_code(400);
    echo 'MISSING PARAMS';
    exit;
}

// ── Проверка подписи ───────────────────────────
// Формат: MD5(MERCHANT_ID:AMOUNT:SECRET1:MERCHANT_ORDER_ID)
$expectedSign = strtoupper(md5(FK_MERCHANT_ID . ':' . $amount . ':' . FK_SECRET1 . ':' . $merchantOrderId));

if (strtoupper($sign) !== $expectedSign) {
    fk_log("ОШИБКА: Неверная подпись. Получено: $sign, Ожидалось: $expectedSign");
    http_response_code(403);
    echo 'WRONG SIGN';
    exit;
}

// ── Находим заказ в БД ─────────────────────────
$orderId = (int) $merchantOrderId;
$stmt    = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$orderId]);
$order   = $stmt->fetch();

if (!$order) {
    fk_log("ОШИБКА: Заказ #$orderId не найден");
    http_response_code(404);
    echo 'ORDER NOT FOUND';
    exit;
}

// ── Проверяем что заказ ещё не оплачен (защита от дублей) ──
if ($order['status'] === 'processing' || $order['status'] === 'delivered') {
    fk_log("Заказ #$orderId уже обработан, статус: {$order['status']}");
    echo 'YES'; // Всё ок, просто дубль
    exit;
}

// ── Проверяем сумму ────────────────────────────
if ((float)$amount < (float)$order['total']) {
    fk_log("ОШИБКА: Сумма не совпадает. Получено: $amount, Ожидалось: {$order['total']}");
    http_response_code(402);
    echo 'WRONG AMOUNT';
    exit;
}

// ── Обновляем статус заказа ────────────────────
$pdo->prepare("UPDATE orders SET status = 'processing', payment_method = 'online' WHERE id = ?")
    ->execute([$orderId]);

// ── Сохраняем FK transaction id (если нужно) ──
// Можно добавить колонку fk_intid в таблицу orders:
// $pdo->prepare("UPDATE orders SET fk_intid = ? WHERE id = ?")->execute([$intid, $orderId]);

fk_log("Заказ #$orderId ОПЛАЧЕН. Сумма: $amount ₽, FK intid: $intid");

// ── Отправляем письмо покупателю ───────────────
$subject = "Оплата подтверждена — Заказ #{$orderId}";
$message = "Здравствуйте, {$order['full_name']}!\n\n"
    . "Ваш заказ #{$orderId} успешно оплачен на сумму {$amount} ₽.\n"
    . "Мы начали его обработку. Ожидайте звонка менеджера.\n\n"
    . "Спасибо за покупку!\nМагазин";
@mail($order['email'], $subject, $message,
    "From: noreply@magazin.ru\r\nContent-Type: text/plain; charset=UTF-8");

// ── Обязательный ответ для FreeKassa ──────────
echo 'YES';
exit;
