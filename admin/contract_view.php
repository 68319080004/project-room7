<?php
// ============================================
// ไฟล์: admin/contract_view.php
// คำอธิบาย: ดูและพิมพ์สัญญาเช่า
// ============================================

require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';
require_once '../models/Contract.php';
require_once '../models/SystemSettings.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();

$contract = new Contract($db);
$settings = new SystemSettings($db);

$contract_id = $_GET['id'] ?? 0;
$contractData = $contract->getById($contract_id);

if (!$contractData) {
    die('ไม่พบสัญญา');
}

// อัพเดทสถานะ
if (isset($_POST['update_status'])) {
    $new_status = $_POST['new_status'];
    if ($contract->updateStatus($contract_id, $new_status)) {
        $contractData = $contract->getById($contract_id);
        $message = 'อัพเดทสถานะสำเร็จ!';
    }
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
    <title>สัญญาเช่า <?php echo $contractData['contract_number']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white; }
            .contract-box { box-shadow: none; border: none; }
        }
        
        body {
            background-color: #f8f9fa;
            padding: 20px;
        }
        
        .contract-box {
            max-width: 900px;
            margin: auto;
            padding: 40px;
            background: white;
            border: 2px solid #333;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        .contract-header {
            text-align: center;
            border-bottom: 3px double #333;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .contract-title {
            font-size: 28px;
            font-weight: bold;
            margin: 20px 0;
        }
        
        .contract-number {
            font-size: 18px;
            color: #666;
        }
        
        .contract-content {
            line-height: 2;
            text-align: justify;
            text-indent: 50px;
        }
        
        .contract-content p {
            margin-bottom: 15px;
        }
        
        .signature-section {
            margin-top: 50px;
            page-break-inside: avoid;
        }
        
        .signature-box {
            border-top: 1px solid #333;
            padding-top: 10px;
            text-align: center;
            min-width: 200px;
        }
        
        .terms-list {
            padding-left: 0;
            list-style: none;
            counter-reset: terms-counter;
        }
        
        .terms-list li {
            counter-increment: terms-counter;
            margin-bottom: 15px;
            text-indent: 0;
        }
        
        .terms-list li::before {
            content: counter(terms-counter) ". ";
            font-weight: bold;
            margin-right: 10px;
        }
        
        .highlight-box {
            background: #f8f9fa;
            border-left: 4px solid #0d6efd;
            padding: 15px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <!-- ปุ่มควบคุม -->
    <div class="no-print text-center mb-3">
        <button onclick="window.print()" class="btn btn-primary btn-lg">
            <i class="bi bi-printer"></i> พิมพ์สัญญา
        </button>
        <a href="contracts.php" class="btn btn-secondary btn-lg">
            <i class="bi bi-arrow-left"></i> กลับ
        </a>
        
        <?php if ($contractData['contract_status'] == 'draft'): ?>
            <button type="button" class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#activateModal">
                <i class="bi bi-check-circle"></i> เปิดใช้งานสัญญา
            </button>
        <?php endif; ?>
    </div>

    <div class="contract-box">
        <!-- Header -->
        <div class="contract-header">
            <h2 style="margin: 0;"><?php echo $dormName; ?></h2>
            <p style="margin: 5px 0;"><?php echo $dormAddress; ?></p>
            <p style="margin: 5px 0;">โทร: <?php echo $dormPhone; ?></p>
            
            <div class="contract-title">สัญญาเช่าห้องพัก</div>
            <div class="contract-number">เลขที่สัญญา: <?php echo $contractData['contract_number']; ?></div>
            
            <?php if ($contractData['contract_status'] == 'draft'): ?>
                <div class="badge bg-warning fs-6 mt-2">DRAFT - ฉบับร่าง</div>
            <?php elseif ($contractData['contract_status'] == 'active'): ?>
                <div class="badge bg-success fs-6 mt-2">ACTIVE - มีผลบังคับใช้</div>
            <?php endif; ?>
        </div>

        <!-- เนื้อหาสัญญา -->
        <div class="contract-content">
            <p>
                สัญญาฉบับนี้ทำขึ้นเมื่อวันที่ <strong><?php echo formatThaiDate($contractData['start_date']); ?></strong> 
                ระหว่าง <strong><?php echo $contractData['landlord_name']; ?></strong> 
                เลขประจำตัวประชาชน <strong><?php echo $contractData['landlord_id_card']; ?></strong> 
                ซึ่งต่อไปในสัญญานี้จะเรียกว่า <strong>"ผู้ให้เช่า"</strong> ฝ่ายหนึ่ง
            </p>
            
            <p>
                กับ <strong><?php echo $contractData['tenant_name']; ?></strong> 
                เลขประจำตัวประชาชน <strong><?php echo $contractData['tenant_id_card']; ?></strong> 
                โทรศัพท์ <strong><?php echo $contractData['phone']; ?></strong>
                ซึ่งต่อไปในสัญญานี้จะเรียกว่า <strong>"ผู้เช่า"</strong> อีกฝ่ายหนึ่ง
            </p>
            
            <p>
                ทั้งสองฝ่ายได้ตกลงทำสัญญาเช่าห้องพักกันโดยมีข้อความดังต่อไปนี้
            </p>
        </div>

        <!-- รายละเอียดห้องและค่าใช้จ่าย -->
        <div class="highlight-box">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-2"><strong><i class="bi bi-door-open"></i> รายละเอียดห้องเช่า</strong></p>
                    <p class="mb-1">ห้องเลขที่: <strong><?php echo $contractData['room_number']; ?></strong></p>
                    <p class="mb-1">ประเภท: <strong><?php echo $contractData['room_type']; ?></strong></p>
                    <p class="mb-1">ชั้น: <strong><?php echo $contractData['floor']; ?></strong></p>
                    <?php if ($contractData['building_name']): ?>
                        <p class="mb-1">อาคาร: <strong><?php echo $contractData['building_name']; ?></strong></p>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <p class="mb-2"><strong><i class="bi bi-cash-stack"></i> ค่าใช้จ่าย</strong></p>
                    <p class="mb-1">ค่าเช่า: <strong>฿<?php echo number_format($contractData['monthly_rent'], 2); ?>/เดือน</strong></p>
                    <p class="mb-1">เงินประกัน: <strong>฿<?php echo number_format($contractData['deposit_amount'], 2); ?></strong></p>
                    <p class="mb-1">ค่าน้ำ: <strong>฿<?php echo number_format($contractData['water_rate'], 2); ?>/ยูนิต</strong></p>
                    <p class="mb-1">ค่าไฟ: <strong>฿<?php echo number_format($contractData['electric_rate'], 2); ?>/ยูนิต</strong></p>
                    <p class="mb-1">ค่าขยะ: <strong>฿<?php echo number_format($contractData['garbage_fee'], 2); ?>/เดือน</strong></p>
                </div>
            </div>
        </div>

        <!-- ระยะเวลาสัญญา -->
        <div class="contract-content">
            <p>
                <strong>ระยะเวลาสัญญา:</strong> สัญญานี้มีผลบังคับใช้ตั้งแต่วันที่ 
                <strong><?php echo formatThaiDate($contractData['start_date']); ?></strong> 
                จนถึงวันที่ <strong><?php echo formatThaiDate($contractData['end_date']); ?></strong>
                รวมระยะเวลา <strong><?php 
                    $start = new DateTime($contractData['start_date']);
                    $end = new DateTime($contractData['end_date']);
                    $interval = $start->diff($end);
                    echo $interval->m + ($interval->y * 12);
                ?> เดือน</strong>
            </p>
        </div>

        <!-- เงื่อนไขและข้อตกลง -->
        <div class="contract-content">
            <p style="text-indent: 0;"><strong>เงื่อนไขและข้อตกลง:</strong></p>
            <ol class="terms-list">
                <?php
                $terms = explode("\n", $contractData['contract_terms']);
                foreach ($terms as $term) {
                    $term = trim($term);
                    if (!empty($term) && !preg_match('/^\d+\./', $term)) {
                        echo "<li>" . nl2br(htmlspecialchars($term)) . "</li>";
                    }
                }
                ?>
            </ol>
        </div>

        <!-- ลงนาม -->
        <div class="signature-section">
            <p class="contract-content">
                สัญญานี้ทำขึ้นเป็นสองฉบับมีข้อความตรงกัน คู่สัญญาต่างได้อ่านและเข้าใจข้อความในสัญญาดีแล้ว 
                จึงได้ลงลายมือชื่อไว้เป็นสำคัญต่อหน้าพยาน
            </p>
            
            <div class="row mt-5">
                <div class="col-md-6 text-center mb-4">
                    <div style="height: 80px;"></div>
                    <div class="signature-box">
                        (<?php echo $contractData['landlord_name']; ?>)
                    </div>
                    <p class="mt-2 mb-0">ผู้ให้เช่า</p>
                    <p class="text-muted small mb-0">
                        วันที่ ......./......./..........
                    </p>
                </div>
                
                <div class="col-md-6 text-center mb-4">
                    <div style="height: 80px;"></div>
                    <div class="signature-box">
                        (<?php echo $contractData['tenant_name']; ?>)
                    </div>
                    <p class="mt-2 mb-0">ผู้เช่า</p>
                    <p class="text-muted small mb-0">
                        วันที่ ......./......./..........
                    </p>
                </div>
            </div>
            
            <?php if ($contractData['witness_name']): ?>
                <div class="row mt-4">
                    <div class="col-md-6 text-center mb-4">
                        <div style="height: 80px;"></div>
                        <div class="signature-box">
                            (<?php echo $contractData['witness_name']; ?>)
                        </div>
                        <p class="mt-2 mb-0">พยาน</p>
                        <p class="text-muted small mb-0">
                            วันที่ ......./......./..........
                        </p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <div class="text-center mt-5 pt-4 border-top">
            <p class="text-muted small mb-0">
                สัญญาฉบับนี้พิมพ์จาก <?php echo $dormName; ?> 
                เมื่อวันที่ <?php echo date('d/m/Y H:i:s'); ?>
            </p>
        </div>
    </div>

    <!-- Modal เปิดใช้งานสัญญา -->
    <div class="modal fade" id="activateModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="new_status" value="active">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">
                            <i class="bi bi-check-circle"></i> เปิดใช้งานสัญญา
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>คำเตือน:</strong> เมื่อเปิดใช้งานแล้ว สัญญาจะมีผลบังคับใช้ทันที
                        </div>
                        <p>ยืนยันการเปิดใช้งานสัญญาเลขที่ <strong><?php echo $contractData['contract_number']; ?></strong>?</p>
                        <p><strong>ผู้เช่า:</strong> <?php echo $contractData['tenant_name']; ?></p>
                        <p><strong>ห้อง:</strong> <?php echo $contractData['room_number']; ?></p>
                        <p><strong>ระยะเวลา:</strong> 
                            <?php echo formatThaiDate($contractData['start_date']); ?> - 
                            <?php echo formatThaiDate($contractData['end_date']); ?>
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" name="update_status" class="btn btn-success">
                            <i class="bi bi-check-circle"></i> ยืนยันเปิดใช้งาน
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>