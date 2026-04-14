<?php
require_once(__DIR__ . "/../includes/auth_check.php");
require_once(__DIR__ . "/../config/DBConn.php");

if ($_SESSION["role_name"] !== "admin") die("Access denied.");

$conn = getConnection();

$users = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM mm_users"))[0];
$movies = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM mm_movies"))[0];
$comments = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM mm_comments"))[0];

$latestMovies = mysqli_query($conn,"SELECT title, poster_image FROM mm_movies ORDER BY created_at DESC LIMIT 6");
?>

<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body>

<?php include("../includes/admin_nav.php"); ?>

<div class="admin-container">

<h1 class="admin-title">Dashboard</h1>

<div class="admin-cards">
<div class="admin-card">Users: <?php echo $users; ?></div>
<div class="admin-card">Movies: <?php echo $movies; ?></div>
<div class="admin-card">Comments: <?php echo $comments; ?></div>
</div>

<h2>Latest Movies</h2>

<div class="movies">
<?php while($m=mysqli_fetch_assoc($latestMovies)){ ?>
<div class="movie-card">
<img src="../assets/images/<?php echo $m["poster_image"]; ?>">
<h3><?php echo $m["title"]; ?></h3>
</div>
<?php } ?>
</div>

</div>

</body>
</html>