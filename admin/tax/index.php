<?php
// admin/tax/index.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../models/Tax.php';

requireRole(['admin', 'owner']);

$db = new Database();
$pdo = $db->getConnection();
$taxModel = new Tax($pdo);

$message = '';
$messageType = '';

// สร้างปีภาษีใหม่
if (isset($_POST['create_tax_year'])) {
    $year = $_POST['tax_year'];
    
    // ตรวจสอบว่ามีปีนี้แล้วหรือไม่
    $existing = $taxModel->getByYear($year);
    if ($existing) {
        $message = 'มีข้อมูลภาษีสำหรับปี ' . $year . ' อยู่แล้ว';
        $messageType = 'warning';
    } else {
        $tax_id = $taxModel->createTaxYear($year, $_SESSION['user_id']);
        if ($tax_id) {
            $message = 'สร้างบันทึกภาษีสำหรับปี ' . $year . ' สำเร็จ';
            $messageType = 'success';
        }
    }
}

$taxes = $taxModel->getAll();
$stats = $taxModel->getSummaryStats();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการภาษี - ระบบจัดการหอพัก</title>
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

        .stat-card.card-income::before { background: var(--gradient-success); }
        .stat-card.card-expense::before { background: var(--gradient-danger); }
        .stat-card.card-net::before { background: var(--gradient-info); }
        .stat-card.card-tax::before { background: var(--gradient-warning); }

        .stat-icon {
            font-size: 3rem;
            opacity: 0.2;
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
        }

        .stat-card.card-income .stat-icon { color: #28C76F; }
        .stat-card.card-expense .stat-icon { color: #f5576c; }
        .stat-card.card-net .stat-icon { color: #4facfe; }
        .stat-card.card-tax .stat-icon { color: #fee140; }

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
            font-size: 2rem;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 0.25rem;
        }

        .stat-card.card-income .stat-number { color: #28C76F; }
        .stat-card.card-expense .stat-number { color: #f5576c; }
        .stat-card.card-net .stat-number { color: #4facfe; }
        .stat-card.card-tax .stat-number { color: #fee140; }

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

        .status-paid {
            background: var(--gradient-success);
            color: white;
        }

        .status-pending {
            background: var(--gradient-warning);
            color: white;
        }

        .status-overdue {
            background: var(--gradient-danger);
            color: white;
        }

        /* Amount Styling */
        .amount-positive {
            color: #28C76F;
            font-weight: 800;
            font-size: 1.1rem;
        }

        .amount-negative {
            color: #f5576c;
            font-weight: 800;
            font-size: 1.1rem;
        }

        /* Year Badge */
        .year-badge {
            background: var(--gradient-primary);
            color: white;
            padding: 0.5rem 1.2rem;
            border-radius: 50px;
            font-weight: 700;
            font-size: 1rem;
            display: inline-block;
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

        .btn-add {
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

        .btn-add:hover {
            background: rgba(255, 255, 255, 0.95);
            transform: translateY(-3px);
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

        /* Modal Styling */
        .modal-content {
            border-radius: 25px;
            border: none;
            overflow: hidden;
        }

        .modal-header {
            background: var(--gradient-primary);
            color: white;
            border: none;
            padding: 1.75rem 2rem;
        }

        .modal-title {
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .modal-body {
            padding: 2rem;
        }

        .modal-footer {
            background: #f9fafb;
            border: none;
            padding: 1.5rem 2rem;
        }

        .form-label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        .form-select {
            border-radius: 15px;
            border: 2px solid #e5e7eb;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }

        .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        /* Alert Styling */
        .alert {
            border-radius: 15px;
            border: none;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
        }

        .alert-success {
            background: linear-gradient(135deg, rgba(129, 251, 184, 0.2) 0%, rgba(40, 199, 111, 0.2) 100%);
            color: #155724;
        }

        .alert-warning {
            background: linear-gradient(135deg, rgba(250, 112, 154, 0.2) 0%, rgba(254, 225, 64, 0.2) 100%);
            color: #856404;
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

            .stat-number {
                font-size: 1.5rem;
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
                                <i class="bi bi-receipt-cutoff"></i> จัดการภาษี
                            </h1>
                            <p class="text-muted mb-0" style="font-size: 1rem; margin-top: 0.5rem;">
                                จัดการและติดตามข้อมูลภาษีประจำปี
                            </p>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <a href="../dashboard.php" class="btn-back">
                                <i class="bi bi-arrow-left-circle-fill"></i>
                                กลับหน้าหลัก
                            </a>
                            <button type="button" class="btn-add" data-bs-toggle="modal" data-bs-target="#createTaxModal">
                                <i class="bi bi-plus-circle-fill"></i>
                                สร้างปีภาษีใหม่
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Alerts -->
                <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
                    <i class="bi bi-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-triangle' ?>-fill"></i>
                    <?= $message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Stats Cards -->
                <div class="stats-container">
                    <div class="stat-card card-income">
                        <div class="stat-icon">
                            <i class="bi bi-arrow-up-circle-fill"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">
                                <i class="bi bi-cash-stack"></i> รายได้รวม
                            </div>
                            <div class="stat-number">฿<?= number_format($stats['total_income_all'] ?? 0, 0) ?></div>
                            <small class="text-muted">รายได้ทั้งหมด</small>
                        </div>
                    </div>

                    <div class="stat-card card-expense">
                        <div class="stat-icon">
                            <i class="bi bi-arrow-down-circle-fill"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">
                                <i class="bi bi-credit-card"></i> ค่าใช้จ่ายรวม
                            </div>
                            <div class="stat-number">฿<?= number_format($stats['total_expense_all'] ?? 0, 0) ?></div>
                            <small class="text-muted">ค่าใช้จ่ายทั้งหมด</small>
                        </div>
                    </div>

                    <div class="stat-card card-net">
                        <div class="stat-icon">
                            <i class="bi bi-wallet2"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">
                                <i class="bi bi-calculator"></i> กำไรสุทธิ
                            </div>
                            <div class="stat-number">฿<?= number_format($stats['total_net_income'] ?? 0, 0) ?></div>
                            <small class="text-muted">กำไรสุทธิทั้งหมด</small>
                        </div>
                    </div>

                    <div class="stat-card card-tax">
                        <div class="stat-icon">
                            <i class="bi bi-piggy-bank-fill"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">
                                <i class="bi bi-percent"></i> ภาษีรวม
                            </div>
                            <div class="stat-number">฿<?= number_format($stats['total_tax_amount'] ?? 0, 0) ?></div>
                            <small class="text-muted">ภาษีทั้งหมด</small>
                        </div>
                    </div>
                </div>

                <!-- Main Table Card -->
                <div class="main-card">
                    <div class="card-header-custom">
                        <h5>
                            <i class="bi bi-table"></i>
                            รายการภาษีทั้งหมด
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (count($taxes) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-custom">
                                <thead>
                                    <tr>
                                        <th><i class="bi bi-calendar3"></i> ปีภาษี</th>
                                        <th><i class="bi bi-arrow-up"></i> รายได้</th>
                                        <th><i class="bi bi-arrow-down"></i> ค่าใช้จ่าย</th>
                                        <th><i class="bi bi-calculator"></i> กำไรสุทธิ</th>
                                        <th><i class="bi bi-percent"></i> ภาษี</th>
                                        <th><i class="bi bi-flag-fill"></i> สถานะ</th>
                                        <th class="text-center"><i class="bi bi-gear-fill"></i> จัดการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach($taxes as $t): ?>
                                    <tr>
                                        <td>
                                            <span class="year-badge">
                                                <i class="bi bi-calendar-check"></i> <?= $t['tax_year'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="amount-positive">฿<?= number_format($t['total_income'], 2) ?></span>
                                        </td>
                                        <td>
                                            <span class="amount-negative">฿<?= number_format($t['total_expenses'], 2) ?></span>
                                        </td>
                                        <td>
                                            <span class="<?= $t['net_income'] >= 0 ? 'amount-positive' : 'amount-negative' ?>">
                                                ฿<?= number_format($t['net_income'], 2) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div>
                                                <strong style="color: #fee140; font-size: 1.1rem;">
                                                    ฿<?= number_format($t['tax_amount'], 2) ?>
                                                </strong>
                                                <?php if($t['tax_rate'] > 0): ?>
                                                <br><small class="text-muted">(<?= number_format($t['tax_rate'], 2) ?>%)</small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php
                                            $statusClass = match($t['payment_status']) {
                                                'paid' => 'status-paid',
                                                'pending' => 'status-pending',
                                                'overdue' => 'status-overdue',
                                                default => 'status-pending'
                                            };
                                            $statusIcon = match($t['payment_status']) {
                                                'paid' => 'bi-check-circle-fill',
                                                'pending' => 'bi-hourglass-split',
                                                'overdue' => 'bi-exclamation-triangle-fill',
                                                default => 'bi-question-circle'
                                            };
                                            $statusText = match($t['payment_status']) {
                                                'paid' => 'ชำระแล้ว',
                                                'pending' => 'รอชำระ',
                                                'overdue' => 'เกินกำหนด',
                                                default => $t['payment_status']
                                            };
                                            ?>
                                            <span class="status-badge <?= $statusClass ?>">
                                                <i class="bi <?= $statusIcon ?>"></i>
                                                <?= $statusText ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <a href="detail.php?id=<?= $t['tax_id'] ?>" class="btn-view">
                                                <i class="bi bi-eye-fill"></i>
                                                ดูรายละเอียด
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
                            <h4>ยังไม่มีข้อมูลภาษี</h4>
                            <p class="text-muted">เริ่มต้นด้วยการสร้างบันทึกภาษีสำหรับปีใหม่</p>
                            <button type="button" class="btn-add mt-3" data-bs-toggle="modal" data-bs-target="#createTaxModal">
                                <i class="bi bi-plus-circle-fill"></i>
                                สร้างปีภาษีใหม่
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal สร้างปีภาษีใหม่ -->
    <div class="modal fade" id="createTaxModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-calendar-plus"></i> สร้างปีภาษีใหม่
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">เลือกปีภาษี <span class="text-danger">*</span></label>
                            <select class="form-select" name="tax_year" required>
                                <option value="">-- เลือกปี --</option>
                                <?php
                                $currentYear = date('Y');
                                for($i = $currentYear; $i >= $currentYear - 10; $i--) {
                                    echo "<option value='$i'>$i (พ.ศ. " . ($i + 543) . ")</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle-fill"></i>
                            <strong>หมายเหตุ:</strong> หลังจากสร้างปีภาษีแล้ว คุณสามารถเพิ่มรายการรายได้และค่าใช้จ่ายได้
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 50px; font-weight: 600;">
                            <i class="bi bi-x-circle"></i> ยกเลิก
                        </button>
                        <button type="submit" name="create_tax_year" class="btn btn-primary" style="border-radius: 50px; font-weight: 600; background: var(--gradient-primary); border: none;">
                            <i class="bi bi-check-circle"></i> สร้าง
                        </button>
                    </div>
                </form>
            </div>
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