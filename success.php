<?php

require_once 'db.php';


// Validate order_id
$order_id = isset($_GET['order_id']) ? (int) $_GET['order_id'] : 0;
if ($order_id <= 0) {
    header("Location: error.php");
    exit();
}

try {
    // Fetch order with customer name
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

    if (empty($items_result)) {
        header("Location: error.php");
        exit();
    }

    // Prepare items array for display
    $items_purchased = [];
    foreach ($items_result as $item) {
        $items_purchased[] = [
            'name'     => $item['product_name'],
            'quantity' => (int)$item['quantity'],
            'price'    => (float)$item['price'],
        ];
    }
} catch (PDOException $e) {
    // Optionally log $e->getMessage() for debugging
    header("Location: error.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Success - Mobile Store</title>
    <link rel="stylesheet" href="css/success.css">
</head>
<body>
    <div class="success-container">
        <h1>Thank You for Your Purchase!</h1>
        <p>Your payment has been processed successfully.</p>
        <p>Your order is being processed — you can check its status in your orders.</p>

        <h2>Order Invoice</h2>
        <div class="invoice">
            <p><strong>Name:</strong> <?= htmlspecialchars($order_details['customer_name']) ?></p>
            <p><strong>Order ID:</strong> <?= (int)$order_details['id'] ?></p>
            <p><strong>Total Paid:</strong> Rs. <?= number_format((float)$order_details['total'], 2) ?></p>

            <h3>Items Purchased</h3>
            <ul>
                <?php foreach ($items_purchased as $item): ?>
                    <li>
                        <?= htmlspecialchars($item['name']) ?> 
                        x<?= $item['quantity'] ?> - $<?= number_format($item['price'] * $item['quantity'], 2) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <a href="my_orders.php" class="btn">My Orders</a>
        <a href="index.php" class="btn">Back to Home</a>
    </div>
</body>
</html>
