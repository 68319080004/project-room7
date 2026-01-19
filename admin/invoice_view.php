<?php
// ============================================
// ไฟล์: admin/invoice_edit.php  
// คำอธิบาย: แก้ไขบิลแบบ Manual ครบถ้วน 100%
// ============================================

require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';
require_once '../models/Invoice.php';

requireRole(['admin', 'owner']);

$database = new Database();
$db = $database->getConnection();
$invoice = new Invoice($db);

$invoice_id = $_GET['id'] ?? 0;
$invoiceData = $invoice->getById($invoice_id);

if (!$invoiceData) {
    die('ไม่พบใบเสร็จ');
}

$message = '';
$messageType = '';

// บันทึกการแก้ไข
if (isset($_POST['update_invoice'])) {
    $sql = "UPDATE invoices SET 
            monthly_rent = :monthly_rent,
            water_charge = :water_charge,
            electric_charge = :electric_charge,
            garbage_fee = :garbage_fee,
            previous_balance = :previous_balance,
            discount = :discount,
            other_charges = :other_charges,
            other_charges_note = :other_charges_note,
            total_amount = :total_amount
            WHERE invoice_id = :invoice_id";
    
    $stmt = $db->prepare($sql);
    
    $total = $_POST['monthly_rent'] + $_POST['water_charge'] + $_POST['electric_charge'] 
           + $_POST['garbage_fee'] + $_POST['previous_balance'] + $_POST['other_charges'] 
           - $_POST['discount'];
    
    $stmt->execute([
        ':monthly_rent' => $_POST['monthly_rent'],
        ':water_charge' => $_POST['water_charge'],
        ':electric_charge' => $_POST['electric_charge'],
        ':garbage_fee' => $_POST['garbage_fee'],
        ':previous_balance' => $_POST['previous_balance'],
        ':discount' => $_POST['discount'],
        ':other_charges' => $_POST['other_charges'],
        ':other_charges_note' => $_POST['other_charges_note'],
        ':total_amount' => $total,
        ':invoice_id' => $invoice_id
    ]);
    
    $message = 'บันทึกการแก้ไขสำเร็จ!';
    $messageType = 'success';
    
    // โหลดข้อมูลใหม่
    $invoiceData = $invoice->getById($invoice_id);
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขบิล - <?php echo $invoiceData['invoice_number']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="bi bi-pencil-square"></i> แก้ไขบิลแบบ Manual
                    </h1>
                    <a href="invoices.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> กลับ
                    </a>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="mb-0">
                                    <i class="bi bi-exclamation-triangle"></i> แก้ไขข้อมูลบิล (Manual Mode)
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-warning">
                                    <i class="bi bi-info-circle"></i>
                                    <strong>คำเตือน:</strong> โหมดนี้ใช้สำหรับกรณีพิเศษเท่านั้น เช่น มีค่าใช้จ่ายเพิ่มเติม หรือต้องการปรับยอดด้วยตนเอง
                                </div>

                                <form method="POST" id="editForm">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <strong>เลขที่บิล:</strong> <?php echo $invoiceData['invoice_number']; ?>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>ห้อง:</strong> <?php echo $invoiceData['room_number']; ?> - <?php echo $invoiceData['tenant_name']; ?>
                                        </div>
                                    </div>

                                    <hr>

                                    <div class="row mb-3">
                                        <label class="col-md-4 col-form-label">
                                            <i class="bi bi-house"></i> ค่าเช่าห้อง (บาท)
                                        </label>
                                        <div class="col-md-8">
                                            <input type="number" step="0.01" class="form-control" name="monthly_rent" 
                                                   value="<?php echo $invoiceData['monthly_rent']; ?>" required>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <label class="col-md-4 col-form-label">
                                            <i class="bi bi-droplet-fill text-info"></i> ค่าน้ำ (บาท)
                                        </label>
                                        <div class="col-md-8">
                                            <input type="number" step="0.01" class="form-control" name="water_charge" 
                                                   value="<?php echo $invoiceData['water_charge']; ?>" required>
                                            <?php if ($invoiceData['water_usage']): ?>
                                                <small class="text-muted">
                                                    มิเตอร์: <?php echo $invoiceData['water_previous']; ?> → <?php echo $invoiceData['water_current']; ?> 
                                                    = <?php echo $invoiceData['water_usage']; ?> ยูนิต
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <label class="col-md-4 col-form-label">
                                            <i class="bi bi-lightning-fill text-warning"></i> ค่าไฟ (บาท)
                                        </label>
                                        <div class="col-md-8">
                                            <input type="number" step="0.01" class="form-control" name="electric_charge" 
                                                   value="<?php echo $invoiceData['electric_charge']; ?>" required>
                                            <?php if ($invoiceData['electric_usage']): ?>
                                                <small class="text-muted">
                                                    มิเตอร์: <?php echo $invoiceData['electric_previous']; ?> → <?php echo $invoiceData['electric_current']; ?> 
                                                    = <?php echo $invoiceData['electric_usage']; ?> ยูนิต
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <label class="col-md-4 col-form-label">
                                            <i class="bi bi-trash"></i> ค่าขยะ (บาท)
                                        </label>
                                        <div class="col-md-8">
                                            <input type="number" step="0.01" class="form-control" name="garbage_fee" 
                                                   value="<?php echo $invoiceData['garbage_fee']; ?>" required>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <label class="col-md-4 col-form-label">
                                            <i class="bi bi-exclamation-circle text-danger"></i> ค่าค้างชำระ (บาท)
                                        </label>
                                        <div class="col-md-8">
                                            <input type="number" step="0.01" class="form-control" name="previous_balance" 
                                                   value="<?php echo $invoiceData['previous_balance']; ?>" required>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <label class="col-md-4 col-form-label">
                                            <i class="bi bi-tag-fill text-success"></i> ส่วนลด (บาท)
                                        </label>
                                        <div class="col-md-8">
                                            <input type="number" step="0.01" class="form-control" name="discount" 
                                                   value="<?php echo $invoiceData['discount']; ?>" required>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <label class="col-md-4 col-form-label">
                                            <i class="bi bi-plus-circle"></i> ค่าใช้จ่ายอื่นๆ (บาท)
                                        </label>
                                        <div class="col-md-8">
                                            <input type="number" step="0.01" class="form-control" name="other_charges" 
                                                   value="<?php echo $invoiceData['other_charges']; ?>" required>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <label class="col-md-4 col-form-label">
                                            <i class="bi bi-chat-left-text"></i> หมายเหตุ
                                        </label>
                                        <div class="col-md-8">
                                            <textarea class="form-control" name="other_charges_note" rows="3" 
                                                      placeholder="ระบุรายละเอียดค่าใช้จ่ายอื่นๆ (ถ้ามี)"><?php echo $invoiceData['other_charges_note']; ?></textarea>
                                        </div>
                                    </div>

                                    <hr>

                                    <div class="row mb-3">
                                        <label class="col-md-4 col-form-label">
                                            <strong>ยอดรวมทั้งสิ้น:</strong>
                                        </label>
                                        <div class="col-md-8">
                                            <input type="text" class="form-control form-control-lg fw-bold text-primary" 
                                                   id="total_display" readonly>
                                            <small class="text-muted">คำนวณอัตโนมัติจากข้อมูลด้านบน</small>
                                        </div>
                                    </div>

                                    <div class="d-grid gap-2">
                                        <button type="submit" name="update_invoice" class="btn btn-success btn-lg">
                                            <i class="bi bi-save"></i> บันทึกการแก้ไข
                                        </button>
                                        <a href="invoice_view.php?id=<?php echo $invoice_id; ?>" class="btn btn-secondary" target="_blank">
                                            <i class="bi bi-eye"></i> ดูตัวอย่างบิล
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-lightbulb"></i> คำแนะนำ</h5>
                            </div>
                            <div class="card-body">
                                <h6 class="text-primary">📝 การใช้งาน:</h6>
                                <ul class="small">
                                    <li>กรอกข้อมูลทุกช่อง ระบบจะคำนวณยอดรวมอัตโนมัติ</li>
                                    <li>ค่าค้างชำระ = ยอดที่ยังไม่จ่ายจากเดือนก่อน</li>
                                    <li>ส่วนลด = จำนวนเงินที่ลดให้</li>
                                    <li>ค่าใช้จ่ายอื่นๆ = เช่น ค่าซ่อม, ค่าปรับ</li>
                                </ul>

                                <h6 class="text-danger mt-3">⚠️ ข้อควรระวัง:</h6>
                                <ul class="small">
                                    <li>การแก้ไขจะเขียนทับข้อมูลเดิมทันที</li>
                                    <li>ควรตรวจสอบยอดให้ถูกต้องก่อนบันทึก</li>
                                    <li>การแก้ไขจะไม่กระทบมิเตอร์</li>
                                </ul>

                                <h6 class="text-success mt-3">💡 เคล็ดลับ:</h6>
                                <ul class="small">
                                    <li>ใช้โหมดนี้เมื่อมีกรณีพิเศษ</li>
                                    <li>ระบุหมายเหตุให้ชัดเจน</li>
                                    <li>สามารถดูตัวอย่างก่อนบันทึกได้</li>
                                </ul>
                            </div>
                        </div>

                        <div class="card bg-info bg-opacity-10 mt-3">
                            <div class="card-body">
                                <h6 class="text-info">
                                    <i class="bi bi-info-circle"></i> ข้อมูลบิลปัจจุบัน
                                </h6>
                                <table class="table table-sm table-borderless small mb-0">
                                    <tr>
                                        <td><strong>เดือน:</strong></td>
                                        <td><?php echo getThaiMonth($invoiceData['invoice_month']) . ' ' . toBuddhistYear($invoiceData['invoice_year']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>สถานะ:</strong></td>
                                        <td><?php echo getPaymentStatusBadge($invoiceData['payment_status']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>ออกบิลเมื่อ:</strong></td>
                                        <td><?php echo formatThaiDate($invoiceData['created_at']); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function calculateTotal() {
            const rent = parseFloat(document.querySelector('[name="monthly_rent"]').value) || 0;
            const water = parseFloat(document.querySelector('[name="water_charge"]').value) || 0;
            const electric = parseFloat(document.querySelector('[name="electric_charge"]').value) || 0;
            const garbage = parseFloat(document.querySelector('[name="garbage_fee"]').value) || 0;
            const previous = parseFloat(document.querySelector('[name="previous_balance"]').value) || 0;
            const discount = parseFloat(document.querySelector('[name="discount"]').value) || 0;
            const other = parseFloat(document.querySelector('[name="other_charges"]').value) || 0;
            
            const total = rent + water + electric + garbage + previous + other - discount;
            
            document.getElementById('total_display').value = '฿' + total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }

        document.querySelectorAll('input[type="number"]').forEach(input => {
            input.addEventListener('input', calculateTotal);
        });

        calculateTotal();
    </script>
</body>
</html>