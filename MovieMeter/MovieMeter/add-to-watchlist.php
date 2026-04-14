<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once(__DIR__ . "/includes/auth_check.php");
require_once(__DIR__ . "/config/DBConn.php");

header("Content-Type: application/json; charset=UTF-8");

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => "You must be logged in."
    ]);
    exit;
}

$conn = getConnection();
mysqli_set_charset($conn, "utf8mb4");

$userId = (int) $_SESSION["user_id"];
$tmdbId = isset($_POST["tmdb_id"]) ? trim($_POST["tmdb_id"]) : "";

if ($tmdbId === "") {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Invalid movie."
    ]);
    exit;
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

function getOrCreateMovieIdFromTmdb($conn, $tmdbId, $userId)
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
    $creatorId = $userId > 0 ? $userId : 1;
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

$movieId = getOrCreateMovieIdFromTmdb($conn, $tmdbId, $userId);

if ($movieId <= 0) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Invalid movie."
    ]);
    exit;
}

$watchlistId = 0;

$watchlistSql = "
    SELECT watchlist_id
    FROM mm_watchlists
    WHERE user_id = ?
    LIMIT 1
";
$watchlistStmt = mysqli_prepare($conn, $watchlistSql);

if ($watchlistStmt) {
    mysqli_stmt_bind_param($watchlistStmt, "i", $userId);
    mysqli_stmt_execute($watchlistStmt);
    $watchlistResult = mysqli_stmt_get_result($watchlistStmt);
    $watchlistRow = mysqli_fetch_assoc($watchlistResult);

    if ($watchlistRow) {
        $watchlistId = (int) $watchlistRow["watchlist_id"];
    }

    mysqli_stmt_close($watchlistStmt);
}

if ($watchlistId <= 0) {
    $now = date("Y-m-d H:i:s");
    $watchlistName = "My Watchlist";

    $createSql = "
        INSERT INTO mm_watchlists (user_id, watchlist_name, created_at)
        VALUES (?, ?, ?)
    ";
    $createStmt = mysqli_prepare($conn, $createSql);

    if (!$createStmt) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Failed to create watchlist."
        ]);
        exit;
    }

    mysqli_stmt_bind_param($createStmt, "iss", $userId, $watchlistName, $now);

    if (!mysqli_stmt_execute($createStmt)) {
        $dbError = mysqli_error($conn);
        mysqli_stmt_close($createStmt);

        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Failed to create watchlist: " . $dbError
        ]);
        exit;
    }

    $watchlistId = (int) mysqli_insert_id($conn);
    mysqli_stmt_close($createStmt);
}

$checkSql = "
    SELECT 1
    FROM mm_watchlist_items
    WHERE watchlist_id = ? AND movie_id = ?
    LIMIT 1
";
$checkStmt = mysqli_prepare($conn, $checkSql);

if ($checkStmt) {
    mysqli_stmt_bind_param($checkStmt, "ii", $watchlistId, $movieId);
    mysqli_stmt_execute($checkStmt);
    $checkResult = mysqli_stmt_get_result($checkStmt);

    if ($checkResult && mysqli_num_rows($checkResult) > 0) {
        mysqli_stmt_close($checkStmt);
        echo json_encode([
            "success" => true,
            "message" => "Movie already in watchlist.",
            "movie_id" => $movieId
        ]);
        exit;
    }

    mysqli_stmt_close($checkStmt);
}

$now = date("Y-m-d H:i:s");

$insertItemSql = "
    INSERT INTO mm_watchlist_items (watchlist_id, movie_id, added_at)
    VALUES (?, ?, ?)
";
$insertItemStmt = mysqli_prepare($conn, $insertItemSql);

if (!$insertItemStmt) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Failed to add movie to watchlist."
    ]);
    exit;
}

mysqli_stmt_bind_param($insertItemStmt, "iis", $watchlistId, $movieId, $now);

if (!mysqli_stmt_execute($insertItemStmt)) {
    $dbError = mysqli_error($conn);
    mysqli_stmt_close($insertItemStmt);

    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Failed to add movie to watchlist: " . $dbError
    ]);
    exit;
}

mysqli_stmt_close($insertItemStmt);

echo json_encode([
    "success" => true,
    "message" => "Movie added to watchlist.",
    "movie_id" => $movieId
]);
exit;