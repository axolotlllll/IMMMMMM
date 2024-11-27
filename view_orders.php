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
        SELECT DISTINCT
            o.id as order_id,
            o.order_date,
            o.total_amount,
            LOWER(TRIM(o.status)) as status,
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
                'status' => $row['status'], // Status is already lowercase and trimmed
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            padding: 20px;
            border: 1px solid #E2E8F0;
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #E2E8F0;
        }

        .order-info {
            flex: 1;
        }

        .order-id {
            font-size: 1.25rem;
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 10px;
        }

        .order-meta {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 15px;
        }

        .info-group {
            background: #F8FAFC;
            padding: 10px;
            border-radius: 8px;
        }

        .info-label {
            font-size: 0.875rem;
            color: #64748B;
            margin-bottom: 4px;
        }

        .info-value {
            font-size: 1rem;
            color: #1E293B;
            font-weight: 500;
        }

        .order-items {
            background: #F8FAFC;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
        }

        .order-items-title {
            font-size: 1rem;
            color: #64748B;
            margin-bottom: 10px;
            font-weight: 500;
        }

        .item-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px;
            background: white;
            border-radius: 6px;
            border: 1px solid #E2E8F0;
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-weight: 500;
        }

        .item-quantity {
            color: #64748B;
            margin-left: 8px;
        }

        .item-price {
            font-weight: 500;
            color: #047857;
        }

        .order-total {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #E2E8F0;
        }

        .total-label {
            font-size: 1rem;
            color: #64748B;
            margin-right: 10px;
        }

        .total-amount {
            font-size: 1.25rem;
            font-weight: 600;
            color: #047857;
        }

        /* Status Colors */
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            text-transform: capitalize;
        }

        .status-badge.status-pending {
            background-color: #FEF3C7;
            color: #92400E;
        }

        .status-badge.status-processing {
            background-color: #DBEAFE;
            color: #1E40AF;
        }

        .status-badge.status-shipped {
            background-color: #CFF9E6;
            color: #047857;
        }

        .status-badge.status-delivered {
            background-color: #BBF7D0;
            color: #166534;
        }

        .status-badge.status-cancelled {
            background-color: #FEE2E2;
            color: #991B1B;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background-color: #f8f9fa;
            color: #1a1a1a;
            text-decoration: none;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            font-weight: 500;
            transition: all 0.2s ease;
            margin-bottom: 20px;
        }

        .back-btn:hover {
            background-color: #e2e8f0;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .back-btn:active {
            transform: translateY(0);
        }

        .back-btn i {
            font-size: 18px;
        }

        @media (max-width: 768px) {
            .order-meta {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div id="statusToast" class="toast-notification"></div>
    <div class="order-container">
        <a href="user_home.php" class="back-btn">
            <i class="fas fa-arrow-left"></i>
            Back
        </a>
        
        <h1>My Orders</h1>
        
        <?php if (empty($orders)): ?>
            <p class="no-orders">You haven't placed any orders yet.</p>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div class="order-info">
                            <div class="order-id">Order #<?= $order['order_id'] ?></div>
                            <div class="order-meta">
                                <div class="info-group">
                                    <div class="info-label">Order Date</div>
                                    <div class="info-value"><?= date('F j, Y g:i A', strtotime($order['order_date'])) ?></div>
                                </div>
                                <div class="info-group">
                                    <div class="info-label">Status</div>
                                    <div class="info-value">
                                        <span class="status-badge status-<?= strtolower($order['status']) ?>">
                                            <?= ucfirst($order['status']) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="order-items">
                        <div class="order-items-title">Order Items</div>
                        <div class="item-list">
                            <?php foreach ($order['items'] as $item): ?>
                                <div class="order-item">
                                    <div class="item-details">
                                        <span class="item-name"><?= htmlspecialchars($item['product_name']) ?></span>
                                        <span class="item-quantity">(<?= $item['quantity'] ?>x)</span>
                                    </div>
                                    <div class="item-price">₱<?= number_format($item['price'], 2) ?> each</div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="order-total">
                            <span class="total-label">Total Amount:</span>
                            <span class="total-amount">₱<?= number_format($order['total_amount'], 2) ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
    const statusCycle = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

    function cycleStatus(orderId) {
        const statusBadge = document.getElementById(`status-badge-${orderId}`);
        const currentStatus = statusBadge.textContent.trim().toLowerCase();
        const currentIndex = statusCycle.indexOf(currentStatus);
        const nextIndex = (currentIndex + 1) % statusCycle.length;
        const newStatus = statusCycle[nextIndex];

        // Make AJAX call to update status
        $.ajax({
            url: 'update_order_status.php',
            method: 'POST',
            data: {
                order_id: orderId,
                status: newStatus
            },
            success: function(response) {
                if (response.success) {
                    updateOrderStatus(orderId, newStatus);
                } else {
                    showToast('Error: ' + response.message, true);
                }
            },
            error: function() {
                showToast('Error updating status', true);
            }
        });
    }

    function updateOrderStatus(orderId, newStatus) {
        const statusBadge = document.getElementById(`status-badge-${orderId}`);
        if (statusBadge) {
            statusBadge.className = `status-badge status-${newStatus}`;
            statusBadge.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
            showToast('Order status updated to: ' + newStatus.charAt(0).toUpperCase() + newStatus.slice(1));
        }
    }

    function showToast(message, isError = false) {
        const toast = $('#statusToast');
        toast.text(message);
        toast.css('background-color', isError ? '#DC2626' : '#4CAF50');
        toast.fadeIn();
        
        setTimeout(() => {
            toast.fadeOut();
        }, 3000);
    }
    </script>
</body>
</html>