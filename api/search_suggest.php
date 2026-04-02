<?php
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json');

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) { echo '[]'; exit; }

$stmt = $pdo->prepare("SELECT id, name, price, main_image FROM products WHERE name LIKE ? AND is_active = 1 LIMIT 6");
$stmt->execute(['%' . $q . '%']);
echo json_encode($stmt->fetchAll());
