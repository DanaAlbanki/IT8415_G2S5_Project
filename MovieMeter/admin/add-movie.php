<?php
require_once(__DIR__ . "/../includes/auth_check.php");
require_once(__DIR__ . "/../config/DBConn.php");

if ($_SESSION["role_name"] !== "admin") die("Access denied.");

$conn = getConnection();

if($_SERVER["REQUEST_METHOD"]=="POST"){

    $title = mysqli_real_escape_string($conn, $_POST["title"]);
    $desc = mysqli_real_escape_string($conn, $_POST["desc"]);
    $fullDesc = mysqli_real_escape_string($conn, $_POST["desc"]); 
    $date = $_POST["date"];
    $creator_id = $_SESSION["user_id"]; 

    $imageName = "default.png"; 

    if(isset($_FILES["image"]) && $_FILES["image"]["error"] == 0){

        $imageName = time() . "_" . basename($_FILES["image"]["name"]);
        $target = "../assets/images/" . $imageName;

        if(!move_uploaded_file($_FILES["image"]["tmp_name"], $target)){
            $imageName = "default.png";
        }
    }

    $sql = "
    INSERT INTO mm_movies(
        creator_id,
        title,
        short_description,
        full_description,
        release_date,
        poster_image,
        status
    )
    VALUES(
        '$creator_id',
        '$title',
        '$desc',
        '$fullDesc',
        '$date',
        '$imageName',
        'published'
    )
    ";

    if(mysqli_query($conn,$sql)){
        header("Location: manage-movies.php");
        exit;
    }else{
        echo "Error: " . mysqli_error($conn);
    }
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
    <textarea name="desc" required></textarea>
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

<?php include("../includes/admin_footer.php"); ?>

</body>
</html>