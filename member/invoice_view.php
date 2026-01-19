<?php
// ============================================
// ไฟล์: member/invoice_view.php
// คำอธิบาย: ดูใบเสร็จแบบละเอียด
// ============================================

require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';
require_once '../models/Invoice.php';
require_once '../models/SystemSettings.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();

$invoice = new Invoice($db);
$settings = new SystemSettings($db);

$invoice_id = $_GET['id'] ?? 0;
$invoiceData = $invoice->getById($invoice_id);

// ตรวจสอบสิทธิ์
if ($_SESSION['role'] == 'member') {
    $tenant = $db->prepare("SELECT tenant_id FROM tenants WHERE user_id = ?");
    $tenant->execute([$_SESSION['user_id']]);
    $tenantData = $tenant->fetch();
    
    if (!$tenantData || $invoiceData['tenant_id'] != $tenantData['tenant_id']) {
        die('ไม่มีสิทธิ์เข้าถึงใบเสร็จนี้');
    }
}

if (!$invoiceData) {
    die('ไม่พบใบเสร็จ');
}

// ดึงข้อมูลหอพัก
$dormName = $settings->get('dormitory_name');
$dormAddress = $settings->get('dormitory_address');
$dormPhone = $settings->get('dormitory_phone');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ใบเสร็จ <?php echo $invoiceData['invoice_number']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none; }
        }
        .invoice-box {
            max-width: 800px;
            margin: auto;
            padding: 30px;
            border: 1px solid #eee;
            box-shadow: 0 0 10px rgba(0, 0, 0, .15);
            font-size: 16px;
            line-height: 24px;
            color: #555;
        }
        .invoice-header {
            border-bottom: 3px solid #0d6efd;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container mt-4 mb-4">
        <div class="invoice-box">
            <!-- ปุ่มพิมพ์ -->
            <div class="no-print mb-3">
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="bi bi-printer"></i> พิมพ์
                </button>
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> กลับ
                </a>
            </div>

            <!-- Header -->
            <div class="invoice-header">
                <div class="row">
                    <div class="col-8">
                        <h2 class="text-primary"><?php echo $dormName; ?></h2>
                        <p class="mb-0"><?php echo $dormAddress; ?></p>
                        <p>โทร: <?php echo $dormPhone; ?></p>
                    </div>
                    <div class="col-4 text-end">
                        <h3 class="text-danger">ใบเสร็จ</h3>
                        <p class="mb-0"><strong><?php echo $invoiceData['invoice_number']; ?></strong></p>
                    </div>
                </div>
            </div>

            <!-- ข้อมูลผู้เช่า -->
            <div class="row mb-4">
                <div class="col-6">
                    <strong>ข้อมูลผู้เช่า:</strong><br>
                    ชื่อ: <?php echo $invoiceData['tenant_name']; ?><br>
                    ห้อง: <?php echo $invoiceData['room_number']; ?> (<?php echo $invoiceData['room_type']; ?>)<br>
                    โทร: <?php echo $invoiceData['phone']; ?>
                </div>
                <div class="col-6 text-end">
                    <strong>เดือน:</strong> <?php echo getThaiMonth($invoiceData['invoice_month']) . ' ' . toBuddhistYear($invoiceData['invoice_year']); ?><br>
                    <strong>วันที่ออกบิล:</strong> <?php echo formatThaiDate($invoiceData['created_at']); ?><br>
                    <strong>กำหนดชำระ:</strong> <span class="text-danger"><?php echo formatThaiDate($invoiceData['due_date']); ?></span>
                </div>
            </div>

            <!-- รายละเอียดค่าใช้จ่าย -->
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>รายการ</th>
                        <th width="150" class="text-end">จำนวน</th>
                        <th width="150" class="text-end">ราคา (บาท)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>ค่าเช่าห้อง</td>
                        <td class="text-end">1 เดือน</td>
                        <td class="text-end"><?php echo formatMoney($invoiceData['monthly_rent']); ?></td>
                    </tr>
                    <tr>
                        <td>
                            ค่าน้ำ<br>
                            <small class="text-muted">
                                (<?php echo formatMoney($invoiceData['water_previous'] ?? 0); ?> → 
                                <?php echo formatMoney($invoiceData['water_current'] ?? 0); ?> = 
                                <?php echo formatMoney($invoiceData['water_usage'] ?? 0); ?> ยูนิต)
                            </small>
                        </td>
                        <td class="text-end"><?php echo formatMoney($invoiceData['water_usage'] ?? 0); ?> ยูนิต</td>
                        <td class="text-end"><?php echo formatMoney($invoiceData['water_charge']); ?></td>
                    </tr>
                    <tr>
                        <td>
                            ค่าไฟ<br>
                            <small class="text-muted">
                                (<?php echo formatMoney($invoiceData['electric_previous'] ?? 0); ?> → 
                                <?php echo formatMoney($invoiceData['electric_current'] ?? 0); ?> = 
                                <?php echo formatMoney($invoiceData['electric_usage'] ?? 0); ?> ยูนิต)
                            </small>
                        </td>
                        <td class="text-end"><?php echo formatMoney($invoiceData['electric_usage'] ?? 0); ?> ยูนิต</td>
                        <td class="text-end"><?php echo formatMoney($invoiceData['electric_charge']); ?></td>
                    </tr>
                    <tr>
                        <td>ค่าขยะ</td>
                        <td class="text-end">1 เดือน</td>
                        <td class="text-end"><?php echo formatMoney($invoiceData['garbage_fee']); ?></td>
                    </tr>
                    <?php if ($invoiceData['previous_balance'] > 0): ?>
                        <tr>
                            <td class="text-danger">ค่าค้างชำระจากเดือนก่อน</td>
                            <td class="text-end">-</td>
                            <td class="text-end text-danger"><?php echo formatMoney($invoiceData['previous_balance']); ?></td>
                        </tr>
                    <?php endif; ?>
                    <?php if ($invoiceData['discount'] > 0): ?>
                        <tr>
                            <td class="text-success">ส่วนลด</td>
                            <td class="text-end">-</td>
                            <td class="text-end text-success">-<?php echo formatMoney($invoiceData['discount']); ?></td>
                        </tr>
                    <?php endif; ?>
                    <?php if ($invoiceData['other_charges'] > 0): ?>
                        <tr>
                            <td>
                                ค่าใช้จ่ายอื่นๆ
                                <?php if ($invoiceData['other_charges_note']): ?>
                                    <br><small class="text-muted"><?php echo $invoiceData['other_charges_note']; ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">-</td>
                            <td class="text-end"><?php echo formatMoney($invoiceData['other_charges']); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr class="table-primary">
                        <th colspan="2" class="text-end">รวมทั้งสิ้น:</th>
                        <th class="text-end fs-4"><?php echo formatMoney($invoiceData['total_amount']); ?> บาท</th>
                    </tr>
                </tfoot>
            </table>

            <!-- สถานะการชำระเงิน -->
            <div class="alert alert-<?php echo $invoiceData['payment_status'] == 'paid' ? 'success' : 'warning'; ?> text-center">
                <h5>
                    <?php echo getPaymentStatusBadge($invoiceData['payment_status']); ?>
                </h5>
                <?php if ($invoiceData['payment_status'] == 'paid'): ?>
                    <p class="mb-0">ชำระเมื่อ: <?php echo formatThaiDate($invoiceData['paid_date']); ?></p>
                    <p class="mb-0">จำนวน: ฿<?php echo formatMoney($invoiceData['paid_amount']); ?></p>
                <?php endif; ?>
            </div>

            <!-- หมายเหตุ -->
            <div class="mt-4 text-center">
                <p class="text-muted mb-0">*** กรุณาชำระเงินภายในกำหนด ***</p>
                <p class="text-muted">ขอบคุณที่ใช้บริการ</p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
