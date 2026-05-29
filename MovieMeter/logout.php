<?php
session_start();

// remove all session variables
session_unset();

// destroy the session completely
session_destroy();

// redirect user to login page
header("Location: login.php");
exit();
?>


