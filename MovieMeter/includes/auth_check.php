<?php
// Start session if it is not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is not logged in
if (!isset($_SESSION["user_id"])) {

    // Default login path
    $loginPath = "login.php";

    // If inside admin or creator folder, go one level up
    if (strpos($_SERVER['PHP_SELF'], '/admin/') !== false || 
        strpos($_SERVER['PHP_SELF'], '/creator/') !== false) {
        $loginPath = "../login.php";
    }

    // Redirect to login page
    header("Location: " . $loginPath);
    exit();
}
?>