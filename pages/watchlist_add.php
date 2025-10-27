<?php
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/auth.php';

require_role('buyer');

$cid = (int)($_GET['id'] ?? 0);
if ($cid > 0) {
    $stmt = $pdo->prepare("INSERT IGNORE INTO watchlist(buyer_id, car_id) VALUES (?, ?)");
    $stmt->execute([current_user()['id'], $cid]);
}

header('Location: /autotrade/index.php');
exit;
