<?php
// ============================================
// ไฟล์: member/create_maintenance.php
// คำอธิบาย: หน้าฟอร์มแจ้งซ่อมแยก (Standalone Form)
// ============================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../models/Maintenance.php';
require_once __DIR__ . '/../models/Tenant.php';

requireRole('member');

$database = new Database();
$db = $database->getConnection();

$maintenance = new Maintenance($db);
$tenant = new Tenant($db);

// ดึงข้อมูลผู้เช่า
$tenantData = $tenant->getByUserId($_SESSION['user_id']);

if (!$tenantData) {
    die('ไม่พบข้อมูลผู้เช่าในระบบ');
}

$message = '';
$messageType = '';

// จัดการการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $issue_type = $_POST['issue_type'];
    $issue_description = $_POST['issue_description'];
    $priority = $_POST['priority'];
    
    // 1. จัดการอัปโหลดรูปภาพ
    $images = [];
    $upload_error = false;
    
    if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
        $upload_dir = __DIR__ . '/../uploads/maintenance/';
        
        // สร้างโฟลเดอร์ถ้ายังไม่มี
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // ตรวจสอบจำนวนไฟล์ (เช่น ไม่เกิน 5 รูป)
        $total_files = count($_FILES['images']['name']);
        
        for ($i = 0; $i < $total_files; $i++) {
            $tmp_name = $_FILES['images']['tmp_name'][$i];
            $file_name = $_FILES['images']['name'][$i];
            $file_error = $_FILES['images']['error'][$i];
            
            if ($file_error == 0) {
                // ตรวจสอบนามสกุลไฟล์
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
                if (in_array($ext, $allowed)) {
                    $new_filename = uniqid() . '_' . time() . '_' . $i . '.' . $ext;
                    $destination = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($tmp_name, $destination)) {
                        $images[] = $new_filename;
                    }
                }
            }
        }
    }
    
    // 2. บันทึกลงฐานข้อมูล
    $request_number = $maintenance->generateRequestNumber();
    
    $data = [
        'room_id' => $tenantData['room_id'],
        'tenant_id' => $tenantData['tenant_id'],
        'request_number' => $request_number,
        'issue_type' => $issue_type,
        'issue_description' => $issue_description,
        'priority' => $priority,
        'images' => !empty($images) ? json_encode($images) : null,
        'request_status' => 'pending',
        'requested_by_user_id' => $_SESSION['user_id']
    ];
    
    if ($maintenance->create($data)) {
        // สำเร็จ -> กลับไปหน้ารายการพร้อมข้อความแจ้งเตือน
        header("Location: maintenance.php?status=success&req=" . $request_number);
        exit;
    } else {
        $message = 'เกิดข้อผิดพลาดในการบันทึกข้อมูล โปรดลองใหม่อีกครั้ง';
        $messageType = 'danger';
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แจ้งซ่อมใหม่ - ระบบจัดการหอพัก</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { background: #f5f7fa; }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .form-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: none;
            overflow: hidden;
        }
        .form-header {
            background: #f8f9fa;
            padding: 20px 25px;
            border-bottom: 1px solid #eee;
        }
        .preview-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
        }
        .preview-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #eee;
        }
        .upload-box {
            border: 2px dashed #cbd5e0;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #fafbfc;
        }
        .upload-box:hover {
            border-color: #667eea;
            background: #f0f4ff;
        }
        .upload-icon {
            font-size: 2rem;
            color: #a0aec0;
            margin-bottom: 10px;
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
                <i class="bi bi-person-circle"></i> <?php echo $_SESSION['full_name']; ?>
            </span>
        </div>
    </nav>

    <div class="container pb-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <a href="maintenance.php" class="btn btn-outline-secondary mb-3">
                    <i class="bi bi-arrow-left"></i> กลับไปหน้าประวัติ
                </a>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="card form-card">
                    <div class="form-header">
                        <h4 class="mb-0 text-primary">
                            <i class="bi bi-tools"></i> ฟอร์มแจ้งซ่อม
                        </h4>
                        <small class="text-muted">ห้อง <?php echo $tenantData['room_number']; ?></small>
                    </div>
                    
                    <div class="card-body p-4">
                        <form method="POST" enctype="multipart/form-data" id="maintenanceForm">
                            
                            <div class="mb-4">
                                <label class="form-label fw-bold">ประเภทปัญหา <span class="text-danger">*</span></label>
                                <div class="row g-2">
                                    <?php 
                                    $types = $maintenance->getIssueTypes();
                                    foreach ($types as $key => $value): 
                                    ?>
                                    <div class="col-6 col-md-4">
                                        <input type="radio" class="btn-check" name="issue_type" 
                                               id="type_<?php echo $key; ?>" value="<?php echo $key; ?>" required>
                                        <label class="btn btn-outline-secondary w-100 text-start" for="type_<?php echo $key; ?>">
                                            <?php echo $value; ?>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">ระดับความเร่งด่วน</label>
                                <select class="form-select" name="priority" required>
                                    <option value="normal" selected>ปกติ (รอได้)</option>
                                    <option value="high">สำคัญ (ควรรีบแก้ไข)</option>
                                    <option value="urgent">เร่งด่วน (ฉุกเฉิน)</option>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">รายละเอียดปัญหา <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="issue_description" rows="5" 
                                          placeholder="อธิบายอาการที่พบ ตำแหน่ง หรือข้อมูลเพิ่มเติม..." required></textarea>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">แนบรูปภาพประกอบ (ถ้ามี)</label>
                                <div class="upload-box" onclick="document.getElementById('fileInput').click()">
                                    <i class="bi bi-cloud-upload upload-icon"></i>
                                    <p class="mb-0 text-muted">คลิกเพื่อเลือกรูปภาพ</p>
                                    <small class="text-muted" style="font-size: 0.8rem;">รองรับ JPG, PNG (สูงสุด 5 รูป)</small>
                                </div>
                                <input type="file" id="fileInput" name="images[]" class="d-none" 
                                       accept="image/*" multiple onchange="previewImages(this)">
                                
                                <div id="imagePreview" class="preview-container"></div>
                            </div>

                            <hr>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-send-fill"></i> ส่งแจ้งซ่อม
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ฟังก์ชันแสดงตัวอย่างรูปภาพก่อนอัปโหลด
        function previewImages(input) {
            const container = document.getElementById('imagePreview');
            container.innerHTML = ''; // ล้างรูปเก่า

            if (input.files) {
                // จำกัดจำนวนไฟล์ที่ 5 รูป
                if(input.files.length > 5) {
                    alert("เลือกรูปได้สูงสุด 5 รูปครับ");
                    input.value = ""; // รีเซ็ตค่า
                    return;
                }

                Array.from(input.files).forEach(file => {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = 'preview-image animate__animated animate__fadeIn';
                        container.appendChild(img);
                    }
                    
                    reader.readAsDataURL(file);
                });
            }
        }
    </script>
</body>
</html>