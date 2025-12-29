<?php

require_once 'db.php';
include 'menu.php';

// Check login
$isLoggedIn = isset($_SESSION['user_id']);
$username = $isLoggedIn ? $_SESSION['username'] : '';

if (!$isLoggedIn) {
    header("Location: login.php");
    exit();
}

$customer_id = (int) $_SESSION['user_id'];

// Admin check
try {
    $stmt = $pdo->prepare("SELECT 1 FROM admins WHERE customer_id = :cid LIMIT 1");
    $stmt->execute([':cid' => $customer_id]);
    $isAdmin = (bool) $stmt->fetchColumn();
} catch (PDOException $e) {
    // Optionally log $e->getMessage()
    $isAdmin = false;
}

if (!$isAdmin) {
    header("Location: unauthorized.php");
    exit();
}

// Data-fetching functions (use PDO)
function getProducts(PDO $pdo)
{
    $stmt = $pdo->query("SELECT * FROM products");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getOrders(PDO $pdo, $status = null)
{
    $sql = "SELECT o.*, c.name AS customer_name FROM orders o
            JOIN customers c ON o.customer_id = c.id";
    $params = [];
    if ($status) {
        $sql .= " WHERE o.status = :status";
        $params[':status'] = $status;
    }
    $sql .= " ORDER BY o.order_date DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getUsers(PDO $pdo)
{
    $stmt = $pdo->query("SELECT * FROM customers");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAdmins(PDO $pdo)
{
    $stmt = $pdo->query("SELECT a.id, c.name, c.email FROM admins a
                         JOIN customers c ON a.customer_id = c.id");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getContacts(PDO $pdo)
{
    $stmt = $pdo->query("SELECT * FROM contact");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function hasOrders(PDO $pdo, $product_id)
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM order_items WHERE product_id = :pid");
    $stmt->execute([':pid' => (int) $product_id]);
    $count = (int) $stmt->fetchColumn();
    return $count > 0;
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Promote User to Administrator
    if (isset($_POST['promote_user'])) {
        $user_id = (int) ($_POST['user_id'] ?? 0);

        // Check if already admin
        $check = $pdo->prepare("SELECT 1 FROM admins WHERE customer_id = :uid LIMIT 1");
        $check->execute([':uid' => $user_id]);
        if ($check->fetchColumn()) {
            header("Location: admin.php?error=" . urlencode("User is already an admin."));
            exit();
        } else {
            $ins = $pdo->prepare("INSERT INTO admins (customer_id) VALUES (:uid)");
            $ins->execute([':uid' => $user_id]);
            header("Location: admin.php?message=" . urlencode("User promoted to admin successfully."));
            exit();
        }
    }

    // Remove Admin
    if (isset($_POST['remove_admin'])) {
        $admin_id = (int) ($_POST['admin_id'] ?? 0);
        $del = $pdo->prepare("DELETE FROM admins WHERE id = :aid");
        $del->execute([':aid' => $admin_id]);
        header("Location: admin.php?message=" . urlencode("Admin removed successfully."));
        exit();
    }

    // Update Order
    if (isset($_POST['update_order'])) {
        $order_id = (int) ($_POST['order_id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $upd = $pdo->prepare("UPDATE orders SET status = :status WHERE id = :oid");
        $upd->execute([':status' => $status, ':oid' => $order_id]);
        header("Location: admin.php?message=" . urlencode("Order status updated successfully."));
        exit();
    }

    // Delete Product
    if (isset($_POST['delete_product'])) {
        $product_id = (int) ($_POST['product_id'] ?? 0);

        if (hasOrders($pdo, $product_id)) {
            header("Location: admin.php?error=" . urlencode("It is not possible to delete the product because it has already been ordered."));
            exit();
        } else {
            $del = $pdo->prepare("DELETE FROM products WHERE id = :pid");
            $del->execute([':pid' => $product_id]);
            header("Location: admin.php?message=" . urlencode("Product successfully deleted."));
            exit();
        }
    }
}

// Prepare page data
$order_status_filter = isset($_GET['status']) ? $_GET['status'] : null;
$products = getProducts($pdo);
$orders = getOrders($pdo, $order_status_filter);
$users = getUsers($pdo);
$admins = getAdmins($pdo);
$contacts = getContacts($pdo);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Mobile Store</title>
    <link rel="stylesheet" href="css/admin.css">
    <script>
        function confirmDelete() {
            return confirm("Are you sure you want to delete this item?");
        }

        // Prevent page scroll to top on form submission
        window.onload = function () {
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.onsubmit = function (e) {
                    e.preventDefault(); // Prevent default form submission
                    const formData = new FormData(form);
                    fetch(form.action || window.location.href, {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.text())
                        .then(data => {
                            document.body.innerHTML = data; // Replace the body content with response
                        })
                        .catch(error => console.error('Error:', error));
                };
            });
        };
    </script>
</head>

<body>
    <div class="admin-container">
        <h1>Admin Dashboard</h1>
        <h2>Welcome, <?= htmlspecialchars($username) ?>!</h2>

        <!-- Messages -->
        <?php if (isset($_GET['message'])): ?>
            <div class="message success"><?= htmlspecialchars($_GET['message']) ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div class="message error"><?= htmlspecialchars($_GET['error']) ?></div>
        <?php endif; ?>

        <!-- Product List -->
        <section class="section">
            <h2>Product List</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?= htmlspecialchars($product['id']) ?></td>
                            <td><?= htmlspecialchars($product['name']) ?></td>
                            <td>Rs.<?= number_format($product['price'], 2) ?></td>
                            <td><?= htmlspecialchars($product['stock']) ?></td>
                            <td>
                                <a href="edit_product.php?product_id=<?= (int) $product['id'] ?>" class="btn-edit">Edit</a>
                                <form method="POST" style="display:inline;" onsubmit="return confirmDelete();">
                                    <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                                    <button type="submit" name="delete_product" class="btn-delete">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <a href="add_product.php" class="btn-add">Add New Product</a>
        </section>

        <div class="divider"></div>

        <!-- Order List -->
        <section class="section">
            <h2>Order List</h2>
            <form method="GET" action="admin.php" class="form-filter">
                <label for="status">Filter by Status:</label>
                <select name="status" id="status">
                    <option value="">All</option>
                    <option value="Pending" <?= $order_status_filter === 'Pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="Completed" <?= $order_status_filter === 'Completed' ? 'selected' : '' ?>>Completed
                    </option>
                    <option value="Canceled" <?= $order_status_filter === 'Canceled' ? 'selected' : '' ?>>Canceled</option>
                </select>
                <button type="submit" class="btn-filter">Filter</button>
            </form>

            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Order Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?= htmlspecialchars($order['id']) ?></td>
                            <td><?= htmlspecialchars($order['customer_name']) ?></td>
                            <td><?= htmlspecialchars($order['order_date']) ?></td>
                            <td><?= htmlspecialchars($order['status']) ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                                    <select name="status">
                                        <option value="Pending" <?= $order['status'] === 'Pending' ? 'selected' : '' ?>>Pending
                                        </option>
                                        <option value="Completed" <?= $order['status'] === 'Completed' ? 'selected' : '' ?>>
                                            Completed</option>
                                        <option value="Canceled" <?= $order['status'] === 'Canceled' ? 'selected' : '' ?>>
                                            Canceled</option>
                                    </select>
                                    <button type="submit" name="update_order" class="btn-update">Update</button>
                                </form>
                                <a href="view_order.php?order_id=<?= (int) $order['id'] ?>" class="btn-view">View Details</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <div class="divider"></div>

        <!-- User Management -->
        <section class="section">
            <h2>User Management</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['id']) ?></td>
                            <td><?= htmlspecialchars($user['name']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
                                    <button type="submit" name="promote_user" class="btn-promote">Promote to Admin</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <div class="divider"></div>

        <!-- Admin Management -->
        <section class="section">
            <h2>Admin Management</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($admins as $admin): ?>
                        <tr>
                            <td><?= htmlspecialchars($admin['id']) ?></td>
                            <td><?= htmlspecialchars($admin['name']) ?></td>
                            <td><?= htmlspecialchars($admin['email']) ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="admin_id" value="<?= (int) $admin['id'] ?>">
                                    <button type="submit" name="remove_admin" class="btn-remove">Remove Admin</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <div class="divider"></div>

        <!-- Contact Messages -->
        <section class="section">
            <h2>Contact Messages</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Message</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($contacts as $contact): ?>
                        <tr>
                            <td><?= htmlspecialchars($contact['id']) ?></td>
                            <td><?= htmlspecialchars($contact['name']) ?></td>
                            <td><?= htmlspecialchars($contact['email']) ?></td>
                            <td><?= htmlspecialchars($contact['message']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </div>
</body>

</html>