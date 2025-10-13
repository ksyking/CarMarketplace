<?php include __DIR__.'/../includes/header.php'; require_role('seller'); $sid=current_user()['id'];

if ($_SERVER['REQUEST_METHOD']==='POST') {
  $stmt=$pdo->prepare("INSERT INTO cars(seller_id,title,make,model,year,price,mileage,description,tags,status)
                       VALUES(?,?,?,?,?,?,?,?,?,'active')");
  $stmt->execute([$sid, $_POST['title'], $_POST['make'], $_POST['model'], (int)$_POST['year'],
                  (float)$_POST['price'], (int)$_POST['mileage'], $_POST['description'], $_POST['tags']]);
  $carId = $pdo->lastInsertId();

  if (!empty($_FILES['image']['name'])) {
    $ok = ['image/jpeg','image/png','image/webp'];
    if (in_array($_FILES['image']['type'],$ok) && $_FILES['image']['size']<=5*1024*1024) {
      $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
      $name = 'uploads/'.uniqid('car_').'.'.$ext;
      move_uploaded_file($_FILES['image']['tmp_name'], __DIR__.'/../public/'.$name);
      $pdo->prepare("INSERT INTO car_images(car_id,path,is_primary) VALUES(?,?,1)")->execute([$carId,$name]);
    }
  }
  header('Location: /autotrade/pages/seller.php'); exit;
}
?>
<h1>Add New Listing</h1>
<form method="post" enctype="multipart/form-data">
  <input name="title" placeholder="Car Name" required>
  <input name="make" placeholder="Make">
  <input name="model" placeholder="Model">
  <input name="year" type="number" placeholder="Year">
  <input name="price" type="number" step="0.01" placeholder="Price">
  <input name="mileage" type="number" placeholder="Mileage">
  <input name="tags" placeholder="Tags, comma separated">
  <textarea name="description" placeholder="Details"></textarea>
  <input type="file" name="image" accept="image/*">
  <button>Save</button>
</form>
<?php include __DIR__.'/../includes/footer.php'; ?>
