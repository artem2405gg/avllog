<?php require_once BASE_PATH . '/templates/header.php'; ?>

<div class="card" style="max-width: 400px; margin: 50px auto;">
    <h2 style="text-align:center;">🔑 Восстановление пароля</h2>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" required placeholder="Введите ваш email">
        </div>
        <button type="submit" class="btn btn-primary btn-block">Отправить ссылку</button>
    </form>
    <p style="text-align:center; margin-top:16px;"><a href="/login">← Вернуться ко входу</a></p>
</div>

<?php require_once BASE_PATH . '/templates/footer.php'; ?>