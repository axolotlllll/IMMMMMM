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

if (isset($_POST['cart_id']) && isset($_POST['remove_from_cart'])) {
    $cartId = $_POST['cart_id'];
    $userId = $_SESSION['user_id'];

    $db = new Database();
    $conn = $db->openConnection();

    try {
        $conn->beginTransaction();

        // Get cart item details before deletion
        $getCartItem = $conn->prepare("
            SELECT c.*, p.quantity as current_stock, p.price 
            FROM cart c
            JOIN products p ON c.product_id = p.product_id
            WHERE c.id = ? AND c.user_id = ? AND c.status = 'active'
        ");
        $getCartItem->execute([$cartId, $userId]);
        $cartItem = $getCartItem->fetch(PDO::FETCH_ASSOC);

        if ($cartItem) {
            // Restore product quantity
            $newStockQuantity = $cartItem['current_stock'] + $cartItem['quantity'];
            $updateProduct = $conn->prepare("
                UPDATE products 
                SET quantity = ? 
                WHERE product_id = ?
            ");
            $updateProduct->execute([$newStockQuantity, $cartItem['product_id']]);

            // Delete the cart item
            $deleteCart = $conn->prepare("
                DELETE FROM cart 
                WHERE id = ? AND user_id = ?
            ");
            $deleteCart->execute([$cartId, $userId]);

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
            
            // Prepare success response
            $response['success'] = true;
            
            // Calculate new cart total
            $cart_total = 0;
            foreach ($_SESSION['cart'] as $item) {
                $cart_total += $item['price'] * $item['quantity'];
            }
            $response['cart_total'] = $cart_total;
            $response['cart_empty'] = empty($_SESSION['cart']);
        } else {
            $response['message'] = 'Cart item not found';
        }
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Remove from cart error: " . $e->getMessage());
        $response['message'] = 'Error removing item from cart';
    } finally {
        $db->closeConnection();
    }
} else {
    $response['message'] = 'Invalid request';
}

echo json_encode($response);

?>