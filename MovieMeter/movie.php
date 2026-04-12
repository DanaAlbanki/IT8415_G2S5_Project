<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once(__DIR__ . "/includes/auth_check.php");
require_once(__DIR__ . "/config/DBConn.php");

if (
    !isset($_SESSION["role_name"]) ||
    ($_SESSION["role_name"] !== "viewer" && $_SESSION["role_name"] !== "admin")
) {
    die("Access denied.");
}

function fetchTmdbMovie($tmdbId)
{
    $apiKey = "d29868793eba2f4ccb7cb36fa101c2a8";
    $url = "https://api.themoviedb.org/3/movie/" . urlencode($tmdbId) . "?api_key=" . $apiKey . "&language=en-US";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => true
    ]);

    $response = curl_exec($ch);

    if ($response === false) {
        curl_close($ch);
        return null;
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        return null;
    }

    $data = json_decode($response, true);
    return is_array($data) ? $data : null;
}

function getMovieIdFromTmdb($conn, $tmdbId)
{
    $sql = "
        SELECT movie_id
        FROM mm_movies
        WHERE external_api_id = ?
        LIMIT 1
    ";
    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        return 0;
    }

    mysqli_stmt_bind_param($stmt, "s", $tmdbId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    return $row ? (int) $row["movie_id"] : 0;
}

function getOrCreateMovieIdFromTmdb($conn, $tmdbId)
{
    $existingMovieId = getMovieIdFromTmdb($conn, $tmdbId);
    if ($existingMovieId > 0) {
        return $existingMovieId;
    }

    $movie = fetchTmdbMovie($tmdbId);
    if (!$movie || empty($movie["id"])) {
        return 0;
    }

    $title = trim($movie["title"] ?? $movie["name"] ?? "Untitled");
    $overview = trim($movie["overview"] ?? "");
    $releaseDate = !empty($movie["release_date"]) ? $movie["release_date"] : null;
    $posterImage = !empty($movie["poster_path"])
        ? "https://image.tmdb.org/t/p/w500" . $movie["poster_path"]
        : "assets/images/notfound.png";

    $status = "published";
    $creatorId = !empty($_SESSION["user_id"]) ? (int) $_SESSION["user_id"] : 1;
    $isApiImported = 1;
    $externalApiSource = "tmdb";
    $externalApiId = (string) $movie["id"];
    $now = date("Y-m-d H:i:s");

    $insertSql = "
        INSERT INTO mm_movies (
            title,
            short_description,
            full_description,
            release_date,
            poster_image,
            status,
            creator_id,
            is_api_imported,
            external_api_source,
            external_api_id,
            created_at,
            updated_at,
            published_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";

    $insertStmt = mysqli_prepare($conn, $insertSql);

    if (!$insertStmt) {
        return 0;
    }

    mysqli_stmt_bind_param(
        $insertStmt,
        "ssssssissssss",
        $title,
        $overview,
        $overview,
        $releaseDate,
        $posterImage,
        $status,
        $creatorId,
        $isApiImported,
        $externalApiSource,
        $externalApiId,
        $now,
        $now,
        $now
    );

    if (!mysqli_stmt_execute($insertStmt)) {
        mysqli_stmt_close($insertStmt);
        return 0;
    }

    $newMovieId = (int) mysqli_insert_id($conn);
    mysqli_stmt_close($insertStmt);

    return $newMovieId;
}

$conn = getConnection();
mysqli_set_charset($conn, "utf8mb4");

$tmdbId = isset($_GET["id"]) ? trim($_GET["id"]) : "";
$userId = isset($_SESSION["user_id"]) ? (int) $_SESSION["user_id"] : 0;
$movieId = 0;
$isInWatchlist = false;

$userRating = 0;
$comments = [];
$summary = [
    "average_rating" => 0,
    "rating_count" => 0,
    "comment_count" => 0
];

if ($tmdbId !== "") {
    $movieId = getOrCreateMovieIdFromTmdb($conn, $tmdbId);

    if ($movieId > 0) {
        $summarySql = "
            SELECT
                COALESCE((
                    SELECT AVG(r.rating_value)
                    FROM mm_ratings r
                    WHERE r.movie_id = ?
                ), 0) AS average_rating,
                (
                    SELECT COUNT(*)
                    FROM mm_ratings r
                    WHERE r.movie_id = ?
                ) AS rating_count,
                (
                    SELECT COUNT(*)
                    FROM mm_comments c
                    WHERE c.movie_id = ?
                      AND c.comment_status = 'visible'
                ) AS comment_count
        ";
        $summaryStmt = mysqli_prepare($conn, $summarySql);

        if ($summaryStmt) {
            mysqli_stmt_bind_param($summaryStmt, "iii", $movieId, $movieId, $movieId);
            mysqli_stmt_execute($summaryStmt);
            $summaryResult = mysqli_stmt_get_result($summaryStmt);
            $summaryRow = mysqli_fetch_assoc($summaryResult);

            if ($summaryRow) {
                $summary = [
                    "average_rating" => (float) $summaryRow["average_rating"],
                    "rating_count" => (int) $summaryRow["rating_count"],
                    "comment_count" => (int) $summaryRow["comment_count"]
                ];
            }

            mysqli_stmt_close($summaryStmt);
        }

        if ($userId > 0) {
            $ratingSql = "
                SELECT rating_value
                FROM mm_ratings
                WHERE movie_id = ? AND user_id = ?
                LIMIT 1
            ";
            $ratingStmt = mysqli_prepare($conn, $ratingSql);

            if ($ratingStmt) {
                mysqli_stmt_bind_param($ratingStmt, "ii", $movieId, $userId);
                mysqli_stmt_execute($ratingStmt);
                $ratingResult = mysqli_stmt_get_result($ratingStmt);
                $ratingRow = mysqli_fetch_assoc($ratingResult);

                if ($ratingRow) {
                    $userRating = (int) $ratingRow["rating_value"];
                }

                mysqli_stmt_close($ratingStmt);
            }

            $watchlistSql = "
                SELECT 1
                FROM mm_watchlist_items wi
                INNER JOIN mm_watchlists w ON wi.watchlist_id = w.watchlist_id
                WHERE w.user_id = ? AND wi.movie_id = ?
                LIMIT 1
            ";
            $watchlistStmt = mysqli_prepare($conn, $watchlistSql);

            if ($watchlistStmt) {
                mysqli_stmt_bind_param($watchlistStmt, "ii", $userId, $movieId);
                mysqli_stmt_execute($watchlistStmt);
                $watchlistResult = mysqli_stmt_get_result($watchlistStmt);
                $isInWatchlist = ($watchlistResult && mysqli_num_rows($watchlistResult) > 0);
                mysqli_stmt_close($watchlistStmt);
            }
        }

        $commentsSql = "
            SELECT
                c.comment_id,
                c.comment_text,
                c.created_at,
                COALESCE(NULLIF(u.full_name, ''), NULLIF(u.username, ''), 'User') AS display_name
            FROM mm_comments c
            INNER JOIN mm_users u ON c.user_id = u.user_id
            WHERE c.movie_id = ?
              AND c.comment_status = 'visible'
            ORDER BY c.created_at DESC, c.comment_id DESC
        ";
        $commentsStmt = mysqli_prepare($conn, $commentsSql);

        if ($commentsStmt) {
            mysqli_stmt_bind_param($commentsStmt, "i", $movieId);
            mysqli_stmt_execute($commentsStmt);
            $commentsResult = mysqli_stmt_get_result($commentsStmt);

            while ($row = mysqli_fetch_assoc($commentsResult)) {
                $comments[] = $row;
            }

            mysqli_stmt_close($commentsStmt);
        }
    }
}

$pageData = [
    "tmdbId" => $tmdbId,
    "movieId" => $movieId,
    "userRating" => $userRating,
    "comments" => $comments,
    "summary" => $summary,
    "isInWatchlist" => $isInWatchlist
];
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
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="movie-page">

    <header class="movie-topbar">
        <a href="index.php" class="back-link">← Back to Home</a>
    </header>

    <main id="movie-detail">
        <?php if ($tmdbId === "") { ?>
            <section class="message-block">
                <h1>Movie not found</h1>
                <p>No movie ID was provided.</p>
                <a href="index.php" class="action-btn secondary-btn">Back Home</a>
            </section>
        <?php } elseif ($movieId <= 0) { ?>
            <section class="message-block">
                <h1>Movie not found</h1>
                <p>We could not load this movie.</p>
                <a href="index.php" class="action-btn secondary-btn">Back Home</a>
            </section>
        <?php } ?>
    </main>

    <?php if ($tmdbId !== "" && $movieId > 0) { ?>
        <script>
            window.moviePageData = <?php echo json_encode($pageData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
        </script>
        <script type="module" src="assets/js/movie.js"></script>
    <?php } ?>

</body>
</html>