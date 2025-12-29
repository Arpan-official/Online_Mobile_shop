<?php

require_once 'db.php';
include 'menu.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$error_message = null;

// Fetch user data
try {
    $stmt = $pdo->prepare("SELECT id, name, email, phone, address, city FROM customers WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // If user not found, log out or redirect
        header("Location: login.php");
        exit();
    }
} catch (PDOException $e) {
    // Optionally log $e->getMessage()
    $error_message = "A database error occurred. Please try again later.";
    $user = [
        'name' => '',
        'email' => '',
        'phone' => '',
        'address' => '',
        'city' => ''
    ];
}

// Handle POST (update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Trim and collect inputs
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');

    // Basic validation
    $errors = [];
    if ($name === '') $errors[] = "Name is required.";
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "A valid email is required.";
    // optional: validate phone format, lengths, etc.

    if (empty($errors)) {
        try {
            // Check if email is used by another account
            $check = $pdo->prepare("SELECT id FROM customers WHERE email = :email AND id != :id LIMIT 1");
            $check->execute([':email' => $email, ':id' => $user_id]);
            if ($check->fetch()) {
                $errors[] = "That email is already registered to another account.";
            } else {
                // Perform update
                $update = $pdo->prepare("
                    UPDATE customers
                    SET name = :name, email = :email, phone = :phone, address = :address, city = :city
                    WHERE id = :id
                ");
                $update->execute([
                    ':name' => $name,
                    ':email' => $email,
                    ':phone' => $phone,
                    ':address' => $address,
                    ':city' => $city,
                    ':id' => $user_id
                ]);

                // Update session username if changed
                $_SESSION['username'] = $name;

                // Redirect back to profile (or show success)
                header("Location: profile.php");
                exit();
            }
        } catch (PDOException $e) {
            // Optionally log $e->getMessage()
            $errors[] = "An unexpected database error occurred. Please try again later.";
        }
    }

    if (!empty($errors)) {
        $error_message = implode(' ', $errors);
        // Keep $user values so the form repopulates with user's latest inputs
        $user['name'] = $name;
        $user['email'] = $email;
        $user['phone'] = $phone;
        $user['address'] = $address;
        $user['city'] = $city;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Mobile Store</title>
    <link rel="stylesheet" href="css/edit_profile.css">
</head>
<body>
    <!-- Include the menu -->
    <?php include 'menu.php'; ?>

    <!-- Edit Profile Section -->
    <section class="edit-profile-section">
        <div class="container">
            <h1>Edit Profile</h1>

            <?php if (!empty($error_message)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <form method="POST" action="edit_profile.php">
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="text" name="phone" id="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="address">Address</label>
                    <input type="text" name="address" id="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="city">City</label>
                    <input type="text" name="city" id="city" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>" required>
                </div>
                <button type="submit" class="btn-submit">Update Profile</button>
                <a href="account.php" class="btn-cancel">Cancel</a>
            </form>
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
