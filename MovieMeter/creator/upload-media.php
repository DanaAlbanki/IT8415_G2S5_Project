<?php
// Show PHP errors for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load authentication and database connection
require_once(__DIR__ . "/../includes/auth_check.php");
require_once("../config/DBConn.php");

// Connect to database
$dbc = getConnection();

// Get creator ID from session
$creator_id = $_SESSION['user_id'];
$movie_id = 0;

// Get movie ID from POST or GET request
if (isset($_POST['movie_id'])) {
    $movie_id = (int)$_POST['movie_id'];
} elseif (isset($_GET['movie_id'])) {
    $movie_id = (int)$_GET['movie_id'];
}

// Validate movie ID
if ($movie_id <= 0) {
    die("INVALID MOVIE ID");
}

// SQL query to check movie ownership
$check = mysqli_query($dbc, "
    SELECT movie_id
    FROM mm_movies
    WHERE movie_id = $movie_id
    AND creator_id = $creator_id
");

// Check if creator owns movie
if (!$check || mysqli_num_rows($check) == 0) {
    die("NOT ALLOWED");
}

// Check if media files are uploaded
if (!empty($_FILES['media_file']['name'][0])) {

    // Define upload folder
    $uploadDir = __DIR__ . "/../assets/uploads/media/";

    // Create folder if missing
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Get uploaded files
    $files = $_FILES['media_file'];
    $count = count($files['name']);

    // Loop through uploaded files
    for ($i = 0; $i < $count; $i++) {

        // Skip files with upload errors
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            continue;
        }

        $tmp = $files['tmp_name'][$i];
        $name = basename($files['name'][$i]);

        if (!$tmp) continue;

        // Get file extension
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        // Allowed media types
        $allowed = ['jpg','jpeg','png','webp','mp4','mov','webm', 'jfif'];

        // Validate file type
        if (!in_array($ext, $allowed)) {
            continue;
        }

        // Generate unique file name
        $fileName = time() . "_" . rand(1000,9999) . "_" . $name;
        $target = $uploadDir . $fileName;

        // Upload media file
        if (move_uploaded_file($tmp, $target)) {

            $file_path = "assets/uploads/media/" . $fileName;

            // Detect media type
            $media_type = in_array($ext, ['mp4','mov','webm']) ? 'video' : 'image';

            // SQL query to insert media record
            mysqli_query($dbc, "
                INSERT INTO mm_movie_media
                (movie_id, media_type, file_path, file_name, uploaded_at, is_primary)
                VALUES
                ($movie_id, '$media_type', '$file_path', '$fileName', NOW(), 0)
            ");
        }
    }
}

// Check if delete action is requested
if (isset($_GET['action']) && $_GET['action'] === 'delete') {

    // Get media ID
    $media_id = (int)($_GET['id'] ?? 0);

    // Validate media ID
    if ($media_id <= 0) {
        die("INVALID REQUEST");
    }

    // SQL query to check media ownership
    $check = mysqli_query($dbc, "
        SELECT m.media_id, m.file_path
        FROM mm_movie_media m
        JOIN mm_movies mo ON m.movie_id = mo.movie_id
        WHERE m.media_id = $media_id
        AND mo.creator_id = $creator_id
    ");

    // Fetch media details
    $media = mysqli_fetch_assoc($check);

    // Validate media ownership
    if (!$media) {
        die("NOT ALLOWED");
    }

    // Build file path
    $filePath = __DIR__ . "/../" . $media['file_path'];

    // Delete media file from storage
    if (file_exists($filePath)) {
        unlink($filePath);
    }

    // SQL query to delete media record
    mysqli_query($dbc, "DELETE FROM mm_movie_media WHERE media_id = $media_id");

    // Redirect back to movie details
    header("Location: movie_details.php?movie_id=$movie_id");
    exit;
}