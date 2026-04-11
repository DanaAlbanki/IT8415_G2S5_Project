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

function formatDateText($date)
{
    if (empty($date) || $date === "0000-00-00") {
        return "N/A";
    }

    $time = strtotime($date);
    return $time ? date("F j, Y", $time) : $date;
}

$movieId = isset($_GET["id"]) ? (int)$_GET["id"] : 0;
$movie = null;

if ($movieId > 0) {
    $updateSql = "
        UPDATE mm_movies
        SET view_count = COALESCE(view_count, 0) + 1
        WHERE movie_id = ? AND status = 'published'
    ";
    $updateStmt = mysqli_prepare($conn, $updateSql);
    mysqli_stmt_bind_param($updateStmt, "i", $movieId);
    mysqli_stmt_execute($updateStmt);

    $sql = "
        SELECT 
            movie_id,
            title,
            short_description,
            release_date,
            poster_image,
            trailer_url,
            average_rating,
            view_count,
            status
        FROM mm_movies
        WHERE movie_id = ?
          AND status = 'published'
        LIMIT 1
    ";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $movieId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $movie = mysqli_fetch_assoc($result);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movie Details</title>

    <link rel="stylesheet" href="assets/css/movie.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
</head>

<body class="movie-page">

    <header class="movie-topbar">
        <a href="index.php" class="back-link">← Back to Home</a>
    </header>

    <main id="movie-detail">
        <?php if (!$movie) { ?>
            <section class="message-block">
                <h1>Movie not found</h1>
                <p>No movie ID was provided or this movie is not available.</p>
                <a href="index.php" class="action-btn secondary-btn">Back Home</a>
            </section>
        <?php } else { ?>
            <?php
            $title = $movie["title"] ?? "Untitled Movie";
            $year = !empty($movie["release_date"]) ? date("Y", strtotime($movie["release_date"])) : "";
            $fullTitle = $year ? $title . " (" . $year . ")" : $title;

            $description = trim((string)($movie["short_description"] ?? ""));
            $posterSrc = !empty($movie["poster_image"])
                ? "uploads/posters/" . rawurlencode($movie["poster_image"])
                : "assets/images/no-image.png";

            $trailerSrc = !empty($movie["trailer_url"])
                ? "uploads/trailers/" . rawurlencode($movie["trailer_url"])
                : "";

            $averageRating = ($movie["average_rating"] !== null && $movie["average_rating"] !== "")
                ? number_format((float)$movie["average_rating"], 2)
                : "N/A";

            $viewCount = isset($movie["view_count"]) ? (int)$movie["view_count"] : 0;
            $status = !empty($movie["status"]) ? $movie["status"] : "published";
            ?>

            <section class="hero" style="background-image: url('<?php echo e($posterSrc); ?>')">
                <div class="hero-overlay"></div>

                <div class="hero-inner">
                    <div class="poster-column">
                        <img src="<?php echo e($posterSrc); ?>" alt="<?php echo e($title); ?>" class="poster-image">
                    </div>

                    <div class="content-column">
                        <h1 class="movie-title"><?php echo e($fullTitle); ?></h1>

                        <?php if ($description !== "") { ?>
                            <p class="short-description"><?php echo e($description); ?></p>
                        <?php } ?>

                        <div class="button-row">
                            <?php if ($trailerSrc !== "") { ?>
                                <a href="#trailer-section" class="action-btn primary-btn">Watch Trailer</a>
                            <?php } ?>
                            <a href="watchlist.php" class="action-btn secondary-btn">My Watchlist</a>
                        </div>
                    </div>
                </div>
            </section>

            <section class="details-section">
                <div class="details-grid">
                    <article class="detail-panel">
                        <h2><?php echo e($title); ?></h2>

                        <div class="info-row">
                            <span>Release Date</span>
                            <strong><?php echo e(formatDateText($movie["release_date"] ?? "")); ?></strong>
                        </div>

                        <div class="info-row">
                            <span>Average Rating</span>
                            <strong><?php echo e($averageRating); ?></strong>
                        </div>

                        <div class="info-row">
                            <span>View Count</span>
                            <strong><?php echo $viewCount; ?></strong>
                        </div>

                        <div class="info-row">
                            <span>Status</span>
                            <strong><?php echo e($status); ?></strong>
                        </div>

                        <div class="info-row">
                            <span>Plot</span>
                            <strong><?php echo e($description !== "" ? $description : "No description available."); ?></strong>
                        </div>
                    </article>
                </div>
            </section>

            <section class="trailer-section" id="trailer-section">
                <div class="section-head">
                    <h2>Trailer</h2>
                </div>

                <?php if ($trailerSrc !== "") { ?>
                    <div class="trailer-frame">
                        <video style="width:100%;height:100%;display:block;background:#000;" controls>
                            <source src="<?php echo e($trailerSrc); ?>" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                    </div>
                <?php } else { ?>
                    <div class="no-trailer">
                        Trailer not available for this movie.
                    </div>
                <?php } ?>
            </section>
        <?php } ?>
    </main>

</body>

</html>