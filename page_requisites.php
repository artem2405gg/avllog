<?php
require_once BASE_PATH . '/templates/header.php';
?>

<style>
    .req-page { max-width: 700px; margin: 0 auto; padding: 20px 0 60px; }
    .req-card {
        background: var(--surface);
        border: 1px solid var(--border);
        padding: 40px;
    }
    .req-card h1 {
        font-size: 28px;
        font-weight: 900;
        letter-spacing: -0.5px;
        margin-bottom: 24px;
        padding-bottom: 16px;
        border-bottom: 2px solid var(--border-light);
    }
    .req-row {
        display: grid;
        grid-template-columns: 200px 1fr;
        gap: 20px;
        padding: 14px 0;
        border-bottom: 1px solid var(--border-light);
        font-size: 14px;
    }
    .req-row:last-child { border-bottom: none; }
    .req-row .label { color: var(--text-secondary); font-weight: 600; }
    .req-row .value { font-weight: 700; }
    @media (max-width: 768px) {
        .req-card { padding: 24px 20px; }
        .req-row { grid-template-columns: 1fr; gap: 4px; }
    }
</style>

<div class="req-page">
    <div class="req-card">
        <h1>Реквизиты</h1>
        
        <div class="req-row">
            <div class="label">Организация</div>
            <div class="value">ИП Гусева Анастасия Максимовна</div>
        </div>
        <div class="req-row">
            <div class="label">ИНН</div>
            <div class="value">352403547008</div>
        </div>
        <div class="req-row">
            <div class="label">ОГРНИП</div>
            <div class="value">324350000057980</div>
        </div>
        <div class="req-row">
            <div class="label">Юридический адрес</div>
            <div class="value">162560, РОССИЯ, ВОЛОГОДСКАЯ ОБЛ, ШЕКСНИНСКИЙ Р-Н, РП. ШЕКСНА, РП ШЕКСНА, УЛ ЛЕНИНА, Д 29, КВ 1</div>
        </div>
        <div class="req-row">
            <div class="label">Расчётный счёт</div>
            <div class="value">40802810200007492414</div>
        </div>
        <div class="req-row">
            <div class="label">Банк</div>
            <div class="value">АО «ТБанк»</div>
        </div>
        <div class="req-row">
            <div class="label">БИК</div>
            <div class="value">044525974</div>
        </div>
        <div class="req-row">
            <div class="label">Корр. счёт</div>
            <div class="value">30101810145250000974</div>
        </div>
        <div class="req-row">
            <div class="label">Email</div>
            <div class="value">info@avllogist.ru</div>
        </div>
        <div class="req-row">
            <div class="label">Телефон</div>
            <div class="value">+7 (921) 541-14-81</div>
        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/templates/footer.php'; ?>