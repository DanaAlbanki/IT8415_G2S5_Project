<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once(__DIR__ . "/../includes/auth_check.php");
require_once("../config/DBConn.php"); 

if ($_SESSION["role_name"] !== "creator") {
    die("Access denied.");
}

$dbc = getConnection();
$creator_id = $_SESSION['user_id'];


$query = "
    SELECT AVG(r.rating_value) AS avg_rating
    FROM mm_ratings r, mm_movies m
    WHERE r.movie_id = m.movie_id
    AND m.creator_id = $creator_id
";
$result = mysqli_query($dbc, $query);
$avg_row = mysqli_fetch_assoc($result);
$avg_rating = $avg_row['avg_rating'] ?? 0;

$sql = "
    SELECT 
        m.movie_id,
        m.title,
        m.poster_image,
        AVG(r.rating_value) AS avg_rating
    FROM mm_movies m
    LEFT JOIN mm_ratings r ON m.movie_id = r.movie_id
    WHERE m.status = 'published'
    AND m.creator_id = $creator_id
    GROUP BY m.movie_id
    ORDER BY avg_rating DESC
    LIMIT 3
";

$movies_result = mysqli_query($dbc, $sql);
?>

<!DOCTYPE html>
<html>
<head>
<title>Creator Dashboard</title>
<link rel="stylesheet" href="../assets/css/creator-dashboard.css">
</head>

<body>

<div class="navbar">
    <div class="logo">
        <img src="../assets/images/logo.png">
    </div>

    <div class="nav-links">
        <a href="../creator/dashboard.php">Dashboard</a>
        <a href="../creator/my-movie.php">My Movies</a>
        <a href="../creator/add-movie.php">Add Movie</a>
        <a href="../creator/import-movies.php">Import Movies</a>
        <a href="../creator/profile.php">Profile</a>
    </div>
</div>

<div class="box">

<h2>Welcome Creator, <?php echo htmlspecialchars($_SESSION["full_name"]); ?></h2>
<hr>

<div class="analytics-row">

    <div class="analytics-card">
        <h4>Average Rating</h4>
        <div class="analytics-value">
            <?php echo number_format($avg_rating, 1); ?> / 5
        </div>
    </div>

    <div class="analytics-card">
        <h4>Total Movies</h4>
        <div class="analytics-value">
            <?php
            $res = mysqli_query($dbc, "SELECT COUNT(*) as total FROM mm_movies WHERE creator_id=$creator_id");
            echo mysqli_fetch_assoc($res)['total'];
            ?>
        </div>
    </div>

    <div class="analytics-card">
        <h4>Published Movies</h4>
        <div class="analytics-value">
            <?php
            $res = mysqli_query($dbc, "SELECT COUNT(*) as total FROM mm_movies WHERE creator_id=$creator_id AND status='published'");
            echo mysqli_fetch_assoc($res)['total'];
            ?>
        </div>
    </div>

    <div class="analytics-card">
        <h4>API Imports</h4>
        <div class="analytics-value">
            <?php
            $res = mysqli_query($dbc, "SELECT COUNT(*) as total FROM mm_movies WHERE creator_id=$creator_id AND is_api_imported=1");
            echo mysqli_fetch_assoc($res)['total'];
            ?>
        </div>
    </div>

</div>
<h2 class="section-title">Top Rated Movies</h2>

<div class="movies-wrapper">
<?php if (mysqli_num_rows($movies_result) > 0) { ?>
    <?php while ($movie = mysqli_fetch_assoc($movies_result)) { ?>

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

        <div class="movie-card">
            <a href="movie_details.php?movie_id=<?php echo (int)$movie["movie_id"]; ?>">
                <img src="<?php echo htmlspecialchars($poster_path); ?>">
                <h3><?php echo htmlspecialchars($movie["title"]); ?></h3>
            </a>
        </div>

    <?php } ?>
<?php } else { ?>
    <p>No movies found.</p>
<?php } ?>
</div>
<br>
<a href="../creator/my-movie.php" class="btn">View All</a>
<hr>


<div class="actions">
    <a href="../logout.php" class="btn btn-secondary">Logout</a>
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