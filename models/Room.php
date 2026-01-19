<?php
class Room {
    private $conn;
    private $table = "rooms";
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // ดึงรายการห้องทั้งหมด (สามารถกรองตาม status ได้)
    public function getAll($status = null): array {
        $query = "SELECT r.*, 
                         t.tenant_id, t.full_name as tenant_name, t.phone as tenant_phone
                  FROM " . $this->table . " r
                  LEFT JOIN tenants t ON r.room_id = t.room_id AND t.is_active = 1";
        
        if ($status) {
            $query .= " WHERE r.room_status = :status";
        }
        
        $query .= " ORDER BY r.room_number ASC";
        
        $stmt = $this->conn->prepare($query);
        if ($status) {
            $stmt->bindParam(':status', $status);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ดึงเฉพาะห้องที่ว่างสำหรับ dropdown ตอนสมัครสมาชิก
    public function getAvailableRooms(): array {
        // ใช้ room_status = 'available' ตามฐานข้อมูลจริง
        $query = "SELECT * FROM " . $this->table . " WHERE room_status = 'available' ORDER BY room_number ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // ดึงข้อมูลห้องตาม ID
    public function getById($room_id) {
        $query = "SELECT * FROM " . $this->table . " WHERE room_id = :room_id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':room_id', $room_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // สร้างห้องใหม่
    public function create(array $data) {
        $query = "INSERT INTO " . $this->table . " 
                  (room_number, room_type, monthly_rent, room_status, floor, description) 
                  VALUES (:room_number, :room_type, :monthly_rent, :room_status, :floor, :description)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':room_number', $data['room_number']);
        $stmt->bindParam(':room_type', $data['room_type']);
        $stmt->bindParam(':monthly_rent', $data['monthly_rent']);
        $stmt->bindParam(':room_status', $data['room_status']);
        $stmt->bindParam(':floor', $data['floor']);
        $stmt->bindParam(':description', $data['description']);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }
    
    // อัพเดทข้อมูลห้อง
    public function update($room_id, array $data) {
        $query = "UPDATE " . $this->table . " 
                  SET room_number = :room_number,
                      room_type = :room_type,
                      monthly_rent = :monthly_rent,
                      room_status = :room_status,
                      floor = :floor,
                      description = :description
                  WHERE room_id = :room_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':room_number', $data['room_number']);
        $stmt->bindParam(':room_type', $data['room_type']);
        $stmt->bindParam(':monthly_rent', $data['monthly_rent']);
        $stmt->bindParam(':room_status', $data['room_status']);
        $stmt->bindParam(':floor', $data['floor']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':room_id', $room_id);
        
        return $stmt->execute();
    }
    
    // ลบห้อง
    public function delete($room_id) {
        $query = "DELETE FROM " . $this->table . " WHERE room_id = :room_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':room_id', $room_id);
        return $stmt->execute();
    }
    
    // นับจำนวนห้องตามสถานะ
    public function countByStatus(): array {
        $query = "SELECT room_status, COUNT(*) as count 
                  FROM " . $this->table . " 
                  GROUP BY room_status";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $result = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[$row['room_status']] = $row['count'];
        }
        
        return $result;
    }
    
    // อัพเดทสถานะห้อง
    public function setStatus($room_id, $status): bool {
        $query = "UPDATE " . $this->table . " SET room_status = :status WHERE room_id = :room_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':room_id', $room_id);
        return $stmt->execute();
    }
}
?>
