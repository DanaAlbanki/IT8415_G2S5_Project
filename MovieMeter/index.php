<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once(__DIR__ . "/includes/auth_check.php");
require_once(__DIR__ . "/config/DBConn.php");

// Allow only viewer role
if ($_SESSION["role_name"] !== "viewer") {
    die("Access denied.");
}

$conn = getConnection();

$query = "
SELECT 
    m.movie_id,
    m.title,
    m.short_description,
    m.release_date,
    m.poster_image,
    m.trailer_url,
    m.status,
    m.published_at
FROM mm_movies m
WHERE m.status = 'published'
ORDER BY m.published_at DESC, m.created_at DESC
LIMIT 10
";

$result = mysqli_query($conn, $query);

if (!$result) {
    die('SQL Error: ' . mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Home - MovieMeter</title>
</head>
<body>

<h2>Welcome, <?php echo htmlspecialchars($_SESSION["full_name"]); ?></h2>
<p><a href="logout.php">Logout</a></p>

<h3>Latest Movies</h3>

<?php if (mysqli_num_rows($result) > 0) { ?>
    <?php while ($row = mysqli_fetch_assoc($result)) { ?>
        <div style="border:1px solid #ccc; padding:15px; margin:15px 0;">

            <h4><?php echo htmlspecialchars($row["title"]); ?></h4>

            <p>
                <?php echo htmlspecialchars($row["short_description"]); ?>
            </p>

            <?php if (!empty($row["poster_image"])) { ?>
                <div style="margin:10px 0;">
                    <img src="uploads/posters/<?php echo htmlspecialchars($row["poster_image"]); ?>" width="180" alt="Movie Poster">
                </div>
            <?php } ?>

            <?php if (!empty($row["trailer_url"])) { ?>
                <div style="margin:10px 0;">
                    <video width="320" controls>
                        <source src="uploads/trailers/<?php echo htmlspecialchars($row["trailer_url"]); ?>" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                </div>
            <?php } ?>

            <p>
                <strong>Release Date:</strong>
                <?php echo htmlspecialchars($row["release_date"]); ?>
            </p>

            <a href="movie-details.php?id=<?php echo (int)$row["movie_id"]; ?>">
                View More
            </a>
        </div>
    <?php } ?>
<?php } else { ?>
    <p>No published movies found.</p>
<?php } ?>

</body>
</html>