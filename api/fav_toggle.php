<?php
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'redirect' => '/login.php']);
    exit;
}

$product_id = (int)($_POST['product_id'] ?? $_GET['product_id'] ?? 0);
if ($product_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Неверный ID товара']);
    exit;
}

try {
    // Проверяем, есть ли уже в избранном
    $check = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND product_id = ?");
    $check->execute([$_SESSION['user_id'], $product_id]);

    if ($check->fetch()) {
        // Удалить из избранного
        $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$_SESSION['user_id'], $product_id]);
        echo json_encode(['success' => true, 'added' => false, 'message' => 'Убрано из избранного']);
    } else {
        // Добавить в избранное
        $stmt = $pdo->prepare("INSERT INTO favorites (user_id, product_id) VALUES (?, ?)");
        $stmt->execute([$_SESSION['user_id'], $product_id]);
        echo json_encode(['success' => true, 'added' => true, 'message' => 'Добавлено в избранное']);
    }
} catch (PDOException $e) {
    error_log('Favorites error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Ошибка сервера']);
}