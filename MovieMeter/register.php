<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once(__DIR__ . "/config/DBConn.php");

$dbc = getConnection();

$message = "";
$message_type = "";

$full_name = "";
$username = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $full_name = trim($_POST["full_name"] ?? "");
    $username = trim($_POST["username"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    $role_id = 3;

    if (empty($full_name) || empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $message = "All fields are required.";
        $message_type = "error";
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";
        $message_type = "error";
    } else {
        $check_sql = "SELECT user_id FROM mm_users WHERE username = ? OR email = ?";
        $stmt = mysqli_prepare($dbc, $check_sql);
        mysqli_stmt_bind_param($stmt, "ss", $username, $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) > 0) {
            $message = "Username or email already exists.";
            $message_type = "error";
            mysqli_stmt_close($stmt);
        } else {
            mysqli_stmt_close($stmt);

            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            $insert_sql = "INSERT INTO mm_users
                           (role_id, full_name, username, email, password_hash, account_status, created_at, updated_at)
                           VALUES (?, ?, ?, ?, ?, 'active', NOW(), NOW())";

            $stmt = mysqli_prepare($dbc, $insert_sql);
            mysqli_stmt_bind_param($stmt, "issss", $role_id, $full_name, $username, $email, $password_hash);

            if (mysqli_stmt_execute($stmt)) {
                $message = "Registration successful. You can login now.";
                $message_type = "success";

                $full_name = "";
                $username = "";
                $email = "";
            } else {
                $message = "Registration failed: " . mysqli_error($dbc);
                $message_type = "error";
            }

            mysqli_stmt_close($stmt);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - MovieMeter</title>

    <link rel="stylesheet" href="assets/css/register.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>

    <div class="page-wrapper">
        <div class="page-image"></div>

        <div class="page-form-side">
            <div class="page-form">
                <div class="brand">
                    <img src="assets/images/logo.png" alt="MovieMeter Logo">
                </div>

                <h1 class="title">Create account</h1>
                <p class="subtitle">Join MovieMeter and start rating your favorite movies</p>

                <?php if (!empty($message)) { ?>
                    <div class="message <?php echo htmlspecialchars($message_type); ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php } ?>

                <form method="POST" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="full_name">Full Name</label>
                            <input
                                type="text"
                                id="full_name"
                                name="full_name"
                                class="form-control"
                                placeholder="Enter full name"
                                value="<?php echo htmlspecialchars($full_name); ?>"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label for="username">Username</label>
                            <input
                                type="text"
                                id="username"
                                name="username"
                                class="form-control"
                                placeholder="Enter username"
                                value="<?php echo htmlspecialchars($username); ?>"
                                required
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-control"
                            placeholder="Enter your email"
                            value="<?php echo htmlspecialchars($email); ?>"
                            required
                        >
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="password">Password</label>
                            <div class="password-wrap">
                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    class="form-control"
                                    placeholder="Password"
                                    required
                                >
                                <button type="button" class="toggle-password" onclick="togglePassword('password', this)">Show</button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirm Password</label>
                            <div class="password-wrap">
                                <input
                                    type="password"
                                    id="confirm_password"
                                    name="confirm_password"
                                    class="form-control"
                                    placeholder="Confirm password"
                                    required
                                >
                                <button type="button" class="toggle-password" onclick="togglePassword('confirm_password', this)">Show</button>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="main-btn">Register</button>

                    <div class="bottom-text">
                        Already have an account? <a href="login.php">Go to Login</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="assets/js/auth.js"></script>
</body>
</html>