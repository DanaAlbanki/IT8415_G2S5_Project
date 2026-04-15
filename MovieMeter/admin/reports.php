<?php
require_once(__DIR__ . "/../includes/auth_check.php");
require_once(__DIR__ . "/../config/DBConn.php");

if ($_SESSION["role_name"] !== "admin") die("Access denied.");

$conn = getConnection();

$totalMovies = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM mm_movies"))[0];
$totalUsers = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM mm_users"))[0];
$totalComments = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM mm_comments"))[0];
?>

<!DOCTYPE html>
<html>
<head>
<title>Reports</title>
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body>

<?php include("../includes/admin_nav.php"); ?>

<div class="admin-container">

<h1 class="admin-title">Reports Dashboard</h1>

<!-- STATS -->
<div class="admin-cards">
    <div class="admin-card">
        <h3>Total Movies</h3>
        <p><?php echo $totalMovies; ?></p>
    </div>

    <div class="admin-card">
        <h3>Total Users</h3>
        <p><?php echo $totalUsers; ?></p>
    </div>

    <div class="admin-card">
        <h3>Total Comments</h3>
        <p><?php echo $totalComments; ?></p>
    </div>
</div>

<!-- BUTTONS -->
<div class="report-buttons">
    <a href="popular-report.php" class="btn btn-add">Popular Movies</a>
    <a href="creator-report.php" class="btn btn-edit">Top Creators</a>
</div>

</div>

<?php include("../includes/admin_footer.php"); ?>
</body>
</html>