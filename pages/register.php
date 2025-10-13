<?php include __DIR__.'/../includes/header.php';
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $role = in_array($_POST['role'], ['buyer','seller']) ? $_POST['role'] : 'buyer';
  $name = trim($_POST['name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $pass = $_POST['password'] ?? '';

  if ($name && filter_var($email, FILTER_VALIDATE_EMAIL) && strlen($pass) >= 6) {
    $stmt = $pdo->prepare('INSERT INTO users(role,name,email,password_hash) VALUES(?,?,?,?)');
    $stmt->execute([$role,$name,$email,password_hash($pass, PASSWORD_DEFAULT)]);
    echo '<p>Account created. <a href="login.php">Log in</a></p>';
  } else {
    echo '<p>Invalid input</p>';
  }
}
?>
<h1>Sign up</h1>
<form method="post">
  <label><input type="radio" name="role" value="buyer" checked> Buyer</label>
  <label><input type="radio" name="role" value="seller"> Seller</label>
  <input name="name" placeholder="Name" required>
  <input name="email" type="email" placeholder="Email" required>
  <input name="password" type="password" placeholder="Password (min 6)" required>
  <button>Create account</button>
</form>
<?php include __DIR__.'/../includes/footer.php'; ?>
