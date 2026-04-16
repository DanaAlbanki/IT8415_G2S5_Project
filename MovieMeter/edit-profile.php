<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once(__DIR__ . "/includes/auth_check.php");
require_once(__DIR__ . "/config/DBConn.php");

$conn = getConnection();
mysqli_set_charset($conn, "utf8mb4");

$userId = (int) $_SESSION["user_id"];
$message = "";
$messageType = "";

function h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function fetchUser($conn, $userId)
{
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
        return null;
    }

    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);

    mysqli_stmt_bind_result(
        $stmt,
        $dbUserId,
        $dbFullName,
        $dbUsername,
        $dbEmail,
        $dbProfileImage,
        $dbCreatedAt,
        $dbRoleName
    );

    $user = null;

    if (mysqli_stmt_fetch($stmt)) {
        $user = array(
            "user_id" => $dbUserId,
            "full_name" => $dbFullName,
            "username" => $dbUsername,
            "email" => $dbEmail,
            "profile_image" => $dbProfileImage,
            "created_at" => $dbCreatedAt,
            "role_name" => $dbRoleName
        );
    }

    mysqli_stmt_close($stmt);

    return $user;
}

function ensureWritableDirectory($dirPath)
{
    if (!is_dir($dirPath)) {
        @mkdir($dirPath, 0775, true);
    }

    clearstatcache();

    if (!is_dir($dirPath)) {
        return false;
    }

    @chmod($dirPath, 0775);
    clearstatcache();

    if (!is_writable($dirPath)) {
        @chmod($dirPath, 0777);
        clearstatcache();
    }

    if (!is_writable($dirPath)) {
        return false;
    }

    $testFile = rtrim($dirPath, "/") . "/write_test_" . uniqid("", true) . ".tmp";
    $written = @file_put_contents($testFile, "ok");

    if ($written === false) {
        return false;
    }

    @unlink($testFile);
    return true;
}

$user = fetchUser($conn, $userId);

if (!$user) {
    mysqli_close($conn);
    die("User not found.");
}

$originalFullName = trim(isset($user["full_name"]) ? $user["full_name"] : "");
$originalUsername = trim(isset($user["username"]) ? $user["username"] : "");
$originalEmail = trim(isset($user["email"]) ? $user["email"] : "");
$originalProfileImage = trim(isset($user["profile_image"]) ? $user["profile_image"] : "");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fullName = trim(isset($_POST["full_name"]) ? $_POST["full_name"] : "");
    $username = trim(isset($_POST["username"]) ? $_POST["username"] : "");
    $email = trim(isset($_POST["email"]) ? $_POST["email"] : "");

    $newProfileImage = $originalProfileImage;
    $imageChanged = false;
    $uploadError = false;

    if ($fullName === "" || $username === "" || $email === "") {
        $message = "Please fill in all fields.";
        $messageType = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $messageType = "error";
    } else {
        if (
            isset($_FILES["profile_image"]) &&
            isset($_FILES["profile_image"]["error"]) &&
            $_FILES["profile_image"]["error"] !== UPLOAD_ERR_NO_FILE
        ) {
            $fileError = (int) $_FILES["profile_image"]["error"];

            if ($fileError !== UPLOAD_ERR_OK) {
                $message = "There was a problem uploading the image.";
                $messageType = "error";
                $uploadError = true;
            } else {
                $tmpPath = $_FILES["profile_image"]["tmp_name"];
                $fileSize = isset($_FILES["profile_image"]["size"]) ? (int) $_FILES["profile_image"]["size"] : 0;
                $originalFileName = isset($_FILES["profile_image"]["name"]) ? $_FILES["profile_image"]["name"] : "";
                $extension = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));
                $allowedExtensions = array("jpg", "jpeg", "png", "webp");

                if ($fileSize > 5 * 1024 * 1024) {
                    $message = "Image size must be 5MB or less.";
                    $messageType = "error";
                    $uploadError = true;
                } elseif (!in_array($extension, $allowedExtensions, true)) {
                    $message = "Only JPG, JPEG, PNG, and WEBP files are allowed.";
                    $messageType = "error";
                    $uploadError = true;
                } elseif (!is_uploaded_file($tmpPath) || @getimagesize($tmpPath) === false) {
                    $message = "Please upload a valid image file.";
                    $messageType = "error";
                    $uploadError = true;
                } else {
                    $uploadsRoot = __DIR__ . "/uploads";
                    $profileUploadDir = $uploadsRoot . "/profile";

                    $rootOk = ensureWritableDirectory($uploadsRoot);
                    $profileOk = ensureWritableDirectory($profileUploadDir);

                    if (!$rootOk || !$profileOk) {
                        $message = "Upload folder is missing or not writable.";
                        $messageType = "error";
                        $uploadError = true;
                    } else {
                        $newFileName = "user_" . $userId . "_" . time() . "_" . uniqid() . "." . $extension;
                        $destination = $profileUploadDir . "/" . $newFileName;
                        $relativeImagePath = "profile/" . $newFileName;

                        if (move_uploaded_file($tmpPath, $destination)) {
                            $newProfileImage = $relativeImagePath;
                            $imageChanged = true;
                        } else {
                            $message = "Failed to save the uploaded image.";
                            $messageType = "error";
                            $uploadError = true;
                        }
                    }
                }
            }
        }

        if (!$uploadError) {
            $textChanged =
                $fullName !== $originalFullName ||
                $username !== $originalUsername ||
                $email !== $originalEmail;

            if (!$textChanged && !$imageChanged) {
                $message = "No changes were made.";
                $messageType = "error";
            } else {
                $checkUsernameSql = "SELECT user_id FROM mm_users WHERE username = ? AND user_id != ? LIMIT 1";
                $checkUsernameStmt = mysqli_prepare($conn, $checkUsernameSql);
                $usernameExists = false;

                if ($checkUsernameStmt) {
                    mysqli_stmt_bind_param($checkUsernameStmt, "si", $username, $userId);
                    mysqli_stmt_execute($checkUsernameStmt);
                    mysqli_stmt_store_result($checkUsernameStmt);
                    $usernameExists = mysqli_stmt_num_rows($checkUsernameStmt) > 0;
                    mysqli_stmt_close($checkUsernameStmt);
                }

                $checkEmailSql = "SELECT user_id FROM mm_users WHERE email = ? AND user_id != ? LIMIT 1";
                $checkEmailStmt = mysqli_prepare($conn, $checkEmailSql);
                $emailExists = false;

                if ($checkEmailStmt) {
                    mysqli_stmt_bind_param($checkEmailStmt, "si", $email, $userId);
                    mysqli_stmt_execute($checkEmailStmt);
                    mysqli_stmt_store_result($checkEmailStmt);
                    $emailExists = mysqli_stmt_num_rows($checkEmailStmt) > 0;
                    mysqli_stmt_close($checkEmailStmt);
                }

                if ($usernameExists) {
                    $message = "This username is already taken.";
                    $messageType = "error";
                } elseif ($emailExists) {
                    $message = "This email is already in use.";
                    $messageType = "error";
                } else {
                    $updateSql = "UPDATE mm_users SET full_name = ?, username = ?, email = ?, profile_image = ? WHERE user_id = ?";
                    $updateStmt = mysqli_prepare($conn, $updateSql);

                    if (!$updateStmt) {
                        $message = "Database error: " . mysqli_error($conn);
                        $messageType = "error";
                    } else {
                        mysqli_stmt_bind_param($updateStmt, "ssssi", $fullName, $username, $email, $newProfileImage, $userId);

                        if (mysqli_stmt_execute($updateStmt)) {
                            if (
                                $imageChanged &&
                                $originalProfileImage !== "" &&
                                $originalProfileImage !== $newProfileImage &&
                                substr($originalProfileImage, 0, 8) === "profile/"
                            ) {
                                $oldImageAbsolutePath = __DIR__ . "/uploads/" . $originalProfileImage;
                                if (is_file($oldImageAbsolutePath)) {
                                    @unlink($oldImageAbsolutePath);
                                }
                            }

                            $_SESSION["full_name"] = $fullName;
                            $_SESSION["username"] = $username;
                            $_SESSION["email"] = $email;
                            $_SESSION["profile_image"] = $newProfileImage;

                            $message = "Profile updated successfully.";
                            $messageType = "success";

                            $user = fetchUser($conn, $userId);
                            $originalFullName = trim(isset($user["full_name"]) ? $user["full_name"] : "");
                            $originalUsername = trim(isset($user["username"]) ? $user["username"] : "");
                            $originalEmail = trim(isset($user["email"]) ? $user["email"] : "");
                            $originalProfileImage = trim(isset($user["profile_image"]) ? $user["profile_image"] : "");
                        } else {
                            $message = "Something went wrong while updating your profile.";
                            $messageType = "error";
                        }

                        mysqli_stmt_close($updateStmt);
                    }
                }
            }
        }
    }
}

mysqli_close($conn);

$fullNameValue = h(isset($user["full_name"]) ? $user["full_name"] : "");
$usernameValue = h(isset($user["username"]) ? $user["username"] : "");
$emailValue = h(isset($user["email"]) ? $user["email"] : "");
$profileImage = trim(isset($user["profile_image"]) ? $user["profile_image"] : "");
$profileImagePath = $profileImage !== "" ? "uploads/" . $profileImage : "";
$avatarLetterSource = trim(
    (isset($user["full_name"]) && $user["full_name"] !== "") ? $user["full_name"] :
    ((isset($user["username"]) && $user["username"] !== "") ? $user["username"] : "U")
);
$avatarLetter = strtoupper(substr($avatarLetterSource, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>

    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/edit-profile.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>

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

    <main class="edit-profile-page">
        <section class="edit-profile-card">
            <div class="edit-profile-header">
                <h1>Edit Profile</h1>
                <p>Update your details</p>
            </div>

            <?php if (!empty($message)): ?>
                <div class="edit-message <?php echo h($messageType); ?>">
                    <?php echo h($message); ?>
                </div>
            <?php endif; ?>

            <form class="edit-profile-form" method="POST" enctype="multipart/form-data" id="editProfileForm">
                <div class="avatar-area">
                    <div class="avatar-upload-wrap">
                        <div class="avatar-shell">
                            <?php if ($profileImagePath !== ""): ?>
                                <img
                                    src="<?php echo h($profileImagePath); ?>"
                                    alt="Profile Avatar"
                                    class="avatar-preview-image"
                                    id="avatarPreviewImage"
                                >
                                <div
                                    class="avatar-fallback"
                                    id="avatarFallback"
                                    style="display:none;"
                                >
                                    <?php echo h($avatarLetter); ?>
                                </div>
                            <?php else: ?>
                                <img
                                    src=""
                                    alt="Profile Avatar"
                                    class="avatar-preview-image"
                                    id="avatarPreviewImage"
                                    style="display:none;"
                                >
                                <div class="avatar-fallback" id="avatarFallback">
                                    <?php echo h($avatarLetter); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <label for="profile_image" class="camera-upload-btn" aria-label="Choose profile image" title="Choose profile image">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M9 4.5 10.5 3h3L15 4.5h2.7c1 0 1.9.4 2.5 1 .6.6 1 1.5 1 2.5v8c0 1-.4 1.9-1 2.5-.6.6-1.5 1-2.5 1H6.3c-1 0-1.9-.4-2.5-1-.6-.6-1-1.5-1-2.5V8c0-1 .4-1.9 1-2.5.6-.6 1.5-1 2.5-1H9Zm3 11.8a4.3 4.3 0 1 0 0-8.6 4.3 4.3 0 0 0 0 8.6Zm0-1.8a2.5 2.5 0 1 1 0-5 2.5 2.5 0 0 1 0 5Z"/>
                            </svg>
                        </label>

                        <input
                            type="file"
                            name="profile_image"
                            id="profile_image"
                            class="hidden-file-input"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                        >
                    </div>
                </div>

                <div class="field-group">
                    <label for="full_name">Full Name</label>
                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        value="<?php echo $fullNameValue; ?>"
                        placeholder="Enter your full name"
                        required
                    >
                </div>

                <div class="field-group">
                    <label for="username">Username</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        value="<?php echo $usernameValue; ?>"
                        placeholder="Enter your username"
                        required
                    >
                </div>

                <div class="field-group">
                    <label for="email">Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?php echo $emailValue; ?>"
                        placeholder="Enter your email"
                        required
                    >
                </div>

                <div class="edit-actions">
                    <button type="submit" class="edit-btn edit-btn-primary" id="saveBtn" disabled>Save Changes</button>
                    <a href="profile.php" class="edit-btn edit-btn-secondary">Cancel</a>
                </div>
            </form>
        </section>
    </main>

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
                    <li><a href="discover.php">Discover</a></li>
                    <li><a href="foryou.php">For You</a></li>
                    <li><a href="watchlist.php">Watchlist</a></li>
                </ul>
            </div>

            <div class="footer-links">
                <h4>Categories</h4>
                <ul>
                    <li><a href="categories.php?genre=28">Action</a></li>
                    <li><a href="categories.php?genre=18">Drama</a></li>
                    <li><a href="categories.php?genre=35">Comedy</a></li>
                    <li><a href="categories.php?genre=14">Fantasy</a></li>
                </ul>
            </div>

            <div class="footer-contact">
                <h4>Contact</h4>
                <p><a href="mailto:support@moviemeter.com">support@moviemeter.com</a></p>
                <p><a href="tel:+97317000000">+973 1700 0000</a></p>
            </div>
        </div>

        <div class="footer-bottom">
            <p>© 2026 MovieMeter. All rights reserved.</p>
        </div>
    </footer>
    
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="assets/js/edit-profile.js"></script>

</body>
</html>