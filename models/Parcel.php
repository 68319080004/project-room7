<?php
// ============================================
// ไฟล์: models/Parcel.php
// คำอธิบาย: Model ระบบจัดการพัสดุ
// ============================================

class Parcel {
    private $conn;
    private $table = "parcels";
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // บันทึกพัสดุ
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                  (room_id, tenant_id, tracking_number, courier_company,
                   sender_name, parcel_type, notes, received_by_staff_id,
                   parcel_status) 
                  VALUES 
                  (:room_id, :tenant_id, :tracking_number, :courier_company,
                   :sender_name, :parcel_type, :notes, :received_by_staff_id,
                   :parcel_status)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':room_id', $data['room_id']);
        $stmt->bindParam(':tenant_id', $data['tenant_id']);
        $stmt->bindParam(':tracking_number', $data['tracking_number']);
        $stmt->bindParam(':courier_company', $data['courier_company']);
        $stmt->bindParam(':sender_name', $data['sender_name']);
        $stmt->bindParam(':parcel_type', $data['parcel_type']);
        $stmt->bindParam(':notes', $data['notes']);
        $stmt->bindParam(':received_by_staff_id', $data['received_by_staff_id']);
        $stmt->bindParam(':parcel_status', $data['parcel_status']);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }
    
    // ดึงพัสดุทั้งหมด
    // ใน models/Parcel.php
    public function getAll() {
        $query = "SELECT p.id as parcel_id, p.*, 
                         r.room_number, 
                         t.full_name as tenant_name, 
                         t.phone as tenant_phone
                  FROM " . $this->table . " p
                  LEFT JOIN rooms r ON p.room_id = r.room_id
                  LEFT JOIN tenants t ON p.tenant_id = t.tenant_id
                  ORDER BY p.received_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // ดึงตาม ID
    public function getById($parcel_id) {
        $query = "SELECT p.*, 
                         r.room_number,
                         t.full_name as tenant_name, t.phone as tenant_phone,
                         u1.full_name as received_by_name
                  FROM " . $this->table . " p
                  JOIN rooms r ON p.room_id = r.room_id
                  JOIN tenants t ON p.tenant_id = t.tenant_id
                  LEFT JOIN users u1 ON p.received_by_staff_id = u1.user_id
                  WHERE p.parcel_id = :parcel_id 
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':parcel_id', $parcel_id);
        $stmt->execute();
        return $stmt->fetch();
    }
    
    // รับพัสดุแล้ว
    // อัปเดตสถานะเป็นรับแล้ว
    public function markAsPickedUp($id, $staffId) {
        $query = "UPDATE " . $this->table . " 
                  SET parcel_status = 'picked_up', 
                      picked_up_at = NOW(), 
                      picked_up_by_user_id = :staffId 
                  WHERE id = :id"; // <-- สังเกตตรงนี้ต้องเป็น id
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':staffId', $staffId);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }
    
    // นับพัสดุที่ยังไม่รับ
    public function countPending($tenant_id = null) {
        $query = "SELECT COUNT(*) as count 
                  FROM " . $this->table . " 
                  WHERE parcel_status = 'waiting'";
        
        if ($tenant_id) {
            $query .= " AND tenant_id = :tenant_id";
        }
        
        $stmt = $this->conn->prepare($query);
        
        if ($tenant_id) {
            $stmt->bindParam(':tenant_id', $tenant_id);
        }
        
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['count'];
    }
    
    // สถิติพัสดุ
    public function getStats() {
        $query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN parcel_status = 'waiting' THEN 1 ELSE 0 END) as waiting,
                    SUM(CASE WHEN parcel_status = 'picked_up' THEN 1 ELSE 0 END) as picked_up,
                    SUM(CASE WHEN DATE(received_at) = CURDATE() THEN 1 ELSE 0 END) as today
                  FROM " . $this->table;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch();
    }
    public function getParcelsByTenant($tenant_id) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE tenant_id = :tenant_id 
                  ORDER BY 
                    CASE WHEN parcel_status = 'waiting' THEN 1 ELSE 2 END, 
                    received_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':tenant_id', $tenant_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // บริษัทขนส่ง
    public function getCourierCompanies() {
        return [
            'Kerry Express' => 'Kerry Express',
            'Flash Express' => 'Flash Express',
            'J&T Express' => 'J&T Express',
            'Thailand Post' => 'ไปรษณีย์ไทย',
            'DHL' => 'DHL',
            'FedEx' => 'FedEx',
            'SCG Express' => 'SCG Express',
            'Best Express' => 'Best Express',
            'Ninja Van' => 'Ninja Van',
            'Other' => 'อื่นๆ'
        ];
    }
    
    // ประเภทพัสดุ
    public function getParcelTypes() {
        return [
            'envelope' => 'ซองจดหมาย',
            'small_box' => 'กล่องเล็ก',
            'medium_box' => 'กล่องกลาง',
            'large_box' => 'กล่องใหญ่',
            'bulky' => 'ขนาดใหญ่มาก'
        ];
    }
}
?>