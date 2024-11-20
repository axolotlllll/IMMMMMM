<?php
session_start();
require_once 'connection.php'; 

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if the add to cart form is submitted
if (isset($_POST['add_to_cart'])) {
    $productId = $_POST['product_id'];
    $quantity = $_POST['quantity'];
    $userId = $_SESSION['user_id'];

    $db = new Database();
    $conn = $db->openConnection();

    try {
        // Start transaction
        $conn->beginTransaction();

        // Get the product details from the database
        $query = $conn->prepare("SELECT product_name, price, quantity FROM products WHERE product_id = :product_id");
        $query->bindParam(':product_id', $productId, PDO::PARAM_INT);
        $query->execute();
        $product = $query->fetch(PDO::FETCH_ASSOC);

        if ($product && $product['quantity'] >= $quantity) {
            // Check if item already exists in cart
            $cartQuery = $conn->prepare("
                SELECT id, quantity 
                FROM cart 
                WHERE user_id = :user_id 
                AND product_id = :product_id 
                AND (status = 'active' OR status = 'saved')
            ");
            $cartQuery->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $cartQuery->bindParam(':product_id', $productId, PDO::PARAM_INT);
            $cartQuery->execute();
            $existingCartItem = $cartQuery->fetch(PDO::FETCH_ASSOC);

            if ($existingCartItem) {
                // Check if total quantity would exceed available stock
                $newCartQuantity = $existingCartItem['quantity'] + $quantity;
                if ($newCartQuantity > $product['quantity']) {
                    $_SESSION['error'] = "Cannot add more items than available in stock";
                    header("Location: user_home.php");
                    exit();
                }
                
                // Update existing cart item
                $updateCartQuery = $conn->prepare("
                    UPDATE cart 
                    SET quantity = :quantity, status = 'active' 
                    WHERE id = :cart_id
                ");
                $updateCartQuery->bindParam(':quantity', $newCartQuantity, PDO::PARAM_INT);
                $updateCartQuery->bindParam(':cart_id', $existingCartItem['id'], PDO::PARAM_INT);
                $updateCartQuery->execute();
            } else {
                // Insert new cart item
                $insertCartQuery = $conn->prepare("
                    INSERT INTO cart (user_id, product_id, product_name, quantity, price, status) 
                    VALUES (:user_id, :product_id, :product_name, :quantity, :price, 'active')
                ");
                $insertCartQuery->bindParam(':user_id', $userId, PDO::PARAM_INT);
                $insertCartQuery->bindParam(':product_id', $productId, PDO::PARAM_INT);
                $insertCartQuery->bindParam(':product_name', $product['product_name'], PDO::PARAM_STR);
                $insertCartQuery->bindParam(':quantity', $quantity, PDO::PARAM_INT);
                $insertCartQuery->bindParam(':price', $product['price'], PDO::PARAM_STR);
                $insertCartQuery->execute();
            }

            // Commit transaction
            $conn->commit();

            // Update session cart
            $cartQuery = $conn->prepare("SELECT * FROM cart WHERE user_id = ? AND status = 'active'");
            $cartQuery->execute([$userId]);
            $_SESSION['cart'] = $cartQuery->fetchAll(PDO::FETCH_ASSOC);

            header("Location: user_home.php");
            exit();
        } else {
            $_SESSION['error'] = "Insufficient stock available";
            header("Location: user_home.php");
            exit();
        }
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollBack();
        $_SESSION['error'] = "Error adding item to cart";
        error_log("Add to cart error: " . $e->getMessage());
        header("Location: user_home.php");
        exit();
    } finally {
        $db->closeConnection();
    }
}
?>
