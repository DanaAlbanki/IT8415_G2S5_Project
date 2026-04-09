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
    $login_input = trim($_POST["login_input"]);
    $password = $_POST["password"];

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
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
</head>
<body>
    <h2>Login</h2>

    <?php if (!empty($message)) { ?>
        <p><?php echo htmlspecialchars($message); ?></p>
    <?php } ?>

    <form method="POST">
        <input type="text" name="login_input" placeholder="Username or Email"><br><br>
        <input type="password" name="password" placeholder="Password"><br><br>
        <button type="submit">Login</button>
    </form>

    <p><a href="register.php">Go to Register</a></p>
</body>
</html>