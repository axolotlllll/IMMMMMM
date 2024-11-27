<?php
session_start();
require_once 'connection.php';

header('Content-Type: application/json');

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check required parameters
if (!isset($_POST['order_id']) || !isset($_POST['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

$order_id = intval($_POST['order_id']);
$new_status = strtolower(trim($_POST['status']));

// Validate status
$allowed_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
if (!in_array($new_status, $allowed_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

try {
    $db = new Database();
    $conn = $db->openConnection();

    // Get current order status
    $stmt = $conn->prepare("SELECT status FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $current_status = $stmt->fetchColumn();

    if ($current_status === false) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit();
    }

    // Update status
    $update = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $success = $update->execute([$new_status, $order_id]);

    if (!$success) {
        echo json_encode(['success' => false, 'message' => 'Failed to update status']);
        exit();
    }

    echo json_encode([
        'success' => true,
        'message' => 'Status updated successfully',
        'new_status' => $new_status,
        'order_id' => $order_id
    ]);

} catch (PDOException $e) {
    error_log("Database error in status update: " . $e->getMessage());
    error_log("SQL State: " . $e->getCode());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error occurred',
        'debug_info' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("General error in status update: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred',
        'debug_info' => $e->getMessage()
    ]);
}

$db->closeConnection();
?>
