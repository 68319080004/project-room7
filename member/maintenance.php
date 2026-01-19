<?php
// ============================================
// ไฟล์: member/maintenance.php
// คำอธิบาย: หน้าแจ้งซ่อมสำหรับผู้เช่า (สวยงาม)
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

$tenantData = $tenant->getByUserId($_SESSION['user_id']);

if (!$tenantData) {
    die('ไม่พบข้อมูลผู้เช่า');
}

$message = '';
$messageType = '';

// บันทึกการแจ้งซ่อม
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_request'])) {
    $issue_type = $_POST['issue_type'];
    $issue_description = $_POST['issue_description'];
    $priority = $_POST['priority'];
    
    // จัดการไฟล์รูปภาพ
    $images = [];
    if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
        $upload_dir = __DIR__ . '/../uploads/maintenance/';
        
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['images']['error'][$key] == 0) {
                $filename = uniqid() . '_' . time() . '.jpg';
                $filepath = $upload_dir . $filename;
                
                if (move_uploaded_file($tmp_name, $filepath)) {
                    $images[] = $filename;
                }
            }
        }
    }
    
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
        $message = 'แจ้งซ่อมสำเร็จ! เลขที่: ' . $request_number;
        $messageType = 'success';
    } else {
        $message = 'เกิดข้อผิดพลาด';
        $messageType = 'danger';
    }
}

// ดึงประวัติแจ้งซ่อม
$requests = $maintenance->getAll(['tenant_id' => $tenantData['tenant_id']]);

// สถิติ
$stats = [
    'total' => 0,
    'pending' => 0,
    'in_progress' => 0,
    'completed' => 0
];

foreach ($requests as $r) {
    $stats['total']++;
$st = $r['request_status'];
if (isset($stats[$st])) {
    $stats[$st]++;
}}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แจ้งซ่อม - ระบบจัดการหอพัก</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { background: #f5f7fa; }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border: none;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
        }
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
        }
        .icon-primary { background: rgba(102, 126, 234, 0.1); color: #667eea; }
        .icon-warning { background: rgba(255, 159, 67, 0.1); color: #ff9f43; }
        .icon-info { background: rgba(0, 207, 232, 0.1); color: #00cfe8; }
        .icon-success { background: rgba(40, 199, 111, 0.1); color: #28c76f; }
        .request-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            transition: all 0.3s ease;
            border-left: 4px solid #e0e0e0;
        }
        .request-card:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .request-card.pending { border-left-color: #ff9f43; }
        .request-card.in_progress { border-left-color: #00cfe8; }
        .request-card.completed { border-left-color: #28c76f; }
        .image-preview {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        .image-preview:hover {
            transform: scale(1.05);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-dark sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-building"></i> ระบบจัดการหอพัก
            </a>
            <div class="d-flex align-items-center">
                <span class="text-white me-3">
                    <i class="bi bi-person-circle"></i> 
                    <?php echo $_SESSION['full_name']; ?>
                </span>
                <a href="../logout.php" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-box-arrow-right"></i> ออกจากระบบ
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4 pb-5">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">
                <i class="bi bi-tools text-primary"></i> แจ้งซ่อม
            </h2>
        
        </div>

        <div>
            <a href="dashboard.php" class="btn btn-outline-secondary me-2">
                <i class="bi bi-arrow-left"></i> กลับหน้าหลัก
            </a>

            <a href="create_maintenance.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> แจ้งซ่อมใหม่
            </a>
        </div>
    </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- สถิติ -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted mb-1 small">ทั้งหมด</p>
                            <h3 class="mb-0"><?php echo $stats['total']; ?></h3>
                        </div>
                        <div class="stat-icon icon-primary">
                            <i class="bi bi-list-ul"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted mb-1 small">รอดำเนินการ</p>
                            <h3 class="mb-0 text-warning"><?php echo $stats['pending']; ?></h3>
                        </div>
                        <div class="stat-icon icon-warning">
                            <i class="bi bi-clock-history"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted mb-1 small">กำลังดำเนินการ</p>
                            <h3 class="mb-0 text-info"><?php echo $stats['in_progress']; ?></h3>
                        </div>
                        <div class="stat-icon icon-info">
                            <i class="bi bi-gear"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted mb-1 small">เสร็จแล้ว</p>
                            <h3 class="mb-0 text-success"><?php echo $stats['completed']; ?></h3>
                        </div>
                        <div class="stat-icon icon-success">
                            <i class="bi bi-check-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
            <form method="GET" class="row g-2 mb-4">
    <div class="col-md-4">
        <input type="text" name="q" class="form-control"
               placeholder="ค้นหาเลขแจ้งซ่อม..."
               value="<?php echo $_GET['q'] ?? ''; ?>">
    </div>
    <div class="col-md-3">
        <select name="status" class="form-select">
            <option value="">ทุกสถานะ</option>
            <option value="pending">รอดำเนินการ</option>
            <option value="in_progress">กำลังซ่อม</option>
            <option value="completed">เสร็จแล้ว</option>
        </select>
    </div>
    <div class="col-md-3">
        <select name="priority" class="form-select">
            <option value="">ทุกระดับ</option>
            <option value="urgent">เร่งด่วน</option>
            <option value="high">สำคัญ</option>
            <option value="normal">ปกติ</option>
        </select>
    </div>
    <div class="col-md-2">
        <button class="btn btn-primary w-100">
            <i class="bi bi-search"></i> ค้นหา
        </button>
    </div>
</form>
        <!-- รายการแจ้งซ่อม -->
        <div class="card" style="border-radius: 15px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.08);">
            <div class="card-body p-4">
                <h5 class="mb-4">
                    <i class="bi bi-clock-history text-primary"></i> 
                    ประวัติการแจ้งซ่อม
                </h5>

                <?php if (count($requests) > 0): ?>
                    <?php foreach ($requests as $r): ?>
                        <div class="request-card <?php echo $r['request_status']; ?>">
                            <div class="row align-items-center">
                                <div class="col-md-2">
                                    <div class="text-center">
                                        <div class="mb-2">
                                            <?php
                                            $icons = [
                                                'plumbing' => 'droplet-fill text-info',
                                                'electrical' => 'lightning-fill text-warning',
                                                'air_condition' => 'snow text-primary',
                                                'furniture' => 'house-door text-success',
                                                'door_lock' => 'key text-danger',
                                                'internet' => 'wifi text-info',
                                                'pest_control' => 'bug text-danger',
                                                'cleaning' => 'brush text-success',
                                                'other' => 'tools text-secondary'
                                            ];
                                            $icon = $icons[$r['issue_type']] ?? 'tools';
                                            ?>
                                            <i class="bi bi-<?php echo $icon; ?>" style="font-size: 2rem;"></i>
                                        </div>
                                        <small class="text-muted"><?php echo $r['request_number']; ?></small>
                                    </div>
                                </div>

                                <div class="col-md-7">
                                    <h6 class="mb-1">
                                        <?php 
                                        $types = $maintenance->getIssueTypes();
                                        echo $types[$r['issue_type']] ?? $r['issue_type']; 
                                        ?>
                                        
                                        <?php if ($r['priority'] == 'urgent'): ?>
                                            <span class="badge bg-danger">เร่งด่วน!</span>
                                        <?php elseif ($r['priority'] == 'high'): ?>
                                            <span class="badge bg-warning">สำคัญ</span>
                                        <?php endif; ?>
                                    </h6>
                                    <p class="mb-1 text-muted"><?php echo $r['issue_description']; ?></p>
                                    <small class="text-muted">
                                        <i class="bi bi-calendar"></i> 
                                        <?php echo formatThaiDate($r['created_at']); ?>
                                    </small>
                                </div>

                                <div class="col-md-3 text-end">
                                    <?php if ($r['request_status'] == 'pending'): ?>
                                        <span class="badge bg-warning fs-6">รอดำเนินการ</span>
                                    <?php elseif ($r['request_status'] == 'in_progress'): ?>
                                        <span class="badge bg-info fs-6">กำลังซ่อม</span>
                                    <?php elseif ($r['request_status'] == 'completed'): ?>
                                        <span class="badge bg-success fs-6">เสร็จแล้ว</span>
                                    <?php endif; ?>
                                    
                                    <div class="mt-2">
                                        <button class="btn btn-sm btn-outline-primary" 
                                                onclick='viewDetail(<?php echo json_encode($r, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                                            <i class="bi bi-eye"></i> ดูรายละเอียด
                                        </button>
                                    </div>
                                    
                                    <?php if ($r['request_status'] == 'completed' && !$r['rating']): ?>
                                        <div class="mt-2">
                                            <button class="btn btn-sm btn-warning" 
                                                    onclick='rateRequest(<?php echo $r["request_id"]; ?>)'>
                                                <i class="bi bi-star"></i> ให้คะแนน
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                        <p class="mt-2">ยังไม่มีประวัติการแจ้งซ่อม</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal แจ้งซ่อมใหม่ -->
    <div class="modal fade" id="newRequestModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">
                            <i class="bi bi-plus-circle"></i> แจ้งซ่อมใหม่
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">ประเภทปัญหา <span class="text-danger">*</span></label>
                            <select class="form-select" name="issue_type" required>
                                <option value="">-- เลือกประเภท --</option>
                                <?php foreach ($maintenance->getIssueTypes() as $key => $value): ?>
                                    <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">ระดับความเร่งด่วน <span class="text-danger">*</span></label>
                            <select class="form-select" name="priority" required>
                                <option value="normal">ปกติ</option>
                                <option value="high">สำคัญ</option>
                                <option value="urgent">เร่งด่วน</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">รายละเอียดปัญหา <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="issue_description" rows="4" 
                                      placeholder="อธิบายปัญหาที่พบให้ละเอียด..." required></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">แนบรูปภาพ (ถ้ามี)</label>
                            <input type="file" class="form-control" name="images[]" 
                                   accept="image/*" multiple>
                            <small class="text-muted">สามารถเลือกได้หลายรูป (ไม่เกิน 5MB ต่อรูป)</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle"></i> ยกเลิก
                        </button>
                        <button type="submit" name="submit_request" class="btn btn-primary">
                            <i class="bi bi-send"></i> ส่งแจ้งซ่อม
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal ดูรายละเอียด -->
    <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-info-circle"></i> รายละเอียดการแจ้งซ่อม
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailContent">
                    <!-- จะถูกเติมด้วย JavaScript -->
                </div>
            </div>
        </div>
    </div>

    <!-- Modal ให้คะแนน -->
    <div class="modal fade" id="ratingModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="maintenance_rating.php">
                    <input type="hidden" name="request_id" id="rating_request_id">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title">
                            <i class="bi bi-star"></i> ให้คะแนนการซ่อม
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3 text-center">
                            <label class="form-label">คะแนนความพึงพอใจ</label>
                            <div class="rating-stars">
                                <i class="bi bi-star-fill text-muted" data-rating="1" style="font-size: 2rem; cursor: pointer;"></i>
                                <i class="bi bi-star-fill text-muted" data-rating="2" style="font-size: 2rem; cursor: pointer;"></i>
                                <i class="bi bi-star-fill text-muted" data-rating="3" style="font-size: 2rem; cursor: pointer;"></i>
                                <i class="bi bi-star-fill text-muted" data-rating="4" style="font-size: 2rem; cursor: pointer;"></i>
                                <i class="bi bi-star-fill text-muted" data-rating="5" style="font-size: 2rem; cursor: pointer;"></i>
                            </div>
                            <input type="hidden" name="rating" id="rating_value" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">ความคิดเห็นเพิ่มเติม</label>
                            <textarea class="form-control" name="feedback" rows="3" 
                                      placeholder="แสดงความคิดเห็น (ถ้ามี)"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-warning">ส่งคะแนน</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewDetail(request) {
            const types = <?php echo json_encode($maintenance->getIssueTypes()); ?>;
            const priorityText = {
                'normal': 'ปกติ',
                'high': 'สำคัญ',
                'urgent': 'เร่งด่วน'
            };
            const statusText = {
                'pending': 'รอดำเนินการ',
                'in_progress': 'กำลังซ่อม',
                'completed': 'เสร็จแล้ว'
            };
            
            let html = `
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <strong>เลขที่:</strong> ${request.request_number}
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>สถานะ:</strong> ${statusText[request.request_status]}
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>ประเภท:</strong> ${types[request.issue_type] || request.issue_type}
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>ความเร่งด่วน:</strong> ${priorityText[request.priority]}
                    </div>
                    <div class="col-12 mb-3">
                        <strong>รายละเอียด:</strong><br>
                        ${request.issue_description}
                    </div>
            `;
            
            if (request.images) {
                const images = JSON.parse(request.images);
                html += `<div class="col-12 mb-3"><strong>รูปภาพ:</strong><br>`;
                images.forEach(img => {
                    html += `<img src="../uploads/maintenance/${img}" class="image-preview m-1" 
                                  onclick="window.open(this.src, '_blank')">`;
                });
                html += `</div>`;
            }
            
            if (request.technician_notes) {
                html += `<div class="col-12 mb-3">
                    <div class="alert alert-info">
                        <strong><i class="bi bi-info-circle"></i> หมายเหตุช่าง:</strong><br>
                        ${request.technician_notes}
                    </div>
                </div>`;
            }
            
            html += `</div>`;
            
            document.getElementById('detailContent').innerHTML = html;
            new bootstrap.Modal(document.getElementById('detailModal')).show();
        }

        function rateRequest(requestId) {
            document.getElementById('rating_request_id').value = requestId;
            new bootstrap.Modal(document.getElementById('ratingModal')).show();
        }

        // ระบบให้คะแนนดาว
        document.querySelectorAll('.rating-stars i').forEach(star => {
            star.addEventListener('click', function() {
                const rating = this.getAttribute('data-rating');
                document.getElementById('rating_value').value = rating;
                
                document.querySelectorAll('.rating-stars i').forEach((s, i) => {
                    if (i < rating) {
                        s.classList.remove('text-muted');
                        s.classList.add('text-warning');
                    } else {
                        s.classList.remove('text-warning');
                        s.classList.add('text-muted');
                    }
                });
            });
            
            star.addEventListener('mouseover', function() {
                const rating = this.getAttribute('data-rating');
                document.querySelectorAll('.rating-stars i').forEach((s, i) => {
                    if (i < rating) {
                        s.classList.add('text-warning');
                    }
                });
            });
        });
        
        document.querySelector('.rating-stars').addEventListener('mouseout', function() {
            const currentRating = document.getElementById('rating_value').value;
            document.querySelectorAll('.rating-stars i').forEach((s, i) => {
                if (i < currentRating) {
                    s.classList.add('text-warning');
                } else {
                    s.classList.remove('text-warning');
                }
            });
        });
    </script>
</body>
</html>