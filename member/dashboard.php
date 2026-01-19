<?php
// ============================================
// ไฟล์: member/dashboard.php (ปรับปรุงใหม่)
// คำอธิบาย: Dashboard สำหรับ Member แบบใหม่
// ============================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../models/Tenant.php';
require_once __DIR__ . '/../models/Invoice.php';
require_once __DIR__ . '/../models/Room.php';

requireRole('member');

$database = new Database();
$db = $database->getConnection();

$tenant = new Tenant($db);
$invoice = new Invoice($db);
$room = new Room($db);

// ดึงข้อมูลผู้เช่า
$tenantData = $tenant->getByUserId($_SESSION['user_id']);
$hasRoom = ($tenantData && $tenantData['room_id']);

// ดึงใบเสร็จ
$invoices = [];
$totalUnpaid = 0;
$currentMonthInvoice = null;

if ($hasRoom) {
    $invoices = $invoice->getByTenant($tenantData['tenant_id']);
    
    // คำนวณยอดค้างชำระ
    foreach ($invoices as $inv) {
        if ($inv['payment_status'] != 'paid') {
            $totalUnpaid += $inv['total_amount'];
        }
    }
    
    // หาบิลเดือนปัจจุบัน
    $currentMonth = date('n');
    $currentYear = date('Y');
    foreach ($invoices as $inv) {
        if ($inv['invoice_month'] == $currentMonth && $inv['invoice_year'] == $currentYear) {
            $currentMonthInvoice = $inv;
            break;
        }
    }
}

// สถิติการใช้น้ำ-ไฟ 3 เดือนล่าสุด
$usageStats = [];
if ($hasRoom) {
    $query = "SELECT i.invoice_month, i.invoice_year,
                     m.water_usage, m.electric_usage,
                     i.water_charge, i.electric_charge
              FROM invoices i
              LEFT JOIN meters m ON i.meter_id = m.meter_id
              WHERE i.tenant_id = :tenant_id
              ORDER BY i.invoice_year DESC, i.invoice_month DESC
              LIMIT 6";
    
    $stmt = $db->prepare($query);
    $stmt->execute([':tenant_id' => $tenantData['tenant_id']]);
    $usageStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>หน้าหลัก - ระบบจัดการหอพัก</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background: #f5f7fa;
        }
        
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .welcome-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
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
        .icon-success { background: rgba(40, 199, 111, 0.1); color: #28c76f; }
        .icon-warning { background: rgba(255, 159, 67, 0.1); color: #ff9f43; }
        .icon-danger { background: rgba(234, 84, 85, 0.1); color: #ea5455; }
        .icon-info { background: rgba(0, 207, 232, 0.1); color: #00cfe8; }
        
        .quick-action-btn {
            border-radius: 12px;
            padding: 15px;
            transition: all 0.3s ease;
            border: 2px solid #e0e0e0;
        }
        
        .quick-action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.1);
        }
        
        .invoice-item {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            transition: all 0.3s ease;
        }
        
        .invoice-item:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .chart-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
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
        <?php if ($hasRoom): ?>
            <!-- Welcome Card -->
            <div class="welcome-card">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2 class="mb-2">
                            <i class="bi bi-hand-wave"></i> 
                            สวัสดี, <?php echo $_SESSION['full_name']; ?>
                        </h2>
                        <p class="mb-0 opacity-75">ยินดีต้อนรับสู่ระบบจัดการหอพัก</p>
                    </div>
                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                        <div class="d-inline-block text-start">
                            <div class="mb-1">
                                <i class="bi bi-door-open"></i> 
                                <strong>ห้อง: <?php echo $tenantData['room_number']; ?></strong>
                            </div>
                            <div>
                                <i class="bi bi-calendar-check"></i> 
                                <?php echo formatThaiDate(date('Y-m-d')); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- สถิติ -->
            <div class="row mb-4">
                <!-- ยอดค้างชำระ -->
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-muted mb-1 small">ยอดค้างชำระ</p>
                                <h3 class="mb-0 <?php echo $totalUnpaid > 0 ? 'text-danger' : 'text-success'; ?>">
                                    ฿<?php echo number_format($totalUnpaid, 0); ?>
                                </h3>
                            </div>
                            <div class="stat-icon <?php echo $totalUnpaid > 0 ? 'icon-danger' : 'icon-success'; ?>">
                                <i class="bi bi-<?php echo $totalUnpaid > 0 ? 'exclamation-triangle' : 'check-circle'; ?>"></i>
                            </div>
                        </div>
                        <?php if ($totalUnpaid > 0): ?>
                            <small class="text-danger">
                                <i class="bi bi-info-circle"></i> กรุณาชำระภายในกำหนด
                            </small>
                        <?php else: ?>
                            <small class="text-success">
                                <i class="bi bi-check-circle"></i> ไม่มียอดค้างชำระ
                            </small>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ค่าเช่าห้อง -->
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-muted mb-1 small">ค่าเช่า/เดือน</p>
                                <h3 class="mb-0">฿<?php echo number_format($tenantData['monthly_rent'], 0); ?></h3>
                            </div>
                            <div class="stat-icon icon-primary">
                                <i class="bi bi-house"></i>
                            </div>
                        </div>
                        <small class="text-muted">
                            <i class="bi bi-door-open"></i> ห้อง <?php echo $tenantData['room_number']; ?>
                        </small>
                    </div>
                </div>

                <!-- น้ำเดือนล่าสุด -->
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-muted mb-1 small">ค่าน้ำล่าสุด</p>
                                <h3 class="mb-0">
                                    <?php 
                                    $lastWater = $usageStats[0]['water_usage'] ?? 0;
                                    echo number_format($lastWater, 1); 
                                    ?>
                                </h3>
                            </div>
                            <div class="stat-icon icon-info">
                                <i class="bi bi-droplet-fill"></i>
                            </div>
                        </div>
                        <small class="text-muted">
                            <i class="bi bi-activity"></i> ยูนิต
                        </small>
                    </div>
                </div>

                <!-- ไฟเดือนล่าสุด -->
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-muted mb-1 small">ค่าไฟล่าสุด</p>
                                <h3 class="mb-0">
                                    <?php 
                                    $lastElectric = $usageStats[0]['electric_usage'] ?? 0;
                                    echo number_format($lastElectric, 1); 
                                    ?>
                                </h3>
                            </div>
                            <div class="stat-icon icon-warning">
                                <i class="bi bi-lightning-fill"></i>
                            </div>
                        </div>
                        <small class="text-muted">
                            <i class="bi bi-activity"></i> ยูนิต
                        </small>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card mb-4" style="border-radius: 15px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.08);">
                <div class="card-body p-4">
                    <h5 class="mb-3">
                        <i class="bi bi-lightning-charge text-primary"></i> เมนูด่วน
                    </h5>
                    <div class="row">
                        <div class="col-6 col-md-3 mb-3">
                            <a href="invoice_view.php?id=<?php echo $currentMonthInvoice['invoice_id'] ?? ''; ?>" 
                               class="quick-action-btn text-center d-block text-decoration-none <?php echo !$currentMonthInvoice ? 'disabled' : ''; ?>">
                                <i class="bi bi-receipt fs-2 text-primary"></i>
                                <p class="mb-0 mt-2 small">ดูบิลเดือนนี้</p>
                            </a>
                        </div>
                        <div class="col-6 col-md-3 mb-3">
                            <a href="payment_upload.php?id=<?php echo $currentMonthInvoice['invoice_id'] ?? ''; ?>" 
                               class="quick-action-btn text-center d-block text-decoration-none <?php echo !$currentMonthInvoice || $currentMonthInvoice['payment_status'] == 'paid' ? 'disabled' : ''; ?>">
                                <i class="bi bi-upload fs-2 text-success"></i>
                                <p class="mb-0 mt-2 small">แจ้งชำระเงิน</p>
                            </a>
                        </div>
                        <div class="col-6 col-md-3 mb-3">
                            <a href="#invoiceHistory" class="quick-action-btn text-center d-block text-decoration-none">
                                <i class="bi bi-clock-history fs-2 text-info"></i>
                                <p class="mb-0 mt-2 small">ประวัติการชำระ</p>
                            </a>
                        </div>
                        <div class="col-6 col-md-3 mb-3">
                            <a href="profile.php" class="quick-action-btn text-center d-block text-decoration-none">
                                <i class="bi bi-person-gear fs-2 text-warning"></i>
                                <p class="mb-0 mt-2 small">ข้อมูลส่วนตัว</p>
                            </a>
                        </div>
                        <div class="col-6 col-md-3 mb-3">
                            <a href="maintenance.php" class="quick-action-btn text-center d-block text-decoration-none">
                                <i class="bi bi-tools fs-2 text-danger"></i>
                                <p class="mb-0 mt-2 small">แจ้งซ่อม/ปัญหา</p>
                            </a>
                        </div>

                        <div class="col-6 col-md-3 mb-3">
                            <a href="parcels.php" class="quick-action-btn text-center d-block text-decoration-none">
                                <i class="bi bi-box-seam fs-2" style="color: #6f42c1;"></i>
                                <p class="mb-0 mt-2 small">พัสดุของฉัน</p>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- กราฟการใช้น้ำ-ไฟ -->
            <?php if (count($usageStats) > 0): ?>
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="chart-card">
                        <h5 class="mb-4">
                            <i class="bi bi-graph-up text-primary"></i> การใช้น้ำ-ไฟย้อนหลัง 6 เดือน
                        </h5>
                        <canvas id="usageChart" height="80"></canvas>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- ประวัติใบเสร็จ -->
            <div class="card" style="border-radius: 15px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.08);">
                <div class="card-body p-4">
                    <h5 class="mb-4" id="invoiceHistory">
                        <i class="bi bi-receipt-cutoff text-primary"></i> ประวัติใบเสร็จ
                    </h5>
                    <?php if (count($invoices) > 0): ?>
                        <?php foreach (array_slice($invoices, 0, 6) as $inv): ?>
                            <div class="invoice-item">
                                <div class="row align-items-center">
                                    <div class="col-md-3">
                                        <h6 class="mb-1">
                                            <?php echo getThaiMonth($inv['invoice_month']) . ' ' . toBuddhistYear($inv['invoice_year']); ?>
                                        </h6>
                                        <small class="text-muted"><?php echo $inv['invoice_number']; ?></small>
                                    </div>
                                    <div class="col-md-2 text-center">
                                        <div class="text-muted small">ยอดเงิน</div>
                                        <strong class="text-primary">฿<?php echo formatMoney($inv['total_amount']); ?></strong>
                                    </div>
                                    <div class="col-md-3 text-center">
                                        <?php echo getPaymentStatusBadge($inv['payment_status']); ?>
                                        <?php if ($inv['payment_status'] == 'paid'): ?>
                                            <div class="small text-muted mt-1">
                                                <?php echo formatThaiDate($inv['paid_date']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-2 text-center">
                                        <small class="text-muted">กำหนดชำระ</small>
                                        <div><?php echo formatThaiDate($inv['due_date']); ?></div>
                                    </div>
                                    <div class="col-md-2 text-end">
                                        <a href="invoice_view.php?id=<?php echo $inv['invoice_id']; ?>" 
                                           class="btn btn-sm btn-outline-primary" target="_blank">
                                            <i class="bi bi-eye"></i> ดู
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php if (count($invoices) > 6): ?>
                            <div class="text-center mt-3">
                                <a href="invoices.php" class="btn btn-outline-primary">
                                    ดูทั้งหมด (<?php echo count($invoices); ?> รายการ)
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                            <p class="mt-2">ยังไม่มีใบเสร็จในระบบ</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        <?php else: ?>
            <!-- กรณีไม่มีห้อง -->
            <div class="text-center py-5">
                <i class="bi bi-house-x" style="font-size: 5rem; color: #ccc;"></i>
                <h3 class="mt-4">คุณยังไม่มีข้อมูลห้องเช่า</h3>
                <p class="text-muted">กรุณาติดต่อเจ้าหน้าที่เพื่อจัดห้องให้คุณ</p>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <?php if (count($usageStats) > 0): ?>
    <script>
        // ข้อมูลกราฟ
        const usageData = <?php echo json_encode(array_reverse($usageStats)); ?>;
        
        const labels = usageData.map(d => {
            const months = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 
                           'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
            return months[d.invoice_month];
        });
        
        const waterData = usageData.map(d => parseFloat(d.water_usage) || 0);
        const electricData = usageData.map(d => parseFloat(d.electric_usage) || 0);
        
        // สร้างกราฟ
        const ctx = document.getElementById('usageChart');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'น้ำ (ยูนิต)',
                        data: waterData,
                        borderColor: '#00cfe8',
                        backgroundColor: 'rgba(0, 207, 232, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'ไฟ (ยูนิต)',
                        data: electricData,
                        borderColor: '#ff9f43',
                        backgroundColor: 'rgba(255, 159, 67, 0.1)',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>