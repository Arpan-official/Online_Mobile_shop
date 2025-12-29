<?php

require_once 'db.php';
include 'menu.php';

// Check if the user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$user_id = $isLoggedIn ? (int) $_SESSION['user_id'] : 0;

// Initialize cart
$cart = [];
$error = null;

try {
    // Fetch cart items for logged-in user
    if ($isLoggedIn) {
        $stmt = $pdo->prepare(
            "SELECT p.*, c.quantity
             FROM cart c
             JOIN products p ON c.product_id = p.id
             WHERE c.user_id = :uid"
        );
        $stmt->execute([':uid' => $user_id]);
        $cart = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Remove item from cart (keeps original behavior: remove by product id)
    if (isset($_GET['remove']) && $isLoggedIn) {
        $product_id = (int) $_GET['remove'];
        $del = $pdo->prepare("DELETE FROM cart WHERE user_id = :uid AND product_id = :pid");
        $del->execute([':uid' => $user_id, ':pid' => $product_id]);
        header("Location: cart.php");
        exit();
    }

    // Add item to cart
    if (isset($_POST['add_to_cart']) && $isLoggedIn) {
        $product_id = (int) ($_POST['product_id'] ?? 0);
        $quantity = (int) ($_POST['quantity'] ?? 1);
        if ($product_id <= 0 || $quantity <= 0) {
            $error = "Invalid product or quantity.";
        } else {
            // Check stock
            $stockStmt = $pdo->prepare("SELECT stock FROM products WHERE id = :pid LIMIT 1");
            $stockStmt->execute([':pid' => $product_id]);
            $stockRow = $stockStmt->fetch(PDO::FETCH_ASSOC);
            $stock = $stockRow ? (int)$stockRow['stock'] : 0;

            // Check if already in cart
            $checkStmt = $pdo->prepare("SELECT 1 FROM cart WHERE user_id = :uid AND product_id = :pid LIMIT 1");
            $checkStmt->execute([':uid' => $user_id, ':pid' => $product_id]);
            $alreadyInCart = (bool) $checkStmt->fetchColumn();

            if ($stock >= $quantity && !$alreadyInCart) {
                $insertStmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (:uid, :pid, :qty)");
                $insertStmt->execute([':uid' => $user_id, ':pid' => $product_id, ':qty' => $quantity]);
                header("Location: cart.php");
                exit();
            } else {
                $error = "This product is out of stock or already in your cart.";
            }
        }
    }
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
    <title>Your Cart - Mobile Store</title>
    <link rel="stylesheet" href="css/cart.css">
    <link rel="stylesheet" href="css/index.css">
</head>
<body>
    <div class="cart-container">
        <h2>Your Cart</h2>

        <?php if (!empty($cart)): ?>
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Image</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $total = 0.0;
                    foreach ($cart as $item):
                        // subtotal = product price * quantity
                        $price = (float) $item['price'];
                        $qty = (int) $item['quantity'];
                        $subtotal = $price * $qty;
                        $total += $subtotal;
                        // product id (from products table)
                        $productId = (int) $item['id'];
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($item['name']) ?></td>
                        <td>
                            <img src="images/<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" width="50">
                        </td>
                        <td><?= $qty ?></td>
                        <td>Rs. <?= number_format($subtotal, 2) ?></td>
                        <td>
                            <a href="cart.php?remove=<?= $productId ?>" class="btn">Remove</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="cart-total">
                <h3>Total: Rs. <?= number_format($total, 2) ?></h3>

                <?php if ($isLoggedIn): ?>
                    <form action="checkout.php" method="POST">
                        <input type="hidden" name="total" value="<?= htmlspecialchars($total) ?>">
                        <?php foreach ($cart as $item): ?>
                            <input type="hidden" name="products[]" value="<?= htmlspecialchars(json_encode([
                                'product_id' => (int)$item['id'],
                                'name' => $item['name'],
                                'price' => (float)$item['price'],
                                'quantity' => (int)$item['quantity'],
                                'image' => $item['image']
                            ])) ?>">
                        <?php endforeach; ?>
                        <button type="submit" class="btn">Proceed to Checkout</button>
                    </form>
                <?php else: ?>
                    <p>Please <a href="login.php">log in</a> to proceed to checkout.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <p>Your cart is empty. Start adding products!</p>
        <?php endif; ?>

        <?php if (isset($error) && $error): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
    </div>
</body>
</html>
