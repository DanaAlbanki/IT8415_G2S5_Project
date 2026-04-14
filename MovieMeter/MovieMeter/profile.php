<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require_once(__DIR__ . "/config/DBConn.php");

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$userId = (int) $_SESSION["user_id"];
$conn = getConnection();
mysqli_set_charset($conn, "utf8mb4");

$sql = "
    SELECT 
        u.user_id,
        u.full_name,
        u.username,
        u.email,
        u.created_at,
        r.role_name
    FROM mm_users u
    LEFT JOIN mm_roles r ON u.role_id = r.role_id
    WHERE u.user_id = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Database error: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);
mysqli_close($conn);

if (!$user) {
    die("User not found.");
}

$fullName = htmlspecialchars($user["full_name"] ?? "User");
$username = htmlspecialchars($user["username"] ?? "username");
$email = htmlspecialchars($user["email"] ?? "No email");
$roleName = htmlspecialchars($user["role_name"] ?? "Member");

$avatarLetterSource = trim($user["full_name"] ?? $user["username"] ?? "U");
$avatarLetter = strtoupper(substr($avatarLetterSource, 0, 1));

// Member Since
$memberSince = "N/A";
if (!empty($user["created_at"])) {
    $timestamp = strtotime($user["created_at"]);
    if ($timestamp) {
        $memberSince = date("F Y", $timestamp);
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile | MovieMeter</title>

    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/profile.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>

<body>

    <!-- NAVBAR -->
    <nav class="navbar">
        <div class="logo">
            <img src="assets/images/logo.png" alt="MovieMeter Logo">
        </div>

        <button class="menu-toggle" id="menuToggle" aria-label="Open menu">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <ul class="nav-links" id="navLinks">
            <li><a href="index.php">Home</a></li>
            <li><a href="discover.php">Discover</a></li>
            <li><a href="categories.php">Categories</a></li>
            <li><a href="foryou.php">For You</a></li>
            <li><a href="watchlist.php">Watchlist</a></li>
            <li><a href="profile.php" class="active-link">Profile</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>

    <!-- HERO -->
    <section class="profile-hero">
        <div class="profile-hero-overlay"></div>
        <div class="profile-hero-content">
            <h1>My Profile</h1>
            <p>Manage your account information and personal details.</p>
        </div>
    </section>

    <!-- PROFILE SECTION -->
    <main class="profile-main">
        <section class="profile-section-clean">
            <div class="profile-top">
                <div class="profile-avatar"><?php echo $avatarLetter; ?></div>

                <div class="profile-intro">
                    <h2><?php echo $fullName; ?></h2>
                    <p><?php echo ucfirst($roleName); ?> Account</p>
                </div>
            </div>

            <div class="profile-info-list">
                <div class="profile-info-row">
                    <div class="profile-label">Full Name</div>
                    <div class="profile-value"><?php echo $fullName; ?></div>
                </div>

                <div class="profile-info-row">
                    <div class="profile-label">Username</div>
                    <div class="profile-value"><?php echo $username; ?></div>
                </div>

                <div class="profile-info-row">
                    <div class="profile-label">Email</div>
                    <div class="profile-value"><?php echo $email; ?></div>
                </div>

                <div class="profile-info-row">
                    <div class="profile-label">Member Since</div>
                    <div class="profile-value"><?php echo htmlspecialchars($memberSince); ?></div>
                </div>
            </div>

            <div class="profile-actions">
                <a href="edit-profile.php" class="profile-btn profile-btn-primary">Edit Profile</a>
                <a href="watchlist.php" class="profile-btn profile-btn-secondary">My Watchlist</a>
            </div>
        </section>
    </main>

    <!-- FOOTER -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-brand">
                <h3>MovieMeter</h3>
                <p>
                    Discover, rate, and explore your favorite movies in one place.
                    Find trending titles and build your personal watchlist.
                </p>
            </div>

            <div class="footer-links">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="discover.php#latest-section">Latest Movies</a></li>
                    <li><a href="discover.php#top-rated-section">Top Rated</a></li>
                    <li><a href="watchlist.php">Watchlist</a></li>
                </ul>
            </div>

            <div class="footer-links">
                <h4>Categories</h4>
                <ul>
                    <li><a href="categories.php">Action</a></li>
                    <li><a href="categories.php">Drama</a></li>
                    <li><a href="categories.php">Comedy</a></li>
                    <li><a href="categories.php">Fantasy</a></li>
                </ul>
            </div>

            <div class="footer-contact">
                <h4>Contact</h4>
                <p>support@moviemeter.com</p>
                <p>+973 1700 0000</p>
            </div>
        </div>

        <div class="footer-bottom">
            <p>© 2026 MovieMeter. All rights reserved.</p>
        </div>
    </footer>

    <script>
        const navbar = document.querySelector('.navbar');
        const menuToggle = document.getElementById('menuToggle');
        const navLinks = document.getElementById('navLinks');

        if (menuToggle) {
            menuToggle.addEventListener('click', () => {
                navLinks.classList.toggle('open');
            });
        }

        document.querySelectorAll('.nav-links a').forEach(link => {
            link.addEventListener('click', () => {
                navLinks.classList.remove('open');
            });
        });

        window.addEventListener('scroll', () => {
            if (window.scrollY > 60) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    </script>

</body>
</html>