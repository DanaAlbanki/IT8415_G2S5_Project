<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once(__DIR__ . "/includes/auth_check.php");
require_once(__DIR__ . "/config/DBConn.php");

header("Content-Type: application/json; charset=UTF-8");

// Sends a JSON response and stops the script
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

    if (!$stmt) {
        return 0;
    }

    mysqli_stmt_bind_param($stmt, "s", $tmdbId);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    return $row ? (int)$row["movie_id"] : 0;
}

$conn = getConnection();
mysqli_set_charset($conn, "utf8mb4");

// Only allow POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    respondJson(false, "Invalid request method.", [], 405);
}

$userId = (int)($_SESSION["user_id"] ?? 0);
$tmdbId = trim($_POST["tmdb_id"] ?? "");

// Make sure the user is logged in
if ($userId <= 0) {
    respondJson(false, "You must be logged in.", [], 401);
}

// Validate the selected movie
if ($tmdbId === "") {
    respondJson(false, "Invalid movie.", [], 422);
}

$movieId = getMovieIdFromTmdb($conn, $tmdbId);

if ($movieId <= 0) {
    respondJson(false, "Movie not found.", [], 404);
}

$watchlistId = 0;

// Look for the user's existing watchlist
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
    $watchlistId = (int)$watchlistRow["watchlist_id"];
}

// Create a watchlist if the user does not have one yet
if ($watchlistId <= 0) {
    $createSql = "INSERT INTO mm_watchlists (user_id) VALUES (?)";
    $createStmt = mysqli_prepare($conn, $createSql);

    if (!$createStmt) {
        respondJson(false, "Failed to create watchlist.", [], 500);
    }

    mysqli_stmt_bind_param($createStmt, "i", $userId);

    if (!mysqli_stmt_execute($createStmt)) {
        mysqli_stmt_close($createStmt);
        mysqli_close($conn);
        respondJson(false, "Failed to create watchlist.", [], 500);
    }

    $watchlistId = (int)mysqli_insert_id($conn);
    mysqli_stmt_close($createStmt);
}

// Check if the movie already exists in the watchlist
$checkSql = "
    SELECT 1
    FROM mm_watchlist_items
    WHERE watchlist_id = ? AND movie_id = ?
    LIMIT 1
";

$checkStmt = mysqli_prepare($conn, $checkSql);

if (!$checkStmt) {
    mysqli_close($conn);
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

// Add the movie to the user's watchlist
$insertSql = "
    INSERT INTO mm_watchlist_items (watchlist_id, movie_id)
    VALUES (?, ?)
";

$insertStmt = mysqli_prepare($conn, $insertSql);

if (!$insertStmt) {
    mysqli_close($conn);
    respondJson(false, "Failed to add movie to watchlist.", [], 500);
}

mysqli_stmt_bind_param($insertStmt, "ii", $watchlistId, $movieId);

if (!mysqli_stmt_execute($insertStmt)) {
    mysqli_stmt_close($insertStmt);
    mysqli_close($conn);
    respondJson(false, "Failed to add movie to watchlist.", [], 500);
}

mysqli_stmt_close($insertStmt);
mysqli_close($conn);

// Return success after adding the movie
respondJson(true, "Movie added to watchlist.", [
    "movie_id" => $movieId
]);
?>