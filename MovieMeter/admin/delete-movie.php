<?php
//Deletes a specific movie from the database based on the provided ID and redirects the administrator back to the movie management page.

require_once(__DIR__ . "/../includes/auth_check.php");
require_once(__DIR__ . "/../config/DBConn.php");

// Ensure only admins can perform delete operations
if ($_SESSION["role_name"] !== "admin") {
    die("Access denied.");
}

$conn = getConnection();

$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

if ($id > 0) {
    $stmt = $conn->prepare("DELETE FROM mm_movies WHERE movie_id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
    } else {
    }
    $stmt->close();
}

header("Location: manage-movies.php");
exit;
?>