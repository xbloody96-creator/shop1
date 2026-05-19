/**
 * Файл: search.php
 * Описание: Страница сайта
 * @version 1.0
 */

<?php
/**
 * Страница поиска товаров
 * Ищет товары по названию и описанию с поддержкой пагинации
 */
// Подключение модуля аутентификации
require_once __DIR__ . '/includes/auth.php';

// Получаем и очищаем поисковый запрос
$searchQuery = trim($_GET['q'] ?? '');
$currentPage = max(1, (int)($_GET['page'] ?? 1));
$itemsPerPage = 20;
$offset = ($currentPage - 1) * $itemsPerPage;

$foundProducts = [];
$totalFound = 0;

// Поиск выполняется только если введено минимум 2 символа
if (strlen($searchQuery) >= 2) {
    $likePattern = '%' . $searchQuery . '%';
    
    // Считаем общее количество найденных товаров
    $countStmt = $pdo->prepare("
        SELECT COUNT(*) FROM products 
        WHERE (name LIKE ? OR description LIKE ?) AND is_active = 1
    ");
    $countStmt->execute([$likePattern, $likePattern]);
    $totalFound = (int)$countStmt->fetchColumn();

    // Получаем товары для текущей страницы
    $stmt = $pdo->prepare("
        SELECT * FROM products 
        WHERE (name LIKE :like1 OR description LIKE :like2) AND is_active = 1 
        ORDER BY is_popular DESC, name ASC 
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':like1', $likePattern);
    $stmt->bindValue(':like2', $likePattern);
    $stmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $foundProducts = $stmt->fetchAll();
}

// Рассчитываем количество страниц
$totalPages = (int)ceil($totalFound / $itemsPerPage);

require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <!-- Хлебные крошки -->
    <div class="breadcrumbs">
        <a href="/shop/index.php">Главная</a> / <span>Поиск</span>
    </div>

    <!-- Заголовок с результатами -->
    <h1 style="font-size:1.4rem;font-weight:800;margin:16px 0">
        🔍 Поиск: «<?= htmlspecialchars($searchQuery) ?>»
        <?php if ($searchQuery): ?>
            <span style="font-size:0.9rem;color:var(--text-muted);font-weight:400">
                — найдено <?= $totalFound ?>
            </span>
        <?php endif; ?>
    </h1>

    <!-- Форма поиска -->
    <form action="" method="GET" style="max-width:600px;display:flex;gap:8px;margin-bottom:32px">
        <input type="text" name="q" value="<?= htmlspecialchars($searchQuery) ?>"
               class="form-control" placeholder="Введите название товара..." style="flex:1">
        <button type="submit" class="btn-primary" style="width:auto;padding:10px 24px">Найти</button>
    </form>

    <?php if (empty($searchQuery)): ?>
        <!-- Подсказка, если запрос ещё не введён -->
        <div class="empty-state">
            <div class="empty-icon">🔍</div>
            <h3>Введите запрос</h3>
            <p>Начните вводить название товара для поиска</p>
        </div>
        
    <?php elseif (empty($foundProducts)): ?>
        <!-- Ничего не найдено -->
        <div class="empty-state">
            <div class="empty-icon">😔</div>
            <h3>Ничего не найдено</h3>
            <p>По запросу «<?= htmlspecialchars($searchQuery) ?>» товаров не найдено. Попробуйте изменить формулировку.</p>
            <a href="/shop/catalog.php" class="btn-primary" style="display:inline-block;margin-top:20px;max-width:200px">
                Смотреть весь каталог
            </a>
        </div>
        
    <?php else: ?>
        <!-- Список найденных товаров -->
        <div class="products-grid">
            <?php foreach ($foundProducts as $product): ?>
                <?php include __DIR__ . '/includes/product_card.php'; ?>
            <?php endforeach; ?>
        </div>

        <!-- Пагинация -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($currentPage > 1): ?>
                <a href="?q=<?= urlencode($searchQuery) ?>&page=<?= $currentPage - 1 ?>">‹</a>
            <?php endif; ?>
            
            <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
                <?php if ($i === $currentPage): ?>
                    <span class="current"><?= $i ?></span>
                <?php else: ?>
                    <a href="?q=<?= urlencode($searchQuery) ?>&page=<?= $i ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($currentPage < $totalPages): ?>
                <a href="?q=<?= urlencode($searchQuery) ?>&page=<?= $currentPage + 1 ?>">›</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
