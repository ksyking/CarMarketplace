<?php
function current_user() { return $_SESSION['user'] ?? null; }
function require_login() {
  if (!current_user()) { header('Location: /autotrade/pages/login.php'); exit; }
}
function require_role($role) {
  require_login();
  if ($_SESSION['user']['role'] !== $role) { http_response_code(403); exit('Forbidden'); }
}
