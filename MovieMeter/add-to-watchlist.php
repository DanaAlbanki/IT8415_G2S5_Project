<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once(__DIR__ . "/includes/auth_check.php");
require_once(__DIR__ . "/config/DBConn.php");

$conn = getConnection();
mysqli_set_charset($conn, "utf8mb4");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit();
}

$userId = (int) $_SESSION["user_id"];
$movieId = isset($_POST["movie_id"]) ? (int) $_POST["movie_id"] : 0;

if ($movieId <= 0) {
    die("Invalid movie.");
}

// Check movie exists and is published
$movieSql = "SELECT movie_id FROM mm_movies WHERE movie_id = ? AND status = 'published' LIMIT 1";
$movieStmt = mysqli_prepare($conn, $movieSql);
mysqli_stmt_bind_param($movieStmt, "i", $movieId);
mysqli_stmt_execute($movieStmt);
$movieResult = mysqli_stmt_get_result($movieStmt);

if (mysqli_num_rows($movieResult) === 0) {
    die("Movie not found or not published.");
}

// Get user's watchlist, or create one if it doesn't exist
$watchlistSql = "SELECT watchlist_id FROM mm_watchlists WHERE user_id = ? LIMIT 1";
$watchlistStmt = mysqli_prepare($conn, $watchlistSql);
mysqli_stmt_bind_param($watchlistStmt, "i", $userId);
mysqli_stmt_execute($watchlistStmt);
$watchlistResult = mysqli_stmt_get_result($watchlistStmt);

if ($watchlistRow = mysqli_fetch_assoc($watchlistResult)) {
    $watchlistId = (int) $watchlistRow["watchlist_id"];
} else {
    $createSql = "INSERT INTO mm_watchlists (user_id, watchlist_name) VALUES (?, ?)";
    $createStmt = mysqli_prepare($conn, $createSql);
    $defaultName = "My Watchlist";
    mysqli_stmt_bind_param($createStmt, "is", $userId, $defaultName);

    if (!mysqli_stmt_execute($createStmt)) {
        die("Failed to create watchlist: " . mysqli_error($conn));
    }

    $watchlistId = mysqli_insert_id($conn);
}

// Check if movie already exists in watchlist
$checkItemSql = "
    SELECT watchlist_id, movie_id
    FROM mm_watchlist_items
    WHERE watchlist_id = ? AND movie_id = ?
    LIMIT 1
";
$checkItemStmt = mysqli_prepare($conn, $checkItemSql);
mysqli_stmt_bind_param($checkItemStmt, "ii", $watchlistId, $movieId);
mysqli_stmt_execute($checkItemStmt);
$checkItemResult = mysqli_stmt_get_result($checkItemStmt);

if (mysqli_num_rows($checkItemResult) === 0) {
    $insertItemSql = "INSERT INTO mm_watchlist_items (watchlist_id, movie_id) VALUES (?, ?)";
    $insertItemStmt = mysqli_prepare($conn, $insertItemSql);
    mysqli_stmt_bind_param($insertItemStmt, "ii", $watchlistId, $movieId);

    if (!mysqli_stmt_execute($insertItemStmt)) {
        die("Failed to add movie to watchlist: " . mysqli_error($conn));
    }
}

header("Location: movie.php?id=" . $movieId);
exit();
?>