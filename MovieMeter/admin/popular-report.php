<?php
// Fetches and displays a leaderboard of the top 10 most-viewed movies from the database.
require_once("../includes/auth_check.php");
require_once("../config/DBConn.php");

$conn=getConnection();

$result=mysqli_query($conn,"
SELECT title, view_count
FROM mm_movies
ORDER BY view_count DESC
LIMIT 10
");
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

<h1 class="admin-title">Popular Movies</h1>

<table class="admin-table">
<tr><th>Movie</th><th>Views</th></tr>

<?php while($row=mysqli_fetch_assoc($result)){ ?>
<tr>
<td><?php echo $row["title"]; ?></td>
<td><?php echo $row["view_count"]; ?></td>
</tr>
<?php } ?>

</table>

</div>
<?php include("../includes/admin_footer.php"); ?>
</body>
</html>