<?php require_once BASE_PATH . '/templates/header.php'; 

global $pdo;
$ownerId = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT DISTINCT pickup_address FROM orders WHERE user_id = ? AND pickup_address IS NOT NULL AND pickup_address != '' ORDER BY id DESC LIMIT 20");
$stmt->execute([$ownerId]);
$pickupHistory = $stmt->fetchAll(PDO::FETCH_COLUMN);

$stmt = $pdo->prepare("SELECT DISTINCT delivery_address FROM orders WHERE user_id = ? AND delivery_address IS NOT NULL AND delivery_address != '' ORDER BY id DESC LIMIT 20");
$stmt->execute([$ownerId]);
$deliveryHistory = $stmt->fetchAll(PDO::FETCH_COLUMN);

$stmt = $pdo->prepare("SELECT DISTINCT title FROM orders WHERE user_id = ? AND title IS NOT NULL AND title != '' ORDER BY id DESC LIMIT 15");
$stmt->execute([$ownerId]);
$titleHistory = $stmt->fetchAll(PDO::FETCH_COLUMN);

$stmt = $pdo->prepare("SELECT phone FROM users WHERE id = ?");
$stmt->execute([$ownerId]);
$profilePhone = $stmt->fetchColumn() ?: '';
?>

<style>
    .create-wrap { display: grid; grid-template-columns: 1fr 340px; gap: 24px; align-items: start; }
    .create-form { background: var(--surface); border: 1px solid var(--border); padding: 28px; }
    .create-form h2 { font-size: 22px; font-weight: 800; letter-spacing: -0.5px; margin-bottom: 8px; }
    .create-form .subtitle { font-size: 13px; color: var(--text-secondary); margin-bottom: 28px; }
    .form-section { margin-bottom: 28px; padding-bottom: 24px; border-bottom: 1px solid var(--border-light); }
    .form-section:last-of-type { border-bottom: none; margin-bottom: 20px; padding-bottom: 0; }
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
    .field-hint { font-size: 11px; color: var(--text-secondary); margin-top: 6px; opacity: 0.8; }
    .grid-2col { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .grid-3col { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; }
    .input-icon { position: relative; }
    .input-icon .icon { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); font-size: 18px; pointer-events: none; }
    .input-icon input { padding-left: 48px; }
    .date-shortcuts { display: flex; gap: 6px; margin-bottom: 8px; flex-wrap: wrap; }
    .date-shortcut { padding: 6px 12px; font-size: 12px; font-weight: 700; background: var(--border-light); border: 1px solid var(--border); color: var(--text-secondary); cursor: pointer; transition: all 0.2s; font-family: inherit; }
    .date-shortcut:hover { background: var(--accent); color: var(--primary); border-color: var(--accent); }
    .contacts-toggle { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; color: var(--text-secondary); cursor: pointer; background: none; border: none; padding: 6px 0; font-family: inherit; font-weight: 600; transition: color 0.2s; }
    .contacts-toggle:hover { color: var(--text); }
    .contacts-toggle .arrow { transition: transform 0.2s; display: inline-block; }
    .contacts-toggle.open .arrow { transform: rotate(90deg); }
    .contacts-block { display: none; margin-top: 12px; padding: 16px; background: var(--border-light); border-left: 3px solid var(--accent); }
    .contacts-block.open { display: block; }
    .checks-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 16px; }
    .check-item { display: flex; align-items: center; gap: 10px; padding: 12px 14px; background: var(--border-light); border: 2px solid transparent; cursor: pointer; transition: all 0.2s; font-size: 13px; font-weight: 600; }
    .check-item:hover { border-color: var(--border); }
    .check-item input[type="checkbox"] { width: 18px; height: 18px; accent-color: var(--accent); cursor: pointer; flex-shrink: 0; }
    .check-item span { color: var(--text-secondary); }
    .check-item input[type="checkbox"]:checked ~ span { color: var(--text); }
    .submit-btn { width: 100%; padding: 18px 24px; font-size: 15px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; background: var(--success); color: white; border: none; cursor: pointer; transition: all 0.25s; display: flex; align-items: center; justify-content: center; gap: 10px; font-family: inherit; }
    .submit-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(16, 185, 129, 0.3); }
    .submit-btn:disabled { opacity: 0.6; cursor: wait; transform: none; }
    .submit-hint { text-align: center; font-size: 12px; color: var(--text-secondary); margin-top: 12px; }
    .create-side { display: flex; flex-direction: column; gap: 16px; position: sticky; top: 90px; }
    .side-panel { background: var(--surface); border: 1px solid var(--border); padding: 20px; }
    .side-panel-title { font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px; color: var(--text-secondary); margin-bottom: 14px; }
    .steps-list { display: flex; flex-direction: column; gap: 14px; }
    .step-item { display: flex; gap: 12px; align-items: flex-start; }
    .step-icon { width: 32px; height: 32px; background: var(--accent); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0; border-radius: 50%; }
    .step-text { font-size: 13px; line-height: 1.5; }
    .step-text strong { display: block; font-weight: 800; font-size: 13px; margin-bottom: 2px; }
    .step-text span { color: var(--text-secondary); font-size: 12px; }
    .preview-panel { background: linear-gradient(135deg, var(--accent), #d97706); color: var(--primary); padding: 20px; }
    .preview-panel .title { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 12px; opacity: 0.8; }
    .preview-panel .preview-route { font-size: 15px; font-weight: 800; margin-bottom: 6px; }
    .preview-panel .preview-meta { font-size: 12px; opacity: 0.9; }
    .preview-panel .preview-time { font-size: 12px; opacity: 0.9; margin-top: 4px; }
    .preview-panel .preview-price { font-size: 24px; font-weight: 900; font-family: var(--font-mono); margin-top: 12px; letter-spacing: -1px; }
    .preview-panel .preview-badges { display: flex; flex-wrap: wrap; gap: 4px; margin-top: 10px; }
    .preview-badge { background: rgba(0,0,0,0.15); padding: 2px 8px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
    .guarantees { display: flex; flex-direction: column; gap: 10px; }
    .guarantee-item { display: flex; align-items: center; gap: 8px; font-size: 12px; color: var(--text-secondary); }
    .guarantee-item .check { color: var(--success); font-weight: 800; font-size: 14px; }
    @media (max-width: 900px) {
        .create-wrap { grid-template-columns: 1fr; }
        .create-side { position: static; order: -1; }
        .grid-2col, .grid-3col, .checks-grid { grid-template-columns: 1fr; }
        .create-form { padding: 20px; }
    }
</style>

<script src="https://unpkg.com/imask@7.6.1/dist/imask.min.js"></script>

<div style="font-size:13px; color:var(--text-secondary); margin-bottom:16px;">
    <a href="/orders" style="color:var(--text-secondary); text-decoration:none;">📋 Заказы</a>
    <span style="opacity:0.4;"> / </span>
    <span style="color:var(--text); font-weight:600;">Новый заказ</span>
</div>

<div class="create-wrap">
    
    <form method="POST" action="/orders/create" class="create-form" id="orderForm">
        <h2>📦 Создать заказ на перевозку</h2>
        <p class="subtitle">Заполните форму — перевозчики откликнутся с ценами</p>
        
        <div class="form-section">
            <div class="section-title"><span class="num">1</span><span>О грузе</span></div>
            
            <div class="field">
                <label>Название заказа <span class="required">*</span></label>
                <input type="text" name="title" required maxlength="100" list="titleHistory"
                       placeholder="Например: Перевозка стройматериалов" 
                       value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
                <datalist id="titleHistory">
                    <?php foreach ($titleHistory as $t): ?>
                        <option value="<?= htmlspecialchars($t) ?>">
                    <?php endforeach; ?>
                </datalist>
                <div class="field-hint">💡 Краткое описание — его увидят перевозчики в списке</div>
            </div>
            
            <div class="grid-2col">
                <div class="field">
                    <label>Тип груза <span class="required">*</span></label>
                    <select name="cargo_type" required>
                        <option value="">Выберите...</option>
                        <option value="Стройматериалы">Стройматериалы</option>
                        <option value="Продукты питания">Продукты питания</option>
                        <option value="Оборудование">Оборудование</option>
                        <option value="Мебель">Мебель</option>
                        <option value="Металлопрокат">Металлопрокат</option>
                        <option value="Химия">Химия</option>
                        <option value="Бытовая техника">Бытовая техника</option>
                        <option value="Пиломатериалы">Пиломатериалы</option>
                        <option value="Зерно / с/х">Зерно / с/х</option>
                        <option value="Сборный груз">Сборный груз</option>
                        <option value="Другое">Другое</option>
                    </select>
                </div>
                
                <div class="field">
                    <label>Требуемый тип ТС</label>
                    <select name="vehicle_type">
                        <option value="">Не важно</option>
                        <option value="тент" <?= ($_POST['vehicle_type'] ?? '') === 'тент' ? 'selected' : '' ?>>Тент</option>
                        <option value="реф" <?= ($_POST['vehicle_type'] ?? '') === 'реф' ? 'selected' : '' ?>>Рефрижератор</option>
                        <option value="борт" <?= ($_POST['vehicle_type'] ?? '') === 'борт' ? 'selected' : '' ?>>Бортовой</option>
                        <option value="изотерм" <?= ($_POST['vehicle_type'] ?? '') === 'изотерм' ? 'selected' : '' ?>>Изотерм</option>
                        <option value="цистерна" <?= ($_POST['vehicle_type'] ?? '') === 'цистерна' ? 'selected' : '' ?>>Цистерна</option>
                        <option value="самосвал" <?= ($_POST['vehicle_type'] ?? '') === 'самосвал' ? 'selected' : '' ?>>Самосвал</option>
                    </select>
                    <div class="field-hint">💡 Не обязательно, но ускорит отклики</div>
                </div>
            </div>
            
            <div class="grid-3col">
                <div class="field">
                    <label>Вес <span class="hint">тонн</span> <span class="required">*</span></label>
                    <input type="number" name="weight" required step="0.1" min="0.1" max="40"
                           placeholder="20" value="<?= htmlspecialchars($_POST['weight'] ?? '') ?>">
                </div>
                
                <div class="field">
                    <label>Объём <span class="hint">м³</span> <span class="required">*</span></label>
                    <input type="number" name="volume" required step="0.1" min="0.1"
                           placeholder="40" value="<?= htmlspecialchars($_POST['volume'] ?? '') ?>">
                </div>
                
                <div class="field">
                    <label>Бюджет <span class="hint">₽</span> <span class="required">*</span></label>
                    <input type="number" name="price" required min="1000" step="500"
                           placeholder="50000" value="<?= htmlspecialchars($_POST['price'] ?? '') ?>">
                </div>
            </div>
        </div>
        
        <div class="form-section">
            <div class="section-title"><span class="num">2</span><span>Маршрут</span></div>
            
            <div class="field">
                <label>📍 Адрес погрузки <span class="required">*</span></label>
                <div class="input-icon">
                    <span class="icon">📍</span>
                    <input type="text" name="pickup_address" required maxlength="200" list="pickupHistory"
                           placeholder="Москва, Ленинградское шоссе, 15"
                           value="<?= htmlspecialchars($_POST['pickup_address'] ?? '') ?>">
                </div>
                <datalist id="pickupHistory">
                    <?php foreach ($pickupHistory as $a): ?>
                        <option value="<?= htmlspecialchars($a) ?>">
                    <?php endforeach; ?>
                </datalist>
                <div class="field-hint">💡 Начните вводить — появятся прошлые адреса</div>
                
                <button type="button" class="contacts-toggle" onclick="toggleContacts('pickupContacts', this)">
                    <span class="arrow">▸</span> Контакты на погрузке (опционально)
                </button>
                <div class="contacts-block" id="pickupContacts">
                    <div class="grid-2col">
                        <div class="field" style="margin-bottom:0;">
                            <label>Контактное лицо</label>
                            <input type="text" name="pickup_contact_name" maxlength="100"
                                   placeholder="Иван Петров"
                                   value="<?= htmlspecialchars($_POST['pickup_contact_name'] ?? '') ?>">
                        </div>
                        <div class="field" style="margin-bottom:0;">
                            <label>Телефон</label>
                            <input type="tel" name="pickup_contact_phone" maxlength="20" id="pickup_phone"
                                   placeholder="+7 (999) 123-45-67"
                                   value="<?= htmlspecialchars($_POST['pickup_contact_phone'] ?? $profilePhone) ?>">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="field">
                <label>🏁 Адрес выгрузки <span class="required">*</span></label>
                <div class="input-icon">
                    <span class="icon">🏁</span>
                    <input type="text" name="delivery_address" required maxlength="200" list="deliveryHistory"
                           placeholder="Казань, ул. Промышленная, 5"
                           value="<?= htmlspecialchars($_POST['delivery_address'] ?? '') ?>">
                </div>
                <datalist id="deliveryHistory">
                    <?php foreach ($deliveryHistory as $a): ?>
                        <option value="<?= htmlspecialchars($a) ?>">
                    <?php endforeach; ?>
                </datalist>
                
                <button type="button" class="contacts-toggle" onclick="toggleContacts('deliveryContacts', this)">
                    <span class="arrow">▸</span> Контакты на выгрузке (опционально)
                </button>
                <div class="contacts-block" id="deliveryContacts">
                    <div class="grid-2col">
                        <div class="field" style="margin-bottom:0;">
                            <label>Контактное лицо</label>
                            <input type="text" name="delivery_contact_name" maxlength="100"
                                   placeholder="Сергей Иванов"
                                   value="<?= htmlspecialchars($_POST['delivery_contact_name'] ?? '') ?>">
                        </div>
                        <div class="field" style="margin-bottom:0;">
                            <label>Телефон</label>
                            <input type="tel" name="delivery_contact_phone" maxlength="20" id="delivery_phone"
                                   placeholder="+7 (999) 123-45-67"
                                   value="<?= htmlspecialchars($_POST['delivery_contact_phone'] ?? '') ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="form-section">
            <div class="section-title"><span class="num">3</span><span>Даты и время</span></div>
            
            <div class="grid-2col">
                <div class="field">
                    <label>📅 Дата погрузки <span class="required">*</span></label>
                    <div class="date-shortcuts">
                        <button type="button" class="date-shortcut" onclick="setDate('pickup_date', 0)">Сегодня</button>
                        <button type="button" class="date-shortcut" onclick="setDate('pickup_date', 1)">Завтра</button>
                        <button type="button" class="date-shortcut" onclick="setDate('pickup_date', 3)">+3 дня</button>
                    </div>
                    <input type="date" name="pickup_date" id="pickup_date" required
                           min="<?= date('Y-m-d') ?>"
                           value="<?= htmlspecialchars($_POST['pickup_date'] ?? date('Y-m-d', strtotime('+1 day'))) ?>">
                </div>
                
                <div class="field">
                    <label>🕐 Окно погрузки</label>
                    <select name="pickup_time_window" id="pickup_time_window" onchange="toggleExactTime('pickup')">
                        <option value="">Не указано</option>
                        <option value="morning" <?= ($_POST['pickup_time_window'] ?? '') === 'morning' ? 'selected' : '' ?>>Утро (8:00–12:00)</option>
                        <option value="day" <?= ($_POST['pickup_time_window'] ?? '') === 'day' ? 'selected' : '' ?>>День (12:00–17:00)</option>
                        <option value="evening" <?= ($_POST['pickup_time_window'] ?? '') === 'evening' ? 'selected' : '' ?>>Вечер (17:00–21:00)</option>
                        <option value="exact" <?= ($_POST['pickup_time_window'] ?? '') === 'exact' ? 'selected' : '' ?>>Точное время</option>
                    </select>
                    <input type="time" name="pickup_time_exact" id="pickup_time_exact" 
                           style="margin-top:8px; display:none;"
                           value="<?= htmlspecialchars($_POST['pickup_time_exact'] ?? '') ?>">
                </div>
            </div>
            
            <div class="grid-2col">
                <div class="field">
                    <label>📅 Дата доставки <span class="required">*</span></label>
                    <div class="date-shortcuts">
                        <button type="button" class="date-shortcut" onclick="setDate('delivery_date', 1)">+1 день</button>
                        <button type="button" class="date-shortcut" onclick="setDate('delivery_date', 3)">+3 дня</button>
                        <button type="button" class="date-shortcut" onclick="setDate('delivery_date', 7)">+7 дней</button>
                    </div>
                    <input type="date" name="delivery_date" id="delivery_date" required
                           min="<?= date('Y-m-d') ?>"
                           value="<?= htmlspecialchars($_POST['delivery_date'] ?? date('Y-m-d', strtotime('+3 day'))) ?>">
                </div>
                
                <div class="field">
                    <label>🕐 Окно выгрузки</label>
                    <select name="delivery_time_window" id="delivery_time_window" onchange="toggleExactTime('delivery')">
                        <option value="">Не указано</option>
                        <option value="morning" <?= ($_POST['delivery_time_window'] ?? '') === 'morning' ? 'selected' : '' ?>>Утро (8:00–12:00)</option>
                        <option value="day" <?= ($_POST['delivery_time_window'] ?? '') === 'day' ? 'selected' : '' ?>>День (12:00–17:00)</option>
                        <option value="evening" <?= ($_POST['delivery_time_window'] ?? '') === 'evening' ? 'selected' : '' ?>>Вечер (17:00–21:00)</option>
                        <option value="exact" <?= ($_POST['delivery_time_window'] ?? '') === 'exact' ? 'selected' : '' ?>>Точное время</option>
                    </select>
                    <input type="time" name="delivery_time_exact" id="delivery_time_exact" 
                           style="margin-top:8px; display:none;"
                           value="<?= htmlspecialchars($_POST['delivery_time_exact'] ?? '') ?>">
                </div>
            </div>
        </div>
        
        <div class="form-section">
            <div class="section-title"><span class="num">4</span><span>Особые условия</span></div>
            
            <div class="checks-grid">
                <label class="check-item">
                    <input type="checkbox" name="requires_pass" value="1" <?= !empty($_POST['requires_pass']) ? 'checked' : '' ?>>
                    <span>🚪 Требуется пропуск</span>
                </label>
                <label class="check-item">
                    <input type="checkbox" name="unload_on_floor" value="1" <?= !empty($_POST['unload_on_floor']) ? 'checked' : '' ?>>
                    <span>🏢 Разгрузка на этаже</span>
                </label>
                <label class="check-item">
                    <input type="checkbox" name="needs_manipulator" value="1" <?= !empty($_POST['needs_manipulator']) ? 'checked' : '' ?>>
                    <span>🏗️ Нужен манипулятор</span>
                </label>
                <label class="check-item">
                    <input type="checkbox" name="is_fragile" value="1" <?= !empty($_POST['is_fragile']) ? 'checked' : '' ?>>
                    <span>⚠️ Хрупкий груз</span>
                </label>
            </div>
            
            <div class="field">
                <label>💬 Комментарий для перевозчика</label>
                <textarea name="special_comment" maxlength="1000"
                          placeholder="Например: Погрузка со двора, нужен пропуск на территорию..."><?= htmlspecialchars($_POST['special_comment'] ?? '') ?></textarea>
                <div class="field-hint">💡 До 1000 символов</div>
            </div>
        </div>
        
        <button type="submit" class="submit-btn">🚀 Опубликовать заказ бесплатно</button>
        <div class="submit-hint">Заказ увидят <strong>60+ перевозчиков</strong>. Первые отклики — за 10 минут.</div>
    </form>
    
    <aside class="create-side">
        <div class="preview-panel">
            <div class="title">👁 Так увидят перевозчики</div>
            <div class="preview-route" id="previewRoute">Откуда → Куда</div>
            <div class="preview-meta" id="previewMeta">Характеристики груза</div>
            <div class="preview-time" id="previewTime"></div>
            <div class="preview-price" id="previewPrice">— ₽</div>
            <div class="preview-badges" id="previewBadges"></div>
        </div>
        
        <div class="side-panel">
            <div class="side-panel-title">📋 Что дальше?</div>
            <div class="steps-list">
                <div class="step-item"><div class="step-icon">1</div><div class="step-text"><strong>Публикуете заказ</strong><span>Мгновенно — заявка уходит перевозчикам</span></div></div>
                <div class="step-item"><div class="step-icon">2</div><div class="step-text"><strong>Получаете отклики</strong><span>С ценами и рейтингами — за 10-60 минут</span></div></div>
                <div class="step-item"><div class="step-icon">3</div><div class="step-text"><strong>Выбираете перевозчика</strong><span>По цене, рейтингу или срокам</span></div></div>
                <div class="step-item"><div class="step-icon">4</div><div class="step-text"><strong>Контролируете перевозку</strong><span>Статусы, чат, документы — онлайн</span></div></div>
            </div>
        </div>
        
        <div class="side-panel">
            <div class="side-panel-title">🛡️ Гарантии</div>
            <div class="guarantees">
                <div class="guarantee-item"><span class="check">✓</span> Размещение бесплатно</div>
                <div class="guarantee-item"><span class="check">✓</span> Отклики за 10 минут</div>
                <div class="guarantee-item"><span class="check">✓</span> Договор и ТН автоматом</div>
                <div class="guarantee-item"><span class="check">✓</span> Никаких посредников</div>
                <div class="guarantee-item"><span class="check">✓</span> Проверенные перевозчики</div>
            </div>
        </div>
    </aside>
</div>

<script>
function setDate(fieldId, daysFromToday) {
    var d = new Date();
    d.setDate(d.getDate() + daysFromToday);
    var yyyy = d.getFullYear();
    var mm = String(d.getMonth() + 1).padStart(2, '0');
    var dd = String(d.getDate()).padStart(2, '0');
    document.getElementById(fieldId).value = yyyy + '-' + mm + '-' + dd;
    updatePreview();
}

function toggleContacts(blockId, btn) {
    var block = document.getElementById(blockId);
    block.classList.toggle('open');
    btn.classList.toggle('open');
}

function toggleExactTime(prefix) {
    var select = document.getElementById(prefix + '_time_window');
    var input = document.getElementById(prefix + '_time_exact');
    if (select.value === 'exact') {
        input.style.display = 'block';
        input.required = true;
    } else {
        input.style.display = 'none';
        input.required = false;
        input.value = '';
    }
    updatePreview();
}

function updatePreview() {
    var pickup = document.querySelector('[name="pickup_address"]').value;
    var delivery = document.querySelector('[name="delivery_address"]').value;
    var cargo = document.querySelector('[name="cargo_type"]').value;
    var vehicle = document.querySelector('[name="vehicle_type"]').value;
    var weight = document.querySelector('[name="weight"]').value;
    var volume = document.querySelector('[name="volume"]').value;
    var price = document.querySelector('[name="price"]').value;
    var pickupDate = document.querySelector('[name="pickup_date"]').value;
    var deliveryDate = document.querySelector('[name="delivery_date"]').value;
    var pickupWindow = document.getElementById('pickup_time_window').value;
    var deliveryWindow = document.getElementById('delivery_time_window').value;
    
    var pickupCity = pickup.split(',')[0].trim() || 'Откуда';
    var deliveryCity = delivery.split(',')[0].trim() || 'Куда';
    document.getElementById('previewRoute').textContent = pickupCity + ' → ' + deliveryCity;
    
    var metaParts = [];
    if (cargo) metaParts.push(cargo);
    if (vehicle) metaParts.push('🚛 ' + vehicle);
    if (weight) metaParts.push(weight + ' т');
    if (volume) metaParts.push(volume + ' м³');
    document.getElementById('previewMeta').textContent = metaParts.length ? metaParts.join(' · ') : 'Характеристики груза';
    
    var timeParts = [];
    var windowLabels = { morning: 'Утро', day: 'День', evening: 'Вечер', exact: 'Точно' };
    if (pickupDate) {
        var pd = new Date(pickupDate);
        var pStr = pd.toLocaleDateString('ru-RU', { day: 'numeric', month: 'short' });
        if (pickupWindow) pStr += ', ' + windowLabels[pickupWindow];
        timeParts.push('📍 ' + pStr);
    }
    if (deliveryDate) {
        var dd = new Date(deliveryDate);
        var dStr = dd.toLocaleDateString('ru-RU', { day: 'numeric', month: 'short' });
        if (deliveryWindow) dStr += ', ' + windowLabels[deliveryWindow];
        timeParts.push('🏁 ' + dStr);
    }
    document.getElementById('previewTime').textContent = timeParts.join('  ·  ');
    
    if (price) {
        document.getElementById('previewPrice').textContent = parseInt(price).toLocaleString('ru-RU') + ' ₽';
    } else {
        document.getElementById('previewPrice').textContent = '— ₽';
    }
    
    var badges = [];
    if (document.querySelector('[name="requires_pass"]').checked) badges.push('🚪 Пропуск');
    if (document.querySelector('[name="unload_on_floor"]').checked) badges.push('🏢 Этаж');
    if (document.querySelector('[name="needs_manipulator"]').checked) badges.push('🏗️ Манипулятор');
    if (document.querySelector('[name="is_fragile"]').checked) badges.push('⚠️ Хрупкое');
    
    var badgesEl = document.getElementById('previewBadges');
    badgesEl.innerHTML = '';
    badges.forEach(function(b) {
        var span = document.createElement('span');
        span.className = 'preview-badge';
        span.textContent = b;
        badgesEl.appendChild(span);
    });
}

document.querySelectorAll('#orderForm input, #orderForm select, #orderForm textarea').forEach(function(el) {
    el.addEventListener('input', updatePreview);
    el.addEventListener('change', updatePreview);
});

updatePreview();

document.getElementById('orderForm').addEventListener('submit', function(e) {
    var pickup = new Date(document.getElementById('pickup_date').value);
    var delivery = new Date(document.getElementById('delivery_date').value);
    if (delivery < pickup) {
        e.preventDefault();
        alert('Дата доставки не может быть раньше даты погрузки');
        return false;
    }
    var btn = document.querySelector('.submit-btn');
    btn.disabled = true;
    btn.innerHTML = '⏳ Публикуем заказ...';
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