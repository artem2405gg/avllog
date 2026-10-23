<?php require_once BASE_PATH . '/templates/header.php'; ?>

<style>
    .stars-input { display: flex; gap: 8px; font-size: 40px; cursor: pointer; }
    .stars-input .star { color: #d1d5db; transition: color 0.2s; }
    .stars-input .star.active { color: #fbbf24; }
    .stars-input:hover .star { color: #fbbf24; }
    .stars-input .star:hover ~ .star { color: #d1d5db; }
</style>

<div class="card" style="max-width: 500px; margin: 30px auto;">
    <h2>⭐ Оставить отзыв</h2>
    <p style="color: var(--gray); margin-bottom: 20px;">
        Заказ: <strong><?= htmlspecialchars($order['title']) ?></strong>
    </p>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?= $error ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label>Оценка</label>
            <div class="stars-input" id="starsInput">
                <span class="star" data-value="1">★</span>
                <span class="star" data-value="2">★</span>
                <span class="star" data-value="3">★</span>
                <span class="star" data-value="4">★</span>
                <span class="star" data-value="5">★</span>
            </div>
            <input type="hidden" name="rating" id="ratingValue" value="5">
        </div>
        
        <div class="form-group">
            <label>Комментарий</label>
            <textarea name="comment" rows="4" placeholder="Расскажите, как прошла сделка..."></textarea>
        </div>
        
        <button type="submit" class="btn btn-primary btn-block">Отправить отзыв</button>
    </form>
</div>

<script>
const stars = document.querySelectorAll('#starsInput .star');
const ratingInput = document.getElementById('ratingValue');

// По умолчанию 5 звёзд
stars.forEach(s => s.classList.add('active'));

stars.forEach(star => {
    star.addEventListener('click', function() {
        const value = this.getAttribute('data-value');
        ratingInput.value = value;
        updateStars(value);
    });
    
    star.addEventListener('mouseenter', function() {
        const value = this.getAttribute('data-value');
        updateStars(value);
    });
});

document.getElementById('starsInput').addEventListener('mouseleave', function() {
    updateStars(ratingInput.value);
});

function updateStars(value) {
    stars.forEach(s => {
        if (s.getAttribute('data-value') <= value) {
            s.classList.add('active');
        } else {
            s.classList.remove('active');
        }
    });
}
</script>

<?php require_once BASE_PATH . '/templates/footer.php'; ?>