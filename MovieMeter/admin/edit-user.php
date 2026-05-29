<?php
// form to update a user's name and email

require_once(__DIR__ . "/../includes/auth_check.php");
require_once(__DIR__ . "/../config/DBConn.php");

if ($_SESSION["role_name"] !== "admin") die("Access denied.");

$conn=getConnection();
$id=$_GET["id"];

$user=mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM mm_users WHERE user_id=$id"));

if($_SERVER["REQUEST_METHOD"]=="POST"){
$name=$_POST["name"];
$email=$_POST["email"];

mysqli_query($conn,"
UPDATE mm_users SET full_name='$name', email='$email'
WHERE user_id=$id
");

header("Location: manage-users.php");
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

<h1 class="admin-title">Edit User</h1>

<form class="admin-form" method="POST">

<div class="form-group">
    <label>Name</label>
    <input name="name" value="<?php echo $user["full_name"]; ?>">
</div>

<div class="form-group">
    <label>Email</label>
    <input name="email" value="<?php echo $user["email"]; ?>">
</div>

<div class="form-actions">
    <button class="btn btn-edit">Update</button>
</div>

</form>

</div>
<?php include("../includes/admin_footer.php"); ?>
</body>
</html>