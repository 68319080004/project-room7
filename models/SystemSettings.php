<?php
// ============================================
// ไฟล์: models/SystemSettings.php
// คำอธิบาย: Model สำหรับจัดการการตั้งค่าระบบ
// ============================================

class SystemSettings {
    private $conn;
    private $table = "system_settings";
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // ดึงการตั้งค่าทั้งหมด
    public function getAll(): mixed {
        $query = "SELECT * FROM " . $this->table . " ORDER BY setting_key ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // ดึงค่าการตั้งค่าตาม key
    public function get($key): mixed {
        $query = "SELECT setting_value FROM " . $this->table . " 
                  WHERE setting_key = :key LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':key', $key);
        $stmt->execute();
        
        $result = $stmt->fetch();
        return $result ? $result['setting_value'] : null;
    }
    
    // อัพเดทการตั้งค่า
    public function update($key, $value, $updated_by): mixed {
        $query = "UPDATE " . $this->table . " 
                  SET setting_value = :value,
                      updated_by = :updated_by
                  WHERE setting_key = :key";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':value', $value);
        $stmt->bindParam(':updated_by', $updated_by);
        $stmt->bindParam(':key', $key);
        
        return $stmt->execute();
    }
    
    // อัพเดทหลายค่าพร้อมกัน
    public function updateMultiple($settings, $updated_by): bool {
        $this->conn->beginTransaction();
        
        try {
            foreach ($settings as $key => $value) {
                $this->update(key: $key, value: $value, updated_by: $updated_by);
            }
            
            $this->conn->commit();
            return true;
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }
}
?>