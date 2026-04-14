<?php
require_once("../includes/auth_check.php");

if ($_SESSION["role_name"] !== "admin") die("Access denied.");
?>

<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

<?php include("../includes/admin_nav.php"); ?>

<div class="admin-container">

<h1 class="admin-title">Admin Settings</h1>

<div class="admin-card">

<p><strong>Name:</strong> <?php echo $_SESSION["full_name"]; ?></p>
<p><strong>Email:</strong> <?php echo $_SESSION["email"]; ?></p>
<p><strong>Role:</strong> <?php echo $_SESSION["role_name"]; ?></p>

<br>

<a href="../logout.php" class="btn btn-delete">Logout</a>

</div>

</div>
</body>
</html>