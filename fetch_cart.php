<?php
session_start();
require_once 'connection.php';

if (!isset($_SESSION['user'])) {
    echo json_encode([]);  // No cart data if the user is not logged in
    exit();
}

$user_id = $_SESSION['user_id'];  // Assuming the user ID is stored in session

$db = new Database();
$conn = $db->openConnection();

// Fetch active cart items
$query = $conn->prepare("SELECT c.id, p.product_name, c.quantity, c.price FROM cart c JOIN products p ON c.product_id = p.product_id WHERE c.user_id = ? AND c.status = 'active'");
$query->execute([$user_id]);
$cart_items = $query->fetchAll(PDO::FETCH_ASSOC);

$db->closeConnection();
echo json_encode($cart_items);
