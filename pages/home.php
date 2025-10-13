<?php
$q = trim($_GET['q'] ?? '');
$priceMin = $_GET['price_min'] ?? '';
$priceMax = $_GET['price_max'] ?? '';
$mileageMax = $_GET['mileage_max'] ?? '';
$where = ["status='active'"]; $params = [];

if ($q) { $where[] = "(make LIKE :q OR model LIKE :q OR title LIKE :q)"; $params[':q']="%$q%"; }
if ($priceMin !== '') { $where[] = "price >= :pmin"; $params[':pmin'] = (float)$priceMin; }
if ($priceMax !== '') { $where[] = "price <= :pmax"; $params[':pmax'] = (float)$priceMax; }
if ($mileageMax !== '') { $where[] = "mileage <= :mmax"; $params[':mmax'] = (int)$mileageMax; }

$sql = "SELECT c.*, (SELECT path FROM car_images WHERE car_id=c.id AND is_primary=1 LIMIT 1) img
        FROM cars c WHERE ".implode(' AND ', $where)." ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql); $stmt->execute($params); $cars = $stmt->fetchAll();

$_SESSION['compare'] = $_SESSION['compare'] ?? []; // holds up to 2 ids
if (isset($_GET['cmp_add'])) {
  $id = (int)$_GET['cmp_add'];
  if (!in_array($id, $_SESSION['compare'])) {
    $_SESSION['compare'][] = $id;
    $_SESSION['compare'] = array_slice($_SESSION['compare'], -2);
  }
  header('Location: /autotrade/index.php'); exit;
}
?>
<h1>Homepage</h1>
<form>
  <input name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search">
  <input name="price_min" type="number" placeholder="Min price">
  <input name="price_max" type="number" placeholder="Max price">
  <input name="mileage_max" type="number" placeholder="Max mileage">
  <button>Filter</button>
</form>

<?php if (count($_SESSION['compare'])===2): ?>
  <p><a href="/autotrade/pages/compare.php">Compare selected (<?= implode(',', $_SESSION['compare']) ?>)</a></p>
<?php endif; ?>

<ul class="grid">
<?php foreach ($cars as $c): ?>
  <li class="card">
    <img src="/autotrade/public/<?= $c['img'] ?: 'css/placeholder.png' ?>" alt="" width="180">
    <h3><?= htmlspecialchars($c['title']) ?></h3>
    <p>$<?= number_format($c['price'], 2) ?> - <?= (int)$c['mileage'] ?> miles</p>
    <a href="/autotrade/pages/car.php?id=<?= $c['id'] ?>">Details</a>
    <a href="/autotrade/index.php?cmp_add=<?= $c['id'] ?>">Compare</a>
    <?php if (current_user() && current_user()['role']==='buyer'): ?>
      <a href="/autotrade/pages/watchlist_add.php?id=<?= $c['id'] ?>">Save</a>
    <?php endif; ?>
  </li>
<?php endforeach; ?>
</ul>
