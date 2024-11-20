<?php
require_once 'connection.php';

try {
    $db = new Database();
    $conn = $db->openConnection();

    $query = "ALTER TABLE products ADD COLUMN image_path VARCHAR(255) DEFAULT NULL";
    $conn->exec($query);
    
    echo "Successfully added image_path column to products table.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
} finally {
    $db->closeConnection();
}
?>
