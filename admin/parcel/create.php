<?php
// admin/parcel/create.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../models/Parcel.php';

requireRole(['admin', 'staff', 'owner']);

$db = new Database();
$pdo = $db->getConnection();
$parcelModel = new Parcel($pdo);

// ดึงรายการบริษัทขนส่งและประเภทพัสดุ
$couriers = $parcelModel->getCourierCompanies();
$types = $parcelModel->getParcelTypes();

// ดึงรายการห้องที่มีคนเช่าอยู่ (เพื่อเอามาใส่ Dropdown)
$stmt = $pdo->query("SELECT r.room_id, r.room_number, t.full_name 
                     FROM rooms r 
                     JOIN tenants t ON r.room_id = t.room_id 
                     WHERE r.room_status = 'occupied' 
                     ORDER BY r.room_number ASC");
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

// บันทึกข้อมูล
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. หา tenant_id จาก room_id ที่เลือก
    $roomId = $_POST['room_id'];
    $tenantStmt = $pdo->prepare("SELECT tenant_id FROM tenants WHERE room_id = ? LIMIT 1");
    $tenantStmt->execute([$roomId]);
    $tenant = $tenantStmt->fetch();

    if ($tenant) {
        $data = [
            'room_id' => $roomId,
            'tenant_id' => $tenant['tenant_id'],
            'tracking_number' => $_POST['tracking_number'],
            'courier_company' => $_POST['courier_company'],
            'sender_name' => $_POST['sender_name'],
            'parcel_type' => $_POST['parcel_type'],
            'notes' => $_POST['notes'],
            'received_by_staff_id' => $_SESSION['user_id'],
            'parcel_status' => 'waiting'
        ];

        if ($parcelModel->create($data)) {
            echo "<script>alert('บันทึกพัสดุเรียบร้อย'); window.location='index.php';</script>";
        } else {
            $error = "เกิดข้อผิดพลาดในการบันทึก";
        }
    } else {
        $error = "ไม่พบผู้เช่าในห้องนี้ (ห้องอาจจะว่าง)";
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รับพัสดุใหม่ | ระบบจัดการหอพัก</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #4e73df;
            --success-color: #1cc88a;
            --danger-color: #e74a3b;
            --warning-color: #f6c23e;
            --info-color: #36b9cc;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .page-header {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .page-header h2 {
            color: #2d3748;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .page-header .icon-box {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary-color), var(--info-color));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }

        .form-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            animation: slideUp 0.5s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card-header-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 1.5rem 2rem;
            border: none;
        }

        .card-header-custom h4 {
            color: white;
            margin: 0;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-section {
            padding: 2rem;
        }

        .form-label {
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .form-label i {
            margin-right: 0.25rem;
            color: var(--primary-color);
        }

        .form-control, .form-select {
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.15);
        }

        .input-group-icon {
            position: relative;
        }

        .input-group-icon i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #a0aec0;
            z-index: 10;
        }

        .input-group-icon .form-control,
        .input-group-icon .form-select {
            padding-left: 2.75rem;
        }

        .required-star {
            color: var(--danger-color);
            font-weight: bold;
        }

        .btn-custom {
            padding: 0.75rem 2rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .btn-save {
            background: linear-gradient(135deg, var(--success-color), #17a673);
            color: white;
        }

        .btn-cancel {
            background: #e2e8f0;
            color: #4a5568;
        }

        .btn-cancel:hover {
            background: #cbd5e0;
            color: #2d3748;
        }

        .alert-custom {
            border-radius: 12px;
            border: none;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .info-box {
            background: linear-gradient(135deg, #e0f2fe 0%, #dbeafe 100%);
            border-left: 4px solid var(--info-color);
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .info-box i {
            color: var(--info-color);
            margin-right: 0.5rem;
        }

        .section-divider {
            border: none;
            height: 2px;
            background: linear-gradient(to right, transparent, #e2e8f0, transparent);
            margin: 2rem 0;
        }

        .form-footer {
            background: #f7fafc;
            padding: 1.5rem 2rem;
            border-top: 2px solid #e2e8f0;
        }

        @media (max-width: 768px) {
            .page-header {
                padding: 1.5rem;
            }

            .form-section {
                padding: 1.5rem;
            }

            .btn-custom {
                width: 100%;
                justify-content: center;
            }
        }

        /* Tooltip style */
        .form-text {
            font-size: 0.85rem;
            color: #718096;
            margin-top: 0.25rem;
        }
    </style>
</head>

<body>
    <div class="container py-4">
        <!-- Page Header -->
        <div class="page-header">
            <h2>
                <div class="icon-box">
                    <i class="bi bi-box-seam"></i>
                </div>
                <span>รับพัสดุเข้าระบบ</span>
            </h2>
        </div>

        <!-- Main Form Card -->
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="form-card">
                    <div class="card-header-custom">
                        <h4>
                            <i class="bi bi-clipboard-check"></i>
                            บันทึกข้อมูลพัสดุใหม่
                        </h4>
                    </div>

                    <div class="form-section">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger alert-custom">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <strong>เกิดข้อผิดพลาด:</strong> <?= $error ?>
                            </div>
                        <?php endif; ?>

                        <!-- Info Box -->
                        <div class="info-box">
                            <i class="bi bi-info-circle-fill"></i>
                            <strong>คำแนะนำ:</strong> กรอกข้อมูลพัสดุที่ได้รับให้ครบถ้วน เพื่อให้ผู้เช่าสามารถติดตามสถานะได้
                        </div>

                        <form method="post" id="parcelForm">
                            <!-- ข้อมูลห้องและผู้รับ -->
                            <h5 class="mb-3" style="color: #2d3748; font-weight: 600;">
                                <i class="bi bi-house-door text-primary"></i> ข้อมูลผู้รับ
                            </h5>

                            <div class="row mb-4">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">
                                        <i class="bi bi-door-open"></i>
                                        ห้องเลขที่ <span class="required-star">*</span>
                                    </label>
                                    <div class="input-group-icon">
                                        <i class="bi bi-building"></i>
                                        <select name="room_id" class="form-select" required>
                                            <option value="">-- เลือกห้องที่รับพัสดุ --</option>
                                            <?php foreach ($rooms as $room): ?>
                                                <option value="<?= $room['room_id'] ?>">
                                                    ห้อง <?= $room['room_number'] ?> - <?= $room['full_name'] ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <small class="form-text">เลือกห้องของผู้เช่าที่จะรับพัสดุ</small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">
                                        <i class="bi bi-upc-scan"></i>
                                        เลขพัสดุ (Tracking No.) <span class="required-star">*</span>
                                    </label>
                                    <div class="input-group-icon">
                                        <i class="bi bi-hash"></i>
                                        <input type="text" name="tracking_number" class="form-control"
                                            placeholder="ระบุเลข Tracking เช่น KERRY123456789" required>
                                    </div>
                                    <small class="form-text">หมายเลขติดตามพัสดุจากบริษัทขนส่ง</small>
                                </div>
                            </div>

                            <hr class="section-divider">

                            <!-- ข้อมูลการจัดส่ง -->
                            <h5 class="mb-3" style="color: #2d3748; font-weight: 600;">
                                <i class="bi bi-truck text-primary"></i> ข้อมูลการจัดส่ง
                            </h5>

                            <div class="row mb-4">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">
                                        <i class="bi bi-geo-alt"></i>
                                        บริษัทขนส่ง <span class="required-star">*</span>
                                    </label>
                                    <div class="input-group-icon">
                                        <i class="bi bi-truck"></i>
                                        <select name="courier_company" class="form-select" required>
                                            <?php foreach ($couriers as $val => $label): ?>
                                                <option value="<?= $val ?>"><?= $label ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">
                                        <i class="bi bi-person-badge"></i>
                                        ชื่อผู้ส่ง
                                    </label>
                                    <div class="input-group-icon">
                                        <i class="bi bi-person"></i>
                                        <input type="text" name="sender_name" class="form-control"
                                            placeholder="เช่น Shopee, Lazada, หรือชื่อผู้ส่ง">
                                    </div>
                                    <small class="form-text">ระบุแหล่งที่มา (ถ้ามี)</small>
                                </div>
                            </div>

                            <hr class="section-divider">

                            <!-- รายละเอียดเพิ่มเติม -->
                            <h5 class="mb-3" style="color: #2d3748; font-weight: 600;">
                                <i class="bi bi-card-list text-primary"></i> รายละเอียดเพิ่มเติม
                            </h5>

                            <div class="row mb-4">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">
                                        <i class="bi bi-box"></i>
                                        ประเภท/ขนาดพัสดุ <span class="required-star">*</span>
                                    </label>
                                    <div class="input-group-icon">
                                        <i class="bi bi-box-seam"></i>
                                        <select name="parcel_type" class="form-select" required>
                                            <?php foreach ($types as $val => $label): ?>
                                                <option value="<?= $val ?>"><?= $label ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">
                                        <i class="bi bi-chat-left-text"></i>
                                        หมายเหตุ
                                    </label>
                                    <div class="input-group-icon">
                                        <i class="bi bi-pencil"></i>
                                        <input type="text" name="notes" class="form-control"
                                            placeholder="เช่น กล่องบุบเล็กน้อย, ของเปราะบาง">
                                    </div>
                                    <small class="form-text">สภาพพัสดุหรือข้อมูลเพิ่มเติม</small>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Form Footer with Actions -->
                    <div class="form-footer">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <a href="index.php" class="btn btn-custom btn-cancel">
                                <i class="bi bi-x-circle"></i>
                                ยกเลิก
                            </a>
                            <button type="submit" form="parcelForm" class="btn btn-custom btn-save">
                                <i class="bi bi-check-circle"></i>
                                บันทึกพัสดุ
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation และ UX enhancements
        document.getElementById('parcelForm').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>กำลังบันทึก...';
        });

        // Auto-focus first input
        document.addEventListener('DOMContentLoaded', function() {
            const firstInput = document.querySelector('select[name="room_id"]');
            if (firstInput) firstInput.focus();
        });
    </script>
</body>

</html>