<?php
// ============================================
// ไฟล์: admin/dashboard.php
// คำอธิบาย: Dashboard สำหรับ Admin/Owner (ปรับปรุงใหม่)
// ============================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../models/Room.php';
require_once __DIR__ . '/../models/Invoice.php';

requireRole(roles: ['admin', 'owner']);

$database = new Database();
$db = $database->getConnection();

$room = new Room(db: $db);
$invoice = new Invoice(db: $db);

// สถิติห้อง
$roomStats = $room->countByStatus();
$totalRooms = array_sum(array: $roomStats);
$occupiedRooms = $roomStats['occupied'] ?? 0;
$availableRooms = $roomStats['available'] ?? 0;
$maintenanceRooms = $roomStats['maintenance'] ?? 0;

// สถิติเดือนนี้
$currentMonth = date(format: 'n');
$currentYear = date(format: 'Y');
$monthlySummary = $invoice->getMonthlySummary(month: $currentMonth, year: $currentYear);

// ใบเสร็จที่รอชำระ
$pendingInvoices = $invoice->getAll(filters: ['status' => 'pending', 'month' => $currentMonth, 'year' => $currentYear]);

// ใบเสร็จที่รอตรวจสอบ
$checkingInvoices = $invoice->getAll(filters: ['status' => 'checking']);

// คำนวณเปอร์เซ็นต์
$occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ระบบจัดการหอพัก</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --warning-gradient: linear-gradient(135deg, #fccb90 0%, #d57eeb 100%);
            --info-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .stat-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            overflow: hidden;
            position: relative;
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
        .stat-card.info { background: var(--info-gradient); }

        .stat-icon {
            font-size: 3.5rem;
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
            font-size: 0.9rem;
            opacity: 0.95;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .stat-change {
            font-size: 0.85rem;
            opacity: 0.9;
            margin-top: 8px;
        }

        .chart-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .chart-card .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 1rem 1.5rem;
            font-weight: 600;
        }

        .alert-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .alert-card .card-header {
            border: none;
            padding: 1rem 1.5rem;
            font-weight: 600;
        }

        .alert-card.pending .card-header {
            background: linear-gradient(135deg, #fccb90 0%, #d57eeb 100%);
        }

        .alert-card.checking .card-header {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .table-modern {
            margin: 0;
        }

        .table-modern thead th {
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            color: #495057;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 1rem;
        }

        .table-modern tbody td {
            padding: 1rem;
            vertical-align: middle;
        }

        .table-modern tbody tr {
            transition: background-color 0.2s;
        }

        .table-modern tbody tr:hover {
            background-color: rgba(0,0,0,0.02);
        }

        .badge-custom {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.75rem;
        }

        .btn-action {
            border-radius: 8px;
            padding: 0.4rem 1rem;
            font-weight: 500;
            transition: all 0.3s;
        }

        .btn-action:hover {
            transform: scale(1.05);
        }

        .page-header {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .progress-ring {
            width: 80px;
            height: 80px;
            margin: 0 auto;
        }

        .progress-ring circle {
            transition: stroke-dashoffset 0.5s ease;
        }

        .empty-state {
            padding: 3rem 2rem;
            text-align: center;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 3rem;
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
                                <i class="bi bi-speedometer2 text-primary"></i> Dashboard
                            </h1>
                            <p class="text-muted mb-0">ภาพรวมระบบจัดการหอพัก</p>
                        </div>
                        <div>
                            <span class="badge bg-light text-dark fs-6 px-3 py-2">
                                <i class="bi bi-calendar-event"></i> 
                                <?php echo getThaiMonth($currentMonth) . ' ' . toBuddhistYear($currentYear); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- สถิติการ์ด -->
                <div class="row g-4 mb-4">
                    <div class="col-lg-3 col-md-6">
                        <div class="card stat-card primary text-white">
                            <div class="card-body position-relative">
                                <i class="bi bi-building stat-icon"></i>
                                <p class="stat-label mb-0">ห้องทั้งหมด</p>
                                <h2 class="stat-value"><?php echo $totalRooms; ?></h2>
                                <div class="stat-change">
                                    <i class="bi bi-graph-up"></i> ห้องในระบบ
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <div class="card stat-card success text-white">
                            <div class="card-body position-relative">
                                <i class="bi bi-check-circle-fill stat-icon"></i>
                                <p class="stat-label mb-0">ห้องที่เช่าแล้ว</p>
                                <h2 class="stat-value"><?php echo $occupiedRooms; ?></h2>
                                <div class="stat-change">
                                    <i class="bi bi-percent"></i> อัตราเข้าพัก <?php echo $occupancyRate; ?>%
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <div class="card stat-card warning text-white">
                            <div class="card-body position-relative">
                                <i class="bi bi-door-open-fill stat-icon"></i>
                                <p class="stat-label mb-0">ห้องว่าง</p>
                                <h2 class="stat-value"><?php echo $availableRooms; ?></h2>
                                <div class="stat-change">
                                    <i class="bi bi-arrow-right"></i> พร้อมให้เช่า
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <div class="card stat-card info text-white">
                            <div class="card-body position-relative">
                                <i class="bi bi-cash-coin stat-icon"></i>
                                <p class="stat-label mb-0">รายได้เดือนนี้</p>
                                <h2 class="stat-value">฿<?php echo number_format($monthlySummary['total_paid'] ?? 0); ?></h2>
                                <div class="stat-change">
                                    <i class="bi bi-arrow-up-circle"></i> ชำระแล้ว
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- กราฟและข้อมูล -->
                <div class="row g-4 mb-4">
                    <div class="col-lg-8">
                        <div class="card chart-card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-bar-chart-line"></i> สรุปรายได้รายเดือน</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="revenueChart" height="80"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card chart-card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-pie-chart-fill"></i> สัดส่วนสถานะห้อง</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="roomStatusChart"></canvas>
                                <div class="text-center mt-3">
                                    <span class="badge badge-custom" style="background: rgba(75, 192, 192, 0.8);">
                                        เช่าแล้ว <?php echo $occupiedRooms; ?>
                                    </span>
                                    <span class="badge badge-custom" style="background: rgba(255, 206, 86, 0.8);">
                                        ว่าง <?php echo $availableRooms; ?>
                                    </span>
                                    <span class="badge badge-custom" style="background: rgba(255, 99, 132, 0.8);">
                                        ซ่อม <?php echo $maintenanceRooms; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- รายการแจ้งเตือน -->
                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="card alert-card pending">
                            <div class="card-header text-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">
                                        <i class="bi bi-exclamation-triangle-fill"></i> รอชำระเงิน
                                    </h5>
                                    <span class="badge bg-white text-dark"><?php echo count($pendingInvoices); ?> รายการ</span>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-modern mb-0">
                                        <thead>
                                            <tr>
                                                <th>ห้อง</th>
                                                <th>ผู้เช่า</th>
                                                <th>ยอดเงิน</th>
                                                <th>กำหนดชำระ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (count($pendingInvoices) > 0): ?>
                                                <?php foreach (array_slice($pendingInvoices, 0, 5) as $inv): ?>
                                                    <tr>
                                                        <td>
                                                            <span class="badge bg-primary"><?php echo $inv['room_number']; ?></span>
                                                        </td>
                                                        <td><strong><?php echo $inv['tenant_name']; ?></strong></td>
                                                        <td>
                                                            <span class="text-danger fw-bold">
                                                                ฿<?php echo formatMoney($inv['total_amount']); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <small class="text-muted">
                                                                <i class="bi bi-clock"></i>
                                                                <?php echo formatThaiDate($inv['due_date']); ?>
                                                            </small>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="4" class="empty-state">
                                                        <i class="bi bi-check-circle"></i>
                                                        <p class="mb-0">ไม่มีรายการรอชำระ</p>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php if (count($pendingInvoices) > 5): ?>
                                    <div class="card-footer text-center bg-light">
                                        <a href="invoices.php?status=pending" class="text-decoration-none">
                                            ดูทั้งหมด <i class="bi bi-arrow-right"></i>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card alert-card checking">
                            <div class="card-header text-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">
                                        <i class="bi bi-clock-history"></i> รอตรวจสอบการชำระ
                                    </h5>
                                    <span class="badge bg-white text-dark"><?php echo count($checkingInvoices); ?> รายการ</span>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-modern mb-0">
                                        <thead>
                                            <tr>
                                                <th>ห้อง</th>
                                                <th>ผู้เช่า</th>
                                                <th>ยอดเงิน</th>
                                                <th class="text-center">จัดการ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (count($checkingInvoices) > 0): ?>
                                                <?php foreach (array_slice($checkingInvoices, 0, 5) as $inv): ?>
                                                    <tr>
                                                        <td>
                                                            <span class="badge bg-info"><?php echo $inv['room_number']; ?></span>
                                                        </td>
                                                        <td><strong><?php echo $inv['tenant_name']; ?></strong></td>
                                                        <td>
                                                            <span class="text-success fw-bold">
                                                                ฿<?php echo formatMoney($inv['total_amount']); ?>
                                                            </span>
                                                        </td>
                                                        <td class="text-center">
                                                            <a href="payments_verify.php?id=<?php echo $inv['invoice_id']; ?>" 
                                                               class="btn btn-sm btn-primary btn-action">
                                                                <i class="bi bi-search"></i> ตรวจสอบ
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="4" class="empty-state">
                                                        <i class="bi bi-check-circle"></i>
                                                        <p class="mb-0">ไม่มีรายการรอตรวจสอบ</p>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php if (count($checkingInvoices) > 5): ?>
                                    <div class="card-footer text-center bg-light">
                                        <a href="payments_verify.php" class="text-decoration-none">
                                            ดูทั้งหมด <i class="bi bi-arrow-right"></i>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // กราฟรายได้
        const revenueCtx = document.getElementById('revenueChart');
        new Chart(revenueCtx, {
            type: 'bar',
            data: {
                labels: ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'],
                datasets: [{
                    label: 'รายได้',
                    data: [35000, 35000, 38000, 35000, 40000, 38000, 42000, 40000, 38000, 45000, 42000, 40000],
                    backgroundColor: 'rgba(102, 126, 234, 0.7)',
                    borderColor: 'rgba(102, 126, 234, 1)',
                    borderWidth: 2,
                    borderRadius: 8,
                    hoverBackgroundColor: 'rgba(102, 126, 234, 0.9)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        borderRadius: 8,
                        callbacks: {
                            label: function(context) {
                                return '฿' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            callback: function(value) {
                                return '฿' + (value / 1000) + 'k';
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // กราฟสถานะห้อง
        const roomCtx = document.getElementById('roomStatusChart');
        new Chart(roomCtx, {
            type: 'doughnut',
            data: {
                labels: ['เช่าแล้ว', 'ว่าง', 'ซ่อมแซม'],
                datasets: [{
                    data: [
                        <?php echo $occupiedRooms; ?>, 
                        <?php echo $availableRooms; ?>, 
                        <?php echo $maintenanceRooms; ?>
                    ],
                    backgroundColor: [
                        'rgba(75, 192, 192, 0.8)',
                        'rgba(255, 206, 86, 0.8)',
                        'rgba(255, 99, 132, 0.8)'
                    ],
                    borderWidth: 3,
                    borderColor: '#fff',
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        borderRadius: 8,
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return context.label + ': ' + context.parsed + ' ห้อง (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>