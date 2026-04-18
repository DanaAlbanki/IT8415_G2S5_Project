<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once(__DIR__ . "/../includes/auth_check.php");
require_once(__DIR__ . "/../config/DBConn.php");

if ($_SESSION["role_name"] !== "creator") {
    die("Access denied.");
}

$dbc = getConnection();

$creator_id = $_SESSION['user_id'];
$movie_id = isset($_GET['movie_id']) ? (int)$_GET['movie_id'] : 0;

if ($movie_id <= 0) {
    die("Invalid movie ID.");
}

$check_sql = "
    SELECT movie_id, status
    FROM mm_movies
    WHERE movie_id = $movie_id
    AND creator_id = $creator_id
    LIMIT 1
";

$result = mysqli_query($dbc, $check_sql);

if (mysqli_num_rows($result) == 0) {
    die("Movie not found or access denied.");
}

$row = mysqli_fetch_assoc($result);

$new_status = ($row['status'] === 'published') ? 'draft' : 'published';

$update_sql = "
    UPDATE mm_movies
    SET status = '$new_status'
    WHERE movie_id = $movie_id
    AND creator_id = $creator_id
";

mysqli_query($dbc, $update_sql);

header("Location: my-movie.php");
exit;