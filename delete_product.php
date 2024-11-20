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
        // First, remove related order items
        $remove_order_items = "DELETE FROM order_items WHERE product_id = :product_id";
        $stmt_remove = $conn->prepare($remove_order_items);
        $stmt_remove->bindValue(':product_id', $product_id);
        $stmt_remove->execute();

        // Then delete the product
        $query = "DELETE FROM products WHERE product_id = :product_id";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':product_id', $product_id);
        
        if ($stmt->execute()) {
           
            header('Location: index.php'); 
            exit();
        } else {
            die('Failed to delete the product.');
        }
    } catch (PDOException $e) {
        die('Error: ' . $e->getMessage());
    }
}

$db->closeConnection();
?>

