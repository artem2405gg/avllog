<?php
// Проверяем, проходил ли пользователь обучение
$stmt = $pdo->prepare("SELECT onboarding_done FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Если уже прошёл — пропускаем
if ($user['onboarding_done']) return;
?>

<style>
    .onboarding-overlay {
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0,0,0,0.8);
        z-index: 99999;
        display: flex;
        align-items: center;
        justify-content: center;
        animation: fadeIn 0.3s ease;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    .onboarding-card {
        background: white;
        border-radius: 20px;
        padding: 40px 30px;
        max-width: 420px;
        width: 90%;
        text-align: center;
        position: relative;
        animation: slideUp 0.4s ease;
    }
    
    @keyframes slideUp {
        from { transform: translateY(30px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    
    .onboarding-step {
        font-size: 11px;
        font-weight: 700;
        color: #9ca3af;
        text-transform: uppercase;
        letter-spacing: 2px;
        margin-bottom: 8px;
    }
    
    .onboarding-icon {
        font-size: 64px;
        margin-bottom: 16px;
        animation: bounce 1s ease infinite;
    }
    
    @keyframes bounce {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-10px); }
    }
    
    .onboarding-title {
        font-size: 22px;
        font-weight: 900;
        color: #0f172a;
        margin-bottom: 8px;
        line-height: 1.3;
    }
    
    .onboarding-text {
        font-size: 14px;
        color: #64748b;
        margin-bottom: 24px;
        line-height: 1.6;
    }
    
    .onboarding-progress {
        display: flex;
        justify-content: center;
        gap: 8px;
        margin-bottom: 24px;
    }
    
    .onboarding-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #e2e8f0;
        transition: all 0.3s;
    }
    
    .onboarding-dot.active {
        background: #f59e0b;
        width: 24px;
        border-radius: 4px;
    }
    
    .onboarding-buttons {
        display: flex;
        gap: 10px;
    }
    
    .onboarding-btn {
        flex: 1;
        padding: 14px;
        border-radius: 12px;
        font-size: 14px;
        font-weight: 700;
        cursor: pointer;
        border: none;
        font-family: 'Inter', sans-serif;
        transition: all 0.3s;
    }
    
    .onboarding-btn.skip {
        background: transparent;
        color: #9ca3af;
    }
    
    .onboarding-btn.next {
        background: #0f172a;
        color: white;
    }
    
    .onboarding-btn.next:hover {
        background: #1a1a1a;
    }
    
    .onboarding-btn.finish {
        background: #f59e0b;
        color: black;
        font-weight: 800;
    }
</style>

<div class="onboarding-overlay" id="onboarding">
    <div class="onboarding-card">
        <div class="onboarding-step" id="onStep">Шаг 1 из 4</div>
        <div class="onboarding-icon" id="onIcon">👋</div>
        <div class="onboarding-title" id="onTitle">Добро пожаловать!</div>
        <div class="onboarding-text" id="onText">
            <?php if ($_SESSION['user_role'] === 'owner'): ?>
                Здесь вы можете найти перевозчика для вашего груза. Давайте посмотрим как это работает.
            <?php else: ?>
                Здесь вы можете находить заказы и зарабатывать. Давайте посмотрим как это работает.
            <?php endif; ?>
        </div>
        
        <div class="onboarding-progress" id="onProgress">
            <div class="onboarding-dot active"></div>
            <div class="onboarding-dot"></div>
            <div class="onboarding-dot"></div>
            <div class="onboarding-dot"></div>
        </div>
        
        <div class="onboarding-buttons">
            <button class="onboarding-btn skip" onclick="skipOnboarding()">Пропустить</button>
            <button class="onboarding-btn next" id="onBtn" onclick="nextStep()">Далее</button>
        </div>
    </div>
</div>

<script>
<?php if ($_SESSION['user_role'] === 'owner'): ?>
var steps = [
    {
        icon: '👋',
        title: 'Добро пожаловать!',
        text: 'Здесь вы можете найти перевозчика для вашего груза. Давайте посмотрим как это работает.'
    },
    {
        icon: '📦',
        title: 'Создайте заказ',
        text: 'Нажмите кнопку <strong>+ Новый заказ</strong> и заполните данные: что везём, откуда, куда и бюджет. Это займёт 1 минуту.'
    },
    {
        icon: '📋',
        title: 'Получите отклики',
        text: 'Перевозчики увидят ваш заказ и предложат свою цену. Вы сможете сравнить предложения и выбрать лучшее.'
    },
    {
        icon: '💬',
        title: 'Общайтесь в чате',
        text: 'После выбора перевозчика откроется чат. Обсуждайте детали, отслеживайте статус и получайте документы.'
    }
];
<?php else: ?>
var steps = [
    {
        icon: '👋',
        title: 'Добро пожаловать!',
        text: 'Здесь вы можете находить заказы на перевозку и зарабатывать. Давайте посмотрим как это работает.'
    },
    {
        icon: '🔍',
        title: 'Просматривайте заказы',
        text: 'На странице <strong>Заказы</strong> вы увидите список доступных заказов. Листайте как в ТикТоке!'
    },
    {
        icon: '💰',
        title: 'Предлагайте цену',
        text: 'Нажмите на заказ и предложите свою цену. Заказчик увидит ваше предложение и сможет вас выбрать.'
    },
    {
        icon: '💬',
        title: 'Общайтесь и зарабатывайте',
        text: 'Когда вас выберут — откроется чат. Меняйте статусы заказа и получайте оплату.'
    }
];
<?php endif; ?>

var currentStep = 0;

function updateStep() {
    var step = steps[currentStep];
    document.getElementById('onStep').textContent = 'Шаг ' + (currentStep + 1) + ' из ' + steps.length;
    document.getElementById('onIcon').textContent = step.icon;
    document.getElementById('onTitle').textContent = step.title;
    document.getElementById('onText').innerHTML = step.text;
    
    // Обновляем точки
    var dots = document.querySelectorAll('.onboarding-dot');
    dots.forEach(function(dot, i) {
        dot.classList.toggle('active', i === currentStep);
    });
    
    // Меняем кнопку на последнем шаге
    var btn = document.getElementById('onBtn');
    if (currentStep === steps.length - 1) {
        btn.textContent = 'Понятно!';
        btn.classList.add('finish');
    } else {
        btn.textContent = 'Далее';
        btn.classList.remove('finish');
    }
}

function nextStep() {
    if (currentStep < steps.length - 1) {
        currentStep++;
        updateStep();
    } else {
        finishOnboarding();
    }
}

function skipOnboarding() {
    finishOnboarding();
}

function finishOnboarding() {
    document.getElementById('onboarding').style.display = 'none';
    fetch('/api/onboarding_done', { method: 'POST' });
}
</script>