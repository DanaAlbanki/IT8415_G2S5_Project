<?php
require_once(__DIR__ . "/auth_check.php");

// Allow only viewer role
if ($_SESSION["role_name"] !== "viewer") {
    die("Access denied.");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Viewer Home</title>
</head>
<body>
    <h2>Welcome, <?php echo htmlspecialchars($_SESSION["full_name"]); ?></h2>

    <p>You are logged in successfully.</p>
    <p>User ID: <?php echo htmlspecialchars($_SESSION["user_id"]); ?></p>
    <p>Username: <?php echo htmlspecialchars($_SESSION["username"]); ?></p>
    <p>Email: <?php echo htmlspecialchars($_SESSION["email"]); ?></p>
    <p>Role: <?php echo htmlspecialchars($_SESSION["role_name"]); ?></p>

    <a href="logout.php">Logout</a>
</body>
</html>