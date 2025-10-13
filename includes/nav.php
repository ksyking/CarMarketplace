<nav>
  <a href="/autotrade/index.php">Home</a>
  <a href="/autotrade/pages/compare.php">Compare</a>
  <?php if (current_user() && current_user()['role']==='seller'): ?>
    <a href="/autotrade/pages/seller.php">Seller</a>
  <?php endif; ?>
  <?php if (current_user() && current_user()['role']==='buyer'): ?>
    <a href="/autotrade/pages/buyer.php">Buyer</a>
  <?php endif; ?>
  <?php if (current_user()): ?>
    <span>Hello, <?= htmlspecialchars(current_user()['name']) ?></span>
    <a href="/autotrade/pages/logout.php">Log out</a>
  <?php else: ?>
    <a href="/autotrade/pages/login.php">Log in</a>
    <a href="/autotrade/pages/register.php">Sign up</a>
  <?php endif; ?>
</nav>
