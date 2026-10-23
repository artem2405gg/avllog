<?php
function generateDocumentsForOrder($orderId) {
    // Просто сохраняем HTML-версии документов в БД
    // PDF создаётся на лету при открытии страницы через print
    global $pdo;
    
    // Создаём записи в БД, что документы готовы
    $stmt = $pdo->prepare("INSERT INTO documents (order_id, type, file_path) VALUES (?, 'contract', ?)");
    $stmt->execute([$orderId, "contract_html"]);
    
    $stmt = $pdo->prepare("INSERT INTO documents (order_id, type, file_path) VALUES (?, 'waybill', ?)");
    $stmt->execute([$orderId, "waybill_html"]);
}

function viewDocument($orderId, $type) {
    checkAuth();
    global $pdo;
    
    // Получаем данные как раньше
    $stmt = $pdo->prepare("
        SELECT o.*, 
               u1.name as owner_name, u1.company_name as owner_company, u1.inn as owner_inn,
               u2.name as carrier_name, u2.company_name as carrier_company, u2.inn as carrier_inn,
               b.price as agreed_price
        FROM orders o
        JOIN users u1 ON o.user_id = u1.id
        JOIN bids b ON o.id = b.order_id AND b.status = 'accepted'
        JOIN users u2 ON b.carrier_id = u2.id
        WHERE o.id = ?
    ");
    $stmt->execute([$orderId]);
    $data = $stmt->fetch();
    
    if (!$data) {
        die("Документ не найден");
    }
    
    // Показываем HTML-страницу с кнопкой печати
    if ($type === 'contract') {
        showContractHtml($data);
    } else {
        showWaybillHtml($data);
    }
}

function showContractHtml($data) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>Договор-заявка №<?= $data['id'] ?></title>
        <style>
            @media print {
                .no-print { display: none; }
                body { margin: 0; padding: 20px; }
            }
            body { 
                font-family: Arial, sans-serif; 
                max-width: 800px; 
                margin: 0 auto; 
                padding: 20px; 
            }
            .header { text-align: center; font-size: 18px; font-weight: bold; margin-bottom: 20px; }
            table { width: 100%; border-collapse: collapse; margin: 15px 0; }
            td { padding: 8px; border: 1px solid #000; }
            .label { background: #f5f5f5; width: 30%; font-weight: bold; }
            .no-print { 
                background: #007bff; color: white; border: none; 
                padding: 12px 30px; font-size: 16px; border-radius: 5px; 
                cursor: pointer; margin-bottom: 20px; 
            }
            .no-print:hover { background: #0056b3; }
            .signatures { margin-top: 50px; }
            .signatures td { border: none; padding: 30px 10px; vertical-align: bottom; }
        </style>
    </head>
    <body>
        <button class="no-print" onclick="window.print()">🖨️ Распечатать / Сохранить как PDF</button>
        <p class="no-print" style="color:#666;">Нажмите кнопку выше, затем в окне печати выберите «Сохранить как PDF»</p>
        
        <div class="header">ДОГОВОР-ЗАЯВКА № <?= $data['id'] ?><br>на перевозку груза автомобильным транспортом</div>
        
        <table>
            <tr><td class="label">Заказчик:</td><td><?= $data['owner_company'] ?: $data['owner_name'] ?>, ИНН <?= $data['owner_inn'] ?></td></tr>
            <tr><td class="label">Перевозчик:</td><td><?= $data['carrier_company'] ?: $data['carrier_name'] ?>, ИНН <?= $data['carrier_inn'] ?></td></tr>
            <tr><td class="label">Маршрут:</td><td><?= $data['pickup_address'] ?> → <?= $data['delivery_address'] ?></td></tr>
            <tr><td class="label">Груз:</td><td><?= $data['cargo_type'] ?>, <?= $data['weight'] ?> т, <?= $data['volume'] ?> м³</td></tr>
            <tr><td class="label">Дата погрузки:</td><td><?= $data['pickup_date'] ?></td></tr>
            <tr><td class="label">Дата доставки:</td><td><?= $data['delivery_date'] ?></td></tr>
            <tr><td class="label">Стоимость:</td><td><strong><?= number_format($data['agreed_price'], 0, ',', ' ') ?> ₽</strong></td></tr>
        </table>
        
        <p>Настоящий договор имеет силу акта выполненных работ при наличии подписей и печатей сторон.</p>
        
        <table class="signatures">
            <tr>
                <td>Заказчик: _______________<br><?= $data['owner_name'] ?></td>
                <td>Перевозчик: _______________<br><?= $data['carrier_name'] ?></td>
            </tr>
        </table>
    </body>
    </html>
    <?php
}

function showWaybillHtml($data) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>Транспортная накладная №<?= $data['id'] ?></title>
        <style>
            @media print {
                .no-print { display: none; }
                body { margin: 0; padding: 20px; }
            }
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
            h3 { text-align: center; }
            table { width: 100%; border-collapse: collapse; margin: 10px 0; }
            td { padding: 6px; border: 1px solid #000; }
            td:first-child { font-weight: bold; width: 40%; }
            .no-print { 
                background: #007bff; color: white; border: none; 
                padding: 12px 30px; font-size: 16px; border-radius: 5px; 
                cursor: pointer; margin-bottom: 20px; 
            }
        </style>
    </head>
    <body>
        <button class="no-print" onclick="window.print()">🖨️ Распечатать / Сохранить как PDF</button>
        
        <h3>ТРАНСПОРТНАЯ НАКЛАДНАЯ</h3>
        <table>
            <tr><td>Грузоотправитель</td><td><?= $data['owner_company'] ?></td></tr>
            <tr><td>Грузополучатель</td><td><?= $data['delivery_address'] ?></td></tr>
            <tr><td>Перевозчик</td><td><?= $data['carrier_company'] ?></td></tr>
            <tr><td>Груз</td><td><?= $data['cargo_type'] ?>, <?= $data['weight'] ?> т</td></tr>
            <tr><td>Дата погрузки</td><td><?= $data['pickup_date'] ?></td></tr>
            <tr><td>Дата выгрузки</td><td><?= $data['delivery_date'] ?></td></tr>
        </table>
        <p style="margin-top:40px;">Груз принял: _____________ &nbsp;&nbsp;&nbsp;&nbsp; Груз сдал: _____________</p>
        <p>Подпись водителя: _____________ &nbsp;&nbsp;&nbsp;&nbsp; Подпись получателя: _____________</p>
    </body>
    </html>
    <?php
}