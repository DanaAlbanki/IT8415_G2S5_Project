<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once(__DIR__ . "/includes/auth_check.php");
require_once(__DIR__ . "/config/DBConn.php");

if ($_SESSION["role_name"] !== "viewer" && $_SESSION["role_name"] !== "admin") {
    die("Access denied.");
}

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    die("Invalid movie ID.");
}

$movieId = (int) $_GET["id"];
$userId = (int) $_SESSION["user_id"];
$roleName = $_SESSION["role_name"];

$conn = getConnection();
mysqli_set_charset($conn, "utf8mb4");

// Get movie
$query = "
SELECT 
    m.movie_id,
    m.title,
    m.short_description,
    m.full_description,
    m.release_date,
    m.poster_image,
    m.trailer_url,
    m.average_rating,
    m.rating_count,
    m.comment_count,
    m.view_count,
    m.status,
    m.created_at,
    m.published_at
FROM mm_movies m
WHERE m.movie_id = ? AND m.status = 'published'
LIMIT 1
";

$stmt = mysqli_prepare($conn, $query);

if (!$stmt) {
    die("Prepare Error: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "i", $movieId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result) {
    die("SQL Error: " . mysqli_error($conn));
}

$movie = mysqli_fetch_assoc($result);

// Get current user's rating
$userRating = 0;
$ratingSql = "SELECT rating_value FROM mm_ratings WHERE movie_id = ? AND user_id = ? LIMIT 1";
$ratingStmt = mysqli_prepare($conn, $ratingSql);
mysqli_stmt_bind_param($ratingStmt, "ii", $movieId, $userId);
mysqli_stmt_execute($ratingStmt);
$ratingResult = mysqli_stmt_get_result($ratingStmt);

if ($ratingRow = mysqli_fetch_assoc($ratingResult)) {
    $userRating = (int) $ratingRow["rating_value"];
}

// Get user's watchlist id
$watchlistId = 0;
$watchlistSql = "SELECT watchlist_id FROM mm_watchlists WHERE user_id = ? LIMIT 1";
$watchlistStmt = mysqli_prepare($conn, $watchlistSql);
mysqli_stmt_bind_param($watchlistStmt, "i", $userId);
mysqli_stmt_execute($watchlistStmt);
$watchlistResult = mysqli_stmt_get_result($watchlistStmt);

if ($watchlistRow = mysqli_fetch_assoc($watchlistResult)) {
    $watchlistId = (int) $watchlistRow["watchlist_id"];
}

// Check if this movie is in watchlist
$isInWatchlist = false;
if ($watchlistId > 0) {
    $checkWatchSql = "
        SELECT watchlist_id, movie_id
        FROM mm_watchlist_items
        WHERE watchlist_id = ? AND movie_id = ?
        LIMIT 1
    ";
    $checkWatchStmt = mysqli_prepare($conn, $checkWatchSql);
    mysqli_stmt_bind_param($checkWatchStmt, "ii", $watchlistId, $movieId);
    mysqli_stmt_execute($checkWatchStmt);
    $checkWatchResult = mysqli_stmt_get_result($checkWatchStmt);

    $isInWatchlist = mysqli_num_rows($checkWatchResult) > 0;
}

// Get comments
$commentsSql = "
SELECT 
    c.comment_id,
    c.comment_text,
    c.created_at,
    u.full_name
FROM mm_comments c
INNER JOIN mm_users u ON c.user_id = u.user_id
WHERE c.movie_id = ? AND c.comment_status = 'visible'
ORDER BY c.created_at DESC
";
$commentsStmt = mysqli_prepare($conn, $commentsSql);
mysqli_stmt_bind_param($commentsStmt, "i", $movieId);
mysqli_stmt_execute($commentsStmt);
$commentsResult = mysqli_stmt_get_result($commentsStmt);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Movie Details</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 30px;
            background: #f7f7f7;
        }
        .box {
            background: #fff;
            border: 1px solid #ddd;
            padding: 20px;
            margin-top: 20px;
        }
        .comment {
            border-bottom: 1px solid #ddd;
            padding: 12px 0;
        }
        textarea {
            width: 100%;
            min-height: 100px;
        }
        .btn {
            padding: 8px 14px;
            border: none;
            cursor: pointer;
            margin-top: 10px;
            margin-right: 8px;
        }
        .btn-primary {
            background: #007bff;
            color: white;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn-success {
            background: #198754;
            color: white;
        }
        select, textarea {
            padding: 8px;
            margin-top: 8px;
        }
    </style>
</head>
<body>

<p>
    <a href="index.php">← Back to Home</a> |
    <a href="my-watchlist.php">My Watchlist</a> |
    <a href="logout.php">Logout</a>
</p>

<?php if ($movie) { ?>
    <div class="box">
        <h2><?php echo htmlspecialchars($movie["title"]); ?></h2>

        <?php if (!empty($movie["poster_image"])) { ?>
            <div style="margin:15px 0;">
                <img src="uploads/posters/<?php echo htmlspecialchars($movie["poster_image"]); ?>" width="250" alt="Movie Poster">
            </div>
        <?php } ?>

        <p>
            <strong>Short Description:</strong><br>
            <?php echo htmlspecialchars($movie["short_description"]); ?>
        </p>

        <p>
            <strong>Full Description:</strong><br>
            <?php echo nl2br(htmlspecialchars($movie["full_description"])); ?>
        </p>

        <p>
            <strong>Release Date:</strong>
            <?php echo !empty($movie["release_date"]) ? htmlspecialchars($movie["release_date"]) : "N/A"; ?>
        </p>

        <?php if (!empty($movie["trailer_url"])) { ?>
            <h3>Trailer / Media</h3>
            <video width="500" controls>
                <source src="uploads/trailers/<?php echo htmlspecialchars($movie["trailer_url"]); ?>" type="video/mp4">
                Your browser does not support the video tag.
            </video>
        <?php } ?>

        <h3>Movie Statistics</h3>
        <p><strong>Average Rating:</strong> <?php echo number_format((float)$movie["average_rating"], 2); ?></p>
        <p><strong>Rating Count:</strong> <?php echo (int)$movie["rating_count"]; ?></p>
        <p><strong>Comment Count:</strong> <?php echo (int)$movie["comment_count"]; ?></p>
        <p><strong>View Count:</strong> <?php echo (int)$movie["view_count"]; ?></p>

        <p>
            <strong>Published At:</strong>
            <?php echo !empty($movie["published_at"]) ? htmlspecialchars($movie["published_at"]) : "Not published yet"; ?>
        </p>

        <?php if ($isInWatchlist) { ?>
            <form method="POST" action="remove-from-watchlist.php">
                <input type="hidden" name="movie_id" value="<?php echo (int)$movie["movie_id"]; ?>">
                <button type="submit" class="btn btn-danger">Remove from Watchlist</button>
            </form>
        <?php } else { ?>
            <form method="POST" action="add-to-watchlist.php">
                <input type="hidden" name="movie_id" value="<?php echo (int)$movie["movie_id"]; ?>">
                <button type="submit" class="btn btn-success">Add to Watchlist</button>
            </form>
        <?php } ?>
    </div>

    <div class="box">
        <h3>Rate This Movie</h3>
        <form method="POST" action="add-rating.php">
            <input type="hidden" name="movie_id" value="<?php echo (int)$movie["movie_id"]; ?>">

            <label for="rating_value"><strong>Your Rating:</strong></label><br>
            <select name="rating_value" id="rating_value" required>
                <option value="">Select rating</option>
                <option value="1" <?php echo ($userRating === 1) ? "selected" : ""; ?>>1</option>
                <option value="2" <?php echo ($userRating === 2) ? "selected" : ""; ?>>2</option>
                <option value="3" <?php echo ($userRating === 3) ? "selected" : ""; ?>>3</option>
                <option value="4" <?php echo ($userRating === 4) ? "selected" : ""; ?>>4</option>
                <option value="5" <?php echo ($userRating === 5) ? "selected" : ""; ?>>5</option>
            </select><br>

            <button type="submit" class="btn btn-primary">
                <?php echo ($userRating > 0) ? "Update Rating" : "Submit Rating"; ?>
            </button>
        </form>
    </div>

    <div class="box">
        <h3>Add Comment</h3>
        <form method="POST" action="add-comment.php">
            <input type="hidden" name="movie_id" value="<?php echo (int)$movie["movie_id"]; ?>">

            <textarea name="comment_text" maxlength="1000" required placeholder="Write your comment here..."></textarea><br>
            <button type="submit" class="btn btn-primary">Post Comment</button>
        </form>
    </div>

    <div class="box">
        <h3>Comments</h3>

        <?php if (mysqli_num_rows($commentsResult) > 0) { ?>
            <?php while ($comment = mysqli_fetch_assoc($commentsResult)) { ?>
                <div class="comment">
                    <p>
                        <strong><?php echo htmlspecialchars($comment["full_name"]); ?></strong><br>
                        <small><?php echo htmlspecialchars($comment["created_at"]); ?></small>
                    </p>

                    <p><?php echo nl2br(htmlspecialchars($comment["comment_text"])); ?></p>

                    <?php if ($roleName === "admin") { ?>
                        <form method="POST" action="delete-comment.php" onsubmit="return confirm('Are you sure you want to delete this comment?');">
                            <input type="hidden" name="comment_id" value="<?php echo (int)$comment["comment_id"]; ?>">
                            <input type="hidden" name="movie_id" value="<?php echo (int)$movie["movie_id"]; ?>">
                            <button type="submit" class="btn btn-danger">Delete Comment</button>
                        </form>
                    <?php } ?>
                </div>
            <?php } ?>
        <?php } else { ?>
            <p>No comments yet.</p>
        <?php } ?>
    </div>

<?php } else { ?>
    <p>Movie not found or not published.</p>
<?php } ?>

</body>
</html>