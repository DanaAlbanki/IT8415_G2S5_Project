<?php
//Deletes a specific user from the database based on the provided ID and redirects the administrator back to the user management page.

require_once(__DIR__ . "/../includes/auth_check.php");
require_once(__DIR__ . "/../config/DBConn.php");

// Ensure only admins can perform delete operations
if ($_SESSION["role_name"] !== "admin") {
    die("Access denied.");
}

$conn = getConnection();

// 1. Sanitize the ID: Ensure it is treated strictly as an integer to prevent injection
$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

if ($id > 0) {
    // 2. Use a prepared statement to prevent SQL Injection
    // The placeholder (?) ensures the database treats the input only as a value, never as a command.
    $stmt = $conn->prepare("DELETE FROM mm_users WHERE user_id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        // Deletion successful
    } else {
        // Log error if deletion fails
        error_log("Database error: " . $stmt->error);
    }
    $stmt->close();
}

// 3. Always redirect after a state-changing operation
header("Location: manage-users.php");
exit;
?>