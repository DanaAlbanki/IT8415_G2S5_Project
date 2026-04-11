<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once(__DIR__ . "/config/DBConn.php");

$dbc = getConnection();

$message = "";
$message_type = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"] ?? "");

    if (empty($email)) {
        $message = "Please enter your email address.";
        $message_type = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $message_type = "error";
    } else {
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - MovieMeter</title>

    <link rel="stylesheet" href="assets/css/forgot-password.css">
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

                <h1 class="title">Forgot password?</h1>
                <p class="subtitle">Enter your email address and we’ll help you reset your password.</p>

                <?php if (!empty($message)) { ?>
                    <div class="message <?php echo htmlspecialchars($message_type); ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php } ?>

                <form method="POST" action="" id="forgotForm">
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

                    <button type="submit" class="main-btn">Send Reset Link</button>

                    <div class="bottom-text">
                        Remember your password? <a href="login.php">Back to Login</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="assets/js/forgot-password.js"></script>
</body>
</html>