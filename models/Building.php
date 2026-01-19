<?php
// ============================================
// ไฟล์: models/Building.php
// คำอธิบาย: Model สำหรับจัดการอาคาร/ทรัพย์สิน
// ============================================

class Building {
    private $conn;
    private $table = "buildings";
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // ดึงรายการอาคารทั้งหมด
    public function getAll($active_only = true) {
        $query = "SELECT b.*,
                         COUNT(DISTINCT r.room_id) as total_rooms,
                         COUNT(DISTINCT CASE WHEN r.room_status = 'occupied' THEN r.room_id END) as occupied_rooms
                  FROM " . $this->table . " b
                  LEFT JOIN rooms r ON b.building_id = r.building_id";
        
        if ($active_only) {
            $query .= " WHERE b.is_active = 1";
        }
        
        $query .= " GROUP BY b.building_id ORDER BY b.building_name ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // ดึงข้อมูลอาคารตาม ID
    public function getById($building_id) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE building_id = :building_id LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':building_id', $building_id);
        $stmt->execute();
        return $stmt->fetch();
    }
    
    // สร้างอาคารใหม่
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                  (building_name, building_type, water_rate_per_unit, water_minimum_unit,
                   water_minimum_charge, electric_rate_per_unit, garbage_fee,
                   address, description, created_by) 
                  VALUES (:building_name, :building_type, :water_rate_per_unit, :water_minimum_unit,
                          :water_minimum_charge, :electric_rate_per_unit, :garbage_fee,
                          :address, :description, :created_by)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':building_name', $data['building_name']);
        $stmt->bindParam(':building_type', $data['building_type']);
        $stmt->bindParam(':water_rate_per_unit', $data['water_rate_per_unit']);
        $stmt->bindParam(':water_minimum_unit', $data['water_minimum_unit']);
        $stmt->bindParam(':water_minimum_charge', $data['water_minimum_charge']);
        $stmt->bindParam(':electric_rate_per_unit', $data['electric_rate_per_unit']);
        $stmt->bindParam(':garbage_fee', $data['garbage_fee']);
        $stmt->bindParam(':address', $data['address']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':created_by', $data['created_by']);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }
    
    // อัพเดทข้อมูลอาคาร
    public function update($building_id, $data) {
        $query = "UPDATE " . $this->table . " 
                  SET building_name = :building_name,
                      building_type = :building_type,
                      water_rate_per_unit = :water_rate_per_unit,
                      water_minimum_unit = :water_minimum_unit,
                      water_minimum_charge = :water_minimum_charge,
                      electric_rate_per_unit = :electric_rate_per_unit,
                      garbage_fee = :garbage_fee,
                      address = :address,
                      description = :description
                  WHERE building_id = :building_id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':building_name', $data['building_name']);
        $stmt->bindParam(':building_type', $data['building_type']);
        $stmt->bindParam(':water_rate_per_unit', $data['water_rate_per_unit']);
        $stmt->bindParam(':water_minimum_unit', $data['water_minimum_unit']);
        $stmt->bindParam(':water_minimum_charge', $data['water_minimum_charge']);
        $stmt->bindParam(':electric_rate_per_unit', $data['electric_rate_per_unit']);
        $stmt->bindParam(':garbage_fee', $data['garbage_fee']);
        $stmt->bindParam(':address', $data['address']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':building_id', $building_id);
        
        return $stmt->execute();
    }
    
    // ปิดการใช้งานอาคาร (ไม่ลบ)
    public function deactivate($building_id) {
        $query = "UPDATE " . $this->table . " 
                  SET is_active = 0 
                  WHERE building_id = :building_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':building_id', $building_id);
        return $stmt->execute();
    }
    
    // เปิดการใช้งานอาคาร
    public function activate($building_id) {
        $query = "UPDATE " . $this->table . " 
                  SET is_active = 1 
                  WHERE building_id = :building_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':building_id', $building_id);
        return $stmt->execute();
    }
    
    // ดึงประเภทอาคารทั้งหมด (สำหรับ dropdown)
    public function getBuildingTypes() {
        return [
            'ห้องเช่า' => 'ห้องเช่า',
            'ตึกเช่า' => 'ตึกเช่า',
            'บ้านเช่า' => 'บ้านเช่า',
            'อพาร์ทเมนท์' => 'อพาร์ทเมนท์',
            'คอนโด' => 'คอนโด'
        ];
    }
    
    // ดึง rate ของอาคาร
    public function getRates($building_id) {
        $query = "SELECT water_rate_per_unit, water_minimum_unit, water_minimum_charge,
                         electric_rate_per_unit, garbage_fee
                  FROM " . $this->table . " 
                  WHERE building_id = :building_id LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':building_id', $building_id);
        $stmt->execute();
        return $stmt->fetch();
    }
    
    // นับจำนวนห้องในอาคาร
    public function countRooms($building_id) {
        $query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN room_status = 'occupied' THEN 1 ELSE 0 END) as occupied,
                    SUM(CASE WHEN room_status = 'available' THEN 1 ELSE 0 END) as available
                  FROM rooms 
                  WHERE building_id = :building_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':building_id', $building_id);
        $stmt->execute();
        return $stmt->fetch();
    }
}
?>