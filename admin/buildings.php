<?php
// ============================================
// ไฟล์: admin/buildings.php
// คำอธิบาย: จัดการอาคาร/ทรัพย์สิน - ปรับปรุงใหม่
// ============================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../models/Building.php';

requireRole(['admin', 'owner']);

$database = new Database();
$db = $database->getConnection();
$building = new Building($db);

$message = '';
$messageType = '';

// เพิ่มอาคารใหม่
if (isset($_POST['add_building'])) {
    $data = [
        'building_name' => $_POST['building_name'],
        'building_type' => $_POST['building_type'],
        'water_rate_per_unit' => $_POST['water_rate_per_unit'],
        'water_minimum_unit' => $_POST['water_minimum_unit'],
        'water_minimum_charge' => $_POST['water_minimum_charge'],
        'electric_rate_per_unit' => $_POST['electric_rate_per_unit'],
        'garbage_fee' => $_POST['garbage_fee'],
        'address' => $_POST['address'] ?? '',
        'description' => $_POST['description'] ?? '',
        'created_by' => $_SESSION['user_id']
    ];
    
    if ($building->create($data)) {
        $message = 'เพิ่มอาคารสำเร็จ!';
        $messageType = 'success';
    } else {
        $message = 'เกิดข้อผิดพลาด';
        $messageType = 'danger';
    }
}

// แก้ไขอาคาร
if (isset($_POST['edit_building'])) {
    $data = [
        'building_name' => $_POST['building_name'],
        'building_type' => $_POST['building_type'],
        'water_rate_per_unit' => $_POST['water_rate_per_unit'],
        'water_minimum_unit' => $_POST['water_minimum_unit'],
        'water_minimum_charge' => $_POST['water_minimum_charge'],
        'electric_rate_per_unit' => $_POST['electric_rate_per_unit'],
        'garbage_fee' => $_POST['garbage_fee'],
        'address' => $_POST['address'] ?? '',
        'description' => $_POST['description'] ?? ''
    ];
    
    if ($building->update($_POST['building_id'], $data)) {
        $message = 'แก้ไขข้อมูลสำเร็จ!';
        $messageType = 'success';
    } else {
        $message = 'เกิดข้อผิดพลาด';
        $messageType = 'danger';
    }
}

// ปิดการใช้งาน/เปิดการใช้งาน
if (isset($_GET['action']) && isset($_GET['id'])) {
    if ($_GET['action'] == 'deactivate') {
        $building->deactivate($_GET['id']);
        $message = 'ปิดการใช้งานอาคารแล้ว';
        $messageType = 'warning';
    } elseif ($_GET['action'] == 'activate') {
        $building->activate($_GET['id']);
        $message = 'เปิดการใช้งานอาคารแล้ว';
        $messageType = 'success';
    }
}

// ดึงรายการอาคาร
$buildings = $building->getAll(false); // false = รวมทั้งที่ปิดการใช้งาน
$buildingTypes = $building->getBuildingTypes();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการอาคาร - ระบบจัดการหอพัก</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            --warning-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --info-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --danger-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }

        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .page-header {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .btn-primary-gradient {
            background: var(--primary-gradient);
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-primary-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .alert-custom {
            border: none;
            border-radius: 10px;
            padding: 1rem 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .building-card {
            border: none;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            height: 100%;
            background: white;
        }

        .building-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 35px rgba(0,0,0,0.15);
        }

        .building-card.inactive {
            opacity: 0.7;
            border: 3px solid #dc3545;
        }

        .building-header {
            background: var(--primary-gradient);
            color: white;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
        }

        .building-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: pulse 3s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.3; }
        }

        .building-header.inactive {
            background: var(--danger-gradient);
        }

        .building-icon {
            width: 60px;
            height: 60px;
            background: rgba(255,255,255,0.2);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 1rem;
            backdrop-filter: blur(10px);
        }

        .building-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }

        .building-type {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            margin-top: 0.5rem;
            backdrop-filter: blur(10px);
        }

        .building-stats {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 1rem;
            margin: 1.5rem;
            border-radius: 12px;
            display: flex;
            justify-content: space-around;
            gap: 1rem;
        }

        .stat-item {
            text-align: center;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-label {
            font-size: 0.75rem;
            color: #6c757d;
            margin-top: 0.3rem;
        }

        .rate-table {
            margin: 0 1.5rem 1.5rem;
        }

        .rate-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 1rem;
            margin-bottom: 0.5rem;
            background: #f8f9fa;
            border-radius: 10px;
            transition: all 0.2s;
        }

        .rate-row:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }

        .rate-icon {
            width: 35px;
            height: 35px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            margin-right: 0.8rem;
        }

        .rate-icon.water { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; }
        .rate-icon.electric { background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%); color: #333; }
        .rate-icon.garbage { background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); color: #333; }

        .rate-info {
            display: flex;
            align-items: center;
            flex: 1;
        }

        .rate-label {
            font-weight: 500;
            color: #495057;
        }

        .rate-value {
            font-weight: 700;
            font-size: 1.1rem;
            color: #212529;
        }

        .rate-detail {
            font-size: 0.75rem;
            color: #6c757d;
            margin-left: 0.5rem;
        }

        .building-footer {
            padding: 1.25rem 1.5rem;
            background: #f8f9fa;
            border-top: 2px solid #e9ecef;
            display: flex;
            gap: 0.5rem;
            justify-content: center;
        }

        .btn-action {
            border-radius: 8px;
            padding: 0.5rem 1.2rem;
            font-weight: 500;
            transition: all 0.3s;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }

        .btn-action:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .btn-action.btn-info {
            background: var(--info-gradient);
            color: white;
        }

        .btn-action.btn-warning {
            background: var(--warning-gradient);
            color: white;
        }

        .btn-action.btn-success {
            background: var(--success-gradient);
            color: white;
        }

        .modal-content {
            border: none;
            border-radius: 20px;
            overflow: hidden;
        }

        .modal-header {
            background: var(--primary-gradient);
            color: white;
            border: none;
            padding: 1.5rem;
        }

        .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }

        .section-header {
            color: #495057;
            font-weight: 600;
            margin: 1.5rem 0 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e9ecef;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
        }

        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 0.75rem;
            transition: all 0.3s;
        }

        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
        }

        .input-group-text {
            background: var(--primary-gradient);
            color: white;
            border: none;
            font-weight: 600;
        }

        .empty-state {
            padding: 5rem 2rem;
            text-align: center;
            background: white;
            border-radius: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        .empty-state i {
            font-size: 5rem;
            opacity: 0.2;
            margin-bottom: 1.5rem;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .empty-state h4 {
            color: #6c757d;
            margin-bottom: 1rem;
        }

        .building-address {
            margin: 0 1.5rem 1rem;
            padding: 0.75rem;
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            border-radius: 8px;
            font-size: 0.85rem;
            color: #856404;
        }

        .building-description {
            margin: 0 1.5rem 1rem;
            padding: 0.75rem;
            background: #d1ecf1;
            border-left: 4px solid #17a2b8;
            border-radius: 8px;
            font-size: 0.85rem;
            color: #0c5460;
        }

        .inactive-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(220, 53, 69, 0.9);
            color: white;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            z-index: 10;
            backdrop-filter: blur(10px);
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <!-- Header -->
                <div class="page-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="h2 mb-1">
                                <i class="bi bi-buildings text-primary"></i> จัดการอาคาร/ทรัพย์สิน
                            </h1>
                            <p class="text-muted mb-0">จัดการข้อมูลอาคารและอัตราค่าบริการต่างๆ</p>
                        </div>
                        <button type="button" class="btn btn-primary-gradient" data-bs-toggle="modal" data-bs-target="#addBuildingModal">
                            <i class="bi bi-plus-circle"></i> เพิ่มอาคารใหม่
                        </button>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?> alert-custom alert-dismissible fade show">
                        <i class="bi bi-<?php echo $messageType == 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill'; ?>"></i>
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- รายการอาคาร -->
                <?php if (count($buildings) > 0): ?>
                    <div class="row">
                        <?php foreach ($buildings as $b): ?>
                            <div class="col-xl-4 col-lg-6 mb-4">
                                <div class="building-card <?php echo !$b['is_active'] ? 'inactive' : ''; ?>">
                                    <?php if (!$b['is_active']): ?>
                                        <span class="inactive-badge">
                                            <i class="bi bi-x-circle"></i> ปิดใช้งาน
                                        </span>
                                    <?php endif; ?>
                                    
                                    <div class="building-header <?php echo !$b['is_active'] ? 'inactive' : ''; ?>">
                                        <div class="building-icon">
                                            <i class="bi bi-building"></i>
                                        </div>
                                        <h3 class="building-title"><?php echo $b['building_name']; ?></h3>
                                        <span class="building-type">
                                            <i class="bi bi-tag"></i> <?php echo $b['building_type']; ?>
                                        </span>
                                    </div>

                                    <div class="building-stats">
                                        <div class="stat-item">
                                            <div class="stat-value"><?php echo $b['total_rooms']; ?></div>
                                            <div class="stat-label">
                                                <i class="bi bi-door-closed"></i> ห้องทั้งหมด
                                            </div>
                                        </div>
                                        <div class="stat-item">
                                            <div class="stat-value"><?php echo $b['occupied_rooms']; ?></div>
                                            <div class="stat-label">
                                                <i class="bi bi-people"></i> เช่าแล้ว
                                            </div>
                                        </div>
                                        <div class="stat-item">
                                            <div class="stat-value"><?php echo $b['total_rooms'] - $b['occupied_rooms']; ?></div>
                                            <div class="stat-label">
                                                <i class="bi bi-door-open"></i> ว่าง
                                            </div>
                                        </div>
                                    </div>

                                    <div class="rate-table">
                                        <div class="rate-row">
                                            <div class="rate-info">
                                                <div class="rate-icon water">
                                                    <i class="bi bi-droplet-fill"></i>
                                                </div>
                                                <div>
                                                    <div class="rate-label">ค่าน้ำ</div>
                                                    <?php if ($b['water_minimum_charge'] > 0): ?>
                                                        <div class="rate-detail">
                                                            ขั้นต่ำ <?php echo $b['water_minimum_unit']; ?> ยูนิต = ฿<?php echo number_format($b['water_minimum_charge'], 2); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="rate-value">
                                                ฿<?php echo number_format($b['water_rate_per_unit'], 2); ?>/ยูนิต
                                            </div>
                                        </div>

                                        <div class="rate-row">
                                            <div class="rate-info">
                                                <div class="rate-icon electric">
                                                    <i class="bi bi-lightning-fill"></i>
                                                </div>
                                                <div class="rate-label">ค่าไฟ</div>
                                            </div>
                                            <div class="rate-value">
                                                ฿<?php echo number_format($b['electric_rate_per_unit'], 2); ?>/ยูนิต
                                            </div>
                                        </div>

                                        <div class="rate-row">
                                            <div class="rate-info">
                                                <div class="rate-icon garbage">
                                                    <i class="bi bi-trash"></i>
                                                </div>
                                                <div class="rate-label">ค่าขยะ</div>
                                            </div>
                                            <div class="rate-value">
                                                ฿<?php echo number_format($b['garbage_fee'], 2); ?>/เดือน
                                            </div>
                                        </div>
                                    </div>

                                    <?php if ($b['address']): ?>
                                        <div class="building-address">
                                            <i class="bi bi-geo-alt-fill"></i> <?php echo $b['address']; ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($b['description']): ?>
                                        <div class="building-description">
                                            <i class="bi bi-info-circle-fill"></i> <?php echo $b['description']; ?>
                                        </div>
                                    <?php endif; ?>

                                    <div class="building-footer">
                                        <button class="btn btn-action btn-info" 
                                                onclick='editBuilding(<?php echo json_encode($b, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                                            <i class="bi bi-pencil-square"></i> แก้ไข
                                        </button>
                                        
                                        <?php if ($b['is_active']): ?>
                                            <a href="?action=deactivate&id=<?php echo $b['building_id']; ?>" 
                                               class="btn btn-action btn-warning"
                                               onclick="return confirm('ยืนยันการปิดการใช้งานอาคาร <?php echo $b['building_name']; ?>?')">
                                                <i class="bi bi-x-circle"></i> ปิดใช้งาน
                                            </a>
                                        <?php else: ?>
                                            <a href="?action=activate&id=<?php echo $b['building_id']; ?>" 
                                               class="btn btn-action btn-success"
                                               onclick="return confirm('ยืนยันการเปิดการใช้งานอาคาร <?php echo $b['building_name']; ?>?')">
                                                <i class="bi bi-check-circle"></i> เปิดใช้งาน
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-buildings"></i>
                        <h4>ยังไม่มีอาคารในระบบ</h4>
                        <p class="text-muted">เริ่มต้นด้วยการเพิ่มอาคารแรกของคุณ</p>
                        <button type="button" class="btn btn-primary-gradient mt-3" data-bs-toggle="modal" data-bs-target="#addBuildingModal">
                            <i class="bi bi-plus-circle"></i> เพิ่มอาคารแรก
                        </button>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- Modal เพิ่มอาคาร -->
    <div class="modal fade" id="addBuildingModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-plus-circle"></i> เพิ่มอาคารใหม่
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label class="form-label">
                                    <i class="bi bi-building"></i> ชื่ออาคาร <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" name="building_name" placeholder="เช่น อาคาร A" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">
                                    <i class="bi bi-tag"></i> ประเภท <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" name="building_type" required>
                                    <?php foreach ($buildingTypes as $type): ?>
                                        <option value="<?php echo $type; ?>"><?php echo $type; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="section-header">
                            <i class="bi bi-calculator"></i> อัตราค่าบริการ
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">
                                    <i class="bi bi-droplet-fill text-info"></i> ค่าน้ำ/ยูนิต (บาท)
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">฿</span>
                                    <input type="number" step="0.01" class="form-control" 
                                           name="water_rate_per_unit" value="18.00" required>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">ยูนิตขั้นต่ำ</label>
                                <input type="number" step="0.01" class="form-control" 
                                       name="water_minimum_unit" value="0" placeholder="0">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">ค่าขั้นต่ำ (บาท)</label>
                                <div class="input-group">
                                    <span class="input-group-text">฿</span>
                                    <input type="number" step="0.01" class="form-control" 
                                           name="water_minimum_charge" value="0" placeholder="0">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="bi bi-lightning-fill text-warning"></i> ค่าไฟ/ยูนิต (บาท)
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">฿</span>
                                    <input type="number" step="0.01" class="form-control" 
                                           name="electric_rate_per_unit" value="5.00" required>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="bi bi-trash"></i> ค่าขยะ/เดือน (บาท)
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">฿</span>
                                    <input type="number" step="0.01" class="form-control" 
                                           name="garbage_fee" value="50.00" required>
                                </div>
                            </div>
                        </div>

                        <div class="section-header">
                            <i class="bi bi-info-circle"></i> ข้อมูลเพิ่มเติม
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                <i class="bi bi-geo-alt"></i> ที่อยู่
                            </label>
                            <textarea class="form-control" name="address" rows="2" placeholder="กรอกที่อยู่อาคาร..."></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                <i class="bi bi-card-text"></i> คำอธิบาย
                            </label>
                            <textarea class="form-control" name="description" rows="2" placeholder="รายละเอียดเพิ่มเติมเกี่ยวกับอาคาร..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle"></i> ยกเลิก
                        </button>
                        <button type="submit" name="add_building" class="btn btn-primary-gradient">
                            <i class="bi bi-check-circle"></i> บันทึก
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal แก้ไขอาคาร -->
    <div class="modal fade" id="editBuildingModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="building_id" id="edit_building_id">
                    <div class="modal-header" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                        <h5 class="modal-title">
                            <i class="bi bi-pencil-square"></i> แก้ไขข้อมูลอาคาร
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label class="form-label">
                                    <i class="bi bi-building"></i> ชื่ออาคาร <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" name="building_name" 
                                       id="edit_building_name" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">
                                    <i class="bi bi-tag"></i> ประเภท <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" name="building_type" id="edit_building_type" required>
                                    <?php foreach ($buildingTypes as $type): ?>
                                        <option value="<?php echo $type; ?>"><?php echo $type; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="section-header">
                            <i class="bi bi-calculator"></i> อัตราค่าบริการ
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">
                                    <i class="bi bi-droplet-fill text-info"></i> ค่าน้ำ/ยูนิต (บาท)
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">฿</span>
                                    <input type="number" step="0.01" class="form-control" 
                                           name="water_rate_per_unit" id="edit_water_rate" required>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">ยูนิตขั้นต่ำ</label>
                                <input type="number" step="0.01" class="form-control" 
                                       name="water_minimum_unit" id="edit_water_min_unit">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">ค่าขั้นต่ำ (บาท)</label>
                                <div class="input-group">
                                    <span class="input-group-text">฿</span>
                                    <input type="number" step="0.01" class="form-control" 
                                           name="water_minimum_charge" id="edit_water_min_charge">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="bi bi-lightning-fill text-warning"></i> ค่าไฟ/ยูนิต (บาท)
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">฿</span>
                                    <input type="number" step="0.01" class="form-control" 
                                           name="electric_rate_per_unit" id="edit_electric_rate" required>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="bi bi-trash"></i> ค่าขยะ/เดือน (บาท)
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">฿</span>
                                    <input type="number" step="0.01" class="form-control" 
                                           name="garbage_fee" id="edit_garbage_fee" required>
                                </div>
                            </div>
                        </div>

                        <div class="section-header">
                            <i class="bi bi-info-circle"></i> ข้อมูลเพิ่มเติม
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                <i class="bi bi-geo-alt"></i> ที่อยู่
                            </label>
                            <textarea class="form-control" name="address" id="edit_address" rows="2"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                <i class="bi bi-card-text"></i> คำอธิบาย
                            </label>
                            <textarea class="form-control" name="description" id="edit_description" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle"></i> ยกเลิก
                        </button>
                        <button type="submit" name="edit_building" class="btn btn-primary-gradient">
                            <i class="bi bi-check-circle"></i> บันทึกการแก้ไข
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editBuilding(building) {
            document.getElementById('edit_building_id').value = building.building_id;
            document.getElementById('edit_building_name').value = building.building_name;
            document.getElementById('edit_building_type').value = building.building_type;
            document.getElementById('edit_water_rate').value = building.water_rate_per_unit;
            document.getElementById('edit_water_min_unit').value = building.water_minimum_unit;
            document.getElementById('edit_water_min_charge').value = building.water_minimum_charge;
            document.getElementById('edit_electric_rate').value = building.electric_rate_per_unit;
            document.getElementById('edit_garbage_fee').value = building.garbage_fee;
            document.getElementById('edit_address').value = building.address || '';
            document.getElementById('edit_description').value = building.description || '';
            
            new bootstrap.Modal(document.getElementById('editBuildingModal')).show();
        }
    </script>
</body>
</html>