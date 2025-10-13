<?php include __DIR__.'/../includes/header.php';
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $email = trim($_POST['email'] ?? '');
  $pass  = $_POST['password'] ?? '';
  $stmt = $pdo->prepare('SELECT * FROM users WHERE email=?');
  $stmt->execute([$email]);
  $u = $stmt->fetch();
  if ($u && password_verify($pass, $u['password_hash'])) {
    $_SESSION['user'] = ['id'=>$u['id'],'name'=>$u['name'],'role'=>$u['role'],'email'=>$u['email']];
    header('Location: /autotrade/index.php'); exit;
  } else { echo '<p>Invalid login</p>'; }
}
?>
<h1>Log in</h1>
<form method="post">
  <input name="email" type="email" placeholder="Email" required>
  <input name="password" type="password" placeholder="Password" required>
  <button>Log in</button>
</form>
<?php include __DIR__.'/../includes/footer.php'; ?>
