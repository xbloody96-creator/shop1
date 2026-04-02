<?php
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['ok' => false, 'redirect' => '/shop/login.php']);
    exit;
}

$data      = json_decode(file_get_contents('php://input'), true);
$productId = (int)($data['product_id'] ?? 0);

if (!$productId) { echo json_encode(['ok' => false]); exit; }

$existing = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND product_id = ?");
$existing->execute([$_SESSION['user_id'], $productId]);
$row = $existing->fetch();

if ($row) {
    $pdo->prepare("DELETE FROM favorites WHERE id = ?")->execute([$row['id']]);
    echo json_encode(['ok' => true, 'added' => false]);
} else {
    $pdo->prepare("INSERT INTO favorites (user_id, product_id) VALUES (?,?)")->execute([$_SESSION['user_id'], $productId]);
    echo json_encode(['ok' => true, 'added' => true]);
}
