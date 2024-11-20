<?php

require_once 'connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_name = $_POST['category_name'];

    $db = new Database();
    $conn = $db->openConnection();

    $query = $conn->prepare("INSERT INTO categories (category_name) VALUES (?)");
    $query->execute([$category_name]);

    $db->closeConnection();

    header("Location: index.php");
    exit();
}
?>
