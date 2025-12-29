<?php

require_once 'db.php';
include 'menu.php';

// Validate order_id
$order_id = isset($_GET['order_id']) ? (int) $_GET['order_id'] : 0;
if ($order_id <= 0) {
    header("Location: error.php");
    exit();
}

try {
    // Fetch order (with customer name)
    $stmt = $pdo->prepare("
        SELECT o.*, c.name AS customer_name
        FROM orders o
        JOIN customers c ON o.customer_id = c.id
        WHERE o.id = :oid
        LIMIT 1
    ");
    $stmt->execute([':oid' => $order_id]);
    $order_details = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order_details) {
        header("Location: error.php");
        exit();
    }

    // Fetch order items with product names
    $itemsStmt = $pdo->prepare("
        SELECT oi.*, p.name AS product_name
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = :oid
    ");
    $itemsStmt->execute([':oid' => $order_id]);
    $items_result = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

    $items_purchased = [];
    foreach ($items_result as $item) {
        $items_purchased[] = [
            'name' => $item['product_name'],
            'quantity' => (int)$item['quantity'],
            'price' => (float)$item['price']
        ];
    }
} catch (PDOException $e) {
    // Optionally log $e->getMessage()
    header("Location: error.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - Mobile Store</title>
    <link rel="stylesheet" href="css/order_details.css">
</head>
<body>
    <div class="order-details-container">
        <h1>Order Details</h1>
        
        <h2>Customer Information</h2>
        <p><strong>Name:</strong> <?= htmlspecialchars($order_details['customer_name']) ?></p>
        <p><strong>Order ID:</strong> <?= htmlspecialchars($order_details['id']) ?></p>
        <p><strong>Date:</strong> <?= htmlspecialchars(date('Y-m-d', strtotime($order_details['order_date']))) ?></p>
        <p><strong>Status:</strong> <?= htmlspecialchars($order_details['status']) ?></p>
        <p><strong>Total Paid:</strong> $<?= number_format((float)$order_details['total'], 2) ?></p>

        <h2>Items Purchased</h2>
        <ul>
            <?php foreach ($items_purchased as $item): ?>
                <li>
                    <?= htmlspecialchars($item['name']) ?> 
                    x<?= $item['quantity'] ?> - $<?= number_format($item['price'] * $item['quantity'], 2) ?>
                </li>
            <?php endforeach; ?>
        </ul>

        <a href="my_orders.php" class="btn">Back to My Orders</a>
        <a href="index.php" class="btn">Back to Home</a>
    </div>
</body>
</html>
