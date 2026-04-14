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

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fullName = trim($_POST["full_name"] ?? "");
    $username = trim($_POST["username"] ?? "");
    $email = trim($_POST["email"] ?? "");

    if ($fullName === "" || $username === "" || $email === "") {
        $message = "Please fill in all fields.";
        $messageType = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $messageType = "error";
    } else {
        $checkUsernameSql = "SELECT user_id FROM mm_users WHERE username = ? AND user_id != ? LIMIT 1";
        $checkUsernameStmt = mysqli_prepare($conn, $checkUsernameSql);
        mysqli_stmt_bind_param($checkUsernameStmt, "si", $username, $userId);
        mysqli_stmt_execute($checkUsernameStmt);
        $checkUsernameResult = mysqli_stmt_get_result($checkUsernameStmt);
        $usernameExists = mysqli_fetch_assoc($checkUsernameResult);
        mysqli_stmt_close($checkUsernameStmt);

        $checkEmailSql = "SELECT user_id FROM mm_users WHERE email = ? AND user_id != ? LIMIT 1";
        $checkEmailStmt = mysqli_prepare($conn, $checkEmailSql);
        mysqli_stmt_bind_param($checkEmailStmt, "si", $email, $userId);
        mysqli_stmt_execute($checkEmailStmt);
        $checkEmailResult = mysqli_stmt_get_result($checkEmailStmt);
        $emailExists = mysqli_fetch_assoc($checkEmailResult);
        mysqli_stmt_close($checkEmailStmt);

        if ($usernameExists) {
            $message = "This username is already taken.";
            $messageType = "error";
        } elseif ($emailExists) {
            $message = "This email is already in use.";
            $messageType = "error";
        } else {
            $updateSql = "UPDATE mm_users SET full_name = ?, username = ?, email = ? WHERE user_id = ?";
            $updateStmt = mysqli_prepare($conn, $updateSql);

            if ($updateStmt) {
                mysqli_stmt_bind_param($updateStmt, "sssi", $fullName, $username, $email, $userId);

                if (mysqli_stmt_execute($updateStmt)) {
                    $message = "Profile updated successfully.";
                    $messageType = "success";

                    $_SESSION["username"] = $username;
                    $_SESSION["email"] = $email;
                    $_SESSION["full_name"] = $fullName;
                } else {
                    $message = "Something went wrong while updating your profile.";
                    $messageType = "error";
                }

                mysqli_stmt_close($updateStmt);
            } else {
                $message = "Database error: " . mysqli_error($conn);
                $messageType = "error";
            }
        }
    }
}

$sql = "SELECT full_name, username, email FROM mm_users WHERE user_id = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Database error: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$user) {
    mysqli_close($conn);
    die("User not found.");
}

mysqli_close($conn);

$fullNameValue = htmlspecialchars($user["full_name"] ?? "", ENT_QUOTES, "UTF-8");
$usernameValue = htmlspecialchars($user["username"] ?? "", ENT_QUOTES, "UTF-8");
$emailValue = htmlspecialchars($user["email"] ?? "", ENT_QUOTES, "UTF-8");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - MovieMeter</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    <style>
        :root {
            --page-bg: #251631;
            --section-bg: #58386f;
            --input-bg: #3a244a;
            --button-bg: #8c2d7a;
            --button-hover: #7a276b;
            --text-main: #ffffff;
            --text-soft: #eadff0;
            --muted: #cbbbd5;
            --line: rgba(255, 255, 255, 0.10);
            --success-bg: rgba(34, 197, 94, 0.16);
            --success-border: rgba(34, 197, 94, 0.4);
            --error-bg: rgba(239, 68, 68, 0.16);
            --error-border: rgba(239, 68, 68, 0.4);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Inter", sans-serif;
            background: var(--page-bg);
            color: var(--text-main);
            min-height: 100vh;
        }

        .topbar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            padding: 20px 28px;
            z-index: 1000;
            background: linear-gradient(to bottom, rgba(37, 22, 49, 0.95), rgba(37, 22, 49, 0));
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 50px;
            padding: 0 22px;
            background: var(--button-bg);
            color: #ffffff;
            font-size: 15px;
            font-weight: 800;
            border-radius: 14px;
            text-decoration: none;
            transition: 0.25s ease;
        }

        .back-link:hover {
            background: var(--button-hover);
        }

        .edit-page {
            max-width: 1000px;
            margin: 0 auto;
            padding: 130px 24px 50px;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-logo {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 10px;
        }

        .page-logo img {
            width: 90px;
            height: auto;
        }

        .page-title {
            font-family: "Bebas Neue", sans-serif;
            font-size: 3rem;
            letter-spacing: 1px;
            margin-bottom: 8px;
            color: var(--text-main);
        }

        .page-subtitle {
            color: var(--text-soft);
            font-size: 1rem;
            line-height: 1.7;
        }

        .message {
            margin: 0 0 24px;
            padding: 14px 16px;
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 600;
        }

        .message.success {
            background: var(--success-bg);
            border: 1px solid var(--success-border);
            color: #dcfce7;
        }

        .message.error {
            background: var(--error-bg);
            border: 1px solid var(--error-border);
            color: #fee2e2;
        }

        .edit-form {
            border-top: 1px solid var(--line);
        }

        .form-row {
            display: grid;
            grid-template-columns: 180px 1fr;
            gap: 24px;
            padding: 20px 0;
            border-bottom: 1px solid var(--line);
            align-items: center;
        }

        .form-label {
            color: var(--muted);
            font-size: 0.88rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .form-field input {
            width: 100%;
            height: 52px;
            border: none;
            outline: none;
            border-radius: 12px;
            background: var(--input-bg);
            color: var(--text-main);
            padding: 0 16px;
            font-size: 0.96rem;
        }

        .form-field input::placeholder {
            color: var(--muted);
        }

        .form-actions {
            margin-top: 28px;
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
        }

        .form-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 52px;
            padding: 0 24px;
            border: none;
            border-radius: 14px;
            background: var(--button-bg);
            color: #ffffff;
            text-decoration: none;
            font-size: 0.96rem;
            font-weight: 800;
            cursor: pointer;
            transition: 0.25s ease;
        }

        .form-btn:hover {
            background: var(--button-hover);
        }

        .form-btn.secondary {
            background: var(--input-bg);
        }

        .form-btn.secondary:hover {
            background: #4a2d5d;
        }

        @media (max-width: 768px) {
            .topbar {
                padding: 16px;
            }

            .edit-page {
                padding: 110px 16px 36px;
            }

            .page-title {
                font-size: 2.2rem;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 8px;
                padding: 18px 0;
            }

            .form-actions {
                flex-direction: column;
            }

            .form-btn {
                width: 100%;
            }
        }
    </style>
</head>

<body>

    <header class="topbar">
        <a href="profile.php" class="back-link">← Back </a>
    </header>

    <main class="edit-page">
        <div class="page-header">
            <div class="page-logo">
                <img src="assets/images/logo.png" alt="MovieMeter Logo">
            </div>
            <h1 class="page-title">Edit Profile</h1>
            <p class="page-subtitle">Update your personal information below.</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="message <?php echo htmlspecialchars($messageType); ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form class="edit-form" method="POST" action="">
            <div class="form-row">
                <div class="form-label">Full Name</div>
                <div class="form-field">
                    <input type="text" name="full_name" value="<?php echo $fullNameValue; ?>" placeholder="Enter your full name">
                </div>
            </div>

            <div class="form-row">
                <div class="form-label">Username</div>
                <div class="form-field">
                    <input type="text" name="username" value="<?php echo $usernameValue; ?>" placeholder="Enter your username">
                </div>
            </div>

            <div class="form-row">
                <div class="form-label">Email</div>
                <div class="form-field">
                    <input type="email" name="email" value="<?php echo $emailValue; ?>" placeholder="Enter your email">
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="form-btn">Save Changes</button>
                <a href="profile.php" class="form-btn secondary">Cancel</a>
            </div>
        </form>
    </main>

</body>
</html>