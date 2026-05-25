<?php
// Enable PHP error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Make sure only logged-in users can access this page
require_once(__DIR__ . "/includes/auth_check.php");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Character encoding and responsive layout -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Browser tab title -->
    <title>For You</title>

    <!-- For You page stylesheet -->
    <link rel="stylesheet" href="assets/css/foryou.css">

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
        <li><a href="foryou.php" class="active-link">For You</a></li>
        <li><a href="watchlist.php">Watchlist</a></li>
        <li><a href="profile.php">Profile</a></li>
        <li><a href="logout.php">Logout</a></li>
    </ul>
</nav>

<!-- For You hero section -->
<section class="for-you-hero">

    <!-- Dark overlay for background image -->
    <div class="overlay"></div>

    <!-- Hero text -->
    <div class="hero-content">
        <h1>For You</h1>
        <p>
            Fresh picks, trending titles, and movies you might like — all in your style.
        </p>
    </div>
</section>

<!-- Main recommendation content -->
<main class="recommendation-page">

    <!-- Trending movies section -->
    <section class="recommendation-section">

        <!-- Section title and refresh button -->
        <div class="section-top">
            <div class="section-header left-align">
                <h2>Trending Now</h2>
                <p>Popular movies people are watching right now.</p>
            </div>

            <button id="refreshTrending" class="refresh-btn" type="button">Refresh</button>
        </div>

        <!-- Trending status and movie cards -->
        <p id="trendingStatus" class="loading-text"></p>
        <div id="trendingMovies" class="movies-row"></div>
    </section>

    <!-- Personalized recommendation section -->
    <section class="recommendation-section">

        <!-- Section title and refresh button -->
        <div class="section-top">
            <div class="section-header left-align">
                <h2>You Might Like</h2>
                <p>Another handpicked set from different genres.</p>
            </div>

            <button id="refreshForYou" class="refresh-btn" type="button">Refresh</button>
        </div>

        <!-- For You status and movie cards -->
        <p id="forYouStatus" class="loading-text"></p>
        <div id="forYouMovies" class="movies-row"></div>
    </section>

</main>

<!-- Website footer -->
<footer class="footer">
    <div class="footer-container">

        <!-- Footer brand description -->
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

<!-- For You page JavaScript -->
<script type="module" src="assets/js/foryou.js"></script>

</body>
</html>