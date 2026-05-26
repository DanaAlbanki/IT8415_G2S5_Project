<?php
// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include authentication and database connection
require_once(__DIR__ . "/includes/auth_check.php");
require_once(__DIR__ . "/config/DBConn.php");

// Open database connection
$conn = getConnection();
mysqli_set_charset($conn, "utf8mb4");

// Only allow comment deletion through POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit();
}

// Make sure only admins can delete comments
if (!isset($_SESSION["role_name"]) || $_SESSION["role_name"] !== "admin") {
    die("Only admin can delete comments.");
}

// Get admin ID, comment ID, movie ID, and redirect target from POST
$adminId = (int) $_SESSION["user_id"];
$commentId = (int) ($_POST["comment_id"] ?? 0);
$movieId = (int) ($_POST["movie_id"] ?? 0);
$redirectTo = isset($_POST["redirect_to"]) ? $_POST["redirect_to"] : "";

// Validate the submitted comment and movie IDs
if ($commentId <= 0 || $movieId <= 0) {
    die("Invalid request.");
}

// Get the movie external ID to redirect back to the movie page
$getMovieSql = "
    SELECT external_api_id
    FROM mm_movies
    WHERE movie_id = ?
    LIMIT 1
";

$getMovieStmt = mysqli_prepare($conn, $getMovieSql);
if (!$getMovieStmt) { die("Database error."); }

mysqli_stmt_bind_param($getMovieStmt, "i", $movieId);
mysqli_stmt_execute($getMovieStmt);
$getMovieResult = mysqli_stmt_get_result($getMovieStmt);
$movieRow = mysqli_fetch_assoc($getMovieResult);
mysqli_stmt_close($getMovieStmt);

if (!$movieRow) { die("Movie not found."); }

$tmdbId = trim($movieRow["external_api_id"] ?? "");

// Soft delete the comment instead of removing it permanently
$deleteSql = "
    UPDATE mm_comments
    SET
        comment_status = 'deleted',
        deleted_by = ?,
        deleted_at = CURRENT_TIMESTAMP
    WHERE comment_id = ? AND movie_id = ?
";

$deleteStmt = mysqli_prepare($conn, $deleteSql);
if (!$deleteStmt) { die("Database error."); }

mysqli_stmt_bind_param($deleteStmt, "iii", $adminId, $commentId, $movieId);

if (!mysqli_stmt_execute($deleteStmt)) {
    mysqli_stmt_close($deleteStmt);
    die("Failed to delete comment.");
}
mysqli_stmt_close($deleteStmt);

// Count remaining visible comments for this movie
$countResult = mysqli_query($conn,
    "SELECT COUNT(*) as cnt FROM mm_comments
     WHERE movie_id = $movieId AND comment_status = 'visible'"
);
$countRow = mysqli_fetch_assoc($countResult);
$newCount = (int)$countRow['cnt'];

// Update the stored visible comment count for the movie
$updateMovieStmt = mysqli_prepare($conn,
    "UPDATE mm_movies SET comment_count = ? WHERE movie_id = ?"
);
if ($updateMovieStmt) {
    mysqli_stmt_bind_param($updateMovieStmt, "ii", $newCount, $movieId);
    mysqli_stmt_execute($updateMovieStmt);
    mysqli_stmt_close($updateMovieStmt);
}

mysqli_close($conn);

// Redirect back to admin manage comments page if request came from admin
if ($redirectTo === "admin") {
    header("Location: admin/manage-comments.php");
    exit();
}

// Redirect back to the movie page when possible
if ($tmdbId !== "") {
    header("Location: movie.php?id=" . urlencode($tmdbId));
    exit();
}

header("Location: index.php");
exit();
?>