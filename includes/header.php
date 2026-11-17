<?php if (!defined('APP')) exit; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Гостиница «Сладкие сны»') ?></title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/site.css">
</head>
<body>

<header class="site-header">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <a href="index.php" class="logo text-decoration-none text-white d-flex align-items-center gap-2">
                <span class="logo-mark">🌙</span>
                <div>
                    <div class="logo-title">Сладкие сны</div>
                    <div class="logo-slogan">Гостиница, в которой снятся только хорошие сны</div>
                </div>
            </a>
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <nav class="main-nav">
                    <a href="index.php" class="<?= ($activePage ?? '') === 'home' ? 'active' : '' ?>">Главная</a>
                    <a href="index.php#catalog" class="<?= ($activePage ?? '') === 'catalog' ? 'active' : '' ?>">Каталог</a>
                    <?php if (is_admin()): ?>
                        <a href="admin.php" class="<?= ($activePage ?? '') === 'admin' ? 'active' : '' ?>">Заявки</a>
                    <?php endif; ?>
                </nav>
                <div id="auth-block">
                    <?php if (is_admin()): ?>
                        <span class="auth-user">👤 <?= e($_SESSION['admin_login']) ?></span>
                        <a href="logout.php" class="btn btn-outline-light btn-sm">Выйти</a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline-light btn-sm">Войти как администратор</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</header>

<main class="container py-4">
<?php render_flash(); ?>
