<?php
// ============================================
// ไฟล์: models/Invoice.php
// คำอธิบาย: Model สำหรับจัดการใบเสร็จ
// ===========================================
class Invoice {
    private $conn;
    private $table = "invoices";
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // สร้างใบเสร็จโดยใช้ Stored Procedure
    public function generateInvoice($room_id, $month, $year) {
        $query = "CALL calculate_invoice(:room_id, :month, :year)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':room_id', $room_id);
        $stmt->bindParam(':month', $month);
        $stmt->bindParam(':year', $year);
        return $stmt->execute();
    }
    
    // ดึงใบเสร็จทั้งหมด
    public function getAll($filters = []) {
        $query = "SELECT i.*, r.room_number, t.full_name as tenant_name, t.phone
                  FROM " . $this->table . " i
                  JOIN rooms r ON i.room_id = r.room_id
                  JOIN tenants t ON i.tenant_id = t.tenant_id
                  WHERE 1=1";
        
        if (isset($filters['month'])) {
            $query .= " AND i.invoice_month = :month";
        }
        
        if (isset($filters['year'])) {
            $query .= " AND i.invoice_year = :year";
        }
        
        if (isset($filters['status'])) {
            $query .= " AND i.payment_status = :status";
        }
        
        if (isset($filters['room_id'])) {
            $query .= " AND i.room_id = :room_id";
        }
        
        $query .= " ORDER BY i.invoice_year DESC, i.invoice_month DESC, r.room_number ASC";
        
        $stmt = $this->conn->prepare($query);
        
        if (isset($filters['month'])) {
            $stmt->bindParam(':month', $filters['month']);
        }
        if (isset($filters['year'])) {
            $stmt->bindParam(':year', $filters['year']);
        }
        if (isset($filters['status'])) {
            $stmt->bindParam(':status', $filters['status']);
        }
        if (isset($filters['room_id'])) {
            $stmt->bindParam(':room_id', $filters['room_id']);
        }
        
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // ดึงใบเสร็จตาม ID
    public function getById($invoice_id) {
        $query = "SELECT i.*, r.room_number, r.room_type, 
                         t.full_name as tenant_name, t.phone, t.line_id,
                         m.water_previous, m.water_current, m.water_usage,
                         m.electric_previous, m.electric_current, m.electric_usage
                  FROM " . $this->table . " i
                  JOIN rooms r ON i.room_id = r.room_id
                  JOIN tenants t ON i.tenant_id = t.tenant_id
                  LEFT JOIN meters m ON i.meter_id = m.meter_id
                  WHERE i.invoice_id = :invoice_id 
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':invoice_id', $invoice_id);
        $stmt->execute();
        return $stmt->fetch();
    }
    
    // ดึงใบเสร็จของผู้เช่า
    public function getByTenant($tenant_id) {
        $query = "SELECT i.*, r.room_number
                  FROM " . $this->table . " i
                  JOIN rooms r ON i.room_id = r.room_id
                  WHERE i.tenant_id = :tenant_id
                  ORDER BY i.invoice_year DESC, i.invoice_month DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':tenant_id', $tenant_id);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // อัพเดทสถานะการชำระ
    public function updatePaymentStatus($invoice_id, $status, $paid_amount = null, $paid_date = null) {
        $query = "UPDATE " . $this->table . " 
                  SET payment_status = :status";
        
        if ($paid_amount !== null) {
            $query .= ", paid_amount = :paid_amount";
        }
        
        if ($paid_date !== null) {
            $query .= ", paid_date = :paid_date";
        }
        
        $query .= " WHERE invoice_id = :invoice_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':invoice_id', $invoice_id);
        
        if ($paid_amount !== null) {
            $stmt->bindParam(':paid_amount', $paid_amount);
        }
        
        if ($paid_date !== null) {
            $stmt->bindParam(':paid_date', $paid_date);
        }
        
        return $stmt->execute();
    }
    
    // สรุปรายได้รายเดือน
    public function getMonthlySummary($month, $year) {
        $query = "SELECT 
                    COUNT(*) as total_invoices,
                    SUM(total_amount) as total_amount,
                    SUM(CASE WHEN payment_status = 'paid' THEN paid_amount ELSE 0 END) as total_paid,
                    SUM(CASE WHEN payment_status != 'paid' THEN total_amount ELSE 0 END) as total_unpaid
                  FROM " . $this->table . "
                  WHERE invoice_month = :month AND invoice_year = :year";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':month', $month);
        $stmt->bindParam(':year', $year);
        $stmt->execute();
        return $stmt->fetch();
    }
    
    // สรุปรายได้รายปี
    public function getYearlySummary($year) {
        $query = "SELECT 
                    invoice_month,
                    COUNT(*) as total_invoices,
                    SUM(total_amount) as total_amount,
                    SUM(CASE WHEN payment_status = 'paid' THEN paid_amount ELSE 0 END) as total_paid
                  FROM " . $this->table . "
                  WHERE invoice_year = :year
                  GROUP BY invoice_month
                  ORDER BY invoice_month ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':year', $year);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
?>