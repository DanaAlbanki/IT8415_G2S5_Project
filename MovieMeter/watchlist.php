<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once(__DIR__ . "/includes/auth_check.php");
require_once(__DIR__ . "/config/DBConn.php");

if (!isset($_SESSION["role_name"]) || $_SESSION["role_name"] !== "viewer") {
    die("Access denied.");
}

$conn = getConnection();
mysqli_set_charset($conn, "utf8mb4");

$userId = (int) $_SESSION["user_id"];

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Get user's watchlist
$watchlistSql = "SELECT watchlist_id, watchlist_name FROM mm_watchlists WHERE user_id = ? LIMIT 1";
$watchlistStmt = mysqli_prepare($conn, $watchlistSql);
mysqli_stmt_bind_param($watchlistStmt, "i", $userId);
mysqli_stmt_execute($watchlistStmt);
$watchlistResult = mysqli_stmt_get_result($watchlistStmt);
$watchlist = mysqli_fetch_assoc($watchlistResult);

// Get watchlist items
$itemsResult = null;

if ($watchlist) {
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
        WHERE wli.watchlist_id = ? 
          AND m.status = 'published'
        ORDER BY wli.added_at DESC
    ";
    $itemsStmt = mysqli_prepare($conn, $itemsSql);
    mysqli_stmt_bind_param($itemsStmt, "i", $watchlist["watchlist_id"]);
    mysqli_stmt_execute($itemsStmt);
    $itemsResult = mysqli_stmt_get_result($itemsStmt);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MovieMeter | Watchlist</title>

    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/watchlist.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>

<body>

    <nav class="navbar">
        <div class="logo">
            <img src="assets/images/logo.png" alt="MovieMeter Logo">
        </div>

        <button class="menu-toggle" id="menuToggle" aria-label="Open menu">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <ul class="nav-links" id="navLinks">
            <li><a href="index.php">Home</a></li>
            <li><a href="discover.php">Discover</a></li>
            <li><a href="categories.php">Categories</a></li>
            <li><a href="foryou.php">For You</a></li>
            <li><a href="watchlist.php" class="active-link">Watchlist</a></li>
            <li><a href="profile.php">Profile</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>

    <section class="watchlist-hero-simple">
        <div class="watchlist-hero-overlay"></div>
        <div class="watchlist-hero-content-simple">
            <h1>My Watchlist</h1>
            <p>Movies you saved to watch later.</p>
        </div>
    </section>

    <section class="movies-section" id="watchlist-section">
        <div class="section-header">
            <h2>Saved Movies</h2>
            <p>Keep track of all the movies you want to watch in one place.</p>
        </div>

        <div class="movies">
            <?php if (!$watchlist) { ?>
                <div class="empty-state">
                    You do not have a watchlist yet.
                </div>

            <?php } elseif (!$itemsResult || mysqli_num_rows($itemsResult) === 0) { ?>
                <div class="empty-state">
                    Your watchlist is empty.
                </div>

            <?php } else { ?>
                <?php while ($movie = mysqli_fetch_assoc($itemsResult)) { ?>
                    <div class="movie-card watchlist-card">
                        <a href="movie-details.php?id=<?php echo (int)$movie["movie_id"]; ?>" class="movie-card-link">
                            <?php if (!empty($movie["poster_image"])) { ?>
                                <img
                                    src="uploads/posters/<?php echo e($movie["poster_image"]); ?>"
                                    alt="<?php echo e($movie["title"]); ?>">
                            <?php } else { ?>
                                <img
                                    src="assets/images/no-image.png"
                                    alt="No Poster Available">
                            <?php } ?>

                            <h3><?php echo e($movie["title"]); ?></h3>
                        </a>

                        <div class="watchlist-card-actions">
                            <form
                                method="POST"
                                action="remove-from-watchlist.php"
                                class="remove-watchlist-form">
                                <input type="hidden" name="movie_id" value="<?php echo (int)$movie["movie_id"]; ?>">
                                <button type="submit" class="watchlist-remove-btn">Remove</button>
                            </form>
                        </div>
                    </div>
                <?php } ?>
            <?php } ?>
        </div>
    </section>

    <footer class="footer">
        <div class="footer-container">
            <div class="footer-brand">
                <h3>MovieMeter</h3>
                <p>
                    Discover, rate, and explore your favorite movies in one place.
                    Find trending titles and build your personal watchlist.
                </p>
            </div>

            <div class="footer-links">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="discover.php#latest-section">Latest Movies</a></li>
                    <li><a href="discover.php#top-rated-section">Top Rated</a></li>
                    <li><a href="watchlist.php">Watchlist</a></li>
                </ul>
            </div>

            <div class="footer-links">
                <h4>Categories</h4>
                <ul>
                    <li><a href="categories.php">Action</a></li>
                    <li><a href="categories.php">Drama</a></li>
                    <li><a href="categories.php">Comedy</a></li>
                    <li><a href="categories.php">Fantasy</a></li>
                </ul>
            </div>

            <div class="footer-contact">
                <h4>Contact</h4>
                <p>support@moviemeter.com</p>
                <p>+973 1700 0000</p>
            </div>
        </div>

        <div class="footer-bottom">
            <p>© 2026 MovieMeter. All rights reserved.</p>
        </div>
    </footer>

    <script src="assets/js/watchlist.js"></script>
</body>

</html>