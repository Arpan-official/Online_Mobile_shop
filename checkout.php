<?php

require_once 'db.php';
include 'menu.php';

// Check if the user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$user_id = $isLoggedIn ? (int) $_SESSION['user_id'] : 0;

// Initialize variables
$total = 0.0;
$cartItems = [];
$error = null;

// Helper: safely decode posted products (array of JSON strings)
function decodeProducts(array $productsPost): array {
    $items = [];
    foreach ($productsPost as $p) {
        $decoded = json_decode($p, true); // assoc array
        if (is_array($decoded) && isset($decoded['product_id'])) {
            // Support both product id field names ('product_id' or 'id')
            $items[] = [
                'id' => isset($decoded['product_id']) ? (int)$decoded['product_id'] : (int)($decoded['id'] ?? 0),
                'name' => $decoded['name'] ?? '',
                'price' => isset($decoded['price']) ? (float)$decoded['price'] : 0.0,
                'quantity' => isset($decoded['quantity']) ? (int)$decoded['quantity'] : (int)($decoded['qty'] ?? 0),
                'image' => $decoded['image'] ?? ''
            ];
        } else {
            // try decode as object style (legacy)
            $obj = json_decode($p);
            if ($obj && isset($obj->id)) {
                $items[] = [
                    'id' => (int)$obj->id,
                    'name' => $obj->name ?? '',
                    'price' => isset($obj->price) ? (float)$obj->price : 0.0,
                    'quantity' => isset($obj->quantity) ? (int)$obj->quantity : 0,
                    'image' => $obj->image ?? ''
                ];
            }
        }
    }
    return $items;
}

// If products were posted (coming from cart.php), decode them
if (!empty($_POST['products']) && is_array($_POST['products'])) {
    $cartItems = decodeProducts($_POST['products']);
}

// If total was posted
if (isset($_POST['total'])) {
    $total = (float) $_POST['total'];
}

// Process form submission (payment simulation + order creation)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic payment fields presence check
    $hasPaymentFields = isset($_POST['card_number'], $_POST['expiry_date'], $_POST['cvv'], $_POST['name']);

    if (empty($cartItems)) {
        $error = "Your cart is empty. Please add products before checking out.";
    } elseif (!$isLoggedIn) {
        $error = "Please log in to place an order.";
    } elseif (!$hasPaymentFields) {
        // If products posted but payment fields not posted yet, just show the form (no error)
        // (This allows showing summary first.)
    } else {
        // Collect payment fields (we do not store card info — this is simulated)
        $card_number = preg_replace('/\D/', '', $_POST['card_number']);
        $expiry_date = $_POST['expiry_date']; // expected YYYY-MM
        $cvv = preg_replace('/\D/', '', $_POST['cvv']);
        $card_name = trim($_POST['name']);

        // Validate payment fields (basic client-side style validation server-side too)
        if ($card_number === '' || $expiry_date === '' || $cvv === '' || $card_name === '') {
            $error = "Please fill in all payment fields.";
        } else {
            // Parse expiry_date (YYYY-MM)
            $parts = explode('-', $expiry_date);
            if (count($parts) !== 2) {
                $error = "Invalid expiry date format.";
            } else {
                $expiry_year = (int)$parts[0];
                $expiry_month = (int)$parts[1];
                $current_year = (int)date('Y');
                $current_month = (int)date('m');

                if ($expiry_year < $current_year || ($expiry_year === $current_year && $expiry_month < $current_month)) {
                    $error = "Card has expired.";
                }
            }

            // Validate CVV
            if (!preg_match('/^\d{3,4}$/', $cvv)) {
                $error = "Invalid CVV.";
            }

            // Basic Luhn check for card number (optional but helpful)
            function luhn_check($number) {
                $sum = 0;
                $alt = false;
                $num = strrev($number);
                for ($i = 0, $len = strlen($num); $i < $len; $i++) {
                    $n = (int)$num[$i];
                    if ($alt) {
                        $n *= 2;
                        if ($n > 9) $n -= 9;
                    }
                    $sum += $n;
                    $alt = !$alt;
                }
                return ($sum % 10) === 0;
            }
            if (!luhn_check($card_number)) {
                $error = "Invalid card number.";
            }
        }

        // If still valid, attempt order creation in a DB transaction
        if (!isset($error)) {
            try {
                // Begin transaction
                $pdo->beginTransaction();

                // For each item, lock the product row and check stock
                foreach ($cartItems as $item) {
                    $pid = (int)$item['id'];
                    $qty = (int)$item['quantity'];
                    if ($pid <= 0 || $qty <= 0) {
                        throw new Exception("Invalid product data.");
                    }

                    // Lock the product row for update to avoid race conditions
                    $lockStmt = $pdo->prepare("SELECT stock, name FROM products WHERE id = :pid FOR UPDATE");
                    $lockStmt->execute([':pid' => $pid]);
                    $prod = $lockStmt->fetch(PDO::FETCH_ASSOC);
                    if (!$prod) {
                        throw new Exception("Product not found: " . htmlspecialchars($item['name']));
                    }
                    $currentStock = (int)$prod['stock'];

                    if ($currentStock < $qty) {
                        throw new Exception("Not enough stock for " . htmlspecialchars($prod['name']) . ".");
                    }
                }

                // Create order
                $status = "Pending";
                $insertOrder = $pdo->prepare("INSERT INTO orders (customer_id, total, status, order_date) VALUES (:cid, :total, :status, NOW())");
                $insertOrder->execute([
                    ':cid' => $user_id,
                    ':total' => $total,
                    ':status' => $status
                ]);
                $orderId = (int)$pdo->lastInsertId();

                // Insert order items and decrement stock
                $insertItem = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (:oid, :pid, :qty, :price)");
                $updateStock = $pdo->prepare("UPDATE products SET stock = stock - :qty WHERE id = :pid");

                foreach ($cartItems as $item) {
                    $pid = (int)$item['id'];
                    $qty = (int)$item['quantity'];
                    $price = (float)$item['price'];

                    // Insert order item
                    $insertItem->execute([
                        ':oid' => $orderId,
                        ':pid' => $pid,
                        ':qty' => $qty,
                        ':price' => $price
                    ]);

                    // Decrement stock
                    $updateStock->execute([
                        ':qty' => $qty,
                        ':pid' => $pid
                    ]);
                }

                // Clear cart for user
                $clearCart = $pdo->prepare("DELETE FROM cart WHERE user_id = :uid");
                $clearCart->execute([':uid' => $user_id]);

                // Commit transaction
                $pdo->commit();

                
                // Redirect to loading/confirmation
                header("Location: loading.php?order_id=" . $orderId);
                exit();
            } catch (Exception $e) {
                // Roll back on error
                if ($pdo->inTransaction()) $pdo->rollBack();
                // Optionally log $e->getMessage()
                $error = $e->getMessage() ?: "An error occurred while processing your order.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Mobile Store</title>
    <link rel="stylesheet" href="css/checkout.css">
    <link rel="stylesheet" href="css/index.css">
</head>
<body>
    <div class="checkout-container">
        <div class="payment-form">
            <h2>Payment Information</h2>

            <?php if (!empty($cartItems)): ?>
                <form action="checkout.php" method="POST">
                    <label for="name">Name on Card:</label>
                    <input type="text" id="name" name="name" required>

                    <label for="card_number">Card Number:</label>
                    <input type="text" id="card_number" name="card_number" maxlength="19" required>

                    <label for="expiry_date">Expiry Date (MM-YYYY):</label>
                    <input type="month" id="expiry_date" name="expiry_date" required>

                    <label for="cvv">CVV:</label>
                    <input type="text" id="cvv" name="cvv" maxlength="4" required>

                    <input type="hidden" name="total" value="<?= htmlspecialchars($total) ?>">
                    <?php foreach ($cartItems as $item): ?>
                        <input type="hidden" name="products[]" value="<?= htmlspecialchars(json_encode($item)) ?>">
                    <?php endforeach; ?>

                    <button type="submit" class="btn">Confirm Payment</button>
                </form>

                <?php if (isset($error)): ?>
                    <p class="error"><?= htmlspecialchars($error) ?></p>
                <?php endif; ?>
            <?php else: ?>
                <p>Your cart is empty. Go back and add some products!</p>
            <?php endif; ?>
        </div>

        <div class="order-summary">
            <h2>Order Summary</h2>
            <ul>
                <?php foreach ($cartItems as $item): ?>
                    <li>
                        <?= htmlspecialchars($item['name']) ?> (x<?= htmlspecialchars($item['quantity']) ?>) - $<?= htmlspecialchars(number_format($item['price'], 2)) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
            <p><strong>Total: $<?= htmlspecialchars(number_format($total, 2)) ?></strong></p>
        </div>
    </div>
</body>
</html>
