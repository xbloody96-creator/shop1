<?php
/**
* Отправка 2FA кодов через PHPMailer (SMTP)
*/
// Подключаем PHPMailer (автозагрузчик Composer)
require_once __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
* Генерация 6-значного кода
*/
function generate2FACode(): string {
    return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

/**
* Сохранение кода в сессии
*/
function save2FACode(string $code, int $userId): void {
    $_SESSION['2fa_code'] = [
        'code' => password_hash($code, PASSWORD_DEFAULT),
        'user_id' => $userId,
        'expires' => time() + 300 // 5 минут
    ];
}

/**
* Проверка кода
*/
function verify2FACode(string $code, int $userId): bool {
    if (!isset($_SESSION['2fa_code']) || $_SESSION['2fa_code']['user_id'] !== $userId) {
        return false;
    }
    $data = $_SESSION['2fa_code'];
    if (time() > $data['expires']) {
        unset($_SESSION['2fa_code']);
        return false;
    }
    if (password_verify($code, $data['code'])) {
        unset($_SESSION['2fa_code']);
        return true;
    }
    return false;
}

/**
* Отправка кода 2FA на email через SMTP
*/
function send2FACode(string $email, int $userId): bool {
    $code = generate2FACode();
    save2FACode($code, $userId);
    
    $mail = new PHPMailer(true);
    
    try {
        // ── НАСТРОЙКИ SMTP ─────────────────────────────
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'xbloody96@gmail.com';
        $mail->Password   = 'sfyp ngfw pqxu ttvw';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';
        // ──────────────────────────────────────────────
        
        // ✅ ИСПРАВЛЕНО: правильный email и имя отправителя
        $mail->setFrom('xbloody96@gmail.com', 'Protech-no');
        $mail->addAddress($email);
        
        // Контент
        $mail->isHTML(true);
        $mail->Subject = '🔐 Код подтверждения — Protech-no';
        $mail->Body    = '
        <div style="font-family:sans-serif;max-width:500px;margin:0 auto;padding:24px;background:#f8f9fa;border-radius:12px">
            <h2 style="color:#0a0a1a;margin-bottom:20px;text-align:center">🔐 Подтверждение входа</h2>
            <p style="font-size:1rem;color:#40405a;line-height:1.6;text-align:center">
                Вы пытаетесь войти в аккаунт на сайте <strong>Protech-no</strong>.<br>
                Для подтверждения используйте код:
            </p>
            <div style="background:#fff;border:3px dashed #ff2d3b;border-radius:10px;padding:20px;text-align:center;margin:24px 0">
                <span style="font-family:monospace;font-size:2.2rem;font-weight:800;letter-spacing:8px;color:#ff2d3b">'.$code.'</span>
            </div>
            <p style="font-size:0.85rem;color:#8080a0;text-align:center">
                ⏰ Код действителен <strong>5 минут</strong>.<br>
                Если вы не запрашивали вход — просто проигнорируйте это письмо.
            </p>
            <hr style="border:none;border-top:1px solid #e0e0e0;margin:24px 0">
            <p style="font-size:0.75rem;color:#a0a0c0;text-align:center">
                Это автоматическое письмо, не отвечайте на него.<br>
                © Protech-no, '.date('Y').'
            </p>
        </div>';
        
        $mail->AltBody = "Ваш код подтверждения: $code\nДействует 5 минут.";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("PHPMailer Error: {$mail->ErrorInfo}");
        return false;
    }
}