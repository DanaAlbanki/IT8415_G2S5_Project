<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once(__DIR__ . "/../includes/auth_check.php");
require_once("../config/DBConn.php");

if ($_SESSION["role_name"] !== "creator") {
    die("Access denied.");
}

$dbc = getConnection();

$message = "";
$errors = 0;
$inserted = 0;

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $pages = isset($_POST["pages"]) ? (int)$_POST["pages"] : 1;
    $pages = max(1, min(20, $pages));

    $apiKey = "d29868793eba2f4ccb7cb36fa101c2a8";

    for ($page = 1; $page <= $pages; $page++) {

        $url = "https://api.themoviedb.org/3/movie/popular?api_key=$apiKey&page=$page";

        $response = file_get_contents($url);
        $data = json_decode($response, true);

        if (empty($data["results"])) continue;

        foreach ($data["results"] as $movie) {

            $tmdb_id = (int)$movie["id"];
            $title = mysqli_real_escape_string($dbc, $movie["title"] ?? "Untitled");

            $poster = !empty($movie["poster_path"])
                ? "https://image.tmdb.org/t/p/w500" . $movie["poster_path"]
                : "assets/images/placeholder.png";

$overview = substr($movie["overview"] ?? "", 0, 255);
$overview = mysqli_real_escape_string($dbc, $overview);

            $check = mysqli_query($dbc, "
                SELECT movie_id 
                FROM mm_movies 
                WHERE external_api_id = '$tmdb_id'
                LIMIT 1
            ");

            if (mysqli_num_rows($check) > 0) {
                continue; 
            }

            $sql = "
                INSERT INTO mm_movies (
                    title,
                    short_description,
                    full_description,
                    poster_image,
                    status,
                    creator_id,
                    is_api_imported,
                    external_api_source,
                    external_api_id,
                    created_at,
                    updated_at,
                    published_at
                ) VALUES (
                    '$title',
                    '$overview',
                    '$overview',
                    '$poster',
                    'published',
                    {$_SESSION['user_id']},
                    1,
                    'tmdb',
                    '$tmdb_id',
                    NOW(),
                    NOW(),
                    NOW()
                )
            ";

            if (mysqli_query($dbc, $sql)) {
                $inserted++;
            } else {
                $errors++;
            }
        }
    }

    $message = "Import completed. Inserted: $inserted | Errors: $errors";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Import Movies</title>
<link rel="stylesheet" href="../assets/css/import.css">

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
<div class="box">

<h2>Import Movies from TMDB</h2>

<?php if ($message) { ?>
    <div class="analytics-card">
        <div class="analytics-value" style="font-size: 1rem;">
            <?php echo $message; ?>
        </div>
    </div>
<?php } ?>

<form method="POST">

    <div class="form-row">
        <label>Number of pages (1–20):</label><br>
        <input type="number" name="pages" min="1" max="20" value="1">
    </div>

    <div class="actions">
        <button type="submit" class="btn">Import Movies</button>
    </div>

</form>

<br>
<div class="analytics-card">
    <h4>Import Status</h4>
    <div class="analytics-value" style="font-size: 1rem;">
        Ready to fetch movies from TMDB
    </div>
</div>

<div class="actions">
    <a href="../creator/my-movie.php" class="btn btn-secondary">My Movies</a>
</div>
</div>
<?php include("../includes/creator_footer.php"); ?>

</body>

<script>
window.addEventListener("scroll", function () {
    const navbar = document.querySelector(".navbar");

    if (window.scrollY > 50) {
        navbar.classList.add("scrolled");
    } else {
        navbar.classList.remove("scrolled");
    }
});
</script>
</html>

