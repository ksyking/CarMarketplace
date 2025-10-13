<?php include __DIR__.'/../includes/header.php';
$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT c.*, u.name seller_name, u.id seller_id
                       FROM cars c JOIN users u ON u.id=c.seller_id WHERE c.id=?");
$stmt->execute([$id]); $car = $stmt->fetch();
if (!$car) { echo '<p>Not found</p>'; include __DIR__.'/../includes/footer.php'; exit; }

$imgs = $pdo->prepare("SELECT * FROM car_images WHERE car_id=? ORDER BY is_primary DESC, id");
$imgs->execute([$id]); $imgs = $imgs->fetchAll();

if ($_SERVER['REQUEST_METHOD']==='POST' && current_user() && current_user()['role']==='buyer') {
  $body = trim($_POST['body'] ?? '');
  if ($body) {
    $msg = $pdo->prepare('INSERT INTO messages(car_id,buyer_id,seller_id,body) VALUES(?,?,?,?)');
    $msg->execute([$id, current_user()['id'], $car['seller_id'], $body]);
    echo '<p>Message sent to seller</p>';
  }
}
?>
<h1><?= htmlspecialchars($car['title']) ?></h1>
<p>Price $<?= number_format($car['price'],2) ?> - Mileage <?= (int)$car['mileage'] ?></p>
<p>Tags: <?= htmlspecialchars($car['tags']) ?></p>
<div>
  <?php foreach ($imgs as $im): ?>
    <img src="/autotrade/public/<?= htmlspecialchars($im['path']) ?>" width="220">
  <?php endforeach; ?>
</div>

<?php if (current_user() && current_user()['role']==='buyer'): ?>
<h3>Contact Seller</h3>
<form method="post">
  <textarea name="body" required placeholder="Your message"></textarea>
  <button>Send</button>
</form>
<?php endif; ?>
<?php include __DIR__.'/../includes/footer.php'; ?>
