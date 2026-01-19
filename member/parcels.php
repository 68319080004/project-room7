<?php
// ไฟล์: member/parcels.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../models/Tenant.php';
require_once __DIR__ . '/../models/Parcel.php';

requireRole('member');

$database = new Database();
$db = $database->getConnection();

$tenant = new Tenant($db);
$parcel = new Parcel($db);

// ดึงข้อมูลผู้เช่า
$tenantData = $tenant->getByUserId($_SESSION['user_id']);
if (!$tenantData) die('ไม่พบข้อมูลผู้เช่า');

// ดึงรายการพัสดุ
$myParcels = $parcel->getParcelsByTenant($tenantData['tenant_id']);

// แยกกลุ่มพัสดุ (รอรับ / รับแล้ว)
$waiting = [];
$history = [];

foreach ($myParcels as $p) {
    if ($p['parcel_status'] == 'waiting') {
        $waiting[] = $p;
    } else {
        $history[] = $p;
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>พัสดุของฉัน | ระบบจัดการหอพัก</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --warning-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-bottom: 3rem;
        }

        /* Header Section */
        .page-header {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
            animation: fadeInDown 0.5s ease;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .page-header h2 {
            color: #2d3748;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .page-header .icon-wrapper {
            width: 60px;
            height: 60px;
            background: var(--primary-gradient);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        /* Alert Banner */
        .alert-custom {
            border-radius: 15px;
            border: none;
            padding: 1.5rem;
            margin-bottom: 2rem;
            animation: slideInRight 0.5s ease;
            box-shadow: 0 5px 20px rgba(255, 193, 7, 0.3);
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .alert-custom .alert-icon {
            width: 50px;
            height: 50px;
            background: rgba(255, 193, 7, 0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        /* Tabs */
        .nav-pills-custom {
            background: white;
            padding: 0.5rem;
            border-radius: 15px;
            box-shadow: var(--card-shadow);
            margin-bottom: 2rem;
        }

        .nav-pills-custom .nav-link {
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            color: #718096;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .nav-pills-custom .nav-link:hover {
            background: #f7fafc;
            color: #667eea;
        }

        .nav-pills-custom .nav-link.active {
            background: var(--primary-gradient);
            color: white;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .nav-pills-custom .badge {
            margin-left: 0.5rem;
        }

        /* Parcel Cards */
        .parcel-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            animation: fadeInUp 0.5s ease;
            animation-fill-mode: both;
        }

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

        .parcel-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }

        .parcel-card-waiting {
            border-left: 5px solid #ffc107;
        }

        .parcel-card-picked {
            border-left: 5px solid #10b981;
        }

        .parcel-icon-box {
            width: 70px;
            height: 70px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            flex-shrink: 0;
        }

        .icon-waiting {
            background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
            color: white;
        }

        .icon-picked {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .parcel-card-body {
            padding: 1.5rem;
        }

        .parcel-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }

        .parcel-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #2d3748;
            margin: 0;
        }

        .badge-status {
            padding: 0.5rem 1rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .badge-waiting {
            background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
            color: white;
        }

        .badge-picked {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .tracking-number {
            background: #f7fafc;
            padding: 0.5rem 0.75rem;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-weight: 600;
            color: #667eea;
            display: inline-block;
            margin: 0.5rem 0;
        }

        .info-row {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0;
            color: #4a5568;
        }

        .info-row i {
            color: #667eea;
            width: 20px;
        }

        .divider {
            border: none;
            height: 2px;
            background: linear-gradient(to right, transparent, #e2e8f0, transparent);
            margin: 1rem 0;
        }

        /* History List */
        .history-list {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
        }

        .history-item {
            padding: 1.5rem;
            border-bottom: 2px solid #f7fafc;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .history-item:last-child {
            border-bottom: none;
        }

        .history-item:hover {
            background: #f7fafc;
            padding-left: 2rem;
        }

        .history-icon {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
        }

        /* Empty State */
        .empty-state {
            background: white;
            border-radius: 20px;
            padding: 4rem 2rem;
            text-align: center;
            box-shadow: var(--card-shadow);
        }

        .empty-state-icon {
            width: 120px;
            height: 120px;
            margin: 0 auto 1.5rem;
            background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e0 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            color: #a0aec0;
        }

        /* Button */
        .btn-back {
            background: white;
            color: #667eea;
            border: 2px solid white;
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-back:hover {
            background: transparent;
            color: white;
            transform: translateX(-5px);
        }

        /* Animation delays for cards */
        .parcel-card:nth-child(1) { animation-delay: 0.1s; }
        .parcel-card:nth-child(2) { animation-delay: 0.2s; }
        .parcel-card:nth-child(3) { animation-delay: 0.3s; }
        .parcel-card:nth-child(4) { animation-delay: 0.4s; }
        .parcel-card:nth-child(5) { animation-delay: 0.5s; }
        .parcel-card:nth-child(6) { animation-delay: 0.6s; }

        /* Responsive */
        @media (max-width: 768px) {
            .page-header {
                padding: 1.5rem;
            }

            .page-header h2 {
                font-size: 1.5rem;
            }

            .parcel-card-body {
                padding: 1rem;
            }

            .parcel-icon-box {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
            }
        }

        /* Pulse animation for waiting badge */
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.7;
            }
        }

        .badge-waiting {
            animation: pulse 2s ease-in-out infinite;
        }
    </style>
</head>
<body>

<div class="container py-4">
    <!-- Page Header -->
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="icon-wrapper">
                    <i class="bi bi-box-seam-fill"></i>
                </div>
                <div>
                    <h2 class="mb-0">พัสดุของฉัน</h2>
                    <p class="text-muted mb-0">ตรวจสอบและติดตามสถานะพัสดุของคุณ</p>
                </div>
            </div>
            <a href="dashboard.php" class="btn btn-back">
                <i class="bi bi-arrow-left me-2"></i> กลับหน้าหลัก
            </a>
        </div>
    </div>

    <!-- Alert for Waiting Parcels -->
    <?php if (count($waiting) > 0): ?>
        <div class="alert alert-warning alert-custom d-flex align-items-center" role="alert">
            <div class="alert-icon me-3">
                <i class="bi bi-bell-fill"></i>
            </div>
            <div class="flex-grow-1">
                <h5 class="alert-heading mb-1">
                    <i class="bi bi-exclamation-circle-fill"></i> มีพัสดุรอรับ <?php echo count($waiting); ?> ชิ้น!
                </h5>
                <p class="mb-0">กรุณาติดต่อรับพัสดุที่นิติบุคคลภายใน 7 วัน</p>
            </div>
            <div class="text-end">
                <span class="badge badge-waiting fs-6"><?php echo count($waiting); ?> ชิ้น</span>
            </div>
        </div>
    <?php endif; ?>

    <!-- Tabs Navigation -->
    <ul class="nav nav-pills nav-pills-custom" id="pills-tab" role="tablist">
        <li class="nav-item flex-fill" role="presentation">
            <button class="nav-link active w-100" id="pills-wait-tab" data-bs-toggle="pill" 
                    data-bs-target="#pills-wait" type="button">
                <i class="bi bi-clock-history me-2"></i>
                รอรับ
                <span class="badge bg-white text-warning"><?php echo count($waiting); ?></span>
            </button>
        </li>
        <li class="nav-item flex-fill" role="presentation">
            <button class="nav-link w-100" id="pills-hist-tab" data-bs-toggle="pill" 
                    data-bs-target="#pills-hist" type="button">
                <i class="bi bi-check-circle me-2"></i>
                รับแล้ว
                <span class="badge bg-white text-success"><?php echo count($history); ?></span>
            </button>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content" id="pills-tabContent">
        
        <!-- Waiting Tab -->
        <div class="tab-pane fade show active" id="pills-wait">
            <?php if (empty($waiting)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="bi bi-box2"></i>
                    </div>
                    <h4 class="text-muted mb-2">ไม่มีพัสดุรอรับ</h4>
                    <p class="text-muted">เมื่อมีพัสดุมาถึง คุณจะเห็นรายการที่นี่</p>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($waiting as $p): ?>
                        <div class="col-lg-6">
                            <div class="parcel-card parcel-card-waiting h-100">
                                <div class="parcel-card-body">
                                    <div class="d-flex gap-3 mb-3">
                                        <div class="parcel-icon-box icon-waiting">
                                            <i class="bi bi-box-seam-fill"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="parcel-header">
                                                <div>
                                                    <h5 class="parcel-title"><?php echo htmlspecialchars($p['courier_company']); ?></h5>
                                                    <span class="tracking-number">
                                                        <i class="bi bi-upc-scan me-1"></i>
                                                        <?php echo htmlspecialchars($p['tracking_number']); ?>
                                                    </span>
                                                </div>
                                                <span class="badge badge-status badge-waiting">
                                                    <i class="bi bi-hourglass-split me-1"></i>รอรับ
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <hr class="divider">

                                    <div class="info-row">
                                        <i class="bi bi-person-fill"></i>
                                        <span><strong>ผู้ส่ง:</strong> <?php echo htmlspecialchars($p['sender_name'] ?: 'ไม่ระบุ'); ?></span>
                                    </div>

                                    <div class="info-row">
                                        <i class="bi bi-box"></i>
                                        <span><strong>ประเภท:</strong> <?php echo htmlspecialchars($p['parcel_type']); ?></span>
                                    </div>

                                    <div class="info-row">
                                        <i class="bi bi-calendar-check"></i>
                                        <span><strong>มาถึงเมื่อ:</strong> <?php echo date('d/m/Y H:i น.', strtotime($p['received_at'])); ?></span>
                                    </div>

                                    <?php if (!empty($p['notes'])): ?>
                                        <div class="info-row">
                                            <i class="bi bi-chat-left-text"></i>
                                            <span><strong>หมายเหตุ:</strong> <?php echo htmlspecialchars($p['notes']); ?></span>
                                        </div>
                                    <?php endif; ?>

                                    <div class="mt-3 p-3 rounded" style="background: linear-gradient(135deg, #fff5e1 0%, #ffe8cc 100%);">
                                        <small class="text-warning">
                                            <i class="bi bi-info-circle-fill me-2"></i>
                                            <strong>กรุณารับพัสดุภายใน 7 วัน</strong>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- History Tab -->
        <div class="tab-pane fade" id="pills-hist">
            <?php if (empty($history)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <h4 class="text-muted mb-2">ยังไม่มีประวัติการรับพัสดุ</h4>
                    <p class="text-muted">ประวัติพัสดุที่คุณรับแล้วจะแสดงที่นี่</p>
                </div>
            <?php else: ?>
                <div class="history-list">
                    <?php foreach ($history as $p): ?>
                        <div class="history-item">
                            <div class="d-flex gap-3 align-items-start">
                                <div class="history-icon">
                                    <i class="bi bi-check-lg"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start mb-2 flex-wrap gap-2">
                                        <div>
                                            <h6 class="mb-1 text-success fw-bold">
                                                <i class="bi bi-check-circle-fill me-1"></i>
                                                <?php echo htmlspecialchars($p['courier_company']); ?>
                                            </h6>
                                            <span class="tracking-number small">
                                                <?php echo htmlspecialchars($p['tracking_number']); ?>
                                            </span>
                                        </div>
                                        <span class="badge badge-status badge-picked">
                                            รับแล้ว
                                        </span>
                                    </div>

                                    <div class="row g-2 small text-muted">
                                        <div class="col-md-4">
                                            <i class="bi bi-person text-success me-1"></i>
                                            <strong>ผู้ส่ง:</strong> <?php echo htmlspecialchars($p['sender_name'] ?: 'ไม่ระบุ'); ?>
                                        </div>
                                        <div class="col-md-4">
                                            <i class="bi bi-calendar-event text-success me-1"></i>
                                            <strong>รับเมื่อ:</strong> <?php echo date('d/m/Y H:i น.', strtotime($p['picked_up_at'])); ?>
                                        </div>
                                        <div class="col-md-4">
                                            <i class="bi bi-box text-success me-1"></i>
                                            <strong>ประเภท:</strong> <?php echo htmlspecialchars($p['parcel_type']); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Add smooth scroll behavior
    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', function() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    });

    // Add entrance animation to cards
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.parcel-card, .history-item').forEach(card => {
        observer.observe(card);
    });
</script>

</body>
</html>