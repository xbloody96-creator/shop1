<?php
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json');

if (!isLoggedIn()) { echo json_encode(['ok'=>false]); exit; }

$data     = json_decode(file_get_contents('php://input'), true);
$cartId   = (int)($data['cart_id'] ?? 0);
$quantity = max(1, (int)($data['quantity'] ?? 1));

$stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
$stmt->execute([$quantity, $cartId, $_SESSION['user_id']]);

echo json_encode(['ok' => $stmt->rowCount() > 0]);
