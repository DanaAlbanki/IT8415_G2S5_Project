<?php
require_once(__DIR__ . "/../includes/auth_check.php");
require_once("../config/DBConn.php");

if ($_SESSION["role_name"] !== "creator") {
    die("Access denied.");
}

$dbc = getConnection();

$movie_id = (int)$_GET['movie_id'];
$creator_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $reason = $_POST['reason'] ?? 'No reason provided';

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

    mysqli_query($dbc, $sql);

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

</form>

</div>

</body>
</html>