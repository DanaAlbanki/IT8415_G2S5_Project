<?php
require_once("../config/database.php");

$id = $_GET['id'];

$conn->query("DELETE FROM mm_users WHERE user_id = $id");

header("Location: manage-users.php");
?>