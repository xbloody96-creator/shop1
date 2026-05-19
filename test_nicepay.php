<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Тест NicePay API</h2>";
echo "<p>Проверка подключения...</p>";

$data = [
    'merchant_id' => '69d0fb8d6ffcca6032214697',
    'secret'      => 'GAWQo-epc31-Xj942-MmXsQ-TkGxv',
    'order_id'    => 'TEST123',
    'customer'    => 'test@test.com',
    'amount'      => 10000, // 100 RUB в копейках
    'currency'    => 'RUB',
    'description' => 'Тестовый платёж',
];

echo "<pre>Данные запроса:\n";
print_r($data);
echo "</pre>";

$ch = curl_init('https://nicepay.io/public/api/payment');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Таймаут 10 секунд
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); // Таймаут подключения 5 секунд
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_VERBOSE, true);

// Логирование
$verbose = fopen('php://temp', 'w+');
curl_setopt($ch, CURLOPT_STDERR, $verbose);

echo "<p>Отправка запроса...</p>";

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
$curlErrno = curl_errno($ch);

rewind($verbose);
$verboseLog = stream_get_contents($verbose);

curl_close($ch);

echo "<h3>Результат:</h3>";
echo "<ul>";
echo "<li>HTTP Code: <strong>$httpCode</strong></li>";
echo "<li>cURL Error No: $curlErrno</li>";
echo "<li>cURL Error: " . ($curlError ?: 'Нет') . "</li>";
echo "</ul>";

if ($response) {
    echo "<h3>Ответ сервера:</h3>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    
    $decoded = json_decode($response, true);
    if ($decoded) {
        echo "<h3>Распарсенный JSON:</h3>";
        echo "<pre>";
        print_r($decoded);
        echo "</pre>";
    }
} else {
    echo "<p style='color:red'><strong>Ошибка: Нет ответа от сервера</strong></p>";
}

echo "<h3>Verbose лог:</h3>";
echo "<pre style='background:#f5f5f5;padding:10px;font-size:12px'>" . htmlspecialchars($verboseLog) . "</pre>";

// Проверка доступности домена
echo "<hr>";
echo "<h3>Проверка доступности домена:</h3>";
$checkUrl = 'https://nicepay.io';
$ch2 = curl_init($checkUrl);
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_TIMEOUT, 5);
curl_setopt($ch2, CURLOPT_NOBODY, true);
curl_exec($ch2);
$checkCode = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
curl_close($ch2);

echo "<p>Домен nicepay.io: HTTP $checkCode</p>";
?>