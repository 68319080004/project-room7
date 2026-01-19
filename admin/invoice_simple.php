<?php
// ============================================
// ไฟล์: admin/invoice_simple.php
// คำอธิบาย: แบบง่าย - แค่ดาวน์โหลด PDF (ไม่ต้องส่ง)
// ============================================
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ดาวน์โหลดบิล</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-download"></i> ดาวน์โหลดบิลทั้งหมด</h5>
            </div>
            <div class="card-body">
                <p>เลือกเดือน/ปี เพื่อดาวน์โหลดบิลทั้งหมด</p>
                
                <form method="POST" action="download_all_invoices.php">
                    <div class="row">
                        <div class="col-md-4">
                            <select name="month" class="form-select" required>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?php echo $m; ?>" <?php echo $m == date('n') ? 'selected' : ''; ?>>
                                        <?php echo getThaiMonth($m); ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <select name="year" class="form-select" required>
                                <?php for ($y = date('Y') - 1; $y <= date('Y') + 1; $y++): ?>
                                    <option value="<?php echo $y; ?>" <?php echo $y == date('Y') ? 'selected' : ''; ?>>
                                        <?php echo toBuddhistYear($y); ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="bi bi-download"></i> ดาวน์โหลดทั้งหมด (ZIP)
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-printer"></i> หรือพิมพ์บิลทีละใบ</h5>
            </div>
            <div class="card-body">
                <ol>
                    <li>ไปที่เมนู "ใบเสร็จ/บิล"</li>
                    <li>คลิกปุ่ม <span class="badge bg-danger">PDF</span> ที่บิลที่ต้องการ</li>
                    <li>พิมพ์หรือบันทึกไฟล์</li>
                </ol>
            </div>
        </div>
    </div>
</body>
</html>