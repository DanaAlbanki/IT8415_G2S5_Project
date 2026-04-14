<?php
require_once(__DIR__ . "/../includes/auth_check.php");
require_once(__DIR__ . "/../config/DBConn.php");

if ($_SESSION["role_name"] !== "admin") die("Access denied.");

$conn=getConnection();
$id=$_GET["id"];

mysqli_query($conn,"DELETE FROM mm_comments WHERE comment_id=$id");

header("Location: manage-comments.php");
exit;