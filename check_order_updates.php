<?php
session_start();
require_once 'connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit();
}

try {
    $db = new Database();
    $conn = $db->openConnection();

    $query = $conn->prepare("
        SELECT id, status, last_updated 
        FROM orders 
        WHERE user_id = ? 
        AND last_updated > DATE_SUB(NOW(), INTERVAL 1 MINUTE)
    ");
    
    $query->execute([$_SESSION['user_id']]);
    $updates = $query->fetchAll(PDO::FETCH_ASSOC);

    $db->closeConnection();
    echo json_encode($updates);
} catch (Exception $e) {
    echo json_encode([]);
}
?>
