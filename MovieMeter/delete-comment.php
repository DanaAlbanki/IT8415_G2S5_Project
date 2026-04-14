<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once(__DIR__ . "/includes/auth_check.php");
require_once(__DIR__ . "/config/DBConn.php");

$conn = getConnection();
mysqli_set_charset($conn, "utf8mb4");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit();
}

if (!isset($_SESSION["role_name"]) || $_SESSION["role_name"] !== "admin") {
    die("Only admin can delete comments.");
}

$adminId = (int) $_SESSION["user_id"];
$commentId = (int) ($_POST["comment_id"] ?? 0);
$movieId = (int) ($_POST["movie_id"] ?? 0);

if ($commentId <= 0 || $movieId <= 0) {
    die("Invalid request.");
}

$getMovieSql = "
    SELECT external_api_id
    FROM mm_movies
    WHERE movie_id = ?
    LIMIT 1
";

$getMovieStmt = mysqli_prepare($conn, $getMovieSql);

if (!$getMovieStmt) {
    die("Database error.");
}

mysqli_stmt_bind_param($getMovieStmt, "i", $movieId);
mysqli_stmt_execute($getMovieStmt);
$getMovieResult = mysqli_stmt_get_result($getMovieStmt);
$movieRow = mysqli_fetch_assoc($getMovieResult);
mysqli_stmt_close($getMovieStmt);

if (!$movieRow) {
    die("Movie not found.");
}

$tmdbId = trim($movieRow["external_api_id"] ?? "");

$deleteSql = "
    UPDATE mm_comments
    SET 
        comment_status = 'deleted',
        deleted_by = ?,
        deleted_at = CURRENT_TIMESTAMP
    WHERE comment_id = ? AND movie_id = ?
";

$deleteStmt = mysqli_prepare($conn, $deleteSql);

if (!$deleteStmt) {
    die("Database error.");
}

mysqli_stmt_bind_param($deleteStmt, "iii", $adminId, $commentId, $movieId);

if (!mysqli_stmt_execute($deleteStmt)) {
    mysqli_stmt_close($deleteStmt);
    die("Failed to delete comment.");
}

mysqli_stmt_close($deleteStmt);

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

if ($updateMovieStmt) {
    mysqli_stmt_bind_param($updateMovieStmt, "ii", $movieId, $movieId);
    mysqli_stmt_execute($updateMovieStmt);
    mysqli_stmt_close($updateMovieStmt);
}

mysqli_close($conn);

if ($tmdbId !== "") {
    header("Location: movie.php?id=" . urlencode($tmdbId));
    exit();
}

header("Location: index.php");
exit();
?>