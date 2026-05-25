<?php
// Enable PHP error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include authentication check
require_once(__DIR__ . "/includes/auth_check.php");

// Include database connection file
require_once(__DIR__ . "/config/DBConn.php");

// Open database connection
$conn = getConnection();
mysqli_set_charset($conn, "utf8mb4");

// Get logged-in user ID
$userId = (int) $_SESSION["user_id"];

// Get user profile information
$sql = "
    SELECT 
        u.user_id,
        u.full_name,
        u.username,
        u.email,
        u.profile_image,
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

// Stop execution if user does not exist
if (!$user) {
    die("User not found.");
}

// Escape profile values before displaying them
$fullName = htmlspecialchars($user["full_name"] ?? "User", ENT_QUOTES, "UTF-8");
$username = htmlspecialchars($user["username"] ?? "username", ENT_QUOTES, "UTF-8");
$email = htmlspecialchars($user["email"] ?? "No email", ENT_QUOTES, "UTF-8");
$roleName = htmlspecialchars($user["role_name"] ?? "Member", ENT_QUOTES, "UTF-8");

// Generate avatar letter from full name or username
$avatarLetterSource = trim($user["full_name"] ?? $user["username"] ?? "U");
$avatarLetter = strtoupper(substr($avatarLetterSource, 0, 1));

// Format account creation date
$memberSince = "N/A";

if (!empty($user["created_at"])) {
    $timestamp = strtotime($user["created_at"]);

    if ($timestamp) {
        $memberSince = date("F Y", $timestamp);
    }
}

// Build profile image path
$profileImage = trim($user["profile_image"] ?? "");
$profileImagePath = "";

if ($profileImage !== "") {
    $profileImagePath = "uploads/" . ltrim($profileImage, "/");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>

    <!-- Character encoding and responsive layout -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Browser tab title -->
    <title>Profile</title>

    <!-- Main website styles -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/profile.css">

    <!-- Google font connection optimization -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- Website fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>

<body>

    <!-- Main navigation bar -->
    <nav class="navbar">

        <!-- Website logo -->
        <div class="logo">
            <img src="assets/images/logo.png" alt="MovieMeter Logo">
        </div>

        <!-- Mobile menu button -->
        <button class="menu-toggle" id="menuToggle" aria-label="Open menu">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <!-- Navigation links -->
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

    <!-- Profile hero section -->
    <section class="profile-hero">

        <!-- Dark overlay -->
        <div class="profile-hero-overlay"></div>

        <!-- Hero content -->
        <div class="profile-hero-content">
            <h1>My Profile</h1>
            <p>Manage your account information and personal details.</p>
        </div>

    </section>

    <!-- Main profile content -->
    <main class="profile-main">

        <section class="profile-section-clean">

            <!-- Profile header -->
            <div class="profile-top">

                <!-- Show uploaded profile image if available -->
                <?php if ($profileImagePath !== ""): ?>

                    <img
                        src="<?php echo htmlspecialchars($profileImagePath, ENT_QUOTES, "UTF-8"); ?>"
                        alt="Profile Image"
                        class="profile-avatar-image"
                        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                    >

                    <!-- Fallback avatar if image fails -->
                    <div class="profile-avatar profile-avatar-fallback" style="display:none;">
                        <?php echo htmlspecialchars($avatarLetter, ENT_QUOTES, "UTF-8"); ?>
                    </div>

                <?php else: ?>

                    <!-- Default avatar -->
                    <div class="profile-avatar">
                        <?php echo htmlspecialchars($avatarLetter, ENT_QUOTES, "UTF-8"); ?>
                    </div>

                <?php endif; ?>

                <!-- Profile intro -->
                <div class="profile-intro">
                    <h2><?php echo $fullName; ?></h2>
                    <p><?php echo ucfirst($roleName); ?> Account</p>
                </div>

            </div>

            <!-- User information list -->
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
                    <div class="profile-value"><?php echo htmlspecialchars($memberSince, ENT_QUOTES, "UTF-8"); ?></div>
                </div>

            </div>

            <!-- Profile action buttons -->
            <div class="profile-actions">
                <a href="edit-profile.php" class="profile-btn profile-btn-primary">Edit Profile</a>
                <a href="watchlist.php" class="profile-btn profile-btn-secondary">My Watchlist</a>
            </div>

        </section>

    </main>

    <!-- Website footer -->
    <footer class="footer">

        <div class="footer-container">

            <!-- Footer brand section -->
            <div class="footer-brand">
                <h3>MovieMeter</h3>

                <p>
                    Discover, rate, and explore your favorite movies in one place.
                    Find trending titles and build your personal watchlist.
                </p>
            </div>

            <!-- Footer quick links -->
            <div class="footer-links">

                <h4>Quick Links</h4>

                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="discover.php">Discover</a></li>
                    <li><a href="foryou.php">For You</a></li>
                    <li><a href="watchlist.php">Watchlist</a></li>
                </ul>

            </div>

            <!-- Footer categories -->
            <div class="footer-links">

                <h4>Categories</h4>

                <ul>
                    <li><a href="categories.php?genre=28">Action</a></li>
                    <li><a href="categories.php?genre=18">Drama</a></li>
                    <li><a href="categories.php?genre=35">Comedy</a></li>
                    <li><a href="categories.php?genre=14">Fantasy</a></li>
                </ul>

            </div>

            <!-- Footer contact information -->
            <div class="footer-contact">

                <h4>Contact</h4>

                <p><a href="mailto:support@moviemeter.com">support@moviemeter.com</a></p>
                <p><a href="tel:+97317000000">+973 1700 0000</a></p>

            </div>

        </div>

        <!-- Footer copyright -->
        <div class="footer-bottom">
            <p>© 2026 MovieMeter. All rights reserved.</p>
        </div>

    </footer>

    <!-- Navbar interaction script -->
    <script>

        const navbar = document.querySelector(".navbar");
        const menuToggle = document.getElementById("menuToggle");
        const navLinks = document.getElementById("navLinks");

        // Open and close mobile menu
        if (menuToggle && navLinks) {
            menuToggle.addEventListener("click", () => {
                navLinks.classList.toggle("open");
            });
        }

        // Close mobile menu after clicking a link
        document.querySelectorAll(".nav-links a").forEach((link) => {
            link.addEventListener("click", () => {
                if (navLinks) {
                    navLinks.classList.remove("open");
                }
            });
        });

        // Add navbar background effect when scrolling
        window.addEventListener("scroll", () => {

            if (!navbar) return;

            if (window.scrollY > 60) {
                navbar.classList.add("scrolled");
            } else {
                navbar.classList.remove("scrolled");
            }
        });

    </script>

</body>
</html>