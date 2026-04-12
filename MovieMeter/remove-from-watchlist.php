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

// Get user's watchlist
$watchlistSql = "SELECT watchlist_id FROM mm_watchlists WHERE user_id = ? LIMIT 1";
$watchlistStmt = mysqli_prepare($conn, $watchlistSql);
mysqli_stmt_bind_param($watchlistStmt, "i", $userId);
mysqli_stmt_execute($watchlistStmt);
$watchlistResult = mysqli_stmt_get_result($watchlistStmt);

if ($watchlistRow = mysqli_fetch_assoc($watchlistResult)) {
    $watchlistId = (int) $watchlistRow["watchlist_id"];

    $deleteSql = "DELETE FROM mm_watchlist_items WHERE watchlist_id = ? AND movie_id = ?";
    $deleteStmt = mysqli_prepare($conn, $deleteSql);
    mysqli_stmt_bind_param($deleteStmt, "ii", $watchlistId, $movieId);

    if (!mysqli_stmt_execute($deleteStmt)) {
        die("Failed to remove movie from watchlist: " . mysqli_error($conn));
    }
}

header("Location: movie.php?id=" . $movieId);
exit();
?>