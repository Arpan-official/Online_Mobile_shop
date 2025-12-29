<?php

require_once 'db.php';

// Get and validate order_id
$order_id = isset($_GET['order_id']) ? (int) $_GET['order_id'] : 0;
if ($order_id <= 0) {
    header("Location: error.php");
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($order) {
        // Store order_id in session if needed
        $_SESSION['order_id'] = $order_id;

        // Redirect to success page after 3 seconds and show spinner
        header("Refresh: 3; url=success.php?order_id=" . $order_id);
        $message = "Redirecting to success page...";
    } else {
        header("Location: error.php");
        exit();
    }
} catch (PDOException $e) {
    // Optionally log $e->getMessage() to a file for debugging
    header("Location: error.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Processing Payment - Mobile Store</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: white;
        }
        .container {
            width: 100%;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #ffffff;
        }
        .spinner {
            width: 80px;
            height: 80px;
            border: 7px solid #ff8800;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spinner 0.7s linear infinite;
        }
        @keyframes spinner {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .msg {
            margin-top: 16px;
            text-align: center;
            color: #333;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div>
            <div class="spinner"></div>
            <?php if (!empty($message)): ?>
                <div class="msg"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
