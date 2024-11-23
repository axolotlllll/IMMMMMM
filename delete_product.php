<?php
require_once 'connection.php';

$db = new Database();
$conn = $db->openConnection();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
   
    $product_id = $_POST['product_id'] ?? '';

    if (!$product_id) {
        die('Product ID is required.');
    }

    try {
        // Start transaction
        $conn->beginTransaction();

        $remove_cart = "DELETE FROM cart WHERE product_id = :product_id";
        $stmt_cart = $conn->prepare($remove_cart);
        $stmt_cart->bindValue(':product_id', $product_id);
        $stmt_cart->execute();

        $remove_order_items = "DELETE FROM order_items WHERE product_id = :product_id";
        $stmt_remove = $conn->prepare($remove_order_items);
        $stmt_remove->bindValue(':product_id', $product_id);
        $stmt_remove->execute();

        $get_image = "SELECT image_path FROM products WHERE product_id = :product_id";
        $stmt_image = $conn->prepare($get_image);
        $stmt_image->bindValue(':product_id', $product_id);
        $stmt_image->execute();
        $image_path = $stmt_image->fetchColumn();

        $query = "DELETE FROM products WHERE product_id = :product_id";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':product_id', $product_id);
        
        if ($stmt->execute()) {
            if ($image_path && file_exists($image_path) && $image_path != 'product_images/default.jpg') {
                unlink($image_path);
            }
            
            $conn->commit();
            
            header('Location: index.php'); 
            exit();
        } else {
            // Rollback on failure
            $conn->rollBack();
            die('Failed to delete the product.');
        }
    } catch (PDOException $e) {
        // Rollback on error
        $conn->rollBack();
        die('Error: ' . $e->getMessage());
    }
}

$db->closeConnection();
?>
