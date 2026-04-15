<?php
require_once("../includes/auth_check.php");
require_once("../config/DBConn.php");

$conn=getConnection();

$result=mysqli_query($conn,"
SELECT u.full_name, COUNT(m.movie_id) as total
FROM mm_users u
JOIN mm_movies m ON u.user_id=m.creator_id
GROUP BY u.user_id
ORDER BY total DESC
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

<h1 class="admin-title">Top Creators</h1>

<table class="admin-table">
<tr><th>Name</th><th>Movies</th></tr>

<?php while($row=mysqli_fetch_assoc($result)){ ?>
<tr>
<td><?php echo $row["full_name"]; ?></td>
<td><?php echo $row["total"]; ?></td>
</tr>
<?php } ?>

</table>

</div>
<?php include("../includes/admin_footer.php"); ?>
</body>
</html>