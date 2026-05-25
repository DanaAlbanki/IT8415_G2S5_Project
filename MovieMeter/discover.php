<?php
// Enable PHP error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in
$isLoggedIn = isset($_SESSION["user_id"]);
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <!-- Character encoding and responsive layout -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Browser tab title -->
    <title>Discover</title>

    <!-- Main discover page stylesheet -->
    <link rel="stylesheet" href="assets/css/discover.css">

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

        <!-- Mobile navigation toggle button -->
        <button class="menu-toggle" id="menuToggle" aria-label="Open menu">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <!-- Main navigation links -->
        <ul class="nav-links" id="navLinks">

            <li><a href="index.php">Home</a></li>
            <li><a href="discover.php" class="active-link">Discover</a></li>
            <li><a href="categories.php">Categories</a></li>

            <!-- Show different links depending on login status -->
            <?php if ($isLoggedIn): ?>

                <li><a href="foryou.php">For You</a></li>
                <li><a href="watchlist.php">Watchlist</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="logout.php">Logout</a></li>

            <?php else: ?>

                <li><a href="login.php">Login</a></li>
                <li><a href="register.php">Sign Up</a></li>

            <?php endif; ?>

        </ul>
    </nav>

    <!-- Hero section -->
    <section class="categories-hero">

        <!-- Dark overlay for hero background -->
        <div class="overlay"></div>

        <!-- Hero text content -->
        <div class="hero-content">
            <h1>Discover Movies</h1>
            <p>Explore the newest releases and the highest rated movies in one place.</p>
        </div>

    </section>

    <!-- Latest movies section -->
    <section class="movies-section showcase-section" id="latest-section">

        <!-- Section heading -->
        <div class="section-header">
            <h2>Latest Movies</h2>
            <p>Discover the most recently published movies.</p>
        </div>

        <!-- Movie carousel -->
        <div class="carousel-shell">

            <!-- Previous button -->
            <button class="section-arrow left" id="latestPrev" type="button">
                &#8592;
            </button>

            <!-- Latest movies container -->
            <div class="carousel-window">
                <div id="latestTrack" class="carousel-track"></div>
            </div>

            <!-- Next button -->
            <button class="section-arrow right" id="latestNext" type="button">
                &#8594;
            </button>

        </div>
    </section>

    <!-- Top rated movies section -->
    <section class="movies-section showcase-section" id="top-rated-section">

        <!-- Section heading -->
        <div class="section-header">
            <h2>Top Rated</h2>
            <p>Highlighting the most liked movies.</p>
        </div>

        <!-- Top rated movie carousel -->
        <div class="carousel-shell">

            <!-- Previous button -->
            <button class="section-arrow left" id="topRatedPrev" type="button">
                &#8592;
            </button>

            <!-- Top rated movies container -->
            <div class="carousel-window">
                <div id="topRatedTrack" class="carousel-track"></div>
            </div>

            <!-- Next button -->
            <button class="section-arrow right" id="topRatedNext" type="button">
                &#8594;
            </button>

        </div>
    </section>

    <!-- Website footer -->
    <footer class="footer">

        <div class="footer-container">

            <!-- Footer brand information -->
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

                    <!-- Logged in user footer links -->
                    <?php if ($isLoggedIn): ?>

                        <li><a href="foryou.php">For You</a></li>
                        <li><a href="watchlist.php">Watchlist</a></li>

                    <?php else: ?>

                        <li><a href="login.php">Login</a></li>

                    <?php endif; ?>

                </ul>

            </div>

            <!-- Footer category shortcuts -->
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

                <p>
                    <a href="mailto:support@moviemeter.com">
                        support@moviemeter.com
                    </a>
                </p>

                <p>
                    <a href="tel:+97317000000">
                        +973 1700 0000
                    </a>
                </p>

            </div>

        </div>

        <!-- Footer copyright -->
        <div class="footer-bottom">
            <p>© 2026 MovieMeter. All rights reserved.</p>
        </div>

    </footer>

    <!-- jQuery library -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- Main discover page JavaScript -->
    <script type="module" src="assets/js/main.js"></script>

</body>
</html>