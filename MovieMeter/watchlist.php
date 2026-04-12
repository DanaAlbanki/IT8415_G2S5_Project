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
$watchlistMovies = [];

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
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($result)) {
    $watchlistMovies[] = $row;
}

$watchlistCount = count($watchlistMovies);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Watchlist</title>

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
            <p id="watchlistCount" class="watchlist-count-hero">
                <?php echo $watchlistCount; ?> <?php echo $watchlistCount === 1 ? "movie" : "movies"; ?> saved
            </p>
        </div>
    </section>

    <section class="movies-section" id="watchlist-section">
        <div class="movies" id="watchlistMovies">
            <?php if ($watchlistCount === 0) { ?>
                <div class="empty-state">Your watchlist is empty.</div>
            <?php } else { ?>
                <?php foreach ($watchlistMovies as $movie) { ?>
                    <div class="movie-card watchlist-card" data-movie-id="<?php echo (int) $movie["movie_id"]; ?>">
                    <a href="movie.php?id=<?php echo urlencode($movie["external_api_id"]); ?>&return_to=<?php echo urlencode($_SERVER["REQUEST_URI"]); ?>" class="movie-card-link">                            <img
                                src="<?php echo htmlspecialchars(!empty($movie["poster_image"]) ? $movie["poster_image"] : "assets/images/notfound.png"); ?>"
                                alt="<?php echo htmlspecialchars($movie["title"] ?: "Movie Poster"); ?>"
                                class="watchlist-poster"
                                onerror="this.src='assets/images/notfound.png'">
                            <h3><?php echo htmlspecialchars($movie["title"] ?: "Untitled Movie"); ?></h3>
                        </a>

                        <div class="watchlist-card-actions">
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

    <script>
        const navbar = document.querySelector(".navbar");
        const menuToggle = document.getElementById("menuToggle");
        const navLinks = document.getElementById("navLinks");
        const watchlistContainer = document.getElementById("watchlistMovies");
        const watchlistCountEl = document.getElementById("watchlistCount");

        if (menuToggle && navLinks) {
            menuToggle.addEventListener("click", () => {
                navLinks.classList.toggle("open");
            });
        }

        document.querySelectorAll(".nav-links a").forEach((link) => {
            link.addEventListener("click", () => {
                if (navLinks) {
                    navLinks.classList.remove("open");
                }
            });
        });

        window.addEventListener("scroll", () => {
            if (!navbar) return;

            if (window.scrollY > 60) {
                navbar.classList.add("scrolled");
            } else {
                navbar.classList.remove("scrolled");
            }
        });

        function updateWatchlistCount() {
            if (!watchlistCountEl || !watchlistContainer) return;

            const count = watchlistContainer.querySelectorAll(".watchlist-card").length;
            watchlistCountEl.textContent = `${count} ${count === 1 ? "movie" : "movies"} saved`;
        }

        function showEmptyState() {
            if (!watchlistContainer) return;

            watchlistContainer.innerHTML = `
                <div class="empty-state">Your watchlist is empty.</div>
            `;

            updateWatchlistCount();
        }

        async function removeFromWatchlist(movieId, button) {
            if (!movieId) {
                alert("Missing movie id.");
                return;
            }

            const oldText = button.textContent;
            button.disabled = true;
            button.textContent = "Removing...";

            try {
                const formData = new FormData();
                formData.append("movie_id", String(movieId));

                const response = await fetch("remove-from-watchlist.php", {
                    method: "POST",
                    body: formData
                });

                const text = await response.text();

                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    throw new Error(text || "Invalid server response.");
                }

                if (!response.ok || !data.success) {
                    throw new Error(data.message || "Failed to remove movie.");
                }

                const card = button.closest(".watchlist-card");
                if (card) {
                    card.remove();
                }

                const remainingCards = watchlistContainer.querySelectorAll(".watchlist-card");
                if (remainingCards.length === 0) {
                    showEmptyState();
                } else {
                    updateWatchlistCount();
                }

            } catch (error) {
                console.error("Remove watchlist error:", error);
                alert(error.message || "Error removing movie.");
                button.disabled = false;
                button.textContent = oldText;
            }
        }

        document.addEventListener("DOMContentLoaded", () => {
            const removeButtons = document.querySelectorAll(".watchlist-remove-btn");

            removeButtons.forEach((button) => {
                button.addEventListener("click", () => {
                    const movieId = button.dataset.movieId;
                    removeFromWatchlist(movieId, button);
                });
            });

            updateWatchlistCount();
        });
    </script>
</body>
</html>