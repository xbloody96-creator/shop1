<?php
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Требуется авторизация']);
    exit;
}

$cart_id = (int)($_POST['cart_id'] ?? $_GET['cart_id'] ?? 0);
$quantity = max(0, (int)($_POST['quantity'] ?? 1));

if ($cart_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Неверный ID корзины']);
    exit;
}

try {
    if ($quantity <= 0) {
        // Удалить позицию
        $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
        $stmt->execute([$cart_id, $_SESSION['user_id']]);
        echo json_encode(['success' => true, 'message' => 'Товар удалён', 'cart_count' => cartCount()]);
    } else {
        // Обновить количество
        $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$quantity, $cart_id, $_SESSION['user_id']]);
        echo json_encode(['success' => true, 'message' => 'Количество обновлено', 'cart_count' => cartCount()]);
    }
} catch (PDOException $e) {
    error_log('Cart update error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Ошибка сервера']);
}