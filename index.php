<?php
define('APP', true);
require_once 'config.php';

$pageTitle  = 'Главная';
$activePage = 'home';

// ==================== ОБРАБОТКА ФИЛЬТРА ====================
$categories = $pdo->query("SELECT DISTINCT category FROM rooms ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
$selectedCats = isset($_GET['category']) ? (array)$_GET['category'] : [];
$priceMin  = isset($_GET['priceMin']) && $_GET['priceMin'] !== '' ? (int)$_GET['priceMin'] : null;
$priceMax  = isset($_GET['priceMax']) && $_GET['priceMax'] !== '' ? (int)$_GET['priceMax'] : null;
$guests    = isset($_GET['guests'])   && $_GET['guests']   !== '' ? (int)$_GET['guests']   : null;
$sort      = in_array($_GET['sort'] ?? '', ['price-asc','price-desc']) ? $_GET['sort'] : 'default';

// Сброс фильтра
if (isset($_GET['reset'])) {
    header('Location: index.php#catalog');
    exit;
}

// ==================== ПОДГОТОВКА ЗАПРОСА ====================
$sql = "SELECT * FROM rooms WHERE 1=1";
$params = [];

if ($selectedCats) {
    $placeholders = implode(',', array_fill(0, count($selectedCats), '?'));
    $sql .= " AND category IN ($placeholders)";
    $params = array_merge($params, $selectedCats);
}
if ($priceMin !== null) { $sql .= " AND price >= ?"; $params[] = $priceMin; }
if ($priceMax !== null) { $sql .= " AND price <= ?"; $params[] = $priceMax; }
if ($guests   !== null) { $sql .= " AND guests >= ?"; $params[] = $guests; }

if ($sort === 'price-asc')  $sql .= " ORDER BY price ASC";
elseif ($sort === 'price-desc') $sql .= " ORDER BY price DESC";
else $sql .= " ORDER BY RAND() LIMIT 6";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rooms = $stmt->fetchAll();

$totalRooms = (int)$pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn();

include 'includes/header.php';
?>

<section class="hero text-center">
    <h1>Отдых, который снится потом только в хорошем смысле</h1>
    <p class="lead">Уютные номера в центре города, завтраки с домашней выпечкой и тишина после 22:00.</p>
    <a href="#catalog" class="btn btn-accent btn-lg">Перейти в каталог номеров</a>
</section>

<section id="catalog" class="mt-5">
    <h2 class="section-title">Каталог номеров</h2>
    <p class="text-muted mb-4">По умолчанию показывается 6 случайных номеров. Используйте фильтры для поиска.</p>

    <form method="get" action="index.php" class="panel filters mb-4">
        <fieldset class="filter-group">
            <legend>Категория</legend>
            <div class="checks">
                <?php foreach ($categories as $c): ?>
                    <label class="check">
                        <input type="checkbox" name="category[]" value="<?= e($c) ?>"
                            <?= in_array($c, $selectedCats) ? 'checked' : '' ?>>
                        <span><?= e($c) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>
        <div class="field field-narrow">
            <label for="priceMin">Цена от, ₽</label>
            <input id="priceMin" name="priceMin" type="text" inputmode="numeric" value="<?= $priceMin !== null ? e($priceMin) : '' ?>" placeholder="0">
        </div>
        <div class="field field-narrow">
            <label for="priceMax">Цена до, ₽</label>
            <input id="priceMax" name="priceMax" type="text" inputmode="numeric" value="<?= $priceMax !== null ? e($priceMax) : '' ?>" placeholder="20000">
        </div>
        <div class="field field-narrow">
            <label for="guests">Гостей</label>
            <select id="guests" name="guests">
                <option value="">не важно</option>
                <?php for ($i = 1; $i <= 6; $i++): ?>
                    <option value="<?= $i ?>" <?= $guests === $i ? 'selected' : '' ?>><?= $i ?>+</option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="field field-narrow">
            <label for="sort">Сортировка</label>
            <select id="sort" name="sort">
                <option value="default" <?= $sort === 'default' ? 'selected' : '' ?>>по умолчанию</option>
                <option value="price-asc" <?= $sort === 'price-asc' ? 'selected' : '' ?>>сначала дешёвые</option>
                <option value="price-desc" <?= $sort === 'price-desc' ? 'selected' : '' ?>>сначала дорогие</option>
            </select>
        </div>
        <div class="filter-actions">
            <button class="btn btn-accent" type="submit">Применить</button>
            <a href="index.php?reset=1#catalog" class="btn btn-outline-accent">Сбросить фильтр</a>
        </div>
    </form>

    <p class="text-muted mb-3">Показано номеров: <strong><?= count($rooms) ?></strong> из <?= $totalRooms ?>.</p>

    <?php if (empty($rooms)): ?>
        <div class="alert alert-info">По выбранным условиям номера не найдены.</div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($rooms as $room): ?>
                <div class="col-md-6 col-lg-4">
                    <article class="card h-100">
                        <img src="<?= room_image($room) ?>" alt="<?= e($room['title']) ?>" class="card-img">
                        <div class="card-body">
                            <span class="badge"><?= e($room['category']) ?></span>
                            <h3 class="card-title"><?= e($room['title']) ?></h3>
                            <p class="price"><?= format_price($room['price']) ?> <span class="text-muted small">/ сутки</span></p>
                            <ul class="features">
                                <?php foreach (features_array($room['features']) as $f): ?>
                                    <li><?= e($f) ?></li>
                                <?php endforeach; ?>
                                <li>Гостей: <?= (int)$room['guests'] ?></li>
                            </ul>
                            <a href="booking.php?room=<?= (int)$room['id'] ?>" class="btn btn-accent btn-block">Забронировать</a>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php include 'includes/footer.php'; ?>
