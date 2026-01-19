<?php
class Database {
    private $host = "localhost";
    private $port = 3307; // port MySQL ใหม่
    private $db_name = "dormitory_management"; // ชื่อฐานข้อมูล
    private $username = "root";
    private $password = ""; // ถ้า root มี password ให้ใส่ที่นี่
    public $conn;

    public function getConnection(): PDO {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};port={$this->port};dbname={$this->db_name};charset=utf8mb4",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            die("Connection Error: " . $e->getMessage());
        }

        return $this->conn;
    }
}
?>
