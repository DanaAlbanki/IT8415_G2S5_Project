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

if (!isset($_SESSION["user_id"])) {
    die("You must be logged in.");
}

$userId = (int) $_SESSION["user_id"];
$movieId = isset($_POST["movie_id"]) ? (int) $_POST["movie_id"] : 0;
$commentText = isset($_POST["comment_text"]) ? trim($_POST["comment_text"]) : "";

if ($movieId <= 0) {
    die("Invalid movie.");
}

if ($commentText === "") {
    die("Comment cannot be empty.");
}

if (mb_strlen($commentText) > 1000) {
    die("Comment is too long.");
}

// Check movie exists and is published
$checkMovieSql = "SELECT movie_id FROM mm_movies WHERE movie_id = ? AND status = 'published' LIMIT 1";
$checkMovieStmt = mysqli_prepare($conn, $checkMovieSql);
mysqli_stmt_bind_param($checkMovieStmt, "i", $movieId);
mysqli_stmt_execute($checkMovieStmt);
$checkMovieResult = mysqli_stmt_get_result($checkMovieStmt);

if (mysqli_num_rows($checkMovieResult) === 0) {
    die("Movie not found or not published.");
}

$insertSql = "
    INSERT INTO mm_comments (movie_id, user_id, comment_text, comment_status)
    VALUES (?, ?, ?, 'visible')
";
$insertStmt = mysqli_prepare($conn, $insertSql);
mysqli_stmt_bind_param($insertStmt, "iis", $movieId, $userId, $commentText);

if (!mysqli_stmt_execute($insertStmt)) {
    die("Failed to add comment: " . mysqli_error($conn));
}

// Update movie comment count
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

header("Location: movie-details.php?id=" . $movieId);
exit();
?>