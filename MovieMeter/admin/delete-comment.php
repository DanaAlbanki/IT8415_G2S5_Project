<?php
require_once("../config/database.php");

$id = $_GET['id'];

$conn->query("UPDATE mm_comments SET comment_status='deleted', deleted_at=NOW() WHERE comment_id=$id");

header("Location: manage-comments.php");
?>