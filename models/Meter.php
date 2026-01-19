<?php
// ============================================
// ไฟล์: models/Meter.php
// คำอธิบาย: Model สำหรับจัดการมิเตอร์
// ============================================

class Meter {
    private $conn;
    private $table = "meters";
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // บันทึกมิเตอร์
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                  (room_id, meter_month, meter_year, water_previous, water_current, 
                   electric_previous, electric_current, recorded_by) 
                  VALUES (:room_id, :meter_month, :meter_year, :water_previous, :water_current,
                          :electric_previous, :electric_current, :recorded_by)
                  ON DUPLICATE KEY UPDATE
                  water_previous = :water_previous,
                  water_current = :water_current,
                  electric_previous = :electric_previous,
                  electric_current = :electric_current,
                  recorded_by = :recorded_by";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':room_id', $data['room_id']);
        $stmt->bindParam(':meter_month', $data['meter_month']);
        $stmt->bindParam(':meter_year', $data['meter_year']);
        $stmt->bindParam(':water_previous', $data['water_previous']);
        $stmt->bindParam(':water_current', $data['water_current']);
        $stmt->bindParam(':electric_previous', $data['electric_previous']);
        $stmt->bindParam(':electric_current', $data['electric_current']);
        $stmt->bindParam(':recorded_by', $data['recorded_by']);
        
        return $stmt->execute();
    }
    
    // ดึงมิเตอร์ล่าสุดของห้อง
    public function getLatestByRoom($room_id) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE room_id = :room_id 
                  ORDER BY meter_year DESC, meter_month DESC 
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':room_id', $room_id);
        $stmt->execute();
        return $stmt->fetch();
    }
    
    // ดึงมิเตอร์ตามเดือน-ปี
    public function getByMonthYear($room_id, $month, $year) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE room_id = :room_id 
                  AND meter_month = :month 
                  AND meter_year = :year 
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':room_id', $room_id);
        $stmt->bindParam(':month', $month);
        $stmt->bindParam(':year', $year);
        $stmt->execute();
        return $stmt->fetch();
    }
    
    // ดึงมิเตอร์ทั้งหมดในเดือน
    public function getAllByMonth($month, $year) {
        $query = "SELECT m.*, r.room_number, t.full_name as tenant_name
                  FROM " . $this->table . " m
                  JOIN rooms r ON m.room_id = r.room_id
                  LEFT JOIN tenants t ON r.room_id = t.room_id AND t.is_active = 1
                  WHERE m.meter_month = :month AND m.meter_year = :year
                  ORDER BY r.room_number ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':month', $month);
        $stmt->bindParam(':year', $year);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
?>