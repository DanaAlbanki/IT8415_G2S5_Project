<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once(__DIR__ . "/includes/auth_check.php");
require_once(__DIR__ . "/config/DBConn.php");

$conn = getConnection();
mysqli_set_charset($conn, "utf8mb4");

$userId = (int) $_SESSION["user_id"];

// Get watchlist info
$watchlistSql = "SELECT watchlist_id, watchlist_name FROM mm_watchlists WHERE user_id = ? LIMIT 1";
$watchlistStmt = mysqli_prepare($conn, $watchlistSql);
mysqli_stmt_bind_param($watchlistStmt, "i", $userId);
mysqli_stmt_execute($watchlistStmt);
$watchlistResult = mysqli_stmt_get_result($watchlistStmt);

$watchlist = mysqli_fetch_assoc($watchlistResult);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>My Watchlist</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 30px;
            background: #f7f7f7;
        }
        .movie-card {
            background: #fff;
            border: 1px solid #ddd;
            padding: 20px;
            margin-top: 20px;
        }
        .btn {
            padding: 8px 14px;
            border: none;
            cursor: pointer;
            margin-top: 10px;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
    </style>
</head>
<body>

<p>
    <a href="index.php">← Back to Home</a> |
    <a href="logout.php">Logout</a>
</p>

<h2>My Watchlist</h2>

<?php if (!$watchlist) { ?>
    <p>You do not have a watchlist yet.</p>
<?php } else { ?>

    <h3><?php echo htmlspecialchars($watchlist["watchlist_name"]); ?></h3>

    <?php
    $itemsSql = "
        SELECT 
            m.movie_id,
            m.title,
            m.short_description,
            m.release_date,
            m.poster_image,
            m.average_rating,
            m.view_count,
            wli.added_at
        FROM mm_watchlist_items wli
        INNER JOIN mm_movies m ON wli.movie_id = m.movie_id
        WHERE wli.watchlist_id = ? AND m.status = 'published'
        ORDER BY wli.added_at DESC
    ";
    $itemsStmt = mysqli_prepare($conn, $itemsSql);
    mysqli_stmt_bind_param($itemsStmt, "i", $watchlist["watchlist_id"]);
    mysqli_stmt_execute($itemsStmt);
    $itemsResult = mysqli_stmt_get_result($itemsStmt);
    ?>

    <?php if (mysqli_num_rows($itemsResult) > 0) { ?>
        <?php while ($movie = mysqli_fetch_assoc($itemsResult)) { ?>
            <div class="movie-card">
                <h3><?php echo htmlspecialchars($movie["title"]); ?></h3>

                <?php if (!empty($movie["poster_image"])) { ?>
                    <div style="margin:10px 0;">
                        <img src="uploads/posters/<?php echo htmlspecialchars($movie["poster_image"]); ?>" width="180" alt="Movie Poster">
                    </div>
                <?php } ?>

                <p><?php echo htmlspecialchars($movie["short_description"]); ?></p>

                <p>
                    <strong>Release Date:</strong>
                    <?php echo !empty($movie["release_date"]) ? htmlspecialchars($movie["release_date"]) : "N/A"; ?>
                </p>

                <p>
                    <strong>Average Rating:</strong>
                    <?php echo number_format((float)$movie["average_rating"], 2); ?>
                    |
                    <strong>Views:</strong>
                    <?php echo (int) $movie["view_count"]; ?>
                </p>

                <p>
                    <strong>Added To Watchlist:</strong>
                    <?php echo htmlspecialchars($movie["added_at"]); ?>
                </p>

                <p>
                    <a href="movie-details.php?id=<?php echo (int)$movie["movie_id"]; ?>">View More</a>
                </p>

                <form method="POST" action="remove-from-watchlist.php" onsubmit="return confirm('Remove this movie from your watchlist?');">
                    <input type="hidden" name="movie_id" value="<?php echo (int)$movie["movie_id"]; ?>">
                    <button type="submit" class="btn btn-danger">Remove</button>
                </form>
            </div>
        <?php } ?>
    <?php } else { ?>
        <p>Your watchlist is empty.</p>
    <?php } ?>

<?php } ?>

</body>
</html>