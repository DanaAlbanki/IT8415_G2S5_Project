<?php
require_once(__DIR__ . "/../includes/auth_check.php");
require_once(__DIR__ . "/../config/DBConn.php");

if ($_SESSION["role_name"] !== "admin") die("Access denied.");

$conn=getConnection();
$id=$_GET["id"];

$movie=mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM mm_movies WHERE movie_id=$id"));

if($_SERVER["REQUEST_METHOD"]=="POST"){

$title=$_POST["title"];
$desc=$_POST["desc"];
$date=$_POST["date"];

if(!empty($_FILES["image"]["name"])){
$image=$_FILES["image"]["name"];
$tmp=$_FILES["image"]["tmp_name"];
move_uploaded_file($tmp,"../assets/images/".$image);

mysqli_query($conn,"UPDATE mm_movies SET poster_image='$image' WHERE movie_id=$id");
}

mysqli_query($conn,"
UPDATE mm_movies 
SET title='$title', short_description='$desc', release_date='$date'
WHERE movie_id=$id
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

<h1 class="admin-title">Edit Movie</h1>

<form class="admin-form" method="POST" enctype="multipart/form-data">

<div class="form-group">
    <label>Title</label>
    <input name="title" value="<?php echo $movie["title"]; ?>">
</div>

<div class="form-group">
    <label>Description</label>
    <textarea name="desc"><?php echo $movie["short_description"]; ?></textarea>
</div>

<div class="form-group">
    <label>Release Date</label>
    <input type="date" name="date" value="<?php echo $movie["release_date"]; ?>">
</div>

<div class="form-group">
    <label>Current Poster</label>
    <img src="../assets/images/<?php echo $movie["poster_image"]; ?>" style="width:150px;">
</div>

<div class="form-group">
    <label>Change Poster</label>
    <input type="file" name="image">
</div>

<div class="form-actions">
    <button class="btn btn-edit">Update</button>
</div>

</form>

</div>
<?php include("../includes/admin_footer.php"); ?>
</body>
</html>