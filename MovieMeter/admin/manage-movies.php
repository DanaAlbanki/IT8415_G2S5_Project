<?php
// lists all movies with edit and delete options

require_once(__DIR__ . "/../includes/auth_check.php");
require_once(__DIR__ . "/../config/DBConn.php");

if ($_SESSION["role_name"] !== "admin") die("Access denied.");

$conn = getConnection();

$result = mysqli_query($conn,"
SELECT movie_id, title, release_date, poster_image
FROM mm_movies
ORDER BY movie_id DESC
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

<div style="display:flex;justify-content:space-between;align-items:center;">
<h1 class="admin-title">Manage Movies</h1>
<a href="add-movie.php" class="btn btn-add">+ Add Movie</a>
</div>

<table class="admin-table">
<tr>
<th>Poster</th>
<th>Title</th>
<th>Release</th>
<th>Actions</th>
</tr>

<?php while($row=mysqli_fetch_assoc($result)){ ?>
<tr>
<td>
<img src="<?php echo (strpos($row["poster_image"], 'http') === 0) 
? $row["poster_image"] 
: '../assets/images/' . $row["poster_image"]; ?>"
style="width:60px;height:90px;object-fit:cover;border-radius:6px;">

<td><?php echo $row["title"]; ?></td>
<td><?php echo $row["release_date"]; ?></td>

<td>
<a href="edit-movie.php?id=<?php echo $row["movie_id"]; ?>" class="btn btn-edit">Edit</a>

<a href="delete-movie.php?id=<?php echo $row["movie_id"]; ?>"
class="btn btn-delete"
onclick="return confirm('Delete this movie?');">Delete</a>
</td>
</tr>
<?php } ?>

</table>

</div>
<?php include("../includes/admin_footer.php"); ?>
</body>
</html>