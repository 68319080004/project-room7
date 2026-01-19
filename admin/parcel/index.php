<?php
// admin/parcel/index.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../models/Parcel.php';

requireRole(['admin', 'staff', 'owner']);

$db = new Database();
$pdo = $db->getConnection();
$parcelModel = new Parcel($pdo);

$parcels = $parcelModel->getAll();
$stats = $parcelModel->getStats();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการพัสดุ - ระบบจัดการหอพัก</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-success: linear-gradient(135deg, #81FBB8 0%, #28C76F 100%);
            --gradient-info: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --gradient-warning: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            --gradient-danger: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --gradient-secondary: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        /* Page Header */
        .page-header {
            background: white;
            border-radius: 25px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 300px;
            height: 300px;
            background: var(--gradient-primary);
            opacity: 0.1;
            border-radius: 50%;
            transform: translate(50%, -50%);
        }

        .page-header h1 {
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 800;
            margin: 0;
        }

        /* Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            position: relative;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            opacity: 0.1;
        }

        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
        }

        .stat-card.total::before { background: var(--gradient-primary); }
        .stat-card.waiting::before { background: var(--gradient-warning); }
        .stat-card.picked::before { background: var(--gradient-success); }
        .stat-card.today::before { background: var(--gradient-info); }

        .stat-icon {
            font-size: 3rem;
            opacity: 0.2;
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
        }

        .stat-card.total .stat-icon { color: #667eea; }
        .stat-card.waiting .stat-icon { color: #fee140; }
        .stat-card.picked .stat-icon { color: #28C76F; }
        .stat-card.today .stat-icon { color: #4facfe; }

        .stat-content {
            position: relative;
            z-index: 2;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #6c757d;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 0.25rem;
        }

        .stat-card.total .stat-number { color: #667eea; }
        .stat-card.waiting .stat-number { color: #f59e0b; }
        .stat-card.picked .stat-number { color: #28C76F; }
        .stat-card.today .stat-number { color: #4facfe; }

        /* Filter Section */
        .filter-card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }

        .search-box {
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 0.9rem 1.2rem 0.9rem 3rem;
            border: 2px solid #e5e7eb;
            border-radius: 15px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .search-box input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        .search-box i {
            position: absolute;
            left: 1.2rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 1.1rem;
        }

        .filter-buttons {
            display: flex;
            gap: 0.8rem;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 0.7rem 1.5rem;
            border: 2px solid #e5e7eb;
            background: white;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .filter-btn:hover, .filter-btn.active {
            border-color: #667eea;
            background: var(--gradient-primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        /* Main Card */
        .main-card {
            background: white;
            border-radius: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            animation: fadeInUp 0.6s ease-out;
        }

        /* Table Styling */
        .table-custom {
            margin: 0;
        }

        .table-custom thead {
            background: var(--gradient-primary);
            color: white;
        }

        .table-custom thead th {
            border: none;
            padding: 1.25rem 1rem;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        .table-custom tbody tr {
            transition: all 0.3s ease;
            border-bottom: 1px solid #f0f0f0;
        }

        .table-custom tbody tr:hover {
            background: linear-gradient(90deg, rgba(102, 126, 234, 0.03) 0%, rgba(118, 75, 162, 0.03) 100%);
            transform: scale(1.01);
        }

        .table-custom tbody td {
            padding: 1.25rem 1rem;
            vertical-align: middle;
        }

        /* Room Badge */
        .room-badge {
            background: var(--gradient-primary);
            color: white;
            padding: 0.5rem 1.2rem;
            border-radius: 20px;
            font-weight: 700;
            display: inline-block;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        /* Status Badges */
        .status-badge {
            padding: 0.6rem 1.4rem;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .status-waiting {
            background: var(--gradient-warning);
            color: white;
        }

        .status-picked {
            background: var(--gradient-success);
            color: white;
        }

        /* Courier Box */
        .courier-box {
            background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
            padding: 0.8rem 1.2rem;
            border-radius: 12px;
            border-left: 4px solid #667eea;
        }

        .tracking-number {
            color: #667eea;
            font-weight: 700;
            font-family: 'Courier New', monospace;
            font-size: 0.95rem;
        }

        /* Buttons */
        .btn-custom {
            border-radius: 15px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            border: none;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-custom:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .btn-add {
            background: var(--gradient-success);
            color: white;
        }

        .btn-pickup {
            background: var(--gradient-success);
            color: white;
            border: none;
            padding: 0.7rem 1.5rem;
            border-radius: 50px;
            font-weight: 700;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            box-shadow: 0 5px 15px rgba(40, 199, 111, 0.3);
        }

        .btn-pickup:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(40, 199, 111, 0.4);
            color: white;
        }

        .btn-completed {
            background: linear-gradient(135deg, #e5e7eb 0%, #d1d5db 100%);
            color: #6b7280;
            border: none;
            padding: 0.7rem 1.5rem;
            border-radius: 50px;
            font-weight: 700;
            cursor: not-allowed;
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 5rem;
            opacity: 0.2;
            margin-bottom: 1.5rem;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Phone Link */
        .phone-link {
            color: #6b7280;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }

        .phone-link:hover {
            color: #667eea;
        }

        /* Date Badge */
        .date-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.3rem 0.8rem;
            background: #f3f4f6;
            border-radius: 20px;
            font-size: 0.85rem;
            color: #6c757d;
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .stats-container {
                grid-template-columns: repeat(2, 1fr);
            }

            .page-header {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <!-- Page Header -->
                <div class="page-header">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div>
                            <h1>
                                <i class="bi bi-box-seam-fill"></i> จัดการพัสดุ
                            </h1>
                            <p class="text-muted mb-0" style="font-size: 1rem; margin-top: 0.5rem;">
                                ระบบจัดการรับ-ส่งพัสดุสำหรับผู้เช่า
                            </p>
                        </div>
                        <a href="create.php" class="btn-custom btn-add">
                            <i class="bi bi-plus-circle-fill"></i>
                            รับพัสดุใหม่
                        </a>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="stats-container">
                    <div class="stat-card total">
                        <div class="stat-icon">
                            <i class="bi bi-boxes"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">
                                <i class="bi bi-archive"></i> พัสดุทั้งหมด
                            </div>
                            <div class="stat-number"><?= $stats['total'] ?? 0 ?></div>
                            <small class="text-muted">ชิ้น</small>
                        </div>
                    </div>

                    <div class="stat-card waiting">
                        <div class="stat-icon">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">
                                <i class="bi bi-hourglass-split"></i> รอรับ
                            </div>
                            <div class="stat-number"><?= $stats['waiting'] ?? 0 ?></div>
                            <small class="text-muted">ชิ้น</small>
                        </div>
                    </div>

                    <div class="stat-card picked">
                        <div class="stat-icon">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">
                                <i class="bi bi-patch-check"></i> รับแล้ว
                            </div>
                            <div class="stat-number"><?= $stats['picked_up'] ?? 0 ?></div>
                            <small class="text-muted">ชิ้น</small>
                        </div>
                    </div>

                    <div class="stat-card today">
                        <div class="stat-icon">
                            <i class="bi bi-calendar-day"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">
                                <i class="bi bi-calendar-event"></i> วันนี้
                            </div>
                            <div class="stat-number"><?= $stats['today'] ?? 0 ?></div>
                            <small class="text-muted">ชิ้น</small>
                        </div>
                    </div>
                </div>

                <!-- Filter Card -->
                <div class="filter-card">
                    <div class="row g-3 align-items-center">
                        <div class="col-md-6">
                            <div class="search-box">
                                <i class="bi bi-search"></i>
                                <input type="text" id="searchInput" class="form-control" 
                                       placeholder="ค้นหาพัสดุ ห้อง ผู้เช่า หรือเลขพัสดุ...">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="filter-buttons">
                                <button class="filter-btn active" data-filter="all">
                                    <i class="bi bi-list-ul"></i> ทั้งหมด
                                </button>
                                <button class="filter-btn" data-filter="waiting">
                                    <i class="bi bi-hourglass-split"></i> รอรับ
                                </button>
                                <button class="filter-btn" data-filter="picked_up">
                                    <i class="bi bi-check-circle"></i> รับแล้ว
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Table Card -->
                <div class="main-card">
                    <div class="card-body p-0">
                        <?php if (count($parcels) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-custom">
                                <thead>
                                    <tr>
                                        <th><i class="bi bi-calendar-event"></i> วันที่รับ</th>
                                        <th><i class="bi bi-door-open-fill"></i> ห้อง</th>
                                        <th><i class="bi bi-person-fill"></i> ผู้เช่า</th>
                                        <th><i class="bi bi-truck"></i> ขนส่ง / เลขพัสดุ</th>
                                        <th><i class="bi bi-send-fill"></i> ผู้ส่ง</th>
                                        <th><i class="bi bi-flag-fill"></i> สถานะ</th>
                                        <th class="text-center"><i class="bi bi-gear-fill"></i> จัดการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach($parcels as $p): ?>
                                    <tr data-status="<?= $p['parcel_status'] ?>" class="parcel-row">
                                        <td>
                                            <div>
                                                <strong style="font-size: 1.05rem; color: #495057;">
                                                    <?= date('d/m/Y', strtotime($p['received_at'])) ?>
                                                </strong><br>
                                                <span class="date-badge">
                                                    <i class="bi bi-clock"></i>
                                                    <?= date('H:i', strtotime($p['received_at'])) ?> น.
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="room-badge">
                                                <i class="bi bi-door-closed"></i> <?= htmlspecialchars($p['room_number']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div>
                                                <strong style="font-size: 1.05rem;"><?= htmlspecialchars($p['tenant_name']) ?></strong><br>
                                                <a href="tel:<?= $p['tenant_phone'] ?>" class="phone-link">
                                                    <i class="bi bi-telephone-fill"></i>
                                                    <?= $p['tenant_phone'] ?>
                                                </a>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="courier-box">
                                                <div style="margin-bottom: 0.3rem;">
                                                    <strong><?= htmlspecialchars($p['courier_company']) ?></strong>
                                                </div>
                                                <div class="tracking-number">
                                                    <i class="bi bi-upc-scan"></i> <?= htmlspecialchars($p['tracking_number']) ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($p['sender_name']) ?></strong>
                                        </td>
                                        <td>
                                            <?php if($p['parcel_status'] == 'waiting'): ?>
                                                <span class="status-badge status-waiting">
                                                    <i class="bi bi-hourglass-split"></i>
                                                    รอรับ
                                                </span>
                                            <?php else: ?>
                                                <div>
                                                    <span class="status-badge status-picked">
                                                        <i class="bi bi-check-circle-fill"></i>
                                                        รับแล้ว
                                                    </span>
                                                    <div class="small text-muted mt-2" style="font-size: 0.85rem;">
                                                        <i class="bi bi-calendar-check"></i> 
                                                        <?= date('d/m/Y H:i', strtotime($p['picked_up_at'])) ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if($p['parcel_status'] == 'waiting'): ?>
                                                <form action="pickup.php" method="post" 
                                                      onsubmit="return confirm('✅ ยืนยันว่าผู้เช่ามารับพัสดุแล้ว?');">
                                                    <input type="hidden" name="id" value="<?= $p['parcel_id'] ?>">
                                                    <button type="submit" class="btn-pickup">
                                                        <i class="bi bi-hand-thumbs-up-fill"></i>
                                                        ส่งมอบของ
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <button class="btn-completed" disabled>
                                                    <i class="bi bi-check-all"></i>
                                                    เรียบร้อย
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="empty-state">
                            <i class="bi bi-inbox"></i>
                            <h4>ไม่มีรายการพัสดุ</h4>
                            <p class="text-muted">ยังไม่มีพัสดุในระบบ เริ่มต้นด้วยการรับพัสดุใหม่</p>
                            <a href="create.php" class="btn-custom btn-add mt-3">
                                <i class="bi bi-plus-circle-fill"></i>
                                รับพัสดุใหม่
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Search functionality
        document.getElementById('searchInput')?.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('.parcel-row');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });

        // Filter functionality
        document.querySelectorAll('.filter-btn').forEach(button => {
            button.addEventListener('click', function() {
                const filter = this.getAttribute('data-filter');
                const rows = document.querySelectorAll('.parcel-row');
                
                // Update button states
                document.querySelectorAll('.filter-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                this.classList.add('active');
                
                // Filter rows
                rows.forEach(row => {
                    if (filter === 'all') {
                        row.style.display = '';
                    } else {
                        row.style.display = row.dataset.status === filter ? '' : 'none';
                    }
                });
            });
        });

        // Animation on load
        document.addEventListener('DOMContentLoaded', function() {
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.animation = `fadeInUp 0.6s ease-out ${index * 0.1}s both`;
            });
        });
    </script>
</body>
</html>