<?php
session_start();

// Check if the user is logged in and if they are an admin
if (!isset($_SESSION['user']) || $_SESSION['user_type'] != 1) {
    // Redirect to login if the user is not logged in or not an admin
    header("Location: login.php");
    exit();
}
require_once 'connection.php';

$db = new Database();
$conn = $db->openConnection(); 

$search = '';
$category_filter = '';
$availability_filter = '';
$date_from = '';
$date_to = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $search = $_POST['search'] ?? '';
    $category_filter = $_POST['category'] ?? '';
    $availability_filter = $_POST['availability'] ?? '';
    $date_from = $_POST['date_from'] ?? '';
    $date_to = $_POST['date_to'] ?? '';
}

$query = "SELECT p.*, c.category_name FROM products p JOIN categories c ON p.category_id = c.category_id WHERE 1=1";

if ($search) {
    $query .= " AND p.product_name LIKE :search";
}

if ($category_filter) {
    $query .= " AND p.category_id = :category";
}

if ($availability_filter) {
    $query .= $availability_filter == 'in_stock' ? " AND p.quantity > 0" : " AND p.quantity = 0";
}

if ($date_from && $date_to) {
    $query .= " AND p.date_created BETWEEN :date_from AND :date_to";
}

$stmt = $conn->prepare($query);

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
$products = $stmt->fetchAll(PDO::FETCH_OBJ);

$category_query = $conn->prepare("SELECT category_id, category_name FROM categories");
$category_query->execute();
$categories = $category_query->fetchAll(PDO::FETCH_OBJ);

$db->closeConnection();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AADMIN KUAN</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin_dashboard_styles.css">
</head>
<body>
    <nav class="admin-nav">
        <div class="admin-title">
            <i class="fas fa-shield-alt"></i>
            Admin Dashboard
        </div>
        <div class="nav-controls">
            <a href="admin_orders.php" class="admin-btn">
                <i class="fas fa-shopping-bag"></i>
                View Orders
            </a>
            <a href="logout.php" class="admin-btn danger">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>
        </div>
    </nav>

    <div class="main-content">
        <div class="search-section">
            <form id="filterForm" method="POST" class="filter-form">
                <div class="form-group">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Search products..." 
                               value="<?= htmlspecialchars($search) ?>" onkeyup="debounceSearch()">
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-tags"></i></span>
                        <select name="category" class="form-control" onchange="this.form.submit()">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category->category_id ?>" 
                                    <?= $category_filter == $category->category_id ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category->category_name) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-box"></i></span>
                        <select name="availability" class="form-control" onchange="this.form.submit()">
                            <option value="">All Availability</option>
                            <option value="in_stock" <?= $availability_filter == 'in_stock' ? 'selected' : '' ?>>In Stock</option>
                            <option value="out_of_stock" <?= $availability_filter == 'out_of_stock' ? 'selected' : '' ?>>Out of Stock</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                        <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($date_from) ?>" onchange="this.form.submit()">
                    </div>
                </div>

                <div class="form-group">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                        <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($date_to) ?>" onchange="this.form.submit()">
                    </div>
                </div>
            </form>
        </div>

        <div class="action-buttons">
            <button type="button" class="admin-btn" data-bs-toggle="modal" data-bs-target="#addProductModal">
                <i class="fas fa-plus"></i> Add New Product
            </button>
            <button type="button" class="admin-btn" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                <i class="fas fa-folder-plus"></i> Add Category
            </button>
        </div>

        <div class="product-grid">
            <?php foreach ($products as $product): ?>
                <div class="product-card">
                    <div class="product-header">
                        <h3 class="product-title"><?= htmlspecialchars($product->product_name) ?></h3>
                        <span class="product-category">
                            <i class="fas fa-tag"></i>
                            <?= htmlspecialchars($product->category_name) ?>
                        </span>
                    </div>
                    <div class="product-info">
                        <p><i class="fas fa-dollar-sign"></i> Price: $<?= number_format($product->price, 2) ?></p>
                        <p>
                            <i class="fas fa-boxes"></i> Quantity: <?= htmlspecialchars($product->quantity) ?>
                            <span class="stock-badge <?= $product->quantity > 0 ? 'in-stock' : 'out-of-stock' ?>">
                                <i class="fas <?= $product->quantity > 0 ? 'fa-check-circle' : 'fa-times-circle' ?>"></i>
                                <?= $product->quantity > 0 ? 'In Stock' : 'Out of Stock' ?>
                            </span>
                        </p>
                    </div>
                    <div class="product-actions">
                        <button class="action-btn edit" data-bs-toggle="modal" data-bs-target="#editProductModal<?= $product->product_id ?>">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <form action="delete_product.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this product?');">
                            <input type="hidden" name="product_id" value="<?= $product->product_id ?>">
                            <button type="submit" class="action-btn delete">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addProductModalLabel">Add New Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="add_product.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Product Name</label>
                            <input type="text" name="name" class="form-control" id="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <input type="text" name="description" class="form-control" id="description" required>
                        </div>
                        <div class="mb-3">
                            <label for="price" class="form-label">Price</label>
                            <input type="number" name="price" class="form-control" id="price" required step="0.01">
                        </div>
                        <div class="mb-3">
                            <label for="category" class="form-label">Category</label>
                            <select name="category_id" class="form-select" id="category" required>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category->category_id ?>">
                                        <?= htmlspecialchars($category->category_name) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="quantity" class="form-label">Quantity</label>
                            <input type="number" name="quantity" class="form-control" id="quantity" required min="0">
                        </div>
                        <div class="mb-3">
                            <label for="product_image" class="form-label">Product Image</label>
                            <input type="file" name="product_image" class="form-control" id="product_image" accept="image/*">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCategoryModalLabel">Add New Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="add_category.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="category_name" class="form-label">Category Name</label>
                            <input type="text" name="category_name" class="form-control" id="category_name" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <?php foreach ($products as $product): ?>
        <div class="modal fade" id="editProductModal<?= $product->product_id ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Product</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="edit_product.php" method="POST" enctype="multipart/form-data">
                        <div class="modal-body">
                            <input type="hidden" name="product_id" value="<?= $product->product_id ?>">

                            <div class="mb-3">
                                <label for="name<?= $product->product_id ?>" class="form-label">Product Name</label>
                                <input type="text" name="name" class="form-control" id="name<?= $product->product_id ?>" 
                                    value="<?= htmlspecialchars($product->product_name) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="description<?= $product->product_id ?>" class="form-label">Description</label>
                                <input type="text" name="description" class="form-control" id="description<?= $product->product_id ?>" 
                                    value="<?= htmlspecialchars($product->description) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="price<?= $product->product_id ?>" class="form-label">Price</label>
                                <input type="number" name="price" class="form-control" id="price<?= $product->product_id ?>" 
                                    value="<?= htmlspecialchars($product->price) ?>" required step="0.01">
                            </div>

                            <div class="mb-3">
                                <label for="category<?= $product->product_id ?>" class="form-label">Category</label>
                                <select name="category_id" class="form-select" id="category<?= $product->product_id ?>" required>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= $category->category_id ?>" 
                                            <?= ($category->category_id == $product->category_id) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($category->category_name) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="quantity<?= $product->product_id ?>" class="form-label">Quantity</label>
                                <input type="number" name="quantity" class="form-control" id="quantity<?= $product->product_id ?>" 
                                    value="<?= htmlspecialchars($product->quantity) ?>" required min="0">
                            </div>

                            <div class="mb-3">
                                <label for="product_image<?= $product->product_id ?>" class="form-label">Product Image</label>
                                <?php if (!empty($product->image_path)): ?>
                                    <div class="mb-2">
                                        <img src="<?= htmlspecialchars($product->image_path) ?>" alt="Current product image" 
                                            style="max-width: 200px; height: auto;" class="img-thumbnail">
                                    </div>
                                <?php endif; ?>
                                <input type="file" name="product_image" class="form-control" 
                                    id="product_image<?= $product->product_id ?>" accept="image/*">
                                <small class="form-text text-muted">Leave empty to keep current image</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function debounceSearch() {
            clearTimeout(window.searchTimeout);
            window.searchTimeout = setTimeout(function () {
                document.getElementById('filterForm').submit();
            }, 500);
        }
    </script>
</body>
</html>
