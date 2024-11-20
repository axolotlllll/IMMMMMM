<?php
class Database {
    private $host = "localhost";
    private $dbname = "product_db";
    private $user = "root";
    private $pass = "";

    public function openConnection() {
        try {
            $conn = new PDO("mysql:host=$this->host;dbname=$this->dbname;charset=utf8", $this->user, $this->pass);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $conn;
        } catch (PDOException $e) {
            echo "Connection failed: " . $e->getMessage();
            exit();
        }
    }

    public function closeConnection() {
        $this->conn = null;
    }
}
?>
