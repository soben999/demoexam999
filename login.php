<?php
define('APP', true);
require_once 'config.php';

if (is_admin()) {
    header('Location: admin.php');
    exit;
}

$pageTitle  = 'Вход';
$activePage = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $login    = trim($_POST['login']    ?? '');
    $password = $_POST['password']      ?? '';

    if (!$login || !$password) {
        $error = 'Заполните все поля.';
    } elseif (admin_login($login, $password)) {
        flash('success', 'Добро пожаловать, ' . $login . '!');
        header('Location: admin.php');
        exit;
    } else {
        $error = 'Неверный логин или пароль. Проверьте раскладку клавиатуры.';
    }
}

include 'includes/header.php';
?>

<div class="panel narrow-panel">
    <h1 class="section-title text-center">Вход для администратора</h1>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="login.php" novalidate>
        <?= csrf_field() ?>
        <div class="mb-3">
            <label class="form-label">Логин <span class="req">*</span></label>
            <input name="login" type="text" class="form-control" placeholder="admin" value="<?= e($_POST['login'] ?? '') ?>" autocomplete="username">
        </div>
        <div class="mb-3">
            <label class="form-label">Пароль <span class="req">*</span></label>
            <input name="password" type="password" class="form-control" autocomplete="current-password">
        </div>
        <button class="btn btn-accent btn-lg w-100" type="submit">Войти</button>
    </form>
    <p class="text-muted small mt-3 text-center">
        Тестовый доступ: <strong>admin</strong> / <strong>sweet2026</strong>
    </p>
</div>

<?php include 'includes/footer.php'; ?>
