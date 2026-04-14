<?php
require_once(__DIR__ . "/../includes/auth_check.php");
require_once(__DIR__ . "/../config/DBConn.php");

if ($_SESSION["role_name"] !== "admin") die("Access denied.");

$conn = getConnection();

if($_SERVER["REQUEST_METHOD"]=="POST"){
$name=$_POST["name"];
$email=$_POST["email"];
$username=$_POST["username"];
$password=password_hash($_POST["password"],PASSWORD_DEFAULT);

mysqli_query($conn,"
INSERT INTO mm_users(full_name,email,username,password_hash,role_id,account_status)
VALUES('$name','$email','$username','$password',3,'active')
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

<h1 class="admin-title">Add User</h1>

<form class="admin-form" method="POST">

<div class="form-group">
    <label>Full Name</label>
    <input name="name" required>
</div>

<div class="form-group">
    <label>Email</label>
    <input name="email" required>
</div>

<div class="form-group">
    <label>Username</label>
    <input name="username" required>
</div>

<div class="form-group">
    <label>Password</label>
    <input type="password" name="password" required>
</div>

<div class="form-actions">
    <button class="btn btn-add">Add User</button>
</div>

</form>

</div>
<?php include("../includes/admin_footer.php"); ?>
</body>
</html>