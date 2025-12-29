<?php

require_once 'db.php';
include 'menu.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$error = null;
$user = [
    'name' => '',
    'email' => '',
    'phone' => '',
    'address' => '',
    'city' => ''
];
$orders = [];

try {
    // Fetch user data
    $stmt = $pdo->prepare("SELECT id, name, email, phone, address, city FROM customers WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $user_id]);
    $userRow = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($userRow) {
        $user = $userRow;
    } else {
        // If user not found, force logout
        header("Location: logout.php");
        exit();
    }

    // Fetch recent orders (last 5)
    $orderStmt = $pdo->prepare("SELECT id, total, status, order_date FROM orders WHERE customer_id = :cid ORDER BY order_date DESC LIMIT 5");
    $orderStmt->execute([':cid' => $user_id]);
    $orders = $orderStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Optionally log $e->getMessage()
    $error = "A database error occurred. Please try again later.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Mobile Store</title>
    <link rel="stylesheet" href="css/profile.css">
</head>
<body>
    <!-- Include the menu (already included above) -->

    <!-- Profile Section -->
    <section class="profile-section">
        <div class="container">
            <h1>Welcome, <?php echo htmlspecialchars($user['name']); ?></h1>

            <?php if (!empty($error)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="profile-details">
                <div class="profile-card">
                    <h2>Personal Information</h2>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($user['phone']); ?></p>
                    <p><strong>Address:</strong> <?php echo htmlspecialchars($user['address']); ?></p>
                    <p><strong>City:</strong> <?php echo htmlspecialchars($user['city']); ?></p>
                    <a href="edit_profile.php" class="btn-edit">Edit Profile</a>
                </div>

                <div class="order-history">
                    <h2>Recent Orders</h2>
                    <?php if (!empty($orders)): ?>
                        <ul>
                            <?php foreach ($orders as $order): ?>
                                <li>
                                    <p>
                                        Order ID: <?php echo (int)$order['id']; ?> |
                                        Total: $<?php echo number_format((float)$order['total'], 2); ?> |
                                        Status: <?php echo htmlspecialchars($order['status']); ?>
                                    </p>
                                    <p><small>Order Date: <?php echo htmlspecialchars(date('Y-m-d', strtotime($order['order_date']))); ?></small></p>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p>You have no recent orders.</p>
                    <?php endif; ?>

                    <a href="my_orders.php" class="btn-view">View All Orders</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <p>&copy; 2024 Mobile Store. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
