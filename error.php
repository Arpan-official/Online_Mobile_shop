<?php
include 'menu.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - Mobile Store</title>
    <link rel="stylesheet" href="css/error.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f6f8;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .error-container {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
            padding: 40px;
            max-width: 500px;
            text-align: center;
        }
        .error-container h1 {
            color: #e63946;
            font-size: 32px;
            margin-bottom: 10px;
        }
        .error-container p {
            font-size: 18px;
            color: #333;
            margin: 10px 0;
        }
        .btn {
            background-color: #ff6600;
            color: #fff;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-size: 16px;
            display: inline-block;
            margin-top: 20px;
            transition: background 0.3s ease;
        }
        .btn:hover {
            background-color: #e65c00;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <h1>Oops! Something went wrong</h1>
        <p>We encountered an unexpected issue while processing your request.</p>
        <p>If this continues, please contact our support team.</p>
        <a href="index.php" class="btn">Return to Home</a>
    </div>
</body>
</html>
