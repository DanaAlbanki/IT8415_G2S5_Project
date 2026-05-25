<?php
// Enable PHP error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start the session
session_start();

// Include database connection file
require_once(__DIR__ . "/config/DBConn.php");

// Open database connection
$dbc = getConnection();

// Store error or status message
$message = "";

// Check if the login form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Get login form values
    $login_input = trim($_POST["login_input"] ?? "");
    $password = $_POST["password"] ?? "";

    // Validate required fields
    if (empty($login_input) || empty($password)) {

        $message = "Please fill in all fields.";

    } else {

        // Get user information and role from the database
        $sql = "SELECT u.user_id, u.role_id, u.full_name, u.username, u.email,
                       u.password_hash, u.account_status, r.role_name
                FROM mm_users u
                JOIN mm_roles r ON u.role_id = r.role_id
                WHERE u.username = ? OR u.email = ?";

        $stmt = mysqli_prepare($dbc, $sql);

        mysqli_stmt_bind_param($stmt, "ss", $login_input, $login_input);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        // Check if the user exists
        if (mysqli_num_rows($result) == 1) {

            $user = mysqli_fetch_assoc($result);

            // Check if the account is active
            if ($user["account_status"] !== "active") {

                $message = "Your account is suspended.";

            }
            // Verify the entered password
            elseif (password_verify($password, $user["password_hash"])) {

                // Save user information in session
                $_SESSION["user_id"] = $user["user_id"];
                $_SESSION["role_id"] = $user["role_id"];
                $_SESSION["role_name"] = $user["role_name"];
                $_SESSION["full_name"] = $user["full_name"];
                $_SESSION["username"] = $user["username"];
                $_SESSION["email"] = $user["email"];

                // Redirect user based on role
                if ($user["role_name"] == "admin") {

                    header("Location: admin/dashboard.php");
                    exit();

                } elseif ($user["role_name"] == "creator") {

                    header("Location: creator/dashboard.php");
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

    <!-- Character encoding and responsive layout -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Browser tab title -->
    <title>Login</title>

    <!-- Login page stylesheet -->
    <link rel="stylesheet" href="assets/css/login.css">

    <!-- Google font connection optimization -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- Website font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>

<body>

    <!-- Main page layout -->
    <div class="page-wrapper">

        <!-- Left side image section -->
        <div class="page-image"></div>

        <!-- Right side login form -->
        <div class="page-form-side">

            <div class="page-form">

                <!-- Website logo -->
                <div class="brand">
                    <img src="assets/images/logo.png" alt="MovieMeter Logo">
                </div>

                <!-- Login page heading -->
                <h1 class="title">Welcome back</h1>
                <p class="subtitle">Sign in to your account</p>

                <!-- Display login error message -->
                <?php if (!empty($message)) { ?>

                    <div class="message">
                        <?php echo htmlspecialchars($message); ?>
                    </div>

                <?php } ?>

                <!-- Login form -->
                <form method="POST" action="">

                    <!-- Email or username input -->
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

                    <!-- Password input -->
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

                            <!-- Show or hide password button -->
                            <button
                                type="button"
                                class="toggle-password"
                                onclick="togglePassword('password', this)"
                            >
                                Show
                            </button>

                        </div>
                    </div>

                    <!-- Forgot password link -->
                    <div class="forgot-row">
                        <a href="forgot-password.php">Forgot password?</a>
                    </div>

                    <!-- Login button -->
                    <button type="submit" class="main-btn">Sign In</button>

                    <!-- Register link -->
                    <div class="bottom-text">
                        Don’t have an account?
                        <a href="register.php">Sign up</a>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <!-- Password visibility toggle script -->
    <script>

        function togglePassword(inputId, button) {

            const input = document.getElementById(inputId);

            // Show password text
            if (input.type === 'password') {

                input.type = 'text';
                button.textContent = 'Hide';

            } else {

                // Hide password text
                input.type = 'password';
                button.textContent = 'Show';
            }
        }

    </script>

</body>
</html>