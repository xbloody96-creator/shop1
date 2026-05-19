<?php
/**
 * Добавление товара в корзину
 * API endpoint для обработки AJAX-запросов
 * 
 * @package Shop
 */

require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json');

// Проверяем авторизацию
if (!isLoggedIn()) {
    echo json_encode(['ok' => false, 'redirect' => '/shop/login.php']);
    exit;
}

// Получаем данные из запроса
$inputData = json_decode(file_get_contents('php://input'), true);
$productId = (int) ($inputData['product_id'] ?? 0);
$quantity  = max(1, (int) ($inputData['quantity'] ?? 1));

// Валидация ID товара
if (!$productId) {
    echo json_encode(['ok' => false, 'message' => 'Товар не найден']);
    exit;
}

// Проверяем существование и активность товара
$stmt = $pdo->prepare("SELECT id, stock FROM products WHERE id = ? AND is_active = 1");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    echo json_encode(['ok' => false, 'message' => 'Товар не найден или не активен']);
    exit;
}

// Проверяем наличие товара в корзине
$stmt = $pdo->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
$stmt->execute([$_SESSION['user_id'], $productId]);
$cartItem = $stmt->fetch();

if ($cartItem) {
    // Товар уже есть — увеличиваем количество
    $newQuantity = $cartItem['quantity'] + $quantity;
    $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
    $stmt->execute([$newQuantity, $cartItem['id']]);
} else {
    // Добавляем новый товар в корзину
    $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $productId, $quantity]);
}

// Считаем общее количество товаров в корзине
$stmt = $pdo->prepare("SELECT COALESCE(SUM(quantity), 0) FROM cart WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$totalCount = (int) $stmt->fetchColumn();

echo json_encode(['ok' => true, 'cart_count' => $totalCount]);
