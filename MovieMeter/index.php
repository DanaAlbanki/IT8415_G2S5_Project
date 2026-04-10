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
<p>
    <a href="my-watchlist.php">My Watchlist</a> |
    <a href="logout.php">Logout</a>
</p>
<h3>Latest Movies</h3>
<div class="container my-4">
    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body">
            <h3 class="mb-3">Find Movies</h3>

            <form method="GET" action="search.php" class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Title</label>
                    <input type="text" name="title" class="form-control" placeholder="Search by title">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Creator</label>
                    <input type="text" name="creator" class="form-control" placeholder="Search by creator">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Date From</label>
                    <input type="date" name="date_from" class="form-control">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Date To</label>
                    <input type="date" name="date_to" class="form-control">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Popularity</label>
                    <select name="popularity" class="form-select">
                        <option value="">Default</option>
                        <option value="rating_high">Highest Rating</option>
                        <option value="rating_count">Most Rated</option>
                        <option value="views">Most Viewed</option>
                        <option value="comments">Most Commented</option>
                        <option value="newest">Newest Release</option>
                        <option value="oldest">Oldest Release</option>
                    </select>
                </div>

                <div class="col-md-2 d-grid">
                    <label class="form-label invisible">Search</label>
                    <button type="submit" class="btn btn-primary">Search</button>
                </div>
            </form>
        </div>
    </div>
</div>

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