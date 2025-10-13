<?php include __DIR__.'/../includes/header.php'; require_role('buyer');
$uid=current_user()['id'];
$stmt=$pdo->prepare("SELECT c.* FROM watchlist w JOIN cars c ON c.id=w.car_id WHERE w.buyer_id=? ORDER BY w.created_at DESC");
$stmt->execute([$uid]); $saved=$stmt->fetchAll();
?>
<h1>Buyer Page</h1>
<h3>Watchlist</h3>
<ul>
<?php foreach ($saved as $c): ?>
  <li><a href="/autotrade/pages/car.php?id=<?= $c['id'] ?>"><?= htmlspecialchars($c['title']) ?></a>
      - $<?= number_format($c['price'],2) ?> - <?= (int)$c['mileage'] ?> miles</li>
<?php endforeach; ?>
</ul>
<?php include __DIR__.'/../includes/footer.php'; ?>
