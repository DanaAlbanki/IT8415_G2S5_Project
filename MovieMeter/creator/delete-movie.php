<?php
require_once(__DIR__ . "/../includes/auth_check.php");
require_once("../config/DBConn.php");

// Allow creators only
if ($_SESSION["role_name"] !== "creator") {
    die("Access denied.");
}

// Connect to database
$dbc = getConnection();

// Get movie ID and creator ID
$movie_id = (int)$_GET['movie_id'];
$creator_id = $_SESSION['user_id'];

// Run when form is submitted
if ($_SERVER["REQUEST_METHOD"] === "POST") {

// Escape delete reason input
$reason = mysqli_real_escape_string($dbc, $_POST['reason']);

// SQL query to soft delete movie
    $sql = "
    UPDATE mm_movies
    SET 
        status = 'deleted',
        deleted_by = $creator_id,
        deleted_reason = '$reason',
        deleted_at = NOW()
    WHERE movie_id = $movie_id
    AND creator_id = $creator_id
    ";

// Execute delete query
    mysqli_query($dbc, $sql);

// Redirect back to movie list
    header("Location: my-movie.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Delete Movie</title>
</head>
<link rel="stylesheet" href="../assets/css/delete-movie.css">
<body>

<div class="box">

<h2>Delete Movie</h2>

<form method="POST">

    <label>Reason for deletion:</label><br><br>

    <textarea name="reason" placeholder="Write why you are deleting this movie..." required></textarea>

    <br>

    <button type="submit">Confirm Delete</button>
<a href="my-movie.php" class="cancel-btn">Cancel</a>
</form>

</div>

</body>
</html>