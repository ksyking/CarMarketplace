<?php
// AUTOTRADE – Homepage (full search + filters + pagination)
// Requires: app/db.php (PDO $pdo), schema from autotrade.sql

require_once __DIR__ . '/app/db.php';

/* ----------------------------- helpers ----------------------------- */
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function keep_params(array $extra = [], array $drop = []) {
  $params = $_GET;
  foreach ($drop as $d) unset($params[$d]);
  foreach ($extra as $k=>$v) $params[$k] = $v;
  return http_build_query($params);
}

/* ----------------------- load static choices ----------------------- */
$bodyTypes   = ['SUV','Truck','Sedan','Coupe','Hatchback','Wagon','Van','Convertible'];
$drivetrains = ['FWD','RWD','AWD','4WD'];
$fuelTypes   = ['Gas','Diesel','Hybrid','EV'];
$transTypes  = ['Automatic','Manual'];

/* ------------------------ read request inputs ---------------------- */
$q            = trim($_GET['q'] ?? '');
$make         = trim($_GET['make'] ?? '');
$model        = trim($_GET['model'] ?? '');
$minYear      = $_GET['min_year'] ?? '';
$maxYear      = $_GET['max_year'] ?? '';
$minPrice     = $_GET['min_price'] ?? '';
$maxPrice     = $_GET['max_price'] ?? '';
$maxMiles     = $_GET['max_miles'] ?? '';
$bodyType     = trim($_GET['body_type'] ?? '');
$drivetrain   = trim($_GET['drivetrain'] ?? '');
$fuelType     = trim($_GET['fuel_type'] ?? '');
$trans        = trim($_GET['transmission'] ?? '');
$colorExt     = trim($_GET['color_ext'] ?? '');
$colorInt     = trim($_GET['color_int'] ?? '');
$state        = trim($_GET['state'] ?? '');
$city         = trim($_GET['city'] ?? '');
$condGrade    = $_GET['condition_grade'] ?? '';   // 1..5
$featuresSel  = array_values(array_filter($_GET['features'] ?? [], fn($x)=>$x!=='' ));
$sort         = $_GET['sort'] ?? 'newest';        // newest | price_asc | price_desc | mileage_asc | mileage_desc | year_desc | year_asc

$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 20;
$offset = ($page - 1) * $limit;

/* ---------------------- preload feature options -------------------- */
$features = [];
try {
  $features = $pdo->query("SELECT id, name FROM features ORDER BY name ASC")->fetchAll();
} catch (Throwable $e) {
  $features = []; // table might be empty early on; fail soft
}

/* ----------------------------- build SQL --------------------------- */
$sql = "
SELECT l.id, l.title, l.price, l.mileage, l.state, l.city,
       l.color_ext, l.color_int, l.created_at,
       v.make, v.model, v.year, v.body_type, v.drivetrain, v.fuel_type, v.transmission,
       u.display_name
FROM listings l
JOIN vehicles v ON v.id = l.vehicle_id
JOIN users   u  ON u.id = l.seller_id
";
$params = [];

// Only join listing_features if we actually filter by features
$needFeatures = count($featuresSel) > 0;
if ($needFeatures) {
  $sql .= " LEFT JOIN listing_features lf ON lf.listing_id = l.id ";
}

$sql .= " WHERE l.is_active = 1 ";

// text search across title/make/model/seller
if ($q !== '') {
  $sql .= " AND (l.title LIKE :q OR v.make LIKE :q OR v.model LIKE :q OR u.display_name LIKE :q) ";
  $params[':q'] = "%$q%";
}
if ($make !== '')       { $sql .= " AND v.make = :make ";                     $params[':make'] = $make; }
if ($model !== '')      { $sql .= " AND v.model = :model ";                   $params[':model'] = $model; }
if ($minYear !== '' && is_numeric($minYear)) { $sql .= " AND v.year >= :minYear "; $params[':minYear'] = (int)$minYear; }
if ($maxYear !== '' && is_numeric($maxYear)) { $sql .= " AND v.year <= :maxYear "; $params[':maxYear'] = (int)$maxYear; }
if ($minPrice !== '' && is_numeric($minPrice)) { $sql .= " AND l.price >= :minPrice "; $params[':minPrice'] = (float)$minPrice; }
if ($maxPrice !== '' && is_numeric($maxPrice)) { $sql .= " AND l.price <= :maxPrice "; $params[':maxPrice'] = (float)$maxPrice; }
if ($maxMiles !== '' && is_numeric($maxMiles)) { $sql .= " AND l.mileage <= :maxMiles "; $params[':maxMiles'] = (int)$maxMiles; }
if ($bodyType !== '')   { $sql .= " AND v.body_type = :bodyType ";            $params[':bodyType'] = $bodyType; }
if ($drivetrain !== '') { $sql .= " AND v.drivetrain = :drivetrain ";         $params[':drivetrain'] = $drivetrain; }
if ($fuelType !== '')   { $sql .= " AND v.fuel_type = :fuelType ";            $params[':fuelType'] = $fuelType; }
if ($trans !== '')      { $sql .= " AND v.transmission = :trans ";            $params[':trans'] = $trans; }
if ($colorExt !== '')   { $sql .= " AND l.color_ext = :colorExt ";            $params[':colorExt'] = $colorExt; }
if ($colorInt !== '')   { $sql .= " AND l.color_int = :colorInt ";            $params[':colorInt'] = $colorInt; }
if ($state !== '')      { $sql .= " AND l.state = :state ";                   $params[':state'] = $state; }
if ($city !== '')       { $sql .= " AND l.city = :city ";                     $params[':city'] = $city; }
if ($condGrade !== '' && is_numeric($condGrade)) { $sql .= " AND l.condition_grade >= :condGrade "; $params[':condGrade'] = (int)$condGrade; }

$having = '';
if ($needFeatures) {
  // require ALL selected features
  $phs = [];
  foreach ($featuresSel as $i => $fid) {
    $ph = ":f{$i}";
    $phs[] = $ph;
    $params[$ph] = (int)$fid;
  }
  $in = implode(',', $phs);
  $sql .= " AND lf.feature_id IN ($in) ";
  $sql .= " GROUP BY l.id ";
  $having = " HAVING COUNT(DISTINCT lf.feature_id) = " . count($featuresSel) . " ";
}

// sorting
switch ($sort) {
  case 'price_asc':    $order = " ORDER BY l.price ASC "; break;
  case 'price_desc':   $order = " ORDER BY l.price DESC "; break;
  case 'mileage_asc':  $order = " ORDER BY l.mileage ASC "; break;
  case 'mileage_desc': $order = " ORDER BY l.mileage DESC "; break;
  case 'year_asc':     $order = " ORDER BY v.year ASC "; break;
  case 'year_desc':    $order = " ORDER BY v.year DESC "; break;
  default:             $order = " ORDER BY l.created_at DESC "; break; // newest
}

// finalize queries
$sqlCount = "SELECT COUNT(*) AS c FROM (" . $sql . $having . ") sub";
$sql      = $sql . $having . $order . " LIMIT :limit OFFSET :offset ";

// run count
$stmtCount = $pdo->prepare($sqlCount);
foreach ($params as $k=>$v) $stmtCount->bindValue($k, $v);
$stmtCount->execute();
$total = (int)$stmtCount->fetchColumn();

// run main
$stmt = $pdo->prepare($sql);
foreach ($params as $k=>$v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$listings = $stmt->fetchAll();

$totalPages = max(1, (int)ceil($total / $limit));
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>AUTOTRADE</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .brand{letter-spacing:2px;font-weight:700}
    .filter-chip{background:#f1f3f5;border-radius:999px;padding:.25rem .75rem;margin-right:.5rem;display:inline-block}
  </style>
</head>
<body class="bg-light">

<!-- Banner / Nav -->
<nav class="navbar navbar-expand-lg bg-white border-bottom">
  <div class="container">
    <a class="navbar-brand mx-auto brand" href="/">AUTOTRADE</a>
    <div class="d-none d-lg-flex gap-4">
      <a class="nav-link" href="/about.php">About</a>
      <a class="nav-link" href="/contact.php">Contact Us</a>
      <a class="nav-link" href="/signup.php">Sign up</a>
      <a class="nav-link" href="/login.php">Log In</a>
    </div>
  </div>
</nav>

<section class="container py-4">

  <!-- Search -->
  <form method="get" class="mb-3">
    <div class="input-group input-group-lg mb-3">
      <span class="input-group-text">Search</span>
      <input name="q" value="<?= h($q) ?>" class="form-control" placeholder="Search users, cars, or listings...">
      <button class="btn btn-primary">Go</button>
    </div>

    <!-- Filters -->
    <div class="card shadow-sm">
      <div class="card-body row g-3 align-items-end">

        <div class="col-12 col-md-3">
          <label class="form-label">Make</label>
          <input name="make" class="form-control" value="<?= h($make) ?>">
        </div>
        <div class="col-12 col-md-3">
          <label class="form-label">Model</label>
          <input name="model" class="form-control" value="<?= h($model) ?>">
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label">Min Year</label>
          <input type="number" name="min_year" class="form-control" value="<?= h($minYear) ?>">
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label">Max Year</label>
          <input type="number" name="max_year" class="form-control" value="<?= h($maxYear) ?>">
        </div>

        <div class="col-6 col-md-2">
          <label class="form-label">Min Price ($)</label>
          <input type="number" name="min_price" class="form-control" value="<?= h($minPrice) ?>">
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label">Max Price ($)</label>
          <input type="number" name="max_price" class="form-control" value="<?= h($maxPrice) ?>">
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label">Max Mileage</label>
          <input type="number" name="max_miles" class="form-control" value="<?= h($maxMiles) ?>">
        </div>

        <div class="col-6 col-md-2">
          <label class="form-label">Body Type</label>
          <select name="body_type" class="form-select">
            <option value="">Any</option>
            <?php foreach ($bodyTypes as $bt): ?>
              <option value="<?= h($bt) ?>" <?= $bodyType===$bt?'selected':'' ?>><?= h($bt) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label">Drivetrain</label>
          <select name="drivetrain" class="form-select">
            <option value="">Any</option>
            <?php foreach ($drivetrains as $dt): ?>
              <option value="<?= h($dt) ?>" <?= $drivetrain===$dt?'selected':'' ?>><?= h($dt) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label">Fuel</label>
          <select name="fuel_type" class="form-select">
            <option value="">Any</option>
            <?php foreach ($fuelTypes as $ft): ?>
              <option value="<?= h($ft) ?>" <?= $fuelType===$ft?'selected':'' ?>><?= h($ft) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label">Transmission</label>
          <select name="transmission" class="form-select">
            <option value="">Any</option>
            <?php foreach ($transTypes as $tt): ?>
              <option value="<?= h($tt) ?>" <?= $trans===$tt?'selected':'' ?>><?= h($tt) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-6 col-md-2">
          <label class="form-label">Exterior Color</label>
          <input name="color_ext" class="form-control" value="<?= h($colorExt) ?>">
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label">Interior Color</label>
          <input name="color_int" class="form-control" value="<?= h($colorInt) ?>">
        </div>

        <div class="col-6 col-md-2">
          <label class="form-label">State</label>
          <input name="state" class="form-control" value="<?= h($state) ?>">
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label">City</label>
          <input name="city" class="form-control" value="<?= h($city) ?>">
        </div>

        <div class="col-6 col-md-2">
          <label class="form-label">Min Condition (1–5)</label>
          <input type="number" min="1" max="5" name="condition_grade" class="form-control" value="<?= h($condGrade) ?>">
        </div>

        <div class="col-12">
          <label class="form-label">Features (must include all selected)</label>
          <select name="features[]" class="form-select" multiple size="5">
            <?php foreach ($features as $f): ?>
              <option value="<?= (int)$f['id'] ?>" <?= in_array($f['id'], array_map('intval',$featuresSel), true)?'selected':'' ?>>
                <?= h($f['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <small class="text-muted">Hold Ctrl (Windows) or Cmd (Mac) to select multiple.</small>
        </div>

        <div class="col-6 col-md-3">
          <label class="form-label">Sort</label>
          <select name="sort" class="form-select">
            <option value="newest"      <?= $sort==='newest'?'selected':'' ?>>Newest</option>
            <option value="price_asc"   <?= $sort==='price_asc'?'selected':'' ?>>Price ↑</option>
            <option value="price_desc"  <?= $sort==='price_desc'?'selected':'' ?>>Price ↓</option>
            <option value="mileage_asc" <?= $sort==='mileage_asc'?'selected':'' ?>>Mileage ↑</option>
            <option value="mileage_desc"<?= $sort==='mileage_desc'?'selected':'' ?>>Mileage ↓</option>
            <option value="year_desc"   <?= $sort==='year_desc'?'selected':'' ?>>Year ↓</option>
            <option value="year_asc"    <?= $sort==='year_asc'?'selected':'' ?>>Year ↑</option>
          </select>
        </div>

        <div class="col-6 col-md-3">
          <button class="btn btn-primary w-100">Apply Filters</button>
        </div>
        <div class="col-6 col-md-3">
          <a class="btn btn-outline-secondary w-100" href="/index.php">Reset</a>
        </div>
      </div>
    </div>
  </form>

  <!-- Active filter chips -->
  <div class="mb-3">
    <?php if($q!==''): ?><span class="filter-chip">“<?= h($q) ?>”</span><?php endif; ?>
    <?php if($make!==''): ?><span class="filter-chip"><?= h($make) ?></span><?php endif; ?>
    <?php if($model!==''): ?><span class="filter-chip"><?= h($model) ?></span><?php endif; ?>
    <?php if($minYear!==''): ?><span class="filter-chip">≥ <?= h($minYear) ?></span><?php endif; ?>
    <?php if($maxYear!==''): ?><span class="filter-chip">≤ <?= h($maxYear) ?></span><?php endif; ?>
    <?php if($minPrice!==''): ?><span class="filter-chip">Min $<?= h($minPrice) ?></span><?php endif; ?>
    <?php if($maxPrice!==''): ?><span class="filter-chip">Max $<?= h($maxPrice) ?></span><?php endif; ?>
    <?php if($maxMiles!==''): ?><span class="filter-chip">≤ <?= h($maxMiles) ?> mi</span><?php endif; ?>
    <?php if($bodyType!==''): ?><span class="filter-chip"><?= h($bodyType) ?></span><?php endif; ?>
    <?php if($drivetrain!==''): ?><span class="filter-chip"><?= h($drivetrain) ?></span><?php endif; ?>
    <?php if($fuelType!==''): ?><span class="filter-chip"><?= h($fuelType) ?></span><?php endif; ?>
    <?php if($trans!==''): ?><span class="filter-chip"><?= h($trans) ?></span><?php endif; ?>
    <?php if($colorExt!==''): ?><span class="filter-chip">Ext: <?= h($colorExt) ?></span><?php endif; ?>
    <?php if($colorInt!==''): ?><span class="filter-chip">Int: <?= h($colorInt) ?></span><?php endif; ?>
    <?php if($state!==''): ?><span class="filter-chip"><?= h($state) ?></span><?php endif; ?>
    <?php if($city!==''): ?><span class="filter-chip"><?= h($city) ?></span><?php endif; ?>
    <?php if($condGrade!==''): ?><span class="filter-chip">Cond ≥ <?= h($condGrade) ?></span><?php endif; ?>
    <?php foreach ($features as $f): if (in_array((int)$f['id'], array_map('intval',$featuresSel), true)): ?>
      <span class="filter-chip"><?= h($f['name']) ?></span>
    <?php endif; endforeach; ?>
  </div>

  <!-- Results -->
  <div class="vstack gap-3">
    <?php if ($total === 0): ?>
      <div class="alert alert-secondary">No results found. Try different filters.</div>
    <?php else: foreach ($listings as $row): ?>
      <div class="card shadow-sm">
        <div class="card-body d-flex justify-content-between align-items-center">
          <div>
            <div class="fw-bold">
              <?= h($row['year'].' '.$row['make'].' '.$row['model']) ?>
            </div>
            <div class="text-muted">
              <?= h($row['title']) ?>
            </div>
            <div class="mt-1">
              <span class="badge bg-light text-dark">Price: $<?= number_format((float)$row['price'],2) ?></span>
              <span class="badge bg-light text-dark">Mileage: <?= number_format((int)$row['mileage']) ?> mi</span>
              <?php if($row['city'] || $row['state']): ?>
                <span class="badge bg-light text-dark"><?= h(trim($row['city'].' '.$row['state'])) ?></span>
              <?php endif; ?>
            </div>
          </div>
          <div class="d-flex gap-2">
            <a class="btn btn-outline-primary" href="/listing.php?id=<?= (int)$row['id'] ?>">View</a>
            <form method="post" action="/compare_add.php" class="m-0">
              <input type="hidden" name="listing_id" value="<?= (int)$row['id'] ?>">
              <button class="btn btn-primary">Compare</button>
            </form>
          </div>
        </div>
      </div>
    <?php endforeach; endif; ?>
  </div>

  <!-- Pagination -->
  <?php if ($totalPages > 1): ?>
    <nav class="mt-4">
      <ul class="pagination">
        <li class="page-item <?= $page<=1?'disabled':'' ?>">
          <a class="page-link" href="?<?= keep_params(['page'=>$page-1], ['page']) ?>">Prev</a>
        </li>
        <?php
          $start = max(1, $page-2);
          $end   = min($totalPages, $page+2);
          for ($i=$start; $i<=$end; $i++): ?>
            <li class="page-item <?= $i===$page?'active':'' ?>">
              <a class="page-link" href="?<?= keep_params(['page'=>$i], ['page']) ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>
        <li class="page-item <?= $page>=$totalPages?'disabled':'' ?>">
          <a class="page-link" href="?<?= keep_params(['page'=>$page+1], ['page']) ?>">Next</a>
        </li>
      </ul>
      <div class="text-muted">Showing page <?= $page ?> of <?= $totalPages ?> (<?= $total ?> results)</div>
    </nav>
  <?php endif; ?>

</section>
</body>
</html>
