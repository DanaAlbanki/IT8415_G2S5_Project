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

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$title = trim($_GET["title"] ?? "");
$creator = trim($_GET["creator"] ?? "");
$date_from = trim($_GET["date_from"] ?? "");
$date_to = trim($_GET["date_to"] ?? "");
$popularity = trim($_GET["popularity"] ?? "");

$page = isset($_GET["page"]) ? (int)$_GET["page"] : 1;
$page = max(1, $page);

$perPage = 10;
$offset = ($page - 1) * $perPage;

$orderOptions = [
    "" => "m.created_at DESC",
    "rating_high" => "m.average_rating DESC, m.rating_count DESC, m.title ASC",
    "rating_count" => "m.rating_count DESC, m.average_rating DESC, m.title ASC",
    "views" => "m.view_count DESC, m.title ASC",
    "comments" => "m.comment_count DESC, m.title ASC",
    "newest" => "m.release_date DESC, m.created_at DESC",
    "oldest" => "m.release_date ASC, m.created_at ASC"
];

$orderBy = $orderOptions[$popularity] ?? $orderOptions[""];

$where = ["m.status = 'published'"];
$params = [];
$types = "";

if ($title !== "") {
    $where[] = "(
        MATCH(m.title, m.short_description, m.full_description) AGAINST (? IN NATURAL LANGUAGE MODE)
        OR m.title LIKE ?
    )";
    $params[] = $title;
    $params[] = "%" . $title . "%";
    $types .= "ss";
}

if ($creator !== "") {
    $where[] = "(u.full_name LIKE ? OR u.username LIKE ?)";
    $params[] = "%" . $creator . "%";
    $params[] = "%" . $creator . "%";
    $types .= "ss";
}

if ($date_from !== "" && $date_to !== "") {
    $where[] = "m.release_date BETWEEN ? AND ?";
    $params[] = $date_from;
    $params[] = $date_to;
    $types .= "ss";
} elseif ($date_from !== "") {
    $where[] = "m.release_date >= ?";
    $params[] = $date_from;
    $types .= "s";
} elseif ($date_to !== "") {
    $where[] = "m.release_date <= ?";
    $params[] = $date_to;
    $types .= "s";
}

$whereSQL = "WHERE " . implode(" AND ", $where);

$countSQL = "
    SELECT COUNT(*) AS total
    FROM mm_movies m
    INNER JOIN mm_users u ON m.creator_id = u.user_id
    $whereSQL
";

$countStmt = mysqli_prepare($conn, $countSQL);

if (!$countStmt) {
    die("Prepare failed (count): " . mysqli_error($conn));
}

if (!empty($params)) {
    mysqli_stmt_bind_param($countStmt, $types, ...$params);
}

mysqli_stmt_execute($countStmt);
$countResult = mysqli_stmt_get_result($countStmt);
$countRow = mysqli_fetch_assoc($countResult);
$totalRows = (int)($countRow["total"] ?? 0);
$totalPages = max(1, (int)ceil($totalRows / $perPage));

if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

$sql = "
    SELECT 
        m.movie_id,
        m.title,
        m.poster_image
    FROM mm_movies m
    INNER JOIN mm_users u ON m.creator_id = u.user_id
    $whereSQL
    ORDER BY $orderBy
    LIMIT ? OFFSET ?
";

$dataStmt = mysqli_prepare($conn, $sql);

if (!$dataStmt) {
    die("Prepare failed (data): " . mysqli_error($conn));
}

$dataParams = $params;
$dataTypes = $types . "ii";
$dataParams[] = $perPage;
$dataParams[] = $offset;

mysqli_stmt_bind_param($dataStmt, $dataTypes, ...$dataParams);
mysqli_stmt_execute($dataStmt);
$result = mysqli_stmt_get_result($dataStmt);

function pageUrl($pageNumber)
{
    $query = $_GET;
    $query["page"] = $pageNumber;
    return "search.php?" . http_build_query($query);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results</title>

    <link rel="stylesheet" href="assets/css/style.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>

    <nav class="navbar scrolled">
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
            <li><a href="watchlist.php">Watchlist</a></li>
            <li><a href="profile.php">Profile</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>

    <section class="search-section" style="padding-top:130px;">
        <div class="search-section-inner">
            <div class="search-heading">
                <h2>Search Movies</h2>
            </div>

            <form class="search-form-grid" method="GET" action="search.php">
                <input
                    type="text"
                    name="title"
                    placeholder="Search by movie title"
                    value="<?php echo e($title); ?>">

                <input
                    type="text"
                    name="creator"
                    placeholder="Search by creator"
                    value="<?php echo e($creator); ?>">

                <input
                    type="date"
                    name="date_from"
                    value="<?php echo e($date_from); ?>">

                <input
                    type="date"
                    name="date_to"
                    value="<?php echo e($date_to); ?>">

                <select name="popularity">
                    <option value="" <?php echo $popularity === "" ? "selected" : ""; ?>>Default</option>
                    <option value="rating_high" <?php echo $popularity === "rating_high" ? "selected" : ""; ?>>Highest Rating</option>
                    <option value="rating_count" <?php echo $popularity === "rating_count" ? "selected" : ""; ?>>Most Rated</option>
                    <option value="views" <?php echo $popularity === "views" ? "selected" : ""; ?>>Most Viewed</option>
                    <option value="comments" <?php echo $popularity === "comments" ? "selected" : ""; ?>>Most Commented</option>
                    <option value="newest" <?php echo $popularity === "newest" ? "selected" : ""; ?>>Newest Release</option>
                    <option value="oldest" <?php echo $popularity === "oldest" ? "selected" : ""; ?>>Oldest Release</option>
                </select>

                <div class="search-form-buttons">
                    <button type="submit" class="search-btn-main">Search</button>
                    <a href="search.php" class="reset-btn-main" style="display:flex;align-items:center;justify-content:center;text-decoration:none;">Reset</a>
                </div>
            </form>
        </div>
    </section>

    <section class="movies-section" id="all-movies-section">
        <div class="section-header">
            <h2><?php echo $totalRows > 0 ? "Search Results" : "No Results"; ?></h2>
        </div>

        <div class="movies">
            <?php if ($totalRows > 0) { ?>
                <?php while ($movie = mysqli_fetch_assoc($result)) { ?>
                    <article class="movie-card">
                        <a href="movie.php?id=<?php echo (int)$movie["movie_id"]; ?>" class="movie-card-link" style="text-decoration:none;color:inherit;">
                            <?php if (!empty($movie["poster_image"])) { ?>
                                <img
                                    src="uploads/posters/<?php echo e($movie["poster_image"]); ?>"
                                    alt="<?php echo e($movie["title"]); ?>">
                            <?php } else { ?>
                                <img
                                    src="assets/images/notfound.png"
                                    alt="No Poster">
                            <?php } ?>

                            <h3><?php echo e($movie["title"]); ?></h3>
                        </a>
                    </article>
                <?php } ?>
            <?php } else { ?>
                <div class="empty-state">
                    No movies found matching your search.
                </div>
            <?php } ?>
        </div>
    </section>

    <?php if ($totalPages > 1) { ?>
        <div class="pagination-wrapper">
            <div class="pagination">
                <?php if ($page > 1) { ?>
                    <a href="<?php echo e(pageUrl($page - 1)); ?>" style="text-decoration:none;">
                        <button type="button">←</button>
                    </a>
                <?php } ?>

                <?php
                $start = max(1, $page - 2);
                $end = min($totalPages, $page + 2);

                for ($i = $start; $i <= $end; $i++) {
                ?>
                    <a href="<?php echo e(pageUrl($i)); ?>" style="text-decoration:none;">
                        <button type="button" class="<?php echo $i === $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </button>
                    </a>
                <?php } ?>

                <?php if ($page < $totalPages) { ?>
                    <a href="<?php echo e(pageUrl($page + 1)); ?>" style="text-decoration:none;">
                        <button type="button">→</button>
                    </a>
                <?php } ?>
            </div>
        </div>
    <?php } ?>

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