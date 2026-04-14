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

if ($tmdbId === "") {
    respondJson(false, "Invalid movie.", [], 422);
}

$movieId = getMovieIdFromTmdb($conn, $tmdbId);

if ($movieId <= 0) {
    respondJson(false, "Movie not found.", [], 404);
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
    $watchlistName = "My Watchlist";
    $now = date("Y-m-d H:i:s");

    $createSql = "
        INSERT INTO mm_watchlists (user_id, watchlist_name, created_at)
        VALUES (?, ?, ?)
    ";

    $createStmt = mysqli_prepare($conn, $createSql);

    if (!$createStmt) {
        respondJson(false, "Failed to create watchlist.", [], 500);
    }

    mysqli_stmt_bind_param($createStmt, "iss", $userId, $watchlistName, $now);

    if (!mysqli_stmt_execute($createStmt)) {
        mysqli_stmt_close($createStmt);
        respondJson(false, "Failed to create watchlist.", [], 500);
    }

    $watchlistId = (int) mysqli_insert_id($conn);
    mysqli_stmt_close($createStmt);
}

$checkSql = "
    SELECT 1
    FROM mm_watchlist_items
    WHERE watchlist_id = ? AND movie_id = ?
    LIMIT 1
";

$checkStmt = mysqli_prepare($conn, $checkSql);

if (!$checkStmt) {
    respondJson(false, "Database error.", [], 500);
}

mysqli_stmt_bind_param($checkStmt, "ii", $watchlistId, $movieId);
mysqli_stmt_execute($checkStmt);
$checkResult = mysqli_stmt_get_result($checkStmt);

if ($checkResult && mysqli_num_rows($checkResult) > 0) {
    mysqli_stmt_close($checkStmt);
    mysqli_close($conn);

    respondJson(true, "Movie already in watchlist.", [
        "movie_id" => $movieId
    ]);
}

mysqli_stmt_close($checkStmt);

$now = date("Y-m-d H:i:s");

$insertSql = "
    INSERT INTO mm_watchlist_items (watchlist_id, movie_id, added_at)
    VALUES (?, ?, ?)
";

$insertStmt = mysqli_prepare($conn, $insertSql);

if (!$insertStmt) {
    respondJson(false, "Failed to add movie to watchlist.", [], 500);
}

mysqli_stmt_bind_param($insertStmt, "iis", $watchlistId, $movieId, $now);

if (!mysqli_stmt_execute($insertStmt)) {
    mysqli_stmt_close($insertStmt);
    mysqli_close($conn);

    respondJson(false, "Failed to add movie to watchlist.", [], 500);
}

mysqli_stmt_close($insertStmt);
mysqli_close($conn);

respondJson(true, "Movie added to watchlist.", [
    "movie_id" => $movieId
]);
?>