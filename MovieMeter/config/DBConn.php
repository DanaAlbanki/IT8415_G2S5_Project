<?php
include("config.php");

function getConnection(){
    global $host, $user, $pass, $db;

    $dbc = mysqli_connect($host, $user, $pass, $db);

    if(!$dbc){
        die("Connection failed: " . mysqli_connect_error());
    }

    return $dbc;
}
?>