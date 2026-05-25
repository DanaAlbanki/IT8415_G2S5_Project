<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start the session only if it is not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in
$isLoggedIn = isset($_SESSION["user_id"]);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories</title>

    <link rel="stylesheet" href="assets/css/categories.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- Page fonts -->
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
        <li><a href="categories.php" class="active-link">Categories</a></li>

        <!-- Show different navigation links based on login status -->
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

<section class="categories-hero">
    <div class="overlay"></div>
    <div class="hero-content">
        <h1>Movie Categories</h1>
        <p>Browse movies by genre and discover titles you might love.</p>
    </div>
</section>

<section class="genres-section">
    <div class="section-header">
        <h2>Browse by Genre</h2>
        <p>Select a category to load movies.</p>
    </div>

    <!-- Genres are loaded here using JavaScript -->
    <div id="genresList" class="genres-list"></div>
</section>

<section class="movies-section" id="category-section">
    <div class="section-header">
        <h2 id="selectedCategoryTitle">Category Movies</h2>
        <p id="selectedCategorySubtitle">Choose a category to explore movies.</p>
    </div>

    <!-- Movies for the selected category are displayed here -->
    <div id="categoryMovies" class="movies"></div>
</section>

<div class="pagination-wrapper">
    <!-- Pagination buttons are generated here -->
    <div id="pagination" class="pagination"></div>
</div>

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
                <li><a href="discover.php">Discover</a></li>

                <!-- Footer links also change depending on login status -->
                <?php if ($isLoggedIn): ?>
                    <li><a href="foryou.php">For You</a></li>
                    <li><a href="watchlist.php">Watchlist</a></li>
                <?php else: ?>
                    <li><a href="login.php">Login</a></li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="footer-links">
            <h4>Categories</h4>
            <ul>
                <li><a href="#" data-genre-link="28">Action</a></li>
                <li><a href="#" data-genre-link="18">Drama</a></li>
                <li><a href="#" data-genre-link="35">Comedy</a></li>
                <li><a href="#" data-genre-link="14">Fantasy</a></li>
            </ul>
        </div>

        <div class="footer-contact">
            <h4>Contact</h4>
            <p><a href="mailto:support@moviemeter.com">support@moviemeter.com</a></p>
            <p><a href="tel:+97317000000">+973 1700 0000</a></p>
        </div>

    </div>

    <div class="footer-bottom">
        <p>© 2026 MovieMeter. All rights reserved.</p>
    </div>
</footer>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script type="module" src="assets/js/categories.js"></script>

<script>
// Make footer category links load movies like the main genre buttons
document.querySelectorAll("[data-genre-link]").forEach(link => {
    link.addEventListener("click", function(e) {
        e.preventDefault();

        const genreId = this.getAttribute("data-genre-link");

        // Move the user to the movies section
        document.getElementById("category-section").scrollIntoView({
            behavior: "smooth"
        });

        // Load movies for the selected footer category
        if (window.loadMoviesByGenre) {
            window.loadMoviesByGenre(genreId);
        }
    });
});
</script>

</body>
</html>