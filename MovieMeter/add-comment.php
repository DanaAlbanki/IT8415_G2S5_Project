<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once(__DIR__ . "/includes/auth_check.php");
require_once(__DIR__ . "/config/DBConn.php");

header("Content-Type: application/json; charset=UTF-8");

function respondJson($success, $message, $extra = [], $statusCode = 200)
{
    http_response_code($statusCode);
    echo json_encode(array_merge([
        "success" => $success,
        "message" => $message
    ], $extra), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
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
    mysqli_stmt_bind_param($stmt, "s", $tmdbId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    return $row ? (int)$row["movie_id"] : 0;
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
    $creatorId = !empty($_SESSION["user_id"]) ? (int)$_SESSION["user_id"] : 2;
    $isApiImported = 1;
    $externalApiSource = "tmdb";
    $externalApiId = (string)$movie["id"];
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
        return 0;
    }

    return (int)mysqli_insert_id($conn);
}

$conn = getConnection();
mysqli_set_charset($conn, "utf8mb4");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    respondJson(false, "Invalid request method.", [], 405);
}

if (!isset($_SESSION["user_id"])) {
    respondJson(false, "You must be logged in.", [], 401);
}

$userId = (int) $_SESSION["user_id"];
$tmdbId = isset($_POST["tmdb_id"]) ? trim($_POST["tmdb_id"]) : "";
$commentText = isset($_POST["comment_text"]) ? trim($_POST["comment_text"]) : "";

if ($tmdbId === "") {
    respondJson(false, "Invalid movie.", [], 422);
}

if ($commentText === "") {
    respondJson(false, "Comment cannot be empty.", [], 422);
}

if (mb_strlen($commentText) > 1000) {
    respondJson(false, "Comment is too long.", [], 422);
}

$movieId = getOrCreateMovieIdFromTmdb($conn, $tmdbId);

if ($movieId <= 0) {
    respondJson(false, "Movie could not be saved locally.", [], 500);
}

$insertSql = "
    INSERT INTO mm_comments (movie_id, user_id, comment_text, comment_status)
    VALUES (?, ?, ?, 'visible')
";
$insertStmt = mysqli_prepare($conn, $insertSql);
mysqli_stmt_bind_param($insertStmt, "iis", $movieId, $userId, $commentText);

if (!mysqli_stmt_execute($insertStmt)) {
    respondJson(false, "Failed to add comment.", [], 500);
}

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
mysqli_stmt_bind_param($summaryStmt, "iii", $movieId, $movieId, $movieId);
mysqli_stmt_execute($summaryStmt);
$summaryResult = mysqli_stmt_get_result($summaryStmt);
$summaryRow = mysqli_fetch_assoc($summaryResult);

$summary = [
    "average_rating" => $summaryRow ? (float) $summaryRow["average_rating"] : 0,
    "rating_count" => $summaryRow ? (int) $summaryRow["rating_count"] : 0,
    "comment_count" => $summaryRow ? (int) $summaryRow["comment_count"] : 0
];

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
mysqli_stmt_bind_param($commentsStmt, "i", $movieId);
mysqli_stmt_execute($commentsStmt);
$commentsResult = mysqli_stmt_get_result($commentsStmt);

$comments = [];
while ($row = mysqli_fetch_assoc($commentsResult)) {
    $comments[] = $row;
}

respondJson(true, "Comment added successfully.", [
    "summary" => $summary,
    "comments" => $comments
]);
?>