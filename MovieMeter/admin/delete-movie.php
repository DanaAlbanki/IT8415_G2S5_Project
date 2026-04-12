<?php
require_once("../config/database.php");

$id = $_GET['id'];

$conn->query("UPDATE mm_movies SET status='deleted', deleted_at=NOW() WHERE movie_id=$id");

header("Location: manage-movies.php");
?>