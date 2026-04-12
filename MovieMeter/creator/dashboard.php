<?php
require_once(__DIR__ . "/../includes/auth_check.php");

if (!isset($_SESSION["role_name"]) || $_SESSION["role_name"] !== "creator") {
    die("Access denied.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Creator Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #1f1229;
            color: #fff;
            margin: 0;
            padding: 40px 20px;
        }

        .box {
            max-width: 700px;
            margin: 0 auto;
            background: #2a1a36;
            padding: 30px;
            border-radius: 16px;
        }

        h2 {
            margin-top: 0;
        }

        p {
            color: #ddd;
        }

        .actions {
            margin-top: 25px;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .btn {
            display: inline-block;
            padding: 12px 18px;
            border: none;
            border-radius: 10px;
            background: #facc15;
            color: #1a1025;
            font-weight: bold;
            text-decoration: none;
            cursor: pointer;
        }

        .btn-secondary {
            background: #6a4385;
            color: #fff;
        }

        .form-row {
            margin: 18px 0;
        }

        input[type="number"] {
            width: 100px;
            padding: 10px;
            border-radius: 8px;
            border: none;
        }

        label {
            display: inline-block;
            margin-bottom: 8px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="box">
        <h2>Welcome Creator, <?php echo htmlspecialchars($_SESSION["full_name"]); ?></h2>
        <p>You have creator access.</p>
        <p>Role: <?php echo htmlspecialchars($_SESSION["role_name"]); ?></p>

        <hr>

        <h3>Import movies from TMDB</h3>
        <p>This will fetch movies from the external API and save them into your database as published movies.</p>

        <form action="import-api-movies.php" method="post">
            <div class="form-row">
                <label for="pages">How many API pages do you want to import?</label><br>
                <input type="number" id="pages" name="pages" min="1" max="20" value="3" required>
            </div>

            <div class="actions">
                <button type="submit" class="btn">Import Movies Now</button>
                <a href="../logout.php" class="btn btn-secondary">Logout</a>
            </div>
        </form>
    </div>
</body>
</html>