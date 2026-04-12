<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once(__DIR__ . "/includes/auth_check.php");

if (!isset($_SESSION["role_name"]) || ($_SESSION["role_name"] !== "viewer" && $_SESSION["role_name"] !== "admin")) {
    die("Access denied.");
}

$movieId = isset($_GET["id"]) ? (int) $_GET["id"] : 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movie Details</title>

    <link rel="stylesheet" href="assets/css/movie.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
</head>

<body class="movie-page">

    <header class="movie-topbar">
        <a href="index.php" class="back-link">← Back to Home</a>
    </header>

    <main id="movie-detail">
        <?php if ($movieId <= 0) { ?>
            <section class="message-block">
                <h1>Movie not found</h1>
                <p>No movie ID was provided.</p>
                <a href="index.php" class="action-btn secondary-btn">Back Home</a>
            </section>
        <?php } ?>
    </main>

    <?php if ($movieId > 0) { ?>
        <script type="module" src="assets/js/movie.js"></script>
    <?php } ?>

</body>

</html>