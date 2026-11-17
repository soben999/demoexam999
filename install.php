<?php
require_once 'config.php';

echo '<h2>Установка базы данных «Сладкие сны»</h2>';
echo '<style>body{font-family:sans-serif;max-width:800px;margin:40px auto;padding:20px;} .ok{color:green;} .err{color:red;} pre{background:#f4f4f4;padding:10px;border-radius:6px;}</style>';

try {
    // ==================== СОЗДАНИЕ ТАБЛИЦ ====================
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS rooms (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(100) NOT NULL,
            category VARCHAR(50) NOT NULL,
            price INT NOT NULL,
            guests INT NOT NULL DEFAULT 2,
            area INT NOT NULL,
            features TEXT,
            image VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo '<p class="ok">✓ Таблица <b>rooms</b> создана.</p>';

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS bookings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            booking_number VARCHAR(20) NOT NULL UNIQUE,
            room_id INT NOT NULL,
            room_title VARCHAR(100) NOT NULL,
            category VARCHAR(50) NOT NULL,
            price INT NOT NULL,
            first_name VARCHAR(60) NOT NULL,
            last_name VARCHAR(60) NOT NULL,
            phone VARCHAR(20) NOT NULL,
            email VARCHAR(100) NOT NULL,
            check_in DATE NOT NULL,
            check_out DATE NOT NULL,
            nights INT NOT NULL,
            total INT NOT NULL,
            comment TEXT,
            status ENUM('new','confirmed','rejected') NOT NULL DEFAULT 'new',
            admin_comment TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            moderated_at TIMESTAMP NULL,
            moderated_by VARCHAR(60) NULL,
            INDEX idx_status (status),
            INDEX idx_room (room_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo '<p class="ok">✓ Таблица <b>bookings</b> создана.</p>';

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            login VARCHAR(50) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo '<p class="ok">✓ Таблица <b>admins</b> создана.</p>';

    // ==================== НАЧАЛЬНЫЕ ДАННЫЕ ====================
    // Администратор (если ещё нет)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE login = ?");
    $stmt->execute(['admin']);
    if ($stmt->fetchColumn() == 0) {
        $hash = password_hash('sweet2026', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO admins (login, password_hash) VALUES (?, ?)");
        $stmt->execute(['admin', $hash]);
        echo '<p class="ok">✓ Создан администратор: <b>admin</b> / <b>sweet2026</b></p>';
    } else {
        echo '<p>ℹ️ Администратор уже существует, пропускаем.</p>';
    }

    // Номера (если ещё нет)
    $stmt = $pdo->query("SELECT COUNT(*) FROM rooms");
    if ($stmt->fetchColumn() == 0) {
        $rooms = [
            ['Мятный сон',          'Стандарт',    3200,  1, 16, '1 односпальная кровать, 16 м², Wi-Fi, Душ', 'img/room-1.jpg'],
            ['Тёплое молоко',       'Стандарт',    3600,  2, 18, '2 односпальные кровати, 18 м², Завтрак включён', 'img/room-2.jpg'],
            ['Облако',              'Стандарт',    3900,  2, 20, '1 двуспальная кровать, 20 м², Кондиционер', 'img/room-3.jpg'],
            ['Карамель',            'Комфорт',     4900,  2, 24, '1 двуспальная кровать, 24 м², Балкон, Завтрак включён', 'img/room-4.jpg'],
            ['Ваниль',              'Комфорт',     5300,  3, 26, 'Двуспальная + диван, 26 м², Рабочая зона', 'img/room-5.jpg'],
            ['Какао',               'Комфорт',     5600,  2, 28, 'Кровать King-size, 28 м², Вид на парк, Халаты и тапочки', 'img/room-6.jpg'],
            ['Шоколадный трюфель',  'Люкс',        8900,  2, 42, 'Кровать King-size, 42 м², Гостиная зона, Мини-бар', 'img/room-7.jpg'],
            ['Медовый месяц',       'Люкс',       10500,  2, 48, 'Кровать King-size, 48 м², Джакузи, Панорамные окна', 'img/room-8.jpg'],
            ['Королевская ночь',    'Люкс',       13900,  3, 60, 'Спальня + гостиная, 60 м², Сауна, Консьерж', 'img/room-9.jpg'],
            ['Пряничный домик',     'Семейный',    6900,  4, 38, '2 комнаты, 38 м², Детская кроватка, Завтрак включён', 'img/room-10.jpg'],
            ['Мармелад',            'Семейный',    7600,  5, 44, '2 комнаты, 44 м², Кухня-ниша, Игровая зона', 'img/room-11.jpg'],
            ['Апартаменты «Сны»',   'Апартаменты', 15900, 4, 75, '2 спальни + гостиная, 75 м², Кухня, Стиральная машина, Терраса', 'img/room-12.jpg'],
        ];
        $stmt = $pdo->prepare("INSERT INTO rooms (title, category, price, guests, area, features, image) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($rooms as $r) {
            $stmt->execute($r);
        }
        echo '<p class="ok">✓ Добавлено <b>12 номеров</b> в каталог.</p>';
    } else {
        echo '<p>ℹ️ Номера уже есть в каталоге, пропускаем.</p>';
    }

    echo '<hr><h3 class="ok">✅ Установка завершена!</h3>';
    echo '<p><b>Важно:</b> удалите файл <code>install.php</code> с сервера в целях безопасности.</p>';
    echo '<p><a href="index.php">→ Перейти на главную</a> | <a href="login.php">→ Войти как администратор</a></p>';

} catch (PDOException $e) {
    echo '<p class="err">❌ Ошибка: ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p>Проверьте настройки в <code>config.php</code>.</p>';
}
