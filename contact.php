<?php
require_once __DIR__ . '/includes/auth.php';

$success = false;
$error = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if (empty($name) || empty($email) || empty($message)) {
        $error = 'Пожалуйста, заполните все обязательные поля';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Некорректный email';
    } else {
        // Сохраняем обращение в БД или отправляем на email
        $to = 'support@protech-no.ru';
        $body = "Имя: $name\nEmail: $email\nТема: $subject\n\nСообщение:\n$message";
        $headers = "From: $email\r\nReply-To: $email\r\nContent-Type: text/plain; charset=UTF-8";
        
        if (@mail($to, 'Обращение в поддержку: ' . $subject, $body, $headers)) {
            $success = true;
        } else {
            $error = 'Ошибка отправки. Попробуйте позже или напишите на support@protech-no.ru';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>
<div class="container" style="max-width:700px;padding:60px 20px">
    <h1 style="font-size:2rem;font-weight:800;margin-bottom:32px">Контакты и поддержка</h1>
    
    <?php if ($success): ?>
    <div class="alert alert-success" style="margin-bottom:24px">
        ✅ Ваше обращение отправлено! Мы ответим вам в ближайшее время.
    </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="alert alert-error" style="margin-bottom:24px">
        ❌ <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>
    
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:32px">
        <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);padding:24px">
            <h3 style="margin-bottom:16px">📧 Email поддержки</h3>
            <p style="color:var(--text2);margin-bottom:12px">Для общих вопросов:</p>
            <a href="mailto:support@protech-no.ru" style="color:var(--accent);font-weight:600">support@protech-no.ru</a>
            <p style="color:var(--text2);margin:16px 0 12px">По вопросам заказов:</p>
            <a href="mailto:orders@protech-no.ru" style="color:var(--accent);font-weight:600">orders@protech-no.ru</a>
        </div>
        
        <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);padding:24px">
            <h3 style="margin-bottom:16px">📞 Телефон</h3>
            <p style="color:var(--text2);margin-bottom:12px">Горячая линия:</p>
            <a href="tel:+78005553535" style="color:var(--accent);font-weight:600;font-size:1.1rem">+7 (800) 555-35-35</a>
            <p style="color:var(--text3);font-size:0.85rem;margin-top:8px">Пн-Пт: 9:00 - 21:00<br>Сб-Вс: 10:00 - 18:00</p>
        </div>
    </div>
    
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);padding:32px">
        <h2 style="margin-bottom:24px">📝 Написать в поддержку</h2>
        
        <form method="POST" action="">
            <div class="form-group" style="margin-bottom:16px">
                <label>Ваше имя <span class="required">*</span></label>
                <input type="text" name="name" class="form-control" placeholder="Иван Иванов" required>
            </div>
            
            <div class="form-group" style="margin-bottom:16px">
                <label>Email <span class="required">*</span></label>
                <input type="email" name="email" class="form-control" placeholder="example@mail.ru" required>
            </div>
            
            <div class="form-group" style="margin-bottom:16px">
                <label>Тема</label>
                <input type="text" name="subject" class="form-control" placeholder="Вопрос по заказу">
            </div>
            
            <div class="form-group" style="margin-bottom:20px">
                <label>Сообщение <span class="required">*</span></label>
                <textarea name="message" class="form-control" rows="5" placeholder="Опишите ваш вопрос..." required></textarea>
            </div>
            
            <button type="submit" class="btn-primary" style="width:100%">
                📩 Отправить обращение
            </button>
            
            <p style="margin-top:16px;font-size:0.85rem;color:var(--text3);text-align:center">
                Среднее время ответа: 2-4 часа в рабочее время
            </p>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>