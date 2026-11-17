<?php
define('APP', true);
require_once 'config.php';

$pageTitle  = 'Бронирование';
$activePage = 'catalog';

// ==================== ПОЛУЧЕНИЕ НОМЕРА ====================
$room = null;
$errors = [];
$values = [
    'first_name' => '', 'last_name' => '', 'phone' => '', 'email' => '',
    'check_in' => '', 'check_out' => '', 'comment' => '', 'consent' => false
];

if (isset($_GET['room'])) {
    $room = get_room($pdo, $_GET['room']);
}

// ==================== ОБРАБОТКА ФОРМЫ ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();

    if (!$room) {
        flash('error', 'Номер не найден.');
        header('Location: index.php#catalog');
        exit;
    }

    $result = validate_booking($_POST);
    $values = $result['values'];
    $errors = $result['errors'];

    if (empty($errors)) {
        // Генерируем номер заявки
        $stmt = $pdo->query("SELECT COUNT(*) FROM bookings");
        $num = $stmt->fetchColumn() + 1;
        $bookingNumber = 'SD-' . str_pad($num, 4, '0', STR_PAD_LEFT);

        $stmt = $pdo->prepare("
            INSERT INTO bookings (booking_number, room_id, room_title, category, price,
                first_name, last_name, phone, email, check_in, check_out, nights, total, comment)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $bookingNumber,
            $room['id'], $room['title'], $room['category'], $room['price'],
            $values['first_name'], $values['last_name'], $values['phone'], $values['email'],
            $result['in_iso'], $result['out_iso'], $result['nights'],
            $result['nights'] * $room['price'],
            $values['comment']
        ]);

        flash('success', "Заявка $bookingNumber успешно отправлена! Администратор свяжется с вами.");
        header('Location: index.php#catalog');
        exit;
    }
}

include 'includes/header.php';
?>

<h1 class="section-title">Бронирование номера</h1>
<p class="mb-3"><a href="index.php#catalog" class="back-link">← Вернуться в каталог</a></p>

<?php if (!$room): ?>
    <div class="alert alert-danger">
        Номер не найден. <a href="index.php#catalog">Вернитесь в каталог</a> и выберите номер заново.
    </div>
<?php else: ?>

<div class="row g-4">
    <aside class="col-md-4">
        <div class="panel">
            <img src="<?= room_image($room) ?>" alt="<?= e($room['title']) ?>" class="w-100 rounded mb-3">
            <span class="badge"><?= e($room['category']) ?></span>
            <h2 class="mt-2"><?= e($room['title']) ?></h2>
            <p class="price"><?= format_price($room['price']) ?> <span class="text-muted small">/ сутки</span></p>
            <ul class="features">
                <?php foreach (features_array($room['features']) as $f): ?>
                    <li><?= e($f) ?></li>
                <?php endforeach; ?>
                <li>Гостей: <?= (int)$room['guests'] ?></li>
            </ul>
        </div>
    </aside>

    <section class="col-md-8">
        <div class="panel">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <strong>Заявка не отправлена. Исправьте ошибки:</strong>
                    <ul class="mb-0 mt-2">
                        <?php foreach ($errors as $err): ?>
                            <li><?= e($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" action="booking.php?room=<?= (int)$room['id'] ?>" novalidate>
                <?= csrf_field() ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Имя <span class="req">*</span></label>
                        <input name="first_name" type="text" class="form-control <?= isset($errors['first_name']) ? 'is-invalid' : '' ?>"
                               value="<?= e($values['first_name']) ?>" placeholder="Иван">
                        <?php if (isset($errors['first_name'])): ?>
                            <div class="invalid-feedback"><?= e($errors['first_name']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Фамилия <span class="req">*</span></label>
                        <input name="last_name" type="text" class="form-control <?= isset($errors['last_name']) ? 'is-invalid' : '' ?>"
                               value="<?= e($values['last_name']) ?>" placeholder="Петров">
                        <?php if (isset($errors['last_name'])): ?>
                            <div class="invalid-feedback"><?= e($errors['last_name']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Телефон <span class="req">*</span></label>
                        <input name="phone" type="tel" class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>"
                               value="<?= e($values['phone']) ?>" placeholder="+7(999)123-45-67" id="phone-input">
                        <small class="form-text">Вводите только цифры — формат подставится автоматически</small>
                        <?php if (isset($errors['phone'])): ?>
                            <div class="invalid-feedback"><?= e($errors['phone']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">E-mail <span class="req">*</span></label>
                        <input name="email" type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                               value="<?= e($values['email']) ?>" placeholder="guest@mail.ru">
                        <?php if (isset($errors['email'])): ?>
                            <div class="invalid-feedback"><?= e($errors['email']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Дата заезда <span class="req">*</span></label>
                        <input name="check_in" type="text" class="form-control <?= isset($errors['check_in']) ? 'is-invalid' : '' ?>"
                               value="<?= e($values['check_in']) ?>" placeholder="ДД.ММ.ГГГГ">
                        <small class="form-text">Например: <?= date('d.m.Y') ?></small>
                        <?php if (isset($errors['check_in'])): ?>
                            <div class="invalid-feedback"><?= e($errors['check_in']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Дата выезда <span class="req">*</span></label>
                        <input name="check_out" type="text" class="form-control <?= isset($errors['check_out']) ? 'is-invalid' : '' ?>"
                               value="<?= e($values['check_out']) ?>" placeholder="ДД.ММ.ГГГГ">
                        <small class="form-text">Позже даты заезда</small>
                        <?php if (isset($errors['check_out'])): ?>
                            <div class="invalid-feedback"><?= e($errors['check_out']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Комментарий (необязательно)</label>
                        <textarea name="comment" class="form-control" rows="3" maxlength="500"><?= e($values['comment']) ?></textarea>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="consent" value="1" id="consent"
                                   <?= $values['consent'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="consent">
                                Согласен на обработку персональных данных <span class="req">*</span>
                            </label>
                        </div>
                        <?php if (isset($errors['consent'])): ?>
                            <div class="text-danger small mt-1"><?= e($errors['consent']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-accent btn-lg w-100" type="submit">Отправить заявку</button>
                    </div>
                </div>
            </form>
        </div>
    </section>
</div>

<script>
// Маска телефона
(function(){
    const input = document.getElementById('phone-input');
    if (!input) return;

    function applyMask(digits) {
        if (digits.startsWith('8')) digits = '7' + digits.slice(1);
        if (!digits.startsWith('7') && digits.length > 0) digits = '7' + digits;
        digits = digits.slice(0, 11);
        let r = '';
        if (digits.length >= 1) r = '+7';
        if (digits.length >= 2) r += '(' + digits.slice(1, 4);
        if (digits.length >= 5) r += ')' + digits.slice(4, 7);
        else if (digits.length === 4) r += ')';
        if (digits.length >= 8) r += '-' + digits.slice(7, 9);
        else if (digits.length === 7) r += '-';
        if (digits.length >= 10) r += '-' + digits.slice(9, 11);
        else if (digits.length === 9) r += '-';
        return r;
    }

    input.addEventListener('input', function() {
        const digits = input.value.replace(/\D/g, '').slice(0, 11);
        input.value = applyMask(digits);
    });
    input.addEventListener('keydown', function(e) {
        const allowed = ['Backspace','Delete','ArrowLeft','ArrowRight','Tab','Home','End'];
        if (allowed.includes(e.key)) return;
        if (e.ctrlKey || e.metaKey) return;
        if (!/^\d$/.test(e.key)) e.preventDefault();
    });
    input.addEventListener('paste', function(e) {
        e.preventDefault();
        const pasted = (e.clipboardData || window.clipboardData).getData('text');
        input.value = applyMask(pasted.replace(/\D/g, '').slice(0, 11));
    });
})();
</script>

<?php endif; ?>

<?php include 'includes/footer.php'; ?>
