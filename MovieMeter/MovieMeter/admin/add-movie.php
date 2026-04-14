<?php
require_once(__DIR__ . "/../includes/auth_check.php");
require_once(__DIR__ . "/../config/DBConn.php");

if ($_SESSION["role_name"] !== "admin") die("Access denied.");

$conn = getConnection();

if($_SERVER["REQUEST_METHOD"]=="POST"){

$title=$_POST["title"];
$desc=$_POST["desc"];
$date=$_POST["date"];

$imageName=$_FILES["image"]["name"];
$tmp=$_FILES["image"]["tmp_name"];

move_uploaded_file($tmp,"../assets/images/".$imageName);

mysqli_query($conn,"
INSERT INTO mm_movies(title, short_description, release_date, poster_image, status)
VALUES('$title','$desc','$date','$imageName','published')
");

header("Location: manage-movies.php");
exit;
}
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

<h1 class="admin-title">Add Movie</h1>

<form class="admin-form" method="POST" enctype="multipart/form-data">

<div class="form-group">
    <label>Movie Title</label>
    <input name="title" required>
</div>

<div class="form-group">
    <label>Description</label>
    <textarea name="desc"></textarea>
</div>

<div class="form-group">
    <label>Release Date</label>
    <input type="date" name="date">
</div>

<div class="form-group">
    <label>Movie Poster</label>
    <input type="file" name="image">
</div>

<div class="form-actions">
    <button class="btn btn-add">Add Movie</button>
</div>

</form>

</div>
</body>
</html>