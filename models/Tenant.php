<?php
class Tenant {
    private $conn;
    private $table = "tenants";
    
    public function __construct($db) {
        $this->conn = $db;
    }

    // ดึงผู้เช่าทั้งหมด
    public function getAll($active_only = true) {
        $sql = "SELECT t.*, r.room_number, r.room_type, r.monthly_rent
                FROM {$this->table} t
                LEFT JOIN rooms r ON t.room_id = r.room_id";

        if ($active_only) {
            $sql .= " WHERE t.is_active = 1";
        }

        $sql .= " ORDER BY r.room_number ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ดึงผู้เช่าตาม tenant_id
    public function getById($tenant_id) {
        $sql = "SELECT t.*, r.room_number, r.room_type, r.monthly_rent
                FROM {$this->table} t
                LEFT JOIN rooms r ON t.room_id = r.room_id
                WHERE t.tenant_id = :tenant_id
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':tenant_id', $tenant_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ดึงผู้เช่าตาม user_id (ใช้ใน dashboard)
    public function getByUserId($user_id) {
        $sql = "SELECT t.*, r.room_number, r.room_type, r.monthly_rent
                FROM {$this->table} t
                LEFT JOIN rooms r ON t.room_id = r.room_id
                WHERE t.user_id = :user_id AND t.is_active = 1
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ✅ สร้าง Tenant อย่างเดียว (ไม่แตะ rooms)
    public function create($data) {
        $sql = "INSERT INTO {$this->table}
                (user_id, room_id, full_name, phone, id_card, line_id, facebook,
                 emergency_contact, emergency_phone, move_in_date,
                 deposit_amount, discount_amount, is_active)
                VALUES
                (:user_id, :room_id, :full_name, :phone, :id_card, :line_id, :facebook,
                 :emergency_contact, :emergency_phone, :move_in_date,
                 :deposit_amount, :discount_amount, 1)";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':user_id' => $data['user_id'],
            ':room_id' => $data['room_id'],
            ':full_name' => $data['full_name'],
            ':phone' => $data['phone'],
            ':id_card' => $data['id_card'],
            ':line_id' => $data['line_id'],
            ':facebook' => $data['facebook'],
            ':emergency_contact' => $data['emergency_contact'],
            ':emergency_phone' => $data['emergency_phone'],
            ':move_in_date' => $data['move_in_date'],
            ':deposit_amount' => $data['deposit_amount'],
            ':discount_amount' => $data['discount_amount']
        ]);

        return $this->conn->lastInsertId();
    }

    // แก้ไขข้อมูลผู้เช่า
    public function update($tenant_id, $data) {
        $sql = "UPDATE {$this->table}
                SET full_name = :full_name,
                    phone = :phone,
                    line_id = :line_id,
                    facebook = :facebook,
                    emergency_contact = :emergency_contact,
                    emergency_phone = :emergency_phone,
                    discount_amount = :discount_amount
                WHERE tenant_id = :tenant_id";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':full_name' => $data['full_name'],
            ':phone' => $data['phone'],
            ':line_id' => $data['line_id'],
            ':facebook' => $data['facebook'],
            ':emergency_contact' => $data['emergency_contact'],
            ':emergency_phone' => $data['emergency_phone'],
            ':discount_amount' => $data['discount_amount'],
            ':tenant_id' => $tenant_id
        ]);
    }

    // ย้ายออก
    public function moveOut($tenant_id, $move_out_date) {
        $sql = "UPDATE {$this->table}
                SET is_active = 0, move_out_date = :move_out_date
                WHERE tenant_id = :tenant_id";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':move_out_date' => $move_out_date,
            ':tenant_id' => $tenant_id
        ]);

        return true;
    }
}
