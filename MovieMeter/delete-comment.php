<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once(__DIR__ . "/includes/auth_check.php");
require_once(__DIR__ . "/config/DBConn.php");

$conn = getConnection();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit();
}

if (!isset($_SESSION["user_id"]) || !isset($_SESSION["role_name"])) {
    die("Access denied.");
}

if ($_SESSION["role_name"] !== "admin") {
    die("Only admin can delete comments.");
}

$adminId = (int) $_SESSION["user_id"];
$commentId = isset($_POST["comment_id"]) ? (int) $_POST["comment_id"] : 0;
$movieId = isset($_POST["movie_id"]) ? (int) $_POST["movie_id"] : 0;

if ($commentId <= 0 || $movieId <= 0) {
    die("Invalid request.");
}

$deleteSql = "
    UPDATE mm_comments
    SET 
        comment_status = 'deleted',
        deleted_by = ?,
        deleted_at = CURRENT_TIMESTAMP
    WHERE comment_id = ?
";
$deleteStmt = mysqli_prepare($conn, $deleteSql);
mysqli_stmt_bind_param($deleteStmt, "ii", $adminId, $commentId);

if (!mysqli_stmt_execute($deleteStmt)) {
    die("Failed to delete comment: " . mysqli_error($conn));
}

// Update visible comment count
$updateMovieSql = "
    UPDATE mm_movies
    SET comment_count = (
        SELECT COUNT(*)
        FROM mm_comments
        WHERE movie_id = ? AND comment_status = 'visible'
    )
    WHERE movie_id = ?
";
$updateMovieStmt = mysqli_prepare($conn, $updateMovieSql);
mysqli_stmt_bind_param($updateMovieStmt, "ii", $movieId, $movieId);
mysqli_stmt_execute($updateMovieStmt);

header("Location: movie.php?id=" . $movieId);
exit();
?>