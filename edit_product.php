<?php
require_once 'connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $db = new Database();
    $conn = $db->openConnection();

    $product_id = $_POST['product_id'] ?? null;
    $name = $_POST['name'] ?? null;
    $description = $_POST['description'] ?? null;
    $price = $_POST['price'] ?? null;
    $category_id = $_POST['category_id'] ?? null; 
    $quantity = $_POST['quantity'] ?? null;

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
            // Get the old image path
            $query = "SELECT image_path FROM products WHERE product_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$product_id]);
            $old_image = $stmt->fetchColumn();

            // Delete old image if it exists
            if ($old_image && file_exists($old_image)) {
                unlink($old_image);
            }

            $image_path = $upload_path;
        } else {
            die("Error uploading file.");
        }
    }

    if ($product_id && $name && $description && $price !== null && $category_id && $quantity !== null) {
        $query = "UPDATE products SET 
                  product_name = :name, 
                  description = :description, 
                  price = :price, 
                  category_id = :category_id, 
                  quantity = :quantity";
        
        // Only update image_path if a new image was uploaded
        if ($image_path !== null) {
            $query .= ", image_path = :image_path";
        }
        
        $query .= " WHERE product_id = :product_id";

        $stmt = $conn->prepare($query);
        $stmt->bindValue(':name', $name);
        $stmt->bindValue(':description', $description);
        $stmt->bindValue(':price', $price);
        $stmt->bindValue(':category_id', $category_id);
        $stmt->bindValue(':quantity', $quantity);
        $stmt->bindValue(':product_id', $product_id);
        
        if ($image_path !== null) {
            $stmt->bindValue(':image_path', $image_path);
        }

        if ($stmt->execute()) {
            header("Location: index.php");
            exit;
        } else {
            echo "Error updating product.";
        }
    } else {
        $missingFields = [];
        if (!$product_id) $missingFields[] = 'product_id';
        if (!$name) $missingFields[] = 'name';
        if (!$description) $missingFields[] = 'description';
        if ($price === null) $missingFields[] = 'price'; 
        if (!$category_id) $missingFields[] = 'category_id';
        if ($quantity === null) $missingFields[] = 'quantity'; 

        echo "All fields are required. Missing fields: " . implode(', ', $missingFields);
    }

    $db->closeConnection();
}
?>
