<?php
session_start();
require_once 'connection.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 1) {
    header("Location: login.php");
    exit();
}

// Add no-cache headers
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

try {
    $db = new Database();
    $conn = $db->openConnection();

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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
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

        .customer-info {
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

        .status-select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            background-color: white;
            min-width: 150px;
        }

        .status-select:hover {
            border-color: var(--primary-color);
        }

        .status-select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(93, 110, 206, 0.2);
        }

        .status-select option {
            padding: 8px;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            text-transform: capitalize;
        }

        /* Status Colors */
        .status-pending {
            background-color: #FEF3C7;
            color: #92400E;
        }

        .status-processing {
            background-color: #EDE9FE;
            color: #5B21B6;
            border: 1px solid #8B5CF6;
        }

        .status-shipped {
            background-color: #CFF9E6;
            color: #047857;
            border: 1px solid #10B981;
        }

        .status-delivered {
            background-color: #DCFCE7;
            color: #15803D;
            border: 1px solid #22C55E;
        }

        .status-cancelled {
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
            .customer-info {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-btn">
            <i class="fas fa-arrow-left"></i>
            Back
        </a>
        <h1>Order Management</h1>

        <div id="statusToast" class="toast-notification"></div>

        <?php if (empty($orders)): ?>
            <p>No orders found.</p>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div class="order-info">
                            <div class="order-id">Order #<?= $order['id'] ?></div>
                            <div class="customer-info">
                                <div class="info-group">
                                    <div class="info-label">Customer</div>
                                    <div class="info-value"><?= htmlspecialchars($order['username']) ?></div>
                                </div>
                                <div class="info-group">
                                    <div class="info-label">Order Date</div>
                                    <div class="info-value"><?= date('F j, Y g:i A', strtotime($order['order_date'])) ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="status-select-container">
                            <?php
                            $currentStatus = strtolower(trim($order['status']));
                            $allowed_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
                            if (!in_array($currentStatus, $allowed_statuses)) {
                                $currentStatus = 'pending';
                            }
                            ?>
                            <select class="status-select" data-order-id="<?= $order['id'] ?>">
                                <option value="pending" <?= $currentStatus === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="processing" <?= $currentStatus === 'processing' ? 'selected' : '' ?>>Processing</option>
                                <option value="shipped" <?= $currentStatus === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                <option value="delivered" <?= $currentStatus === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                <option value="cancelled" <?= $currentStatus === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                            <span class="status-badge status-<?= $currentStatus ?>"><?= ucfirst($currentStatus) ?></span>
                        </div>
                    </div>
                    <div class="order-items">
                        <div class="order-items-title">Order Items</div>
                        <div class="item-list">
                            <?php
                            $items = explode('<br>', $order['order_items']);
                            foreach ($items as $item):
                                if (trim($item) === '') continue;
                                preg_match('/(\d+)x (.*?) \(₱([\d,]+\.\d{2}) each\)/', $item, $matches);
                                if (count($matches) >= 4):
                                    $quantity = $matches[1];
                                    $productName = $matches[2];
                                    $price = $matches[3];
                            ?>
                                <div class="order-item">
                                    <div class="item-details">
                                        <span class="item-name"><?= htmlspecialchars($productName) ?></span>
                                        <span class="item-quantity">(<?= $quantity ?>x)</span>
                                    </div>
                                    <div class="item-price">₱<?= $price ?> each</div>
                                </div>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
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
    $(document).ready(function() {
        function showToast(message, isError = false) {
            const toast = $('#statusToast');
            toast.text(message);
            toast.removeClass('success error').addClass(isError ? 'error' : 'success');
            toast.fadeIn();
            setTimeout(() => toast.fadeOut(), 3000);
        }

        $('.status-select').change(function() {
            const select = $(this);
            const orderId = select.data('order-id');
            const newStatus = select.val();
            const previousStatus = select.find('option:selected').attr('data-previous-status') || select.val();

            $.ajax({
                url: 'update_order_status.php',
                method: 'POST',
                data: {
                    order_id: orderId,
                    status: newStatus
                },
                success: function(response) {
                    if (response.success) {
                        showToast('Status updated successfully');
                        select.find('option:selected').attr('data-previous-status', newStatus);
                        
                        // Update status badge if it exists
                        const statusBadge = select.closest('.order-card').find('.status-badge');
                        if (statusBadge.length) {
                            statusBadge.attr('class', 'status-badge status-' + newStatus.toLowerCase())
                                     .text(newStatus.charAt(0).toUpperCase() + newStatus.slice(1));
                        }
                    } else {
                        showToast(response.message || 'Failed to update status', true);
                        select.val(previousStatus);
                    }
                },
                error: function() {
                    showToast('Error updating status', true);
                    select.val(previousStatus);
                }
            });
        });
    });
    </script>
</body>
</html>