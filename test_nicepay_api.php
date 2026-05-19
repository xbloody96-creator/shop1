<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$apiData = [
    'merchant_id' => '69d0fb8d6ffcca6032214697',
    'secret'      => 'GAWQo-epc31-Xj942-MmXsQ-TkGxv',
    'order_id'    => 'TEST_' . time(),
    'customer'    => 'test@test.com',
    'amount'      => 10000,
    'currency'    => 'RUB',
    'description' => 'Тест',
];

echo "Отправка запроса...\n";
$ch = curl_init('https://nicepay.io/public/api/payment');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($apiData),
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT => 10,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "HTTP: $httpCode\n";
echo "cURL Error: " . ($curlError ?: 'Нет') . "\n";
echo "Response: $response\n";
?>