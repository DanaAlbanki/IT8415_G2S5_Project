<?php
require_once(__DIR__ . "/../includes/auth_check.php");
require_once(__DIR__ . "/../config/DBConn.php");

if ($_SESSION["role_name"] !== "admin") die("Access denied.");

$conn = getConnection();
$userId = (int) $_SESSION["user_id"];

$user = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT full_name, email 
FROM mm_users 
WHERE user_id = $userId
"));

if($_SERVER["REQUEST_METHOD"]=="POST"){
$name = $_POST["name"];
$email = $_POST["email"];

mysqli_query($conn,"
UPDATE mm_users 
SET full_name='$name', email='$email'
WHERE user_id=$userId
");

header("Location: admin-profile.php");
exit;
}
?>

<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/profile.css">
<link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body>

<?php include("../includes/admin_nav.php"); ?>

<div class="admin-container">

<h1 class="admin-title">Edit Profile</h1>

<form class="admin-form" method="POST">

<div class="form-group">
<label>Full Name</label>
<input name="name" value="<?php echo $user["full_name"]; ?>" required>
</div>

<div class="form-group">
<label>Email</label>
<input name="email" value="<?php echo $user["email"]; ?>" required>
</div>

<div class="form-actions" style="text-align:center; margin-top:20px;">
<button class="btn btn-edit">Update Profile</button>
</div>

</form>

</div>
<?php include("../includes/admin_footer.php"); ?>
</body>
</html>