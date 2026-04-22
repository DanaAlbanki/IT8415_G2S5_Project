<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current = basename($_SERVER['PHP_SELF']);
?>

<?php
$noBackPages = [
    'dashboard.php',
    'manage-users.php',
    'manage-movies.php',
    'manage-comments.php',
    'reports.php',
    'admin-profile.php'
];

if (!in_array($current, $noBackPages)) {
?>
<div class="admin-back">
    <a href="javascript:history.back()">← Back</a>
</div>
<?php } ?>

<nav class="admin-navbar">

    <div class="admin-logo">
        <img src="../assets/images/logo.png" alt="Logo">
    </div>

    <ul class="admin-nav-links">
        <li><a href="dashboard.php" class="<?= ($current=='dashboard.php')?'active':'' ?>">Dashboard</a></li>
        <li><a href="manage-users.php" class="<?= ($current=='manage-users.php')?'active':'' ?>">Users</a></li>
        <li><a href="manage-movies.php" class="<?= ($current=='manage-movies.php')?'active':'' ?>">Movies</a></li>
        <li><a href="manage-comments.php" class="<?= ($current=='manage-comments.php')?'active':'' ?>">Comments</a></li>
        <li><a href="reports.php" class="<?= ($current=='reports.php')?'active':'' ?>">Reports</a></li>
        <li><a href="admin-profile.php" class="<?= ($current=='admin-profile.php')?'active':'' ?>">Profile</a></li>
    </ul>

    <div class="admin-right">
        <a href="../logout.php" class="admin-logout">Logout</a>
    </div>

</nav>