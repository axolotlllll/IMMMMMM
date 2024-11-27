<?php
require_once 'connection.php';

// Initialize search variables
$search = $_POST['search'] ?? '';
$category_filter = $_POST['category'] ?? '';
$date_from = $_POST['date_from'] ?? '';
$date_to = $_POST['date_to'] ?? '';

$db = new Database();
$conn = $db->openConnection();

// Base query
$query = "
    SELECT p.product_id, p.product_name, p.description, p.price, c.category_name, p.quantity, p.image_path, p.date_created
    FROM products p
    JOIN categories c ON p.category_id = c.category_id
    WHERE 1=1
";

// Add search conditions
if ($search) {
    $query .= " AND p.product_name LIKE :search";
}

if ($category_filter) {
    $query .= " AND p.category_id = :category";
}

if ($date_from && $date_to) {
    $query .= " AND p.date_created BETWEEN :date_from AND :date_to";
}

$stmt = $conn->prepare($query);

// Bind parameters
if ($search) {
    $stmt->bindValue(':search', "%$search%");
}
if ($category_filter) {
    $stmt->bindValue(':category', $category_filter);
}
if ($date_from && $date_to) {
    $stmt->bindValue(':date_from', $date_from);
    $stmt->bindValue(':date_to', $date_to);
}

$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$db->closeConnection();

// Output product cards
foreach ($products as $product): 
    $image_path = !empty($product['image_path']) ? $product['image_path'] : 'product_images/default.jpg';
?>
    <div class="product-card">
        <div class="product-image">
            <img src="<?= htmlspecialchars($image_path) ?>" alt="<?= htmlspecialchars($product['product_name']) ?>">
        </div>
        <div class="product-details">
            <h3><?= htmlspecialchars($product['product_name']) ?></h3>
            <p class="category"><?= htmlspecialchars($product['category_name']) ?></p>
            <p class="description"><?= htmlspecialchars($product['description']) ?></p>
            <p class="price">₱<?= number_format($product['price'], 2) ?></p>
            <p class="stock">Stock: <?= htmlspecialchars($product['quantity']) ?></p>
            <div class="product-buttons">
                <?php if ($product['quantity'] > 0): ?>
                    <button class="buy-now-btn" onclick="openBuyNowModal(<?= $product['product_id'] ?>, '<?= htmlspecialchars(addslashes($product['product_name'])) ?>', <?= $product['price'] ?>, <?= $product['quantity'] ?>)">
                        <i class="fas fa-shopping-cart"></i> Buy Now
                    </button>
                <?php else: ?>
                    <button class="out-of-stock-btn" disabled>
                        <i class="fas fa-times-circle"></i> Out of Stock
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endforeach;

// If no products found
if (empty($products)): ?>
    <div class="no-products">
        <p>No products found matching your search criteria.</p>
    </div>
<?php endif; ?>
