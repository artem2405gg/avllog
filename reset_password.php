<?php require_once BASE_PATH . '/templates/header.php'; ?>

<div class="card" style="max-width: 400px; margin: 50px auto;">
    <h2 style="text-align:center;">🔐 Новый пароль</h2>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?= $error ?></div>
    <?php endif; ?>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php elseif (!isset($error) || $error != "Ссылка недействительна или истекла."): ?>
        <form method="POST">
            <div class="form-group">
                <label>Новый пароль (минимум 6 символов)</label>
                <input type="password" name="password" required placeholder="Новый пароль">
            </div>
            <button type="submit" class="btn btn-primary btn-block">Сохранить пароль</button>
        </form>
    <?php endif; ?>
</div>

<?php require_once BASE_PATH . '/templates/footer.php'; ?>