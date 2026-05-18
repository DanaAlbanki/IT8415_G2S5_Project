<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once(__DIR__ . "/../includes/auth_check.php");
require_once("../config/DBConn.php");

$dbc = getConnection();
$creator_id = $_SESSION['user_id'];
$movie_id = 0;

if (isset($_POST['movie_id'])) {
    $movie_id = (int)$_POST['movie_id'];
} elseif (isset($_GET['movie_id'])) {
    $movie_id = (int)$_GET['movie_id'];
}

if ($movie_id <= 0) {
    die("INVALID MOVIE ID");
}

$check = mysqli_query($dbc, "
    SELECT movie_id
    FROM mm_movies
    WHERE movie_id = $movie_id
    AND creator_id = $creator_id
");

if (!$check || mysqli_num_rows($check) == 0) {
    die("NOT ALLOWED");
}

if (!empty($_FILES['media_file']['name'][0])) {

    $uploadDir = __DIR__ . "/../assets/uploads/media/";

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $files = $_FILES['media_file'];
    $count = count($files['name']);

    for ($i = 0; $i < $count; $i++) {

        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            continue;
        }
        $tmp = $files['tmp_name'][$i];
        $name = basename($files['name'][$i]);
        if (!$tmp) continue;
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp','mp4','mov','webm', 'jfif'];

        if (!in_array($ext, $allowed)) {
            continue;
        }
        $fileName = time() . "_" . rand(1000,9999) . "_" . $name;
        $target = $uploadDir . $fileName;

        if (move_uploaded_file($tmp, $target)) {
            $file_path = "assets/uploads/media/" . $fileName;
            $media_type = in_array($ext, ['mp4','mov','webm']) ? 'video' : 'image';
            mysqli_query($dbc, "
                INSERT INTO mm_movie_media
                (movie_id, media_type, file_path, file_name, uploaded_at, is_primary)
                VALUES
                ($movie_id, '$media_type', '$file_path', '$fileName', NOW(), 0)
            ");
        }
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'delete') {

    $media_id = (int)($_GET['id'] ?? 0);

    if ($media_id <= 0) {
        die("INVALID REQUEST");
    }

    $check = mysqli_query($dbc, "
        SELECT m.media_id, m.file_path
        FROM mm_movie_media m
        JOIN mm_movies mo ON m.movie_id = mo.movie_id
        WHERE m.media_id = $media_id
        AND mo.creator_id = $creator_id
    ");

    $media = mysqli_fetch_assoc($check);

    if (!$media) {
        die("NOT ALLOWED");
    }

    $filePath = __DIR__ . "/../" . $media['file_path'];

    if (file_exists($filePath)) {
        unlink($filePath);
    }

    mysqli_query($dbc, "DELETE FROM mm_movie_media WHERE media_id = $media_id");

    header("Location: movie_details.php?movie_id=$movie_id");
    exit;
}