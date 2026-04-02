<?php
require_once __DIR__ . '/includes/auth.php';
logoutUser();
header('Location: /shop/index.php');
exit;
