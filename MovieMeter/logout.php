<?php
session_start();

// Remove all session variables
session_unset();

// Destroy the session completely
session_destroy();

// Redirect user to login page
header("Location: login.php");
exit();
?>


