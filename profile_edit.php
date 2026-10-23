<?php require_once BASE_PATH . '/templates/header.php'; ?>

<div class="card" style="max-width: 600px; margin: 0 auto;">
    <h2>✏️ Редактировать профиль</h2>
    
    <?php if (isset($_GET['required']) && $_GET['required'] == 'inn'): ?>
        <div class="alert" style="background:#fff3cd; color:#92400e; border-color:#f59e0b;">
            ⚠️ Для создания заказа необходимо заполнить ИНН.
        </div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?= $error ?></div>
    <?php endif; ?>
    
    <form method="POST" action="/profile/edit">
        
        <!-- ==== Основное ==== -->
        <h3 style="font-size:14px; text-transform:uppercase; letter-spacing:1px; color:var(--text-secondary); margin:16px 0 12px; padding-bottom:8px; border-bottom:1px solid var(--border-light);">
            👤 Основное
        </h3>
        
        <div class="form-group">
            <label>Имя *</label>
            <input type="text" name="name" required value="<?= htmlspecialchars($profile['name']) ?>">
        </div>
        
        <div class="form-group">
            <label>Телефон</label>
            <input type="text" name="phone" value="<?= htmlspecialchars($profile['phone']) ?>">
        </div>
        
        <!-- ==== Реквизиты ==== -->
        <h3 style="font-size:14px; text-transform:uppercase; letter-spacing:1px; color:var(--text-secondary); margin:24px 0 12px; padding-bottom:8px; border-bottom:1px solid var(--border-light);">
            🏢 Реквизиты компании
        </h3>
        
        <div class="form-group">
            <label>Компания / ИП</label>
            <input type="text" name="company_name" value="<?= htmlspecialchars($profile['company_name'] ?? '') ?>">
        </div>
        
        <div class="form-group">
            <label>ИНН</label>
            <input type="text" name="inn" value="<?= htmlspecialchars($profile['inn'] ?? '') ?>" maxlength="12">
        </div>
        
        <div class="form-group">
            <label>ОГРН / ОГРНИП</label>
            <input type="text" name="ogrnip" value="<?= htmlspecialchars($profile['ogrnip'] ?? '') ?>" maxlength="15" placeholder="15 цифр для ООО или 15 для ИП">
        </div>
        
        <div class="form-group">
            <label>КПП <small style="color:var(--text-secondary); font-weight:400;">(только для ООО)</small></label>
            <input type="text" name="kpp" value="<?= htmlspecialchars($profile['kpp'] ?? '') ?>" maxlength="9" placeholder="9 цифр">
        </div>
        
        <div class="form-group">
            <label>Юридический адрес</label>
            <input type="text" name="address" value="<?= htmlspecialchars($profile['address'] ?? '') ?>" placeholder="Например: 162560, Вологодская обл., г. Шексна, ул. Ленина, д. 29">
        </div>
        
        <div class="form-group">
            <label>Ставка НДС</label>
            <select name="nds_rate">
                <option value="20" <?= ($profile['nds_rate'] ?? '20') == '20' ? 'selected' : '' ?>>НДС 20% (ОСНО)</option>
                <option value="0" <?= ($profile['nds_rate'] ?? '20') == '0' ? 'selected' : '' ?>>Без НДС (УСН)</option>
            </select>
        </div>
        
        <!-- ==== Реквизиты для carrier ==== -->
        <?php if ($_SESSION['user_role'] === 'carrier'): ?>
        <h3 style="font-size:14px; text-transform:uppercase; letter-spacing:1px; color:var(--text-secondary); margin:24px 0 12px; padding-bottom:8px; border-bottom:1px solid var(--border-light);">
            💰 Банковские реквизиты
        </h3>
        
        <div class="form-group">
            <label>Реквизиты для получения оплаты</label>
            <textarea name="bank_details" rows="6" placeholder="ИП Иванов Иван Иванович&#10;ИНН: 123456789012&#10;ОГРНИП: 123456789012345&#10;Р/с: 40802810000000000001&#10;Банк: АО «Т-Банк»&#10;БИК: 044525974&#10;К/с: 30101810145250000974"><?= htmlspecialchars($profile['bank_details'] ?? '') ?></textarea>
            <small style="color:var(--text-secondary); font-size:12px;">Эти реквизиты увидит только заказчик после выбора вас исполнителем. Используются для перевода оплаты.</small>
        </div>
        <?php endif; ?>
        
        <!-- ==== Безопасность ==== -->
        <h3 style="font-size:14px; text-transform:uppercase; letter-spacing:1px; color:var(--text-secondary); margin:24px 0 12px; padding-bottom:8px; border-bottom:1px solid var(--border-light);">
            🔐 Безопасность
        </h3>
        
        <div class="form-group">
            <label>Новый пароль <small style="color:var(--text-secondary); font-weight:400;">(оставьте пустым, чтобы не менять)</small></label>
            <input type="password" name="new_password" placeholder="Минимум 6 символов">
        </div>
        
        <button type="submit" class="btn btn-primary btn-block">💾 Сохранить</button>
        <a href="/profile" class="btn btn-ghost btn-block" style="margin-top:8px;">Отмена</a>
    </form>
</div>

<?php require_once BASE_PATH . '/templates/footer.php'; ?>