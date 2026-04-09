<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once(__DIR__ . "/config/DBConn.php");
// Open database connection
$dbc = getConnection();

// Message for success or error
$message = "";

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Get form data and remove extra spaces
    $full_name = trim($_POST["full_name"]);
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];

    // Default role = viewer
    $role_id = 3;

    // Check if any field is empty
    if (empty($full_name) || empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $message = "All fields are required.";
    }
    // Check if passwords match
    elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";
    }
    else {
        // Check if username or email already exists
        $check_sql = "SELECT user_id FROM mm_users WHERE username = ? OR email = ?";
        $stmt = mysqli_prepare($dbc, $check_sql);
        mysqli_stmt_bind_param($stmt, "ss", $username, $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) > 0) {
            $message = "Username or email already exists.";
        } else {
            // Hash the password before saving
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            // Insert new user into the database
            $insert_sql = "INSERT INTO mm_users
                           (role_id, full_name, username, email, password_hash, account_status, created_at, updated_at)
                           VALUES (?, ?, ?, ?, ?, 'active', NOW(), NOW())";

            $stmt = mysqli_prepare($dbc, $insert_sql);
            mysqli_stmt_bind_param($stmt, "issss", $role_id, $full_name, $username, $email, $password_hash);

            if (mysqli_stmt_execute($stmt)) {
                $message = "Registration successful. You can login now.";
            } else {
                $message = "Registration failed: " . mysqli_error($dbc);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
</head>
<body>
    <h2>Register</h2>

    <?php if (!empty($message)) { ?>
        <p><?php echo $message; ?></p>
    <?php } ?>

    <form method="POST">
        <input type="text" name="full_name" placeholder="Full Name"><br><br>
        <input type="text" name="username" placeholder="Username"><br><br>
        <input type="email" name="email" placeholder="Email"><br><br>
        <input type="password" name="password" placeholder="Password"><br><br>
        <input type="password" name="confirm_password" placeholder="Confirm Password"><br><br>
        <button type="submit">Register</button>
    </form>

    <p><a href="login.php">Go to Login</a></p>
</body>
</html>