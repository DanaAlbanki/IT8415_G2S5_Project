<?php
require_once(__DIR__ . "/../includes/auth_check.php");
require_once(__DIR__ . "/../config/DBConn.php");

if ($_SESSION["role_name"] !== "admin") die("Access denied.");

$conn = getConnection();
$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

if ($id > 0) {
    // 1. Prepare and execute the delete
    $stmt = $conn->prepare("DELETE FROM mm_comments WHERE comment_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    // 2. CRITICAL: Close the statement before moving on
    $stmt->close();
}

// 3. Now perform the redirect
header("Location: manage-comments.php");
exit; // Always use exit after header()
?>