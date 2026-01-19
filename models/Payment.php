<?php
// ============================================
// ไฟล์: models/Payment.php
// คำอธิบาย: Model สำหรับจัดการการชำระเงิน
// ============================================

class Payment {
    private $conn;
    private $table = "payments";
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // บันทึกการชำระเงิน
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                  (invoice_id, payment_amount, payment_method, payment_slip, 
                   payment_date, payment_time, bank_name, transfer_ref, note, payment_status) 
                  VALUES (:invoice_id, :payment_amount, :payment_method, :payment_slip,
                          :payment_date, :payment_time, :bank_name, :transfer_ref, :note, :payment_status)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':invoice_id', $data['invoice_id']);
        $stmt->bindParam(':payment_amount', $data['payment_amount']);
        $stmt->bindParam(':payment_method', $data['payment_method']);
        $stmt->bindParam(':payment_slip', $data['payment_slip']);
        $stmt->bindParam(':payment_date', $data['payment_date']);
        $stmt->bindParam(':payment_time', $data['payment_time']);
        $stmt->bindParam(':bank_name', $data['bank_name']);
        $stmt->bindParam(':transfer_ref', $data['transfer_ref']);
        $stmt->bindParam(':note', $data['note']);
        $stmt->bindParam(':payment_status', $data['payment_status']);
        
        if ($stmt->execute()) {
            $payment_id = $this->conn->lastInsertId();
            
            // อัพเดทสถานะใบเสร็จเป็น "checking"
            $updateInvoice = "UPDATE invoices SET payment_status = 'checking' WHERE invoice_id = :invoice_id";
            $stmtInvoice = $this->conn->prepare($updateInvoice);
            $stmtInvoice->bindParam(':invoice_id', $data['invoice_id']);
            $stmtInvoice->execute();
            
            return $payment_id;
        }
        
        return false;
    }
    
    // ดึงการชำระเงินทั้งหมด
    public function getAll($status = null) {
        $query = "SELECT p.*, i.invoice_number, i.total_amount, 
                         r.room_number, t.full_name as tenant_name
                  FROM " . $this->table . " p
                  JOIN invoices i ON p.invoice_id = i.invoice_id
                  JOIN rooms r ON i.room_id = r.room_id
                  JOIN tenants t ON i.tenant_id = t.tenant_id";
        
        if ($status) {
            $query .= " WHERE p.payment_status = :status";
        }
        
        $query .= " ORDER BY p.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        
        if ($status) {
            $stmt->bindParam(':status', $status);
        }
        
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // ดึงการชำระเงินตาม ID
    public function getById($payment_id) {
        $query = "SELECT p.*, i.invoice_number, i.total_amount,
                         r.room_number, t.full_name as tenant_name, t.phone
                  FROM " . $this->table . " p
                  JOIN invoices i ON p.invoice_id = i.invoice_id
                  JOIN rooms r ON i.room_id = r.room_id
                  JOIN tenants t ON i.tenant_id = t.tenant_id
                  WHERE p.payment_id = :payment_id 
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':payment_id', $payment_id);
        $stmt->execute();
        return $stmt->fetch();
    }
    
    // อนุมัติการชำระเงิน
    public function approve($payment_id, $verified_by) {
        $this->conn->beginTransaction();
        
        try {
            // อัพเดทสถานะการชำระเงิน
            $query = "UPDATE " . $this->table . " 
                      SET payment_status = 'approved',
                          verified_by = :verified_by,
                          verified_at = NOW()
                      WHERE payment_id = :payment_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':verified_by', $verified_by);
            $stmt->bindParam(':payment_id', $payment_id);
            $stmt->execute();
            
            // ดึงข้อมูลการชำระเงิน
            $payment = $this->getById($payment_id);
            
            // อัพเดทใบเสร็จ
            $updateInvoice = "UPDATE invoices 
                              SET payment_status = 'paid',
                                  paid_amount = :paid_amount,
                                  paid_date = :paid_date
                              WHERE invoice_id = :invoice_id";
            
            $stmtInvoice = $this->conn->prepare($updateInvoice);
            $stmtInvoice->bindParam(':paid_amount', $payment['payment_amount']);
            $stmtInvoice->bindParam(':paid_date', $payment['payment_date']);
            $stmtInvoice->bindParam(':invoice_id', $payment['invoice_id']);
            $stmtInvoice->execute();
            
            $this->conn->commit();
            return true;
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }
    
    // ปฏิเสธการชำระเงิน
    public function reject($payment_id, $verified_by) {
        $this->conn->beginTransaction();
        
        try {
            // อัพเดทสถานะการชำระเงิน
            $query = "UPDATE " . $this->table . " 
                      SET payment_status = 'rejected',
                          verified_by = :verified_by,
                          verified_at = NOW()
                      WHERE payment_id = :payment_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':verified_by', $verified_by);
            $stmt->bindParam(':payment_id', $payment_id);
            $stmt->execute();
            
            // ดึงข้อมูลการชำระเงิน
            $payment = $this->getById($payment_id);
            
            // อัพเดทใบเสร็จกลับเป็น pending
            $updateInvoice = "UPDATE invoices 
                              SET payment_status = 'pending'
                              WHERE invoice_id = :invoice_id";
            
            $stmtInvoice = $this->conn->prepare($updateInvoice);
            $stmtInvoice->bindParam(':invoice_id', $payment['invoice_id']);
            $stmtInvoice->execute();
            
            $this->conn->commit();
            return true;
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }
}
?>