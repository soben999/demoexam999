<?php
// ==================== НАСТРОЙКИ БД ====================
// Заполните данными из панели хостинга
define('DB_HOST', 'localhost');
define('DB_NAME', 'u1234567_sweet');   // ← имя вашей БД
define('DB_USER', 'u1234567_admin');   // ← логин вашей БД
define('DB_PASS', 'sweetdreems999');    // ← пароль вашей БД
define('DB_CHARSET', 'utf8mb4');

// ==================== ПОДКЛЮЧЕНИЕ ====================
try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    die('Ошибка подключения к БД. Запустите <a href="install.php">install.php</a> и проверьте настройки в config.php.<br>Детали: ' . htmlspecialchars($e->getMessage()));
}

// ==================== СЕССИИ ====================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Подключаем функции
require_once __DIR__ . '/includes/functions.php';
