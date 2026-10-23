<?php require_once BASE_PATH . '/templates/header.php'; 

global $pdo;
$ownerId = $_SESSION['user_id'];
$orderId = $order['id'];

$stmt = $pdo->prepare("SELECT DISTINCT pickup_address FROM orders WHERE user_id = ? AND pickup_address IS NOT NULL AND pickup_address != '' AND id != ? ORDER BY id DESC LIMIT 20");
$stmt->execute([$ownerId, $orderId]);
$pickupHistory = $stmt->fetchAll(PDO::FETCH_COLUMN);

$stmt = $pdo->prepare("SELECT DISTINCT delivery_address FROM orders WHERE user_id = ? AND delivery_address IS NOT NULL AND delivery_address != '' AND id != ? ORDER BY id DESC LIMIT 20");
$stmt->execute([$ownerId, $orderId]);
$deliveryHistory = $stmt->fetchAll(PDO::FETCH_COLUMN);

$stmt = $pdo->prepare("SELECT DISTINCT title FROM orders WHERE user_id = ? AND title IS NOT NULL AND title != '' AND id != ? ORDER BY id DESC LIMIT 15");
$stmt->execute([$ownerId, $orderId]);
$titleHistory = $stmt->fetchAll(PDO::FETCH_COLUMN);

$cargoOptions = ['Стройматериалы','Продукты питания','Оборудование','Мебель','Металлопрокат','Химия','Бытовая техника','Пиломатериалы','Зерно / с/х','Сборный груз','Другое'];
?>

<style>
    .edit-wrap { max-width: 1000px; margin: 0 auto; }
    .edit-form { background: var(--surface); border: 1px solid var(--border); padding: 28px; }
    .edit-form h2 { font-size: 22px; font-weight: 800; letter-spacing: -0.5px; margin-bottom: 8px; }
    .edit-form .subtitle { font-size: 13px; color: var(--text-secondary); margin-bottom: 28px; }
    .form-section { margin-bottom: 28px; padding-bottom: 24px; border-bottom: 1px solid var(--border-light); }
    .form-section:last-of-type { border-bottom: none; padding-bottom: 0; }
    .section-title { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px; color: var(--text-secondary); margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
    .section-title .num { width: 22px; height: 22px; background: var(--accent); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 800; border-radius: 50%; }
    .field { margin-bottom: 16px; }
    .field:last-child { margin-bottom: 0; }
    .field label { display: block; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: var(--text-secondary); margin-bottom: 8px; }
    .field label .required { color: var(--danger); }
    .field label .hint { float: right; font-weight: 500; text-transform: none; letter-spacing: 0; color: var(--text-secondary); opacity: 0.7; }
    .field input, .field select, .field textarea { width: 100%; padding: 14px 16px; border: 2px solid var(--border); font-size: 15px; font-family: inherit; background: var(--border-light); transition: all 0.2s; color: var(--text); }
    .field input:focus, .field select:focus, .field textarea:focus { outline: none; border-color: var(--primary); background: var(--surface); }
    .field textarea { resize: vertical; min-height: 80px; }
    .grid-2col { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .input-icon { position: relative; }
    .input-icon .icon { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); font-size: 18px; pointer-events: none; }
    .input-icon input { padding-left: 48px; }
    .checks-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 16px; }
    .check-item { display: flex; align-items: center; gap: 10px; padding: 12px 14px; background: var(--border-light); border: 2px solid transparent; cursor: pointer; transition: all 0.2s; font-size: 13px; font-weight: 600; }
    .check-item:hover { border-color: var(--border); }
    .check-item input[type="checkbox"] { width: 18px; height: 18px; accent-color: var(--accent); cursor: pointer; flex-shrink: 0; }
    .check-item span { color: var(--text-secondary); }
    .check-item input[type="checkbox"]:checked ~ span { color: var(--text); }
    .actions-bar { display: flex; gap: 12px; justify-content: space-between; align-items: center; margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border-light); flex-wrap: wrap; }
    .btn-save { padding: 14px 32px; font-size: 14px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; background: var(--success); color: white; border: none; cursor: pointer; font-family: inherit; transition: all 0.2s; }
    .btn-save:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(16, 185, 129, 0.3); }
    .btn-cancel { padding: 14px 24px; font-size: 13px; font-weight: 700; background: transparent; color: var(--text-secondary); border: 2px solid var(--border); cursor: pointer; text-decoration: none; font-family: inherit; transition: all 0.2s; }
    .btn-cancel:hover { border-color: var(--text); color: var(--text); }
    @media (max-width: 900px) {
        .grid-2col, .checks-grid { grid-template-columns: 1fr; }
        .edit-form { padding: 20px; }
    }
</style>

<script src="https://unpkg.com/imask@7.6.1/dist/imask.min.js"></script>

<div style="font-size:13px; color:var(--text-secondary); margin-bottom:16px;">
    <a href="/orders/my" style="color:var(--text-secondary); text-decoration:none;">📋 Мои заказы</a>
    <span style="opacity:0.4;"> / </span>
    <a href="/orders/view/<?= $orderId ?>" style="color:var(--text-secondary); text-decoration:none;">Заказ #<?= $orderId ?></a>
    <span style="opacity:0.4;"> / </span>
    <span style="color:var(--text); font-weight:600;">Редактирование</span>
</div>

<div class="edit-wrap">
    <form method="POST" action="/orders/edit/<?= $orderId ?>" class="edit-form">
        <h2>✏️ Редактировать заказ #<?= $orderId ?></h2>
        <p class="subtitle">Изменения увидят перевозчики в ленте заказов</p>
        
        <div class="form-section">
            <div class="section-title"><span class="num">1</span><span>О грузе</span></div>
            
            <div class="field">
                <label>Название заказа <span class="required">*</span></label>
                <input type="text" name="title" required maxlength="100" list="titleHistory"
                       value="<?= htmlspecialchars($order['title']) ?>">
                <datalist id="titleHistory">
                    <?php foreach ($titleHistory as $t): ?>
                        <option value="<?= htmlspecialchars($t) ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>
            
            <div class="grid-2col">
                <div class="field">
                    <label>Тип груза <span class="required">*</span></label>
                    <select name="cargo_type" required>
                        <?php foreach ($cargoOptions as $opt): ?>
                            <option value="<?= $opt ?>" <?= $order['cargo_type'] === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label>Требуемый тип ТС</label>
                    <select name="vehicle_type">
                        <option value="">Не важно</option>
                        <option value="тент" <?= ($order['vehicle_type'] ?? '') === 'тент' ? 'selected' : '' ?>>Тент</option>
                        <option value="реф" <?= ($order['vehicle_type'] ?? '') === 'реф' ? 'selected' : '' ?>>Рефрижератор</option>
                        <option value="борт" <?= ($order['vehicle_type'] ?? '') === 'борт' ? 'selected' : '' ?>>Бортовой</option>
                        <option value="изотерм" <?= ($order['vehicle_type'] ?? '') === 'изотерм' ? 'selected' : '' ?>>Изотерм</option>
                        <option value="цистерна" <?= ($order['vehicle_type'] ?? '') === 'цистерна' ? 'selected' : '' ?>>Цистерна</option>
                        <option value="самосвал" <?= ($order['vehicle_type'] ?? '') === 'самосвал' ? 'selected' : '' ?>>Самосвал</option>
                    </select>
                </div>
            </div>
            
            <div class="grid-2col">
                <div class="field">
                    <label>Вес <span class="hint">тонн</span> <span class="required">*</span></label>
                    <input type="number" name="weight" required step="0.1" min="0.1" max="40"
                           value="<?= htmlspecialchars($order['weight']) ?>">
                </div>
                <div class="field">
                    <label>Объём <span class="hint">м³</span> <span class="required">*</span></label>
                    <input type="number" name="volume" required step="0.1" min="0.1"
                           value="<?= htmlspecialchars($order['volume']) ?>">
                </div>
            </div>
            
            <div class="field">
                <label>Бюджет <span class="hint">₽</span> <span class="required">*</span></label>
                <input type="number" name="price" required min="1000" step="500"
                       value="<?= (int)$order['price'] ?>">
            </div>
        </div>
        
        <div class="form-section">
            <div class="section-title"><span class="num">2</span><span>Маршрут</span></div>
            
            <div class="field">
                <label>📍 Адрес погрузки <span class="required">*</span></label>
                <div class="input-icon">
                    <span class="icon">📍</span>
                    <input type="text" name="pickup_address" required maxlength="200" list="pickupHistory"
                           value="<?= htmlspecialchars($order['pickup_address']) ?>">
                </div>
                <datalist id="pickupHistory">
                    <?php foreach ($pickupHistory as $a): ?>
                        <option value="<?= htmlspecialchars($a) ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>
            
            <div class="grid-2col">
                <div class="field">
                    <label>Контактное лицо</label>
                    <input type="text" name="pickup_contact_name" maxlength="100"
                           value="<?= htmlspecialchars($order['pickup_contact_name'] ?? '') ?>">
                </div>
                <div class="field">
                    <label>Телефон</label>
                    <input type="tel" name="pickup_contact_phone" maxlength="20" id="pickup_phone"
                           value="<?= htmlspecialchars($order['pickup_contact_phone'] ?? '') ?>">
                </div>
            </div>
            
            <div class="field">
                <label>🏁 Адрес выгрузки <span class="required">*</span></label>
                <div class="input-icon">
                    <span class="icon">🏁</span>
                    <input type="text" name="delivery_address" required maxlength="200" list="deliveryHistory"
                           value="<?= htmlspecialchars($order['delivery_address']) ?>">
                </div>
                <datalist id="deliveryHistory">
                    <?php foreach ($deliveryHistory as $a): ?>
                        <option value="<?= htmlspecialchars($a) ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>
            
            <div class="grid-2col">
                <div class="field">
                    <label>Контактное лицо</label>
                    <input type="text" name="delivery_contact_name" maxlength="100"
                           value="<?= htmlspecialchars($order['delivery_contact_name'] ?? '') ?>">
                </div>
                <div class="field">
                    <label>Телефон</label>
                    <input type="tel" name="delivery_contact_phone" maxlength="20" id="delivery_phone"
                           value="<?= htmlspecialchars($order['delivery_contact_phone'] ?? '') ?>">
                </div>
            </div>
        </div>
        
        <div class="form-section">
            <div class="section-title"><span class="num">3</span><span>Даты и время</span></div>
            
            <div class="grid-2col">
                <div class="field">
                    <label>📅 Дата погрузки <span class="required">*</span></label>
                    <input type="date" name="pickup_date" required min="<?= date('Y-m-d') ?>"
                           value="<?= htmlspecialchars($order['pickup_date']) ?>">
                </div>
                <div class="field">
                    <label>🕐 Окно погрузки</label>
                    <select name="pickup_time_window" id="pickup_time_window" onchange="toggleExact('pickup')">
                        <option value="">Не указано</option>
                        <option value="morning" <?= $order['pickup_time_window'] === 'morning' ? 'selected' : '' ?>>Утро (8:00–12:00)</option>
                        <option value="day" <?= $order['pickup_time_window'] === 'day' ? 'selected' : '' ?>>День (12:00–17:00)</option>
                        <option value="evening" <?= $order['pickup_time_window'] === 'evening' ? 'selected' : '' ?>>Вечер (17:00–21:00)</option>
                        <option value="exact" <?= $order['pickup_time_window'] === 'exact' ? 'selected' : '' ?>>Точное время</option>
                    </select>
                    <input type="time" name="pickup_time_exact" id="pickup_time_exact"
                           style="margin-top:8px; <?= $order['pickup_time_window'] === 'exact' ? '' : 'display:none;' ?>"
                           value="<?= htmlspecialchars($order['pickup_time_exact'] ?? '') ?>">
                </div>
            </div>
            
            <div class="grid-2col">
                <div class="field">
                    <label>📅 Дата доставки <span class="required">*</span></label>
                    <input type="date" name="delivery_date" required min="<?= date('Y-m-d') ?>"
                           value="<?= htmlspecialchars($order['delivery_date']) ?>">
                </div>
                <div class="field">
                    <label>🕐 Окно выгрузки</label>
                    <select name="delivery_time_window" id="delivery_time_window" onchange="toggleExact('delivery')">
                        <option value="">Не указано</option>
                        <option value="morning" <?= $order['delivery_time_window'] === 'morning' ? 'selected' : '' ?>>Утро (8:00–12:00)</option>
                        <option value="day" <?= $order['delivery_time_window'] === 'day' ? 'selected' : '' ?>>День (12:00–17:00)</option>
                        <option value="evening" <?= $order['delivery_time_window'] === 'evening' ? 'selected' : '' ?>>Вечер (17:00–21:00)</option>
                        <option value="exact" <?= $order['delivery_time_window'] === 'exact' ? 'selected' : '' ?>>Точное время</option>
                    </select>
                    <input type="time" name="delivery_time_exact" id="delivery_time_exact"
                           style="margin-top:8px; <?= $order['delivery_time_window'] === 'exact' ? '' : 'display:none;' ?>"
                           value="<?= htmlspecialchars($order['delivery_time_exact'] ?? '') ?>">
                </div>
            </div>
        </div>
        
        <div class="form-section">
            <div class="section-title"><span class="num">4</span><span>Особые условия</span></div>
            
            <div class="checks-grid">
                <label class="check-item">
                    <input type="checkbox" name="requires_pass" value="1" <?= !empty($order['requires_pass']) ? 'checked' : '' ?>>
                    <span>🚪 Требуется пропуск</span>
                </label>
                <label class="check-item">
                    <input type="checkbox" name="unload_on_floor" value="1" <?= !empty($order['unload_on_floor']) ? 'checked' : '' ?>>
                    <span>🏢 Разгрузка на этаже</span>
                </label>
                <label class="check-item">
                    <input type="checkbox" name="needs_manipulator" value="1" <?= !empty($order['needs_manipulator']) ? 'checked' : '' ?>>
                    <span>🏗️ Нужен манипулятор</span>
                </label>
                <label class="check-item">
                    <input type="checkbox" name="is_fragile" value="1" <?= !empty($order['is_fragile']) ? 'checked' : '' ?>>
                    <span>⚠️ Хрупкий груз</span>
                </label>
            </div>
            
            <div class="field">
                <label>💬 Комментарий</label>
                <textarea name="special_comment" maxlength="1000"><?= htmlspecialchars($order['special_comment'] ?? '') ?></textarea>
            </div>
        </div>
        
        <div class="actions-bar">
            <a href="/orders/view/<?= $orderId ?>" class="btn-cancel">← Отмена</a>
            <button type="submit" class="btn-save">💾 Сохранить</button>
        </div>
    </form>
</div>

<script>
function toggleExact(prefix) {
    var select = document.getElementById(prefix + '_time_window');
    var input = document.getElementById(prefix + '_time_exact');
    if (select.value === 'exact') {
        input.style.display = 'block';
    } else {
        input.style.display = 'none';
        input.value = '';
    }
}

document.querySelector('form').addEventListener('submit', function() {
    var btn = document.querySelector('.btn-save');
    btn.disabled = true;
    btn.innerHTML = '⏳ Сохраняем...';
});

document.addEventListener('DOMContentLoaded', function() {
    if (typeof IMask !== 'undefined') {
        ['pickup_phone', 'delivery_phone'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el) {
                IMask(el, {
                    mask: '+{7} (000) 000-00-00',
                    lazy: false,
                    placeholderChar: '_'
                });
            }
        });
    }
});
</script>

<?php require_once BASE_PATH . '/templates/footer.php'; ?>