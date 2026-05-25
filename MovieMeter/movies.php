<?php
// Enable PHP error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if it is not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection file
require_once(__DIR__ . "/config/DBConn.php");

// Check whether the user is logged in
$isLoggedIn = isset($_SESSION["user_id"]);

// Open database connection
$conn = getConnection();
mysqli_set_charset($conn, "utf8mb4");

// Escape output safely before displaying it in HTML
function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Pagination setup
$perPage = 10;
$page = isset($_GET["page"]) ? (int) $_GET["page"] : 1;
$page = max(1, $page);

// Count total published movies
$countSql = "SELECT COUNT(*) AS total FROM mm_movies WHERE status = 'published'";
$countResult = mysqli_query($conn, $countSql);
$countRow = mysqli_fetch_assoc($countResult);
$totalRows = (int) ($countRow["total"] ?? 0);

// Calculate page numbers
$totalPages = max(1, (int) ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

// Get latest published movies for current page
$sql = "
    SELECT 
        movie_id,
        external_api_id,
        title,
        short_description,
        release_date,
        poster_image
    FROM mm_movies
    WHERE status = 'published'
    ORDER BY published_at DESC, created_at DESC
    LIMIT ? OFFSET ?
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Database error: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "ii", $perPage, $offset);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Character encoding and responsive page settings -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Latest Movies</title>

    <!-- Main website stylesheet -->
    <link rel="stylesheet" href="assets/css/style.css">

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

            <!-- Show different links based on login status -->
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

    <!-- Latest movies section -->
    <section class="movies-section" style="padding-top:130px;">
        <div class="section-header">
            <h2>Latest Movies</h2>
            <p>Browse the latest movies added to the platform.</p>
        </div>

        <!-- Movie cards container -->
        <div id="movies" class="movies">
            <?php if (mysqli_num_rows($result) > 0) { ?>
                <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                    <article class="movie-card">
                        <a href="movie.php?id=<?php echo urlencode($row["external_api_id"]); ?>&return_to=<?php echo urlencode($_SERVER["REQUEST_URI"]); ?>" class="movie-card-link">
                            <?php if (!empty($row["poster_image"])) { ?>
                                <img
                                    src="<?php echo e($row["poster_image"]); ?>"
                                    alt="<?php echo e($row["title"]); ?>"
                                    onerror="this.src='assets/images/notfound.png'">
                            <?php } else { ?>
                                <img
                                    src="assets/images/notfound.png"
                                    alt="No Poster Available">
                            <?php } ?>

                            <h3><?php echo e($row["title"]); ?></h3>
                        </a>
                    </article>
                <?php } ?>
            <?php } else { ?>
                <div class="empty-state">No published movies found.</div>
            <?php } ?>
        </div>
    </section>

    <!-- Pagination controls -->
    <div class="pagination-wrapper">
        <div class="pagination">
            <form method="GET" style="display:inline;">
                <input type="hidden" name="page" value="<?php echo max(1, $page - 1); ?>">
                <button type="submit" <?php echo $page <= 1 ? 'disabled' : ''; ?>>Previous</button>
            </form>

            <span>Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>

            <form method="GET" style="display:inline;">
                <input type="hidden" name="page" value="<?php echo min($totalPages, $page + 1); ?>">
                <button type="submit" <?php echo $page >= $totalPages ? 'disabled' : ''; ?>>Next</button>
            </form>
        </div>
    </div>

    <!-- Mobile navigation toggle script -->
    <script>
        const menuToggle = document.getElementById("menuToggle");
        const navLinks = document.getElementById("navLinks");

        if (menuToggle && navLinks) {
            menuToggle.addEventListener("click", () => {
                navLinks.classList.toggle("open");
            });
        }
    </script>

</body>
</html>