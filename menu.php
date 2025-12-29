<?php
session_start();
require_once 'db.php'; // provides $pdo

$isLoggedIn = isset($_SESSION['user_id']);
$username = $isLoggedIn ? $_SESSION['username'] : '';
$isAdmin = false;

if ($isLoggedIn) {
    $customer_id = (int) $_SESSION['user_id'];
    try {
        $stmt = $pdo->prepare("SELECT 1 FROM admins WHERE customer_id = :cid LIMIT 1");
        $stmt->execute([':cid' => $customer_id]);
        $isAdmin = (bool) $stmt->fetchColumn();
    } catch (PDOException $e) {
        // optionally log $e->getMessage(); keep UI silent about DB internals
        $isAdmin = false;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Mobile Store</title>
    <link rel="stylesheet" href="css/menu.css">
</head>
<body>
<nav class="navbar">
    <div class="logo">
        <a href="index.php">Mobile Store</a>
    </div>
    <div class="menu-toggle" id="mobile-menu">
        <span class="bar"></span>
        <span class="bar"></span>
        <span class="bar"></span>
    </div>
    <ul class="nav-list">
        <li><a href="index.php">Home</a></li>
        <li>
            <a href="shop.php">Shop</a>
            <ul class="submenu">
                <li><a href="shop.php?type=smartphone">Smartphones</a></li>
                <li><a href="shop.php?type=tablet">Tablets</a></li>
                <li><a href="shop.php?type=accessory">Accessories</a></li>
            </ul>

        </li>
        <li><a href="aboutus.php">About Us</a></li>
        <?php if ($isLoggedIn): ?>
           
            <li>
                <a href="profile.php">Welcome, <?php echo htmlspecialchars($username); ?></a>
                <ul class="submenu">
                    <li><a href="my_orders.php">My Orders</a></li>
                    <?php if ($isAdmin): ?>
                        <li><a href="admin.php">Admin</a></li>
                    <?php endif; ?>
                    <li><a href="cart.php">Cart</a></li> 
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </li>
        <?php else: ?>
            <li><a href="login.php">Login</a></li>
            <li><a href="register.php">Register</a></li>
        <?php endif; ?>
    </ul>
</nav>
</body>
<script>
    const menuToggle = document.getElementById('mobile-menu');
    const navList = document.querySelector('.nav-list');

    menuToggle.addEventListener('click', function() {
        navList.classList.toggle('active');
        menuToggle.classList.toggle('active');
    });
</script>
</html>
