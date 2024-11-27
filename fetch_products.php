<?php
require_once 'connection.php';

if (isset($_GET['term'])) {
    $term = $_GET['term'];
    
    $db = new Database();
    $conn = $db->openConnection();
    
    $query = $conn->prepare("
        SELECT product_name 
        FROM products 
        WHERE product_name LIKE :term 
        LIMIT 10
    ");
    
    $query->execute(['term' => "%$term%"]);
    $products = $query->fetchAll(PDO::FETCH_COLUMN);
    
    $db->closeConnection();
    
    echo json_encode($products);
}
?>
