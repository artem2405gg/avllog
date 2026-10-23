<?php 
$pageTitle = 'Поиск заказов для перевозчиков'; 
require_once BASE_PATH . '/templates/header.php'; 
?>

<div class="card" style="max-width:800px; margin:0 auto;">
    <h1>Грузоперевозки для водителей и перевозчиков</h1>
    <p style="font-size:16px; line-height:1.8; color:#64748b; margin-bottom:20px;">
        Ищете заказы на грузоперевозки? AVL Logistic — это платформа, где перевозчики находят прямые заказы 
        от грузовладельцев без посредников и диспетчеров. Зарегистрируйтесь и получите доступ к базе заказов 
        по всей России.
    </p>
    
    <h2>Почему AVL Logistic?</h2>
    <ul style="font-size:15px; line-height:2; color:#64748b; margin-bottom:20px;">
        <li>✅ Прямые заказы от грузовладельцев</li>
        <li>✅ Бесплатный пробный период 14 дней</li>
        <li>✅ Автоматические документы</li>
        <li>✅ Встроенный чат с заказчиком</li>
        <li>✅ Рейтинг и отзывы</li>
        <li>✅ Работает с телефона</li>
    </ul>
    
    <h2>Как начать?</h2>
    <p style="font-size:15px; line-height:1.8; color:#64748b; margin-bottom:20px;">
        Зарегистрируйтесь как перевозчик, заполните профиль и получите доступ к списку заказов. 
        Откликайтесь на подходящие заказы и зарабатывайте.
    </p>
    
    <a href="/register" class="btn btn-primary btn-lg">🚛 Зарегистрироваться как перевозчик</a>
</div>

<?php require_once BASE_PATH . '/templates/footer.php'; ?>