<?php
// ============================================
// ไฟล์: register.php (Fullscreen Modern Edition)
// คำอธิบาย: หน้าสมัครสมาชิก สวยงามเต็มจอพร้อมอนิเมชั่น
// ============================================

require_once 'config/database.php';
require_once 'config/session.php';
require_once 'models/User.php';
require_once 'models/Room.php';
require_once 'models/Tenant.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$room = new Room($db);
$tenant = new Tenant($db);

// ดึงห้องว่าง พร้อมข้อมูลอาคาร
$query = "SELECT r.*, 
                 b.building_id,
                 b.building_name, 
                 b.building_type,
                 b.water_rate_per_unit,
                 b.electric_rate_per_unit,
                 b.garbage_fee
          FROM rooms r
          LEFT JOIN buildings b ON r.building_id = b.building_id
          WHERE r.room_status = 'available'
            AND (b.is_active = 1 OR b.building_id IS NULL)
          ORDER BY 
            CASE WHEN b.building_name IS NULL THEN 1 ELSE 0 END,
            b.building_name ASC, 
            r.room_number ASC";

$stmt = $db->prepare($query);
$stmt->execute();
$availableRooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $room_id = $_POST['room_id'];
    $id_card = trim($_POST['id_card']);
    $line_id = trim($_POST['line_id']);
    $facebook = trim($_POST['facebook']);
    $emergency_contact = trim($_POST['emergency_contact']);
    $emergency_phone = trim($_POST['emergency_phone']);
    $move_in_date = $_POST['move_in_date'];
    $deposit_amount = $_POST['deposit_amount'];

    // Validation
    if (empty($username) || empty($password) || empty($full_name) || empty($phone) || empty($room_id)) {
        $error = 'กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน';
    } elseif (strlen($password) < 6) {
        $error = 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร';
    } elseif ($password !== $confirm_password) {
        $error = 'รหัสผ่านไม่ตรงกัน';
    } else {
        $db->beginTransaction();
        
        try {
            $user_id = $user->create($username, $password, $full_name, $phone, 'member');
            
            if (!$user_id) {
                throw new Exception('ชื่อผู้ใช้นี้ถูกใช้งานแล้ว');
            }
            
            $tenantData = [
                'user_id' => $user_id,
                'room_id' => $room_id,
                'full_name' => $full_name,
                'phone' => $phone,
                'id_card' => $id_card,
                'line_id' => $line_id,
                'facebook' => $facebook,
                'emergency_contact' => $emergency_contact,
                'emergency_phone' => $emergency_phone,
                'move_in_date' => $move_in_date,
                'deposit_amount' => $deposit_amount,
                'discount_amount' => 0
            ];
            
            $tenant_id = $tenant->create($tenantData);
            
            if (!$tenant_id) {
                throw new Exception('ไม่สามารถสร้างข้อมูลผู้เช่าได้');
            }
            
            $roomData = $room->getById($room_id);
            $room->update($room_id, [
                'room_number' => $roomData['room_number'],
                'room_type' => $roomData['room_type'],
                'monthly_rent' => $roomData['monthly_rent'],
                'room_status' => 'occupied',
                'floor' => $roomData['floor'],
                'description' => $roomData['description'],
                'building_id' => $roomData['building_id']
            ]);
            
            $db->commit();
            
            $success = 'สมัครสมาชิกสำเร็จ! กำลังนำคุณไปยังหน้า Login...';
            header("refresh:3;url=login.php");
            
        } catch (Exception $e) {
            $db->rollBack();
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก - ระบบจัดการหอพัก</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');

        :root {
            --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-success: linear-gradient(135deg, #81FBB8 0%, #28C76F 100%);
            --gradient-info: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --gradient-warning: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            overflow-x: hidden;
            position: relative;
        }

        /* ==================== ANIMATED BACKGROUND ==================== */
        .animated-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            overflow: hidden;
        }

        .floating-shapes {
            position: absolute;
            width: 100%;
            height: 100%;
        }

        .shape {
            position: absolute;
            opacity: 0.15;
            animation: float 20s infinite ease-in-out;
        }

        .shape:nth-child(1) {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #fff 0%, transparent 100%);
            border-radius: 50%;
            top: 10%;
            left: 10%;
            animation-delay: 0s;
            animation-duration: 25s;
        }

        .shape:nth-child(2) {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #fff 0%, transparent 100%);
            border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
            top: 50%;
            right: 10%;
            animation-delay: 5s;
            animation-duration: 30s;
        }

        .shape:nth-child(3) {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #fff 0%, transparent 100%);
            border-radius: 50%;
            bottom: 15%;
            left: 15%;
            animation-delay: 10s;
            animation-duration: 35s;
        }

        .shape:nth-child(4) {
            width: 150px;
            height: 150px;
            background: linear-gradient(135deg, #fff 0%, transparent 100%);
            border-radius: 63% 37% 54% 46% / 55% 48% 52% 45%;
            top: 30%;
            left: 50%;
            animation-delay: 2s;
            animation-duration: 28s;
        }

        .shape:nth-child(5) {
            width: 90px;
            height: 90px;
            background: linear-gradient(135deg, #fff 0%, transparent 100%);
            border-radius: 50%;
            bottom: 30%;
            right: 20%;
            animation-delay: 7s;
            animation-duration: 32s;
        }

        @keyframes float {
            0%, 100% {
                transform: translate(0, 0) rotate(0deg) scale(1);
            }
            25% {
                transform: translate(30px, -30px) rotate(90deg) scale(1.1);
            }
            50% {
                transform: translate(-20px, 20px) rotate(180deg) scale(0.9);
            }
            75% {
                transform: translate(20px, 30px) rotate(270deg) scale(1.05);
            }
        }

        /* Particles */
        .particles {
            position: absolute;
            width: 100%;
            height: 100%;
        }

        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: white;
            border-radius: 50%;
            opacity: 0.3;
            animation: particle-float 15s infinite;
        }

        @keyframes particle-float {
            0% {
                transform: translateY(100vh) translateX(0);
                opacity: 0;
            }
            10% {
                opacity: 0.3;
            }
            90% {
                opacity: 0.3;
            }
            100% {
                transform: translateY(-100px) translateX(100px);
                opacity: 0;
            }
        }

        /* ==================== MAIN CONTAINER ==================== */
        .register-container {
            position: relative;
            z-index: 1;
            max-width: 100%;
            min-height: 100vh;
            padding: 20px;
            display: flex;
            flex-direction: column;
        }

        /* Progress Steps */
        .progress-steps {
            background: white;
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            animation: slideDown 0.8s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .steps-wrapper {
            display: flex;
            justify-content: space-between;
            position: relative;
        }

        .steps-wrapper::before {
            content: '';
            position: absolute;
            top: 30px;
            left: 0;
            right: 0;
            height: 3px;
            background: #e5e7eb;
            z-index: 0;
        }

        .step-item {
            position: relative;
            z-index: 1;
            text-align: center;
            flex: 1;
        }

        .step-circle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #e5e7eb;
            color: #9ca3af;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0 auto 10px;
            transition: all 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        .step-item.active .step-circle {
            background: var(--gradient-primary);
            color: white;
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
            transform: scale(1.1);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% {
                box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
            }
            50% {
                box-shadow: 0 5px 30px rgba(102, 126, 234, 0.6);
            }
        }

        .step-label {
            font-size: 0.9rem;
            color: #6b7280;
            font-weight: 600;
        }

        .step-item.active .step-label {
            color: #667eea;
        }

        /* Main Card */
        .register-card {
            background: white;
            border-radius: 25px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            animation: fadeInScale 1s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            flex: 1;
        }

        @keyframes fadeInScale {
            from {
                opacity: 0;
                transform: scale(0.9) translateY(30px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .card-header-custom {
            padding: 30px;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .card-header-custom::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .card-header-custom h5 {
            margin: 0;
            font-weight: 700;
            font-size: 1.3rem;
            position: relative;
            z-index: 1;
        }

        .header-account { background: var(--gradient-primary); }
        .header-personal { background: var(--gradient-info); }
        .header-emergency { background: var(--gradient-warning); }
        .header-room { background: var(--gradient-success); }

        .card-body {
            padding: 30px;
        }

        /* Form Elements */
        .form-label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .required-mark {
            color: #ef4444;
            font-weight: 700;
        }

        .form-control, .form-select {
            padding: 12px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
            transform: translateY(-2px);
        }

        /* Room Cards */
        .room-card {
            cursor: pointer;
            transition: all 0.3s ease;
            height: 100%;
        }

        .room-card input[type="radio"] {
            display: none;
        }

        .room-card label {
            height: 100%;
            margin: 0;
            border: 3px solid #e5e7eb;
            border-radius: 20px;
            transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            cursor: pointer;
        }

        .room-card:hover label {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
            border-color: #667eea;
        }

        .room-card input[type="radio"]:checked + label {
            background: var(--gradient-primary);
            border-color: #667eea;
            box-shadow: 0 20px 50px rgba(102, 126, 234, 0.5);
            animation: roomSelect 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        @keyframes roomSelect {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
            100% {
                transform: scale(1);
            }
        }

        .room-card input[type="radio"]:checked + label * {
            color: white !important;
        }

        .room-card input[type="radio"]:checked + label .badge {
            background: rgba(255, 255, 255, 0.3) !important;
            color: white !important;
        }

        .room-icon {
            font-size: 3.5rem;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 15px;
            animation: iconBounce 2s infinite;
        }

        @keyframes iconBounce {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
        }

        .room-card input[type="radio"]:checked + label .room-icon {
            -webkit-text-fill-color: white;
            animation: iconSpin 0.6s ease-out;
        }

        @keyframes iconSpin {
            from {
                transform: rotate(0deg) scale(1);
            }
            to {
                transform: rotate(360deg) scale(1);
            }
        }

        /* Building Section */
        .building-section {
            margin-bottom: 40px;
            opacity: 0;
            animation: fadeInUp 0.8s ease-out forwards;
        }

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

        .building-section:nth-child(2) { animation-delay: 0.2s; }
        .building-section:nth-child(3) { animation-delay: 0.4s; }
        .building-section:nth-child(4) { animation-delay: 0.6s; }

        .building-header {
            background: white;
            border: 3px solid #667eea;
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.2);
            transition: all 0.3s ease;
        }

        .building-header:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .building-title {
            font-size: 1.5rem;
            font-weight: 700;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 15px;
        }

        .building-info {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 15px;
            background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .info-item:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        /* Buttons */
        .btn-submit {
            padding: 16px 40px;
            background: var(--gradient-primary);
            border: none;
            border-radius: 50px;
            color: white;
            font-weight: 700;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
            width: 100%;
            position: relative;
            overflow: hidden;
        }

        .btn-submit::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .btn-submit:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.5);
        }

        .btn-submit:active {
            transform: translateY(-1px);
        }

        .btn-back {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-back:hover {
            color: #764ba2;
            transform: translateX(-5px);
        }

        /* Alerts */
        .alert {
            border-radius: 15px;
            padding: 20px;
            border: none;
            margin-bottom: 25px;
            animation: alertSlide 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        @keyframes alertSlide {
            from {
                opacity: 0;
                transform: translateX(-50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .alert-danger {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
        }

        .alert-success {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
        }

        .alert-info {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1e40af;
        }

        /* Badge */
        .badge-custom {
            padding: 6px 12px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.3s ease;
        }

        .badge-custom:hover {
            transform: scale(1.1);
        }

        /* Loading Spinner */
        .spinner-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .spinner-overlay.active {
            display: flex;
        }

        .spinner {
            width: 60px;
            height: 60px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Success Notification */
        .success-notification {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0);
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            z-index: 10000;
            text-align: center;
            transition: all 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        .success-notification.show {
            transform: translate(-50%, -50%) scale(1);
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background: var(--gradient-success);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            animation: successPop 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        @keyframes successPop {
            0% {
                transform: scale(0);
                opacity: 0;
            }
            50% {
                transform: scale(1.2);
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .progress-steps {
                padding: 20px;
            }

            .step-circle {
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
            }

            .step-label {
                font-size: 0.75rem;
            }

            .card-body {
                padding: 20px;
            }

            .building-info {
                gap: 10px;
            }

            .room-icon {
                font-size: 2.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="animated-bg">
        <div class="floating-shapes">
            <div class="shape"></div>
            <div class="shape"></div>
            <div class="shape"></div>
            <div class="shape"></div>
            <div class="shape"></div>
        </div>
        <div class="particles" id="particles"></div>
    </div>

    <!-- Loading Spinner -->
    <div class="spinner-overlay" id="loadingSpinner">
        <div class="spinner"></div>
    </div>

    <!-- Success Notification -->
    <div class="success-notification" id="successNotification">
        <div class="success-icon">
            <i class="bi bi-check-lg" style="font-size: 3rem; color: white;"></i>
        </div>
        <h3 style="color: #28C76F; font-weight: 700;">สำเร็จ!</h3>
        <p style="color: #6b7280; margin: 10px 0 0 0;">กำลังนำคุณไปยังหน้า Login...</p>
    </div>

    <div class="register-container">
        <!-- Progress Steps -->
        <div class="progress-steps">
            <div class="steps-wrapper">
                <div class="step-item active">
                    <div class="step-circle">1</div>
                    <div class="step-label">บัญชี</div>
                </div>
                <div class="step-item active">
                    <div class="step-circle">2</div>
                    <div class="step-label">ข้อมูลส่วนตัว</div>
                </div>
                <div class="step-item active">
                    <div class="step-circle">3</div>
                    <div class="step-label">ติดต่อฉุกเฉิน</div>
                </div>
                <div class="step-item active">
                    <div class="step-circle">4</div>
                    <div class="step-label">เลือกห้อง</div>
                </div>
            </div>
        </div>

        <!-- Main Form -->
        <div class="register-card">
            <div class="text-center p-5">
                <div class="mb-4">
                    <i class="bi bi-person-plus-fill" style="font-size: 4rem; background: var(--gradient-primary); -webkit-background-clip: text; -webkit-text-fill-color: transparent; animation: iconBounce 2s infinite;"></i>
                </div>
                <h2 style="background: var(--gradient-primary); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-weight: 800;">สมัครสมาชิก</h2>
                <p class="text-muted">กรอกข้อมูลเพื่อสร้างบัญชีและเลือกห้องพักของคุณ</p>
            </div>

            <?php if ($error): ?>
                <div class="px-5">
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-circle-fill me-2"></i><?php echo $error; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="px-5">
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle-fill me-2"></i><?php echo $success; ?>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="registerForm">
                <!-- Step 1: Account Info -->
                <div class="mb-4">
                    <div class="card-header-custom header-account">
                        <h5><i class="bi bi-shield-lock-fill me-2"></i>ข้อมูลบัญชีผู้ใช้</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">
                                    <i class="bi bi-person-circle"></i> ชื่อผู้ใช้
                                    <span class="required-mark">*</span>
                                </label>
                                <input type="text" class="form-control" name="username" 
                                       placeholder="username" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">
                                    <i class="bi bi-key-fill"></i> รหัสผ่าน
                                    <span class="required-mark">*</span>
                                </label>
                                <input type="password" class="form-control" name="password" 
                                       placeholder="อย่างน้อย 6 ตัวอักษร" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">
                                    <i class="bi bi-check2-circle"></i> ยืนยันรหัสผ่าน
                                    <span class="required-mark">*</span>
                                </label>
                                <input type="password" class="form-control" name="confirm_password" 
                                       placeholder="กรอกรหัสผ่านอีกครั้ง" required>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Personal Info -->
                <div class="mb-4">
                    <div class="card-header-custom header-personal">
                        <h5><i class="bi bi-person-badge-fill me-2"></i>ข้อมูลส่วนตัว</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="bi bi-person-fill"></i> ชื่อ-นามสกุล
                                    <span class="required-mark">*</span>
                                </label>
                                <input type="text" class="form-control" name="full_name" 
                                       placeholder="ชื่อ-นามสกุลจริง" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="bi bi-telephone-fill"></i> เบอร์โทรศัพท์
                                    <span class="required-mark">*</span>
                                </label>
                                <input type="tel" class="form-control" name="phone" 
                                       placeholder="08X-XXX-XXXX" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="bi bi-card-text"></i> เลขบัตรประชาชน
                                </label>
                                <input type="text" class="form-control" name="id_card" 
                                       placeholder="X-XXXX-XXXXX-XX-X">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="bi bi-calendar-event"></i> วันที่เข้าพัก
                                    <span class="required-mark">*</span>
                                </label>
                                <input type="date" class="form-control" name="move_in_date" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">
                                    <i class="bi bi-chat-dots-fill"></i> LINE ID
                                </label>
                                <input type="text" class="form-control" name="line_id" 
                                       placeholder="@yourline">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">
                                    <i class="bi bi-facebook"></i> Facebook
                                </label>
                                <input type="text" class="form-control" name="facebook" 
                                       placeholder="ชื่อ Facebook">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">
                                    <i class="bi bi-cash-coin"></i> เงินประกัน (บาท)
                                </label>
                                <input type="number" class="form-control" name="deposit_amount" 
                                       value="0" placeholder="0">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Emergency Contact -->
                <div class="mb-4">
                    <div class="card-header-custom header-emergency">
                        <h5><i class="bi bi-exclamation-triangle-fill me-2"></i>ข้อมูลติดต่อฉุกเฉิน</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="bi bi-person-fill-exclamation"></i> ชื่อผู้ติดต่อฉุกเฉิน
                                </label>
                                <input type="text" class="form-control" name="emergency_contact" 
                                       placeholder="ชื่อผู้ติดต่อฉุกเฉิน">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="bi bi-telephone-forward-fill"></i> เบอร์โทรฉุกเฉิน
                                </label>
                                <input type="tel" class="form-control" name="emergency_phone" 
                                       placeholder="08X-XXX-XXXX">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 4: Room Selection -->
                <div class="mb-4">
                    <div class="card-header-custom header-room">
                        <h5><i class="bi bi-door-open-fill me-2"></i>เลือกห้องพัก <span class="required-mark">*</span></h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($availableRooms) > 0): ?>
                            <div class="alert alert-info mb-4">
                                <i class="bi bi-info-circle-fill me-2"></i>
                                มีห้องว่าง <strong><?php echo count($availableRooms); ?></strong> ห้อง - คลิกเพื่อเลือกห้องที่คุณต้องการ
                            </div>

                            <?php 
                            // จัดกลุ่มห้องตามอาคาร
                            $roomsByBuilding = [];
                            foreach ($availableRooms as $r) {
                                $buildingKey = $r['building_id'] ?? 'no_building';
                                $roomsByBuilding[$buildingKey][] = $r;
                            }
                            
                            foreach ($roomsByBuilding as $buildingKey => $rooms): 
                                $firstRoom = $rooms[0]; 
                                $hasBuilding = ($buildingKey !== 'no_building' && $firstRoom['building_id']);
                            ?>
                                <div class="building-section">
                                    <div class="building-header">
                                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                                            <div>
                                                <h3 class="building-title">
                                                    <i class="bi bi-building-fill me-2"></i>
                                                    <?php echo $hasBuilding ? $firstRoom['building_name'] : 'ห้องพักทั่วไป'; ?>
                                                </h3>
                                                <div class="d-flex gap-2 flex-wrap">
                                                    <?php if ($hasBuilding && $firstRoom['building_type']): ?>
                                                        <span class="badge badge-custom bg-primary">
                                                            <?php echo $firstRoom['building_type']; ?>
                                                        </span>
                                                    <?php endif; ?>
                                                    <span class="badge badge-custom bg-success">
                                                        <i class="bi bi-check-circle"></i> <?php echo count($rooms); ?> ห้องว่าง
                                                    </span>
                                                </div>
                                            </div>
                                            <?php if ($hasBuilding && $firstRoom['water_rate_per_unit']): ?>
                                                <div class="building-info">
                                                    <div class="info-item">
                                                        <i class="bi bi-droplet-fill text-primary"></i>
                                                        ฿<?php echo number_format($firstRoom['water_rate_per_unit'], 2); ?>/หน่วย
                                                    </div>
                                                    <div class="info-item">
                                                        <i class="bi bi-lightning-fill text-warning"></i>
                                                        ฿<?php echo number_format($firstRoom['electric_rate_per_unit'], 2); ?>/หน่วย
                                                    </div>
                                                    <div class="info-item">
                                                        <i class="bi bi-trash3-fill text-secondary"></i>
                                                        ฿<?php echo number_format($firstRoom['garbage_fee'], 2); ?>/เดือน
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="row g-4">
                                        <?php foreach ($rooms as $r): ?>
                                            <div class="col-md-4">
                                                <div class="room-card">
                                                    <input type="radio" name="room_id" 
                                                           id="room_<?php echo $r['room_id']; ?>" 
                                                           value="<?php echo $r['room_id']; ?>" required>
                                                    <label for="room_<?php echo $r['room_id']; ?>" class="card">
                                                        <div class="card-body text-center p-4">
                                                            <i class="bi bi-house-door-fill room-icon"></i>
                                                            <h4 class="mb-3" style="color: #667eea; font-weight: 800;">
                                                                ห้อง <?php echo $r['room_number']; ?>
                                                            </h4>
                                                            <div class="mb-3">
                                                                <span class="badge badge-custom" style="background: var(--gradient-info); color: white;">
                                                                    <i class="bi bi-snow2"></i> <?php echo $r['room_type']; ?>
                                                                </span>
                                                                <span class="badge badge-custom bg-secondary">
                                                                    <i class="bi bi-building"></i> ชั้น <?php echo $r['floor']; ?>
                                                                </span>
                                                            </div>
                                                            <h5 class="mb-0" style="color: #28C76F; font-weight: 700;">
                                                                <i class="bi bi-cash-stack"></i>
                                                                ฿<?php echo number_format($r['monthly_rent'], 0); ?>
                                                                <small style="font-size: 0.8rem; color: #6b7280;">/เดือน</small>
                                                            </h5>
                                                            <?php if ($r['description']): ?>
                                                                <hr class="my-3">
                                                                <p class="mb-0" style="font-size: 0.85rem; color: #6b7280;">
                                                                    <?php echo htmlspecialchars($r['description']); ?>
                                                                </p>
                                                            <?php endif; ?>
                                                        </div>
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                        <?php else: ?>
                            <div class="alert alert-danger">
                                <h5 class="mb-3">
                                    <i class="bi bi-x-circle-fill me-2"></i>ไม่มีห้องว่าง
                                </h5>
                                <p class="mb-0">
                                    ขออภัย ขณะนี้ไม่มีห้องว่างในระบบ กรุณาติดต่อเจ้าหน้าที่เพื่อสอบถามข้อมูลเพิ่มเติม
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (count($availableRooms) > 0): ?>
                    <div class="p-5 pt-0">
                        <button type="submit" class="btn-submit">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            สมัครสมาชิกและจองห้อง
                        </button>
                        <div class="text-center mt-4">
                            <a href="login.php" class="btn-back">
                                <i class="bi bi-arrow-left-circle-fill"></i>
                                กลับไปหน้า Login
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="p-5 pt-0 text-center">
                        <a href="login.php" class="btn-back">
                            <i class="bi bi-arrow-left-circle-fill"></i>
                            กลับไปหน้า Login
                        </a>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ==================== CREATE PARTICLES ====================
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 30;

            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 15 + 's';
                particle.style.animationDuration = (Math.random() * 10 + 10) + 's';
                particlesContainer.appendChild(particle);
            }
        }

        createParticles();

        // ==================== FORM VALIDATION ====================
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.querySelector('[name="password"]').value;
            const confirmPassword = document.querySelector('[name="confirm_password"]').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                showNotification('error', 'รหัสผ่านไม่ตรงกัน กรุณาตรวจสอบอีกครั้ง');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                showNotification('error', 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร');
                return false;
            }
            
            const roomSelected = document.querySelector('input[name="room_id"]:checked');
            if (!roomSelected) {
                e.preventDefault();
                showNotification('error', 'กรุณาเลือกห้องพัก');
                return false;
            }

            // Show loading
            showLoading();
        });

        // ==================== NOTIFICATIONS ====================
        function showNotification(type, message) {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 20px 30px;
                background: ${type === 'error' ? 'linear-gradient(135deg, #fee2e2 0%, #fecaca 100%)' : 'linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%)'};
                color: ${type === 'error' ? '#991b1b' : '#065f46'};
                border-radius: 15px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
                z-index: 10001;
                font-weight: 600;
                animation: slideInRight 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            `;
            notification.innerHTML = `
                <i class="bi bi-${type === 'error' ? 'exclamation-circle' : 'check-circle'}-fill me-2"></i>
                ${message}
            `;
            document.body.appendChild(notification);

            setTimeout(() => {
                notification.style.animation = 'slideOutRight 0.5s ease-in';
                setTimeout(() => notification.remove(), 500);
            }, 3000);
        }

        // ==================== LOADING SPINNER ====================
        function showLoading() {
            document.getElementById('loadingSpinner').classList.add('active');
        }

        function hideLoading() {
            document.getElementById('loadingSpinner').classList.remove('active');
        }

        // ==================== PASSWORD STRENGTH ====================
        const passwordInput = document.querySelector('[name="password"]');
        const confirmPasswordInput = document.querySelector('[name="confirm_password"]');

        passwordInput.addEventListener('input', function() {
            const strength = this.value.length;
            if (strength < 6) {
                this.style.borderColor = '#ef4444';
                this.style.boxShadow = '0 0 0 4px rgba(239, 68, 68, 0.1)';
            } else if (strength < 10) {
                this.style.borderColor = '#f59e0b';
                this.style.boxShadow = '0 0 0 4px rgba(245, 158, 11, 0.1)';
            } else {
                this.style.borderColor = '#10b981';
                this.style.boxShadow = '0 0 0 4px rgba(16, 185, 129, 0.1)';
            }
        });

        confirmPasswordInput.addEventListener('input', function() {
            const password = passwordInput.value;
            if (this.value === password && password.length >= 6) {
                this.style.borderColor = '#10b981';
                this.style.boxShadow = '0 0 0 4px rgba(16, 185, 129, 0.1)';
            } else {
                this.style.borderColor = '#ef4444';
                this.style.boxShadow = '0 0 0 4px rgba(239, 68, 68, 0.1)';
            }
        });

        // ==================== ROOM SELECTION ====================
        document.querySelectorAll('input[name="room_id"]').forEach(radio => {
            radio.addEventListener('change', function() {
                // Scroll into view
                this.parentElement.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'center' 
                });

                // Confetti effect
                createConfetti(this.parentElement);

                // Success notification
                showRoomSelectedNotification();
            });
        });

        function showRoomSelectedNotification() {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%) scale(0);
                background: linear-gradient(135deg, #81FBB8 0%, #28C76F 100%);
                color: white;
                padding: 30px 50px;
                border-radius: 25px;
                font-weight: 700;
                font-size: 1.3rem;
                box-shadow: 0 15px 50px rgba(40, 199, 111, 0.5);
                z-index: 10000;
                animation: popIn 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55) forwards;
            `;
            notification.innerHTML = `
                <i class="bi bi-check-circle-fill me-2" style="font-size: 1.5rem;"></i>
                เลือกห้องนี้แล้ว!
            `;
            document.body.appendChild(notification);

            setTimeout(() => {
                notification.style.animation = 'popOut 0.4s ease-in forwards';
                setTimeout(() => notification.remove(), 400);
            }, 1500);
        }

        function createConfetti(element) {
            const rect = element.getBoundingClientRect();
            const centerX = rect.left + rect.width / 2;
            const centerY = rect.top + rect.height / 2;

            for (let i = 0; i < 30; i++) {
                const confetti = document.createElement('div');
                confetti.style.cssText = `
                    position: fixed;
                    width: 10px;
                    height: 10px;
                    background: ${['#667eea', '#764ba2', '#81FBB8', '#28C76F', '#4facfe'][Math.floor(Math.random() * 5)]};
                    left: ${centerX}px;
                    top: ${centerY}px;
                    border-radius: 50%;
                    pointer-events: none;
                    z-index: 9999;
                `;
                document.body.appendChild(confetti);

                const angle = (Math.PI * 2 * i) / 30;
                const velocity = 100 + Math.random() * 100;
                const vx = Math.cos(angle) * velocity;
                const vy = Math.sin(angle) * velocity;

                let x = centerX;
                let y = centerY;
                let opacity = 1;
                let vy_current = vy;

                const animate = () => {
                    x += vx * 0.02;
                    y += vy_current * 0.02;
                    vy_current += 5; // gravity
                    opacity -= 0.02;

                    confetti.style.left = x + 'px';
                    confetti.style.top = y + 'px';
                    confetti.style.opacity = opacity;

                    if (opacity > 0) {
                        requestAnimationFrame(animate);
                    } else {
                        confetti.remove();
                    }
                };

                animate();
            }
        }

        // ==================== PHONE FORMATTING ====================
        document.querySelector('[name="phone"]').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 10) value = value.slice(0, 10);
            e.target.value = value;
        });

        const emergencyPhone = document.querySelector('[name="emergency_phone"]');
        if (emergencyPhone) {
            emergencyPhone.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length > 10) value = value.slice(0, 10);
                e.target.value = value;
            });
        }

        // ==================== ID CARD FORMATTING ====================
        const idCardInput = document.querySelector('[name="id_card"]');
        if (idCardInput) {
            idCardInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length > 13) value = value.slice(0, 13);
                
                if (value.length > 0) {
                    let formatted = value[0];
                    if (value.length > 1) formatted += '-' + value.slice(1, 5);
                    if (value.length > 5) formatted += '-' + value.slice(5, 10);
                    if (value.length > 10) formatted += '-' + value.slice(10, 12);
                    if (value.length > 12) formatted += '-' + value.slice(12, 13);
                    e.target.value = formatted;
                }
            });
        }

        // ==================== SMOOTH SCROLL ====================
        window.scrollTo({ top: 0, behavior: 'smooth' });

        // ==================== INTERSECTION OBSERVER ====================
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.building-section').forEach(section => {
            observer.observe(section);
        });

        // ==================== INPUT ANIMATIONS ====================
        document.querySelectorAll('.form-control, .form-select').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
                this.parentElement.style.transition = 'transform 0.3s ease';
            });

            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });

        // ==================== ADD KEYFRAME ANIMATIONS ====================
        const styleSheet = document.createElement('style');
        styleSheet.textContent = `
            @keyframes slideInRight {
                from {
                    transform: translateX(400px);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }

            @keyframes slideOutRight {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(400px);
                    opacity: 0;
                }
            }

            @keyframes popIn {
                0% {
                    transform: translate(-50%, -50%) scale(0);
                    opacity: 0;
                }
                50% {
                    transform: translate(-50%, -50%) scale(1.1);
                }
                100% {
                    transform: translate(-50%, -50%) scale(1);
                    opacity: 1;
                }
            }

            @keyframes popOut {
                from {
                    transform: translate(-50%, -50%) scale(1);
                    opacity: 1;
                }
                to {
                    transform: translate(-50%, -50%) scale(0);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(styleSheet);

        // ==================== PARALLAX EFFECT ====================
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            const shapes = document.querySelectorAll('.shape');
            shapes.forEach((shape, index) => {
                const speed = (index + 1) * 0.1;
                shape.style.transform = `translateY(${scrolled * speed}px)`;
            });
        });

        // ==================== SUCCESS PAGE REDIRECT ====================
        <?php if ($success): ?>
        setTimeout(() => {
            document.getElementById('successNotification').classList.add('show');
        }, 500);
        <?php endif; ?>

        // ==================== PREVENT DOUBLE SUBMIT ====================
        let isSubmitting = false;
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            if (isSubmitting) {
                e.preventDefault();
                return false;
            }
            isSubmitting = true;
        });

        // ==================== AUTO-HIDE ALERTS ====================
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.transition = 'all 0.5s ease';
                alert.style.opacity = '0';
                alert.style.transform = 'translateX(100px)';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>