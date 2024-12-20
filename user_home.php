<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$username = isset($_SESSION['user']) ? htmlspecialchars($_SESSION['user']) : 'Guest';

$greetings = [
    "Maayong pagbalik, <span class='username-highlight'>$username</span> ang Gamhanan.",
    "Ah, <span class='username-highlight'>$username</span> ang Mahimayaon mipauli na!",
    "Pagdayeg, <span class='username-highlight'>$username</span> ang Maalamon.",
    "Himaya kanimo, <span class='username-highlight'>$username</span> ang Isug!",
    "Maayong pagbalik, <span class='username-highlight'>$username</span> ang Banggiitan.",
    "Ang gingharian nagpasalamat sa imong pagbalik, <span class='username-highlight'>$username</span> ang Mahalangdon.",
    "Usa ka halangdon nga pagbalik, <span class='username-highlight'>$username</span> ang Kusgan!"
];

$greeting = $greetings[array_rand($greetings)];

require_once 'connection.php';
// Fetch products from the database
$db = new Database();
$conn = $db->openConnection();

// Initialize search variables
$search = '';
$category_filter = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $search = $_POST['search'] ?? '';
    $category_filter = $_POST['category'] ?? '';
}

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

$stmt = $conn->prepare($query);

// Bind parameters
if ($search) {
    $stmt->bindValue(':search', "%$search%");
}
if ($category_filter) {
    $stmt->bindValue(':category', $category_filter);
}

$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch categories for the filter
$categoryQuery = $conn->query("SELECT * FROM categories");
$categories = $categoryQuery->fetchAll(PDO::FETCH_ASSOC);

$db->closeConnection();

$default_image = 'product_images/default.jpg';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>USERR KUAN</title>
    <link rel="stylesheet" href="user_home_styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Add jQuery UI for autocomplete -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <style>
        /* Search Form Styles */
        #searchForm {
            max-width: 800px;
            margin: 30px 0 40px 40px;
            padding: 0;
        }

        .search-group {
            display: grid;
            grid-template-columns: minmax(250px, 2fr) minmax(150px, 1fr);
            gap: 200px;
            align-items: center;
        }

        /* Input Wrappers */
        .search-input-wrapper,
        .filter-wrapper {
            position: relative;
            width: 100%;
        }

        /* Base Input Styles */
        .search-input,
        .category-select {
            width: 100%;
            padding: 12px 40px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(229, 231, 235, 0.3);
            border-radius: 25px;
            font-size: 0.95rem;
            color: #4B5563;
            transition: all 0.3s ease;
            height: 45px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1), 
                       0 1px 3px rgba(0, 0, 0, 0.08);
        }

        .search-input::placeholder {
            color: #9CA3AF;
        }

        /* Focus States */
        .search-input:focus,
        .category-select:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(99, 102, 241, 0.5);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2),
                       0 2px 4px rgba(0, 0, 0, 0.1);
        }

        /* Hover States */
        .search-input:hover,
        .category-select:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(229, 231, 235, 0.4);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.12),
                       0 2px 4px rgba(0, 0, 0, 0.08);
        }

        /* Icons */
        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #9CA3AF;
            font-size: 1rem;
            pointer-events: none;
            transition: color 0.2s ease;
        }

        /* Icon Color Change on Focus */
        .search-input:focus ~ .input-icon,
        .category-select:focus ~ .input-icon {
            color: #6366F1;
        }

        /* Select Specific Styles */
        .category-select {
            appearance: none;
            padding-right: 40px;
            cursor: pointer;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%239CA3AF'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 16px;
        }

        /* Responsive Design */
        @media (max-width: 640px) {
            .search-group {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            #searchForm {
                padding: 0 20px;
                margin: 20px auto 30px;
            }
        }
    </style>
    <script>
    // Buy Now Modal Functions
    function openBuyNowModal(productId, productName, price, maxQty) {
        document.getElementById('buyNowProductId').value = productId;
        document.getElementById('buyNowProductName').textContent = productName;
        document.getElementById('buyNowPrice').textContent = '₱' + parseFloat(price).toFixed(2);
        document.getElementById('maxQuantity').value = maxQty;
        document.getElementById('buyNowQuantity').max = maxQty;
        document.getElementById('buyNowFormQuantity').value = 1;
        updateBuyNowTotal();
        document.getElementById('buyNowModal').style.display = 'flex';
    }

    function closeBuyNowModal() {
        document.getElementById('buyNowModal').style.display = 'none';
    }

    function updateBuyNowTotal() {
        const quantity = parseInt(document.getElementById('buyNowQuantity').value);
        const price = parseFloat(document.getElementById('buyNowPrice').textContent.replace('₱', ''));
        const maxQty = parseInt(document.getElementById('maxQuantity').value);
        
        // Validate quantity
        if (quantity > maxQty) {
            document.getElementById('buyNowQuantity').value = maxQty;
            document.getElementById('buyNowFormQuantity').value = maxQty;
            updateBuyNowTotal();
            return;
        }
        
        if (quantity < 1) {
            document.getElementById('buyNowQuantity').value = 1;
            document.getElementById('buyNowFormQuantity').value = 1;
            updateBuyNowTotal();
            return;
        }
        
        document.getElementById('buyNowFormQuantity').value = quantity;
        document.getElementById('buyNowTotal').textContent = '₱' + (quantity * price).toFixed(2);
    }

    function submitBuyNow() {
        const quantity = document.getElementById('buyNowQuantity').value;
        document.getElementById('buyNowFormQuantity').value = quantity;
        document.getElementById('buyNowForm').submit();
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        const buyNowModal = document.getElementById('buyNowModal');
        const cartModal = document.getElementById('cartModal');
        if (event.target == buyNowModal) {
            buyNowModal.style.display = 'none';
        }
        if (event.target == cartModal) {
            cartModal.style.display = 'none';
        }
    }

    $(document).ready(function() {
        // Function to perform the search
        function performSearch() {
            const formData = $('#searchForm').serialize();
            
            $.ajax({
                url: 'search_products.php',
                type: 'POST',
                data: formData,
                success: function(response) {
                    $('.product-container').html(response);
                }
            });
        }

        // Debounce function to limit how often a function can fire
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        // Debounced search function
        const debouncedSearch = debounce(performSearch, 300);

        // Handle search input changes
        $('#search').on('input', debouncedSearch);

        // Handle category select changes
        $('.category-select').on('change', performSearch);

        // Autocomplete functionality
        $("#search").autocomplete({
            source: function(request, response) {
                $.ajax({
                    url: "fetch_products.php",
                    dataType: "json",
                    data: {
                        term: request.term
                    },
                    success: function(data) {
                        response(data);
                    }
                });
            },
            minLength: 2,
            select: function(event, ui) {
                $(this).val(ui.item.value);
                performSearch();
            }
        });

        // Prevent form submission
        $('#searchForm').on('submit', function(e) {
            e.preventDefault();
            performSearch();
        });
    });
    </script>
</head>
<body>

<nav class="user-nav">
    <div class="user-title">
        <i class="fas fa-shopping-cart"></i>
        User Dashboard
    </div>
    <div class="nav-controls">
        <a href="cart.php" class="user-btn" id="viewCartBtn">
            <i class="fas fa-shopping-cart"></i>
            View Cart
        </a>
        <a href="view_orders.php" class="user-btn">
            <i class="fas fa-list"></i>
            My Orders
        </a>
        <a href="logout.php" class="user-btn danger">
            <i class="fas fa-sign-out-alt"></i>
            Logout
        </a>
    </div>
</nav>

<script>
document.getElementById('viewCartBtn').addEventListener('click', function(e) {
    e.preventDefault(); // Prevent default link behavior
    openCartModal(); // Call the existing cart modal function
});
</script>

<div class="content-wrapper">
    <h2><?= $greeting ?></h2>
    
    <!-- Search Form -->
    <form id="searchForm" method="POST">
        <div class="search-group">
            <div class="search-input-wrapper">
                <input type="text" 
                       id="search" 
                       name="search" 
                       placeholder="Search products..." 
                       value="<?= htmlspecialchars($search) ?>" 
                       class="search-input"
                       autocomplete="off">
                <i class="fas fa-search input-icon"></i>
            </div>
            
            <div class="filter-wrapper">
                <select name="category" class="category-select">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= $category['category_id'] ?>" <?= $category_filter == $category['category_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($category['category_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <i class="fas fa-tag input-icon"></i>
            </div>
        </div>
    </form>

    <!-- Buy Now Modal -->
    <div id="buyNowModal" class="buy-now-modal">
        <div class="buy-now-modal-content">
            <div class="modal-header">
                <h2>Buy Now</h2>
                <button class="close-modal-btn" onclick="closeBuyNowModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="modal-body">
                <div class="buy-now-details">
                    <p>
                        <label>Product:</label>
                        <span id="buyNowProductName"></span>
                    </p>
                    <p>
                        <label>Price:</label>
                        <span id="buyNowPrice"></span>
                    </p>
                    <div class="buy-now-quantity">
                        <label>Quantity:</label>
                        <div class="quantity-container">
                            <button type="button" class="quantity-btn" onclick="if(this.nextElementSibling.value>1)this.nextElementSibling.value--;updateBuyNowTotal();">
                                <i class="fas fa-minus"></i>
                            </button>
                            <input type="number" id="buyNowQuantity" value="1" min="1" class="quantity-input" onchange="updateBuyNowTotal()">
                            <button type="button" class="quantity-btn" onclick="if(this.previousElementSibling.value < document.getElementById('maxQuantity').value)this.previousElementSibling.value++;updateBuyNowTotal();">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    <p class="buy-now-total">
                        <label>Total:</label>
                        <span id="buyNowTotal"></span>
                    </p>
                </div>
            </div>

            <div class="modal-footer">
                <form id="buyNowForm" action="checkout.php" method="POST">
                    <input type="hidden" id="buyNowProductId" name="product_id">
                    <input type="hidden" id="buyNowFormQuantity" name="quantity">
                    <input type="hidden" id="maxQuantity">
                    <input type="hidden" name="buy_now" value="1">
                    <button type="button" class="btn-secondary" onclick="closeBuyNowModal()">Cancel</button>
                    <button type="submit" class="btn-primary" onclick="submitBuyNow()">Confirm Purchase</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Display products -->
    <div class="product-container">
        <?php if ($products): ?>
            <?php foreach ($products as $product): ?>
                <div class="product-card">
                    <div class="product-image">
                        <img src="<?= htmlspecialchars($product['image_path'] ?? $default_image) ?>" alt="<?= htmlspecialchars($product['product_name']) ?>">
                    </div>
                    <div class="product-details">
                        <h3>
                            <i class="fas fa-tag"></i>
                            <?= htmlspecialchars($product['product_name']) ?>
                        </h3>
                        <p class="product-description">
                            <i class="fas fa-info-circle"></i>
                            <?= htmlspecialchars($product['description']) ?>
                        </p>
                        <p class="product-price">
                            <i class="fas fa-dollar-sign"></i>
                            ₱<?= number_format($product['price'], 2) ?>
                        </p>
                        <p class="product-category">
                            <i class="fas fa-folder"></i>
                            <?= htmlspecialchars($product['category_name']) ?>
                        </p>
                        <p class="product-quantity <?= $product['quantity'] > 0 ? 'in-stock' : 'out-of-stock' ?>">
                            <?php if ($product['quantity'] > 0): ?>
                                <i class="fas fa-check-circle"></i>
                                In Stock: <?= $product['quantity'] ?>
                            <?php else: ?>
                                <i class="fas fa-times-circle"></i>
                                Out of Stock
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="product-buttons">
                        <?php if ($product['quantity'] > 0): ?>
                            <form action="addtocart.php" method="POST">
                                <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
                                <div class="quantity-container">
                                    <button type="button" class="quantity-btn" onclick="decrementProductQuantity(this)">-</button>
                                    <input type="number" name="quantity" value="1" min="1" max="<?= $product['quantity'] ?>" class="quantity-input">
                                    <button type="button" class="quantity-btn" onclick="incrementProductQuantity(this, <?= $product['quantity'] ?>)">+</button>
                                </div>
                                <button type="submit" name="add_to_cart" class="btn-primary">
                                    <i class="fas fa-cart-plus"></i>
                                    Add to Cart
                                </button>
                            </form>
                            
                            <button onclick="openBuyNowModal(
                                '<?= $product['product_id'] ?>', 
                                '<?= htmlspecialchars($product['product_name']) ?>', 
                                '<?= $product['price'] ?>',
                                '<?= $product['quantity'] ?>'
                            )" class="btn-secondary">
                                <i class="fas fa-shopping-bag"></i>
                                Buy Now
                            </button>
                        <?php else: ?>
                            <div class="out-of-stock-message">
                                <i class="fas fa-ban"></i>
                                Product Unavailable
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No products available at the moment.</p>
        <?php endif; ?>
    </div>
    <!-- Cart Button


    <!-- Cart Modal -->
    <div id="cartModal" class="cart-modal">
        <div class="cart-modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-shopping-cart"></i> Your Cart</h2>
                <button class="modal-close-btn" onclick="closeCartModal()">
                    <i class="fas fa-times"></i> Close
                </button>
            </div>
            <div id="cartItems">
                <?php if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])): ?>
                    <div class="cart-labels">
                        <div><i class="fas fa-shopping-bag"></i>Product</div>
                        <div><i class="fas fa-dollar-sign"></i>Price</div>
                        <div><i class="fas fa-sort-numeric-up"></i>Quantity</div>
                        <div><i class="fas fa-receipt"></i>Total</div>
                        <div><i class="fas fa-cogs"></i>Actions</div>
                    </div>
                    <!-- Cart items display section -->
    <?php foreach ($_SESSION['cart'] as $item): ?>
        <div class="cart-item" id="cart-item-<?php echo $item['id']; ?>">
            <div><?php echo htmlspecialchars($item['product_name']); ?></div>
            <div>₱<?php echo number_format($item['price'], 2); ?></div>
            <div class="quantity-container">
                <div class="quantity-controls">
                    <button class="quantity-btn quantity-minus" onclick="decrementQuantity(this)">
                        <i class="fas fa-minus"></i>
                    </button>
                    <input type="number" value="<?php echo $item['quantity']; ?>" 
                           min="1" class="quantity-input"
                           onchange="handleQuantityChange(<?php echo $item['id']; ?>, this)">
                    <button class="quantity-btn quantity-plus" onclick="incrementQuantity(this)">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>
            <div>₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></div>
            <div class="cart-actions">
                <button type="button" class="update-btn" onclick="updateCartItem(<?php echo $item['id']; ?>, this.parentElement.parentElement.querySelector('.quantity-input').value)">
                    <i class="fas fa-sync-alt"></i> Update
                </button>
                <button type="button" class="remove-btn" onclick="removeCartItem(<?php echo $item['id']; ?>)">
                    <i class="fas fa-trash-alt"></i> Remove
                </button>
            </div>
        </div>
    <?php endforeach; ?>
                    <div class="cart-total">
                        ₱<span id="cart-total"><?php
                            $total = 0;
                            if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
                                foreach ($_SESSION['cart'] as $item) {
                                    $total += $item['price'] * $item['quantity'];
                                }
                            }
                            echo number_format($total, 2);
                        ?></span>
                        <form action="checkout.php" method="POST" style="display: inline; margin-left: 20px;">
                            <button type="submit" name="checkout" class="checkout-btn">Checkout</button>
                        </form>
                    </div>
                <?php else: ?>
                    <p>Your cart is empty</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        let updateTimeout;

    function handleQuantityChange(cartId, input) {
        let quantity = parseInt(input.value);
        if (isNaN(quantity) || quantity < 1) {
            alert('Please enter a valid quantity');
            input.value = 1;
        }
    }

    function updateCartItem(cartId, quantity) {
        // Validate quantity
        quantity = parseInt(quantity);
        if (isNaN(quantity) || quantity < 1) {
            alert('Please enter a valid quantity');
            return;
        }

        fetch('update_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `cart_id=${cartId}&quantity=${quantity}&update_quantity=1`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload(); // Refresh page after successful update
            } else {
                alert(data.message || 'Error updating cart');
                if (data.message && data.message.includes('login')) {
                    window.location.reload();
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating cart. Please try again.');
        });
    }

    function removeCartItem(cartId) {
        if (!confirm('Are you sure you want to remove this item?')) return;

        fetch('remove_from_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `cart_id=${cartId}&remove_from_cart=1`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload(); // Refresh page immediately after successful removal
            } else {
                alert(data.message || 'Error removing item');
                if (data.message && data.message.includes('login')) {
                    window.location.reload();
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error removing item. Please try again.');
        });
    }

        function openCartModal() {
            document.getElementById('cartModal').style.display = 'flex';
        }

        function closeCartModal() {
            document.getElementById('cartModal').style.display = 'none';
        }
        function updateProductQuantities() {
        fetch('update_product_quantities.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    data.products.forEach(product => {
                        const quantityElement = document.querySelector(`.product-card[data-product-id="${product.product_id}"] .product-quantity`);
                        if (quantityElement) {
                            quantityElement.textContent = `Available: ${product.quantity}`;
                            
                            // Update max quantity in quantity inputs
                            const quantityInput = document.querySelector(`.product-card[data-product-id="${product.product_id}"] .quantity-input`);
                            if (quantityInput) {
                                quantityInput.max = product.quantity;
                                if (parseInt(quantityInput.value) > product.quantity) {
                                    quantityInput.value = product.quantity;
                                }
                            }
                        }
                    });
                }
            })
            .catch(error => console.error('Error updating quantities:', error));
    }
    function incrementProductQuantity(btn, maxQuantity) {
        const input = btn.previousElementSibling;
        const currentValue = parseInt(input.value);
        if (currentValue < maxQuantity) {
            input.value = currentValue + 1;
        } else {
            alert('Maximum available quantity is ' + maxQuantity);
        }
    }

    function decrementProductQuantity(btn) {
        const input = btn.nextElementSibling;
        const currentValue = parseInt(input.value);
        if (currentValue > 1) {
            input.value = currentValue - 1;
        }
    }
    function incrementQuantity(btn) {
        const input = btn.previousElementSibling;
        const currentValue = parseInt(input.value);
        input.value = currentValue + 1;
        handleQuantityChange(input.closest('.cart-item').id.replace('cart-item-', ''), input);
    }

    function decrementQuantity(btn) {
        const input = btn.nextElementSibling;
        const currentValue = parseInt(input.value);
        if (currentValue > 1) {
            input.value = currentValue - 1;
            handleQuantityChange(input.closest('.cart-item').id.replace('cart-item-', ''), input);
        }
    }
    </script>

</div>
</body>
</html>
