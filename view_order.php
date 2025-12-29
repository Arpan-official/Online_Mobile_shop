<?php

require_once 'db.php';
include 'menu.php';

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get order ID
$order_id = isset($_GET['order_id']) ? (int) $_GET['order_id'] : 0;
if ($order_id <= 0) {
    echo "Invalid order ID!";
    exit();
}

try {
    // Fetch order details
    $orderStmt = $pdo->prepare("
        SELECT o.*, c.name AS customer_name
        FROM orders o
        JOIN customers c ON o.customer_id = c.id
        WHERE o.id = :oid
        LIMIT 1
    ");
    $orderStmt->execute([':oid' => $order_id]);
    $order = $orderStmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo "Order not found!";
        exit();
    }

    // Fetch ordered items
    $itemsStmt = $pdo->prepare("
        SELECT oi.*, p.name AS product_name
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = :oid
    ");
    $itemsStmt->execute([':oid' => $order_id]);
    $order_items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Optional: log $e->getMessage();
    echo "An error occurred while fetching order details.";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - Mobile Store</title>
    <link rel="stylesheet" href="css/admin.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .order-details-container {
            max-width: 800px;
            margin: 20px auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1, h2, h3 {
            text-align: center;
            color: #333;
        }
        p {
            font-size: 16px;
            line-height: 1.6;
            margin: 10px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #007bff;
            color: white;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        .btn-back {
            display: block;
            width: 100%;
            text-align: center;
            background-color: #007bff;
            color: white;
            padding: 10px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .btn-back:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="order-details-container">
        <h1>Order Details</h1>
        <h2>Order ID: <?= htmlspecialchars($order['id']) ?></h2>
        <p><strong>Customer Name:</strong> <?= htmlspecialchars($order['customer_name']) ?></p>
        <p><strong>Order Date:</strong> <?= htmlspecialchars($order['order_date']) ?></p>
        <p><strong>Status:</strong> <?= htmlspecialchars($order['status']) ?></p>
        <p><strong>Total:</strong> $<?= number_format((float)$order['total'], 2) ?></p>

        <h3>Ordered Items</h3>
        <?php if (!empty($order_items)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>Quantity</th>
                        <th>Price per Unit</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order_items as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['product_name']) ?></td>
                            <td><?= (int)$item['quantity'] ?></td>
                            <td>$<?= number_format((float)$item['price'], 2) ?></td>
                            <td>$<?= number_format($item['quantity'] * $item['price'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No items found for this order.</p>
        <?php endif; ?>

        <a href="admin.php" class="btn-back">Back to Admin Dashboard</a>
    </div>
</body>
</html>
