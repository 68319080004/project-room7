<?php
// ============================================
// ไฟล์: admin/contracts_create.php
// คำอธิบาย: สร้างสัญญาเช่าอัตโนมัติ
// ============================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../models/Contract.php';
require_once __DIR__ . '/../models/Tenant.php';
require_once __DIR__ . '/../models/Room.php';
require_once __DIR__ . '/../models/Building.php';
require_once __DIR__ . '/../models/SystemSettings.php';

requireRole(['admin', 'owner']);

$database = new Database();
$db = $database->getConnection();

$contract = new Contract($db);
$tenant = new Tenant($db);
$room = new Room($db);
$building = new Building($db);
$settings = new SystemSettings($db);

$message = '';
$messageType = '';

// สร้างสัญญา
if (isset($_POST['create_contract'])) {
    $tenant_id = $_POST['tenant_id'];
    $tenantData = $tenant->getById($tenant_id);
    $roomData = $room->getById($tenantData['room_id']);
    
    // ดึง Rate จากอาคาร (ถ้ามี)
    $rates = [];
    if ($roomData['building_id']) {
        $rates = $building->getRates($roomData['building_id']);
    }
    
    // สร้างเลขสัญญา
    $contract_number = $contract->generateContractNumber();
    
    // คำนวณวันสิ้นสุดสัญญา
    $start_date = $_POST['start_date'];
    $duration_months = $_POST['duration_months'];
    $end_date = date('Y-m-d', strtotime("+{$duration_months} months", strtotime($start_date)));
    
    // แทนที่ค่าใน Terms
    $terms = $contract->getDefaultTerms();
    $terms = str_replace('{water_rate}', $rates['water_rate_per_unit'] ?? 18, $terms);
    $terms = str_replace('{electric_rate}', $rates['electric_rate_per_unit'] ?? 5, $terms);
    $terms = str_replace('{garbage_fee}', $rates['garbage_fee'] ?? 50, $terms);
    
    $contractData = [
        'tenant_id' => $tenant_id,
        'room_id' => $tenantData['room_id'],
        'contract_number' => $contract_number,
        'start_date' => $start_date,
        'end_date' => $end_date,
        'monthly_rent' => $roomData['monthly_rent'],
        'deposit_amount' => $_POST['deposit_amount'],
        'water_rate' => $rates['water_rate_per_unit'] ?? 18,
        'electric_rate' => $rates['electric_rate_per_unit'] ?? 5,
        'garbage_fee' => $rates['garbage_fee'] ?? 50,
        'contract_terms' => $terms,
        'landlord_name' => $_POST['landlord_name'],
        'landlord_id_card' => $_POST['landlord_id_card'],
        'witness_name' => $_POST['witness_name'] ?? '',
        'witness_id_card' => $_POST['witness_id_card'] ?? '',
        'contract_status' => 'draft',
        'created_by' => $_SESSION['user_id']
    ];
    
    $contract_id = $contract->create($contractData);
    
    if ($contract_id) {
        $message = "สร้างสัญญาสำเร็จ! เลขที่สัญญา: <strong>{$contract_number}</strong>";
        $messageType = 'success';
        header("Location: contract_view.php?id={$contract_id}");
        exit();
    } else {
        $message = 'เกิดข้อผิดพลาดในการสร้างสัญญา';
        $messageType = 'danger';
    }
}

// ดึงผู้เช่าที่ยังไม่มีสัญญา หรือสัญญาหมดอายุ
$activeTenants = $tenant->getAll(true);

// ดึงการตั้งค่าระบบ
$dormName = $settings->get('dormitory_name');
$dormAddress = $settings->get('dormitory_address');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สร้างสัญญาเช่า - ระบบจัดการหอพัก</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="bi bi-file-earmark-text"></i> สร้างสัญญาเช่าอัตโนมัติ
                    </h1>
                    <a href="contracts.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> กลับ
                    </a>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">
                                    <i class="bi bi-file-earmark-plus"></i> กรอกข้อมูลสัญญา
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" id="contractForm">
                                    <!-- เลือกผู้เช่า -->
                                    <div class="mb-4">
                                        <label class="form-label">
                                            <i class="bi bi-person"></i> เลือกผู้เช่า <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select form-select-lg" name="tenant_id" 
                                                id="tenantSelect" required>
                                            <option value="">-- เลือกผู้เช่า --</option>
                                            <?php foreach ($activeTenants as $t): ?>
                                                <option value="<?php echo $t['tenant_id']; ?>"
                                                        data-room="<?php echo $t['room_number']; ?>"
                                                        data-rent="<?php echo $t['monthly_rent']; ?>"
                                                        data-phone="<?php echo $t['phone']; ?>"
                                                        data-idcard="<?php echo $t['id_card']; ?>">
                                                    <?php echo $t['full_name']; ?> - ห้อง <?php echo $t['room_number']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div id="tenantInfo" class="mt-2 alert alert-info d-none"></div>
                                    </div>

                                    <hr>

                                    <!-- ข้อมูลสัญญา -->
                                    <h6 class="text-primary mb-3">
                                        <i class="bi bi-calendar-check"></i> ข้อมูลสัญญา
                                    </h6>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">วันที่เริ่มสัญญา <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" name="start_date" 
                                                   value="<?php echo date('Y-m-d'); ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">ระยะเวลาสัญญา (เดือน) <span class="text-danger">*</span></label>
                                            <select class="form-select" name="duration_months" required>
                                                <option value="6">6 เดือน</option>
                                                <option value="12" selected>12 เดือน (1 ปี)</option>
                                                <option value="24">24 เดือน (2 ปี)</option>
                                                <option value="36">36 เดือน (3 ปี)</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">เงินประกัน (บาท) <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" class="form-control" 
                                               name="deposit_amount" value="0" required>
                                        <small class="text-muted">ปกติคิด 1-2 เท่าของค่าเช่า</small>
                                    </div>

                                    <hr>

                                    <!-- ข้อมูลผู้ให้เช่า -->
                                    <h6 class="text-primary mb-3">
                                        <i class="bi bi-person-badge"></i> ข้อมูลผู้ให้เช่า
                                    </h6>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">ชื่อผู้ให้เช่า <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="landlord_name" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">เลขบัตรประชาชน <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="landlord_id_card" 
                                                   placeholder="X-XXXX-XXXXX-XX-X" required>
                                        </div>
                                    </div>

                                    <hr>

                                    <!-- ข้อมูลพยาน (ถ้ามี) -->
                                    <h6 class="text-primary mb-3">
                                        <i class="bi bi-people"></i> ข้อมูลพยาน (ถ้ามี)
                                    </h6>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">ชื่อพยาน</label>
                                            <input type="text" class="form-control" name="witness_name">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">เลขบัตรประชาชน</label>
                                            <input type="text" class="form-control" name="witness_id_card" 
                                                   placeholder="X-XXXX-XXXXX-XX-X">
                                        </div>
                                    </div>

                                    <div class="d-grid gap-2 mt-4">
                                        <button type="submit" name="create_contract" class="btn btn-success btn-lg">
                                            <i class="bi bi-file-earmark-check"></i> สร้างสัญญา
                                        </button>
                                        <a href="contracts.php" class="btn btn-secondary">
                                            <i class="bi bi-x-circle"></i> ยกเลิก
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- คำแนะนำ -->
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-lightbulb"></i> คำแนะนำ</h5>
                            </div>
                            <div class="card-body">
                                <h6 class="text-primary">📋 เกี่ยวกับสัญญา:</h6>
                                <ul class="small">
                                    <li>ระบบจะดึงข้อมูลห้อง ค่าเช่า และ Rate น้ำ-ไฟอัตโนมัติ</li>
                                    <li>สัญญาจะถูกสร้างเป็นแบบ Draft ก่อน</li>
                                    <li>สามารถแก้ไขและพิมพ์ได้ทันที</li>
                                </ul>

                                <h6 class="text-success mt-3">✅ สิ่งที่ควรเตรียม:</h6>
                                <ul class="small">
                                    <li>ข้อมูลผู้ให้เช่า (ชื่อ + เลขบัตรประชาชน)</li>
                                    <li>วันที่เริ่มสัญญา</li>
                                    <li>ระยะเวลาสัญญา (เดือน)</li>
                                    <li>จำนวนเงินประกัน</li>
                                </ul>

                                <h6 class="text-info mt-3">💡 เคล็ดลับ:</h6>
                                <ul class="small">
                                    <li>เงินประกันปกติคิด 1-2 เท่าของค่าเช่า</li>
                                    <li>สัญญามาตรฐานคือ 12 เดือน</li>
                                    <li>ควรมีพยานในการลงนาม (แนะนำ)</li>
                                </ul>
                            </div>
                        </div>

                        <div class="card bg-warning bg-opacity-10 mt-3">
                            <div class="card-body">
                                <h6 class="text-warning">
                                    <i class="bi bi-exclamation-triangle"></i> หมายเหตุ
                                </h6>
                                <p class="small mb-0">
                                    สัญญาที่สร้างจะอยู่ในสถานะ "Draft" ต้องลงนามและเปลี่ยนเป็น "Active" ถึงจะมีผลบังคับใช้
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('tenantSelect').addEventListener('change', function() {
            const selected = this.options[this.selectedIndex];
            const info = document.getElementById('tenantInfo');
            
            if (this.value) {
                const room = selected.getAttribute('data-room');
                const rent = selected.getAttribute('data-rent');
                const phone = selected.getAttribute('data-phone');
                
                info.innerHTML = `
                    <strong>ข้อมูลผู้เช่า:</strong><br>
                    <i class="bi bi-door-open"></i> ห้อง: <strong>${room}</strong><br>
                    <i class="bi bi-cash"></i> ค่าเช่า: <strong>฿${parseFloat(rent).toLocaleString()}/เดือน</strong><br>
                    <i class="bi bi-telephone"></i> โทร: ${phone}
                `;
                info.classList.remove('d-none');
                
                // แนะนำเงินประกัน (2 เท่าของค่าเช่า)
                document.querySelector('[name="deposit_amount"]').value = parseFloat(rent) * 2;
            } else {
                info.classList.add('d-none');
            }
        });
    </script>
</body>
</html>