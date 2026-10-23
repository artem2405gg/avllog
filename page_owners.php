<?php 
$pageTitle = 'Поиск перевозчиков для грузовладельцев'; 
require_once BASE_PATH . '/templates/header.php'; 
?>

<div class="card" style="max-width:800px; margin:0 auto;">
    <h1>Грузоперевозки для грузовладельцев и бизнеса</h1>
    <p style="font-size:16px; line-height:1.8; color:#64748b; margin-bottom:20px;">
        Нужно перевезти груз? AVL Logistic — платформа, где грузовладельцы находят проверенных перевозчиков 
        без посредников. Разместите заказ за 1 минуту и получайте отклики от водителей.
    </p>
    
    <h2>Преимущества для грузовладельцев</h2>
    <ul style="font-size:15px; line-height:2; color:#64748b; margin-bottom:20px;">
        <li>✅ Бесплатное размещение заказов</li>
        <li>✅ Поиск перевозчиков по всей России</li>
        <li>✅ Сравнение цен и рейтинга</li>
        <li>✅ Готовые документы в PDF</li>
        <li>✅ Отслеживание груза онлайн</li>
    </ul>
    
    <h2>Как это работает?</h2>
    <p style="font-size:15px; line-height:1.8; color:#64748b; margin-bottom:20px;">
        Создайте заказ — укажите маршрут, тип груза и бюджет. Перевозчики увидят ваш заказ 
        и предложат свои цены. Выберите лучшего и отслеживайте доставку.
    </p>
    
    <a href="/register" class="btn btn-primary btn-lg">📦 Зарегистрироваться как грузовладелец</a>
</div>

<?php require_once BASE_PATH . '/templates/footer.php'; ?>