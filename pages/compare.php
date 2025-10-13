<?php include __DIR__.'/../includes/header.php';
$ids = $_SESSION['compare'] ?? [];
if (count($ids) < 2) { echo '<p>Select two cars on the homepage to compare.</p>'; include __DIR__.'/../includes/footer.php'; exit; }
$in = implode(',', array_map('intval',$ids));
$cars = $pdo->query("SELECT * FROM cars WHERE id IN ($in)")->fetchAll();
?>
<h1>Compare</h1>
<div class="compare">
  <?php foreach ($cars as $c): ?>
    <section>
      <h2><?= htmlspecialchars($c['title']) ?></h2>
      <p>Price $<?= number_format($c['price'],2) ?></p>
      <p>Mileage <?= (int)$c['mileage'] ?></p>
      <p>Year <?= (int)$c['year'] ?></p>
      <p>Tags <?= htmlspecialchars($c['tags']) ?></p>
      <a href="/autotrade/pages/car.php?id=<?= $c['id'] ?>">Car Detail</a>
    </section>
  <?php endforeach; ?>
</div>
<?php include __DIR__.'/../includes/footer.php'; ?>
