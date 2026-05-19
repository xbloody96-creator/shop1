<?php
/**
* NicePay — Result URL (обработчик уведомлений)
*/
require_once __DIR__ . '/includes/db.php';

// ── Ваши данные из NicePay ──
define('NICEPAY_MERCHANT_ID', '69d0fb8d6ffcca6032214697');
define('NICEPAY_SECRET_KEY', 'GAWQo-epc31-Xj942-MmXsQ-TkGxv');

// Логирование
function np_log(string $msg): void {
    $dir = __DIR__ . '/logs';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    file_put_contents(
        $dir . '/nicepay_' . date('Y-m-d') . '.log',
        '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL,
        FILE_APPEND | LOCK_EX
    );
}

// Получаем параметры
$merchantId = $_GET['merchant_id'] ?? '';
$orderId    = $_GET['order_id'] ?? '';
$amount     = $_GET['amount'] ?? '';
$sign       = $_GET['sign'] ?? '';
$status     = $_GET['status'] ?? '';

np_log("Получен запрос: order_id=$orderId amount=$amount status=$status sign=$sign");

// Проверка подписи
$expectedSign = hash('sha256', NICEPAY_MERCHANT_ID . ':' . $orderId . ':' . $amount . ':' . 'RUB' . ':' . NICEPAY_SECRET_KEY);
if ($sign !== $expectedSign) {
    np_log("ОШИБКА: Неверная подпись");
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'WRONG SIGN']);
    exit;
}

// Находим заказ
$orderId = (int)$orderId;
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    np_log("Заказ #$orderId не найден");
    echo json_encode(['status' => 'error', 'message' => 'ORDER NOT FOUND']);
    exit;
}

// Если уже оплачен
if ($order['status'] === 'processing' || $order['status'] === 'delivered') {
    np_log("Заказ #$orderId уже оплачен");
    echo json_encode(['status' => 'success', 'message' => 'ALREADY PAID']);
    exit;
}

// Обновляем статус
if ($status === 'success' || $status === 'paid') {
    $pdo->prepare("UPDATE orders SET status = 'processing', payment_method = 'online' WHERE id = ?")
        ->execute([$orderId]);
    
    np_log("Заказ #$orderId ОПЛАЧЕН. Сумма: $amount ₽");
    
    // Отправляем письмо
    $subject = "Оплата подтверждена — Заказ #{$orderId}";
    $message = "Здравствуйте, {$order['full_name']}!\n\n"
        . "Ваш заказ #{$orderId} успешно оплачен на сумму {$amount} ₽.\n"
        . "Мы начали его обработку.\n\n"
        . "Спасибо за покупку!\nProtech-no";
    
    @mail($order['email'], $subject, $message, "From: noreply@protech-no.ru\r\nContent-Type: text/plain; charset=UTF-8");
    
    echo json_encode(['status' => 'success', 'message' => 'OK']);
} else {
    np_log("Статус: $status");
    echo json_encode(['status' => 'pending']);
}
?>