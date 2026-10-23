<?php
require_once BASE_PATH . '/templates/header.php';

global $pdo;

if (!$news) {
    header('Location: /news');
    exit;
}

$publishedAt = $news['published_at'] ?: $news['created_at'];
$authorName  = $news['author_company'] ?: $news['author_name'];

// Время чтения (на лету, без колонки в БД)
$plainText = trim(strip_tags($news['content']));
$wordCount = $plainText ? count(preg_split('/\s+/u', $plainText, -1, PREG_SPLIT_NO_EMPTY)) : 0;
$readTime  = max(1, (int)ceil($wordCount / 180));

// Похожие новости (3 штуки, кроме текущей)
$stmt = $pdo->prepare("
    SELECT id, title, excerpt, content, image_path, published_at, created_at
    FROM news
    WHERE status = 'published' AND id != ?
    ORDER BY COALESCE(published_at, created_at) DESC, id DESC
    LIMIT 3
");
$stmt->execute([$news['id']]);
$related = $stmt->fetchAll(PDO::FETCH_ASSOC);

// URL текущей страницы для «поделиться»
$shareUrl = SITE_URL . '/news/view/' . $news['id'];
$shareText = urlencode($news['title'] . ' — AVL Logistic');
?>

<style>
    /* ============ ПРОГРЕСС-БАР ЧТЕНИЯ ============ */
    .read-progress {
        position: fixed;
        top: 3px;
        left: 0;
        height: 3px;
        background: linear-gradient(90deg, var(--accent), #fbbf24);
        z-index: 9999;
        width: 0%;
        transition: width 0.1s linear;
    }

    /* ============ ШАПКА С ОБЛОЖКОЙ ============ */
    .news-hero {
        position: relative;
        width: 100%;
        aspect-ratio: 21/9;
        overflow: hidden;
        background: linear-gradient(135deg, #0a0a0a, #1f1f1f);
        margin-bottom: 32px;
    }
    .news-hero img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    .news-hero::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, rgba(0,0,0,0.1) 0%, rgba(0,0,0,0.75) 100%);
    }
    .news-hero-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        padding: 40px 24px 32px;
        z-index: 2;
        color: white;
    }
    .news-hero-overlay .news-category {
        display: inline-block;
        padding: 4px 12px;
        background: var(--accent);
        color: var(--primary);
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        margin-bottom: 12px;
    }
    .news-hero-overlay h1 {
        color: white;
        font-size: 36px;
        font-weight: 900;
        letter-spacing: -1px;
        line-height: 1.15;
        margin: 0;
        max-width: 900px;
    }
    @media (max-width: 768px) {
        .news-hero { aspect-ratio: 16/10; }
        .news-hero-overlay { padding: 24px 16px 20px; }
        .news-hero-overlay h1 { font-size: 22px; }
    }

    /* ============ МЕТА ============ */
    .news-meta {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
        align-items: center;
        padding-bottom: 20px;
        border-bottom: 1px solid var(--border);
        margin-bottom: 28px;
        font-size: 13px;
        color: var(--text-secondary);
    }
    .news-meta span {
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .news-meta strong { color: var(--text); }

    /* ============ КОНТЕНТ ============ */
    .news-content {
        font-size: 17px;
        line-height: 1.75;
        color: var(--text);
        word-wrap: break-word;
        max-width: 720px;
        margin: 0 auto;
    }
    .news-content h2 {
        font-size: 24px;
        font-weight: 800;
        letter-spacing: -0.5px;
        margin: 32px 0 14px;
        line-height: 1.25;
    }
    .news-content h3 {
        font-size: 19px;
        font-weight: 700;
        margin: 24px 0 10px;
        line-height: 1.3;
    }
    .news-content p {
        margin: 0 0 18px;
    }
    .news-content ul, .news-content ol {
        margin: 0 0 18px;
        padding-left: 26px;
    }
    .news-content li {
        margin-bottom: 8px;
    }
    .news-content a {
        color: var(--accent);
        text-decoration: underline;
        text-underline-offset: 3px;
        font-weight: 600;
    }
    .news-content a:hover {
        color: #b45309;
    }
    .news-content blockquote {
        border-left: 4px solid var(--accent);
        padding: 4px 0 4px 20px;
        margin: 22px 0;
        font-size: 17px;
        color: var(--text-secondary);
        font-style: italic;
        background: var(--border-light);
        padding: 14px 20px;
    }
    .news-content img {
        max-width: 100%;
        height: auto;
        display: block;
        margin: 24px auto;
        border: 1px solid var(--border);
    }
    .news-content strong { font-weight: 800; }
    .news-content em { font-style: italic; }

    /* ============ SHARE ============ */
    .share-block {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        align-items: center;
        margin: 40px 0 0;
        padding-top: 24px;
        border-top: 1px solid var(--border);
    }
    .share-label {
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        color: var(--text-secondary);
        margin-right: 8px;
    }
    .share-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 9px 16px;
        border: 1px solid var(--border);
        background: var(--surface);
        color: var(--text);
        font-size: 12px;
        font-weight: 700;
        text-decoration: none;
        cursor: pointer;
        font-family: inherit;
        transition: all 0.2s;
    }
    .share-btn:hover {
        border-color: var(--primary);
        background: var(--border-light);
        transform: translateY(-1px);
    }
    .share-btn.tg:hover { background: #e8f4fd; border-color: #229ED9; color: #229ED9; }
    .share-btn.vk:hover { background: #eef3fb; border-color: #0077FF; color: #0077FF; }
    .share-btn.copy.copied { background: #d1fae5; border-color: var(--success); color: #065f46; }

    /* ============ ПОХОЖИЕ ============ */
    .related-block {
        margin-top: 60px;
        padding-top: 40px;
        border-top: 2px solid var(--border);
    }
    .related-block h2 {
        font-size: 18px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        color: var(--text-secondary);
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .related-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
    }
    .related-card {
        display: flex;
        flex-direction: column;
        text-decoration: none;
        color: inherit;
        background: var(--surface);
        border: 1px solid var(--border);
        overflow: hidden;
        transition: all 0.25s;
    }
    .related-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.08);
        border-color: var(--accent);
    }
    .related-card-cover {
        width: 100%;
        aspect-ratio: 16/9;
        overflow: hidden;
        background: linear-gradient(135deg, #000, #1a1a1a);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .related-card-cover img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .related-card-cover .placeholder { font-size: 32px; opacity: 0.5; }
    .related-card-body { padding: 14px; }
    .related-card-date {
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1.2px;
        color: var(--text-secondary);
        margin-bottom: 6px;
    }
    .related-card-title {
        font-size: 14px;
        font-weight: 800;
        line-height: 1.35;
        margin: 0;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    @media (max-width: 768px) {
        .news-content { font-size: 16px; }
        .news-content h2 { font-size: 20px; }
        .related-grid { grid-template-columns: 1fr; }
        .share-block { gap: 8px; }
    }
</style>

<!-- Прогресс-бар -->
<div class="read-progress" id="readProgress"></div>

<!-- Хлебные крошки -->
<div style="max-width:1120px; margin:16px auto 0; padding:0 24px; font-size:13px; color:var(--text-secondary);">
    <a href="/news" style="color:var(--text-secondary); text-decoration:none;">📰 Новости</a>
    <span style="opacity:0.4;"> / </span>
    <span style="color:var(--text); font-weight:600;">
        <?= htmlspecialchars(mb_substr($news['title'], 0, 60)) ?><?= mb_strlen($news['title']) > 60 ? '…' : '' ?>
    </span>
</div>

<!-- ШАПКА С ОБЛОЖКОЙ -->
<?php if (!empty($news['image_path'])): ?>
    <div class="news-hero">
        <img src="/<?= htmlspecialchars($news['image_path']) ?>" 
             alt="<?= htmlspecialchars($news['title']) ?>">
        <div class="news-hero-overlay">
            <div class="news-category">📰 Новость</div>
            <h1><?= htmlspecialchars($news['title']) ?></h1>
        </div>
    </div>
<?php else: ?>
    <div style="max-width:1120px; margin:16px auto 0; padding:0 24px;">
        <h1 style="font-size:36px; font-weight:900; letter-spacing:-1px; line-height:1.15; margin:16px 0 0;">
            <?= htmlspecialchars($news['title']) ?>
        </h1>
    </div>
<?php endif; ?>

<!-- МЕТА -->
<div style="max-width:760px; margin:0 auto; padding:0 24px;">
    <div class="news-meta" style="margin-top:<?= !empty($news['image_path']) ? '0' : '16px' ?>;">
        <span>📅 <strong><?= date('d.m.Y', strtotime($publishedAt)) ?></strong></span>
        <span>👤 <strong><?= htmlspecialchars($authorName) ?></strong></span>
        <span>⏱ <strong><?= $readTime ?></strong> мин чтения</span>
        <span>👁 <strong><?= (int)$news['views_count'] ?></strong></span>
    </div>

    <!-- КОНТЕНТ (HTML от Quill, уже прогонялся через sanitize) -->
    <div class="news-content">
        <?= $news['content'] ?>
    </div>

    <!-- ПОДЕЛИТЬСЯ -->
    <div class="share-block">
        <span class="share-label">Поделиться:</span>
        <a href="https://t.me/share/url?url=<?= urlencode($shareUrl) ?>&text=<?= $shareText ?>"
           target="_blank" rel="noopener" class="share-btn tg">
            ✈️ Telegram
        </a>
        <a href="https://vk.com/share.php?url=<?= urlencode($shareUrl) ?>&title=<?= $shareText ?>"
           target="_blank" rel="noopener" class="share-btn vk">
            🔵 ВКонтакте
        </a>
        <button type="button" class="share-btn copy" id="copyLink" data-url="<?= htmlspecialchars($shareUrl) ?>">
            🔗 Скопировать ссылку
        </button>
    </div>

    <!-- НАЗАД -->
    <div style="margin-top:32px;">
        <a href="/news" class="btn btn-outline">← Все новости</a>
    </div>

    <!-- ПОХОЖИЕ -->
    <?php if (!empty($related)): ?>
    <div class="related-block">
        <h2>📚 Читайте также</h2>
        <div class="related-grid">
            <?php foreach ($related as $r): 
                $rDate = $r['published_at'] ?: $r['created_at'];
            ?>
                <a href="/news/view/<?= $r['id'] ?>" class="related-card">
                    <div class="related-card-cover">
                        <?php if (!empty($r['image_path'])): ?>
                            <img src="/<?= htmlspecialchars($r['image_path']) ?>" 
                                 alt="<?= htmlspecialchars($r['title']) ?>" loading="lazy">
                        <?php else: ?>
                            <span class="placeholder">📰</span>
                        <?php endif; ?>
                    </div>
                    <div class="related-card-body">
                        <div class="related-card-date">📅 <?= date('d.m.Y', strtotime($rDate)) ?></div>
                        <h3 class="related-card-title"><?= htmlspecialchars($r['title']) ?></h3>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// ========== ПРОГРЕСС-БАР ЧТЕНИЯ ==========
(function() {
    const bar = document.getElementById('readProgress');
    if (!bar) return;
    
    function update() {
        const scrollTop = window.scrollY;
        const docHeight = document.documentElement.scrollHeight - window.innerHeight;
        const pct = docHeight > 0 ? (scrollTop / docHeight) * 100 : 0;
        bar.style.width = Math.min(100, Math.max(0, pct)) + '%';
    }
    
    window.addEventListener('scroll', update, { passive: true });
    window.addEventListener('resize', update);
    update();
})();

// ========== КОПИРОВАТЬ ССЫЛКУ ==========
document.getElementById('copyLink')?.addEventListener('click', function() {
    const url = this.dataset.url;
    const btn = this;
    
    navigator.clipboard.writeText(url).then(() => {
        const original = btn.textContent;
        btn.textContent = '✅ Скопировано';
        btn.classList.add('copied');
        setTimeout(() => {
            btn.textContent = original;
            btn.classList.remove('copied');
        }, 2000);
    }).catch(() => {
        // Fallback для старых браузеров
        const ta = document.createElement('textarea');
        ta.value = url;
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
        
        btn.textContent = '✅ Скопировано';
        setTimeout(() => btn.textContent = '🔗 Скопировать ссылку', 2000);
    });
});
</script>

<?php require_once BASE_PATH . '/templates/footer.php'; ?>