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

function getMovieIdFromTmdb($conn, $tmdbId)
{
    $sql = "SELECT movie_id FROM mm_movies WHERE external_api_id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        return 0;
    }

    mysqli_stmt_bind_param($stmt, "s", $tmdbId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    return $row ? (int) $row["movie_id"] : 0;
}

$conn = getConnection();
mysqli_set_charset($conn, "utf8mb4");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    respondJson(false, "Invalid request method.", [], 405);
}

$userId = (int) $_SESSION["user_id"];
$tmdbId = trim($_POST["tmdb_id"] ?? "");
$commentText = trim($_POST["comment_text"] ?? "");

if ($tmdbId === "") {
    respondJson(false, "Invalid movie.", [], 422);
}

if ($commentText === "") {
    respondJson(false, "Comment cannot be empty.", [], 422);
}

if (mb_strlen($commentText) > 1000) {
    respondJson(false, "Comment is too long.", [], 422);
}

$movieId = getMovieIdFromTmdb($conn, $tmdbId);

if ($movieId <= 0) {
    respondJson(false, "Movie not found.", [], 404);
}

$insertSql = "
    INSERT INTO mm_comments (movie_id, user_id, comment_text, comment_status)
    VALUES (?, ?, ?, 'visible')
";

$insertStmt = mysqli_prepare($conn, $insertSql);

if (!$insertStmt) {
    respondJson(false, "Database error.", [], 500);
}

mysqli_stmt_bind_param($insertStmt, "iis", $movieId, $userId, $commentText);

if (!mysqli_stmt_execute($insertStmt)) {
    mysqli_stmt_close($insertStmt);
    respondJson(false, "Failed to add comment.", [], 500);
}

mysqli_stmt_close($insertStmt);

$summarySql = "
    SELECT
        COALESCE((
            SELECT AVG(r.rating_value)
            FROM mm_ratings r
            WHERE r.movie_id = ?
        ), 0) AS average_rating,
        (
            SELECT COUNT(*)
            FROM mm_ratings r
            WHERE r.movie_id = ?
        ) AS rating_count,
        (
            SELECT COUNT(*)
            FROM mm_comments c
            WHERE c.movie_id = ?
              AND c.comment_status = 'visible'
        ) AS comment_count
";

$summaryStmt = mysqli_prepare($conn, $summarySql);

if (!$summaryStmt) {
    respondJson(false, "Database error.", [], 500);
}

mysqli_stmt_bind_param($summaryStmt, "iii", $movieId, $movieId, $movieId);
mysqli_stmt_execute($summaryStmt);
$summaryResult = mysqli_stmt_get_result($summaryStmt);
$summaryRow = mysqli_fetch_assoc($summaryResult);
mysqli_stmt_close($summaryStmt);

$summary = [
    "average_rating" => $summaryRow ? (float) $summaryRow["average_rating"] : 0,
    "rating_count" => $summaryRow ? (int) $summaryRow["rating_count"] : 0,
    "comment_count" => $summaryRow ? (int) $summaryRow["comment_count"] : 0
];

$commentsSql = "
    SELECT
        c.comment_id,
        c.comment_text,
        c.created_at,
        COALESCE(NULLIF(u.full_name, ''), NULLIF(u.username, ''), 'User') AS display_name
    FROM mm_comments c
    INNER JOIN mm_users u ON c.user_id = u.user_id
    WHERE c.movie_id = ?
      AND c.comment_status = 'visible'
    ORDER BY c.created_at DESC, c.comment_id DESC
";

$commentsStmt = mysqli_prepare($conn, $commentsSql);

if (!$commentsStmt) {
    respondJson(false, "Database error.", [], 500);
}

mysqli_stmt_bind_param($commentsStmt, "i", $movieId);
mysqli_stmt_execute($commentsStmt);
$commentsResult = mysqli_stmt_get_result($commentsStmt);

$comments = [];

while ($row = mysqli_fetch_assoc($commentsResult)) {
    $comments[] = $row;
}

mysqli_stmt_close($commentsStmt);
mysqli_close($conn);

respondJson(true, "Comment added successfully.", [
    "summary" => $summary,
    "comments" => $comments
]);
?>