<?php
// admin/tax/detail.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../models/Tax.php';

requireRole(['admin', 'owner']);

$db = new Database();
$pdo = $db->getConnection();
$taxModel = new Tax($pdo);

$message = '';
$messageType = '';

$tax_id = $_GET['id'] ?? null;

if (!$tax_id) {
    header('Location: index.php');
    exit;
}

$tax = $taxModel->getById($tax_id);

if (!$tax) {
    header('Location: index.php');
    exit;
}

// เพิ่มรายได้
if (isset($_POST['add_income'])) {
    $data = [
        'tax_id' => $tax_id,
        'income_type' => $_POST['income_type'],
        'description' => $_POST['description'],
        'amount' => $_POST['amount'],
        'income_date' => $_POST['income_date']
    ];
    
    if ($taxModel->addIncome($data)) {
        $message = 'เพิ่มรายการรายได้สำเร็จ';
        $messageType = 'success';
        $tax = $taxModel->getById($tax_id); // Refresh data
    } else {
        $message = 'เกิดข้อผิดพลาด';
        $messageType = 'danger';
    }
}

// เพิ่มค่าใช้จ่าย
if (isset($_POST['add_expense'])) {
    $data = [
        'tax_id' => $tax_id,
        'expense_type' => $_POST['expense_type'],
        'description' => $_POST['description'],
        'amount' => $_POST['amount'],
        'expense_date' => $_POST['expense_date'],
        'vendor_name' => $_POST['vendor_name'] ?? null
    ];
    
    if ($taxModel->addExpense($data)) {
        $message = 'เพิ่มรายการค่าใช้จ่ายสำเร็จ';
        $messageType = 'success';
        $tax = $taxModel->getById($tax_id); // Refresh data
    } else {
        $message = 'เกิดข้อผิดพลาด';
        $messageType = 'danger';
    }
}

// ลบรายได้
if (isset($_GET['delete_income'])) {
    if ($taxModel->deleteIncome($_GET['delete_income'], $tax_id)) {
        $message = 'ลบรายการรายได้สำเร็จ';
        $messageType = 'success';
        $tax = $taxModel->getById($tax_id);
    }
}

// ลบค่าใช้จ่าย
if (isset($_GET['delete_expense'])) {
    if ($taxModel->deleteExpense($_GET['delete_expense'], $tax_id)) {
        $message = 'ลบรายการค่าใช้จ่ายสำเร็จ';
        $messageType = 'success';
        $tax = $taxModel->getById($tax_id);
    }
}

// อัพเดทสถานะการชำระภาษี
if (isset($_POST['update_payment_status'])) {
    $status = $_POST['payment_status'];
    $paid_date = $status === 'paid' ? date('Y-m-d') : null;
    
    if ($taxModel->updatePaymentStatus($tax_id, $status, $paid_date)) {
        $message = 'อัพเดทสถานะสำเร็จ';
        $messageType = 'success';
        $tax = $taxModel->getById($tax_id);
    }
}

$incomes = $taxModel->getIncomes($tax_id);
$expenses = $taxModel->getExpenses($tax_id);

// จัดกลุ่มรายได้ตามประเภท
$incomeByType = [];
foreach ($incomes as $income) {
    $type = $income['income_type'];
    if (!isset($incomeByType[$type])) {
        $incomeByType[$type] = 0;
    }
    $incomeByType[$type] += $income['amount'];
}

// จัดกลุ่มค่าใช้จ่ายตามประเภท
$expenseByType = [];
foreach ($expenses as $expense) {
    $type = $expense['expense_type'];
    if (!isset($expenseByType[$type])) {
        $expenseByType[$type] = 0;
    }
    $expenseByType[$type] += $expense['amount'];
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายละเอียดภาษี ปี <?= $tax['tax_year'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #7c3aed;
            --secondary-color: #6366f1;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #06b6d4;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .main-container {
            background: white;
            border-radius: 25px;
            box-shadow: 0 25px 80px rgba(0,0,0,0.35);
            overflow: hidden;
            margin: 2rem auto;
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .header-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 2.5rem;
            position: relative;
            overflow: hidden;
        }

        .header-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: pulse 15s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1) rotate(0deg); }
            50% { transform: scale(1.1) rotate(180deg); }
        }

        .header-content {
            position: relative;
            z-index: 1;
        }

        .header-title {
            font-size: 2.2rem;
            font-weight: 800;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .year-badge-large {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            padding: 0.8rem 2rem;
            border-radius: 50px;
            font-size: 1.5rem;
            font-weight: 800;
            border: 2px solid white;
        }

        .btn-back, .btn-action-header {
            padding: 0.7rem 1.8rem;
            border-radius: 50px;
            font-weight: 700;
            transition: all 0.4s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            border: 2px solid white;
        }

        .btn-back {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            backdrop-filter: blur(10px);
        }

        .btn-back:hover {
            background: white;
            color: var(--primary-color);
            transform: translateX(-8px);
        }

        .btn-action-header {
            background: white;
            color: var(--primary-color);
        }

        .btn-action-header:hover {
            background: #f0f0f0;
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(255,255,255,0.4);
        }

        .summary-section {
            padding: 2.5rem;
            background: linear-gradient(to bottom, #f8f9fa 0%, white 100%);
        }

        .summary-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
        }

        .summary-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--card-color) 0%, var(--card-color-light) 100%);
        }

        .summary-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 50px rgba(0,0,0,0.15);
        }

        .summary-card.card-income {
            --card-color: var(--success-color);
            --card-color-light: #34d399;
        }

        .summary-card.card-expense {
            --card-color: var(--danger-color);
            --card-color-light: #f87171;
        }

        .summary-card.card-net {
            --card-color: #3b82f6;
            --card-color-light: #60a5fa;
        }

        .summary-card.card-tax {
            --card-color: var(--warning-color);
            --card-color-light: #fbbf24;
        }

        .summary-icon {
            width: 70px;
            height: 70px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 1.2rem;
            background: linear-gradient(135deg, var(--card-color) 0%, var(--card-color-light) 100%);
            color: white;
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
        }

        .summary-label {
            font-size: 1rem;
            color: #6b7280;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .summary-value {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--card-color) 0%, var(--card-color-light) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
        }

        .content-section {
            padding: 0 2.5rem 2.5rem;
        }

        .section-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }

        .section-header {
            background: linear-gradient(135deg, #1f2937 0%, #374151 100%);
            color: white;
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .section-header h5 {
            margin: 0;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .btn-add-item {
            background: white;
            color: #1f2937;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 50px;
            font-weight: 700;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-add-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255,255,255,0.3);
        }

        .table thead {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
        }

        .table thead th {
            padding: 1.2rem 1rem;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
            color: #374151;
            border: none;
        }

        .table tbody tr {
            transition: all 0.3s ease;
            border-bottom: 1px solid #e5e7eb;
        }

        .table tbody tr:hover {
            background: linear-gradient(to right, #f9fafb 0%, #ffffff 100%);
            transform: scale(1.002);
        }

        .table tbody td {
            padding: 1.2rem 1rem;
            vertical-align: middle;
        }

        .type-badge {
            padding: 0.4rem 1rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.85rem;
            display: inline-block;
        }

        .type-rent { background: linear-gradient(135deg, #ec4899 0%, #f472b6 100%); color: white; }
        .type-electricity { background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%); color: white; }
        .type-water { background: linear-gradient(135deg, #06b6d4 0%, #22d3ee 100%); color: white; }
        .type-service { background: linear-gradient(135deg, #8b5cf6 0%, #a78bfa 100%); color: white; }
        .type-maintenance { background: linear-gradient(135deg, #ef4444 0%, #f87171 100%); color: white; }
        .type-utilities { background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%); color: white; }
        .type-salary { background: linear-gradient(135deg, #10b981 0%, #34d399 100%); color: white; }
        .type-supplies { background: linear-gradient(135deg, #6366f1 0%, #818cf8 100%); color: white; }
        .type-tax { background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%); color: white; }
        .type-insurance { background: linear-gradient(135deg, #7c3aed 0%, #a78bfa 100%); color: white; }
        .type-other { background: linear-gradient(135deg, #6b7280 0%, #9ca3af 100%); color: white; }

        .amount-income {
            color: var(--success-color);
            font-weight: 800;
            font-size: 1.1rem;
        }

        .amount-expense {
            color: var(--danger-color);
            font-weight: 800;
            font-size: 1.1rem;
        }

        .btn-delete {
            background: linear-gradient(135deg, var(--danger-color) 0%, #dc2626 100%);
            color: white;
            border: none;
            padding: 0.4rem 1rem;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 0.85rem;
        }

        .btn-delete:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(239, 68, 68, 0.4);
            color: white;
        }

        .chart-container {
            padding: 2rem;
            background: white;
            border-radius: 15px;
        }

        .status-update-section {
            background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
            padding: 1.5rem;
            border-radius: 15px;
            margin-top: 1rem;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #6b7280;
        }

        .empty-state i {
            font-size: 4rem;
            opacity: 0.3;
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .header-section { padding: 1.5rem; }
            .summary-section, .content-section { padding: 1.5rem; }
            .summary-value { font-size: 2rem; }
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="main-container">
        <!-- Header -->
        <div class="header-section">
            <div class="header-content">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                    <h2 class="header-title">
                        <i class="bi bi-receipt-cutoff"></i>
                        รายละเอียดภาษี
                    </h2>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="index.php" class="btn-back">
                            <i class="bi bi-arrow-left-circle-fill"></i>
                            กลับ
                        </a>
                        <button class="btn-action-header" onclick="window.print()">
                            <i class="bi bi-printer-fill"></i>
                            พิมพ์รายงาน
                        </button>
                    </div>
                </div>
                <div class="year-badge-large">
                    <i class="bi bi-calendar3"></i> ปีภาษี <?= $tax['tax_year'] ?> (พ.ศ. <?= $tax['tax_year'] + 543 ?>)
                </div>
            </div>
        </div>

        <!-- Alerts -->
        <?php if ($message): ?>
        <div style="padding: 2rem 2.5rem 0;">
            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" style="border-radius: 15px;">
                <?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
        <?php endif; ?>

        <!-- Summary -->
        <div class="summary-section">
            <div class="row g-4">
                <div class="col-lg-3 col-md-6">
                    <div class="summary-card card-income">
                        <div class="summary-icon">
                            <i class="bi bi-arrow-up-circle-fill"></i>
                        </div>
                        <div class="summary-label">รายได้ทั้งหมด</div>
                        <div class="summary-value">฿<?= number_format($tax['total_income'], 0) ?></div>
                        <small class="text-muted"><?= count($incomes) ?> รายการ</small>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="summary-card card-expense">
                        <div class="summary-icon">
                            <i class="bi bi-arrow-down-circle-fill"></i>
                        </div>
                        <div class="summary-label">ค่าใช้จ่ายทั้งหมด</div>
                        <div class="summary-value">฿<?= number_format($tax['total_expenses'], 0) ?></div>
                        <small class="text-muted"><?= count($expenses) ?> รายการ</small>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="summary-card card-net">
                        <div class="summary-icon">
                            <i class="bi bi-wallet2"></i>
                        </div>
                        <div class="summary-label">กำไรสุทธิ</div>
                        <div class="summary-value">฿<?= number_format($tax['net_income'], 0) ?></div>
                        <small class="text-muted">รายได้ - ค่าใช้จ่าย</small>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="summary-card card-tax">
                        <div class="summary-icon">
                            <i class="bi bi-piggy-bank-fill"></i>
                        </div>
                        <div class="summary-label">ภาษีที่ต้องชำระ</div>
                        <div class="summary-value">฿<?= number_format($tax['tax_amount'], 0) ?></div>
                        <small class="text-muted">
                            <?php if($tax['tax_rate'] > 0): ?>
                                อัตรา <?= number_format($tax['tax_rate'], 2) ?>%
                            <?php else: ?>
                                ไม่ต้องเสียภาษี
                            <?php endif; ?>
                        </small>
                    </div>
                </div>
            </div>

            <!-- Status Update Section -->
            <div class="status-update-section mt-4">
                <form method="POST" class="d-flex align-items-center gap-3 flex-wrap">
                    <div class="flex-grow-1">
                        <label class="form-label fw-bold mb-2">สถานะการชำระภาษี</label>
                        <select name="payment_status" class="form-select" style="border-radius: 12px;">
                            <option value="pending" <?= $tax['payment_status'] == 'pending' ? 'selected' : '' ?>>รอชำระ</option>
                            <option value="paid" <?= $tax['payment_status'] == 'paid' ? 'selected' : '' ?>>ชำระแล้ว</option>
                            <option value="overdue" <?= $tax['payment_status'] == 'overdue' ? 'selected' : '' ?>>เกินกำหนด</option>
                        </select>
                    </div>
                    <div style="padding-top: 2rem;">
                        <button type="submit" name="update_payment_status" class="btn btn-primary" style="border-radius: 50px; font-weight: 700;">
                            <i class="bi bi-check-circle"></i> อัพเดทสถานะ
                        </button>
                    </div>
                </form>
                <?php if($tax['paid_date']): ?>
                <div class="mt-3">
                    <small class="text-muted">
                        <i class="bi bi-calendar-check"></i> ชำระเมื่อ: <?= date('d/m/Y', strtotime($tax['paid_date'])) ?>
                    </small>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="content-section">
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="section-card">
                        <div class="section-header">
                            <h5><i class="bi bi-pie-chart-fill"></i> สัดส่วนรายได้</h5>
                        </div>
                        <div class="chart-container">
                            <canvas id="incomeChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="section-card">
                        <div class="section-header">
                            <h5><i class="bi bi-pie-chart-fill"></i> สัดส่วนค่าใช้จ่าย</h5>
                        </div>
                        <div class="chart-container">
                            <canvas id="expenseChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Income Table -->
            <div class="section-card">
                <div class="section-header">
                    <h5>
                        <i class="bi bi-arrow-up-circle-fill"></i>
                        รายการรายได้ (<?= count($incomes) ?> รายการ)
                    </h5>
                    <button type="button" class="btn-add-item" data-bs-toggle="modal" data-bs-target="#addIncomeModal">
                        <i class="bi bi-plus-circle-fill"></i>
                        เพิ่มรายได้
                    </button>
                </div>
                <div class="table-responsive">
                    <?php if (count($incomes) > 0): ?>
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th><i class="bi bi-calendar"></i> วันที่</th>
                                <th><i class="bi bi-tag"></i> ประเภท</th>
                                <th><i class="bi bi-file-text"></i> รายละเอียด</th>
                                <th><i class="bi bi-cash"></i> จำนวนเงิน</th>
                                <th><i class="bi bi-gear"></i> จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach($incomes as $income): ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($income['income_date'])) ?></td>
                                <td>
                                    <?php
                                    $typeClass = 'type-' . $income['income_type'];
                                    $typeName = match($income['income_type']) {
                                        'rent' => 'ค่าเช่า',
                                        'electricity' => 'ค่าไฟ',
                                        'water' => 'ค่าน้ำ',
                                        'service' => 'ค่าบริการ',
                                        'other' => 'อื่นๆ',
                                        default => $income['income_type']
                                    };
                                    ?>
                                    <span class="type-badge <?= $typeClass ?>"><?= $typeName ?></span>
                                </td>
                                <td><?= htmlspecialchars($income['description']) ?></td>
                                <td><span class="amount-income">฿<?= number_format($income['amount'], 2) ?></span></td>
                                <td>
                                    <a href="?id=<?= $tax_id ?>&delete_income=<?= $income['income_id'] ?>" 
                                       class="btn-delete"
                                       onclick="return confirm('ยืนยันการลบรายการนี้?')">
                                        <i class="bi bi-trash-fill"></i> ลบ
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                        <tfoot style="background: #f9fafb;">
                            <tr>
                                <th colspan="3" class="text-end" style="padding: 1.5rem;">รวมรายได้:</th>
                                <th colspan="2" style="padding: 1.5rem;">
                                    <span class="amount-income" style="font-size: 1.3rem;">
                                        ฿<?= number_format($tax['total_income'], 2) ?>
                                    </span>
                                </th>
                            </tr>
                        </tfoot>
                    </table>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-inbox"></i>
                        <p>ยังไม่มีรายการรายได้</p>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addIncomeModal" style="border-radius: 50px;">
                            <i class="bi bi-plus-circle"></i> เพิ่มรายการแรก
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Expense Table -->
            <div class="section-card mt-4">
                <div class="section-header">
                    <h5>
                        <i class="bi bi-arrow-down-circle-fill"></i>
                        รายการค่าใช้จ่าย (<?= count($expenses) ?> รายการ)
                    </h5>
                    <button type="button" class="btn-add-item" data-bs-toggle="modal" data-bs-target="#addExpenseModal">
                        <i class="bi bi-plus-circle-fill"></i>
                        เพิ่มค่าใช้จ่าย
                    </button>
                </div>
                <div class="table-responsive">
                    <?php if (count($expenses) > 0): ?>
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th><i class="bi bi-calendar"></i> วันที่</th>
                                <th><i class="bi bi-tag"></i> ประเภท</th>
                                <th><i class="bi bi-file-text"></i> รายละเอียด</th>
<th><i class="bi bi-shop"></i> ผู้ขาย</th>
<th><i class="bi bi-cash"></i> จำนวนเงิน</th>
<th><i class="bi bi-gear"></i> จัดการ</th>
</tr>
</thead>
<tbody>
<?php foreach($expenses as $expense): ?>
<tr>
<td><?= date('d/m/Y', strtotime($expense['expense_date'])) ?></td>
<td>
<?php
                                 $typeClass = 'type-' . $expense['expense_type'];
                                 $typeName = match($expense['expense_type']) {
                                     'maintenance' => 'ซ่อมแซม',
                                     'utilities' => 'สาธารณูปโภค',
                                     'salary' => 'เงินเดือน',
                                     'supplies' => 'วัสดุสิ้นเปลือง',
                                     'tax' => 'ภาษี',
                                     'insurance' => 'ประกัน',
                                     'other' => 'อื่นๆ',
                                     default => $expense['expense_type']
                                 };
                                 ?>
<span class="type-badge <?= $typeClass ?>"><?= $typeName ?></span>
</td>
<td><?= htmlspecialchars($expense['description']) ?></td>
<td><?= htmlspecialchars($expense['vendor_name'] ?? '-') ?></td>
<td><span class="amount-expense">฿<?= number_format($expense['amount'], 2) ?></span></td>
<td>
<a href="?id=<?= $tax_id ?>&delete_expense=<?= $expense['expense_id'] ?>" 
                                    class="btn-delete"
                                    onclick="return confirm('ยืนยันการลบรายการนี้?')">
<i class="bi bi-trash-fill"></i> ลบ
</a>
</td>
</tr>
<?php endforeach; ?>
</tbody>
<tfoot style="background: #f9fafb;">
<tr>
<th colspan="4" class="text-end" style="padding: 1.5rem;">รวมค่าใช้จ่าย:</th>
<th colspan="2" style="padding: 1.5rem;">
<span class="amount-expense" style="font-size: 1.3rem;">
฿<?= number_format($tax['total_expenses'], 2) ?>
</span>
</th>
</tr>
</tfoot>
</table>
<?php else: ?>
<div class="empty-state">
<i class="bi bi-inbox"></i>
<p>ยังไม่มีรายการค่าใช้จ่าย</p>
<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addExpenseModal" style="border-radius: 50px;">
<i class="bi bi-plus-circle"></i> เพิ่มรายการแรก
</button>
</div>
<?php endif; ?>
</div>
</div>
</div>
</div>
</div>
<!-- Modal เพิ่มรายได้ -->
<div class="modal fade" id="addIncomeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 20px; overflow: hidden;">
            <form method="POST">
                <div class="modal-header" style="background: linear-gradient(135deg, var(--success-color) 0%, #34d399 100%); color: white; border: none; padding: 1.5rem 2rem;">
                    <h5 class="modal-title" style="font-weight: 700;">
                        <i class="bi bi-plus-circle-fill"></i> เพิ่มรายการรายได้
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding: 2rem;">
                    <div class="mb-3">
                        <label class="form-label fw-bold">ประเภทรายได้ <span class="text-danger">*</span></label>
                        <select name="income_type" class="form-select" required style="border-radius: 12px; border: 2px solid #e5e7eb; padding: 0.75rem;">
                            <option value="">-- เลือกประเภท --</option>
                            <option value="rent">💰 ค่าเช่า</option>
                            <option value="electricity">⚡ ค่าไฟ</option>
                            <option value="water">💧 ค่าน้ำ</option>
                            <option value="service">🛎️ ค่าบริการ</option>
                            <option value="other">📦 อื่นๆ</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">รายละเอียด <span class="text-danger">*</span></label>
                        <input type="text" name="description" class="form-control" required placeholder="เช่น: ค่าเช่าห้อง 101 เดือนมกราคม" style="border-radius: 12px; border: 2px solid #e5e7eb; padding: 0.75rem;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">จำนวนเงิน (บาท) <span class="text-danger">*</span></label>
                        <input type="number" name="amount" class="form-control" required step="0.01" min="0" placeholder="0.00" style="border-radius: 12px; border: 2px solid #e5e7eb; padding: 0.75rem;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">วันที่ <span class="text-danger">*</span></label>
                        <input type="date" name="income_date" class="form-control" required style="border-radius: 12px; border: 2px solid #e5e7eb; padding: 0.75rem;">
                    </div>
                </div>
                <div class="modal-footer" style="background: #f9fafb; border: none; padding: 1.5rem 2rem;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 50px; font-weight: 600;">
                        <i class="bi bi-x-circle"></i> ยกเลิก
                    </button>
                    <button type="submit" name="add_income" class="btn btn-success" style="border-radius: 50px; font-weight: 600; background: linear-gradient(135deg, var(--success-color) 0%, #34d399 100%); border: none;">
                        <i class="bi bi-check-circle"></i> บันทึก
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Modal เพิ่มค่าใช้จ่าย -->
<div class="modal fade" id="addExpenseModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 20px; overflow: hidden;">
            <form method="POST">
                <div class="modal-header" style="background: linear-gradient(135deg, var(--danger-color) 0%, #dc2626 100%); color: white; border: none; padding: 1.5rem 2rem;">
                    <h5 class="modal-title" style="font-weight: 700;">
                        <i class="bi bi-plus-circle-fill"></i> เพิ่มรายการค่าใช้จ่าย
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding: 2rem;">
                    <div class="mb-3">
                        <label class="form-label fw-bold">ประเภทค่าใช้จ่าย <span class="text-danger">*</span></label>
                        <select name="expense_type" class="form-select" required style="border-radius: 12px; border: 2px solid #e5e7eb; padding: 0.75rem;">
                            <option value="">-- เลือกประเภท --</option>
                            <option value="maintenance">🔧 ซ่อมแซม</option>
                            <option value="utilities">💡 สาธารณูปโภค</option>
                            <option value="salary">💵 เงินเดือน</option>
                            <option value="supplies">📦 วัสดุสิ้นเปลือง</option>
                            <option value="tax">🏛️ ภาษี</option>
                            <option value="insurance">🛡️ ประกัน</option>
                            <option value="other">📋 อื่นๆ</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">รายละเอียด <span class="text-danger">*</span></label>
                        <input type="text" name="description" class="form-control" required placeholder="เช่น: ซ่อมท่อน้ำรั่ว ชั้น 2" style="border-radius: 12px; border: 2px solid #e5e7eb; padding: 0.75rem;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">ผู้ขาย/ร้านค้า</label>
                        <input type="text" name="vendor_name" class="form-control" placeholder="เช่น: ร้านช่างประปา" style="border-radius: 12px; border: 2px solid #e5e7eb; padding: 0.75rem;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">จำนวนเงิน (บาท) <span class="text-danger">*</span></label>
                        <input type="number" name="amount" class="form-control" required step="0.01" min="0" placeholder="0.00" style="border-radius: 12px; border: 2px solid #e5e7eb; padding: 0.75rem;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">วันที่ <span class="text-danger">*</span></label>
                        <input type="date" name="expense_date" class="form-control" required style="border-radius: 12px; border: 2px solid #e5e7eb; padding: 0.75rem;">
                    </div>
                </div>
                <div class="modal-footer" style="background: #f9fafb; border: none; padding: 1.5rem 2rem;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 50px; font-weight: 600;">
                        <i class="bi bi-x-circle"></i> ยกเลิก
                    </button>
                    <button type="submit" name="add_expense" class="btn btn-danger" style="border-radius: 50px; font-weight: 600; background: linear-gradient(135deg, var(--danger-color) 0%, #dc2626 100%); border: none;">
                        <i class="bi bi-check-circle"></i> บันทึก
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Income Chart
const incomeData = <?= json_encode(array_values($incomeByType)) ?>;
const incomeLabels = <?= json_encode(array_map(function($type) {
    return match($type) {
        'rent' => 'ค่าเช่า',
        'electricity' => 'ค่าไฟ',
        'water' => 'ค่าน้ำ',
        'service' => 'ค่าบริการ',
        'other' => 'อื่นๆ',
        default => $type
    };
}, array_keys($incomeByType))) ?>;

const incomeChart = new Chart(document.getElementById('incomeChart'), {
    type: 'doughnut',
    data: {
        labels: incomeLabels,
        datasets: [{
            data: incomeData,
            backgroundColor: [
                'rgba(236, 72, 153, 0.8)',
                'rgba(245, 158, 11, 0.8)',
                'rgba(6, 182, 212, 0.8)',
                'rgba(139, 92, 246, 0.8)',
                'rgba(107, 114, 128, 0.8)'
            ],
            borderWidth: 3,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 15,
                    font: {
                        size: 12,
                        weight: 'bold'
                    }
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.label + ': ฿' + context.parsed.toLocaleString('th-TH', {minimumFractionDigits: 2});
                    }
                }
            }
        }
    }
});

// Expense Chart
const expenseData = <?= json_encode(array_values($expenseByType)) ?>;
const expenseLabels = <?= json_encode(array_map(function($type) {
    return match($type) {
        'maintenance' => 'ซ่อมแซม',
        'utilities' => 'สาธารณูปโภค',
        'salary' => 'เงินเดือน',
        'supplies' => 'วัสดุ',
        'tax' => 'ภาษี',
        'insurance' => 'ประกัน',
        'other' => 'อื่นๆ',
        default => $type
    };
}, array_keys($expenseByType))) ?>;

const expenseChart = new Chart(document.getElementById('expenseChart'), {
    type: 'doughnut',
    data: {
        labels: expenseLabels,
        datasets: [{
            data: expenseData,
            backgroundColor: [
                'rgba(239, 68, 68, 0.8)',
                'rgba(245, 158, 11, 0.8)',
                'rgba(16, 185, 129, 0.8)',
                'rgba(99, 102, 241, 0.8)',
                'rgba(220, 38, 38, 0.8)',
                'rgba(124, 58, 237, 0.8)',
                'rgba(107, 114, 128, 0.8)'
            ],
            borderWidth: 3,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 15,
                    font: {
                        size: 12,
                        weight: 'bold'
                    }
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.label + ': ฿' + context.parsed.toLocaleString('th-TH', {minimumFractionDigits: 2});
                    }
                }
            }
        }
    }
});
</script>
</body>
</html>
```