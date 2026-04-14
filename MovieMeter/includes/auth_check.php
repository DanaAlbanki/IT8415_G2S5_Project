<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["user_id"])) {

    $loginPath = "login.php";

    // if inside admin or creator folder go one level up
    if (strpos($_SERVER['PHP_SELF'], '/admin/') !== false || 
        strpos($_SERVER['PHP_SELF'], '/creator/') !== false) {
        $loginPath = "../login.php";
    }

    header("Location: " . $loginPath);
    exit();
}
?>