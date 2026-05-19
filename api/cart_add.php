<?php
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json');

// Проверка авторизации
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Требуется авторизация', 'redirect' => '/login.php']);
    exit;
}

// Получение данных
$method = $_SERVER['REQUEST_METHOD'];
$action = $_POST['action'] ?? $_GET['action'] ?? 'add';
$product_id = (int)($_POST['product_id'] ?? $_GET['product_id'] ?? 0);
$quantity = max(1, (int)($_POST['quantity'] ?? 1));

// Валидация
if ($product_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Неверный ID товара']);
    exit;
}

// Проверка существования товара
$check = $pdo->prepare("SELECT id, name, price, stock FROM products WHERE id = ? AND is_active = 1");
$check->execute([$product_id]);
$product = $check->fetch();

if (!$product) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Товар не найден']);
    exit;
}

// Проверка наличия
if ($quantity > $product['stock']) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Недостаточно товара на складе. Доступно: ' . $product['stock']]);
    exit;
}

try {
    if ($action === 'remove') {
        // Удалить из корзины
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$_SESSION['user_id'], $product_id]);
        echo json_encode([
            'success' => true,
            'message' => 'Товар удалён из корзины',
            'cart_count' => cartCount()
        ]);
    } else {
        // Добавить или обновить количество
        $stmt = $pdo->prepare("
            INSERT INTO cart (user_id, product_id, quantity)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)
        ");
        $stmt->execute([$_SESSION['user_id'], $product_id, $quantity]);

        echo json_encode([
            'success' => true,
            'message' => 'Товар добавлен в корзину',
            'cart_count' => cartCount(),
            'product' => [
                'id' => $product['id'],
                'name' => $product['name'],
                'price' => (float)$product['price'],
                'quantity' => $quantity
            ]
        ]);
    }
} catch (PDOException $e) {
    error_log('Cart error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Ошибка сервера']);
}