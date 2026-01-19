<?php
// ============================================
// ไฟล์: models/Contract.php
// คำอธิบาย: Model สำหรับจัดการสัญญาเช่า
// ============================================

class Contract {
    private $conn;
    private $table = "contracts";
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // สร้างสัญญาใหม่
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                  (tenant_id, room_id, contract_number, start_date, end_date,
                   monthly_rent, deposit_amount, water_rate, electric_rate, 
                   garbage_fee, contract_terms, landlord_name, landlord_id_card,
                   witness_name, witness_id_card, contract_status, created_by) 
                  VALUES 
                  (:tenant_id, :room_id, :contract_number, :start_date, :end_date,
                   :monthly_rent, :deposit_amount, :water_rate, :electric_rate,
                   :garbage_fee, :contract_terms, :landlord_name, :landlord_id_card,
                   :witness_name, :witness_id_card, :contract_status, :created_by)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':tenant_id', $data['tenant_id']);
        $stmt->bindParam(':room_id', $data['room_id']);
        $stmt->bindParam(':contract_number', $data['contract_number']);
        $stmt->bindParam(':start_date', $data['start_date']);
        $stmt->bindParam(':end_date', $data['end_date']);
        $stmt->bindParam(':monthly_rent', $data['monthly_rent']);
        $stmt->bindParam(':deposit_amount', $data['deposit_amount']);
        $stmt->bindParam(':water_rate', $data['water_rate']);
        $stmt->bindParam(':electric_rate', $data['electric_rate']);
        $stmt->bindParam(':garbage_fee', $data['garbage_fee']);
        $stmt->bindParam(':contract_terms', $data['contract_terms']);
        $stmt->bindParam(':landlord_name', $data['landlord_name']);
        $stmt->bindParam(':landlord_id_card', $data['landlord_id_card']);
        $stmt->bindParam(':witness_name', $data['witness_name']);
        $stmt->bindParam(':witness_id_card', $data['witness_id_card']);
        $stmt->bindParam(':contract_status', $data['contract_status']);
        $stmt->bindParam(':created_by', $data['created_by']);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }
    
    // ดึงสัญญาทั้งหมด
    public function getAll($filters = []) {
        $query = "SELECT c.*, 
                         r.room_number, r.room_type,
                         t.full_name as tenant_name, t.phone, t.id_card,
                         u.full_name as created_by_name
                  FROM " . $this->table . " c
                  JOIN rooms r ON c.room_id = r.room_id
                  JOIN tenants t ON c.tenant_id = t.tenant_id
                  LEFT JOIN users u ON c.created_by = u.user_id
                  WHERE 1=1";
        
        if (isset($filters['status'])) {
            $query .= " AND c.contract_status = :status";
        }
        
        if (isset($filters['tenant_id'])) {
            $query .= " AND c.tenant_id = :tenant_id";
        }
        
        $query .= " ORDER BY c.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        
        if (isset($filters['status'])) {
            $stmt->bindParam(':status', $filters['status']);
        }
        
        if (isset($filters['tenant_id'])) {
            $stmt->bindParam(':tenant_id', $filters['tenant_id']);
        }
        
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // ดึงสัญญาตาม ID
    public function getById($contract_id) {
        $query = "SELECT c.*, 
                         r.room_number, r.room_type, r.floor,
                         t.full_name as tenant_name, t.phone, t.id_card as tenant_id_card,
                         t.line_id, t.emergency_contact, t.emergency_phone,
                         b.building_name, b.building_type, b.address as building_address
                  FROM " . $this->table . " c
                  JOIN rooms r ON c.room_id = r.room_id
                  JOIN tenants t ON c.tenant_id = t.tenant_id
                  LEFT JOIN buildings b ON r.building_id = b.building_id
                  WHERE c.contract_id = :contract_id 
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':contract_id', $contract_id);
        $stmt->execute();
        return $stmt->fetch();
    }
    
    // สร้างเลขสัญญาอัตโนมัติ
    public function generateContractNumber() {
        $year = date('Y') + 543; // พ.ศ.
        $month = date('m');
        
        // นับสัญญาในเดือนนี้
        $query = "SELECT COUNT(*) as count FROM " . $this->table . " 
                  WHERE YEAR(created_at) = YEAR(NOW()) 
                  AND MONTH(created_at) = MONTH(NOW())";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch();
        
        $runningNumber = str_pad($result['count'] + 1, 4, '0', STR_PAD_LEFT);
        
        return "CONTRACT-{$year}{$month}-{$runningNumber}";
    }
    
    // อัพเดทสถานะสัญญา
    public function updateStatus($contract_id, $status) {
        $query = "UPDATE " . $this->table . " 
                  SET contract_status = :status 
                  WHERE contract_id = :contract_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':contract_id', $contract_id);
        
        return $stmt->execute();
    }
    
    // ตรวจสอบสัญญาที่ใกล้หมดอายุ
    public function getExpiringSoon($days = 30) {
        $query = "SELECT c.*, 
                         r.room_number,
                         t.full_name as tenant_name, t.phone
                  FROM " . $this->table . " c
                  JOIN rooms r ON c.room_id = r.room_id
                  JOIN tenants t ON c.tenant_id = t.tenant_id
                  WHERE c.contract_status = 'active'
                  AND DATEDIFF(c.end_date, NOW()) <= :days
                  AND DATEDIFF(c.end_date, NOW()) >= 0
                  ORDER BY c.end_date ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':days', $days);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // ดึง Template เงื่อนไขสัญญา
    public function getDefaultTerms() {
        return "1. ผู้เช่าตกลงเช่าห้องพักตามสัญญาฉบับนี้ และชำระค่าเช่าภายในวันที่ 5 ของทุกเดือน

2. ผู้เช่าตกลงวางเงินประกันค่าเสียหายตามจำนวนที่ระบุในสัญญา

3. ค่าน้ำประปาคิดตามจำนวนหน่วยที่ใช้จริง ในอัตราหน่วยละ {water_rate} บาท

4. ค่าไฟฟ้าคิดตามจำนวนหน่วยที่ใช้จริง ในอัตราหน่วยละ {electric_rate} บาท

5. ค่าขยะคิดเหมาเดือนละ {garbage_fee} บาท

6. ผู้เช่าจะต้องดูแลรักษาห้องเช่าให้อยู่ในสภาพดี และไม่ทำให้เสียหาย

7. ห้ามนำสิ่งผิดกฎหมายเข้ามาในห้องเช่าโดยเด็ดขาด

8. หากผู้เช่าประสงค์จะยกเลิกสัญญาก่อนกำหนด จะต้องแจ้งล่วงหน้าอย่างน้อย 30 วัน

9. ผู้ให้เช่าสงวนสิทธิ์ในการเข้าตรวจสอบห้องเช่าได้ โดยแจ้งให้ทราบล่วงหน้า

10. สัญญานี้มีผลบังคับใช้ตั้งแต่วันที่เริ่มสัญญา จนถึงวันที่สิ้นสุดสัญญา";
    }
    
    // ลบสัญญา (เฉพาะที่ยังไม่ลงนาม)
    public function delete($contract_id) {
        $query = "DELETE FROM " . $this->table . " 
                  WHERE contract_id = :contract_id 
                  AND contract_status = 'draft'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':contract_id', $contract_id);
        return $stmt->execute();
    }
}
?>