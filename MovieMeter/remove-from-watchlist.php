<?php
// Enable PHP error reporting but hide errors from users
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Include authentication check and database connection
require_once(__DIR__ . "/includes/auth_check.php");
require_once(__DIR__ . "/config/DBConn.php");

// Return response as JSON
header("Content-Type: application/json; charset=UTF-8");

// Send JSON response and stop script
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

// Get local movie ID using TMDB ID
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

// Open database connection
$conn = getConnection();
mysqli_set_charset($conn, "utf8mb4");

// Only allow POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    respondJson(false, "Invalid request method.", [], 405);
}

// Get current user and movie values
$userId = (int) $_SESSION["user_id"];
$movieId = (int) ($_POST["movie_id"] ?? 0);
$tmdbId = trim($_POST["tmdb_id"] ?? "");

// Use TMDB ID to find movie ID if movie ID was not sent
if ($movieId <= 0 && $tmdbId !== "") {
    $movieId = getMovieIdFromTmdb($conn, $tmdbId);
}

// Validate movie ID
if ($movieId <= 0) {
    respondJson(false, "Missing movie id.", [], 422);
}

$watchlistId = 0;

// Get the user's watchlist
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

// Return success if the user has no watchlist to remove from
if ($watchlistId <= 0) {
    mysqli_close($conn);

    respondJson(true, "Watchlist not found.", [
        "movie_id" => $movieId
    ]);
}

// Remove movie from watchlist
$deleteSql = "
    DELETE FROM mm_watchlist_items
    WHERE watchlist_id = ? AND movie_id = ?
";

$deleteStmt = mysqli_prepare($conn, $deleteSql);

if (!$deleteStmt) {
    respondJson(false, "Failed to remove movie from watchlist.", [], 500);
}

mysqli_stmt_bind_param($deleteStmt, "ii", $watchlistId, $movieId);

// Execute delete query
if (!mysqli_stmt_execute($deleteStmt)) {
    mysqli_stmt_close($deleteStmt);
    mysqli_close($conn);

    respondJson(false, "Failed to remove movie from watchlist.", [], 500);
}

// Check whether any row was actually removed
$affected = mysqli_stmt_affected_rows($deleteStmt);

mysqli_stmt_close($deleteStmt);
mysqli_close($conn);

// Return final remove result
respondJson(true, $affected > 0 ? "Movie removed from watchlist." : "Movie was not in watchlist.", [
    "movie_id" => $movieId
]);
?>