<?php
require_once 'connection.php';

// Database direct insert for admin
try {
    $db = new Database();
    $conn = $db->openConnection();

    $username = 'newadmin'; // Change this to your desired username
    $password = 'password123'; // Plain text password
    
    $stmt = $conn->prepare("INSERT INTO users (username, password, user_type) VALUES (?, ?, 1)");
    $result = $stmt->execute([$username, $password]);
    
    if ($result) {
        echo "Admin user '$username' created successfully!";
    } else {
        echo "Failed to create admin user.";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
} finally {
    $db->closeConnection();
}
?>
