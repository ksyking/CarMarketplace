<?php
include __DIR__ . '/../includes/header.php';

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $u = $stmt->fetch();

    if ($u && password_verify($pass, $u['password_hash'])) {
        $_SESSION['user'] = [
            'id'    => $u['id'],
            'name'  => $u['name'],
            'role'  => $u['role'],
            'email' => $u['email']
        ];
        header('Location: /autotrade/index.php');
        exit;
    } else {
        $error = 'Invalid email or password';
    }
}
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
</style>

<div class="at-container">
  <div class="at-card">
    <div class="at-title">Log in</div>

    <?php if ($error): ?>
      <div class="at-alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form method="post" autocomplete="on" novalidate>
      <input class="at-field" id="email" name="email" type="email" placeholder="Email" required>
      <input class="at-field" id="password" name="password" type="password" placeholder="Password" required>
      <button class="at-btn" type="submit">Log in</button>
    </form>

    <div class="at-muted">
      Don’t have an account?
      <a href="/autotrade/signup.php" style="color:var(--at-blue); text-decoration:none;">Sign up</a>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
