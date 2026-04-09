<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once(__DIR__ . "/includes/auth_check.php");
require_once(__DIR__ . "/config/DBConn.php");

// Allow only viewer role
if ($_SESSION["role_name"] !== "viewer") {
    die("Access denied.");
}

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    die("Invalid movie ID.");
}

$movieId = (int) $_GET["id"];
$conn = getConnection();

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
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Movie Details</title>
</head>
<body>

<p>
    <a href="index.php">← Back to Home</a> |
    <a href="logout.php">Logout</a>
</p>

<?php if ($movie) { ?>
    <div style="border:1px solid #ccc; padding:20px; margin-top:20px;">

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
    </div>
<?php } else { ?>
    <p>Movie not found or not published.</p>
<?php } ?>

</body>
</html>