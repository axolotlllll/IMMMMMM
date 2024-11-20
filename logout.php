<?php
// Start session
session_start();
require_once 'connection.php';

// If user has items in cart, save them to database before logout
if (isset($_SESSION['user_id']) && isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $db = new Database();
    $conn = $db->openConnection();
    
    try {
        $conn->beginTransaction();
        
        // Update cart items status to 'saved'
        $updateCartQuery = $conn->prepare("
            UPDATE cart 
            SET status = 'saved' 
            WHERE user_id = ? 
            AND status = 'active'
        ");
        $updateCartQuery->execute([$_SESSION['user_id']]);
        
        // Don't reduce product quantities since we're just saving the cart state
        
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Logout error: " . $e->getMessage());
    } finally {
        $db->closeConnection();
    }
}

// Destroy the session and redirect to the login page
session_unset();
session_destroy();

header("Location: login.php");
exit();
?>