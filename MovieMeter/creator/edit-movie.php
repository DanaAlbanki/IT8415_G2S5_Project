<?php
// Show PHP errors for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load authentication and database connection
require_once(__DIR__ . "/../includes/auth_check.php");
require_once("../config/DBConn.php");

// Allow creators only
if ($_SESSION["role_name"] !== "creator") {
    die("Access denied.");
}

// Connect to database
$dbc = getConnection();

// Get movie ID and creator ID
$movie_id = isset($_GET['movie_id']) ? (int)$_GET['movie_id'] : 0;
$creator_id = $_SESSION['user_id'];

// SQL query to get movie details
$sql = "
    SELECT *
    FROM mm_movies
    WHERE movie_id = $movie_id
    AND creator_id = $creator_id
";

// Execute movie query
$result = mysqli_query($dbc, $sql);
$movie = mysqli_fetch_assoc($result);

// Check if movie exists
if (!$movie) {
    die("Movie not found.");
}

// Run when form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Escape form inputs
    $title = mysqli_real_escape_string($dbc, $_POST['title']);
    $short_description = mysqli_real_escape_string($dbc, $_POST['short_description']);
    $full_description = mysqli_real_escape_string($dbc, $_POST['full_description']);
    $release_date = !empty($_POST['release_date'])
        ? "'" . mysqli_real_escape_string($dbc, $_POST['release_date']) . "'"
        : "NULL";

    // Keep current poster image
    $poster_image = $movie['poster_image'];

    // Check if new poster is uploaded
    if (!empty($_FILES['poster_file']['name'])) {

        $uploadDir = __DIR__ . "/../assets/uploads/media/";
        $fileName = time() . "_" . basename($_FILES['poster_file']['name']);
        $targetFile = $uploadDir . $fileName;

        // Get file extension
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // Allowed image types
        $allowed = ['jpg','jpeg','png','webp'];

        // Validate image type
        if (in_array($ext, $allowed)) {

            // Upload new poster image
            if (move_uploaded_file($_FILES['poster_file']['tmp_name'], $targetFile)) {
                $poster_image = "assets/uploads/media/" . $fileName;
            }
        }
    }

    // SQL query to update movie details
    $update_sql = "
        UPDATE mm_movies
        SET title = '$title',
            short_description = '$short_description',
            full_description = '$full_description',
            release_date = $release_date,
            poster_image = '$poster_image'
        WHERE movie_id = $movie_id AND creator_id = $creator_id
    ";

    // Execute update query
    mysqli_query($dbc, $update_sql);

    // Check if media files were uploaded
    if (!empty($_FILES['media_file']['name'][0])) {
        $_POST['action'] = 'upload';
        $_POST['movie_id'] = $movie_id;

        include(__DIR__ . "/upload-media.php");
    }

    // Show success message and redirect
    echo "<script>alert('Movie updated successfully!'); window.location='movie_details.php?movie_id=$movie_id';</script>";
    exit;
}

// Get poster image path
$poster = $movie['poster_image'] ?? '';
$poster_path = (!empty($poster))
    ? ((strpos($poster, 'http') === 0) ? $poster : '../' . $poster)
    : '../assets/images/placeholder.png';

// SQL query to get movie media files
$media_sql = "
    SELECT *
    FROM mm_movie_media
    WHERE movie_id = $movie_id
    ORDER BY is_primary DESC, uploaded_at DESC
";

// Execute media query
$media_result = mysqli_query($dbc, $media_sql);
?>

<!DOCTYPE html>
<html>
<head>
<title>Edit Movie</title>
<link rel="stylesheet" href="../assets/css/creator-form.css">
</head>

<body>
<div class="top-back">
    <a href="movie_details.php?movie_id=<?php echo $movie_id; ?>"
       class="back-link"
       onclick="event.preventDefault(); goBackSmart(this.href);">
        ← Back
    </a>
</div>
<div class="container">

<form method="POST" enctype="multipart/form-data" style="display:contents;">

<div>

    <div class="stat">
        <div class="label">Poster Preview</div>

        <img id="posterPreview"
             class="poster"
             src="<?php echo htmlspecialchars($poster_path); ?>">

        <input type="file" name="poster_file" accept="image/*">
    </div>

    <div class="details-box">
        <div class="stat">
            <div class="label">Short Description</div>
            <textarea name="short_description"><?php echo htmlspecialchars($movie['short_description']); ?></textarea>
        </div>
    </div>

</div>

<div class="right-box">

    <div class="stat">
        <div class="label">Title</div>
        <input type="text" name="title"
               value="<?php echo htmlspecialchars($movie['title']); ?>">
    </div>

    <div class="stat">
        <div class="label">Full Description</div>
        <textarea name="full_description"><?php echo htmlspecialchars($movie['full_description']); ?></textarea>
    </div>

    <div class="stat">
        <div class="label">Release Date</div>
        <input type="date" name="release_date"
               value="<?php echo htmlspecialchars($movie['release_date']); ?>">
    </div>

    <div class="stat">
        <div class="label">Media</div>

        <input type="file"
               id="mediaInput"
               name="media_file[]"
               multiple
               accept="image/*,video/*">

        <div class="media-row" id="mediaPreview"></div>

        <div class="media-row">

            <?php while ($m = mysqli_fetch_assoc($media_result)) { ?>

                <div class="media-item" style="position:relative;">

                    <a href="upload-media.php?action=delete&id=<?php echo $m['media_id']; ?>&movie_id=<?php echo $movie_id; ?>"
                       onclick="return confirm('Delete this media?');"
                       style="
                            position:absolute;
                            top:2px;
                            right:2px;
                            background:red;
                            color:white;
                            border:none;
                            border-radius:4px;
                            padding:3px 6px;
                            font-size:12px;
                            cursor:pointer;
                            z-index:10;
                            text-decoration:none;
                       ">
                        X
                    </a>

                    <?php if ($m['media_type'] === 'image') { ?>
                        <img src="../<?php echo $m['file_path']; ?>"
                             style="width:100%;height:100%;object-fit:cover;">
                    <?php } else { ?>
                        <video muted style="width:100%;height:100%;object-fit:cover;">
                            <source src="../<?php echo $m['file_path']; ?>">
                        </video>
                    <?php } ?>

                </div>

            <?php } ?>

        </div>

    </div>

    <div style="margin-top:10px;">

        <?php
        // Set publish button text and color
        $current_status = $movie['status'] ?? 'draft';
        $btn_text = ($current_status === 'published') ? 'Unpublish Movie' : 'Publish Movie';
        $btn_color = ($current_status === 'published') ? '#ef4444' : '#22c55e';
        ?>

        <a href="publish.php?movie_id=<?php echo $movie_id; ?>"
           style="
                display:inline-block;
                background:<?php echo $btn_color; ?>;
                color:white;
                padding:6px 14px;
                border-radius:8px;
                font-size:13px;
                font-weight:bold;
                text-decoration:none;
           ">
            <?php echo $btn_text; ?>
        </a>

    </div>

    <div style="margin-top:6px;">
        <button type="submit"
                style="
                    padding:6px 14px;
                    border:none;
                    border-radius:8px;
                    background:#facc15;
                    color:#1f1229;
                    font-weight:bold;
                    cursor:pointer;
                    font-size:13px;
                ">
            Save Changes
        </button>
    </div>

</div>

</form>

</div>

<script>
document.querySelector('input[name="poster_file"]').addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = e => document.getElementById('posterPreview').src = e.target.result;
    reader.readAsDataURL(file);
});

function goBackSmart(fallbackUrl) {
    try {
        if (window.history.length > 1 && document.referrer) {
            const referrerUrl = new URL(document.referrer, window.location.origin);

            if (referrerUrl.origin === window.location.origin) {
                window.history.back();
                return;
            }
        }
    } catch (e) {}

    window.location.href = fallbackUrl;
}
</script>

</body>
</html>