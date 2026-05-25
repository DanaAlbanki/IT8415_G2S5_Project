<?php
// Enable PHP error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start the session only if it has not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection file
require_once(__DIR__ . "/config/DBConn.php");

// Check if the user is logged in
$isLoggedIn = isset($_SESSION["user_id"]);

// Fetch movie details from TMDB API
function fetchTmdbMovie($tmdbId)
{
    $apiKey = "d29868793eba2f4ccb7cb36fa101c2a8";
    $url = "https://api.themoviedb.org/3/movie/" . urlencode($tmdbId) . "?api_key=" . $apiKey . "&language=en-US&append_to_response=videos";

    // Create and configure the API request
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => true
    ]);

    // Run the request
    $response = curl_exec($ch);

    if ($response === false) {
        curl_close($ch);
        return null;
    }

    // Check response status
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        return null;
    }

    // Decode API response
    $data = json_decode($response, true);
    return is_array($data) ? $data : null;
}

// Get local movie ID using TMDB ID
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

// Get the best available trailer URL from TMDB video results
function getTrailerUrlFromMovie($movie)
{
    if (empty($movie["videos"]["results"]) || !is_array($movie["videos"]["results"])) {
        return null;
    }

    $videos = $movie["videos"]["results"];
    $selectedVideo = null;

    // Prefer official YouTube trailers
    foreach ($videos as $video) {
        if (
            ($video["site"] ?? "") === "YouTube" &&
            ($video["type"] ?? "") === "Trailer" &&
            !empty($video["official"]) &&
            !empty($video["key"])
        ) {
            $selectedVideo = $video;
            break;
        }
    }

    // If no official trailer exists, use any YouTube trailer
    if (!$selectedVideo) {
        foreach ($videos as $video) {
            if (
                ($video["site"] ?? "") === "YouTube" &&
                ($video["type"] ?? "") === "Trailer" &&
                !empty($video["key"])
            ) {
                $selectedVideo = $video;
                break;
            }
        }
    }

    // If no trailer exists, use any YouTube video
    if (!$selectedVideo) {
        foreach ($videos as $video) {
            if (
                ($video["site"] ?? "") === "YouTube" &&
                !empty($video["key"])
            ) {
                $selectedVideo = $video;
                break;
            }
        }
    }

    if (!$selectedVideo) {
        return null;
    }

    return "https://www.youtube.com/embed/" . trim((string) $selectedVideo["key"]);
}

// Sync TMDB genres into local categories and link them to the movie
function syncMovieGenresToCategories($conn, $movieId, $movie)
{
    if (!$movieId || empty($movie["genres"]) || !is_array($movie["genres"])) {
        return;
    }

    foreach ($movie["genres"] as $genre) {
        $categoryName = trim($genre["name"] ?? "");

        if ($categoryName === "") {
            continue;
        }

        $categoryId = 0;

        // Check if category already exists
        $findCategorySql = "
            SELECT category_id
            FROM mm_categories
            WHERE category_name = ?
            LIMIT 1
        ";

        $findCategoryStmt = mysqli_prepare($conn, $findCategorySql);

        if ($findCategoryStmt) {
            mysqli_stmt_bind_param($findCategoryStmt, "s", $categoryName);
            mysqli_stmt_execute($findCategoryStmt);
            $findCategoryResult = mysqli_stmt_get_result($findCategoryStmt);
            $existingCategory = mysqli_fetch_assoc($findCategoryResult);

            if ($existingCategory) {
                $categoryId = (int) $existingCategory["category_id"];
            }

            mysqli_stmt_close($findCategoryStmt);
        }

        // Create category if it does not exist
        if ($categoryId <= 0) {
            $description = $categoryName . " movies";

            $insertCategorySql = "
                INSERT INTO mm_categories (
                    category_name,
                    description,
                    created_at
                ) VALUES (?, ?, NOW())
            ";

            $insertCategoryStmt = mysqli_prepare($conn, $insertCategorySql);

            if ($insertCategoryStmt) {
                mysqli_stmt_bind_param($insertCategoryStmt, "ss", $categoryName, $description);

                if (mysqli_stmt_execute($insertCategoryStmt)) {
                    $categoryId = (int) mysqli_insert_id($conn);
                }

                mysqli_stmt_close($insertCategoryStmt);
            }

            // Try finding category again if insert did not return an ID
            if ($categoryId <= 0) {
                $findAgainStmt = mysqli_prepare($conn, $findCategorySql);

                if ($findAgainStmt) {
                    mysqli_stmt_bind_param($findAgainStmt, "s", $categoryName);
                    mysqli_stmt_execute($findAgainStmt);
                    $findAgainResult = mysqli_stmt_get_result($findAgainStmt);
                    $existingCategory = mysqli_fetch_assoc($findAgainResult);

                    if ($existingCategory) {
                        $categoryId = (int) $existingCategory["category_id"];
                    }

                    mysqli_stmt_close($findAgainStmt);
                }
            }
        }

        if ($categoryId <= 0) {
            continue;
        }

        // Check if movie is already linked to this category
        $checkLinkSql = "
            SELECT 1
            FROM mm_movie_categories
            WHERE movie_id = ? AND category_id = ?
            LIMIT 1
        ";

        $checkLinkStmt = mysqli_prepare($conn, $checkLinkSql);

        $alreadyLinked = false;

        if ($checkLinkStmt) {
            mysqli_stmt_bind_param($checkLinkStmt, "ii", $movieId, $categoryId);
            mysqli_stmt_execute($checkLinkStmt);
            $checkLinkResult = mysqli_stmt_get_result($checkLinkStmt);
            $alreadyLinked = ($checkLinkResult && mysqli_num_rows($checkLinkResult) > 0);
            mysqli_stmt_close($checkLinkStmt);
        }

        // Link movie to category if it is not already linked
        if (!$alreadyLinked) {
            $insertLinkSql = "
                INSERT INTO mm_movie_categories (movie_id, category_id)
                VALUES (?, ?)
            ";

            $insertLinkStmt = mysqli_prepare($conn, $insertLinkSql);

            if ($insertLinkStmt) {
                mysqli_stmt_bind_param($insertLinkStmt, "ii", $movieId, $categoryId);
                mysqli_stmt_execute($insertLinkStmt);
                mysqli_stmt_close($insertLinkStmt);
            }
        }
    }
}

// Update local movie information from TMDB
function updateExistingMovieFromTmdb($conn, $movieId, $movie)
{
    $releaseDate = !empty($movie["release_date"]) ? $movie["release_date"] : null;
    $posterImage = !empty($movie["poster_path"])
        ? "https://image.tmdb.org/t/p/w500" . $movie["poster_path"]
        : "assets/images/notfound.png";
    $trailerUrl = getTrailerUrlFromMovie($movie);
    $now = date("Y-m-d H:i:s");

    $updateSql = "
        UPDATE mm_movies
        SET release_date = ?,
            poster_image = ?,
            trailer_url = ?,
            updated_at = ?
        WHERE movie_id = ?
    ";

    $updateStmt = mysqli_prepare($conn, $updateSql);

    if ($updateStmt) {
        mysqli_stmt_bind_param(
            $updateStmt,
            "ssssi",
            $releaseDate,
            $posterImage,
            $trailerUrl,
            $now,
            $movieId
        );
        mysqli_stmt_execute($updateStmt);
        mysqli_stmt_close($updateStmt);
    }
}

// Get existing movie or create it from TMDB data
function getOrCreateMovieIdFromTmdb($conn, $tmdbId)
{
    $existingMovieId = getMovieIdFromTmdb($conn, $tmdbId);
    $movie = fetchTmdbMovie($tmdbId);

    if (!$movie || empty($movie["id"])) {
        return $existingMovieId > 0 ? $existingMovieId : 0;
    }

    if ($existingMovieId > 0) {
        syncMovieGenresToCategories($conn, $existingMovieId, $movie);
        updateExistingMovieFromTmdb($conn, $existingMovieId, $movie);
        return $existingMovieId;
    }

    // Prepare movie data from TMDB
    $title = trim($movie["title"] ?? $movie["name"] ?? "Untitled");
    $overview = trim($movie["overview"] ?? "");
    $releaseDate = !empty($movie["release_date"]) ? $movie["release_date"] : null;
    $posterImage = !empty($movie["poster_path"])
        ? "https://image.tmdb.org/t/p/w500" . $movie["poster_path"]
        : "assets/images/notfound.png";
    $trailerUrl = getTrailerUrlFromMovie($movie);

    $status = "published";
    $creatorId = 2;
    $isApiImported = 1;
    $externalApiSource = "tmdb";
    $externalApiId = (string) $movie["id"];
    $now = date("Y-m-d H:i:s");

    // Insert imported movie into local database
    $insertSql = "
        INSERT INTO mm_movies (
            title,
            short_description,
            full_description,
            release_date,
            poster_image,
            trailer_url,
            status,
            creator_id,
            is_api_imported,
            external_api_source,
            external_api_id,
            created_at,
            updated_at,
            published_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";

    $insertStmt = mysqli_prepare($conn, $insertSql);

    if (!$insertStmt) {
        return 0;
    }

    mysqli_stmt_bind_param(
        $insertStmt,
        "sssssssissssss",
        $title,
        $overview,
        $overview,
        $releaseDate,
        $posterImage,
        $trailerUrl,
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

    syncMovieGenresToCategories($conn, $newMovieId, $movie);

    return $newMovieId;
}

// Open database connection
$conn = getConnection();
mysqli_set_charset($conn, "utf8mb4");

// Read page values
$tmdbId = isset($_GET["id"]) ? trim($_GET["id"]) : "";
$userId = isset($_SESSION["user_id"]) ? (int) $_SESSION["user_id"] : 0;
$movieId = 0;
$isInWatchlist = false;

$returnTo = "index.php";

// Validate return URL to avoid unsafe redirects
if (isset($_GET["return_to"]) && $_GET["return_to"] !== "") {
    $candidate = trim($_GET["return_to"]);

    if (
        strpos($candidate, "http://") === false &&
        strpos($candidate, "https://") === false &&
        strpos($candidate, "//") === false &&
        stripos($candidate, "javascript:") !== 0
    ) {
        $returnTo = $candidate;
    }
}

// Default movie interaction values
$userRating = 0;
$comments = [];
$summary = [
    "average_rating" => 0,
    "rating_count" => 0,
    "comment_count" => 0,
    "view_count" => 0
];

if ($tmdbId !== "") {
    $movieId = getOrCreateMovieIdFromTmdb($conn, $tmdbId);

    if ($movieId > 0) {
        if (!isset($_SESSION["viewed_movies"])) {
            $_SESSION["viewed_movies"] = [];
        }

        // Count a view once per session for this movie
        if (!in_array($movieId, $_SESSION["viewed_movies"], true)) {
            $viewStmt = mysqli_prepare(
                $conn,
                "UPDATE mm_movies SET view_count = view_count + 1 WHERE movie_id = ?"
            );

            if ($viewStmt) {
                mysqli_stmt_bind_param($viewStmt, "i", $movieId);
                mysqli_stmt_execute($viewStmt);
                mysqli_stmt_close($viewStmt);
            }

            $_SESSION["viewed_movies"][] = $movieId;
        }

        // Get movie rating, comment, and view summary
        $summarySql = "
            SELECT
                m.view_count,
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
            FROM mm_movies m
            WHERE m.movie_id = ?
            LIMIT 1
        ";

        $summaryStmt = mysqli_prepare($conn, $summarySql);

        if ($summaryStmt) {
            mysqli_stmt_bind_param($summaryStmt, "iiii", $movieId, $movieId, $movieId, $movieId);
            mysqli_stmt_execute($summaryStmt);
            $summaryResult = mysqli_stmt_get_result($summaryStmt);
            $summaryRow = mysqli_fetch_assoc($summaryResult);

            if ($summaryRow) {
                $summary = [
                    "average_rating" => (float) $summaryRow["average_rating"],
                    "rating_count" => (int) $summaryRow["rating_count"],
                    "comment_count" => (int) $summaryRow["comment_count"],
                    "view_count" => (int) $summaryRow["view_count"]
                ];
            }

            mysqli_stmt_close($summaryStmt);
        }

        if ($userId > 0) {
            // Get current user's rating for this movie
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

            // Check whether movie is already in user's watchlist
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

        // Get visible comments for this movie
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

// Prepare data for JavaScript movie page
$pageData = [
    "tmdbId" => $tmdbId,
    "movieId" => $movieId,
    "isLoggedIn" => $isLoggedIn,
    "userRating" => $userRating,
    "comments" => $comments,
    "summary" => $summary,
    "isInWatchlist" => $isInWatchlist
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Character encoding and responsive page settings -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movie Details</title>

    <!-- Movie details page stylesheet -->
    <link rel="stylesheet" href="assets/css/movie.css">

    <!-- Google font connection optimization -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- Website fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="movie-page">

    <!-- Top bar with back button -->
    <header class="movie-topbar">
        <a
            href="<?php echo htmlspecialchars($returnTo, ENT_QUOTES, 'UTF-8'); ?>"
            class="back-link"
            onclick="event.preventDefault(); goBackSmart(this.href);"
        >← Back</a>
    </header>

    <!-- Movie details content will be filled by JavaScript -->
    <main id="movie-detail">
        <?php if ($tmdbId === "") { ?>
            <!-- Message shown when no movie ID is provided -->
            <section class="message-block">
                <h1>Movie not found</h1>
                <p>No movie ID was provided.</p>
                <a
                    href="<?php echo htmlspecialchars($returnTo, ENT_QUOTES, 'UTF-8'); ?>"
                    class="action-btn secondary-btn"
                    onclick="event.preventDefault(); goBackSmart(this.href);"
                >Go Back</a>
            </section>
        <?php } elseif ($movieId <= 0) { ?>
            <!-- Message shown when movie cannot be loaded -->
            <section class="message-block">
                <h1>Movie not found</h1>
                <p>We could not load this movie.</p>
                    <a
                        href="<?php echo htmlspecialchars($returnTo, ENT_QUOTES, 'UTF-8'); ?>"
                        class="action-btn secondary-btn"
                        onclick="event.preventDefault(); goBackSmart(this.href);"
                    >Go Back</a>
            </section>
        <?php } ?>
    </main>

    <?php if ($tmdbId !== "" && $movieId > 0) { ?>
        <!-- Pass PHP movie data to JavaScript -->
        <script>
            window.moviePageData = <?php echo json_encode($pageData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
        </script>

        <!-- jQuery library -->
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

        <!-- Movie details page JavaScript -->
        <script type="module" src="assets/js/movie.js"></script>
    <?php } ?>

    <!-- Smart back button behavior -->
    <script>
        function goBackSmart(fallbackUrl) {
            try {
                if (window.history.length > 1 && document.referrer) {
                    const referrerUrl = new URL(document.referrer, window.location.origin);

                    if (referrerUrl.origin === window.location.origin) {
                        window.history.back();
                        return;
                    }
                }
            } catch (e) {
            }

            window.location.href = fallbackUrl;
        }
    </script>
</body>
</html>