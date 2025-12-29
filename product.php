<?php

require_once 'db.php';
include 'menu.php';

$isLoggedIn = isset($_SESSION['user_id']);
$username = $isLoggedIn ? $_SESSION['username'] : '';

if (!isset($_GET['id'])) {
    echo "Product not found.";
    exit();
}

$product_id = (int) $_GET['id'];
$error = null;
$success = null;
$product = null;
$comments = [];
$hasPurchased = false;

try {
    // Fetch product + category name
    $stmt = $pdo->prepare("
        SELECT p.*, c.name AS category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.id = :pid
        LIMIT 1
    ");
    $stmt->execute([':pid' => $product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        echo "Product not found.";
        exit();
    }

    // Fetch comments with customer names
    $cstmt = $pdo->prepare("
        SELECT c.*, cu.name AS customer_name
        FROM comments c
        JOIN customers cu ON c.customer_id = cu.id
        WHERE c.product_id = :pid
        ORDER BY c.comment_date DESC
    ");
    $cstmt->execute([':pid' => $product_id]);
    $comments = $cstmt->fetchAll(PDO::FETCH_ASSOC);

    // Check purchase status (if logged in)
    if ($isLoggedIn) {
        $customer_id = (int) $_SESSION['user_id'];
        $pstmt = $pdo->prepare("
            SELECT 1
            FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            WHERE o.customer_id = :cid AND oi.product_id = :pid
            LIMIT 1
        ");
        $pstmt->execute([':cid' => $customer_id, ':pid' => $product_id]);
        $hasPurchased = (bool) $pstmt->fetchColumn();
    }

    // Handle Add to Cart
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
        if (!$isLoggedIn) {
            header("Location: login.php");
            exit();
        }

        $quantity = isset($_POST['quantity']) ? (int) $_POST['quantity'] : 1;
        if ($quantity < 1) $quantity = 1;

        // Refresh product stock from DB to be safe
        $stockStmt = $pdo->prepare("SELECT stock FROM products WHERE id = :pid LIMIT 1");
        $stockStmt->execute([':pid' => $product_id]);
        $stockRow = $stockStmt->fetch(PDO::FETCH_ASSOC);
        $stock = $stockRow ? (int)$stockRow['stock'] : 0;

        if ($stock < $quantity) {
            $error = "This product is out of stock or does not have enough quantity.";
        } else {
            // Check cart for existing item
            $checkCart = $pdo->prepare("SELECT 1 FROM cart WHERE user_id = :uid AND product_id = :pid LIMIT 1");
            $checkCart->execute([':uid' => (int)$_SESSION['user_id'], ':pid' => $product_id]);
            if ($checkCart->fetchColumn()) {
                $error = "This product is already in your cart.";
            } else {
                $insert = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (:uid, :pid, :qty)");
                $insert->execute([':uid' => (int)$_SESSION['user_id'], ':pid' => $product_id, ':qty' => $quantity]);
                $success = "Product added to cart successfully.";
                // reload comments/products if needed (not necessary)
            }
        }
    }

    // Handle submitting a comment (only if purchased)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
        if (!$isLoggedIn) {
            header("Location: login.php");
            exit();
        }
        if (!$hasPurchased) {
            $error = "You need to purchase this product before leaving a review.";
        } else {
            $commentText = trim($_POST['comment']);
            if ($commentText === '') {
                $error = "Comment cannot be empty.";
            } else {
                $ins = $pdo->prepare("INSERT INTO comments (product_id, customer_id, comment, comment_date) VALUES (:pid, :cid, :comment, NOW())");
                $ins->execute([
                    ':pid' => $product_id,
                    ':cid' => (int)$_SESSION['user_id'],
                    ':comment' => $commentText
                ]);
                // redirect to avoid resubmission
                header("Location: product.php?id=" . $product_id);
                exit();
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
    <title><?= htmlspecialchars($product['name']) ?> - Product Details</title>
    <link rel="stylesheet" href="css/product.css">
    <link rel="stylesheet" href="css/index.css">
</head>
<body>
    <div class="product-container">
        <div class="product-image">
            <img src="images/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
        </div>
        <div class="product-details">
            <h1><?= htmlspecialchars($product['name']) ?></h1>
            <p class="category">Category: <?= htmlspecialchars($product['category_name'] ?? 'Uncategorized') ?></p>
            <p class="category">Stock: <?= (int)$product['stock'] ?></p>
            <p class="description"><?= nl2br(htmlspecialchars($product['description'])) ?></p>
            <p class="price">Rs. <?= number_format((float)$product['price'], 2) ?></p>

            <?php if ((int)$product['stock'] <= 0): ?>
                <p class="error">This product is out of stock.</p>
            <?php else: ?>
                <form method="POST" action="product.php?id=<?= $product_id ?>">
                    <label for="quantity">Quantity:</label>
                    <input type="number" name="quantity" id="quantity" value="1" min="1" max="<?= (int)$product['stock'] ?>" required>
                    <button type="submit" name="add_to_cart" class="btn">Add to Cart</button>
                </form>
            <?php endif; ?>

            <?php if ($success): ?>
                <p class="success"><?= htmlspecialchars($success) ?></p>
            <?php elseif ($error): ?>
                <p class="error"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div class="comments-section">
        <h2>Customer Reviews</h2>

        <?php if (!empty($comments)): ?>
            <div class="comments-list">
                <?php foreach ($comments as $comment): ?>
                    <div class="comment">
                        <h4><?= htmlspecialchars($comment['customer_name']) ?></h4>
                        <p><?= nl2br(htmlspecialchars($comment['comment'])) ?></p>
                        <span class="comment-date"><?= htmlspecialchars($comment['comment_date']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>No comments yet. Be the first to review this product!</p>
        <?php endif; ?>

        <?php if ($isLoggedIn): ?>
            <?php if ($hasPurchased): ?>
                <form action="product.php?id=<?= $product_id ?>" method="POST" class="comment-form">
                    <textarea name="comment" placeholder="Leave your review here..." required></textarea>
                    <button type="submit" class="btn">Submit Comment</button>
                </form>
            <?php else: ?>
                <p>You need to purchase this product before leaving a review.</p>
            <?php endif; ?>
        <?php else: ?>
            <p><a href="login.php">Log in</a> to leave a comment.</p>
        <?php endif; ?>
    </div>
</body>
</html>
