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
    <title>MovieMeter | Discover</title>

    <link rel="stylesheet" href="assets/css/discover.css">

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
            <li><a href="discover.php" class="active-link">Discover</a></li>
            <li><a href="categories.php">Categories</a></li>
            <li><a href="foryou.php">For You</a></li>
            <li><a href="watchlist.php">Watchlist</a></li>
            <li><a href="profile.php">Profile</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>

    <section class="categories-hero">
        <div class="overlay"></div>
        <div class="hero-content">
            <h1>Discover Movies</h1>
            <p>Explore the newest releases and the highest rated movies in one place.</p>
        </div>
    </section>

    <section class="movies-section showcase-section" id="latest-section">
        <div class="section-header">
            <h2>Latest Movies</h2>
            <p>Discover the most recently published movies.</p>
        </div>

        <div class="carousel-shell">
            <button class="section-arrow left" id="latestPrev" type="button">&#8592;</button>

            <div class="carousel-window">
                <div id="latestTrack" class="carousel-track"></div>
            </div>

            <button class="section-arrow right" id="latestNext" type="button">&#8594;</button>
        </div>
    </section>

    <section class="movies-section showcase-section" id="top-rated-section">
        <div class="section-header">
            <h2>Top Rated</h2>
            <p>Highlighting the most liked movies.</p>
        </div>

        <div class="carousel-shell">
            <button class="section-arrow left" id="topRatedPrev" type="button">&#8592;</button>

            <div class="carousel-window">
                <div id="topRatedTrack" class="carousel-track"></div>
            </div>

            <button class="section-arrow right" id="topRatedNext" type="button">&#8594;</button>
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

    <script type="module" src="assets/js/main.js"></script>
</body>