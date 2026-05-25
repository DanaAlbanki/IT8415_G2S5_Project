<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once(__DIR__ . "/includes/auth_check.php");
require_once(__DIR__ . "/config/DBConn.php");

header("Content-Type: application/json; charset=UTF-8");

function respondJson($success, $message, $extra = [], $statusCode = 200)
{
    http_response_code($statusCode);

    echo json_encode(
        array_merge([
            "success" => $success,
            "message" => $message
        ], $extra),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    exit();
}

// Gets the local movie ID using the TMDB external ID
function getMovieIdFromTmdb($conn, $tmdbId)
{
    $sql = "SELECT movie_id FROM mm_movies WHERE external_api_id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) return 0;

    mysqli_stmt_bind_param($stmt, "s", $tmdbId);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    return $row ? (int)$row["movie_id"] : 0;
}

$conn = getConnection();
mysqli_set_charset($conn, "utf8mb4");

// Only allow rating requests through POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    respondJson(false, "Invalid request method.", [], 405);
}

$userId = (int) $_SESSION["user_id"];
$tmdbId = trim($_POST["tmdb_id"] ?? "");
$ratingValue = (int) ($_POST["rating_value"] ?? 0);

// Validate submitted movie and rating values
if ($tmdbId === "") {
    respondJson(false, "Invalid movie.", [], 422);
}

if ($ratingValue < 1 || $ratingValue > 5) {
    respondJson(false, "Rating must be between 1 and 5.", [], 422);
}

$movieId = getMovieIdFromTmdb($conn, $tmdbId);

if ($movieId <= 0) {
    respondJson(false, "Movie not found.", [], 404);
}

// Check whether the user already rated this movie
$checkSql = "
    SELECT movie_id
    FROM mm_ratings
    WHERE movie_id = ? AND user_id = ?
    LIMIT 1
";

$checkStmt = mysqli_prepare($conn, $checkSql);
mysqli_stmt_bind_param($checkStmt, "ii", $movieId, $userId);
mysqli_stmt_execute($checkStmt);

$result = mysqli_stmt_get_result($checkStmt);

if (mysqli_num_rows($result) > 0) {
    // Update the existing rating
    $updateSql = "
        UPDATE mm_ratings
        SET rating_value = ?, rated_at = CURRENT_TIMESTAMP
        WHERE movie_id = ? AND user_id = ?
    ";

    $updateStmt = mysqli_prepare($conn, $updateSql);
    mysqli_stmt_bind_param($updateStmt, "iii", $ratingValue, $movieId, $userId);

    if (!mysqli_stmt_execute($updateStmt)) {
        respondJson(false, "Failed to update rating.", [], 500);
    }

    mysqli_stmt_close($updateStmt);
} else {
    // Add a new rating
    $insertSql = "
        INSERT INTO mm_ratings (movie_id, user_id, rating_value)
        VALUES (?, ?, ?)
    ";

    $insertStmt = mysqli_prepare($conn, $insertSql);
    mysqli_stmt_bind_param($insertStmt, "iii", $movieId, $userId, $ratingValue);

    if (!mysqli_stmt_execute($insertStmt)) {
        respondJson(false, "Failed to add rating.", [], 500);
    }

    mysqli_stmt_close($insertStmt);
}

mysqli_stmt_close($checkStmt);

// Get updated rating summary
$summarySql = "
    SELECT
        COALESCE(AVG(r.rating_value), 0) AS average_rating,
        COUNT(*) AS rating_count
    FROM mm_ratings r
    WHERE r.movie_id = ?
";

$summaryStmt = mysqli_prepare($conn, $summarySql);
mysqli_stmt_bind_param($summaryStmt, "i", $movieId);
mysqli_stmt_execute($summaryStmt);

$summaryResult = mysqli_stmt_get_result($summaryStmt);
$summaryRow = mysqli_fetch_assoc($summaryResult);

mysqli_stmt_close($summaryStmt);

// Get updated visible comment count
$commentSql = "
    SELECT COUNT(*) AS comment_count
    FROM mm_comments
    WHERE movie_id = ?
      AND comment_status = 'visible'
";

$commentStmt = mysqli_prepare($conn, $commentSql);
mysqli_stmt_bind_param($commentStmt, "i", $movieId);
mysqli_stmt_execute($commentStmt);

$commentResult = mysqli_stmt_get_result($commentStmt);
$commentRow = mysqli_fetch_assoc($commentResult);

mysqli_stmt_close($commentStmt);

$summary = [
    "average_rating" => $summaryRow ? (float)$summaryRow["average_rating"] : 0,
    "rating_count" => $summaryRow ? (int)$summaryRow["rating_count"] : 0,
    "comment_count" => $commentRow ? (int)$commentRow["comment_count"] : 0
];

mysqli_close($conn);

// Return the updated rating and summary data
respondJson(true, "Rating submitted successfully.", [
    "user_rating" => $ratingValue,
    "summary" => $summary
]);
?>