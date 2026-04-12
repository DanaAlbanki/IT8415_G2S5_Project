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
$ratingValue = isset($_POST["rating_value"]) ? (int) $_POST["rating_value"] : 0;

if ($movieId <= 0) {
    die("Invalid movie.");
}

if ($ratingValue < 1 || $ratingValue > 5) {
    die("Rating must be between 1 and 5.");
}

// Make sure movie exists and is published
$checkMovieSql = "SELECT movie_id FROM mm_movies WHERE movie_id = ? AND status = 'published' LIMIT 1";
$checkMovieStmt = mysqli_prepare($conn, $checkMovieSql);
mysqli_stmt_bind_param($checkMovieStmt, "i", $movieId);
mysqli_stmt_execute($checkMovieStmt);
$checkMovieResult = mysqli_stmt_get_result($checkMovieStmt);

if (mysqli_num_rows($checkMovieResult) === 0) {
    die("Movie not found or not published.");
}

// Since one rating per user per movie is allowed,
// update if exists, otherwise insert.
$checkRatingSql = "SELECT movie_id FROM mm_ratings WHERE movie_id = ? AND user_id = ? LIMIT 1";
$checkRatingStmt = mysqli_prepare($conn, $checkRatingSql);
mysqli_stmt_bind_param($checkRatingStmt, "ii", $movieId, $userId);
mysqli_stmt_execute($checkRatingStmt);
$checkRatingResult = mysqli_stmt_get_result($checkRatingStmt);

if (mysqli_num_rows($checkRatingResult) > 0) {
    $updateSql = "UPDATE mm_ratings SET rating_value = ?, rated_at = CURRENT_TIMESTAMP WHERE movie_id = ? AND user_id = ?";
    $updateStmt = mysqli_prepare($conn, $updateSql);
    mysqli_stmt_bind_param($updateStmt, "iii", $ratingValue, $movieId, $userId);

    if (!mysqli_stmt_execute($updateStmt)) {
        die("Failed to update rating: " . mysqli_error($conn));
    }
} else {
    $insertSql = "INSERT INTO mm_ratings (movie_id, user_id, rating_value) VALUES (?, ?, ?)";
    $insertStmt = mysqli_prepare($conn, $insertSql);
    mysqli_stmt_bind_param($insertStmt, "iii", $movieId, $userId, $ratingValue);

    if (!mysqli_stmt_execute($insertStmt)) {
        die("Failed to add rating: " . mysqli_error($conn));
    }
}

// Recalculate movie rating summary
$summarySql = "
    UPDATE mm_movies
    SET 
        average_rating = (
            SELECT IFNULL(AVG(rating_value), 0)
            FROM mm_ratings
            WHERE movie_id = ?
        ),
        rating_count = (
            SELECT COUNT(*)
            FROM mm_ratings
            WHERE movie_id = ?
        )
    WHERE movie_id = ?
";
$summaryStmt = mysqli_prepare($conn, $summarySql);
mysqli_stmt_bind_param($summaryStmt, "iii", $movieId, $movieId, $movieId);
mysqli_stmt_execute($summaryStmt);

header("Location: movie.php?id=" . $movieId);
exit();
?>