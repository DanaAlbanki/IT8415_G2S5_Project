<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<nav class="admin-navbar">

    <div class="admin-logo">
        <img src="../assets/images/logo.png" alt="Logo">
    </div>

    <ul class="admin-nav-links">
        <li><a href="dashboard.php" class="active">Dashboard</a></li>
        <li><a href="manage-users.php" class="active">Users</a></li>
        <li><a href="manage-movies.php" class="active">Movies</a></li>
        <li><a href="manage-comments.php" class="active">Comments</a></li>
        <li><a href="reports.php" class="active">Reports</a></li>
        <li><a href="Settings.php" class="active">Settings</a></li>
    </ul>

    <div class="admin-right">
        <span class="admin-user">
            <?php echo $_SESSION["username"] ?? "Admin"; ?>
        </span>
        <a href="../logout.php" class="btn btn-delete">Logout</a>
    </div>

</nav>