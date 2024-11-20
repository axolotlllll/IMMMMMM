<?php
session_start();
require_once 'connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$db = new Database();
$conn = $db->openConnection();

try {
    // Get all orders with their items for the current user
    $query = $conn->prepare("
        SELECT 
            o.id as order_id,
            o.order_date,
            o.total_amount,
            o.status,
            oi.quantity,
            oi.price as item_price,
            p.product_name,
            p.product_id
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.product_id
        WHERE o.user_id = ?
        ORDER BY o.order_date DESC
    ");
    
    $query->execute([$_SESSION['user_id']]);
    $results = $query->fetchAll(PDO::FETCH_ASSOC);

    // Organize results by order
    $orders = [];
    foreach ($results as $row) {
        if (!isset($orders[$row['order_id']])) {
            $orders[$row['order_id']] = [
                'order_id' => $row['order_id'],
                'order_date' => $row['order_date'],
                'total_amount' => $row['total_amount'],
                'status' => $row['status'],
                'items' => []
            ];
        }
        if ($row['product_id']) {
            $orders[$row['order_id']]['items'][] = [
                'product_name' => $row['product_name'],
                'quantity' => $row['quantity'],
                'price' => $row['item_price']
            ];
        }
    }
} catch (Exception $e) {
    error_log("Error fetching orders: " . $e->getMessage());
    $_SESSION['error'] = "Error loading orders";
} finally {
    $db->closeConnection();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2ecc71;
            --text-color: #333;
            --background-color: #f9f9f9;
            --border-color: #ddd;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            line-height: 1.6;
        }

        .order-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        h1 {
            text-align: center;
            color: var(--text-color);
            margin-bottom: 2rem;
        }

        .order-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
            padding: 1.5rem;
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .order-date {
            color: #666;
            font-size: 0.9rem;
        }

        .order-total {
            font-weight: bold;
            color: var(--primary-color);
        }

        .order-items {
            list-style: none;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--border-color);
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .item-details {
            flex-grow: 1;
        }

        .item-price {
            font-weight: bold;
            margin-left: 1rem;
        }

        .back-btn {
            display: inline-block;
            color: var(--primary-color);
            text-decoration: none;
            margin-bottom: 1rem;
            font-weight: bold;
        }

        .back-btn:hover {
            text-decoration: underline;
        }

        .empty-message {
            text-align: center;
            padding: 2rem;
            color: #666;
        }

        .success-message {
            background-color: var(--secondary-color);
            color: white;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            text-align: center;
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: bold;
            text-transform: capitalize;
        }

        .status-pending {
            background-color: #f1c40f;
            color: #fff;
        }

        .status-processing {
            background-color: #3498db;
            color: #fff;
        }

        .status-completed {
            background-color: #2ecc71;
            color: #fff;
        }

        .status-cancelled {
            background-color: #e74c3c;
            color: #fff;
        }

        .quantity-badge {
            background-color: var(--primary-color);
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.85rem;
            margin-left: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="order-container">
        <a href="user_home.php" class="back-btn">← Back to Products</a>
        
        <h1>My Orders</h1>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="success-message">
                <?php echo htmlspecialchars($_SESSION['success']); ?>
                <?php unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($orders)): ?>
            <div class="order-card">
                <p class="empty-message">No orders found. Start shopping to see your orders here!</p>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <div class="order-date">Order Date: <?php echo date('F j, Y g:i A', strtotime($order['order_date'])); ?></div>
                            <div>Order ID: #<?php echo $order['order_id']; ?></div>
                        </div>
                        <div>
                            <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                <?php echo $order['status']; ?>
                            </span>
                        </div>
                    </div>
                    <ul class="order-items">
                        <?php foreach ($order['items'] as $item): ?>
                            <li class="order-item">
                                <div class="item-details">
                                    <?php echo htmlspecialchars($item['product_name']); ?>
                                    <span class="quantity-badge">×<?php echo $item['quantity']; ?></span>
                                </div>
                                <div class="item-price">
                                    ₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="order-header" style="margin-top: 1rem;">
                        <div></div>
                        <div class="order-total">Total: ₱<?php echo number_format($order['total_amount'], 2); ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>