<?php

require_once 'db.php'; 
include 'menu.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Unauthorized - Mobile Store</title>
    <link rel="stylesheet" href="css/error.css">
    <style>
        /* Small inline fallback in case css/error.css is missing */
        .unauth-container {
            max-width: 720px;
            margin: 80px auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            padding: 36px;
            text-align: center;
            font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
        }
        .unauth-h1 { color: #e63946; margin: 0 0 12px; font-size: 28px; }
        .unauth-p { color: #333; margin-bottom: 18px; font-size: 16px; }
        .btn {
            display:inline-block;
            background:#ff6600;
            color:#fff;
            padding:10px 18px;
            border-radius:6px;
            text-decoration:none;
        }
    </style>
</head>
<body>
    <div class="unauth-container">
        <h1 class="unauth-h1">Unauthorized</h1>
        <p class="unauth-p">You do not have permission to view this page. If you think this is a mistake, contact the site administrator.</p>

        <?php if (isset($_SESSION['user_id'])): ?>
            <a class="btn" href="index.php">Back to Home</a>
            <?php if (!empty($_SESSION['username'])): ?>
                <a class="btn" href="profile.php" style="margin-left:8px;">My Account</a>
            <?php endif; ?>
        <?php else: ?>
            <a class="btn" href="login.php">Log in</a>
            <a class="btn" href="index.php" style="margin-left:8px;">Back to Home</a>
        <?php endif; ?>
    </div>
</body>
</html>
