<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once(__DIR__ . "/config/DBConn.php");

// Open database connection
$dbc = getConnection();

// Message for errors
$message = "";

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Get input values
    $login_input = trim($_POST["login_input"] ?? "");
    $password = $_POST["password"] ?? "";

    // Check if fields are empty
    if (empty($login_input) || empty($password)) {
        $message = "Please fill in all fields.";
    } else {

        // Get user data and role
        $sql = "SELECT u.user_id, u.role_id, u.full_name, u.username, u.email,
                       u.password_hash, u.account_status, r.role_name
                FROM mm_users u
                JOIN mm_roles r ON u.role_id = r.role_id
                WHERE u.username = ? OR u.email = ?";

        $stmt = mysqli_prepare($dbc, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $login_input, $login_input);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        // Check if user exists
        if (mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);

            // Check account status
            if ($user["account_status"] !== "active") {
                $message = "Your account is suspended.";
            }
            // Verify password
            elseif (password_verify($password, $user["password_hash"])) {

                // Save user data in session
                $_SESSION["user_id"] = $user["user_id"];
                $_SESSION["role_id"] = $user["role_id"];
                $_SESSION["role_name"] = $user["role_name"];
                $_SESSION["full_name"] = $user["full_name"];
                $_SESSION["username"] = $user["username"];
                $_SESSION["email"] = $user["email"];

                // Redirect by role
                if ($user["role_name"] == "admin") {
                    header("Location: admin_dashboard.php");
                    exit();
                } elseif ($user["role_name"] == "creator") {
                    header("Location: creator_dashboard.php");
                    exit();
                } else {
                    header("Location: index.php");
                    exit();
                }

            } else {
                $message = "Invalid password.";
            }
        } else {
            $message = "User not found.";
        }

        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>

    <link rel="stylesheet" href="assets/css/login.css">
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

                <h1 class="title">Welcome back</h1>
                <p class="subtitle">Sign in to your account</p>

                <?php if (!empty($message)) { ?>
                    <div class="message"><?php echo htmlspecialchars($message); ?></div>
                <?php } ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="login_input">Email or Username</label>
                        <input
                            type="text"
                            id="login_input"
                            name="login_input"
                            class="form-control"
                            placeholder="Enter your email or username"
                            value="<?php echo isset($login_input) ? htmlspecialchars($login_input) : ''; ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="password-wrap">
                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="form-control"
                                placeholder="Enter your password"
                                required
                            >
                            <button type="button" class="toggle-password" onclick="togglePassword('password', this)">Show</button>
                        </div>
                    </div>

                    <div class="forgot-row">
                        <a href="forgot-password.php">Forgot password?</a>
                    </div>

                    <button type="submit" class="main-btn">Sign In</button>

                    <div class="bottom-text">
                        Don’t have an account? <a href="register.php">Sign up</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(inputId, button) {
            const input = document.getElementById(inputId);

            if (input.type === 'password') {
                input.type = 'text';
                button.textContent = 'Hide';
            } else {
                input.type = 'password';
                button.textContent = 'Show';
            }
        }
    </script>

</body>
</html>