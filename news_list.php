<?php
require_once BASE_PATH . '/templates/header.php';

global $pdo;

// ===== Пагинация =====
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

// ===== Счётчик =====
$total = (int)$pdo->query("SELECT COUNT(*) FROM news WHERE status = 'published'")->fetchColumn();
$totalPages = max(1, ceil($total / $perPage));

// ===== Список =====
$stmt = $pdo->prepare("
    SELECT n.*, u.name AS author_name, u.company_name AS author_company
    FROM news n
    JOIN users u ON u.id = n.author_id
    WHERE n.status = 'published'
    ORDER BY COALESCE(n.published_at, n.created_at) DESC, n.id DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute();
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    .news-page-header {
        text-align: center;
        margin-bottom: 36px;
    }
    .news-page-header h1 {
        font-size: 40px;
        font-weight: 900;
        letter-spacing: -1.5px;
        margin-bottom: 10px;
    }
    .news-page-header p {
        font-size: 15px;
        color: var(--text-secondary);
    }

    .news-page-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 20px;
    }
    .news-page-card {
        display: flex;
        flex-direction: column;
        background: var(--surface);
        border: 1px solid var(--border);
        text-decoration: none;
        color: inherit;
        overflow: hidden;
        transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        position: relative;
    }
    .news-page-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 32px rgba(0,0,0,0.1);
        border-color: var(--accent);
    }
    .news-page-card:hover .news-page-title {
        color: var(--accent);
    }
    .news-page-cover {
        width: 100%;
        aspect-ratio: 16/10;
        overflow: hidden;
        background: linear-gradient(135deg, #000, #1a1a1a);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .news-page-cover img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.4s ease;
    }
    .news-page-card:hover .news-page-cover img {
        transform: scale(1.05);
    }
    .news-page-cover .placeholder {
        font-size: 56px;
        opacity: 0.5;
    }
    .news-page-body {
        padding: 20px;
        display: flex;
        flex-direction: column;
        gap: 8px;
        flex: 1;
    }
    .news-page-date {
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1.2px;
        color: var(--text-secondary);
    }
    .news-page-title {
        font-size: 17px;
        font-weight: 800;
        letter-spacing: -0.3px;
        line-height: 1.35;
        margin: 0;
        transition: color 0.2s;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .news-page-excerpt {
        font-size: 13px;
        color: var(--text-secondary);
        line-height: 1.55;
        margin: 0;
        flex: 1;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .news-page-cta {
        font-size: 12px;
        font-weight: 800;
        color: var(--accent);
        margin-top: 4px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    @media (max-width: 768px) {
        .news-page-header h1 { font-size: 28px; }
        .news-page-grid { grid-template-columns: 1fr; gap: 14px; }
    }
</style>

<div style="max-width:1000px; margin:0 auto; padding:0 0 40px;">
    
    <div class="news-page-header">
        <h1>📰 Новости</h1>
        <p>Что происходит на AVL Logistic</p>
    </div>

    <?php if (empty($items)): ?>
        <div class="card" style="text-align:center; padding:60px 20px;">
            <div style="font-size:64px; opacity:0.4; margin-bottom:16px;">📭</div>
            <h2 style="margin:0 0 8px;">Пока новостей нет</h2>
            <p style="color:var(--text-secondary); margin:0;">
                Загляните позже — мы регулярно публикуем обновления платформы.
            </p>
        </div>
    <?php else: ?>
        <div class="news-page-grid">
            <?php foreach ($items as $n): 
                $publishedAt = $n['published_at'] ?: $n['created_at'];
                $excerpt = $n['excerpt'] ?: mb_substr(strip_tags($n['content']), 0, 180) . '…';
            ?>
                <a href="/news/view/<?= $n['id'] ?>" class="news-page-card">
                    <div class="news-page-cover">
                        <?php if (!empty($n['image_path'])): ?>
                            <img src="/<?= htmlspecialchars($n['image_path']) ?>" 
                                 alt="<?= htmlspecialchars($n['title']) ?>"
                                 loading="lazy">
                        <?php else: ?>
                            <span class="placeholder">📰</span>
                        <?php endif; ?>
                    </div>
                    <div class="news-page-body">
                        <div class="news-page-date">📅 <?= date('d.m.Y', strtotime($publishedAt)) ?></div>
                        <h3 class="news-page-title"><?= htmlspecialchars($n['title']) ?></h3>
                        <p class="news-page-excerpt"><?= htmlspecialchars($excerpt) ?></p>
                        <div class="news-page-cta">Читать →</div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
            <div style="display:flex; justify-content:center; gap:6px; margin-top:32px; flex-wrap:wrap;">
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <a href="/news?page=<?= $p ?>" 
                       class="btn <?= $p === $page ? 'btn-primary' : 'btn-outline' ?> btn-sm" 
                       style="min-width:36px;"><?= $p ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once BASE_PATH . '/templates/footer.php'; ?>