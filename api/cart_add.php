<?php
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['ok' => false, 'redirect' => '/shop/login.php']);
    exit;
}

$data      = json_decode(file_get_contents('php://input'), true);
$productId = (int)($data['product_id'] ?? 0);
$quantity  = max(1, (int)($data['quantity'] ?? 1));

if (!$productId) {
    echo json_encode(['ok' => false, 'message' => 'Товар не найден']);
    exit;
}

// Проверяем существование товара
$stmt = $pdo->prepare("SELECT id, stock FROM products WHERE id = ? AND is_active = 1");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    echo json_encode(['ok' => false, 'message' => 'Товар не найден']);
    exit;
}

// Проверяем есть ли уже в корзине
$existing = $pdo->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
$existing->execute([$_SESSION['user_id'], $productId]);
$cartRow = $existing->fetch();

if ($cartRow) {
    $newQty = $cartRow['quantity'] + $quantity;
    $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?")->execute([$newQty, $cartRow['id']]);
} else {
    $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?,?,?)")->execute([$_SESSION['user_id'], $productId, $quantity]);
}

// Считаем общее кол-во в корзине
$total = $pdo->prepare("SELECT COALESCE(SUM(quantity),0) FROM cart WHERE user_id = ?");
$total->execute([$_SESSION['user_id']]);
$cartCount = (int)$total->fetchColumn();

echo json_encode(['ok' => true, 'cart_count' => $cartCount]);
