<?php
// ============================================
// ไฟล์: admin/includes/navbar.php
// คำอธิบาย: Navbar สำหรับ Admin
// ============================================

// กำหนด base path สำหรับ admin
$base_path = '/cns68-1/Roomrentalsystem/admin/';
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?= $base_path ?>dashboard.php">
            <i class="bi bi-building"></i> ระบบจัดการหอพัก
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item me-3">
                    <span class="text-white">
                        <i class="bi bi-person-circle"></i> 
                        <?= htmlspecialchars($_SESSION['full_name']) ?> 
                        <span class="badge bg-primary"><?= strtoupper($_SESSION['role']) ?></span>
                    </span>
                </li>
                <li class="nav-item">
                    <a class="nav-link btn btn-outline-light btn-sm" href="<?= $base_path ?>../logout.php">
                        <i class="bi bi-box-arrow-right"></i> ออกจากระบบ
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
