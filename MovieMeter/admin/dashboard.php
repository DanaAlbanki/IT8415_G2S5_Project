<?php
require_once(__DIR__ . "/../includes/auth_check.php");

// Allow only admin
if ($_SESSION["role_name"] !== "admin") {
    die("Access denied.");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
</head>
<body>
    <h2>Welcome Admin, <?php echo htmlspecialchars($_SESSION["full_name"]); ?></h2>
    <p>You have admin access.</p>
    <p>Role: <?php echo htmlspecialchars($_SESSION["role_name"]); ?></p>

    <a href="logout.php">Logout</a>
</body>
</html>