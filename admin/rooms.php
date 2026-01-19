<?php
// ============================================
// ไฟล์: admin/rooms.php
// คำอธิบาย: จัดการห้องเช่า (เพิ่ม/ลบ/แก้ไข) - ปรับปรุงใหม่
// ============================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../models/Room.php';
require_once __DIR__ . '/../models/Building.php';

requireRole(['admin', 'owner']);

$database = new Database();
$db = $database->getConnection();
$room = new Room($db);
$building = new Building($db);

$message = '';
$messageType = '';

// เพิ่มห้องใหม่
if (isset($_POST['add_room'])) {
    $data = [
        'room_number' => $_POST['room_number'],
        'room_type' => $_POST['room_type'],
        'monthly_rent' => $_POST['monthly_rent'],
        'room_status' => 'available',
        'floor' => $_POST['floor'],
        'description' => $_POST['description'] ?? '',
        'building_id' => $_POST['building_id']
    ];

    if ($room->create($data)) {
        $message = 'เพิ่มห้องสำเร็จ!';
        $messageType = 'success';
    } else {
        $message = 'เกิดข้อผิดพลาด หรือเลขห้องซ้ำ';
        $messageType = 'danger';
    }
}

// แก้ไขห้อง
if (isset($_POST['edit_room'])) {
    $data = [
        'room_number' => $_POST['room_number'],
        'room_type' => $_POST['room_type'],
        'monthly_rent' => $_POST['monthly_rent'],
        'room_status' => $_POST['room_status'],
        'floor' => $_POST['floor'],
        'description' => $_POST['description'] ?? '',
        'building_id' => $_POST['building_id']
    ];

    if ($room->update($_POST['room_id'], $data)) {
        $message = 'แก้ไขข้อมูลสำเร็จ!';
        $messageType = 'success';
    } else {
        $message = 'เกิดข้อผิดพลาด';
        $messageType = 'danger';
    }
}

// ลบห้อง
if (isset($_GET['delete']) && $_SESSION['role'] == 'owner') {
    if ($room->delete($_GET['delete'])) {
        $message = 'ลบห้องสำเร็จ!';
        $messageType = 'success';
    } else {
        $message = 'ไม่สามารถลบห้องที่มีผู้เช่าได้';
        $messageType = 'danger';
    }
}

// ดึงรายการห้องทั้งหมด
$rooms = $room->getAll();
$roomStats = $room->countByStatus();
$buildings = $building->getAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการห้องเช่า - ระบบจัดการหอพัก</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            --warning-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
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

        .stat-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            overflow: hidden;
            position: relative;
            height: 100%;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            opacity: 0.1;
            background: linear-gradient(135deg, rgba(255,255,255,0.3) 0%, rgba(255,255,255,0) 100%);
        }

        .stat-card.primary { background: var(--primary-gradient); }
        .stat-card.success { background: var(--success-gradient); }
        .stat-card.warning { background: var(--warning-gradient); }
        .stat-card.danger { background: var(--danger-gradient); }

        .stat-icon {
            font-size: 3rem;
            opacity: 0.2;
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }

        .stat-label {
            font-size: 0.95rem;
            opacity: 0.95;
            font-weight: 500;
        }

        .main-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .card-header-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.25rem;
            border: none;
            font-weight: 600;
        }

        .table-modern {
            margin: 0;
        }

        .table-modern thead th {
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            color: #495057;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 1rem;
            white-space: nowrap;
        }

        .table-modern tbody td {
            padding: 1rem;
            vertical-align: middle;
        }

        .table-modern tbody tr {
            transition: all 0.2s;
            border-bottom: 1px solid #f0f0f0;
        }

        .table-modern tbody tr:hover {
            background: linear-gradient(to right, rgba(102, 126, 234, 0.05), transparent);
            transform: scale(1.01);
        }

        .room-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 10px;
            font-weight: 700;
            font-size: 1.1rem;
            min-width: 70px;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
        }

        .badge-status {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }

        .badge-status.occupied {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
        }

        .badge-status.available {
            background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
            color: #333;
        }

        .badge-status.maintenance {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            color: white;
        }

        .btn-action {
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-weight: 500;
            transition: all 0.3s;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }

        .btn-action:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .btn-action.btn-info {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
        }

        .btn-action.btn-danger {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            color: white;
        }

        .btn-primary-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
        }

        .modal-content {
            border: none;
            border-radius: 15px;
            overflow: hidden;
        }

        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
        }

        .modal-header .btn-close {
            filter: brightness(0) invert(1);
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

        .alert-custom {
            border: none;
            border-radius: 10px;
            padding: 1rem 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .search-box {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .search-box input {
            padding-left: 2.5rem;
        }

        .search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }

        .filter-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-bottom: 1.5rem;
        }

        .filter-btn {
            padding: 0.5rem 1rem;
            border: 2px solid #e9ecef;
            background: white;
            border-radius: 20px;
            transition: all 0.3s;
            font-weight: 500;
        }

        .filter-btn:hover, .filter-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: transparent;
        }

        .tenant-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .tenant-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .empty-state {
            padding: 4rem 2rem;
            text-align: center;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 4rem;
            opacity: 0.3;
            margin-bottom: 1rem;
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
                            <i class="bi bi-door-open text-primary"></i> จัดการห้องเช่า
                        </h1>
                        <p class="text-muted mb-0">จัดการข้อมูลห้องพัก เพิ่ม แก้ไข และติดตามสถานะ</p>
                    </div>
                    <button type="button" class="btn btn-primary-gradient" data-bs-toggle="modal" data-bs-target="#addRoomModal">
                        <i class="bi bi-plus-circle"></i> เพิ่มห้องใหม่
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

            <!-- สถิติห้อง -->
            <div class="row g-4 mb-4">
                <div class="col-lg-3 col-md-6">
                    <div class="card stat-card primary text-white">
                        <div class="card-body position-relative">
                            <i class="bi bi-building stat-icon"></i>
                            <p class="stat-label mb-1">ห้องทั้งหมด</p>
                            <h2 class="stat-value"><?php echo count($rooms); ?></h2>
                            <small style="opacity: 0.9;">
                                <i class="bi bi-layers"></i> ห้องในระบบ
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="card stat-card success text-white">
                        <div class="card-body position-relative">
                            <i class="bi bi-check-circle-fill stat-icon"></i>
                            <p class="stat-label mb-1">เช่าแล้ว</p>
                            <h2 class="stat-value"><?php echo $roomStats['occupied'] ?? 0; ?></h2>
                            <small style="opacity: 0.9;">
                                <i class="bi bi-people"></i> มีผู้เช่าอยู่
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="card stat-card warning text-white">
                        <div class="card-body position-relative">
                            <i class="bi bi-door-open-fill stat-icon"></i>
                            <p class="stat-label mb-1">ห้องว่าง</p>
                            <h2 class="stat-value"><?php echo $roomStats['available'] ?? 0; ?></h2>
                            <small style="opacity: 0.9;">
                                <i class="bi bi-arrow-right-circle"></i> พร้อมให้เช่า
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="card stat-card danger text-white">
                        <div class="card-body position-relative">
                            <i class="bi bi-tools stat-icon"></i>
                            <p class="stat-label mb-1">ซ่อมแซม</p>
                            <h2 class="stat-value"><?php echo $roomStats['maintenance'] ?? 0; ?></h2>
                            <small style="opacity: 0.9;">
                                <i class="bi bi-wrench"></i> อยู่ระหว่างแก้ไข
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ตารางห้อง -->
            <div class="card main-card">
                <div class="card-header-custom">
                    <h5 class="mb-0"><i class="bi bi-list-ul"></i> รายการห้องทั้งหมด</h5>
                </div>
                <div class="card-body">
                    <!-- ค้นหาและกรอง -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="search-box">
                                <i class="bi bi-search"></i>
                                <input type="text" class="form-control" id="searchRoom" placeholder="ค้นหาห้อง, ผู้เช่า, อาคาร...">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="filter-buttons">
                                <button class="filter-btn active" data-filter="all">
                                    <i class="bi bi-grid"></i> ทั้งหมด
                                </button>
                                <button class="filter-btn" data-filter="available">
                                    <i class="bi bi-door-open"></i> ว่าง
                                </button>
                                <button class="filter-btn" data-filter="occupied">
                                    <i class="bi bi-check-circle"></i> เช่าแล้ว
                                </button>
                                <button class="filter-btn" data-filter="maintenance">
                                    <i class="bi bi-tools"></i> ซ่อมแซม
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-modern" id="roomsTable">
                            <thead>
                                <tr>
                                    <th><i class="bi bi-hash"></i> ห้อง</th>
                                    <th><i class="bi bi-tag"></i> ประเภท</th>
                                    <th><i class="bi bi-building"></i> ชั้น</th>
                                    <th><i class="bi bi-cash"></i> ค่าเช่า/เดือน</th>
                                    <th><i class="bi bi-circle"></i> สถานะ</th>
                                    <th><i class="bi bi-geo-alt"></i> อาคาร</th>
                                    <th><i class="bi bi-person"></i> ผู้เช่า</th>
                                    <th class="text-center"><i class="bi bi-gear"></i> จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($rooms) > 0): ?>
                                    <?php foreach ($rooms as $r): ?>
                                        <tr data-status="<?php echo $r['room_status']; ?>">
                                            <td>
                                                <span class="room-number"><?php echo $r['room_number']; ?></span>
                                            </td>
                                            <td>
                                                <i class="bi bi-<?php echo $r['room_type'] == 'แอร์' ? 'snow' : 'fan'; ?>"></i>
                                                <strong><?php echo $r['room_type']; ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark">
                                                    <i class="bi bi-layers"></i> ชั้น <?php echo $r['floor']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <strong class="text-primary">฿<?php echo formatMoney($r['monthly_rent']); ?></strong>
                                            </td>
                                            <td>
                                                <?php if ($r['room_status'] == 'occupied'): ?>
                                                    <span class="badge-status occupied">
                                                        <i class="bi bi-check-circle-fill"></i> เช่าแล้ว
                                                    </span>
                                                <?php elseif ($r['room_status'] == 'available'): ?>
                                                    <span class="badge-status available">
                                                        <i class="bi bi-door-open"></i> ว่าง
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge-status maintenance">
                                                        <i class="bi bi-tools"></i> ซ่อมแซม
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <i class="bi bi-building text-muted"></i>
                                                <?php echo $r['building_name'] ?? '-'; ?>
                                            </td>
                                            <td>
                                                <?php if ($r['tenant_name']): ?>
                                                    <div class="tenant-info">
                                                        <div class="tenant-avatar">
                                                            <?php echo mb_substr($r['tenant_name'], 0, 1); ?>
                                                        </div>
                                                        <span><?php echo $r['tenant_name']; ?></span>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <button class="btn btn-action btn-sm btn-info" 
                                                        onclick="editRoom(<?php echo htmlspecialchars(json_encode($r)); ?>)"
                                                        title="แก้ไข">
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>
                                                <?php if ($_SESSION['role'] == 'owner' && $r['room_status'] == 'available'): ?>
                                                    <a href="?delete=<?php echo $r['room_id']; ?>" 
                                                       class="btn btn-action btn-sm btn-danger"
                                                       onclick="return confirm('ยืนยันการลบห้อง <?php echo $r['room_number']; ?>?')"
                                                       title="ลบ">
                                                        <i class="bi bi-trash3"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="empty-state">
                                            <i class="bi bi-inbox"></i>
                                            <p class="mb-0">ยังไม่มีข้อมูลห้องในระบบ</p>
                                            <button class="btn btn-primary-gradient mt-3" data-bs-toggle="modal" data-bs-target="#addRoomModal">
                                                <i class="bi bi-plus-circle"></i> เพิ่มห้องแรก
                                            </button>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal เพิ่มห้อง -->
<div class="modal fade" id="addRoomModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle"></i> เพิ่มห้องใหม่</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-hash"></i> เลขห้อง <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" name="room_number" placeholder="เช่น 101" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-tag"></i> ประเภทห้อง <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" name="room_type" required>
                                <option value="">-- เลือกประเภท --</option>
                                <option value="แอร์">แอร์</option>
                                <option value="พัดลม">พัดลม</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-layers"></i> ชั้น <span class="text-danger">*</span>
                            </label>
                            <input type="number" class="form-control" name="floor" placeholder="เช่น 1" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-cash"></i> ค่าเช่า/เดือน <span class="text-danger">*</span>
                            </label>
                            <input type="number" step="0.01" class="form-control" name="monthly_rent" placeholder="3000" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-building"></i> เลือกอาคาร <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" name="building_id" required>
                            <option value="">-- เลือกอาคาร --</option>
                            <?php foreach ($buildings as $b): ?>
                                <option value="<?php echo $b['building_id']; ?>">
                                    <?php echo $b['building_name']; ?> (<?php echo $b['building_type']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-card-text"></i> คำอธิบาย
                        </label>
                        <textarea class="form-control" name="description" rows="3" placeholder="รายละเอียดเพิ่มเติมเกี่ยวกับห้อง..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> ยกเลิก
                    </button>
                    <button type="submit" name="add_room" class="btn btn-primary-gradient">
                        <i class="bi bi-check-circle"></i> บันทึก
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal แก้ไข
 <function_calls>
<invoke name="artifacts">
<parameter name="command">update</parameter>
<parameter name="id">modern_rooms_page</parameter>
<parameter name="old_str"><!-- Modal แก้ไข</parameter>
<parameter name="new_str"><!-- Modal แก้ไขห้อง -->
<div class="modal fade" id="editRoomModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="room_id" id="edit_room_id">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil-square"></i> แก้ไขข้อมูลห้อง</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-hash"></i> เลขห้อง <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" name="room_number" id="edit_room_number" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-tag"></i> ประเภทห้อง <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" name="room_type" id="edit_room_type" required>
                                <option value="แอร์">แอร์</option>
                                <option value="พัดลม">พัดลม</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-layers"></i> ชั้น <span class="text-danger">*</span>
                            </label>
                            <input type="number" class="form-control" name="floor" id="edit_floor" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-cash"></i> ค่าเช่า/เดือน <span class="text-danger">*</span>
                            </label>
                            <input type="number" step="0.01" class="form-control" name="monthly_rent" id="edit_monthly_rent" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-circle"></i> สถานะห้อง <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" name="room_status" id="edit_room_status" required>
                            <option value="available">ว่าง</option>
                            <option value="occupied">เช่าแล้ว</option>
                            <option value="maintenance">ซ่อมแซม</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-building"></i> เลือกอาคาร <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" name="building_id" id="edit_building_id" required>
                            <option value="">-- เลือกอาคาร --</option>
                            <?php foreach ($buildings as $b): ?>
                                <option value="<?php echo $b['building_id']; ?>">
                                    <?php echo $b['building_name']; ?> (<?php echo $b['building_type']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-card-text"></i> คำอธิบาย
                        </label>
                        <textarea class="form-control" name="description" id="edit_description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> ยกเลิก
                    </button>
                    <button type="submit" name="edit_room" class="btn btn-primary-gradient">
                        <i class="bi bi-check-circle"></i> บันทึกการแก้ไข
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ฟังก์ชันแก้ไขห้อง
function editRoom(room) {
    document.getElementById('edit_room_id').value = room.room_id;
    document.getElementById('edit_room_number').value = room.room_number;
    document.getElementById('edit_room_type').value = room.room_type;
    document.getElementById('edit_floor').value = room.floor;
    document.getElementById('edit_monthly_rent').value = room.monthly_rent;
    document.getElementById('edit_room_status').value = room.room_status;
    document.getElementById('edit_building_id').value = room.building_id;
    document.getElementById('edit_description').value = room.description || '';
    new bootstrap.Modal(document.getElementById('editRoomModal')).show();
}

// ค้นหาห้อง
document.getElementById('searchRoom')?.addEventListener('input', function(e) {
    const searchText = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('#roomsTable tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchText) ? '' : 'none';
    });
});

// กรองสถานะห้อง
document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        // อัพเดท active state
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        const filter = this.dataset.filter;
        const rows = document.querySelectorAll('#roomsTable tbody tr');
        
        rows.forEach(row => {
            if (filter === 'all' || row.dataset.status === filter) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
});
</script>
</body>
</html></parameter>