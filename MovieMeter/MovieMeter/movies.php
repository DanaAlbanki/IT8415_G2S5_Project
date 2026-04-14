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

$perPage = 10;
$page = isset($_GET["page"]) ? (int)$_GET["page"] : 1;
$page = max(1, $page);

$countSql = "SELECT COUNT(*) AS total FROM mm_movies WHERE status = 'published'";
$countResult = mysqli_query($conn, $countSql);
$totalRows = (int)(mysqli_fetch_assoc($countResult)["total"] ?? 0);

$totalPages = max(1, (int)ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$sql = "
    SELECT 
        movie_id,
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
mysqli_stmt_bind_param($stmt, "ii", $perPage, $offset);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Latest Movies</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

    <section class="movies-section">
        <div class="section-header">
            <h2>Latest Movies</h2>
            <p>Browse the latest movies added to the platform.</p>
        </div>

        <div id="movies" class="movies">
            <?php if (mysqli_num_rows($result) > 0) { ?>
                <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                    <article class="movie-card">
                        <a href="movie.php?id=<?php echo (int)$row["movie_id"]; ?>" class="movie-card-link">
                            <?php if (!empty($row["poster_image"])) { ?>
                                <img src="uploads/posters/<?php echo e($row["poster_image"]); ?>" alt="<?php echo e($row["title"]); ?>">
                            <?php } else { ?>
                                <img src="assets/images/no-image.png" alt="No Poster Available">
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

    <div class="pagination">
        <form method="GET" style="display:inline;">
            <input type="hidden" name="page" value="<?php echo max(1, $page - 1); ?>">
            <button id="prev" type="submit" <?php echo $page <= 1 ? 'disabled' : ''; ?>>Previous</button>
        </form>

        <span id="page">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>

        <form method="GET" style="display:inline;">
            <input type="hidden" name="page" value="<?php echo min($totalPages, $page + 1); ?>">
            <button id="next" type="submit" <?php echo $page >= $totalPages ? 'disabled' : ''; ?>>Next</button>
        </form>
    </div>

</body>

</html>