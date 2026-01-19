<?php
// models/Tax.php

class Tax {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // สร้างบันทึกภาษีปีใหม่
    public function createTaxYear($year, $user_id) {
        $sql = "INSERT INTO taxes (tax_year, created_by) VALUES (:year, :user_id)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':year' => $year, ':user_id' => $user_id]);
        return $this->pdo->lastInsertId();
    }
    
    // ดึงข้อมูลภาษีทั้งหมด
    public function getAll() {
        $sql = "SELECT t.*, u.full_name as created_by_name
                FROM taxes t
                LEFT JOIN users u ON t.created_by = u.user_id
                ORDER BY t.tax_year DESC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // ดึงข้อมูลตาม ID
    public function getById($tax_id) {
        $sql = "SELECT t.*, u.full_name as created_by_name
                FROM taxes t
                LEFT JOIN users u ON t.created_by = u.user_id
                WHERE t.tax_id = :tax_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':tax_id' => $tax_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // ดึงข้อมูลตามปี
    public function getByYear($year) {
        $sql = "SELECT * FROM taxes WHERE tax_year = :year";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':year' => $year]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // คำนวณภาษีใหม่
    public function calculateTax($tax_id) {
        // ดึงข้อมูลรายได้และค่าใช้จ่าย
        $income = $this->getTotalIncome($tax_id);
        $expense = $this->getTotalExpense($tax_id);
        $netIncome = $income - $expense;
        
        // คำนวณภาษีตามอัตรา
        $taxAmount = $this->calculateTaxAmount($netIncome);
        $taxRate = $netIncome > 0 ? ($taxAmount / $netIncome) * 100 : 0;
        
        // อัพเดทข้อมูล
        $sql = "UPDATE taxes 
                SET total_income = :income,
                    total_expenses = :expense,
                    net_income = :net_income,
                    tax_amount = :tax_amount,
                    tax_rate = :tax_rate
                WHERE tax_id = :tax_id";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':income' => $income,
            ':expense' => $expense,
            ':net_income' => $netIncome,
            ':tax_amount' => $taxAmount,
            ':tax_rate' => $taxRate,
            ':tax_id' => $tax_id
        ]);
    }
    
    // คำนวณจำนวนภาษีตามอัตรา
    private function calculateTaxAmount($netIncome) {
        if ($netIncome <= 0) return 0;
        
        $sql = "SELECT * FROM tax_brackets WHERE is_active = TRUE ORDER BY min_income";
        $stmt = $this->pdo->query($sql);
        $brackets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $taxAmount = 0;
        $remainingIncome = $netIncome;
        
        foreach ($brackets as $bracket) {
            $min = $bracket['min_income'];
            $max = $bracket['max_income'] ?? PHP_FLOAT_MAX;
            $rate = $bracket['tax_rate'] / 100;
            
            if ($remainingIncome <= 0) break;
            
            $taxableInThisBracket = 0;
            
            if ($netIncome > $max) {
                $taxableInThisBracket = $max - $min + 1;
            } else if ($netIncome > $min) {
                $taxableInThisBracket = $netIncome - $min;
            }
            
            $taxAmount += $taxableInThisBracket * $rate;
            $remainingIncome -= $taxableInThisBracket;
        }
        
        return round($taxAmount, 2);
    }
    
    // ดึงรายได้ทั้งหมด
    public function getTotalIncome($tax_id) {
        $sql = "SELECT COALESCE(SUM(amount), 0) as total FROM tax_incomes WHERE tax_id = :tax_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':tax_id' => $tax_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }
    
    // ดึงค่าใช้จ่ายทั้งหมด
    public function getTotalExpense($tax_id) {
        $sql = "SELECT COALESCE(SUM(amount), 0) as total FROM tax_expenses WHERE tax_id = :tax_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':tax_id' => $tax_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }
    
    // เพิ่มรายการรายได้
    public function addIncome($data) {
        $sql = "INSERT INTO tax_incomes (tax_id, income_type, description, amount, income_date, reference_id)
                VALUES (:tax_id, :type, :description, :amount, :date, :ref_id)";
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute([
            ':tax_id' => $data['tax_id'],
            ':type' => $data['income_type'],
            ':description' => $data['description'],
            ':amount' => $data['amount'],
            ':date' => $data['income_date'],
            ':ref_id' => $data['reference_id'] ?? null
        ]);
        
        if ($result) {
            $this->calculateTax($data['tax_id']);
        }
        
        return $result;
    }
    
    // เพิ่มรายการค่าใช้จ่าย
    public function addExpense($data) {
        $sql = "INSERT INTO tax_expenses (tax_id, expense_type, description, amount, expense_date, receipt_image, vendor_name)
                VALUES (:tax_id, :type, :description, :amount, :date, :receipt, :vendor)";
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute([
            ':tax_id' => $data['tax_id'],
            ':type' => $data['expense_type'],
            ':description' => $data['description'],
            ':amount' => $data['amount'],
            ':date' => $data['expense_date'],
            ':receipt' => $data['receipt_image'] ?? null,
            ':vendor' => $data['vendor_name'] ?? null
        ]);
        
        if ($result) {
            $this->calculateTax($data['tax_id']);
        }
        
        return $result;
    }
    
    // ดึงรายการรายได้
    public function getIncomes($tax_id) {
        $sql = "SELECT * FROM tax_incomes WHERE tax_id = :tax_id ORDER BY income_date DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':tax_id' => $tax_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // ดึงรายการค่าใช้จ่าย
    public function getExpenses($tax_id) {
        $sql = "SELECT * FROM tax_expenses WHERE tax_id = :tax_id ORDER BY expense_date DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':tax_id' => $tax_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // อัพเดทสถานะการชำระภาษี
    public function updatePaymentStatus($tax_id, $status, $paid_date = null) {
        $sql = "UPDATE taxes SET payment_status = :status";
        $params = [':status' => $status, ':tax_id' => $tax_id];
        
        if ($paid_date) {
            $sql .= ", paid_date = :paid_date";
            $params[':paid_date'] = $paid_date;
        }
        
        $sql .= " WHERE tax_id = :tax_id";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }
    
    // สถิติภาษีรายปี
    public function getYearlyStats() {
        $sql = "SELECT 
                    tax_year,
                    total_income,
                    total_expenses,
                    net_income,
                    tax_amount,
                    payment_status
                FROM taxes
                ORDER BY tax_year DESC
                LIMIT 10";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // สถิติรวม
    public function getSummaryStats() {
        $sql = "SELECT 
                    COUNT(*) as total_years,
                    SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) as paid_years,
                    SUM(CASE WHEN payment_status = 'pending' THEN 1 ELSE 0 END) as pending_years,
                    SUM(total_income) as total_income_all,
                    SUM(total_expenses) as total_expense_all,
                    SUM(net_income) as total_net_income,
                    SUM(tax_amount) as total_tax_amount
                FROM taxes";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // ลบรายการรายได้
    public function deleteIncome($income_id, $tax_id) {
        $sql = "DELETE FROM tax_incomes WHERE income_id = :income_id";
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute([':income_id' => $income_id]);
        
        if ($result) {
            $this->calculateTax($tax_id);
        }
        
        return $result;
    }
    
    // ลบรายการค่าใช้จ่าย
    public function deleteExpense($expense_id, $tax_id) {
        $sql = "DELETE FROM tax_expenses WHERE expense_id = :expense_id";
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute([':expense_id' => $expense_id]);
        
        if ($result) {
            $this->calculateTax($tax_id);
        }
        
        return $result;
    }
}