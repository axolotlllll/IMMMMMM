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
    $orders = array_values($orders);
} catch (Exception $e) {
    error_log("Error fetching orders: " . $e->getMessage());
    $orders = [];
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
            --background-color: #f4f6f7;
            --card-background: #ffffff;
            --border-color: #e0e0e0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Segoe UI', Roboto, sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            line-height: 1.6;
        }

        .order-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .back-btn {
            display: inline-block;
            color: var(--primary-color);
            text-decoration: none;
            margin-bottom: 20px;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .back-btn:hover {
            color: darken(var(--primary-color), 10%);
        }

        h1 {
            text-align: center;
            color: var(--text-color);
            margin-bottom: 30px;
            font-weight: 600;
        }

        .success-message {
            background-color: rgba(46, 204, 113, 0.1);
            border: 1px solid var(--secondary-color);
            color: var(--secondary-color);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }

        .order-card {
            background-color: var(--card-background);
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            padding: 20px;
            transition: box-shadow 0.3s ease;
            animation: fadeIn 0.6s ease-out;
            position: relative;
            overflow: hidden;
        }

        .order-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(
                45deg, 
                transparent, 
                rgba(255,255,255,0.05), 
                transparent
            );
            transform: rotate(-45deg);
            transition: all 0.5s ease;
        }

        .order-card:hover::before {
            top: -10%;
            left: -10%;
            background: linear-gradient(
                45deg, 
                transparent, 
                rgba(255,255,255,0.1), 
                transparent
            );
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 15px;
            margin-bottom: 15px;
        }

        .order-header strong {
            font-size: 1.1em;
            color: var(--primary-color);
        }

        .order-date {
            color: #6c757d;
            font-size: 0.9em;
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--secondary-color);
        }

        .order-date i {
            color: var(--primary-color);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .status-badge:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.15);
        }

        .status-badge i {
            margin-right: 5px;
        }

        .status-badge.status-pending {
            background-color: #f39c12;
            color: white;
        }

        .status-badge.status-completed {
            background-color: var(--secondary-color);
            color: white;
        }

        .status-badge.status-cancelled {
            background-color: #e74c3c;
            color: white;
        }

        .order-items {
            margin: 15px 0;
        }

        .order-item {
            display: flex;
            align-items: center;
            transition: background-color 0.3s ease;
        }

        .order-item:hover {
            background-color: rgba(52, 152, 219, 0.05);
        }

        .order-item-icon {
            margin-right: 15px;
            color: var(--primary-color);
            font-size: 1.2em;
        }

        .item-details {
            display: flex;
            align-items: center;
        }

        .item-details strong {
            margin-right: 10px;
        }

        .quantity-badge {
            background-color: var(--primary-color);
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8em;
        }

        .item-price {
            font-weight: 600;
            color: var(--primary-color);
        }

        .total-amount {
            animation: pulse 2s infinite;
            font-size: 1.3em;
            color: var(--primary-color);
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: flex-end;
        }

        .total-amount i {
            margin-right: 10px;
            color: var(--secondary-color);
        }

        .empty-message {
            text-align: center;
            color: #6c757d;
            padding: 30px;
            background-color: var(--card-background);
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        @media (max-width: 600px) {
            .order-container {
                padding: 0 10px;
            }

            .order-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .order-date {
                margin-top: 5px;
            }

            .order-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .order-item-icon {
                margin-bottom: 10px;
            }

            .item-price {
                margin-top: 10px;
                align-self: flex-end;
            }
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
                        <strong>Order #<?php echo htmlspecialchars($order['order_id']); ?></strong>
                        <?php if (isset($order['status'])): ?>
                            <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                <i class="fa-solid fa-circle-check"></i>
                                <?php echo htmlspecialchars($order['status']); ?>
                            </span>
                        <?php endif; ?>
                        <div class="order-date">
                            <i class="fa-solid fa-calendar-days"></i>
                            <?php echo date('F j, Y g:i A', strtotime($order['order_date'])); ?>
                        </div>
                    </div>
                    
                    <div class="order-items">
                        <?php foreach ($order['items'] as $item): ?>
                            <div class="order-item">
                                <div class="item-details">
                                    <i class="fa-solid fa-box order-item-icon"></i>
                                    <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                                    <span class="quantity-badge">Qty: <?php echo htmlspecialchars($item['quantity']); ?></span>
                                </div>
                                <div class="item-price">
                                    ₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="total-amount">
                        <i class="fa-solid fa-money-bill-wave"></i>
                        Total Amount: ₱<?php echo number_format($order['total_amount'], 2); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>