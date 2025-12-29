<?php
require_once 'db.php';
include 'menu.php'; // assumes this starts the session if needed

$error = null;

// Read filters
$search      = trim($_GET['search'] ?? '');
$categoryId  = 0;
$type        = $_GET['type'] ?? '';
$page        = 1;
$perPage     = 9; 

// Validate category (from dropdown or manual URL)
if (isset($_GET['category']) && ctype_digit((string)$_GET['category'])) {
    $categoryId = (int) $_GET['category'];
}

// Validate type (from menu: smartphone / tablet / accessory)
$allowedTypes = ['smartphone', 'tablet', 'accessory'];
if (!in_array($type, $allowedTypes, true)) {
    $type = '';
}

// Validate page number
if (isset($_GET['page']) && ctype_digit((string)$_GET['page']) && (int)$_GET['page'] > 0) {
    $page = (int) $_GET['page'];
}

try {
    // -------------------------------
    // MAIN PRODUCT LIST QUERY
    // -------------------------------
    // We always join categories so we can filter by type and show category info
    $sqlBase = "
        FROM products p
        JOIN categories c ON p.category_id = c.id
        WHERE 1=1
    ";
    $params = [];

    if ($search !== '') {
        $sqlBase .= " AND p.name LIKE :search";
        $params[':search'] = '%' . $search . '%';
    }

    if ($categoryId > 0) {
        $sqlBase .= " AND c.id = :cid";
        $params[':cid'] = $categoryId;
    }

    if ($type !== '') {
        $sqlBase .= " AND c.type = :ptype";
        $params[':ptype'] = $type;
    }

    // Pagination
    $offset = ($page - 1) * $perPage;

    // Final product query
    $sqlProducts = "
        SELECT SQL_CALC_FOUND_ROWS
               p.*,
               c.name AS category_name,
               c.type AS category_type
        $sqlBase
        ORDER BY p.id DESC
        LIMIT :limit OFFSET :offset
    ";

    $prodStmt = $pdo->prepare($sqlProducts);

    // Bind dynamic params
    foreach ($params as $key => $value) {
        $prodStmt->bindValue($key, $value);
    }
    $prodStmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
    $prodStmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

    $prodStmt->execute();
    $products = $prodStmt->fetchAll(PDO::FETCH_ASSOC);

    // Total rows for pagination
    $totalRows  = (int) $pdo->query("SELECT FOUND_ROWS()")->fetchColumn();
    $totalPages = $totalRows > 0 ? (int) ceil($totalRows / $perPage) : 1;

    // -------------------------------
    // BEST SELLERS
    // -------------------------------
    $bestSellersSql = "
        SELECT p.*,
           COALESCE(SUM(oi.quantity), 0) AS total_sold
        FROM products p
        LEFT JOIN order_items oi ON p.id = oi.product_id
        GROUP BY p.id
        HAVING total_sold > 0
        ORDER BY total_sold DESC
        LIMIT 5
    ";
    $bestSellersStmt = $pdo->query($bestSellersSql);
    $best_sellers = $bestSellersStmt->fetchAll(PDO::FETCH_ASSOC);

    // -------------------------------
    // MOST COMMENTED
    // -------------------------------
    $mostCommentedSql = "
        SELECT p.*,
               COUNT(c.id) AS total_comments
        FROM products p
        LEFT JOIN comments c ON p.id = c.product_id
        GROUP BY p.id
        HAVING total_comments > 0
        ORDER BY total_comments DESC
        LIMIT 5
    ";
    $mostCommentedStmt = $pdo->query($mostCommentedSql);
    $most_commented = $mostCommentedStmt->fetchAll(PDO::FETCH_ASSOC);

    // -------------------------------
    // CATEGORIES FOR DROPDOWN
    // -------------------------------
    $catStmt = $pdo->query("SELECT id, name FROM categories ORDER BY name");
    $categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // You can log $e->getMessage() to a file
    $error          = "A database error occurred. Please try again later.";
    $products       = [];
    $best_sellers   = [];
    $most_commented = [];
    $categories     = [];
    $totalRows      = 0;
    $totalPages     = 1;
    $page           = 1;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop - Mobile Store</title>
    <link rel="stylesheet" href="css/shop.css">
    <link rel="stylesheet" href="css/index.css">
</head>
<body>
    <div class="container">
        <?php if (!empty($error)): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <!-- SEARCH + CATEGORY FILTER (TYPE COMES FROM MENU/LINK) -->
        <form method="GET" action="shop.php" class="search-form" role="search">
            <input
                type="text"
                name="search"
                placeholder="Search by name..."
                value="<?= htmlspecialchars($search) ?>"
            >

            <!-- preserve type when searching/filtering -->
            <?php if ($type !== ''): ?>
                <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">
            <?php endif; ?>

            <select name="category">
                <option value="0" style="color: #999;" >All Categories</option>
                <?php foreach ($categories as $category): ?>
                    <option
                        value="<?= (int)$category['id'] ?>"
                        <?= $categoryId === (int)$category['id'] ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars($category['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="btn">Search</button>
        </form>

        <!-- MAIN PRODUCT GRID -->
        <div class="product-grid">
            <?php if (!empty($products)): ?>
                <?php foreach ($products as $product): ?>
                    <?php
                        $fileName = $product['image'] ?: '';
                        $filePath = __DIR__ . '/images/' . $fileName;
                        $imgUrl   = 'images/' . $fileName;

                        if (!$fileName || !is_file($filePath)) {
                            $imgUrl = 'images/placeholder.png'; // make sure this exists
                        }
                    ?>
                    <div class="product-card">
                        <img src="<?= htmlspecialchars($imgUrl) ?>"
                             alt="<?= htmlspecialchars($product['name']) ?>">
                        <h3><?= htmlspecialchars($product['name']) ?></h3>
                        <?php if (!empty($product['category_type'])): ?>
                            <p class="product-type">
                                Type: <?= htmlspecialchars(ucfirst($product['category_type'])) ?>
                            </p>
                        <?php endif; ?>
                        <p>Price: Rs. <?= number_format((float)$product['price'], 2) ?></p>
                        <a href="product.php?id=<?= (int)$product['id'] ?>" class="btn">View Details</a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No products found.</p>
            <?php endif; ?>
        </div>

        <!-- PAGINATION -->
        <?php if ($totalRows > 0 && $totalPages > 1): ?>
            <nav class="pagination" aria-label="Product pages">
                <?php
                    // build base query params except page
                    $baseParams = $_GET;
                    unset($baseParams['page']);
                ?>
                <?php if ($page > 1): ?>
                    <a href="?<?= htmlspecialchars(http_build_query(array_merge($baseParams, ['page' => $page - 1]))) ?>">
                        &laquo; Prev
                    </a>
                <?php endif; ?>

                <span>Page <?= $page ?> of <?= $totalPages ?></span>

                <?php if ($page < $totalPages): ?>
                    <a href="?<?= htmlspecialchars(http_build_query(array_merge($baseParams, ['page' => $page + 1]))) ?>">
                        Next &raquo;
                    </a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>

        <!-- BEST SELLERS -->
        <h2>Best Sellers</h2>
        <div class="product-grid">
            <?php if (!empty($best_sellers)): ?>
                <?php foreach ($best_sellers as $product): ?>
                    <?php
                        $fileName = $product['image'] ?: '';
                        $filePath = __DIR__ . '/images/' . $fileName;
                        $imgUrl   = 'images/' . $fileName;

                        if (!$fileName || !is_file($filePath)) {
                            $imgUrl = 'images/placeholder.png';
                        }
                    ?>
                    <div class="product-card">
                        <img src="<?= htmlspecialchars($imgUrl) ?>"
                             alt="<?= htmlspecialchars($product['name']) ?>">
                        <h3><?= htmlspecialchars($product['name']) ?></h3>
                        <p>Total Sold: <?= (int)$product['total_sold'] ?></p>
                        <p>Price: Rs. <?= number_format((float)$product['price'], 2) ?></p>
                        <a href="product.php?id=<?= (int)$product['id'] ?>" class="btn">View Details</a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No best sellers yet.</p>
            <?php endif; ?>
        </div>

        <!-- MOST COMMENTED -->
        <h2>Most Commented</h2>
        <div class="product-grid">
            <?php if (!empty($most_commented)): ?>
                <?php foreach ($most_commented as $product): ?>
                    <?php
                        $fileName = $product['image'] ?: '';
                        $filePath = __DIR__ . '/images/' . $fileName;
                        $imgUrl   = 'images/' . $fileName;

                        if (!$fileName || !is_file($filePath)) {
                            $imgUrl = 'images/placeholder.png';
                        }
                    ?>
                    <div class="product-card">
                        <img src="<?= htmlspecialchars($imgUrl) ?>"
                             alt="<?= htmlspecialchars($product['name']) ?>">
                        <h3><?= htmlspecialchars($product['name']) ?></h3>
                        <p>Total Comments: <?= (int)$product['total_comments'] ?></p>
                        <p>Price: Rs. <?= number_format((float)$product['price'], 2) ?></p>
                        <a href="product.php?id=<?= (int)$product['id'] ?>" class="btn">View Details</a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No commented products yet.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
