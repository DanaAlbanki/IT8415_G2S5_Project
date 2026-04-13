<?php
require_once(__DIR__ . "/../includes/auth_check.php");

// Allow only creator
if ($_SESSION["role_name"] !== "creator") {
    die("Access denied.");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Creator Dashboard</title>
</head>
<body>
    <h2>Welcome Creator, <?php echo htmlspecialchars($_SESSION["full_name"]); ?></h2>
    <p>You have creator access.</p>
    <p>Role: <?php echo htmlspecialchars($_SESSION["role_name"]); ?></p>

    <a href="logout.php">Logout</a>
</body>
</html>