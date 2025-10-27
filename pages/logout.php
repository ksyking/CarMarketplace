<?php
require_once __DIR__ . '/../app/db.php';
session_destroy();
header('Location: /autotrade/index.php');
exit;
