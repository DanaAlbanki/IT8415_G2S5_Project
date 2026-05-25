<?php
//Deletes a specific movie from the database based on the provided ID and redirects the administrator back to the movie management page.

require_once(__DIR__ . "/../includes/auth_check.php");
require_once(__DIR__ . "/../config/DBConn.php");

// Ensure only admins can perform delete operations
if ($_SESSION["role_name"] !== "admin") {
    die("Access denied.");
}

$conn = getConnection();

// 1. Sanitize the ID: Ensure it is treated strictly as an integer
$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

if ($id > 0) {
    // 2. Use a prepared statement to prevent SQL Injection
    // This separates the SQL command from the data being deleted
    $stmt = $conn->prepare("DELETE FROM mm_movies WHERE movie_id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        // Successfully deleted
    } else {
        // Handle potential errors here (e.g., logging)
    }
    $stmt->close();
}

// 3. Always redirect after a destructive operation
header("Location: manage-movies.php");
exit;
?>