<?php
// ============================================
// ไฟล์: member/payment_upload.php
// คำอธิบาย: หน้าอัปโหลดสลิปชำระเงิน
// ============================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../models/Invoice.php';
require_once __DIR__ . '/../models/Payment.php';
require_once __DIR__ . '/../models/Tenant.php';

requireRole('member');

$database = new Database();
$db = $database->getConnection();

$invoice = new Invoice($db);
$payment = new Payment($db);
$tenant = new Tenant($db);

// ตรวจสอบสิทธิ์
$tenantData = $tenant->getByUserId($_SESSION['user_id']);
if (!$tenantData) {
    die('ไม่พบข้อมูลผู้เช่า');
}

$invoice_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$invoiceData = $invoice->getById($invoice_id);

if (!$invoiceData || $invoiceData['tenant_id'] != $tenantData['tenant_id']) {
    die('ไม่พบใบเสร็จหรือไม่มีสิทธิ์เข้าถึง');
}

$message = '';
$messageType = '';

// อัปโหลดสลิป
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $payment_amount = $_POST['payment_amount'];
    $payment_date = $_POST['payment_date'];
    $bank_name = $_POST['bank_name'];
    $note = $_POST['note'];
    
    // Upload ไฟล์
    $slip_filename = '';
    if (isset($_FILES['payment_slip']) && $_FILES['payment_slip']['error'] == 0) {
        $slip_filename = uploadFile($_FILES['payment_slip']);
        
        if (!$slip_filename) {
            $message = 'ไม่สามารถอัปโหลดไฟล์ได้ กรุณาตรวจสอบไฟล์และลองใหม่';
            $messageType = 'danger';
        }
    } else {
        $message = 'กรุณาแนบสลิปการโอนเงิน';
        $messageType = 'danger';
    }
    
    if ($slip_filename) {
        $paymentData = [
            'invoice_id' => $invoice_id,
            'payment_amount' => $payment_amount,
            'payment_method' => 'transfer',
            'payment_slip' => $slip_filename,
            'payment_date' => $payment_date,
            'payment_time' => date('H:i:s'),
            'bank_name' => $bank_name,
            'transfer_ref' => '',
            'note' => $note,
            'payment_status' => 'pending'
        ];
        
        if ($payment->create($paymentData)) {
            $message = 'แจ้งการชำระเงินสำเร็จ! รอเจ้าหน้าที่ตรวจสอบ';
            $messageType = 'success';
            header("refresh:2;url=dashboard.php");
        } else {
            $message = 'เกิดข้อผิดพลาดในการบันทึก';
            $messageType = 'danger';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แจ้งชำระเงิน - ระบบจัดการหอพัก</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <nav class="navbar navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-building"></i> ระบบจัดการหอพัก
            </a>
            <a href="dashboard.php" class="btn btn-outline-light btn-sm">
                <i class="bi bi-arrow-left"></i> กลับ
            </a>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0"><i class="bi bi-cash-coin"></i> แจ้งชำระเงิน</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-<?php echo $messageType; ?>">
                                <?php echo $message; ?>
                            </div>
                        <?php endif; ?>

                        <!-- ข้อมูลใบเสร็จ -->
                        <div class="alert alert-info">
                            <h5>รายละเอียดใบเสร็จ</h5>
                            <hr>
                            <table class="table table-borderless mb-0">
                                <tr>
                                    <th width="40%">เลขที่ใบเสร็จ:</th>
                                    <td><?php echo $invoiceData['invoice_number']; ?></td>
                                </tr>
                                <tr>
                                    <th>ห้อง:</th>
                                    <td><?php echo $invoiceData['room_number']; ?></td>
                                </tr>
                                <tr>
                                    <th>เดือน/ปี:</th>
                                    <td><?php echo getThaiMonth($invoiceData['invoice_month']) . ' ' . toBuddhistYear($invoiceData['invoice_year']); ?></td>
                                </tr>
                                <tr>
                                    <th>ยอดที่ต้องชำระ:</th>
                                    <td><h4 class="text-danger mb-0">฿<?php echo formatMoney($invoiceData['total_amount']); ?></h4></td>
                                </tr>
                            </table>
                        </div>

                        <!-- ฟอร์มอัปโหลดสลิป -->
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label">จำนวนเงินที่โอน <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" class="form-control form-control-lg" 
                                       name="payment_amount" value="<?php echo $invoiceData['total_amount']; ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">วันที่โอนเงิน <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="payment_date" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">ธนาคาร</label>
                                <select class="form-select" name="bank_name">
                                    <option value="">เลือกธนาคาร</option>
                                    <option value="ธนาคารกรุงเทพ">ธนาคารกรุงเทพ</option>
                                    <option value="ธนาคารกสิกรไทย">ธนาคารกสิกรไทย</option>
                                    <option value="ธนาคารไทยพาณิชย์">ธนาคารไทยพาณิชย์</option>
                                    <option value="ธนาคารกรุงไทย">ธนาคารกรุงไทย</option>
                                    <option value="ธนาคารทหารไทยธนชาต">ธนาคารทหารไทยธนชาต</option>
                                    <option value="PromptPay">PromptPay</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">แนบสลิปการโอนเงิน <span class="text-danger">*</span></label>
                                <input type="file" class="form-control" name="payment_slip" 
                                       accept="image/*,.pdf" required>
                                <small class="text-muted">รองรับไฟล์: JPG, PNG, PDF (ไม่เกิน 5MB)</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">หมายเหตุ</label>
                                <textarea class="form-control" name="note" rows="3" 
                                          placeholder="ระบุรายละเอียดเพิ่มเติม (ถ้ามี)"></textarea>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="bi bi-check-circle"></i> ยืนยันการชำระเงิน
                                </button>
                                <a href="dashboard.php" class="btn btn-secondary">
                                    <i class="bi bi-x-circle"></i> ยกเลิก
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>