<?php
// a page to display the details (name,comments, rating,etc.) of a selected movie
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once(__DIR__ . "/../includes/auth_check.php");
require_once("../config/DBConn.php");

if ($_SESSION["role_name"] !== "creator") {
    die("Access denied.");
}

$dbc = getConnection();

$movie_id = isset($_GET['movie_id']) ? (int)$_GET['movie_id'] : 0;
$creator_id = $_SESSION['user_id'];

$sql = "
    SELECT *
    FROM mm_movies
    WHERE movie_id = $movie_id
    AND creator_id = $creator_id
";

$result = mysqli_query($dbc, $sql);
$movie = mysqli_fetch_assoc($result);

if (!$movie) {
    die("Movie not found.");
}

$watchlist_sql = "
    SELECT COUNT(*) AS watchlist_count
    FROM mm_watchlist_items
    WHERE movie_id = $movie_id
";

$watchlist_result = mysqli_query($dbc, $watchlist_sql);
$watchlist_row = mysqli_fetch_assoc($watchlist_result);
$watchlist_count = $watchlist_row['watchlist_count'] ?? 0;

$comments_sql = "
    SELECT comment_text, created_at
    FROM mm_comments
    WHERE movie_id = $movie_id
    ORDER BY created_at DESC
";

$comments_result = mysqli_query($dbc, $comments_sql);

$media_sql = "
    SELECT media_id, file_path
    FROM mm_movie_media
    WHERE movie_id = $movie_id
    ORDER BY media_id DESC
";

$media_result = mysqli_query($dbc, $media_sql);
?>

<!DOCTYPE html>
<html>
<head>
<title>Movie Details</title>
</head>
<link rel="stylesheet" href="../assets/css/movie-details.css">

<body>

<div class="navbar">
    <div class="logo">
        <img src="../assets/images/logo.png">
    </div>

  <div class="nav-links">
        <a href="../creator/dashboard.php" class="active-link">Dashboard</a>
        <a href="../creator/my-movie.php">My Movies</a>
        <a href="../creator/add-movie.php">Add Movie</a>
        <a href="../creator/import-movies.php">Import Movies</a>
        <a href="../creator/profile.php">Profile</a>
        <a href="../logout.php">Logout</a>
    </div>
</div>
    
<div class="top-back">
      <a href="movie_details.php?movie_id=<?php echo $movie_id; ?>"
       class="back-link"
       onclick="event.preventDefault(); goBack();">
        Back
    </a>
</div>
<div class="container">

    <div>

        <?php
        $poster = $movie['poster_image'] ?? '';

        if (!empty($poster)) {
            $poster_path = (strpos($poster, 'http') === 0)
                ? $poster
                : '../' . $poster;
        } else {
            $poster_path = '../assets/images/placeholder.png';
        }
        ?>

        <img class="poster"
             src="<?php echo htmlspecialchars($poster_path); ?>"
             onerror="this.src='../assets/images/placeholder.png';">

        <div class="details-box">
            <?php if (!empty($movie['short_description'])) { ?>
                <div class="stat">
                    <div class="label">Short Description</div>
                    <div class="small">
                        <?php echo htmlspecialchars($movie['short_description']); ?>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>

    <div class="right-box">

        <div class="stat">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:10px;">

                <div class="value" style="font-size:1.8rem;">
                    <?php echo htmlspecialchars($movie['title'] ?? 'No Title'); ?>
                </div>

                <div style="display:flex; gap:10px;">

                    <a href="edit-movie.php?movie_id=<?php echo $movie_id; ?>"
                       style="background:#facc15;color:#1f1229;padding:8px 12px;border-radius:8px;">
                        Edit
                    </a>

                    <a href="delete-movie.php?movie_id=<?php echo $movie_id; ?>"
                       style="background:#ef4444;color:white;padding:8px 12px;border-radius:8px;">
                        Delete
                    </a>

                </div>

            </div>
        </div>

        <?php if (!empty($movie['full_description'])) { ?>
            <div class="stat">
                <div class="label">Description</div>
                <div class="small">
                    <?php echo htmlspecialchars($movie['full_description']); ?>
                </div>
            </div>
        <?php } ?>

        <div class="stat">
            <div class="label">Trailer</div>

            <?php if (!empty($movie['trailer_url'])) { ?>
                <a href="<?php echo htmlspecialchars($movie['trailer_url']); ?>" target="_blank">
                    Watch Trailer
                </a>
            <?php } else { ?>
                <span class="small">No trailer available</span>
            <?php } ?>

            <hr>

            <p class="small">Release: <?php echo $movie['release_date'] ?? 'N/A'; ?></p>
            <p class="small">Published: <?php echo $movie['published_at'] ?? 'N/A'; ?></p>
        </div>

        <div style="display:flex; gap:18px; flex-wrap:wrap;">

            <div class="stat" style="flex:1;">
                <div class="label">Rating</div>
                <div class="value">⭐ <?php echo number_format($movie['average_rating'] ?? 0, 1); ?></div>
            </div>

            <div class="stat" style="flex:1;">
                <div class="label">Count</div>
                <div class="value"><?php echo (int)($movie['rating_count'] ?? 0); ?></div>
            </div>

            <div class="stat" style="flex:1;">
                <div class="label">Views</div>
                <div class="value"><?php echo (int)($movie['view_count'] ?? 0); ?></div>
            </div>

            <div class="stat" style="flex:1;">
                <div class="label">Watchlist</div>
                <div class="value"><?php echo (int)$watchlist_count; ?></div>
            </div>

        </div>

        <div class="stat">
            <div class="label">Comments</div>

            <?php if (mysqli_num_rows($comments_result) > 0) { ?>
                <?php while ($c = mysqli_fetch_assoc($comments_result)) { ?>
                    <div class="comment">
                        <?php echo htmlspecialchars($c['comment_text']); ?>
                        <br>
                        <small><?php echo $c['created_at']; ?></small>
                    </div>
                <?php } ?>
            <?php } else { ?>
                <span class="small">No comments yet</span>
            <?php } ?>
        </div>

      
        <div class="media-box">

            <div class="label">Movie Media</div>

            <?php if (mysqli_num_rows($media_result) > 0) { ?>

                <div class="media-grid">

                    <?php while ($m = mysqli_fetch_assoc($media_result)) {

                        // Get media file path
                        $path = $m['file_path'];

                        // Check if media path is external or local
                        $media_path = (strpos($path, 'http') === 0)
                            ? $path
                            : '../' . $path;
                    ?>

                        <div class="media-item">
                            <img src="<?php echo htmlspecialchars($media_path); ?>"
                                 onerror="this.src='../assets/images/placeholder.png';">

                        </div>

                    <?php } ?>

                </div>

            <?php } else { ?>

                <p class="small">No media uploaded yet</p>

            <?php } ?>

        </div>

    </div>

</div>

</body>

 
<script>
window.addEventListener("scroll", function () {
    const navbar = document.querySelector(".navbar");
 
    if (window.scrollY > 50) {
        navbar.classList.add("scrolled");
    } else {
        navbar.classList.remove("scrolled");
    }
});
 
function goBack() {
    window.history.back();
}
</script>

</html>