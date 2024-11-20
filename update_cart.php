<?php
session_start();
require_once 'connection.php';

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

header('Content-Type: application/json');
$response = ['success' => false];

if (isset($_POST['cart_id']) && isset($_POST['quantity']) && isset($_POST['update_quantity'])) {
    $cartId = $_POST['cart_id'];
    $newQuantity = (int)$_POST['quantity'];
    $userId = $_SESSION['user_id'];
    
    if ($newQuantity < 1) {
        $response['message'] = 'Quantity must be at least 1';
        echo json_encode($response);
        exit;
    }

    $db = new Database();
    $conn = $db->openConnection();

    try {
        $conn->beginTransaction();

        // Get current cart item and product details
        $getCartItem = $conn->prepare("
            SELECT c.*, p.quantity as available_stock, p.price, p.product_name 
            FROM cart c
            JOIN products p ON c.product_id = p.product_id
            WHERE c.id = ? AND c.user_id = ? AND c.status = 'active'
        ");
        $getCartItem->execute([$cartId, $userId]);
        $cartItem = $getCartItem->fetch(PDO::FETCH_ASSOC);

        if ($cartItem) {
            // Check if requested quantity is available
            if ($newQuantity <= $cartItem['available_stock']) {
                // Update cart quantity
                $updateCart = $conn->prepare("
                    UPDATE cart 
                    SET quantity = ? 
                    WHERE id = ? AND user_id = ?
                ");
                $updateCart->execute([$newQuantity, $cartId, $userId]);

                // Update session cart
                $cartQuery = $conn->prepare("
                    SELECT c.*, p.price, p.product_name 
                    FROM cart c
                    JOIN products p ON c.product_id = p.product_id
                    WHERE c.user_id = ? AND c.status = 'active'
                ");
                $cartQuery->execute([$userId]);
                $_SESSION['cart'] = $cartQuery->fetchAll(PDO::FETCH_ASSOC);

                $conn->commit();

                // Calculate new totals for response
                $response['success'] = true;
                $response['item_total'] = $cartItem['price'] * $newQuantity;
                
                // Calculate cart total
                $cart_total = 0;
                foreach ($_SESSION['cart'] as $item) {
                    $cart_total += $item['price'] * $item['quantity'];
                }
                $response['cart_total'] = $cart_total;
            } else {
                $response['message'] = 'Not enough stock available. Available: ' . $cartItem['available_stock'];
            }
        } else {
            $response['message'] = 'Cart item not found';
        }
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Update cart error: " . $e->getMessage());
        $response['message'] = 'Error updating cart';
    } finally {
        $db->closeConnection();
    }
} else {
    $response['message'] = 'Invalid request';
}

echo json_encode($response);
?>