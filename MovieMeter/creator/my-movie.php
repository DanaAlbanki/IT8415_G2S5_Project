<?php
// a page to display all loged in creator movies and search/filter them 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . "/../includes/auth_check.php");
require_once("../config/DBConn.php");

if ($_SESSION["role_name"] !== "creator") {
    die("Access denied.");
}

$dbc = getConnection();

$creator_id = $_SESSION['user_id'];


$title      = $_GET['title'] ?? '';
$status     = $_GET['status'] ?? '';
$date_from  = $_GET['date_from'] ?? '';
$date_to    = $_GET['date_to'] ?? '';

$sql = "
    SELECT movie_id, title, poster_image, release_date, short_description, status
    FROM mm_movies
    WHERE creator_id = $creator_id
    AND status != 'deleted'
";

if (!empty($title)) {
    $safe_title = mysqli_real_escape_string($dbc, $title);
    $sql .= " AND title LIKE '%$safe_title%'";
}

if (!empty($status)) {
    $safe_status = mysqli_real_escape_string($dbc, $status);
    $sql .= " AND status = '$safe_status'";
}

if (!empty($date_from)) {
    $safe_from = mysqli_real_escape_string($dbc, $date_from);
    $sql .= " AND DATE(release_date) >= '$safe_from'";
}

if (!empty($date_to)) {
    $safe_to = mysqli_real_escape_string($dbc, $date_to);
    $sql .= " AND DATE(release_date) <= '$safe_to'";
}

$sql .= " ORDER BY release_date DESC";

$result = mysqli_query($dbc, $sql);

if (!$result) {
    die("SQL Error: " . mysqli_error($dbc));
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Movies</title>
    <link rel="stylesheet" href="../assets/css/my-movie.css">
</head>

<body>

<div class="navbar">
    <div class="logo">
        <img src="../assets/images/logo.png">
    </div>

    <div class="nav-links">
        <a href="../creator/dashboard.php" class="active-link">Dashboard</a>
        <a href="../creator/my-movie.php">My Movies</a>
        <a href="../creator/add-movie.php">Add Movie</a>
        <a href="../creator/import-movies.php">Import Movies</a>
        <a href="../creator/profile.php">Profile</a>
        <a href="../logout.php">Logout</a>
    </div>
</div>

<!-- TOP CONTENT -->
<div class="box">

    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
        <div class="section-header">
            <h2>My Movies</h2>
        </div>

        <a href="add-movie.php"
           style="background:#facc15;color:#1f1229;padding:8px 14px;border-radius:8px;font-weight:bold;text-decoration:none;">
            + Add Movie
        </a>
    </div>

</div>

<!-- FULL WIDTH SEARCH -->
<div class="search-section">
    <div class="search-section-inner">

        <div class="search-heading">
            <h2>Search Movies</h2>
            <p>Search by title, status, and date range.</p>
        </div>

        <form id="searchForm" class="search-form-grid" method="GET">

            <input type="text" name="title" placeholder="Search by title"
                   value="<?php echo htmlspecialchars($title); ?>">

            <select name="status">
                <option value="">All Status</option>
                <option value="published" <?php if ($status == 'published') echo 'selected'; ?>>Published</option>
                <option value="draft" <?php if ($status == 'draft') echo 'selected'; ?>>Draft</option>
            </select>

            <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
            <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">

            <div class="search-form-buttons">
                <button type="submit" class="search-btn-main">Search</button>

                <button type="button" class="reset-btn-main"
                        onclick="window.location.href='my-movie.php'">
                    Reset
                </button>
            </div>

        </form>

    </div>
</div>

<!-- MOVIES SECTION -->
<div class="box">

    <div class="movies">

        <?php if (mysqli_num_rows($result) > 0) { ?>
            <?php while ($movie = mysqli_fetch_assoc($result)) { ?>

                <?php
                // Get movie poster
                $poster = $movie["poster_image"];

                // Check if poster exists
                if (!empty($poster)) {
                    $poster_path = (strpos($poster, 'http') === 0)
                        ? $poster
                        : '../' . $poster;
                } else {
                    $poster_path = '../assets/images/placeholder.png';
                }
                ?>

                <article class="movie-card">
                    <a href="movie_details.php?movie_id=<?php echo (int)$movie["movie_id"]; ?>">

                        <img src="<?php echo htmlspecialchars($poster_path); ?>">

                        <div class="movie-info">

                            <h3><?php echo htmlspecialchars($movie["title"]); ?></h3>

                            <p>
                                <?php
                                // Limit description length
                                $desc = trim((string)($movie["short_description"] ?? ""));
                                echo htmlspecialchars(strlen($desc) > 120 ? substr($desc, 0, 120) . "..." : $desc);
                                ?>
                            </p>

                            <span class="movie-release">
                                Release: <?php echo date("F j, Y", strtotime($movie["release_date"])); ?>
                            </span>

                            <span class="status-badge <?php echo $movie['status']; ?>">
                                <?php echo ucfirst($movie['status']); ?>
                            </span>

                        </div>

                    </a>
                </article>

            <?php } ?>
        <?php } else { ?>
            <div class="empty-state">No movies found.</div>
        <?php } ?>

    </div>
</div>

<script>
window.addEventListener("scroll", function () {
    const navbar = document.querySelector(".navbar");
    if (window.scrollY > 50) {
        navbar.classList.add("scrolled");
    } else {
        navbar.classList.remove("scrolled");
    }
});
</script>

<?php include("../includes/creator_footer.php"); ?>

</body>
</html>