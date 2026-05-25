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

$message = "";
$message_type = "";
$email = "";

// Handle forgot password form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"] ?? "");

    // Validate email input
    if (empty($email)) {
        $message = "Please enter your email address.";
        $message_type = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $message_type = "error";
    } else {
        // Check if an account exists with this email
        $sql = "SELECT user_id FROM mm_users WHERE email = ? LIMIT 1";
        $stmt = mysqli_prepare($dbc, $sql);

        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) === 1) {
            $message = "Password reset request submitted successfully. Please contact the admin or continue with the reset step later.";
            $message_type = "success";
            $email = "";
        } else {
            $message = "No account found with this email address.";
            $message_type = "error";
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
    <title>Forgot Password</title>

    <!-- Forgot password page stylesheet -->
    <link rel="stylesheet" href="assets/css/forgot-password.css">

    <!-- Google font connection optimization -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- Website font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>

<body>

    <!-- Main page layout wrapper -->
    <div class="page-wrapper">

        <!-- Left side image area -->
        <div class="page-image"></div>

        <!-- Right side form area -->
        <div class="page-form-side">
            <div class="page-form">

                <!-- Website logo -->
                <div class="brand">
                    <img src="assets/images/logo.png" alt="MovieMeter Logo">
                </div>

                <!-- Page heading -->
                <h1 class="title">Forgot password?</h1>
                <p class="subtitle">Enter your email address and we’ll help you reset your password.</p>

                <!-- Display success or error message -->
                <?php if (!empty($message)) { ?>
                    <div class="message <?php echo htmlspecialchars($message_type); ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php } ?>

                <!-- Forgot password form -->
                <form method="POST" action="" id="forgotForm">

                    <!-- Email input field -->
                    <div class="form-group">
                        <label for="email">Email Address</label>
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

                    <!-- Submit button -->
                    <button type="submit" class="main-btn">Send Reset Link</button>

                    <!-- Back to login link -->
                    <div class="bottom-text">
                        Remember your password? <a href="login.php">Back to Login</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- jQuery library -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- Forgot password page JavaScript -->
    <script src="assets/js/forgot-password.js"></script>

</body>
</html>