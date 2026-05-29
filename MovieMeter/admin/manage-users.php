<?php
// lists all users with edit and delete options

require_once(__DIR__ . "/../includes/auth_check.php");
require_once(__DIR__ . "/../config/DBConn.php");

if ($_SESSION["role_name"] !== "admin") die("Access denied.");

$conn = getConnection();

$result = mysqli_query($conn,"
SELECT u.user_id, u.full_name, u.email, r.role_name
FROM mm_users u
LEFT JOIN mm_roles r ON u.role_id = r.role_id
ORDER BY u.user_id DESC
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
<h1 class="admin-title">Manage Users</h1>
<a href="add-user.php" class="btn btn-add">+ Add User</a>
</div>

<table class="admin-table">
<tr>
<th>Name</th>
<th>Email</th>
<th>Role</th>
<th>Actions</th>
</tr>

<?php while($row=mysqli_fetch_assoc($result)){ ?>
<tr>
<td><?php echo htmlspecialchars($row["full_name"]); ?></td>
<td><?php echo htmlspecialchars($row["email"]); ?></td>
<td><?php echo $row["role_name"]; ?></td>
<td>
<a href="edit-user.php?id=<?php echo $row["user_id"]; ?>" class="btn btn-edit">Edit</a>
<a href="delete-user.php?id=<?php echo $row["user_id"]; ?>" 
class="btn btn-delete"
onclick="return confirm('Are you sure?');">Delete</a>
</td>
</tr>
<?php } ?>

</table>

</div>
<?php include("../includes/admin_footer.php"); ?>
</body>
</html>