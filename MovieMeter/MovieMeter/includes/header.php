<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MovieMeter</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background: #f5f5f5;
        }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 20px auto;
        }

        .movie-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .movie-card {
            background: #fff;
            border: 1px solid #ddd;
            padding: 15px;
            width: 250px;
            border-radius: 8px;
        }

        .movie-card img,
        .movie-card video {
            width: 100%;
            border-radius: 6px;
            margin-bottom: 10px;
        }

        .movie-card h3 {
            margin: 10px 0 8px;
        }

        .btn {
            display: inline-block;
            padding: 8px 14px;
            background: #222;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
        }

        .btn:hover {
            background: #444;
        }

        .details-box {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #ddd;
        }

        .details-box img,
        .details-box video {
            max-width: 400px;
            width: 100%;
            margin-bottom: 15px;
            border-radius: 8px;
        }

        .message {
            background: #fff3cd;
            padding: 12px;
            border: 1px solid #ffe69c;
            border-radius: 6px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>