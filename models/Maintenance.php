<?php
// ============================================
// ไฟล์: models/Maintenance.php
// คำอธิบาย: Model ระบบแจ้งซ่อม
// ============================================

class Maintenance {
    private $conn;
    private $table = "maintenance_requests";
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // สร้างรายการแจ้งซ่อม
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                  (room_id, tenant_id, request_number, issue_type, 
                   issue_description, priority, images, request_status, 
                   requested_by_user_id) 
                  VALUES 
                  (:room_id, :tenant_id, :request_number, :issue_type,
                   :issue_description, :priority, :images, :request_status,
                   :requested_by_user_id)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':room_id', $data['room_id']);
        $stmt->bindParam(':tenant_id', $data['tenant_id']);
        $stmt->bindParam(':request_number', $data['request_number']);
        $stmt->bindParam(':issue_type', $data['issue_type']);
        $stmt->bindParam(':issue_description', $data['issue_description']);
        $stmt->bindParam(':priority', $data['priority']);
        $stmt->bindParam(':images', $data['images']);
        $stmt->bindParam(':request_status', $data['request_status']);
        $stmt->bindParam(':requested_by_user_id', $data['requested_by_user_id']);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }
    
    // ดึงรายการทั้งหมด
    public function getAll($filters = []) {
        $query = "SELECT m.*, 
                         r.room_number, r.room_type,
                         t.full_name as tenant_name, t.phone,
                         u1.full_name as requested_by_name,
                         u2.full_name as assigned_to_name,
                         u3.full_name as completed_by_name
                  FROM " . $this->table . " m
                  JOIN rooms r ON m.room_id = r.room_id
                  LEFT JOIN tenants t ON m.tenant_id = t.tenant_id
                  LEFT JOIN users u1 ON m.requested_by_user_id = u1.user_id
                  LEFT JOIN users u2 ON m.assigned_to = u2.user_id
                  LEFT JOIN users u3 ON m.completed_by = u3.user_id
                  WHERE 1=1";
        
        if (isset($filters['status'])) {
            $query .= " AND m.request_status = :status";
        }
        
        if (isset($filters['priority'])) {
            $query .= " AND m.priority = :priority";
        }
        
        if (isset($filters['tenant_id'])) {
            $query .= " AND m.tenant_id = :tenant_id";
        }
        
        $query .= " ORDER BY 
                    CASE m.request_status 
                        WHEN 'pending' THEN 1 
                        WHEN 'in_progress' THEN 2 
                        ELSE 3 
                    END,
                    CASE m.priority 
                        WHEN 'urgent' THEN 1 
                        WHEN 'high' THEN 2 
                        WHEN 'normal' THEN 3 
                        ELSE 4 
                    END,
                    m.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        
        if (isset($filters['status'])) {
            $stmt->bindParam(':status', $filters['status']);
        }
        
        if (isset($filters['priority'])) {
            $stmt->bindParam(':priority', $filters['priority']);
        }
        
        if (isset($filters['tenant_id'])) {
            $stmt->bindParam(':tenant_id', $filters['tenant_id']);
        }
        
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // ดึงตาม ID
    public function getById($request_id) {
        $query = "SELECT m.*, 
                         r.room_number, r.room_type,
                         t.full_name as tenant_name, t.phone,
                         u1.full_name as requested_by_name,
                         u2.full_name as assigned_to_name
                  FROM " . $this->table . " m
                  JOIN rooms r ON m.room_id = r.room_id
                  LEFT JOIN tenants t ON m.tenant_id = t.tenant_id
                  LEFT JOIN users u1 ON m.requested_by_user_id = u1.user_id
                  LEFT JOIN users u2 ON m.assigned_to = u2.user_id
                  WHERE m.request_id = :request_id 
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':request_id', $request_id);
        $stmt->execute();
        return $stmt->fetch();
    }
    
    // สร้างเลขที่แจ้งซ่อม
    public function generateRequestNumber() {
        $year = date('Y');
        $month = date('m');
        
        $query = "SELECT COUNT(*) as count FROM " . $this->table . " 
                  WHERE YEAR(created_at) = YEAR(NOW()) 
                  AND MONTH(created_at) = MONTH(NOW())";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch();
        
        $runningNumber = str_pad($result['count'] + 1, 4, '0', STR_PAD_LEFT);
        
        return "MR{$year}{$month}-{$runningNumber}";
    }
    
    // อัพเดทสถานะ
    public function updateStatus($request_id, $status, $user_id, $notes = null) {
        $query = "UPDATE " . $this->table . " 
                  SET request_status = :status";
        
        if ($status == 'in_progress') {
            $query .= ", assigned_to = :user_id, started_at = NOW()";
        } elseif ($status == 'completed') {
            $query .= ", completed_by = :user_id, completed_at = NOW()";
        }
        
        if ($notes) {
            $query .= ", technician_notes = :notes";
        }
        
        $query .= " WHERE request_id = :request_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':request_id', $request_id);
        
        if ($status == 'in_progress' || $status == 'completed') {
            $stmt->bindParam(':user_id', $user_id);
        }
        
        if ($notes) {
            $stmt->bindParam(':notes', $notes);
        }
        
        return $stmt->execute();
    }
    
    // ให้คะแนน
    public function addRating($request_id, $rating, $feedback = null) {
        $query = "UPDATE " . $this->table . " 
                  SET rating = :rating, feedback = :feedback
                  WHERE request_id = :request_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':rating', $rating);
        $stmt->bindParam(':feedback', $feedback);
        $stmt->bindParam(':request_id', $request_id);
        
        return $stmt->execute();
    }
    
    // สถิติ
    public function getStats() {
        $query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN request_status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN request_status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                    SUM(CASE WHEN request_status = 'completed' THEN 1 ELSE 0 END) as completed,
                    AVG(CASE WHEN rating IS NOT NULL THEN rating END) as avg_rating,
                    AVG(CASE WHEN completed_at IS NOT NULL 
                        THEN TIMESTAMPDIFF(HOUR, created_at, completed_at) END) as avg_completion_hours
                  FROM " . $this->table;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch();
    }
    
    // ประเภทปัญหา
    public function getIssueTypes() {
        return [
            'plumbing' => 'ประปา/ท่อน้ำ',
            'electrical' => 'ไฟฟ้า',
            'air_condition' => 'เครื่องปรับอากาศ',
            'furniture' => 'เฟอร์นิเจอร์',
            'door_lock' => 'ประตู/กุญแจ',
            'internet' => 'อินเทอร์เน็ต',
            'pest_control' => 'แมลง/สัตว์รบกวน',
            'cleaning' => 'ความสะอาด',
            'other' => 'อื่นๆ'
        ];
    }
}
?>