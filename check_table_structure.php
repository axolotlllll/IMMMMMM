<?php
require_once 'connection.php';

$db = new Database();
$conn = $db->openConnection();

echo "Products Table Structure:\n";
$stmt = $conn->query('DESCRIBE products');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($row);
}

echo "\nCategories Table Structure:\n";
$stmt = $conn->query('DESCRIBE categories');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($row);
}
?>
