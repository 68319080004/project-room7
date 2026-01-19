<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php'; 

// 1. สร้างตัวเชื่อมต่อฐานข้อมูล
$db = new Database();
$pdo = $db->getConnection();

$id = $_GET['id'] ?? 0;

// 2. ดึงข้อมูล
$sql = "SELECT 
            m.*,
            r.room_number,
            COALESCE(t.full_name, u.full_name) AS requester_name,
            tech.full_name AS technician_name
        FROM maintenance_requests m
        LEFT JOIN rooms r ON m.room_id = r.room_id
        LEFT JOIN tenants t ON m.tenant_id = t.tenant_id
        LEFT JOIN users u ON m.requested_by_user_id = u.user_id
        LEFT JOIN users tech ON m.assigned_to = tech.user_id
        WHERE m.request_id = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$r = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$r) die("ไม่พบรายการแจ้งซ่อมนี้");
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายละเอียดการแจ้งซ่อม</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0">รายละเอียดงานซ่อม #<?= htmlspecialchars($r['request_number'] ?? $r['request_id']) ?></h4>
            <a href="../dashboard.php" class="btn btn-light btn-sm">กลับหน้าหลัก</a>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <p><strong>ห้อง:</strong> <?= htmlspecialchars($r['room_number']) ?></p>
                    <p><strong>ผู้แจ้ง:</strong> <?= htmlspecialchars($r['requester_name'] ?? 'ไม่ระบุ') ?></p>
                    <p><strong>ประเภท:</strong> <?= htmlspecialchars($r['issue_type'] ?? '-') ?></p>
                    <p><strong>วันที่แจ้ง:</strong> <?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>สถานะปัจจุบัน:</strong> 
                        <span class="badge bg-secondary"><?= htmlspecialchars($r['request_status']) ?></span>
                    </p>
                    <p><strong>ช่างผู้รับผิดชอบ:</strong> <?= htmlspecialchars($r['technician_name'] ?? 'ยังไม่มอบหมาย') ?></p>
                </div>
            </div>

            <hr>

            <div class="mb-4">
                <h5>รายละเอียดปัญหา:</h5>
                <div class="alert alert-light border">
                    <?= nl2br(htmlspecialchars($r['issue_description'])) ?>
                </div>
            </div>

            <?php if (!empty($r['images'])): ?>
            <div class="mb-4">
                <h5>รูปภาพประกอบ:</h5>
                <div class="d-flex flex-wrap gap-2">
                <?php 
                    // 1. ลองแปลง JSON กลับเป็น Array ก่อน
                    $imgs = json_decode($r['images'], true);
                    
                    // 2. ถ้าแปลงไม่ได้ (อาจเป็นข้อมูลเก่า) ให้ลองลบอักขระพิเศษแล้วตัดคำ
                    if (!is_array($imgs)) {
                        $cleanStr = str_replace(['[', ']', '"'], '', $r['images']);
                        $imgs = explode(',', $cleanStr);
                    }

                    foreach($imgs as $img):
                        $cleanImg = trim($img); // ลบช่องว่างหัวท้าย
                        if($cleanImg == '') continue;
                ?>
                    <a href="../../uploads/maintenance/<?= htmlspecialchars($cleanImg) ?>" target="_blank">
                        <img src="../../uploads/maintenance/<?= htmlspecialchars($cleanImg) ?>" 
                             class="img-thumbnail" 
                             style="max-height: 200px; object-fit: cover;">
                    </a>
                <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <hr>

            <div class="row g-3">
                <div class="col-md-6">
                    <form method="post" action="assign.php" class="card p-3 bg-light">
                        <h6>มอบหมายช่าง</h6>
                        <input type="hidden" name="id" value="<?= $r['request_id'] ?>">
                        <select name="assigned_to" class="form-select mb-2" required>
                            <option value="">-- เลือกช่าง --</option>
                            <?php
                                $techs = $pdo->query("SELECT user_id, full_name FROM users WHERE role IN ('technician', 'admin')")->fetchAll();
                                foreach($techs as $tech) {
                                    $selected = ($r['assigned_to'] == $tech['user_id']) ? 'selected' : '';
                                    echo "<option value='{$tech['user_id']}' $selected>{$tech['full_name']}</option>";
                                }
                            ?>
                        </select>
                        <button type="submit" class="btn btn-outline-primary btn-sm w-100">บันทึกการมอบหมาย</button>
                    </form>
                </div>

                <div class="col-md-6">
                    <form method="post" action="update_status.php" class="card p-3 bg-light">
                        <h6>อัปเดตสถานะงาน</h6>
                        <input type="hidden" name="id" value="<?= $r['request_id'] ?>">
                        <select name="status" class="form-select mb-2">
                            <?php 
                                $statuses = ['new', 'assigned', 'in_progress', 'done', 'cancelled'];
                                foreach($statuses as $s) {
                                    $sel = ($r['request_status'] == $s) ? 'selected' : '';
                                    echo "<option value='$s' $sel>$s</option>";
                                }
                            ?>
                        </select>
                        <textarea name="note" class="form-control mb-2" placeholder="หมายเหตุเพิ่มเติม (ถ้ามี)"></textarea>
                        <button type="submit" class="btn btn-success btn-sm w-100">บันทึกสถานะ</button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>