<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once(__DIR__ . "/config/DBConn.php");

$isLoggedIn = isset($_SESSION['user_id']);

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
    die("SQL Error: " . mysqli_error($conn));
}

$totalMovies = mysqli_num_rows($result);

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>

    <link rel="stylesheet" href="assets/css/style.css">

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
            <li><a href="index.php" class="active-link">Home</a></li>
            <li><a href="discover.php">Discover</a></li>
            <li><a href="categories.php">Categories</a></li>

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

    <section class="hero-slider">

        <div class="slide active" style="background-image: url('assets/images/Tangled-Movie.jpg');">
            <div class="overlay"></div>
            <div class="slide-content">
                <h1>Tangled</h1>
                <p>
                    Experience the magical journey of Rapunzel in a world full of adventure,
                    emotion, and unforgettable moments.
                </p>
                <div class="hero-buttons">
                    <a href="#" class="primary-btn hero-detail-btn" data-title="Tangled">View Details</a>
                </div>
            </div>
        </div>

        <div class="slide" style="background-image: url('assets/images/three.jpeg');">
            <div class="overlay"></div>
            <div class="slide-content">
                <h1>Harry Potter</h1>
                <p>
                    Enter the wizarding world and explore friendship, mystery,
                    and dark secrets through one of the most iconic fantasy series ever made.
                </p>
                <div class="hero-buttons">
                    <a href="#" class="primary-btn hero-detail-btn" data-title="Harry Potter and the Philosopher's Stone">View Details</a>
                </div>
            </div>
        </div>

        <div class="slide" style="background-image: url('assets/images/IT.jpeg');">
            <div class="overlay"></div>
            <div class="slide-content">
                <h1>IT</h1>
                <p>
                    A terrifying horror film about a group of kids who must confront
                    a shape-shifting evil clown that feeds on fear and haunts their town.
                </p>
                <div class="hero-buttons">
                    <a href="#" class="primary-btn hero-detail-btn" data-title="It">View Details</a>
                </div>
            </div>
        </div>

        <button class="slider-btn prev-slide">&#10094;</button>
        <button class="slider-btn next-slide">&#10095;</button>

        <div class="slider-dots">
            <span class="dot active" data-slide="0"></span>
            <span class="dot" data-slide="1"></span>
            <span class="dot" data-slide="2"></span>
        </div>
    </section>

    <section class="search-section">
        <div class="search-section-inner">
            <div class="search-heading">
                <h2>Search Movies</h2>
                <p>Search by title, creator, date range, and popularity.</p>
            </div>

            <form id="searchForm" class="search-form-grid" method="GET" action="search.php">
                <input type="text" id="searchTitle" name="title" placeholder="Search by movie title">
                <input type="text" id="searchCreator" name="creator" placeholder="Search by creator">
                <input type="date" id="fromDate" name="date_from">
                <input type="date" id="toDate" name="date_to">

                <select id="sortBy" name="popularity">
                    <option value="">Default</option>
                    <option value="rating_high">Highest Rating</option>
                    <option value="rating_count">Most Rated</option>
                    <option value="views">Most Viewed</option>
                    <option value="comments">Most Commented</option>
                    <option value="newest">Newest Release</option>
                    <option value="oldest">Oldest Release</option>
                </select>

                <div class="search-form-buttons">
                    <button type="submit" class="search-btn-main">Search</button>
                    <button type="reset" class="reset-btn-main">Reset</button>
                </div>
            </form>
        </div>
    </section>

    <section class="movies-section" id="all-movies-section">
        <div class="section-header">
            <h2>Browse Movies</h2>
            <p id="resultsCount">Found <?php echo $totalMovies; ?> results</p>
        </div>

        <div id="movies" class="movies">
            <?php if ($totalMovies > 0) { ?>
                <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                    <article class="movie-card">
                        <a href="movie.php?id=<?php echo (int)$row['movie_id']; ?>&return_to=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="movie-card-link">
                            <div class="movie-poster-wrap">
                                <?php if (!empty($row["poster_image"])) { ?>
                                    <img
                                        src="uploads/posters/<?php echo e(rawurlencode($row["poster_image"])); ?>"
                                        alt="<?php echo e($row["title"]); ?>">
                                <?php } else { ?>
                                    <div class="movie-no-poster">No Poster</div>
                                <?php } ?>
                            </div>

                            <div class="movie-info">
                                <h3><?php echo e($row["title"]); ?></h3>

                                <p>
                                    <?php
                                    $desc = trim((string)($row["short_description"] ?? ""));
                                    echo e(strlen($desc) > 120 ? substr($desc, 0, 120) . "..." : $desc);
                                    ?>
                                </p>

                                <span class="movie-release">
                                    Release: <?php echo e($row["release_date"]); ?>
                                </span>

                                <span class="movie-action">View Details</span>
                            </div>
                        </a>
                    </article>
                <?php } ?>
            <?php } else { ?>
                <p class="no-movies">No published movies found.</p>
            <?php } ?>
        </div>
    </section>

    <div class="pagination-wrapper">
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
                    <li><a href="categories.php?genre=28">Action</a></li>
                    <li><a href="categories.php?genre=18">Drama</a></li>
                    <li><a href="categories.php?genre=35">Comedy</a></li>
                    <li><a href="categories.php?genre=14">Fantasy</a></li>
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
    <script type="module" src="assets/js/main.js"></script>
    <script type="module" src="assets/js/home.js"></script>

</body>

</html>