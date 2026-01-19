<?php
class User {
    private $conn;
    private $table = "users";
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Login
    public function login($username, $password): bool {
        $query = "SELECT user_id, username, password, full_name, role, phone 
                  FROM " . $this->table . " 
                  WHERE username = :username AND is_active = 1 
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch();
            
            if (password_verify(password: $password, hash: $row['password'])) {
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['full_name'] = $row['full_name'];
                $_SESSION['role'] = $row['role'];
                $_SESSION['phone'] = $row['phone'];
                
                return true;
            }
        }
        
        return false;
    }
    
    // Logout
    public function logout(): bool {
        session_unset();
        session_destroy();
        return true;
    }
    
    // สร้าง User ใหม่ พร้อมสร้าง Tenant และอัปเดตสถานะห้อง
    public function create($username, $password, $full_name, $phone, $role = 'member', $room_id = null, $line_id = null, $facebook = null) {
        // ตรวจสอบชื่อผู้ใช้ซ้ำ
        $check = $this->conn->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
        $check->bindParam(':username', $username);
        $check->execute();
        if ($check->rowCount() > 0) {
            return false; // ชื่อผู้ใช้ซ้ำ
        }

        // แฮชรหัสผ่าน
        $hashed_password = password_hash(password: $password, algo: PASSWORD_DEFAULT);

        // สร้าง User
        $query = "INSERT INTO " . $this->table . " 
                  (username, password, full_name, phone, role, line_id, facebook) 
                  VALUES (:username, :password, :full_name, :phone, :role, :line_id, :facebook)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':full_name', $full_name);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':role', $role);
        $stmt->bindParam(':line_id', $line_id);
        $stmt->bindParam(':facebook', $facebook);

        if ($stmt->execute()) {
            $user_id = $this->conn->lastInsertId();

            // ถ้ามีการเลือกห้อง ให้สร้าง tenant และอัปเดตสถานะห้อง
            if ($room_id) {
                // สร้าง Tenant
                $queryTenant = "INSERT INTO tenants (user_id, room_id, full_name, phone, move_in_date, is_active)
                                VALUES (:user_id, :room_id, :full_name, :phone, NOW(), 1)";
                $stmtTenant = $this->conn->prepare($queryTenant);
                $stmtTenant->bindParam(':user_id', $user_id);
                $stmtTenant->bindParam(':room_id', $room_id);
                $stmtTenant->bindParam(':full_name', $full_name);
                $stmtTenant->bindParam(':phone', $phone);
                $stmtTenant->execute();

                // อัปเดตสถานะห้องเป็นไม่ว่าง
                $queryRoom = "UPDATE rooms SET room_status = 'occupied' WHERE room_id = :room_id";
                $stmtRoom = $this->conn->prepare($queryRoom);
                $stmtRoom->bindParam(':room_id', $room_id);
                $stmtRoom->execute();
            }

            return $user_id;
        }

        return false;
    }
    
    // ดึงข้อมูล User ตาม ID
    public function getById($user_id): mixed {
        $query = "SELECT * FROM " . $this->table . " WHERE user_id = :user_id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt->fetch();
    }
    
    // อัพเดทข้อมูล
    public function update($user_id, $data): mixed {
        $fields = [];
        foreach ($data as $key => $value) {
            if ($key !== 'user_id' && $key !== 'password') {
                $fields[] = "$key = :$key";
            }
        }
        
        $query = "UPDATE " . $this->table . " 
                  SET " . implode(', ', $fields) . " 
                  WHERE user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        
        foreach ($data as $key => $value) {
            if ($key !== 'user_id' && $key !== 'password') {
                $stmt->bindParam(':' . $key, $data[$key]);
            }
        }
        
        return $stmt->execute();
    }
    
    // เปลี่ยนรหัสผ่าน
    public function changePassword($user_id, $new_password): mixed {
        $query = "UPDATE " . $this->table . " 
                  SET password = :password 
                  WHERE user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        
        $hashed_password = password_hash(password: $new_password, algo: PASSWORD_DEFAULT);
        
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':user_id', $user_id);
        
        return $stmt->execute();
    }
    
    // ดึงรายการ User ทั้งหมด
    public function getAll($role = null) {
        $query = "SELECT user_id, username, full_name, phone, email, role, is_active, created_at 
                  FROM " . $this->table;
        
        if ($role) {
            $query .= " WHERE role = :role";
        }
        
        $query .= " ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        
        if ($role) {
            $stmt->bindParam(':role', $role);
        }
        
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
?>
