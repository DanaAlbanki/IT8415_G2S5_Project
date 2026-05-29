<?php
// redirects to login if user is not logged in

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["user_id"])) {

    $loginPath = "login.php";

    if (strpos($_SERVER['PHP_SELF'], '/admin/') !== false || 
        strpos($_SERVER['PHP_SELF'], '/creator/') !== false) {
        $loginPath = "../login.php";
    }

    header("Location: " . $loginPath);
    exit();
}
?>