<?php
// Start session if it is not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get current page file name
$current = basename($_SERVER['PHP_SELF']);
?>

<?php
// Pages that should not show the back button
$noBackPages = [
    'dashboard.php',
    'manage-users.php',
    'manage-movies.php',
    'manage-comments.php',
    'reports.php',
    'admin-profile.php'
];

// Show back button only on pages not listed above
if (!in_array($current, $noBackPages)) {
?>
<div class="admin-back">
    <a href="javascript:history.back()">← Back</a>
</div>
<?php } ?>

<nav class="admin-navbar">

    <!-- Admin logo -->
    <div class="admin-logo">
        <img src="../assets/images/logo.png" alt="Logo">
    </div>

    <!-- Admin navigation links -->
    <ul class="admin-nav-links">
        <li><a href="dashboard.php" class="<?= ($current=='dashboard.php')?'active':'' ?>">Dashboard</a></li>
        <li><a href="manage-users.php" class="<?= ($current=='manage-users.php')?'active':'' ?>">Users</a></li>
        <li><a href="manage-movies.php" class="<?= ($current=='manage-movies.php')?'active':'' ?>">Movies</a></li>
        <li><a href="manage-comments.php" class="<?= ($current=='manage-comments.php')?'active':'' ?>">Comments</a></li>
        <li><a href="reports.php" class="<?= ($current=='reports.php')?'active':'' ?>">Reports</a></li>
        <li><a href="admin-profile.php" class="<?= ($current=='admin-profile.php')?'active':'' ?>">Profile</a></li>
    </ul>

    <!-- Logout button -->
    <div class="admin-right">
        <a href="../logout.php" class="admin-logout">Logout</a>
    </div>

</nav>