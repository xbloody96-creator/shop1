/**
 * Файл: news_detail.php
 * Описание: Страница сайта
 * @version 1.0
 */

<?php
/**
 * Страница отдельной новости
 * Показывает полную версию новости по ID
 */
// Подключение модуля аутентификации
require_once __DIR__ . '/includes/auth.php';

// Получаем ID новости из параметров
$newsId = (int)($_GET['id'] ?? 0);

// Запрашиваем активную новость с указанным ID
$stmt = // SQL Запрос: выборка данных
    $pdo->prepare("SELECT * FROM news WHERE id = ? AND is_active = 1");
$stmt->execute([$newsId]);
$newsItem = $stmt->fetch();

// Если новость не найдена — перенаправляем на список новостей
if (!$newsItem) {
    // Перенаправление пользователя
header('Location: /shop/news.php');
    exit;
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width:860px">
    <!-- Хлебные крошки -->
    <div class="breadcrumbs">
        <a href="/shop/index.php">Главная</a> /
        <a href="/shop/news.php">Новости</a> /
        <span><?= htmlspecialchars($newsItem['title']) ?></span>
    </div>
    
    <!-- Карточка новости -->
    <article style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:40px;margin-top:16px">
        <!-- Дата публикации -->
        <div style="font-size:0.85rem;color:var(--text-muted);margin-bottom:12px">
            📅 <?= date('d.m.Y', strtotime($newsItem['created_at'])) ?>
        </div>
        
        <!-- Заголовок -->
        <h1 style="font-size:1.8rem;font-weight:800;margin-bottom:20px">
            <?= htmlspecialchars($newsItem['title']) ?>
        </h1>
        
        <!-- Изображение (если есть) -->
        <?php if ($newsItem['image']): ?>
        <img src="/shop/uploads/<?= htmlspecialchars($newsItem['image']) ?>" 
             alt="<?= htmlspecialchars($newsItem['title']) ?>" 
             style="width:100%;max-height:400px;object-fit:cover;border-radius:var(--radius-sm);margin-bottom:24px">
        <?php endif; ?>
        
        <!-- Полный текст новости -->
        <div style="line-height:1.9;color:var(--text-muted);font-size:0.95rem">
            <?= nl2br(htmlspecialchars($newsItem['content'] ?? '')) ?>
        </div>
    </article>
    
    <!-- Кнопка "Назад к списку" -->
    <div style="margin-top:20px">
        <a href="/shop/news.php" style="color:var(--accent2);font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:6px">
            ← Все новости
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
