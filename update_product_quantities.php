<?php
session_start();
require_once 'connection.php';

header('Content-Type: application/json');

try {
    $db = new Database();
    $conn = $db->openConnection();

    $query = $conn->prepare("
        SELECT product_id, quantity 
        FROM products
    ");
    $query->execute();
    $products = $query->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'products' => $products]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error fetching product quantities']);
} finally {
    $db->closeConnection();
}
?>