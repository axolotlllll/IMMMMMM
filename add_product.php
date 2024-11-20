<?php

require_once 'connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $db = new Database();
    $conn = $db->openConnection();

    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $category_id = $_POST['category_id'];
    $quantity = $_POST['quantity'];

    // Handle image upload
    $image_path = null;
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['product_image'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        
        if (!in_array($file['type'], $allowed_types)) {
            die("Error: Only JPEG, PNG and GIF images are allowed.");
        }

        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $extension;
        $upload_path = 'product_images/' . $filename;

        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            $image_path = $upload_path;
        } else {
            die("Error uploading file.");
        }
    }
    
    if (isset($name, $description, $price, $category_id, $quantity)) {
        $query = "INSERT INTO products (product_name, description, price, category_id, quantity, image_path, date_created) 
                  VALUES (:name, :description, :price, :category_id, :quantity, :image_path, NOW())";

        $stmt = $conn->prepare($query);
        $stmt->bindValue(':name', $name);
        $stmt->bindValue(':description', $description);
        $stmt->bindValue(':price', $price);
        $stmt->bindValue(':category_id', $category_id); 
        $stmt->bindValue(':quantity', $quantity);
        $stmt->bindValue(':image_path', $image_path);

        if ($stmt->execute()) {
            header("Location: index.php"); 
        } else {
            echo "Error adding product.";
        }
    } else {
        echo "All fields are required.";
    }

    $db->closeConnection();
}
?>
