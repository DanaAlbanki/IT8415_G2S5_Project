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
    $sql = "
        SELECT movie_id
        FROM mm_movies
        WHERE external_api_id = ?
        LIMIT 1
    ";

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
$movieId = (int) ($_POST["movie_id"] ?? 0);
$tmdbId = trim($_POST["tmdb_id"] ?? "");

if ($movieId <= 0 && $tmdbId !== "") {
    $movieId = getMovieIdFromTmdb($conn, $tmdbId);
}

if ($movieId <= 0) {
    respondJson(false, "Missing movie id.", [], 422);
}

$watchlistId = 0;

$watchlistSql = "
    SELECT watchlist_id
    FROM mm_watchlists
    WHERE user_id = ?
    LIMIT 1
";

$watchlistStmt = mysqli_prepare($conn, $watchlistSql);

if (!$watchlistStmt) {
    respondJson(false, "Database error.", [], 500);
}

mysqli_stmt_bind_param($watchlistStmt, "i", $userId);
mysqli_stmt_execute($watchlistStmt);
$watchlistResult = mysqli_stmt_get_result($watchlistStmt);
$watchlistRow = mysqli_fetch_assoc($watchlistResult);
mysqli_stmt_close($watchlistStmt);

if ($watchlistRow) {
    $watchlistId = (int) $watchlistRow["watchlist_id"];
}

if ($watchlistId <= 0) {
    mysqli_close($conn);

    respondJson(true, "Watchlist not found.", [
        "movie_id" => $movieId
    ]);
}

$deleteSql = "
    DELETE FROM mm_watchlist_items
    WHERE watchlist_id = ? AND movie_id = ?
";

$deleteStmt = mysqli_prepare($conn, $deleteSql);

if (!$deleteStmt) {
    respondJson(false, "Failed to remove movie from watchlist.", [], 500);
}

mysqli_stmt_bind_param($deleteStmt, "ii", $watchlistId, $movieId);

if (!mysqli_stmt_execute($deleteStmt)) {
    mysqli_stmt_close($deleteStmt);
    mysqli_close($conn);

    respondJson(false, "Failed to remove movie from watchlist.", [], 500);
}

$affected = mysqli_stmt_affected_rows($deleteStmt);
mysqli_stmt_close($deleteStmt);
mysqli_close($conn);

respondJson(true, $affected > 0 ? "Movie removed from watchlist." : "Movie was not in watchlist.", [
    "movie_id" => $movieId
]);
?>