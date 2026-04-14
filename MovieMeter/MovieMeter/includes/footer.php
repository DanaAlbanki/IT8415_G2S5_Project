<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<div style="background:#222; color:white; padding:15px 0;">
    <div class="container" style="display:flex; justify-content:space-between; align-items:center;">
        <div>
            <a href="index.php" style="color:white; text-decoration:none; font-size:22px; font-weight:bold;">
                MovieMeter
            </a>
        </div>

        <div>
            <?php if (isset($_SESSION["full_name"])) { ?>
                <span style="margin-right:15px;">
                    Welcome, <?php echo htmlspecialchars($_SESSION["full_name"]); ?>
                </span>
            <?php } ?>

            <a href="index.php" style="color:white; margin-right:15px; text-decoration:none;">Home</a>
            <a href="logout.php" style="color:white; text-decoration:none;">Logout</a>
        </div>
    </div>
</div>