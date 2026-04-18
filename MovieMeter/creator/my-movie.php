<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . "/../includes/auth_check.php");
require_once("../config/DBConn.php");

if ($_SESSION["role_name"] !== "creator") {
    die("Access denied.");
}

$dbc = getConnection();
$creator_id = $_SESSION['user_id'];

$sql = "
    SELECT movie_id, title, poster_image, published_at, short_description, status
    FROM mm_movies
    WHERE creator_id = $creator_id
    AND status != 'deleted'
    ORDER BY release_date DESC
";

$result = mysqli_query($dbc, $sql);
?>

<!DOCTYPE html>
<html>
<head>
<title>My Movies</title>


</head>
<link rel="stylesheet" href="../assets/css/my-movie.css">

<body>
<div class="navbar">
    <div class="logo">
        <img src="../assets/images/logo.png">
    </div>

    <div class="nav-links">
        <a href="../creator/dashboard.php">Dashboard</a>
        <a href="../creator/my-movie.php" class="active-link">My Movies</a>
        <a href="../creator/add-movie.php">Add Movie</a>
        <a href="../creator/import-movies.php">Import Movies</a>
        <a href="../creator/profile.php">Profile</a>
    </div>
</div>

<div class="box">
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
    <h2 style="margin:0;">My Movies</h2>
    <a href="add-movie.php"
       style="
            background:#facc15;
            color:#1f1229;
            padding:8px 14px;
            border-radius:8px;
            font-weight:bold;
            text-decoration:none;
            transition:0.3s ease;
       "
       onmouseover="this.style.opacity='0.8'"
       onmouseout="this.style.opacity='1'">
        + Add Movie
    </a>
</div>
    <hr>
<div class="movies-wrapper">
<?php if (mysqli_num_rows($result) > 0) { ?>
    <?php while ($movie = mysqli_fetch_assoc($result)) { ?>

        <?php
        $poster = $movie["poster_image"];

        if (!empty($poster)) {
            $poster_path = (strpos($poster, 'http') === 0)
                ? $poster
                : '../' . $poster;
        } else {
            $poster_path = '../assets/images/placeholder.png';
        }
        ?>
        <article class="movie-card">
            <a href="movie_details.php?movie_id=<?php echo (int)$movie["movie_id"]; ?>">
                <img src="<?php echo htmlspecialchars($poster_path); ?>">
                <?php if (!empty($movie['title'])) { ?>
                    <h3><?php echo htmlspecialchars($movie["title"]); ?></h3>
                <?php } ?>
                    
                <?php if (!empty($movie['short_description'])) { ?>
                    <h3><?php echo htmlspecialchars($movie["short_description"]); ?></h3>
                <?php } ?>

                <?php if (!empty($movie["published_at"])) { ?>
                    <p class="movie-date">
                        <?php echo date("F j, Y", strtotime($movie["published_at"])); ?>
                    </p>
                <?php } ?>

                <?php if (!empty($movie['status'])) { ?>
                    <span class="status-badge <?php echo $movie['status']; ?>">
                        <?php echo ucfirst($movie['status']); ?>
                    </span>
                <?php } ?>
            </a>
        </article>

    <?php } ?>
<?php } else { ?>
    <div class="empty-state">No movies found.</div>
<?php } ?>
</div>
</div>

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

</body>
</html>