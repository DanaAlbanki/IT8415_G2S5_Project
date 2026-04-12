<?php
require_once(__DIR__ . "/../includes/auth_check.php");
require_once(__DIR__ . "/../config/DBConn.php");
require_once(__DIR__ . "/../config/tmdb_config.php");

if (!isset($_SESSION["role_name"]) || $_SESSION["role_name"] !== "creator") {
    die("Access denied.");
}

$conn = getConnection();
mysqli_set_charset($conn, "utf8mb4");

$creatorId = (int) $_SESSION["user_id"];
$pages = isset($_POST["pages"]) ? (int) $_POST["pages"] : 1;
$pages = max(1, min($pages, 20));

$insertedCount = 0;
$skippedCount = 0;
$errorMessages = [];

function fetchTmdbMovies($page)
{
    $url = TMDB_BASE_URL . "/discover/movie?" . http_build_query([
        "api_key" => TMDB_API_KEY,
        "language" => "en-US",
        "sort_by" => "primary_release_date.desc",
        "include_adult" => "false",
        "include_video" => "false",
        "page" => $page,
        "primary_release_date.lte" => date("Y-m-d")
    ]);

    $response = @file_get_contents($url);

    if ($response === false) {
        return null;
    }

    $data = json_decode($response, true);

    if (!is_array($data) || !isset($data["results"])) {
        return null;
    }

    return $data["results"];
}

function truncateText($text, $maxLength)
{
    $text = trim((string)$text);

    if ($text === "") {
        return "";
    }

    if (mb_strlen($text) <= $maxLength) {
        return $text;
    }

    return mb_substr($text, 0, $maxLength - 3) . "...";
}

for ($page = 1; $page <= $pages; $page++) {
    $movies = fetchTmdbMovies($page);

    if ($movies === null) {
        $errorMessages[] = "Failed to fetch API page " . $page;
        continue;
    }

    foreach ($movies as $movie) {
        $externalApiId = isset($movie["id"]) ? (string)$movie["id"] : "";
        $title = trim($movie["title"] ?? "");
        $overview = trim($movie["overview"] ?? "");
        $releaseDate = trim($movie["release_date"] ?? "");
        $posterPath = trim($movie["poster_path"] ?? "");

        if ($externalApiId === "" || $title === "") {
            $skippedCount++;
            continue;
        }

        // Check duplicate by TMDB id
        $checkSql = "SELECT movie_id FROM mm_movies WHERE external_api_source = ? AND external_api_id = ? LIMIT 1";
        $checkStmt = mysqli_prepare($conn, $checkSql);

        if (!$checkStmt) {
            $errorMessages[] = "Prepare failed on duplicate check: " . mysqli_error($conn);
            continue;
        }

        $source = "tmdb";
        mysqli_stmt_bind_param($checkStmt, "ss", $source, $externalApiId);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);
        $existing = mysqli_fetch_assoc($checkResult);
        mysqli_stmt_close($checkStmt);

        if ($existing) {
            $skippedCount++;
            continue;
        }

        $shortDescription = truncateText($overview !== "" ? $overview : "Imported from TMDB.", 500);
        $fullDescription = $overview !== "" ? $overview : "Imported from TMDB.";
        $posterImage = $posterPath !== "" ? TMDB_IMG_PATH . $posterPath : null;
        $status = "published";
        $isApiImported = 1;
        $trailerUrl = null;
        $externalSource = "tmdb";

        $releaseDateValue = $releaseDate !== "" ? $releaseDate : null;

        $insertSql = "
            INSERT INTO mm_movies (
                creator_id,
                title,
                short_description,
                full_description,
                release_date,
                poster_image,
                trailer_url,
                status,
                is_api_imported,
                external_api_source,
                external_api_id,
                created_at,
                updated_at,
                published_at
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), NOW())
        ";

        $insertStmt = mysqli_prepare($conn, $insertSql);

        if (!$insertStmt) {
            $errorMessages[] = "Prepare failed on insert: " . mysqli_error($conn);
            continue;
        }

        mysqli_stmt_bind_param(
            $insertStmt,
            "isssssssiss",
            $creatorId,
            $title,
            $shortDescription,
            $fullDescription,
            $releaseDateValue,
            $posterImage,
            $trailerUrl,
            $status,
            $isApiImported,
            $externalSource,
            $externalApiId
        );

        $ok = mysqli_stmt_execute($insertStmt);

        if ($ok) {
            $newMovieId = mysqli_insert_id($conn);
            $insertedCount++;

            // optional: also insert poster into media table
            if ($posterImage !== null) {
                $mediaType = "image";
                $filePath = $posterImage;
                $fileName = basename($posterPath);
                $isPrimary = 1;

                $mediaSql = "
                    INSERT INTO mm_movie_media (
                        movie_id,
                        media_type,
                        file_path,
                        file_name,
                        is_primary,
                        uploaded_at
                    )
                    VALUES (?, ?, ?, ?, ?, NOW())
                ";

                $mediaStmt = mysqli_prepare($conn, $mediaSql);

                if ($mediaStmt) {
                    mysqli_stmt_bind_param(
                        $mediaStmt,
                        "isssi",
                        $newMovieId,
                        $mediaType,
                        $filePath,
                        $fileName,
                        $isPrimary
                    );
                    mysqli_stmt_execute($mediaStmt);
                    mysqli_stmt_close($mediaStmt);
                }
            }
        } else {
            $errorMessages[] = "Insert failed for movie: " . $title . " - " . mysqli_error($conn);
        }

        mysqli_stmt_close($insertStmt);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import API Movies</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #1f1229;
            color: #fff;
            margin: 0;
            padding: 40px 20px;
        }

        .box {
            max-width: 800px;
            margin: 0 auto;
            background: #2a1a36;
            padding: 30px;
            border-radius: 16px;
        }

        h2 {
            margin-top: 0;
        }

        .success {
            color: #86efac;
        }

        .warning {
            color: #fcd34d;
        }

        .error {
            color: #fca5a5;
        }

        .btn {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 18px;
            border-radius: 10px;
            background: #facc15;
            color: #1a1025;
            font-weight: bold;
            text-decoration: none;
        }

        ul {
            line-height: 1.7;
        }
    </style>
</head>
<body>
    <div class="box">
        <h2>TMDB Import Completed</h2>

        <p class="success">Inserted movies: <?php echo (int)$insertedCount; ?></p>
        <p class="warning">Skipped duplicates/invalid movies: <?php echo (int)$skippedCount; ?></p>

        <?php if (!empty($errorMessages)) { ?>
            <h3 class="error">Errors</h3>
            <ul>
                <?php foreach ($errorMessages as $msg) { ?>
                    <li><?php echo htmlspecialchars($msg); ?></li>
                <?php } ?>
            </ul>
        <?php } else { ?>
            <p class="success">No errors found.</p>
        <?php } ?>

        <a href="dashboard.php" class="btn">Back to Creator Dashboard</a>
    </div>
</body>
</html>