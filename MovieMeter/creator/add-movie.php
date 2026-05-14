<?php
require_once(__DIR__ . "/../includes/auth_check.php");
require_once("../config/DBConn.php");

if ($_SESSION["role_name"] !== "creator") {
    die("Access denied.");
}

$dbc = getConnection();
$creator_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $title = $_POST['title'];
    $release_date = $_POST['release_date'] ?? '';
    $short = $_POST['short_description'];
    $full  = $_POST['full_description'];
    $trailer = $_POST['trailer_url'];

    
    if (empty($release_date)) {
        echo "<script>
            alert('Release date is required!');
            window.history.back();
        </script>";
        exit;
    }
    
    $poster = "";
    if (!empty($_FILES['poster']['name'])) {
        $uploadDir = __DIR__ . "/../assets/uploads/media/";
        $fileName = time() . "_" . basename($_FILES['poster']['name']);
        if (move_uploaded_file($_FILES['poster']['tmp_name'], $uploadDir . $fileName)) {
            $poster = "assets/uploads/media/" . $fileName;
        }
    }
    
    $sql = "
        INSERT INTO mm_movies
        (creator_id, title, short_description, full_description, trailer_url, poster_image, release_date, created_at, updated_at)
        VALUES
        ($creator_id, '$title', '$short', '$full', '$trailer', '$poster', '$release_date', NOW(), NOW())
    ";

    mysqli_query($dbc, $sql);
    $movie_id = mysqli_insert_id($dbc);
    
    if (!empty($_FILES['media_file']['name'][0])) {
        $_POST['action'] = 'upload';
        $_POST['movie_id'] = $movie_id;
        include(__DIR__ . "/upload-media.php");
    }

    header("Location: my-movie.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Add Movie</title>

<link rel="stylesheet" href="../assets/css/creator-form.css">

</head>

<body>

<div class="navbar">
    <div class="logo">
        <img src="../assets/images/logo.png">
    </div>

 <div class="nav-links">
        <a href="../creator/dashboard.php" class="active-link">Dashboard</a>
        <a href="../creator/my-movie.php">My Movies</a>
        <a href="../creator/add-movie.php">Add Movie</a>
        <a href="../creator/import-movies.php">Import Movies</a>
        <a href="../creator/profile.php">Profile</a>
        <a href="../logout.php">Logout</a>
    </div>
</div>

<div class="container">

<form method="POST" enctype="multipart/form-data" style="display:contents;">

<div>

    <div class="stat">
        <div class="label">Poster</div>
        <input type="file" name="poster">
    </div>

    <div class="details-box">
        <div class="stat">
            <div class="label">Short Description</div>
            <textarea name="short_description"></textarea>
        </div>
    </div>

</div>

<div class="right-box">

    <div class="stat">
        <div class="label">Title</div>
        <input type="text" name="title" required>
    </div>

    <div class="stat">
        <div class="label">Full Description</div>
        <textarea name="full_description"></textarea>
    </div>

    <div class="stat">
        <div class="label">Release Date</div>
        <input type="date" name="release_date" id="release_date">

        <small id="dateWarning" style="color:#ff6b6b; display:none;">
            Release date is required
        </small>
    </div>

    <div class="stat">
        <div class="label">Media</div>

        <input type="file"
               id="mediaInput"
               name="media_file[]"
               multiple
               accept="image/*,video/*">

        <div class="media-row" id="mediaPreview"></div>
    </div>

    <div class="stat">
        <div class="label">Trailer URL</div>
        <input type="text" name="trailer_url">
    </div>

    <button type="submit">Add Movie</button>

</div>

</form>

</div>

<script>
document.querySelector("form").addEventListener("submit", function(e) {
    const date = document.getElementById("release_date").value;
    const warning = document.getElementById("dateWarning");

    if (!date) {
        e.preventDefault();
        warning.style.display = "block";
    }
});

window.addEventListener("scroll", function () {
    const navbar = document.querySelector(".navbar");

    if (window.scrollY > 50) {
        navbar.classList.add("scrolled");
    } else {
        navbar.classList.remove("scrolled");
    }
});

const mediaInput = document.getElementById('mediaInput');
const preview = document.getElementById('mediaPreview');

let selectedFiles = [];

mediaInput.addEventListener('change', function () {

    selectedFiles = Array.from(this.files);
    preview.innerHTML = "";

    selectedFiles.forEach((file, index) => {

        const reader = new FileReader();

        reader.onload = function (e) {

            const div = document.createElement("div");
            div.className = "media-item";
            div.style.position = "relative";

            div.innerHTML = `
                <button type="button"
                    onclick="removeFile(${index})"
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
">
                    X
                </button>

                ${file.type.startsWith("image")
                    ? `<img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover;">`
                    : `<video muted style="width:100%;height:100%;object-fit:cover;" src="${e.target.result}"></video>`
                }
            `;

            preview.appendChild(div);
        };

        reader.readAsDataURL(file);
    });

    updateInputFiles();
});

function removeFile(index) {
    selectedFiles.splice(index, 1);
    updateInputFiles();
    refreshPreview();
}

function updateInputFiles() {
    const dt = new DataTransfer();
    selectedFiles.forEach(file => dt.items.add(file));
    mediaInput.files = dt.files;
}

function refreshPreview() {
    mediaInput.dispatchEvent(new Event('change'));
}
</script>
<?php include("../includes/creator_footer.php"); ?>

</body>
</html>