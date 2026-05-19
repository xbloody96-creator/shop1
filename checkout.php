<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/check_2fa.php';
requireLogin();
require2FAVerified();

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
    
    // Валидация телефона: только цифры, ровно 12 символов
    $cleanPhone = preg_replace('/\D/', '', $phone);
    if (empty($cleanPhone)) {
        $errors[] = 'Введите номер телефона';
    } elseif (strlen($cleanPhone) !== 12) {
        $errors[] = 'Номер телефона должен содержать ровно 12 цифр';
    }
    
    if (empty($errors)) {
        $pdo->prepare("INSERT INTO orders (user_id, full_name, email, phone, address, payment_method, total, status) VALUES (?,?,?,?,?,?,?,?)")
            ->execute([$userId, $fullName, $email, $cleanPhone, $address, 'online', $total, 'pending']);
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
<style>
.checkout-page {
    padding: 32px 0;
}
.checkout-title {
    font-family: var(--font-d);
    font-size: 1.8rem;
    font-weight: 900;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 12px;
}
.checkout-grid {
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 28px;
}
.checkout-form-section {
    background: var(--surface);
    border: 1px solid var(--border2);
    border-radius: var(--radius-lg);
    padding: 28px;
    margin-bottom: 20px;
}
.checkout-section-title {
    font-family: var(--font-d);
    font-size: 1.1rem;
    font-weight: 800;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--text);
}
.checkout-input-group {
    margin-bottom: 16px;
}
.checkout-input-group label {
    display: block;
    font-size: 0.82rem;
    font-weight: 600;
    color: var(--text2);
    margin-bottom: 8px;
    letter-spacing: 0.02em;
}
.checkout-input {
    width: 100%;
    padding: 14px 16px;
    background: var(--bg2);
    border: 2px solid var(--border2);
    border-radius: var(--radius-md);
    color: var(--text);
    font-size: 0.95rem;
    font-weight: 500;
    transition: all var(--tr);
}
.checkout-input:focus {
    outline: none;
    border-color: var(--accent);
    box-shadow: 0 0 0 4px var(--accent-glow);
    background: var(--surface);
}
.checkout-input.error {
    border-color: var(--accent);
    box-shadow: 0 0 0 4px var(--accent-glow);
}
.checkout-input::placeholder {
    color: var(--text3);
}
.checkout-textarea {
    min-height: 120px;
    resize: vertical;
}
.checkout-summary {
    background: var(--surface);
    border: 1px solid var(--border2);
    border-radius: var(--radius-lg);
    padding: 24px;
    position: sticky;
    top: 100px;
    height: fit-content;
}
.checkout-summary-title {
    font-family: var(--font-d);
    font-size: 1.2rem;
    font-weight: 900;
    margin-bottom: 20px;
    color: var(--text);
    display: flex;
    align-items: center;
    gap: 10px;
}
.checkout-summary-item {
    display: flex;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px solid var(--border);
    font-size: 0.9rem;
}
.checkout-summary-item:last-child {
    border-bottom: none;
}
.checkout-summary-name {
    color: var(--text2);
    max-width: 60%;
}
.checkout-summary-qty {
    color: var(--text3);
    font-size: 0.85rem;
}
.checkout-summary-price {
    font-weight: 700;
    color: var(--text);
}
.checkout-summary-row {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    font-size: 0.9rem;
    color: var(--text2);
}
.checkout-summary-total {
    display: flex;
    justify-content: space-between;
    padding: 16px 0;
    margin-top: 12px;
    border-top: 2px solid var(--border2);
    font-size: 1.1rem;
    font-weight: 800;
    color: var(--text);
}
.checkout-total-amount {
    font-family: var(--font-d);
    font-size: 1.5rem;
    font-weight: 900;
    color: var(--accent);
}
.checkout-submit-btn {
    width: 100%;
    padding: 16px;
    background: var(--accent);
    color: #fff;
    border: 2px solid var(--accent);
    border-radius: var(--radius-md);
    font-family: var(--font-d);
    font-size: 0.9rem;
    font-weight: 800;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    cursor: pointer;
    transition: all var(--tr);
    margin-top: 20px;
}
.checkout-submit-btn:hover {
    background: #fff;
    color: var(--accent);
    transform: translateY(-3px);
    box-shadow: 0 12px 40px rgba(255,45,59,0.3);
}
.checkout-submit-btn:active {
    transform: translateY(-1px);
}
.form-row-checkout {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}
@media (max-width: 900px) {
    .checkout-grid {
        grid-template-columns: 1fr;
    }
    .checkout-summary {
        position: static;
        margin-top: 20px;
    }
    .form-row-checkout {
        grid-template-columns: 1fr;
    }
}
</style>
<div class="container checkout-page">
    <div class="breadcrumbs">
        <a href="/index.php">Главная</a> / <a href="/cart.php">Корзина</a> / <span>Оформление заказа</span>
    </div>
    <h1 class="checkout-title">📦 Оформление заказа</h1>

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

    <div class="checkout-grid">
        <form method="POST" action="">
            <div class="checkout-form-section">
                <h3 class="checkout-section-title">👤 Данные покупателя</h3>
                <div class="form-row-checkout">
                    <div class="checkout-input-group">
                        <label>ФИО <span class="required">*</span></label>
                        <input type="text" name="full_name" class="checkout-input"
                               value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" required>
                    </div>
                    <div class="checkout-input-group">
                        <label>Email <span class="required">*</span></label>
                        <input type="email" name="email" class="checkout-input"
                               value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="checkout-input-group">
                    <label>Телефон <span class="required">*</span></label>
                    <input type="tel" name="phone" id="phone-input" class="checkout-input" 
                           placeholder="+7 (___) ___-__-__" maxlength="16" required>
                </div>
            </div>

            <div class="checkout-form-section">
                <h3 class="checkout-section-title">🚚 Адрес доставки</h3>
                <div class="checkout-input-group">
                    <label>Адрес <span class="required">*</span></label>
                    <textarea name="address" class="checkout-input checkout-textarea"
                              placeholder="Город, улица, дом, квартира" required></textarea>
                </div>
            </div>

            <button type="submit" class="checkout-submit-btn">✅ Подтвердить заказ</button>
        </form>

        <div class="checkout-summary">
            <h3 class="checkout-summary-title">🛒 Ваш заказ</h3>
            <?php foreach ($cartItems as $item): ?>
                <div class="checkout-summary-item">
                    <div>
                        <div class="checkout-summary-name"><?= htmlspecialchars(mb_substr($item['name'],0,40)) ?></div>
                        <div class="checkout-summary-qty">×<?= $item['quantity'] ?> шт.</div>
                    </div>
                    <div class="checkout-summary-price"><?= number_format($item['price'] * $item['quantity'], 0, '', ' ') ?> ₽</div>
                </div>
            <?php endforeach; ?>
            <div class="checkout-summary-row">
                <span>Доставка</span>
                <span style="color:var(--success);font-weight:600">Бесплатно</span>
            </div>
            <div class="checkout-summary-total">
                <span>Итого</span>
                <span class="checkout-total-amount"><?= number_format($total, 0, '', ' ') ?> ₽</span>
            </div>
        </div>
    </div>

    <script>
    // Маска для телефона и запрет букв
    document.addEventListener('DOMContentLoaded', function() {
        const phoneInput = document.getElementById('phone-input');
        
        phoneInput.addEventListener('input', function(e) {
            // Удаляем все нецифровые символы
            let value = e.target.value.replace(/\D/g, '');
            
            // Ограничиваем до 12 символов
            if (value.length > 12) {
                value = value.substring(0, 12);
            }
            
            // Форматируем номер
            let formattedValue = '';
            if (value.length > 0) {
                formattedValue = '+' + value;
                if (value.length > 1) {
                    formattedValue = '+7 (' + value.substring(1, 4);
                    if (value.length > 4) {
                        formattedValue += ') ' + value.substring(4, 7);
                        if (value.length > 7) {
                            formattedValue += '-' + value.substring(7, 9);
                            if (value.length > 9) {
                                formattedValue += '-' + value.substring(9, 12);
                            }
                        }
                    }
                }
            }
            
            e.target.value = formattedValue;
        });
        
        phoneInput.addEventListener('keydown', function(e) {
            // Разрешаем backspace, delete, tab, escape, enter
            if ([8, 9, 13, 27, 46].indexOf(e.keyCode) !== -1 ||
                // Ctrl+A, Command+A
                (e.keyCode === 65 && (e.ctrlKey === true || e.metaKey === true)) ||
                // home, end, left, right
                (e.keyCode >= 35 && e.keyCode <= 39)) {
                return;
            }
            // Запрещаем ввод букв и спецсимволов
            if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && 
                (e.keyCode < 96 || e.keyCode > 105)) {
                e.preventDefault();
            }
        });
        
        phoneInput.addEventListener('paste', function(e) {
            e.preventDefault();
            let text = e.clipboardData.getData('text');
            let value = text.replace(/\D/g, '').substring(0, 12);
            
            let formattedValue = '';
            if (value.length > 0) {
                formattedValue = '+' + value;
                if (value.length > 1) {
                    formattedValue = '+7 (' + value.substring(1, 4);
                    if (value.length > 4) {
                        formattedValue += ') ' + value.substring(4, 7);
                        if (value.length > 7) {
                            formattedValue += '-' + value.substring(7, 9);
                            if (value.length > 9) {
                                formattedValue += '-' + value.substring(9, 12);
                            }
                        }
                    }
                }
            }
            
            e.target.value = formattedValue;
        });
    });
    </script>

    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>