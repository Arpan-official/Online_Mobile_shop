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
    $stmt = $pdo->prepare("SELECT 1 FROM admins WHERE customer_id = :cid LIMIT 1");
    $stmt->execute([':cid' => $customer_id]);
    $isAdmin = (bool) $stmt->fetchColumn();
} catch (PDOException $e) {
    // Log error if you have a logger, but don't expose DB internals to users
    $isAdmin = false;
}

if (!$isAdmin) {
    header("Location: unauthorized.php");
    exit();
}

// Fetch categories from the database
try {
    $stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    $categories = [];
    // Optionally set a flash message to show an error
}

// Handle product submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic trimming and validation
    $name = trim($_POST['name'] ?? '');
    $price = $_POST['price'] ?? '';
    $stock = $_POST['stock'] ?? '';
    $description = trim($_POST['description'] ?? '');
    $category_id = (int) ($_POST['category_id'] ?? 0);

    // Validate required fields (simple)
    $errors = [];
    if ($name === '') $errors[] = "Product name is required.";
    if ($price === '' || !is_numeric($price)) $errors[] = "Valid price is required.";
    if ($stock === '' || !is_numeric($stock)) $errors[] = "Valid stock quantity is required.";
    if ($category_id <= 0) $errors[] = "Please choose a category.";
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "Product image is required.";
    }

    // File upload handling
    if (empty($errors)) {
        $target_dir = __DIR__ . "/images/"; // absolute path is safer
        if (!is_dir($target_dir)) {
            // Try to create directory if not exists
            @mkdir($target_dir, 0755, true);
        }

        $original_filename = basename($_FILES["image"]["name"]);
        $imageFileType = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));
        $image_temp = $_FILES["image"]["tmp_name"];
        $image_size = $_FILES["image"]["size"];

        // Check if image is actual image
        $check = @getimagesize($image_temp);
        if ($check === false) {
            $errors[] = "File is not a valid image.";
        }

        // Check file size (limit to 5MB)
        if ($image_size > 5 * 1024 * 1024) {
            $errors[] = "Sorry, your file is too large (max 5MB).";
        }

        // Allowed formats
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($imageFileType, $allowed, true)) {
            $errors[] = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
        }

        // Generate a unique filename to avoid collisions
        $safe_basename = bin2hex(random_bytes(8)) . '.' . $imageFileType;
        $target_file = $target_dir . $safe_basename;
    }

    if (empty($errors)) {
        if (move_uploaded_file($image_temp, $target_file)) {
            // Insert product into DB
            try {
                $insert = $pdo->prepare("
                    INSERT INTO products (name, price, stock, description, image, category_id)
                    VALUES (:name, :price, :stock, :description, :image, :category_id)
                ");
                $insert->execute([
                    ':name' => $name,
                    ':price' => (float) $price,
                    ':stock' => (int) $stock,
                    ':description' => $description,
                    ':image' => $safe_basename, // store only the safe filename
                    ':category_id' => $category_id
                ]);

                header("Location: admin.php?message=" . urlencode("Product added successfully."));
                exit();
            } catch (PDOException $e) {
                // Remove uploaded file if DB insert failed
                if (file_exists($target_file)) {
                    @unlink($target_file);
                }
                $errors[] = "Database error while adding product.";
                // Optionally log $e->getMessage()
            }
        } else {
            $errors[] = "Sorry, there was an error uploading your file.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - Admin Dashboard</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <div class="admin-container">
        <h1>Add New Product</h1>

        <?php if (!empty($errors)): ?>
            <div class="errors">
                <ul>
                    <?php foreach ($errors as $err): ?>
                        <li><?php echo htmlspecialchars($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="add_product.php" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Product Name</label>
                <input type="text" name="name" id="name" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required>
            </div>
            <div class="form-group">
                <label for="price">Price</label>
                <input type="number" step="0.01" name="price" id="price" value="<?php echo isset($price) ? htmlspecialchars($price) : ''; ?>" required>
            </div>
            <div class="form-group">
                <label for="stock">Stock Quantity</label>
                <input type="number" name="stock" id="stock" value="<?php echo isset($stock) ? htmlspecialchars($stock) : ''; ?>" required>
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea name="description" id="description" rows="4" required><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
            </div>
            <div class="form-group">
                <label for="category_id">Category</label>
                <select name="category_id" id="category_id" required>
                    <option value="">Select a category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo (int)$category['id']; ?>" <?php echo (isset($category_id) && $category_id == $category['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="image">Product Image</label>
                <input type="file" name="image" id="image" accept="image/*" required>
            </div>
            <button type="submit" class="btn-submit">Add Product</button>
            <a href="admin.php" class="btn-cancel">Cancel</a>
        </form>
    </div>
</body>
</html>
