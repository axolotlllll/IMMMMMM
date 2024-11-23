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
    $conn->beginTransaction();

    if (isset($_POST['buy_now']) && isset($_POST['product_id']) && isset($_POST['quantity'])) {
        $stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ?");
        $stmt->execute([$_POST['product_id']]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product || $product['quantity'] < $_POST['quantity']) {
            throw new Exception("Product not available in requested quantity");
        }

        // Calculate total
        $total_amount = $product['price'] * $_POST['quantity'];

        // Create order
        $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, status) VALUES (?, ?, 'pending')");
        $stmt->execute([$_SESSION['user_id'], $total_amount]);
        $order_id = $conn->lastInsertId();

        // Add order item
        $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        $stmt->execute([$order_id, $_POST['product_id'], $_POST['quantity'], $product['price']]);

        // Update product quantity
        $new_quantity = $product['quantity'] - $_POST['quantity'];
        $stmt = $conn->prepare("UPDATE products SET quantity = ? WHERE product_id = ?");
        $stmt->execute([$new_quantity, $_POST['product_id']]);

    } else {
        // Regular cart checkout
        if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
            header("Location: user_home.php");
            exit();
        }

        // Verify stock availability for all cart items
        foreach ($_SESSION['cart'] as $item) {
            $stmt = $conn->prepare("SELECT quantity FROM products WHERE product_id = ?");
            $stmt->execute([$item['product_id']]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$product || $product['quantity'] < $item['quantity']) {
                throw new Exception("Product '{$item['product_name']}' is not available in requested quantity");
            }
        }

        // Calculate total amount
        $total_amount = 0;
        foreach ($_SESSION['cart'] as $item) {
            $total_amount += $item['price'] * $item['quantity'];
        }

        // Create order
        $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, status) VALUES (?, ?, 'pending')");
        $stmt->execute([$_SESSION['user_id'], $total_amount]);
        $order_id = $conn->lastInsertId();

        // Add order items and update product quantities
        foreach ($_SESSION['cart'] as $item) {
            // Add order item
            $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);

            // Update product quantity
            $stmt = $conn->prepare("UPDATE products SET quantity = quantity - ? WHERE product_id = ?");
            $stmt->execute([$item['quantity'], $item['product_id']]);
        }

        // Clear the cart in database
        $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND status = 'active'");
        $stmt->execute([$_SESSION['user_id']]);

        // Clear the cart in session
        unset($_SESSION['cart']);
    }

    $conn->commit();
    $_SESSION['success'] = "Order placed successfully!";
    header("Location: view_orders.php");
    exit();

} catch (Exception $e) {
    $conn->rollBack();
    error_log("Error during checkout: " . $e->getMessage());
    $_SESSION['error'] = "Error processing your order: " . $e->getMessage();
    header("Location: user_home.php");
    exit();
} finally {
    $db->closeConnection();
}
?>
