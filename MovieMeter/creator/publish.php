<?php
// Show PHP errors for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load authentication and database connection
require_once(__DIR__ . "/../includes/auth_check.php");
require_once(__DIR__ . "/../config/DBConn.php");

// Allow creators only
if ($_SESSION["role_name"] !== "creator") {
    die("Access denied.");
}

// Connect to database
$dbc = getConnection();

// Get creator ID and movie ID
$creator_id = $_SESSION['user_id'];
$movie_id = isset($_GET['movie_id']) ? (int)$_GET['movie_id'] : 0;

// Validate movie ID
if ($movie_id <= 0) {
    die("Invalid movie ID.");
}

// SQL query to check movie ownership and status
$check_sql = "
    SELECT movie_id, status
    FROM mm_movies
    WHERE movie_id = $movie_id
    AND creator_id = $creator_id
    LIMIT 1
";

// Execute movie check query
$result = mysqli_query($dbc, $check_sql);

// Check if movie exists
if (mysqli_num_rows($result) == 0) {
    die("Movie not found or access denied.");
}

// Fetch movie data
$row = mysqli_fetch_assoc($result);

// Toggle movie status
$new_status = ($row['status'] === 'published') ? 'draft' : 'published';

// SQL query to update movie status
$update_sql = "
    UPDATE mm_movies
    SET status = '$new_status'
    WHERE movie_id = $movie_id
    AND creator_id = $creator_id
";

// Execute update query
mysqli_query($dbc, $update_sql);

// Redirect back to movie list
header("Location: my-movie.php");
exit;

