<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once(__DIR__ . "/includes/auth_check.php");
require_once(__DIR__ . "/config/DBConn.php");

header("Content-Type: application/json; charset=UTF-8");

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => "You must be logged in."
    ]);
    exit;
}

$conn = getConnection();
mysqli_set_charset($conn, "utf8mb4");

$userId = (int) $_SESSION["user_id"];

/*
|-------------------------------------------------
| Accept both movie_id and tmdb_id
|-------------------------------------------------
*/
$movieId = isset($_POST["movie_id"]) ? (int) $_POST["movie_id"] : 0;
$tmdbId = isset($_POST["tmdb_id"]) ? trim($_POST["tmdb_id"]) : "";

if ($movieId <= 0 && $tmdbId !== "") {
    $sql = "
        SELECT movie_id
        FROM mm_movies
        WHERE external_api_id = ?
        LIMIT 1
    ";
    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $tmdbId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);

        if ($row) {
            $movieId = (int) $row["movie_id"];
        }

        mysqli_stmt_close($stmt);
    }
}

if ($movieId <= 0) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Missing movie id."
    ]);
    exit;
}

/*
|-------------------------------------------------
| Get user's watchlist
|-------------------------------------------------
*/
$watchlistId = 0;

$watchlistSql = "
    SELECT watchlist_id
    FROM mm_watchlists
    WHERE user_id = ?
    LIMIT 1
";
$watchlistStmt = mysqli_prepare($conn, $watchlistSql);

if ($watchlistStmt) {
    mysqli_stmt_bind_param($watchlistStmt, "i", $userId);
    mysqli_stmt_execute($watchlistStmt);
    $watchlistResult = mysqli_stmt_get_result($watchlistStmt);
    $watchlistRow = mysqli_fetch_assoc($watchlistResult);

    if ($watchlistRow) {
        $watchlistId = (int) $watchlistRow["watchlist_id"];
    }

    mysqli_stmt_close($watchlistStmt);
}

if ($watchlistId <= 0) {
    echo json_encode([
        "success" => true,
        "message" => "Watchlist not found."
    ]);
    exit;
}

/*
|-------------------------------------------------
| Delete movie only from THIS user's watchlist
|-------------------------------------------------
*/
$deleteSql = "
    DELETE FROM mm_watchlist_items
    WHERE watchlist_id = ? AND movie_id = ?
";
$deleteStmt = mysqli_prepare($conn, $deleteSql);

if (!$deleteStmt) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Failed to remove movie from watchlist."
    ]);
    exit;
}

mysqli_stmt_bind_param($deleteStmt, "ii", $watchlistId, $movieId);

if (!mysqli_stmt_execute($deleteStmt)) {
    $dbError = mysqli_error($conn);
    mysqli_stmt_close($deleteStmt);

    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Failed to remove movie from watchlist: " . $dbError
    ]);
    exit;
}

$affected = mysqli_stmt_affected_rows($deleteStmt);
mysqli_stmt_close($deleteStmt);

echo json_encode([
    "success" => true,
    "message" => $affected > 0 ? "Movie removed from watchlist." : "Movie was not in watchlist.",
    "movie_id" => $movieId
]);
exit;