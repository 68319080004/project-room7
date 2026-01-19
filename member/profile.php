<?php
// ============================================
// ไฟล์: member/profile.php
// คำอธิบาย: หน้าโปรไฟล์ (แก้ไขตัดส่วนราคาออกเพื่อแก้ Error)
// ============================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../models/Tenant.php';

requireRole('member');

$database = new Database();
$db = $database->getConnection();

$tenant = new Tenant($db);
$user_id = $_SESSION['user_id'];

// ดึงข้อมูลผู้เช่า + ข้อมูลห้อง
// แก้ไข: ตัด r.price หรือ r.room_price ออกก่อน เพื่อกัน Error
$query = "SELECT 
            t.*, 
            u.username, 
            u.email, 
            r.room_number, 
            b.building_name
          FROM tenants t
          JOIN users u ON t.user_id = u.user_id
          LEFT JOIN rooms r ON t.room_id = r.room_id
          LEFT JOIN buildings b ON r.building_id = b.building_id
          WHERE t.user_id = :user_id";

$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$profile) {
    die("ไม่พบข้อมูลผู้ใช้งาน");
}

$message = '';
$messageType = '';

// --- จัดการการอัปเดตข้อมูล ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // 1. กรณีแก้ไขเบอร์โทรศัพท์
    if (isset($_POST['update_info'])) {
        $new_phone = trim($_POST['phone']);
        
        if (!empty($new_phone)) {
            $updateSql = "UPDATE tenants SET phone = :phone WHERE user_id = :user_id";
            $updateStmt = $db->prepare($updateSql);
            $updateStmt->bindParam(':phone', $new_phone);
            $updateStmt->bindParam(':user_id', $user_id);
            
            if ($updateStmt->execute()) {
                $profile['phone'] = $new_phone;
                $message = "อัปเดตข้อมูลติดต่อเรียบร้อยแล้ว";
                $messageType = "success";
            } else {
                $message = "เกิดข้อผิดพลาดในการอัปเดต";
                $messageType = "danger";
            }
        }
    }

    // 2. กรณีเปลี่ยนรหัสผ่าน
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        $pwdQuery = "SELECT password FROM users WHERE user_id = :user_id";
        $pwdStmt = $db->prepare($pwdQuery);
        $pwdStmt->bindParam(':user_id', $user_id);
        $pwdStmt->execute();
        $userRow = $pwdStmt->fetch(PDO::FETCH_ASSOC);

        if (password_verify($current_password, $userRow['password'])) {
            if ($new_password === $confirm_password) {
                if (strlen($new_password) >= 6) {
                    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    
                    $updatePwdSql = "UPDATE users SET password = :password WHERE user_id = :user_id";
                    $updatePwdStmt = $db->prepare($updatePwdSql);
                    $updatePwdStmt->bindParam(':password', $new_password_hash);
                    $updatePwdStmt->bindParam(':user_id', $user_id);
                    
                    if ($updatePwdStmt->execute()) {
                        $message = "เปลี่ยนรหัสผ่านสำเร็จ";
                        $messageType = "success";
                    }
                } else {
                    $message = "รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย 6 ตัวอักษร";
                    $messageType = "warning";
                }
            } else {
                $message = "รหัสผ่านใหม่ไม่ตรงกัน";
                $messageType = "danger";
            }
        } else {
            $message = "รหัสผ่านปัจจุบันไม่ถูกต้อง";
            $messageType = "danger";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ข้อมูลส่วนตัว - ระบบจัดการหอพัก</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { background: #f5f7fa; }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .profile-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            border: none;
            overflow: hidden;
            margin-bottom: 20px;
        }
        .profile-header {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
            padding: 30px 20px;
            text-align: center;
        }
        .avatar-circle {
            width: 100px;
            height: 100px;
            background: white;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #667eea;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            margin-bottom: 15px;
        }
        .info-label {
            color: #6c757d;
            font-size: 0.9rem;
            font-weight: 500;
        }
        .info-value {
            font-weight: 600;
            color: #2d3748;
        }
        .rent-badge {
            background: #e2e8f0;
            color: #4a5568;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark sticky-top mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-building"></i> ระบบจัดการหอพัก
            </a>
            <span class="text-white">
                <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($profile['full_name']); ?>
            </span>
        </div>
    </nav>

    <div class="container pb-5">
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show mb-4" role="alert">
                <i class="bi <?php echo $messageType == 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'; ?>"></i>
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-4">
                <div class="profile-card">
                    <div class="profile-header">
                        <div class="avatar-circle">
                            <i class="bi bi-person-fill"></i>
                        </div>
                        <h5 class="mb-1"><?php echo htmlspecialchars($profile['full_name']); ?></h5>
                        <p class="text-muted mb-2">ผู้เช่า</p>
                        <span class="badge bg-primary rounded-pill px-3">
                            <i class="bi bi-key-fill"></i> ห้อง <?php echo htmlspecialchars($profile['room_number'] ?? '-'); ?>
                        </span>
                    </div>
                    <div class="card-body px-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="info-label">สถานะการเช่า</span>
                            <span class="badge bg-success">Active</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="info-label">วันที่เริ่มสัญญา</span>
                            <span class="info-value"><?php echo date('d/m/Y', strtotime($profile['move_in_date'])); ?></span>
                        </div>
                        
                        </div>
                </div>

                <div class="list-group profile-card">
                    <a href="dashboard.php" class="list-group-item list-group-item-action border-0 py-3">
                        <i class="bi bi-speedometer2 me-2 text-primary"></i> แดชบอร์ด
                    </a>
                    <a href="maintenance.php" class="list-group-item list-group-item-action border-0 py-3">
                        <i class="bi bi-tools me-2 text-warning"></i> ประวัติแจ้งซ่อม
                    </a>
                    <a href="../logout.php" class="list-group-item list-group-item-action border-0 py-3 text-danger">
                        <i class="bi bi-box-arrow-right me-2"></i> ออกจากระบบ
                    </a>
                </div>
            </div>

            <div class="col-lg-8">
                
                <div class="profile-card">
                    <div class="card-header bg-white border-bottom p-4">
                        <h5 class="mb-0 text-primary"><i class="bi bi-person-lines-fill"></i> ข้อมูลส่วนตัว</h5>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label info-label">เลขบัตรประชาชน</label>
                                    <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($profile['id_card']); ?>" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label info-label">ชื่อผู้ใช้งาน (Username)</label>
                                    <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($profile['username']); ?>" readonly>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label info-label">ที่อยู่ตามทะเบียนบ้าน</label>
                                    <textarea class="form-control bg-light" rows="2" readonly><?php echo htmlspecialchars($profile['address']); ?></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label info-label">เบอร์โทรศัพท์ <i class="bi bi-pencil-square text-muted"></i></label>
                                    <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($profile['phone']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label info-label">อีเมล</label>
                                    <input type="email" class="form-control bg-light" value="<?php echo htmlspecialchars($profile['email']); ?>" readonly>
                                    <div class="form-text text-muted">หากต้องการเปลี่ยนอีเมล กรุณาติดต่อเจ้าหน้าที่</div>
                                </div>
                            </div>
                            <div class="mt-4 text-end">
                                <button type="submit" name="update_info" class="btn btn-primary">
                                    <i class="bi bi-save"></i> บันทึกข้อมูล
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="profile-card">
                    <div class="card-header bg-white border-bottom p-4">
                        <h5 class="mb-0 text-danger"><i class="bi bi-shield-lock"></i> เปลี่ยนรหัสผ่าน</h5>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST">
                            <div class="row mb-3">
                                <label class="col-sm-4 col-form-label">รหัสผ่านปัจจุบัน</label>
                                <div class="col-sm-8">
                                    <input type="password" name="current_password" class="form-control" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label class="col-sm-4 col-form-label">รหัสผ่านใหม่</label>
                                <div class="col-sm-8">
                                    <input type="password" name="new_password" class="form-control" minlength="6" required>
                                </div>
                            </div>
                            <div class="row mb-4">
                                <label class="col-sm-4 col-form-label">ยืนยันรหัสผ่านใหม่</label>
                                <div class="col-sm-8">
                                    <input type="password" name="confirm_password" class="form-control" minlength="6" required>
                                </div>
                            </div>
                            <div class="text-end">
                                <button type="submit" name="change_password" class="btn btn-outline-danger">
                                    ยืนยันเปลี่ยนรหัสผ่าน
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>