<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';

requireRole(['admin', 'owner']);

$db = new Database();
$pdo = $db->getConnection();

$sql = "
    SELECT 
        m.*,
        r.room_number,
        COALESCE(t.full_name, u.full_name) AS requester_name,
        tech.full_name AS technician_name
    FROM maintenance_requests m
    LEFT JOIN rooms r ON m.room_id = r.room_id
    LEFT JOIN tenants t ON m.tenant_id = t.tenant_id
    LEFT JOIN users u ON m.requested_by_user_id = u.user_id
    LEFT JOIN users tech ON m.assigned_to = tech.user_id
    ORDER BY FIELD(m.request_status, 'new', 'assigned', 'in_progress', 'done'), m.created_at DESC
";

try {
    $stmt = $pdo->query($sql);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// นับจำนวนตามสถานะ
$statusCounts = [
    'new' => 0,
    'assigned' => 0,
    'in_progress' => 0,
    'done' => 0
];
foreach ($requests as $r) {
    $status = $r['request_status'];
    if (isset($statusCounts[$status])) {
        $statusCounts[$status]++;
    }
}

$totalRequests = count($requests);
$urgentCount = 0;
foreach ($requests as $r) {
    if ($r['priority'] === 'urgent') $urgentCount++;
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายการแจ้งซ่อม - ระบบจัดการหอพัก</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-success: linear-gradient(135deg, #81FBB8 0%, #28C76F 100%);
            --gradient-info: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --gradient-warning: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            --gradient-danger: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --gradient-secondary: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        /* Page Header */
        .page-header {
            background: white;
            border-radius: 25px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 300px;
            height: 300px;
            background: var(--gradient-primary);
            opacity: 0.1;
            border-radius: 50%;
            transform: translate(50%, -50%);
        }

        .page-header h1 {
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 800;
            margin: 0;
        }

        /* Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            position: relative;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            opacity: 0.1;
        }

        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
        }

        .stat-card.new::before { background: var(--gradient-danger); }
        .stat-card.assigned::before { background: var(--gradient-info); }
        .stat-card.in-progress::before { background: var(--gradient-warning); }
        .stat-card.done::before { background: var(--gradient-success); }
        .stat-card.total::before { background: var(--gradient-primary); }

        .stat-icon {
            font-size: 3rem;
            opacity: 0.2;
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
        }

        .stat-card.new .stat-icon { color: #f5576c; }
        .stat-card.assigned .stat-icon { color: #4facfe; }
        .stat-card.in-progress .stat-icon { color: #fee140; }
        .stat-card.done .stat-icon { color: #28C76F; }
        .stat-card.total .stat-icon { color: #667eea; }

        .stat-content {
            position: relative;
            z-index: 2;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #6c757d;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 0.25rem;
        }

        .stat-card.new .stat-number { color: #f5576c; }
        .stat-card.assigned .stat-number { color: #4facfe; }
        .stat-card.in-progress .stat-number { color: #fee140; }
        .stat-card.done .stat-number { color: #28C76F; }
        .stat-card.total .stat-number { color: #667eea; }

        /* Main Card */
        .main-card {
            background: white;
            border-radius: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            animation: fadeInUp 0.6s ease-out;
        }

        .card-header-custom {
            background: var(--gradient-primary);
            color: white;
            padding: 1.75rem 2rem;
            border: none;
        }

        .card-header-custom h5 {
            margin: 0;
            font-weight: 700;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        /* Table Styling */
        .table-custom {
            margin: 0;
        }

        .table-custom thead {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
        }

        .table-custom thead th {
            border: none;
            padding: 1.25rem 1rem;
            font-weight: 700;
            color: #495057;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        .table-custom tbody tr {
            transition: all 0.3s ease;
            border-bottom: 1px solid #f0f0f0;
        }

        .table-custom tbody tr:hover {
            background: linear-gradient(90deg, rgba(102, 126, 234, 0.03) 0%, rgba(118, 75, 162, 0.03) 100%);
            transform: scale(1.01);
        }

        .table-custom tbody td {
            padding: 1.25rem 1rem;
            vertical-align: middle;
        }

        /* Status Badges */
        .status-badge {
            padding: 0.5rem 1.25rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .badge-new {
            background: var(--gradient-danger);
            color: white;
        }

        .badge-assigned {
            background: var(--gradient-info);
            color: white;
        }

        .badge-in_progress {
            background: var(--gradient-warning);
            color: white;
        }

        .badge-done, .badge-completed {
            background: var(--gradient-success);
            color: white;
        }

        /* Buttons */
        .btn-view {
            background: var(--gradient-primary);
            color: white;
            border: none;
            padding: 0.6rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-view:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .btn-back {
            background: white;
            color: #667eea;
            border: 2px solid white;
            padding: 0.75rem 1.75rem;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .btn-back:hover {
            background: rgba(255, 255, 255, 0.95);
            transform: translateX(-5px);
            color: #667eea;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 5rem;
            opacity: 0.2;
            margin-bottom: 1.5rem;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .empty-state h4 {
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        /* Room Badge */
        .room-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.35rem 1rem;
            border-radius: 20px;
            font-weight: 700;
            display: inline-block;
        }

        /* Technician Info */
        .technician-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .technician-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--gradient-success);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Priority Badge */
        .priority-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-weight: 600;
            display: inline-block;
            margin-top: 0.25rem;
        }

        .priority-urgent {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
            color: white;
        }

        .priority-normal {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
        }

        .priority-low {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
            color: #495057;
        }

        @media (max-width: 768px) {
            .stats-container {
                grid-template-columns: repeat(2, 1fr);
            }

            .page-header {
                padding: 1.5rem;
            }

            .table-responsive {
                margin: 0 -1rem;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <!-- Page Header -->
                <div class="page-header">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div>
                            <h1>
                                <i class="bi bi-tools"></i> รายการแจ้งซ่อม
                            </h1>
                            <p class="text-muted mb-0" style="font-size: 1rem; margin-top: 0.5rem;">
                                จัดการและติดตามรายการแจ้งซ่อมทั้งหมด
                            </p>
                        </div>
                        <a href="../dashboard.php" class="btn-back">
                            <i class="bi bi-arrow-left-circle-fill"></i>
                            กลับหน้าหลัก
                        </a>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="stats-container">
                    <div class="stat-card total">
                        <div class="stat-icon">
                            <i class="bi bi-list-check"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">
                                <i class="bi bi-archive"></i> ทั้งหมด
                            </div>
                            <div class="stat-number"><?= $totalRequests ?></div>
                            <small class="text-muted">รายการทั้งหมด</small>
                        </div>
                    </div>

                    <div class="stat-card new">
                        <div class="stat-icon">
                            <i class="bi bi-exclamation-circle-fill"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">
                                <i class="bi bi-bell"></i> รายการใหม่
                            </div>
                            <div class="stat-number"><?= $statusCounts['new'] ?></div>
                            <small class="text-muted">รอดำเนินการ</small>
                        </div>
                    </div>

                    <div class="stat-card assigned">
                        <div class="stat-icon">
                            <i class="bi bi-person-check-fill"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">
                                <i class="bi bi-clipboard-check"></i> มอบหมายแล้ว
                            </div>
                            <div class="stat-number"><?= $statusCounts['assigned'] ?></div>
                            <small class="text-muted">กำลังรอดำเนินการ</small>
                        </div>
                    </div>

                    <div class="stat-card in-progress">
                        <div class="stat-icon">
                            <i class="bi bi-gear-fill"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">
                                <i class="bi bi-hourglass-split"></i> กำลังซ่อม
                            </div>
                            <div class="stat-number"><?= $statusCounts['in_progress'] ?></div>
                            <small class="text-muted">อยู่ระหว่างดำเนินการ</small>
                        </div>
                    </div>

                    <div class="stat-card done">
                        <div class="stat-icon">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">
                                <i class="bi bi-patch-check"></i> เสร็จสิ้น
                            </div>
                            <div class="stat-number"><?= $statusCounts['done'] ?></div>
                            <small class="text-muted">ดำเนินการเสร็จแล้ว</small>
                        </div>
                    </div>
                </div>

                <!-- Main Table Card -->
                <div class="main-card">
                    <div class="card-header-custom">
                        <h5>
                            <i class="bi bi-table"></i>
                            รายการแจ้งซ่อมทั้งหมด
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (count($requests) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-custom">
                                <thead>
                                    <tr>
                                        <th><i class="bi bi-flag-fill"></i> สถานะ</th>
                                        <th><i class="bi bi-calendar3"></i> วันที่แจ้ง</th>
                                        <th><i class="bi bi-door-open-fill"></i> ห้อง</th>
                                        <th><i class="bi bi-person-fill"></i> ผู้แจ้ง</th>
                                        <th><i class="bi bi-wrench"></i> รายละเอียดปัญหา</th>
                                        <th><i class="bi bi-person-gear"></i> ช่างผู้รับผิดชอบ</th>
                                        <th class="text-center"><i class="bi bi-gear-fill"></i> จัดการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($requests as $r): ?>
                                    <?php 
                                        $st = $r['request_status']; 
                                        $statusIcon = match($st) {
                                            'new' => 'bi-exclamation-circle-fill',
                                            'assigned' => 'bi-person-check-fill',
                                            'in_progress' => 'bi-gear-fill',
                                            'done', 'completed' => 'bi-check-circle-fill',
                                            default => 'bi-question-circle'
                                        };
                                        $statusText = match($st) {
                                            'new' => 'ใหม่',
                                            'assigned' => 'มอบหมาย',
                                            'in_progress' => 'กำลังซ่อม',
                                            'done' => 'เสร็จสิ้น',
                                            'completed' => 'เสร็จสมบูรณ์',
                                            default => $st
                                        };
                                    ?>
                                    <tr>
                                        <td>
                                            <span class="status-badge badge-<?= $st ?>">
                                                <i class="bi <?= $statusIcon ?>"></i>
                                                <?= $statusText ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div>
                                                <strong style="color: #495057;"><?= date('d/m/Y', strtotime($r['created_at'])) ?></strong>
                                            </div>
                                            <small class="text-muted">
                                                <i class="bi bi-clock"></i> <?= date('H:i', strtotime($r['created_at'])) ?> น.
                                            </small>
                                        </td>
                                        <td>
                                            <span class="room-badge">
                                                <i class="bi bi-door-closed"></i> <?= htmlspecialchars($r['room_number']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--gradient-info); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700;">
                                                    <?= mb_substr($r['requester_name'] ?? '?', 0, 1) ?>
                                                </div>
                                                <span><?= htmlspecialchars($r['requester_name'] ?? '-') ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <div style="max-width: 280px;">
                                                <?= htmlspecialchars(mb_substr($r['issue_description'], 0, 70)) ?>
                                                <?= mb_strlen($r['issue_description']) > 70 ? '...' : '' ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($r['technician_name']): ?>
                                                <div class="technician-info">
                                                    <div class="technician-avatar">
                                                        <?= mb_substr($r['technician_name'], 0, 1) ?>
                                                    </div>
                                                    <span><?= htmlspecialchars($r['technician_name']) ?></span>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">
                                                    <i class="bi bi-dash-circle"></i> ยังไม่มอบหมาย
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <a href="view.php?id=<?= $r['request_id'] ?>" class="btn-view">
                                                <i class="bi bi-eye-fill"></i> ดูรายละเอียด
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="empty-state">
                            <i class="bi bi-inbox"></i>
                            <h4>ไม่มีรายการแจ้งซ่อม</h4>
                            <p class="text-muted">ยังไม่มีรายการแจ้งซ่อมในระบบในขณะนี้</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Smooth animations on load
        document.addEventListener('DOMContentLoaded', function() {
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.animation = `fadeInUp 0.6s ease-out ${index * 0.1}s both`;
            });
        });
    </script>
</body>
</html>