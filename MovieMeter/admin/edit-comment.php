<?php
// form to edit a user comment

require_once(__DIR__ . "/../includes/auth_check.php");
require_once(__DIR__ . "/../config/DBConn.php");

if ($_SESSION["role_name"] !== "admin") die("Access denied.");

$conn=getConnection();
$id=$_GET["id"];

$comment=mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM mm_comments WHERE comment_id=$id"));

if($_SERVER["REQUEST_METHOD"]=="POST"){
$text=$_POST["text"];

mysqli_query($conn,"UPDATE mm_comments SET comment_text='$text' WHERE comment_id=$id");

header("Location: manage-comments.php");
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

<h1 class="admin-title">Edit Comment</h1>

<form class="admin-form" method="POST">

<div class="form-group">
    <label>Comment</label>
    <textarea name="text"><?php echo $comment["comment_text"]; ?></textarea>
</div>

<div class="form-actions">
    <button class="btn btn-edit">Update</button>
</div>

</form>

</div>
<?php include("../includes/admin_footer.php"); ?>
</body>
</html>