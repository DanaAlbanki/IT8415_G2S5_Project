<?php
// Include database configuration file
require_once(__DIR__ . "/config.php");

function getConnection(){
    // Use database settings from config.php
    global $host, $user, $pass, $db;

    // Create database connection
    $dbc = mysqli_connect($host, $user, $pass, $db);

    // Stop if connection fails
    if(!$dbc){
        die("Connection failed: " . mysqli_connect_error());
    }

    // Return active connection
    return $dbc;
}
?>