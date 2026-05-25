<?php
// Enable PHP error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include authentication check and database connection
require_once(__DIR__ . "/includes/auth_check.php");
require_once(__DIR__ . "/config/DBConn.php");

// Open database connection
$conn = getConnection();
mysqli_set_charset($conn, "utf8mb4");

// Get logged-in user ID
$userId = (int) $_SESSION["user_id"];

// Store watchlist movies
$watchlistMovies = [];

// Get all published movies from the user's watchlist
$sql = "
    SELECT
        m.movie_id,
        m.external_api_id,
        m.title,
        m.poster_image,
        m.release_date,
        wi.added_at
    FROM mm_watchlist_items wi
    INNER JOIN mm_watchlists w ON wi.watchlist_id = w.watchlist_id
    INNER JOIN mm_movies m ON wi.movie_id = m.movie_id
    WHERE w.user_id = ?
      AND m.status = 'published'
    ORDER BY wi.added_at DESC, m.title ASC
";

// Prepare SQL query
$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Database error: " . mysqli_error($conn));
}

// Bind user ID and execute query
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

// Store watchlist results into array
while ($row = mysqli_fetch_assoc($result)) {
    $watchlistMovies[] = $row;
}

// Close database resources
mysqli_stmt_close($stmt);
mysqli_close($conn);

// Count total watchlist movies
$watchlistCount = count($watchlistMovies);
?>

<!DOCTYPE html>
<html lang="en">
<head>

    <!-- Character encoding and responsive layout -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Browser tab title -->
    <title>Watchlist</title>

    <!-- Website styles -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/watchlist.css">

    <!-- Google font connection optimization -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- Website fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>

<body>

    <!-- Main navigation bar -->
    <nav class="navbar">

        <!-- Website logo -->
        <div class="logo">
            <img src="assets/images/logo.png" alt="MovieMeter Logo">
        </div>

        <!-- Mobile menu button -->
        <button class="menu-toggle" id="menuToggle" aria-label="Open menu">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <!-- Navigation links -->
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

    <!-- Watchlist hero section -->
    <section class="watchlist-hero-simple">

        <!-- Hero overlay -->
        <div class="watchlist-hero-overlay"></div>

        <!-- Hero content -->
        <div class="watchlist-hero-content-simple">

            <h1>My Watchlist</h1>

            <!-- Dynamic movie count -->
            <p id="watchlistCount" class="watchlist-count-hero">
                <?php echo $watchlistCount; ?>
                <?php echo $watchlistCount === 1 ? "movie" : "movies"; ?> saved
            </p>

        </div>

    </section>

    <!-- Watchlist movies section -->
    <section class="movies-section" id="watchlist-section">

        <div class="movies" id="watchlistMovies">

            <!-- Show empty message if no movies exist -->
            <?php if ($watchlistCount === 0) { ?>

                <div class="empty-state">Your watchlist is empty.</div>

            <?php } else { ?>

                <!-- Loop through watchlist movies -->
                <?php foreach ($watchlistMovies as $movie) { ?>

                    <div class="movie-card watchlist-card" data-movie-id="<?php echo (int) $movie["movie_id"]; ?>">

                        <!-- Movie details page link -->
                        <a href="movie.php?id=<?php echo urlencode($movie["external_api_id"]); ?>&return_to=<?php echo urlencode($_SERVER["REQUEST_URI"]); ?>" class="movie-card-link">

                            <!-- Movie poster -->
                            <img
                                src="<?php echo htmlspecialchars(!empty($movie["poster_image"]) ? $movie["poster_image"] : "assets/images/notfound.png", ENT_QUOTES, "UTF-8"); ?>"
                                alt="<?php echo htmlspecialchars($movie["title"] ?: "Movie Poster", ENT_QUOTES, "UTF-8"); ?>"
                                class="watchlist-poster"
                                onerror="this.src='assets/images/notfound.png'">

                            <!-- Movie title -->
                            <h3><?php echo htmlspecialchars($movie["title"] ?: "Untitled Movie", ENT_QUOTES, "UTF-8"); ?></h3>

                        </a>

                        <!-- Watchlist action buttons -->
                        <div class="watchlist-card-actions">

                            <!-- Remove movie button -->
                            <button
                                type="button"
                                class="watchlist-remove-btn"
                                data-movie-id="<?php echo (int) $movie["movie_id"]; ?>"
                            >
                                Remove
                            </button>

                        </div>

                    </div>

                <?php } ?>

            <?php } ?>

        </div>

    </section>

    <!-- Website footer -->
    <footer class="footer">

        <div class="footer-container">

            <!-- Footer brand section -->
            <div class="footer-brand">

                <h3>MovieMeter</h3>

                <p>
                    Discover, rate, and explore your favorite movies in one place.
                    Find trending titles and build your personal watchlist.
                </p>

            </div>

            <!-- Footer quick links -->
            <div class="footer-links">

                <h4>Quick Links</h4>

                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="discover.php">Discover</a></li>
                    <li><a href="foryou.php">For You</a></li>
                    <li><a href="watchlist.php">Watchlist</a></li>
                </ul>

            </div>

            <!-- Footer category links -->
            <div class="footer-links">

                <h4>Categories</h4>

                <ul>
                    <li><a href="categories.php?genre=28">Action</a></li>
                    <li><a href="categories.php?genre=18">Drama</a></li>
                    <li><a href="categories.php?genre=35">Comedy</a></li>
                    <li><a href="categories.php?genre=14">Fantasy</a></li>
                </ul>

            </div>

            <!-- Footer contact information -->
            <div class="footer-contact">

                <h4>Contact</h4>

                <p><a href="mailto:support@moviemeter.com">support@moviemeter.com</a></p>
                <p><a href="tel:+97317000000">+973 1700 0000</a></p>

            </div>

        </div>

        <!-- Footer copyright -->
        <div class="footer-bottom">
            <p>© 2026 MovieMeter. All rights reserved.</p>
        </div>

    </footer>

    <!-- jQuery library -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- Watchlist page JavaScript -->
    <script src="assets/js/watchlist.js"></script>

</body>
</html>