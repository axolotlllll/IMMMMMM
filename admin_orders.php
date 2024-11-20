<?php
session_start();
require_once 'connection.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 1) {
    header("Location: login.php");
    exit();
}

$db = new Database();
$conn = $db->openConnection();

// Update order status if requested
if (isset($_POST['update_status']) && isset($_POST['order_id']) && isset($_POST['new_status'])) {
    try {
        $updateStatus = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $updateStatus->execute([$_POST['new_status'], $_POST['order_id']]);
        $_SESSION['success'] = "Order status updated successfully";
    } catch (Exception $e) {
        error_log("Error updating order status: " . $e->getMessage());
        $_SESSION['error'] = "Error updating order status";
    }
}

try {
    // Get all orders with user information and order items
    $query = $conn->prepare("
        SELECT 
            o.*, 
            u.username,
            GROUP_CONCAT(
                CONCAT(
                    oi.quantity, 'x ', 
                    p.product_name, 
                    ' (₱', FORMAT(oi.price, 2), ' each)'
                ) SEPARATOR '<br>'
            ) as order_items
        FROM orders o
        JOIN users u ON o.user_id = u.id
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.product_id
        GROUP BY o.id
        ORDER BY o.order_date DESC
    ");
    $query->execute();
    $orders = $query->fetchAll(PDO::FETCH_ASSOC);
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
    <title>Admin - Order Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #5D6ECE;
            --secondary-color: #4A5568;
            --background-color: #f7fafc;
            --card-background: #ffffff;
            --text-color: #2D3748;
            --border-color: #E2E8F0;
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
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .back-btn {
            display: inline-block;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            transition: background-color 0.3s ease;
        }

        .back-btn:hover {
            background-color: color-mix(in srgb, var(--primary-color) 80%, black);
        }

        h1 {
            text-align: center;
            color: var(--secondary-color);
            margin-bottom: 30px;
            font-weight: 600;
        }

        .success-message, .error-message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 500;
        }

        .success-message {
            background-color: rgba(72, 187, 120, 0.1);
            color: #48BB78;
            border: 1px solid #48BB78;
        }

        .error-message {
            background-color: rgba(245, 101, 101, 0.1);
            color: #F56565;
            border: 1px solid #F56565;
        }

        .order-card {
            background-color: var(--card-background);
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            padding: 25px;
            transition: box-shadow 0.3s ease;
            animation: fadeIn 0.6s ease-out;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
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

        .order-info {
            display: flex;
            flex-direction: column;
        }

        .order-info strong {
            color: var(--primary-color);
            font-size: 1.1em;
            margin-bottom: 5px;
        }

        .order-status {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending {
            background-color: #FBD38D;
            color: #744210;
        }

        .status-completed {
            background-color: #68D391;
            color: #22543D;
        }

        .status-cancelled {
            background-color: #FEB2B2;
            color: #822727;
        }

        .order-items {
            background-color: var(--background-color);
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }

        .order-items p {
            margin-bottom: 5px;
            color: var(--secondary-color);
        }

        .status-form {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid var(--border-color);
        }

        .status-select {
            padding: 8px 15px;
            border: 1px solid var(--border-color);
            border-radius: 20px;
            background-color: white;
            color: var(--text-color);
            flex-grow: 1;
            margin-right: 15px;
            transition: all 0.3s ease;
            border-radius: 20px;
            padding: 8px 15px;
            appearance: none;
            background-image: url("data:image/svg+xml;charset=utf8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 4 5'%3E%3Cpath fill='%23343a40' d='M2 0L0 2h4zm0 5L0 3h4z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 8px 10px;
        }

        .status-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }

        .order-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 15px;
            border-radius: 20px;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .action-btn i {
            margin-right: 5px;
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

        .order-details {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .order-details i {
            color: var(--primary-color);
            font-size: 1.2em;
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

        @media (max-width: 768px) {
            .order-container {
                padding: 0 10px;
            }

            .order-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .status-form {
                flex-direction: column;
                align-items: stretch;
            }

            .status-select {
                margin-right: 0;
                margin-bottom: 10px;
            }
        }

        @media (max-width: 600px) {
            .order-actions {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>
<body>
    <a href="index.php" class="back-btn">Back to Admin Panel</a>
    
    <div class="order-container">
        <h1>Order Management</h1>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="success-message"><?php echo $_SESSION['success']; ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-message"><?php echo $_SESSION['error']; ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (empty($orders)): ?>
            <p>No orders found.</p>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div class="order-info">
                            <h3>Order #<?php echo $order['id']; ?></h3>
                            <p>
                                <strong>Customer:</strong> <?php echo htmlspecialchars($order['username']); ?><br>
                                <strong>Date:</strong> <?php echo date('F j, Y g:i A', strtotime($order['order_date'])); ?><br>
                                <strong>Total Amount:</strong> <span class="total-amount"><i class="fas fa-money-bill-wave"></i> ₱<?php echo number_format($order['total_amount'], 2); ?></span>
                            </p>
                        </div>
                        <span class="order-status status-<?php echo strtolower($order['status']); ?>">
                            <?php echo ucfirst($order['status']); ?>
                        </span>
                    </div>
                    
                    <div class="order-items">
                        <strong>Order Items:</strong><br>
                        <?php echo $order['order_items']; ?>
                    </div>

                    <div class="order-details">
                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($order['username']); ?>
                        <i class="fas fa-calendar-alt"></i> <?php echo date('F j, Y g:i A', strtotime($order['order_date'])); ?>
                    </div>

                    <form class="status-form" method="POST">
                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                        <select name="new_status" class="status-select">
                            <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="completed" <?php echo $order['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                        <button type="submit" name="update_status" class="action-btn">Update Status <i class="fas fa-edit"></i></button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>