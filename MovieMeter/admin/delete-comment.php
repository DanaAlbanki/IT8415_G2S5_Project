<?php
//This code allow admin to delete the comments created by the users
require_once(__DIR__ . "/../includes/auth_check.php");
require_once(__DIR__ . "/../config/DBConn.php");

if ($_SESSION["role_name"] !== "admin") die("Access denied.");

$conn = getConnection();
$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

if ($id > 0) {
    $stmt = $conn->prepare("DELETE FROM mm_comments WHERE comment_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    $stmt->close();
}

header("Location: manage-comments.php");
exit; 
?>