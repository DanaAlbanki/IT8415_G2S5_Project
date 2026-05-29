<?php
// deletes a comment and redirects back

require_once(__DIR__ . "/../includes/auth_check.php");
require_once(__DIR__ . "/../config/DBConn.php");

if ($_SESSION["role_name"] !== "admin") {
    die("Access denied.");
}

$conn = getConnection();

$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

if ($id > 0) {

    $stmt = mysqli_prepare($conn,
    "DELETE FROM mm_comments WHERE comment_id = ?");

    mysqli_stmt_bind_param($stmt, "i", $id);

    mysqli_stmt_execute($stmt);

    mysqli_stmt_close($stmt);
}

header("Location: manage-comments.php");
exit();
?>