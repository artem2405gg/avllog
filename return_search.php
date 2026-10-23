<?php require_once BASE_PATH . '/templates/header.php'; ?>

<div style="font-size:13px; color:var(--text-secondary); margin-bottom:16px;">
    <a href="/orders" style="color:var(--text-secondary); text-decoration:none;">📋 Заказы</a>
    <span style="opacity:0.4;"> / </span>
    <span style="color:var(--text); font-weight:600;">Обратные рейсы</span>
</div>

<h1>🔄 Обратные рейсы</h1>
<p class="text-muted" style="margin-bottom:24px; max-width:700px;">
    Укажите, где сейчас ваша машина — система найдёт заказы рядом, 
    чтобы не ехать пустым. Поиск живёт до конца выбранного периода.
</p>

<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-error"><?= htmlspecialchars($_SESSION['error']) ?></div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (!empty($activeSearch)): ?>
    
    <!-- Активный поиск -->
    <div class="card" style="border-left:4px solid var(--success);">
        <div style="display:flex; justify-content:space-between; align-items:start; gap:16px; flex-wrap:wrap; margin-bottom:16px;">
            <div>
                <div style="font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:1.5px; color:var(--success); margin-bottom:8px;">
                    ✅ Активный поиск
                </div>
                <h2 style="margin:0 0 8px;"><?= htmlspecialchars($activeSearch['city']) ?></h2>
                <div class="text-muted" style="font-size:13px;">
                    📅 <?= date('d.m.Y', strtotime($activeSearch['date_from'])) ?> — <?= date('d.m.Y', strtotime($activeSearch['date_to'])) ?>
                    <?php if ($activeSearch['capacity_tons']): ?>
                        · ⚖️ до <?= $activeSearch['capacity_tons'] ?> т
                    <?php endif; ?>
                    <?php if ($activeSearch['vehicle_type']): ?>
                        · 🚛 <?= htmlspecialchars($activeSearch['vehicle_type']) ?>
                    <?php endif; ?>
                </div>
            </div>
            <div style="text-align:right;">
                <div style="font-size:11px; color:var(--text-secondary); text-transform:uppercase; letter-spacing:1px; font-weight:700; margin-bottom:4px;">
                    Просмотров
                </div>
                <div style="font-size:32px; font-weight:900; font-family:var(--font-mono); line-height:1;">
                    <?= (int)$activeSearch['views_count'] ?>
                </div>
            </div>
        </div>
        
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <a href="/orders/return/<?= $activeSearch['id'] ?>" class="btn btn-accent">
                🔍 Смотреть заказы
            </a>
            <a href="/orders/return_cancel/<?= $activeSearch['id'] ?>" 
               class="btn btn-outline" 
               onclick="return confirm('Отменить поиск?');">
                Отменить
            </a>
        </div>
    </div>
    
<?php else: ?>

    <!-- Форма создания поиска -->
    <form method="POST" action="/orders/return" class="card" style="max-width:720px;">
        <h2 style="margin-bottom:8px;">🔍 Новый поиск</h2>
        <p class="text-muted" style="margin-bottom:24px; font-size:13px;">
            Чем точнее укажете параметры — тем релевантнее будут заказы.
        </p>
        
        <!-- Город -->
        <div class="form-group">
            <label>Где сейчас машина *</label>
            <input type="text" name="city" required maxlength="100"
                   placeholder="Например: Казань"
                   list="citySuggest" autofocus>
            <datalist id="citySuggest">
                <?php
                global $pdo;
                $cities = $pdo->query("SELECT name FROM cities_coords ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
                foreach ($cities as $c): ?>
                    <option value="<?= htmlspecialchars($c) ?>">
                <?php endforeach; ?>
            </datalist>
        </div>
        
        <!-- Даты -->
        <div class="grid-2">
            <div class="form-group">
                <label>Готов к загрузке с *</label>
                <input type="date" name="date_from" required 
                       min="<?= date('Y-m-d') ?>"
                       value="<?= date('Y-m-d') ?>">
            </div>
            <div class="form-group">
                <label>По *</label>
                <input type="date" name="date_to" required 
                       min="<?= date('Y-m-d') ?>"
                       value="<?= date('Y-m-d', strtotime('+3 days')) ?>">
            </div>
        </div>
        
        <!-- ТС -->
        <div class="grid-2">
            <div class="form-group">
                <label>Тип ТС</label>
                <select name="vehicle_type">
                    <option value="">Не важно</option>
                    <option value="тент">Тент</option>
                    <option value="реф">Рефрижератор</option>
                    <option value="борт">Бортовой</option>
                    <option value="изотерм">Изотерм</option>
                    <option value="цистерна">Цистерна</option>
                    <option value="самосвал">Самосвал</option>
                </select>
            </div>
            <div class="form-group">
                <label>Грузоподъёмность (т)</label>
                <input type="number" name="capacity_tons" step="0.5" min="0.5" max="40"
                       placeholder="20">
            </div>
        </div>
        
        <div class="grid-2">
            <div class="form-group">
                <label>Объём кузова (м³)</label>
                <input type="number" name="capacity_volume" step="0.5" min="1"
                       placeholder="82">
            </div>
            <div class="form-group">
                <label>Радиус поиска</label>
                <select name="radius_km">
                    <option value="100">100 км</option>
                    <option value="300" selected>300 км</option>
                    <option value="500">500 км</option>
                    <option value="1000">1000 км</option>
                </select>
            </div>
        </div>
        
        <!-- Направление -->
        <div class="form-group">
            <label>Куда хотите ехать (необязательно)</label>
            <input type="text" name="preferred_destination" maxlength="100"
                   placeholder="Например: Москва">
        </div>
        
        <!-- Телефон -->
        <div class="form-group">
            <label>Телефон для связи</label>
            <input type="tel" name="contact_phone" maxlength="20"
                   value="<?= htmlspecialchars($profilePhone ?? '') ?>"
                   placeholder="+7 (999) 123-45-67">
        </div>
        
        <!-- Комментарий -->
        <div class="form-group">
            <label>Комментарий</label>
            <textarea name="comment" maxlength="500" rows="3"
                      placeholder="Например: Пустой тент, готов взять попутный груз"><?= htmlspecialchars($_POST['comment'] ?? '') ?></textarea>
        </div>
        
        <button type="submit" class="btn btn-accent btn-lg btn-block" style="margin-top:8px;">
            🔍 Найти обратные заказы
        </button>
    </form>

<?php endif; ?>

<?php require_once BASE_PATH . '/templates/footer.php'; ?>