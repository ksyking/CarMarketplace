<?php include __DIR__.'/../includes/header.php'; require_role('seller');
$sid = current_user()['id'];
$cars = $pdo->prepare('SELECT * FROM cars WHERE seller_id=? ORDER BY created_at DESC');
$cars->execute([$sid]); $cars = $cars->fetchAll();
?>
<h1>Seller Page</h1>
<p><a href="seller_add.php">Add New Listing</a></p>
<table>
<tr><th>Title</th><th>Status</th><th>Actions</th></tr>
<?php foreach ($cars as $c): ?>
<tr>
  <td><?= htmlspecialchars($c['title']) ?></td>
  <td><?= htmlspecialchars($c['status']) ?></td>
  <td>
    <a href="seller_edit.php?id=<?= $c['id'] ?>">Edit</a>
    <a href="seller.php?toggle=<?= $c['id'] ?>">Toggle</a>
    <a href="seller.php?del=<?= $c['id'] ?>" onclick="return confirm('Delete?')">Delete</a>
  </td>
</tr>
<?php endforeach; ?>
</table>
<?php
if (isset($_GET['toggle'])) {
  $id=(int)$_GET['toggle']; $pdo->prepare("UPDATE cars SET status=IF(status='active','inactive','active') WHERE id=? AND seller_id=?")->execute([$id,$sid]);
  header('Location: seller.php'); exit;
}
if (isset($_GET['del'])) {
  $id=(int)$_GET['del']; $pdo->prepare("DELETE FROM cars WHERE id=? AND seller_id=?")->execute([$id,$sid]);
  header('Location: seller.php'); exit;
}
include __DIR__.'/../includes/footer.php';
