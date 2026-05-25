<?php
// Fetches the current admin user's details from the database and renders them in a profile view with secure output encoding.

require_once(__DIR__ . "/../includes/auth_check.php");
require_once(__DIR__ . "/../config/DBConn.php");

if ($_SESSION["role_name"] !== "admin") die("Access denied.");

$userId = (int) $_SESSION["user_id"];
$conn = getConnection();
mysqli_set_charset($conn, "utf8mb4");

$sql = "
SELECT 
    u.full_name,
    u.username,
    u.email,
    u.created_at,
    r.role_name
FROM mm_users u
LEFT JOIN mm_roles r ON u.role_id = r.role_id
WHERE u.user_id = ?
LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

$fullName = htmlspecialchars($user["full_name"]);
$username = htmlspecialchars($user["username"]);
$email = htmlspecialchars($user["email"]);
$roleName = htmlspecialchars($user["role_name"]);

$avatarLetter = strtoupper(substr($fullName, 0, 1));

$memberSince = date("F Y", strtotime($user["created_at"]));
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

<h1 class="admin-title">Admin Profile</h1>

<div class="profile-section-clean">

<div class="profile-top">
    <div class="profile-avatar"><?php echo $avatarLetter; ?></div>

    <div class="profile-intro">
        <h2><?php echo $fullName; ?></h2>
        <p><?php echo ucfirst($roleName); ?> Account</p>
    </div>
</div>

<div class="profile-info-list">

<div class="profile-info-row">
    <div class="profile-label">Full Name</div>
    <div class="profile-value"><?php echo $fullName; ?></div>
</div>

<div class="profile-info-row">
    <div class="profile-label">Username</div>
    <div class="profile-value"><?php echo $username; ?></div>
</div>

<div class="profile-info-row">
    <div class="profile-label">Email</div>
    <div class="profile-value"><?php echo $email; ?></div>
</div>

<div class="profile-info-row">
    <div class="profile-label">Member Since</div>
    <div class="profile-value"><?php echo $memberSince; ?></div>
</div>

</div>

<div class="profile-actions">
<a href="edit-admin-profile.php" class="profile-btn profile-btn-primary">Edit Profile</a>
</div>

</div>

</div>

<?php include("../includes/admin_footer.php"); ?>
</body>
</html>