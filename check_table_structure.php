<?php
require_once 'connection.php';

$db = new Database();
$conn = $db->openConnection();

// Check products table structure
echo "Products Table Structure:\n";
$stmt = $conn->query('DESCRIBE products');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($row);
}

// Check categories table structure
echo "\nCategories Table Structure:\n";
$stmt = $conn->query('DESCRIBE categories');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($row);
}
?>
