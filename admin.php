<?php
define('APP', true);
require_once 'config.php';

require_admin();

$pageTitle  = 'Админ-панель';
$activePage = 'admin';

// ==================== ОБРАБОТКА ДЕЙСТВИЙ ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();

    $id       = (int)($_POST['id'] ?? 0);
    $decision = $_POST['decision'] ?? '';
    $comment  = trim(mb_substr($_POST['admin_comment'] ?? '', 0, 300));

    if ($id && in_array($decision, ['confirmed', 'rejected'])) {
        if ($decision === 'rejected' && !$comment) {
            flash('error', 'Укажите причину отклонения.');
        } else {
            $stmt = $pdo->prepare("
                UPDATE bookings SET status = ?, admin_comment = ?, moderated_at = NOW(), moderated_by = ?
                WHERE id = ?
            ");
            $stmt->execute([$decision, $comment, $_SESSION['admin_login'], $id]);
            flash('success', 'Заявка #' . $id . ' ' . ($decision === 'confirmed' ? 'подтверждена' : 'отклонена') . '.');
        }
    }
    header('Location: admin.php?' . http_build_query(array_filter([
        'status' => $_POST['status_filter'] ?? 'all',
        'search' => trim($_POST['search_filter'] ?? '')
    ])));
    exit;
}

// ==================== ФИЛЬТРЫ ====================
$status = in_array($_GET['status'] ?? '', ['new','confirmed','rejected']) ? $_GET['status'] : 'all';
$search = trim($_GET['search'] ?? '');

$sql = "SELECT * FROM bookings WHERE 1=1";
$params = [];

if ($status !== 'all') {
    $sql .= " AND status = ?";
    $params[] = $status;
}
if ($search !== '') {
    $sql .= " AND (booking_number LIKE ? OR last_name LIKE ? OR first_name LIKE ? OR phone LIKE ? OR email LIKE ? OR room_title LIKE ?)";
    $like = '%' . $search . '%';
    $params = array_merge($params, [$like, $like, $like, $like, $like, $like]);
}

$sql .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

// Статистика
$stats = [
    'all'       => (int)$pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn(),
    'new'       => (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status='new'")->fetchColumn(),
    'confirmed' => (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status='confirmed'")->fetchColumn(),
    'rejected'  => (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status='rejected'")->fetchColumn(),
];

include 'includes/header.php';
?>

<h1 class="section-title">Модерирование заявок</h1>

<div class="stats mb-3">
    <a class="stat <?= $status === 'all' ? 'active' : '' ?>" href="admin.php">Все: <strong><?= $stats['all'] ?></strong></a>
    <a class="stat stat-new <?= $status === 'new' ? 'active' : '' ?>" href="admin.php?status=new">Новые: <strong><?= $stats['new'] ?></strong></a>
    <a class="stat stat-confirmed <?= $status === 'confirmed' ? 'active' : '' ?>" href="admin.php?status=confirmed">Подтверждённые: <strong><?= $stats['confirmed'] ?></strong></a>
    <a class="stat stat-rejected <?= $status === 'rejected' ? 'active' : '' ?>" href="admin.php?status=rejected">Отклонённые: <strong><?= $stats['rejected'] ?></strong></a>
</div>

<form method="get" class="panel filters mb-3">
    <div class="field field-narrow">
        <label>Статус</label>
        <select name="status">
            <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>все</option>
            <option value="new" <?= $status === 'new' ? 'selected' : '' ?>>новые</option>
            <option value="confirmed" <?= $status === 'confirmed' ? 'selected' : '' ?>>подтверждённые</option>
            <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>отклонённые</option>
        </select>
    </div>
    <div class="field">
        <label>Поиск</label>
        <input type="text" name="search" value="<?= e($search) ?>" placeholder="SD-0001 или Иванов">
    </div>
    <div class="filter-actions">
        <button class="btn btn-accent" type="submit">Применить</button>
        <a href="admin.php" class="btn btn-outline-accent">Сбросить</a>
    </div>
</form>

<?php if (empty($bookings)): ?>
    <div class="alert alert-info">Заявок не найдено.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-admin">
            <thead>
                <tr>
                    <th>№ / создана</th>
                    <th>Гость</th>
                    <th>Номер</th>
                    <th>Даты</th>
                    <th>Сумма</th>
                    <th>Статус</th>
                    <th>Решение</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($bookings as $b): ?>
                <tr>
                    <td>
                        <strong><?= e($b['booking_number']) ?></strong><br>
                        <small class="text-muted"><?= format_datetime_ru($b['created_at']) ?></small>
                    </td>
                    <td>
                        <?= e($b['last_name']) ?> <?= e($b['first_name']) ?><br>
                        <small><a href="tel:<?= e($b['phone']) ?>"><?= e($b['phone']) ?></a></small><br>
                        <small><a href="mailto:<?= e($b['email']) ?>"><?= e($b['email']) ?></a></small>
                        <?php if ($b['comment']): ?>
                            <br><small class="text-muted">💬 <?= e($b['comment']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        «<?= e($b['room_title']) ?>»<br>
                        <small class="text-muted"><?= e($b['category']) ?> · <?= format_price($b['price']) ?>/сут.</small>
                    </td>
                    <td>
                        <?= format_date_ru($b['check_in']) ?> —<br>
                        <?= format_date_ru($b['check_out']) ?><br>
                        <small class="text-muted"><?= (int)$b['nights'] ?> ноч.</small>
                    </td>
                    <td><strong><?= format_price($b['total']) ?></strong></td>
                    <td>
                        <span class="status status-<?= $b['status'] ?>">
                            <?= ['new'=>'Новая','confirmed'=>'Подтверждена','rejected'=>'Отклонена'][$b['status']] ?>
                        </span>
                        <?php if ($b['admin_comment']): ?>
                            <br><small class="text-muted"><?= e($b['admin_comment']) ?></small>
                        <?php endif; ?>
                        <?php if ($b['moderated_by']): ?>
                            <br><small class="text-muted"><?= e($b['moderated_by']) ?>, <?= format_datetime_ru($b['moderated_at']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form method="post" action="admin.php" class="moderate-form">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                            <input type="hidden" name="status_filter" value="<?= e($status) ?>">
                            <input type="hidden" name="search_filter" value="<?= e($search) ?>">
                            <input type="text" name="admin_comment" class="form-control form-control-sm"
                                   placeholder="Причина / комментарий"
                                   value="<?= e($b['admin_comment']) ?>" maxlength="300">
                            <div class="d-flex gap-1 mt-1">
                                <button class="btn btn-sm btn-success flex-fill" type="submit" name="decision" value="confirmed">Подтвердить</button>
                                <button class="btn btn-sm btn-danger flex-fill" type="submit" name="decision" value="rejected">Отклонить</button>
                            </div>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
