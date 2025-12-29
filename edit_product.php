<?php

require_once 'db.php';
include 'menu.php';

// Check if the user is logged in and is an administrator
$isLoggedIn = isset($_SESSION['user_id']);
if (!$isLoggedIn) {
    header("Location: login.php");
    exit();
}

$customer_id = (int) $_SESSION['user_id'];

// Admin check (lightweight)
try {
    $checkAdmin = $pdo->prepare("SELECT 1 FROM admins WHERE customer_id = :cid LIMIT 1");
    $checkAdmin->execute([':cid' => $customer_id]);
    $isAdmin = (bool) $checkAdmin->fetchColumn();
} catch (PDOException $e) {
    // optionally log $e->getMessage()
    $isAdmin = false;
}

if (!$isAdmin) {
    header("Location: unauthorized.php");
    exit();
}

// Ensure product_id is provided
if (!isset($_GET['product_id'])) {
    header("Location: admin.php");
    exit();
}

$product_id = (int) $_GET['product_id'];

// Fetch product
try {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = :pid LIMIT 1");
    $stmt->execute([':pid' => $product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        header("Location: admin.php?error=" . urlencode("Product not found."));
        exit();
    }
} catch (PDOException $e) {
    // optionally log $e->getMessage()
    header("Location: admin.php?error=" . urlencode("Database error."));
    exit();
}

// Fetch categories
try {
    $catStmt = $pdo->query("SELECT id, name FROM categories ORDER BY name");
    $categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categories = [];
}

// Handle update
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $price = $_POST['price'] ?? '';
    $stock = $_POST['stock'] ?? '';
    $description = trim($_POST['description'] ?? '');
    $category_id = (int) ($_POST['category_id'] ?? 0);

    // Basic validation
    if ($name === '') $errors[] = "Product name is required.";
    if ($price === '' || !is_numeric($price)) $errors[] = "Valid price is required.";
    if ($stock === '' || !is_numeric($stock) || (int)$stock < 0) $errors[] = "Valid stock quantity is required.";
    if ($category_id <= 0) $errors[] = "Please select a category.";

    // File upload handling (optional)
    $uploadImageName = null;
    if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $image_temp = $_FILES['image']['tmp_name'];
        $orig_name = basename($_FILES['image']['name']);
        $imageFileType = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));
        $image_size = $_FILES['image']['size'];

        // Validate image
        if (@getimagesize($image_temp) === false) {
            $errors[] = "Uploaded file is not a valid image.";
        }
        if ($image_size > 5 * 1024 * 1024) {
            $errors[] = "Image must be 5MB or smaller.";
        }
        $allowed = ['jpg','jpeg','png','gif'];
        if (!in_array($imageFileType, $allowed, true)) {
            $errors[] = "Allowed image types: JPG, JPEG, PNG, GIF.";
        }

        // Prepare safe unique filename
        if (empty($errors)) {
            $safe_name = bin2hex(random_bytes(8)) . '.' . $imageFileType;
            $target_dir = __DIR__ . '/images/';
            if (!is_dir($target_dir)) {
                @mkdir($target_dir, 0755, true);
            }
            $target_file = $target_dir . $safe_name;
            if (!move_uploaded_file($image_temp, $target_file)) {
                $errors[] = "Failed to move uploaded file.";
            } else {
                $uploadImageName = $safe_name; // store only safe name
            }
        }
    }

    if (empty($errors)) {
        try {
            if ($uploadImageName !== null) {
                $update = $pdo->prepare("
                    UPDATE products
                    SET name = :name, price = :price, stock = :stock, description = :desc, category_id = :cid, image = :image
                    WHERE id = :pid
                ");
                $update->execute([
                    ':name' => $name,
                    ':price' => (float)$price,
                    ':stock' => (int)$stock,
                    ':desc' => $description,
                    ':cid' => $category_id,
                    ':image' => $uploadImageName,
                    ':pid' => $product_id
                ]);
            } else {
                $update = $pdo->prepare("
                    UPDATE products
                    SET name = :name, price = :price, stock = :stock, description = :desc, category_id = :cid
                    WHERE id = :pid
                ");
                $update->execute([
                    ':name' => $name,
                    ':price' => (float)$price,
                    ':stock' => (int)$stock,
                    ':desc' => $description,
                    ':cid' => $category_id,
                    ':pid' => $product_id
                ]);
            }

            header("Location: admin.php?message=" . urlencode("Product updated successfully."));
            exit();
        } catch (PDOException $e) {
            // Optionally unlink uploaded file if DB failed
            if (!empty($uploadImageName) && file_exists(__DIR__ . '/images/' . $uploadImageName)) {
                @unlink(__DIR__ . '/images/' . $uploadImageName);
            }
            $errors[] = "Database error while updating product.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - Admin Dashboard</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <div class="admin-container">
        <h1>Edit Product</h1>

        <?php if (!empty($errors)): ?>
            <div class="errors">
                <ul>
                    <?php foreach ($errors as $err): ?>
                        <li><?php echo htmlspecialchars($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="edit_product.php?product_id=<?php echo (int)$product_id; ?>" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Product Name</label>
                <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
            </div>
            <div class="form-group">
                <label for="price">Price</label>
                <input type="number" step="0.01" name="price" id="price" value="<?php echo htmlspecialchars($product['price']); ?>" required>
            </div>
            <div class="form-group">
                <label for="stock">Stock Quantity</label>
                <input type="number" name="stock" id="stock" min="0" value="<?php echo htmlspecialchars($product['stock']); ?>" required>
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea name="description" id="description" rows="4" required><?php echo htmlspecialchars($product['description']); ?></textarea>
            </div>
            <div class="form-group">
                <label for="category_id">Category</label>
                <select name="category_id" id="category_id" required>
                    <option value="">Select a category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo (int)$category['id']; ?>" <?php echo $category['id'] == $product['category_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="image">Product Image (optional)</label>
                <input type="file" name="image" id="image" accept="image/*">
                <?php if (!empty($product['image'])): ?>
                    <p>Current image: <img src="images/<?php echo htmlspecialchars($product['image']); ?>" alt="" width="80"></p>
                <?php endif; ?>
            </div>
            <button type="submit" class="btn-submit">Update Product</button>
            <a href="admin.php" class="btn-cancel">Cancel</a>
        </form>
    </div>
</body>
</html>
