<?php
// deletes a user by ID and goes back to user list

require_once(__DIR__ . "/../includes/auth_check.php");
require_once(__DIR__ . "/../config/DBConn.php");

if ($_SESSION["role_name"] !== "admin") {
    die("Access denied.");
}

$conn = getConnection();

$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

if ($id > 0) {
    $stmt = $conn->prepare("DELETE FROM mm_users WHERE user_id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
    } else {
        error_log("Database error: " . $stmt->error);
    }
    $stmt->close();
}

header("Location: manage-users.php");
exit;
?>