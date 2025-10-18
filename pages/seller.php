<?php
session_start();
require_once '../app/db.php'; // adjust path as needed

// Check if seller is logged in
//if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seller') {
  //  header('Location: ../login.php');
  //  exit();
//}

//$seller_id = $_SESSION['user_id'];

// Fetch all listings by this seller with primary image
$sql = "
    SELECT c.id, c.title, c.price, c.status, ci.path AS image_path
    FROM cars c
    LEFT JOIN car_images ci ON c.id = ci.car_id AND ci.is_primary = 1
    WHERE c.seller_id = ?
    ORDER BY c.created_at DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Seller Dashboard</title>
  <link rel="stylesheet" href="../assets/styles.css"> <!-- Optional CSS file -->
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 0;
      text-align: center;
    }

    header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: #f4f4f4;
      padding: 10px 20px;
    }

    header nav a {
      margin: 0 10px;
      text-decoration: none;
      color: #333;
    }

    .container {
      max-width: 900px;
      margin: 20px auto;
      text-align: left;
      padding: 20px;
      border: 1px solid #ccc;
      border-radius: 8px;
    }

    .add-btn {
      display: block;
      margin: 0 auto 20px;
      padding: 10px 20px;
      background: #007bff;
      color: #fff;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-size: 16px;
    }

    .listing {
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 1px solid #ddd;
      padding: 15px 0;
    }

    .listing img {
      width: 120px;
      height: 80px;
      object-fit: cover;
      border: 1px solid #aaa;
      border-radius: 5px;
    }

    .listing .actions button {
      margin: 5px;
      padding: 6px 12px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }

    .edit-btn {
      background-color: #28a745;
      color: white;
    }

    .delete-btn {
      background-color: #dc3545;
      color: white;
    }

    .status {
      font-weight: bold;
      color: #333;
    }

    footer {
      margin-top: 40px;
      font-size: 1.2em;
      font-weight: bold;
    }
  </style>
</head>
<body>

<header>
  <nav>
    <a href="../about.php">About</a>
    <a href="../contact.php">Contact Us</a>
    <a href="../signup.php">Sign Up</a>
    <a href="../login.php">Log In</a>
  </nav>
</header>

<div class="container">
  <button class="add-btn" onclick="window.location.href='seller_add.php'">Add New Listing</button>

  <?php if ($result->num_rows > 0): ?>
    <?php while ($row = $result->fetch_assoc()): ?>
      <div class="listing">
        <div class="info">
          <img src="<?= htmlspecialchars($row['image_path'] ?: '../assets/placeholder.png') ?>" alt="Car Image">
          <p><strong><?= htmlspecialchars($row['title']) ?></strong><br>
          $<?= number_format($row['price'], 2) ?></p>
        </div>

        <div class="status">
          Status: <?= ucfirst($row['status']) ?>
        </div>

        <div class="actions">
          <button class="edit-btn" onclick="window.location.href='seller_edit.php?id=<?= $row['id'] ?>'">Edit</button>
          <button class="delete-btn" onclick="if(confirm('Are you sure?')) window.location.href='seller_delete.php?id=<?= $row['id'] ?>'">Delete</button>
        </div>
      </div>
    <?php endwhile; ?>
  <?php else: ?>
    <p>No listings found. Click "Add New Listing" to create one.</p>
  <?php endif; ?>
</div>

<footer>Seller Page</footer>

</body>
</html>
