<?php
// ==================== БЕЗОПАСНОСТЬ ====================
function e($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

function csrf_token() {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

function check_csrf() {
    if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'])) {
        http_response_code(403);
        die('Ошибка безопасности (CSRF). Обновите страницу и повторите действие.');
    }
}

// ==================== АВТОРИЗАЦИЯ ====================
function is_admin() {
    return !empty($_SESSION['admin_login']);
}

function require_admin() {
    if (!is_admin()) {
        flash('error', 'Раздел доступен только администратору.');
        header('Location: login.php');
        exit;
    }
}

function admin_login($login, $password) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE login = ?");
    $stmt->execute([$login]);
    $admin = $stmt->fetch();
    if ($admin && password_verify($password, $admin['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_login'] = $admin['login'];
        $_SESSION['admin_id']    = $admin['id'];
        return true;
    }
    return false;
}

function admin_logout() {
    unset($_SESSION['admin_login'], $_SESSION['admin_id']);
    session_destroy();
}

// ==================== FLASH-СООБЩЕНИЯ ====================
function flash($type, $text) {
    $_SESSION['flash'][] = ['type' => $type, 'text' => $text];
}

function get_flash() {
    $msgs = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $msgs;
}

function render_flash() {
    foreach (get_flash() as $f) {
        $class = $f['type'] === 'success' ? 'alert-success' : ($f['type'] === 'error' ? 'alert-danger' : 'alert-info');
        echo '<div class="alert ' . $class . '">' . e($f['text']) . '</div>';
    }
}

// ==================== ФОРМАТИРОВАНИЕ ====================
function format_price($n) {
    return number_format((int)$n, 0, '.', ' ') . ' ₽';
}

function format_date_ru($date) {
    if (!$date) return '—';
    $ts = is_numeric($date) ? $date : strtotime($date);
    return date('d.m.Y', $ts);
}

function format_datetime_ru($dt) {
    if (!$dt) return '—';
    return date('d.m.Y H:i', strtotime($dt));
}

// ==================== ВАЛИДАЦИЯ ====================
function validate_booking($data) {
    $errors = [];
    $v = [];

    $v['first_name'] = trim($data['first_name'] ?? '');
    $v['last_name']  = trim($data['last_name']  ?? '');
    $v['phone']      = preg_replace('/\s+/', '', trim($data['phone'] ?? ''));
    $v['email']      = preg_replace('/\s+/', '', trim($data['email'] ?? ''));
    $v['check_in']   = trim($data['check_in']  ?? '');
    $v['check_out']  = trim($data['check_out'] ?? '');
    $v['comment']    = trim(mb_substr($data['comment'] ?? '', 0, 500));
    $v['consent']    = !empty($data['consent']);

    $reName  = '/^[А-Яа-яЁё]+(?:[ .\-][А-Яа-яЁё]+)*\.?$/u';
    $rePhone = '/^\+7\(\d{3}\)\d{3}-\d{2}-\d{2}$/';
    $reEmail = '/^[A-Za-z0-9._%+-]+@[A-Za-z0-9-]+(?:\.[A-Za-z0-9-]+)*\.[A-Za-z]{2,}$/';

    // Имя
    if (!$v['first_name']) $errors['first_name'] = 'Укажите имя.';
    elseif (mb_strlen($v['first_name']) < 2) $errors['first_name'] = 'Имя слишком короткое (минимум 2 символа).';
    elseif (!preg_match($reName, $v['first_name'])) $errors['first_name'] = 'Имя может содержать только кириллицу, пробелы, точки и тире.';

    // Фамилия
    if (!$v['last_name']) $errors['last_name'] = 'Укажите фамилию.';
    elseif (mb_strlen($v['last_name']) < 2) $errors['last_name'] = 'Фамилия слишком короткая.';
    elseif (!preg_match($reName, $v['last_name'])) $errors['last_name'] = 'Фамилия может содержать только кириллицу, пробелы, точки и тире.';

    // Телефон
    if (!$v['phone']) $errors['phone'] = 'Укажите телефон.';
    elseif (!preg_match($rePhone, $v['phone'])) $errors['phone'] = 'Телефон должен быть в формате +7(ХХХ)ХХХ-ХХ-ХХ.';

    // E-mail
    if (!$v['email']) $errors['email'] = 'Укажите e-mail.';
    elseif (!preg_match($reEmail, $v['email'])) $errors['email'] = 'Некорректный формат e-mail.';

    // Даты
    $parseDate = function($str) {
        if (!preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $str)) return null;
        [$d, $m, $y] = array_map('intval', explode('.', $str));
        if (!checkdate($m, $d, $y)) return null;
        return sprintf('%04d-%02d-%02d', $y, $m, $d);
    };

    $inIso  = $parseDate($v['check_in']);
    $outIso = $parseDate($v['check_out']);
    $today  = date('Y-m-d');

    if (!$v['check_in']) $errors['check_in'] = 'Укажите дату заезда.';
    elseif (!$inIso) $errors['check_in'] = 'Дата должна быть в формате ДД.ММ.ГГГГ и существовать в календаре.';
    elseif ($inIso < $today) $errors['check_in'] = 'Дата заезда не может быть в прошлом. Сегодня: ' . format_date_ru($today) . '.';

    if (!$v['check_out']) $errors['check_out'] = 'Укажите дату выезда.';
    elseif (!$outIso) $errors['check_out'] = 'Дата должна быть в формате ДД.ММ.ГГГГ и существовать в календаре.';
    elseif ($outIso < $today) $errors['check_out'] = 'Дата выезда не может быть в прошлом.';

    $nights = 0;
    if ($inIso && $outIso && empty($errors['check_in']) && empty($errors['check_out'])) {
        $nights = (strtotime($outIso) - strtotime($inIso)) / 86400;
        if ($nights <= 0) $errors['check_out'] = 'Дата выезда должна быть позже даты заезда.';
        elseif ($nights > 60) $errors['check_out'] = 'Максимальный срок — 60 дней.';
    }

    if (!$v['consent']) $errors['consent'] = 'Необходимо согласие на обработку персональных данных.';

    return ['values' => $v, 'errors' => $errors, 'in_iso' => $inIso, 'out_iso' => $outIso, 'nights' => $nights];
}

// ==================== НОМЕРА ====================
function get_rooms($pdo) {
    return $pdo->query("SELECT * FROM rooms ORDER BY id")->fetchAll();
}

function get_room($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ?");
    $stmt->execute([(int)$id]);
    return $stmt->fetch();
}

function room_image($room) {
    if (!empty($room['image']) && file_exists(__DIR__ . '/../' . $room['image'])) {
        return $room['image'];
    }
    // SVG-заглушка
    $colors = [
        'Стандарт'    => ['#7c6bb0','#463a6b'],
        'Комфорт'     => ['#5b8fb9','#2f4f6f'],
        'Люкс'        => ['#c9a227','#7a5c12'],
        'Семейный'    => ['#57a773','#2f6244'],
        'Апартаменты' => ['#c07a86','#6d3a45'],
    ];
    [$c1, $c2] = $colors[$room['category']] ?? ['#7c6bb0','#463a6b'];
    $title = e($room['title']);
    $cat   = e($room['category']);
    return "data:image/svg+xml;utf8," . urlencode(
        "<svg xmlns='http://www.w3.org/2000/svg' width='600' height='400'>
         <defs><linearGradient id='g' x1='0' y1='0' x2='1' y2='1'>
         <stop offset='0' stop-color='$c1'/><stop offset='1' stop-color='$c2'/></linearGradient></defs>
         <rect width='600' height='400' fill='url(#g)'/>
         <text x='300' y='80' font-family='sans-serif' font-size='30' fill='#fff' text-anchor='middle' opacity='.95'>$cat</text>
         <text x='300' y='360' font-family='sans-serif' font-size='22' fill='#fff' text-anchor='middle' opacity='.8'>Сладкие сны · $title</text>
         </svg>"
    );
}

function features_array($features) {
    if (is_array($features)) return $features;
    return array_filter(array_map('trim', explode(',', (string)$features)));
}
