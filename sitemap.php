<?php
require_once __DIR__ . '/app/config.php';
require_once __DIR__ . '/app/database.php';

header('Content-Type: application/xml; charset=utf-8');

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <!-- Главные страницы -->
    <url>
        <loc>https://avllogist.ru/</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc>https://avllogist.ru/orders</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>hourly</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc>https://avllogist.ru/register</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc>https://avllogist.ru/pricing</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>
    <url>
        <loc>https://avllogist.ru/page/carriers</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc>https://avllogist.ru/page/owners</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
    
    <!-- Активные заказы -->
    <?php
    try {
        $stmt = $pdo->query("
            SELECT id, created_at 
            FROM orders 
            WHERE status IN ('new', 'active', 'accepted') 
            ORDER BY id DESC 
            LIMIT 500
        ");
        $orders = $stmt->fetchAll();
        
        foreach ($orders as $order) {
            $lastmod = date('Y-m-d', strtotime($order['created_at']));
    ?>
    <url>
        <loc>https://avllogist.ru/orders/view/<?= $order['id'] ?></loc>
        <lastmod><?= $lastmod ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>0.6</priority>
    </url>
    <?php 
        }
    } catch (Exception $e) {
        // Игнорируем
    }
    ?>
</urlset>