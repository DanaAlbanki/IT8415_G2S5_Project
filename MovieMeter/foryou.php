<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once(__DIR__ . "/includes/auth_check.php");

if (!isset($_SESSION["role_name"]) || $_SESSION["role_name"] !== "viewer") {
    die("Access denied.");
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>For You</title>

    <link rel="stylesheet" href="assets/css/foryou.css">

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
            <li><a href="foryou.php" class="active-link">For You</a></li>
            <li><a href="my-watchlist.php">Watchlist</a></li>
            <li><a href="profile.php">Profile</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>

    <section class="for-you-hero">
        <div class="overlay"></div>
        <div class="hero-content">
            <h1>For You</h1>
            <p>
                Fresh picks, trending titles, and movies you might like — all in your style.
            </p>
        </div>
    </section>

    <main class="recommendation-page">

        <section class="recommendation-section">
            <div class="section-top">
                <div class="section-header left-align">
                    <h2>Trending Now</h2>
                    <p>Popular movies people are watching right now.</p>
                </div>

                <button id="refreshTrending" class="refresh-btn" type="button">Refresh</button>
            </div>

            <p id="trendingStatus" class="loading-text"></p>
            <div id="trendingMovies" class="movies-row"></div>
        </section>

        <section class="recommendation-section">
            <div class="section-top">
                <div class="section-header left-align">
                    <h2>You Might Like</h2>
                    <p>Another handpicked set from different genres.</p>
                </div>

                <button id="refreshForYou" class="refresh-btn" type="button">Refresh</button>
            </div>

            <p id="forYouStatus" class="loading-text"></p>
            <div id="forYouMovies" class="movies-row"></div>
        </section>

    </main>

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
                    <li><a href="discover.php">Latest Movies</a></li>
                    <li><a href="discover.php">Top Rated</a></li>
                    <li><a href="my-watchlist.php">Watchlist</a></li>
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

    <script type="module" src="assets/js/foryou.js"></script>
</body>

</html>