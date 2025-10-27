<?php
// Do logic BEFORE any HTML output so redirects work
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/auth.php';

$err = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $role = in_array($_POST['role'] ?? '', ['buyer','seller']) ? $_POST['role'] : 'buyer';
  $name = trim($_POST['name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $pass = $_POST['password'] ?? '';

  if (!$name || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 6) {
    $err = 'Please provide name, a valid email, and a password of at least 6 characters.';
  } else {
    try {
      $stmt = $pdo->prepare('INSERT INTO users(role,name,email,password_hash) VALUES(?,?,?,?)');
      $stmt->execute([$role, $name, $email, password_hash($pass, PASSWORD_DEFAULT)]);
      // Success → go to login
      header('Location: /autotrade/pages/login.php?created=1');
      exit;
    } catch (PDOException $e) {
      // MySQL duplicate email error code = 1062
      if (isset($e->errorInfo[1]) && $e->errorInfo[1] == 1062) {
        $err = 'That email is already registered. Try logging in.';
      } else {
        $err = 'Signup failed. Please try again.';
      }
    }
  }
}

// Now safe to output HTML
include __DIR__ . '/../includes/header.php';
?>

<style>
  :root{
    --at-bg:#f5f6f8;
    --at-card:#ffffff;
    --at-border:#e5e7eb;
    --at-text:#111827;
    --at-muted:#6b7280;
    --at-blue:#0d6efd;
    --at-blue-600:#0b5ed7;
    --at-radius:12px;
  }
  body{background:var(--at-bg);}
  .at-container{
    max-width: 460px;
    margin: 48px auto 64px auto;
    padding: 0 16px;
  }
  .at-card{
    background:var(--at-card);
    border:1px solid var(--at-border);
    border-radius: var(--at-radius);
    box-shadow: 0 1px 2px rgba(0,0,0,0.03), 0 8px 24px rgba(0,0,0,0.06);
    padding: 28px;
  }
  .at-title{
    font-size: 28px;
    font-weight: 600;
    letter-spacing: 0.3px;
    margin: 4px 0 20px 0;
    text-align:center;
  }
  .at-field{
    display:block;
    width:100%;
    margin: 10px 0 14px 0;
    padding: 12px 14px;
    border:1px solid var(--at-border);
    border-radius: 10px;
    background:#fff;
    font-size:16px;
    outline:none;
  }
  .at-field:focus{
    border-color: var(--at-blue);
    box-shadow: 0 0 0 3px rgba(13,110,253,0.15);
  }
  .at-btn{
    display:inline-block;
    width:100%;
    padding: 12px 16px;
    border:0;
    border-radius: 10px;
    background: var(--at-blue);
    color:#fff;
    font-weight:600;
    font-size:16px;
    cursor:pointer;
  }
  .at-btn:hover{ background: var(--at-blue-600); }
  .at-muted{
    color: var(--at-muted);
    font-size: 14px;
    text-align:center;
    margin-top: 14px;
  }
  .at-alert{
    border:1px solid #fecaca;
    background:#fef2f2;
    color:#991b1b;
    padding:10px 12px;
    border-radius:10px;
    font-size:14px;
    margin-bottom:14px;
  }
  .at-radio {
    display:flex; gap:12px; margin: 6px 0 12px;
  }
  .at-radio label { font-size:14px; color:var(--at-text); }
</style>

<div class="at-container">
  <div class="at-card">
    <div class="at-title">Sign up</div>

    <?php if ($err): ?>
      <div class="at-alert"><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form method="post" novalidate>
      <div class="at-radio">
        <label><input type="radio" name="role" value="buyer" <?= (!isset($_POST['role']) || $_POST['role']==='buyer') ? 'checked' : '' ?>> Buyer</label>
        <label><input type="radio" name="role" value="seller" <?= (isset($_POST['role']) && $_POST['role']==='seller') ? 'checked' : '' ?>> Seller</label>
      </div>

      <input class="at-field" name="name" placeholder="Name" value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name'], ENT_QUOTES, 'UTF-8') : '' ?>" required>
      <input class="at-field" name="email" type="email" placeholder="Email" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8') : '' ?>" required>
      <input class="at-field" name="password" type="password" placeholder="Password (min 6)" required>

      <button class="at-btn" type="submit">Create account</button>
    </form>

    <div class="at-muted">
      Already have an account?
      <a href="/autotrade/pages/login.php" style="color:var(--at-blue); text-decoration:none;">Log in</a>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
