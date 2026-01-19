<?php
// ============================================
// ไฟล์: admin/invoice_pdf.php
// คำอธิบาย: แสดงบิลแบบ HTML พิมพ์ได้เลย (ไม่ต้อง Library)
// ============================================

require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';
require_once '../models/Invoice.php';
require_once '../models/SystemSettings.php';

$database = new Database();
$db = $database->getConnection();

$invoice = new Invoice($db);
$settings = new SystemSettings($db);

$invoice_id = $_GET['id'] ?? 0;
$invoiceData = $invoice->getById($invoice_id);

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
            .no-print { display: none !important; }
            body { background: white; }
            .container { max-width: 100%; }
        }
        
        body {
            background-color: #f8f9fa;
            padding: 20px;
        }
        
        .invoice-box {
            max-width: 800px;
            margin: auto;
            padding: 40px;
            background: white;
            border: 1px solid #ddd;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        .invoice-header {
            border-bottom: 3px solid #0d6efd;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .invoice-title {
            color: #dc3545;
            font-size: 32px;
            font-weight: bold;
        }
        
        .info-table td {
            padding: 5px 0;
        }
        
        .items-table {
            width: 100%;
            margin: 30px 0;
        }
        
        .items-table th {
            background-color: #0d6efd;
            color: white;
            padding: 12px;
            text-align: left;
        }
        
        .items-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #ddd;
        }
        
        .total-row {
            background-color: #e7f3ff;
            font-weight: bold;
            font-size: 20px;
        }
        
        .footer-note {
            margin-top: 40px;
            text-align: center;
            color: #666;
        }
    </style>
</head>
<body>
    <!-- ปุ่มด้านบน (ไม่พิมพ์) -->
    <div class="no-print text-center mb-3">
        <button onclick="window.print()" class="btn btn-primary btn-lg">
            <i class="bi bi-printer"></i> พิมพ์ / บันทึก PDF
        </button>
        <a href="invoices.php" class="btn btn-secondary btn-lg">
            <i class="bi bi-arrow-left"></i> กลับ
        </a>
        <a href="?id=<?php echo $invoice_id; ?>&download=1" class="btn btn-success btn-lg">
            <i class="bi bi-download"></i> ดาวน์โหลด HTML
        </a>
    </div>

    <?php
    // ถ้ากด Download
    if (isset($_GET['download'])) {
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: attachment; filename="Invoice_' . $invoiceData['invoice_number'] . '.html"');
    }
    ?>

    <div class="invoice-box">
        <!-- Header -->
        <div class="invoice-header">
            <div class="row">
                <div class="col-8">
                    <h2 class="text-primary mb-2"><?php echo $dormName; ?></h2>
                    <p class="mb-1"><?php echo $dormAddress; ?></p>
                    <p class="mb-0">โทร: <?php echo $dormPhone; ?></p>
                </div>
                <div class="col-4 text-end">
                    <div class="invoice-title">ใบเสร็จรับเงิน</div>
                    <h5 class="mt-2"><?php echo $invoiceData['invoice_number']; ?></h5>
                </div>
            </div>
        </div>

        <!-- ข้อมูลผู้เช่า -->
        <div class="row mb-4">
            <div class="col-6">
                <h6 class="text-primary fw-bold">ข้อมูลผู้เช่า:</h6>
                <table class="info-table">
                    <tr>
                        <td width="80"><strong>ชื่อ:</strong></td>
                        <td><?php echo $invoiceData['tenant_name']; ?></td>
                    </tr>
                    <tr>
                        <td><strong>ห้อง:</strong></td>
                        <td><?php echo $invoiceData['room_number']; ?> (<?php echo $invoiceData['room_type']; ?>)</td>
                    </tr>
                    <tr>
                        <td><strong>โทร:</strong></td>
                        <td><?php echo $invoiceData['phone']; ?></td>
                    </tr>
                </table>
            </div>
            <div class="col-6 text-end">
                <h6 class="text-primary fw-bold">รายละเอียดบิล:</h6>
                <table class="info-table" style="margin-left: auto;">
                    <tr>
                        <td width="120"><strong>เดือน:</strong></td>
                        <td><?php echo getThaiMonth($invoiceData['invoice_month']) . ' ' . toBuddhistYear($invoiceData['invoice_year']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>วันที่ออกบิล:</strong></td>
                        <td><?php echo formatThaiDate($invoiceData['created_at']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>กำหนดชำระ:</strong></td>
                        <td class="text-danger fw-bold"><?php echo formatThaiDate($invoiceData['due_date']); ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- ตารางรายการ -->
        <table class="items-table table table-bordered">
            <thead>
                <tr>
                    <th>รายการ</th>
                    <th width="150" class="text-center">จำนวน</th>
                    <th width="150" class="text-end">ราคา (บาท)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>ค่าเช่าห้อง</td>
                    <td class="text-center">1 เดือน</td>
                    <td class="text-end"><?php echo formatMoney($invoiceData['monthly_rent']); ?></td>
                </tr>
                <tr>
                    <td>
                        ค่าน้ำ<br>
                        <small class="text-muted">
                            มิเตอร์: <?php echo formatMoney($invoiceData['water_previous'] ?? 0); ?> → 
                            <?php echo formatMoney($invoiceData['water_current'] ?? 0); ?> = 
                            <strong><?php echo formatMoney($invoiceData['water_usage'] ?? 0); ?> ยูนิต</strong>
                        </small>
                    </td>
                    <td class="text-center"><?php echo formatMoney($invoiceData['water_usage'] ?? 0); ?> ยูนิต</td>
                    <td class="text-end text-info fw-bold"><?php echo formatMoney($invoiceData['water_charge']); ?></td>
                </tr>
                <tr>
                    <td>
                        ค่าไฟ<br>
                        <small class="text-muted">
                            มิเตอร์: <?php echo formatMoney($invoiceData['electric_previous'] ?? 0); ?> → 
                            <?php echo formatMoney($invoiceData['electric_current'] ?? 0); ?> = 
                            <strong><?php echo formatMoney($invoiceData['electric_usage'] ?? 0); ?> ยูนิต</strong>
                        </small>
                    </td>
                    <td class="text-center"><?php echo formatMoney($invoiceData['electric_usage'] ?? 0); ?> ยูนิต</td>
                    <td class="text-end text-warning fw-bold"><?php echo formatMoney($invoiceData['electric_charge']); ?></td>
                </tr>
                <tr>
                    <td>ค่าขยะ</td>
                    <td class="text-center">1 เดือน</td>
                    <td class="text-end"><?php echo formatMoney($invoiceData['garbage_fee']); ?></td>
                </tr>
                
                <?php if ($invoiceData['previous_balance'] > 0): ?>
                    <tr class="table-danger">
                        <td><strong>ค่าค้างชำระจากเดือนก่อน</strong></td>
                        <td class="text-center">-</td>
                        <td class="text-end text-danger fw-bold"><?php echo formatMoney($invoiceData['previous_balance']); ?></td>
                    </tr>
                <?php endif; ?>
                
                <?php if ($invoiceData['discount'] > 0): ?>
                    <tr class="table-success">
                        <td><strong>ส่วนลด</strong></td>
                        <td class="text-center">-</td>
                        <td class="text-end text-success fw-bold">-<?php echo formatMoney($invoiceData['discount']); ?></td>
                    </tr>
                <?php endif; ?>
                
                <?php if ($invoiceData['other_charges'] > 0): ?>
                    <tr>
                        <td>
                            <strong>ค่าใช้จ่ายอื่นๆ</strong>
                            <?php if ($invoiceData['other_charges_note']): ?>
                                <br><small class="text-muted"><?php echo $invoiceData['other_charges_note']; ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">-</td>
                        <td class="text-end"><?php echo formatMoney($invoiceData['other_charges']); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="2" class="text-end">รวมทั้งสิ้น:</td>
                    <td class="text-end text-primary fs-4">
                        <?php echo formatMoney($invoiceData['total_amount']); ?> บาท
                    </td>
                </tr>
            </tfoot>
        </table>

        <!-- สถานะการชำระ -->
        <div class="alert alert-<?php echo $invoiceData['payment_status'] == 'paid' ? 'success' : 'warning'; ?> text-center">
            <h5><?php echo getPaymentStatusBadge($invoiceData['payment_status']); ?></h5>
            <?php if ($invoiceData['payment_status'] == 'paid'): ?>
                <p class="mb-0">ชำระเมื่อ: <?php echo formatThaiDate($invoiceData['paid_date']); ?> | 
                จำนวน: ฿<?php echo formatMoney($invoiceData['paid_amount']); ?></p>
            <?php endif; ?>
        </div>

        <!-- หมายเหตุ -->
        <div class="footer-note">
            <p class="mb-1"><strong>*** กรุณาชำระเงินภายในกำหนด ***</strong></p>
            <p class="mb-0">ขอบคุณที่ใช้บริการ</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto print ถ้ามี query string ?print=1
        if (window.location.search.includes('print=1')) {
            window.onload = function() {
                setTimeout(() => window.print(), 500);
            }
        }
    </script>
</body>
</html>