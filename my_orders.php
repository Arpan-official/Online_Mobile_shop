<?php

require_once 'db.php';
include 'menu.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("
        SELECT o.*, c.name AS customer_name
        FROM orders o
        JOIN customers c ON o.customer_id = c.id
        WHERE o.customer_id = :uid
        ORDER BY o.order_date DESC
    ");
    $stmt->execute([':uid' => $user_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Optionally log $e->getMessage()
    $orders = [];
    $error = "A database error occurred. Please try again later.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Mobile Store</title>
    <link rel="stylesheet" href="css/my_orders.css">
</head>
<body>
    <div class="orders-container">
        <h1>My Orders</h1>

        <?php if (!empty($error)): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <?php if (!empty($orders)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Date</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?= htmlspecialchars($order['id']) ?></td>
                            <td><?= htmlspecialchars(date('Y-m-d', strtotime($order['order_date']))) ?></td>
                            <td>$<?= number_format((float)$order['total'], 2) ?></td>
                            <td><?= htmlspecialchars($order['status']) ?></td>
                            <td>
                                <a href="order_details.php?order_id=<?= (int)$order['id'] ?>" class="btn">View Details</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>You have no orders yet.</p>
        <?php endif; ?>

        <a href="index.php" class="btn">Back to Home</a>
    </div>
</body>
</html>
