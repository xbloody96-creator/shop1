<?php
require_once __DIR__ . '/includes/db.php';

$categoryId = (int)($_GET['category'] ?? 1);
$perPage = 20;
$offset = 0;

echo "Category ID: $categoryId\n<br>";

// Тест 1: Прямой запрос
$stmt = $pdo->prepare("SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.is_active = 1 AND p.category_id = ? LIMIT $perPage OFFSET $offset");
$stmt->execute([$categoryId]);
$products = $stmt->fetchAll();

echo "Products found: " . count($products) . "<br><br>";

foreach ($products as $p) {
    echo "- {$p['name']} (ID: {$p['id']}, Category: {$p['cat_name']})<br>";
}
