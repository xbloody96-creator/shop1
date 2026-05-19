<?php
require_once __DIR__ . '/includes/email.php';
$result = send2FACode('xbloody96@gmail.com', 999);
echo $result ? '✅ Письмо отправлено!' : '❌ Ошибка';
