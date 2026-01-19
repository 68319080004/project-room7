<?php
// ============================================
// ไฟล์: admin/includes/sidebar.php
// คำอธิบาย: Sidebar Menu สำหรับ Admin (อัปเดตเพิ่มเมนู Tax)
// ============================================

// กำหนด base path สำหรับ admin
$base_path = '/cns68-1/Roomrentalsystem/admin/';

// ฟังก์ชันตรวจสอบหน้า active (ปรับปรุงให้รองรับ sub-folder ได้ดีขึ้น)
function isActive($page) {
    // ใช้ strpos เพื่อเช็คว่า URL ปัจจุบันมี path ที่ส่งมาหรือไม่
    // วิธีนี้จะช่วยให้ path อย่าง 'tax/index.php' ทำงานได้ถูกต้อง
    return strpos($_SERVER['PHP_SELF'], $page) !== false ? 'active' : '';
}
?>
<nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= isActive('dashboard.php') ?>" href="<?= $base_path ?>dashboard.php">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= isActive('rooms.php') ?>" href="<?= $base_path ?>rooms.php">
                    <i class="bi bi-door-open"></i> จัดการห้องเช่า
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= isActive('buildings.php') ?>" href="<?= $base_path ?>buildings.php">
                    <i class="bi bi-buildings"></i> จัดการอาคาร
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= isActive('tenants.php') ?>" href="<?= $base_path ?>tenants.php">
                    <i class="bi bi-people"></i> จัดการผู้เช่า
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= isActive('meters.php') ?>" href="<?= $base_path ?>meters.php">
                    <i class="bi bi-speedometer"></i> บันทึกมิเตอร์
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= isActive('maintenance/index.php') ?>" href="<?= $base_path ?>maintenance/index.php">
                    <i class="bi bi-tools"></i> แจ้งซ่อม / ซ่อมบำรุง
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= isActive('parcel/index.php') ?>" href="<?= $base_path ?>parcel/index.php">
                    <i class="bi bi-box-seam"></i> จัดการพัสดุ
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?= isActive('tax/index.php') ?>" href="<?= $base_path ?>tax/index.php">
                    <i class="bi bi-currency-exchange"></i> จัดการภาษี (Tax)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= isActive('invoices.php') ?>" href="<?= $base_path ?>invoices.php">
                    <i class="bi bi-receipt"></i> ใบเสร็จ/บิล
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= isActive('payments.php') ?>" href="<?= $base_path ?>payments.php">
                    <i class="bi bi-credit-card"></i> การชำระเงิน
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= isActive('reports.php') ?>" href="<?= $base_path ?>reports.php">
                    <i class="bi bi-bar-chart"></i> รายงาน
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= isActive('reports_advanced.php') ?>" href="<?= $base_path ?>reports_advanced.php">
                    <i class="bi bi-bar-chart-line-fill"></i> รายงานขั้นสูง
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= isActive('invoice_create_manual.php') ?>" href="<?= $base_path ?>invoice_create_manual.php">
                    <i class="bi bi-pencil-square"></i> สร้างบิล Manual
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= isActive('contracts.php') ?>" href="<?= $base_path ?>contracts.php">
                    <i class="bi bi-file-earmark-text"></i> จัดการสัญญาเช่า
                </a>
            </li>

            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'owner'): ?>
                <hr>
                <li class="nav-item">
                    <a class="nav-link <?= isActive('users.php') ?>" href="<?= $base_path ?>users.php">
                        <i class="bi bi-person-gear"></i> จัดการผู้ใช้
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= isActive('settings.php') ?>" href="<?= $base_path ?>settings.php">
                        <i class="bi bi-gear"></i> ตั้งค่าระบบ
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
</nav>