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
    header('Location: /cart.php');
    exit;
}

$total = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $cartItems));

$errors  = [];
$success = false;
$orderId = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $address  = trim($_POST['address'] ?? '');
    
    if (empty($fullName)) $errors[] = 'Введите ФИО';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Некорректный email';
    if (empty($address)) $errors[] = 'Введите адрес доставки';
    
    if (empty($errors)) {
        $pdo->prepare("INSERT INTO orders (user_id, full_name, email, phone, address, payment_method, total, status) VALUES (?,?,?,?,?,?,?,?)")
            ->execute([$userId, $fullName, $email, $phone, $address, 'online', $total, 'pending']);
        $orderId = $pdo->lastInsertId();
        
        foreach ($cartItems as $item) {
            $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?,?,?,?)")
                ->execute([$orderId, $item['product_id'], $item['quantity'], $item['price']]);
        }
        
        $pdo->prepare("DELETE FROM cart WHERE user_id = ?")->execute([$userId]);
        $success = true;
    }
}

require_once __DIR__ . '/includes/header.php';
?>
<div class="container">
    <div class="breadcrumbs">
        <a href="/index.php">Главная</a> / <a href="/cart.php">Корзина</a> / <span>Оформление заказа</span>
    </div>
    <h1 style="font-size:1.6rem;font-weight:800;margin:12px 0 24px">📦 Оформление заказа</h1>

    <?php if ($success && $orderId): ?>
    <?php
    // ── NicePay настройки ──────────────────────
    define('NICEPAY_MERCHANT_ID', '69d0fb8d6ffcca6032214697');
    define('NICEPAY_SECRET', 'GAWQo-epc31-Xj942-MmXsQ-TkGxv');
    define('NICEPAY_CURRENCY', 'RUB');

    // ── Список прокси для обхода блокировки ──────────────────────
    $proxies = [
        ['ip' => '13.230.49.39', 'port' => 8080, 'type' => CURLPROXY_HTTP, 'country' => 'Japan'],
        ['ip' => '113.160.132.26', 'port' => 8080, 'type' => CURLPROXY_HTTP, 'country' => 'Vietnam'],
        ['ip' => '161.35.70.36', 'port' => 8888, 'type' => CURLPROXY_HTTP, 'country' => 'Germany'],
        ['ip' => '192.73.244.36', 'port' => 80, 'type' => CURLPROXY_HTTP, 'country' => 'United States'],
        ['ip' => '175.101.240.38', 'port' => 80, 'type' => CURLPROXY_HTTP, 'country' => 'India'],
        ['ip' => '5.161.50.82', 'port' => 8118, 'type' => CURLPROXY_HTTP, 'country' => 'United States'],
        ['ip' => '35.225.22.61', 'port' => 80, 'type' => CURLPROXY_HTTP, 'country' => 'United States'],
        ['ip' => '51.79.135.131', 'port' => 8080, 'type' => CURLPROXY_HTTP, 'country' => 'Singapore'],
        ['ip' => '167.71.196.28', 'port' => 8080, 'type' => CURLPROXY_HTTP, 'country' => 'Singapore'],
        ['ip' => '89.58.55.33', 'port' => 80, 'type' => CURLPROXY_HTTP, 'country' => 'Germany'],
        ['ip' => '38.92.10.139', 'port' => 33985, 'type' => CURLPROXY_HTTP, 'country' => 'United States'],
        ['ip' => '5.255.123.43', 'port' => 1080, 'type' => CURLPROXY_HTTP, 'country' => 'Netherlands'],
        ['ip' => '151.236.24.38', 'port' => 80, 'type' => CURLPROXY_HTTP, 'country' => 'Iceland'],
        ['ip' => '156.38.112.11', 'port' => 80, 'type' => CURLPROXY_HTTP, 'country' => 'Ghana'],
        ['ip' => '185.76.240.203', 'port' => 10001, 'type' => CURLPROXY_HTTP, 'country' => 'Russia'],
    ];

    // Создаем платеж через API NicePay
    $npAmount = (int)($total * 100); // В копейках!
    $npData = [
        'merchant_id' => NICEPAY_MERCHANT_ID,
        'secret'      => NICEPAY_SECRET,
        'order_id'    => (string)$orderId,
        'customer'    => $user['email'],
        'amount'      => $npAmount,
        'currency'    => NICEPAY_CURRENCY,
        'description' => 'Оплата заказа #' . $orderId . ' в Protech-no',
        'success_url' => 'https://protech-no.ru/success.php',
        'fail_url'    => 'https://protech-no.ru/fail.php',
    ];

    // ── Попытка подключения через прокси ──────────────────────
    $paymentLink = null;
    $error = 'Не удалось создать платеж';
    $usedProxy = null;

    foreach ($proxies as $proxy) {
        $proxyUrl = $proxy['ip'] . ':' . $proxy['port'];
        
        $ch = curl_init('https://nicepay.io/public/api/payment');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($npData),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            
            // 🔧 Настройки прокси
            CURLOPT_PROXY => $proxyUrl,
            CURLOPT_PROXYTYPE => $proxy['type'],
            CURLOPT_HTTPPROXYTUNNEL => 1,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        // Логируем попытку
        if ($httpCode !== 200) {
            error_log("Proxy {$proxyUrl} ({$proxy['country']}): HTTP=$httpCode, Error=$curlError");
        }
        
        if ($httpCode === 200 && $response) {
            $usedProxy = $proxyUrl;
            $npResponse = json_decode($response, true);
            
            if (isset($npResponse['status']) && $npResponse['status'] === 'success') {
                $paymentLink = $npResponse['data']['link'];
                $error = null;
                error_log("✅ Успех через прокси: {$proxyUrl} ({$proxy['country']})");
                break; // Успех! Выходим из цикла
            } else {
                $error = $npResponse['data']['message'] ?? 'Ошибка API NicePay';
            }
        }
    }
    ?>

    <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);padding:48px 40px;text-align:center;max-width:580px;margin:0 auto">
        <div style="width:76px;height:76px;border-radius:50%;background:rgba(0,214,143,0.1);border:2px solid rgba(0,214,143,0.25);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:2rem">✅</div>
        <h2 style="font-family:var(--font-d);font-size:1.3rem;font-weight:900;margin-bottom:10px">Заказ #<?= $orderId ?> оформлен!</h2>
        <p style="color:var(--text2);margin-bottom:28px;font-size:0.9rem">
            Для завершения покупки оплатите заказ.<br>
            После оплаты мы начнём его обработку.
        </p>

        <div style="background:var(--surface2);border:1px solid var(--border);border-radius:var(--radius-md);padding:16px 20px;margin-bottom:24px;display:flex;justify-content:space-between;align-items:center">
            <span style="font-size:0.85rem;color:var(--text3)">Сумма к оплате</span>
            <span style="font-family:var(--font-d);font-size:1.3rem;font-weight:900;color:var(--accent)"><?= number_format($total, 0, '', ' ') ?> ₽</span>
        </div>

        <?php if ($paymentLink): ?>
            <a href="<?= htmlspecialchars($paymentLink) ?>" class="btn-primary" style="font-size:0.88rem;padding:15px;text-decoration:none;display:inline-block">
                💳 Перейти к оплате
            </a>
            <p style="margin-top:14px;font-size:0.78rem;color:var(--text3)">
                Перенаправление на страницу оплаты NicePay...
                <?php if ($usedProxy): ?>
                <br><small style="color:var(--success)">✓ Подключено через прокси (<?= explode(':', $usedProxy)[0] ?>)</small>
                <?php endif; ?>
            </p>
            <script>
            setTimeout(() => {
                window.location.href = '<?= htmlspecialchars($paymentLink) ?>';
            }, 2000);
            </script>
        <?php else: ?>
            <div class="alert alert-error">
                Ошибка: <?= htmlspecialchars($error) ?>
                <br><small>Попробуйте позже или свяжитесь с поддержкой</small>
            </div>
            <a href="/profile.php#orders" class="btn-secondary">Мои заказы</a>
        <?php endif; ?>

        <div style="display:flex;gap:10px;margin-top:20px">
            <a href="/index.php" class="btn-secondary" style="flex:1;font-size:0.82rem">На главную</a>
        </div>
    </div>

    <?php else: ?>

    <?php foreach ($errors as $e): ?>
        <div class="alert alert-error"><?= htmlspecialchars($e) ?></div>
    <?php endforeach; ?>

    <div style="display:grid;grid-template-columns:1fr 380px;gap:24px">
        <form method="POST" action="">
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

            <div class="admin-form-card" style="margin-bottom:16px">
                <h3 style="font-weight:800;margin-bottom:16px">🚚 Адрес доставки</h3>
                <div class="form-group">
                    <label>Адрес <span class="required">*</span></label>
                    <textarea name="address" class="form-control" rows="3"
                              placeholder="Город, улица, дом, квартира" required></textarea>
                </div>
            </div>

            <button type="submit" class="btn-primary" style="margin-top:20px">✅ Подтвердить заказ</button>
        </form>

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