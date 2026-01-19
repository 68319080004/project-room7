<?php
// ============================================
// ไฟล์: login.php (ULTRA PREMIUM EDITION)
// คำอธิบาย: หน้า Login พร้อม Backend + Panda + 3D Particles
// ============================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/models/User.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน';
    } else {
        $database = new Database();
        $db = $database->getConnection();
        $user = new User($db);

        if ($user->login($username, $password)) {
            if ($_SESSION['role'] === 'owner' || $_SESSION['role'] === 'admin') {
                header("Location: admin/dashboard.php");
                exit();
            } else {
                header("Location: member/dashboard.php");
                exit();
            }
        } else {
            $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - ระบบจัดการหอพัก</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&family=Syne:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --neon-blue: #00d4ff;
            --neon-purple: #a855f7;
            --neon-pink: #ec4899;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Poppins', sans-serif;
            background: #000000;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        #background-canvas {
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1;
        }

        .hidden-svg { display: none; }

        .gradient-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 80%, rgba(0, 212, 255, 0.08) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(168, 85, 247, 0.08) 0%, transparent 50%);
            z-index: 2;
            pointer-events: none;
        }

        .login-container {
            position: relative;
            z-index: 100;
            width: 100%;
            max-width: 480px;
            padding: 20px;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(40px);
            border: 2px solid rgba(0, 212, 255, 0.3);
            border-radius: 40px;
            box-shadow: 0 0 100px rgba(0, 212, 255, 0.3), 0 20px 80px rgba(0, 0, 0, 0.8);
            overflow: hidden;
            animation: cardEntrance 1.2s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            padding: 40px;
        }

        @keyframes cardEntrance {
            0% { opacity: 0; transform: translateY(100px) scale(0.8); }
            100% { opacity: 1; transform: translateY(0) scale(1); }
        }

        .panda-container {
            position: relative;
            height: 200px;
            margin-bottom: 30px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .panda-face {
            height: 7.5em;
            width: 8.4em;
            background-color: #fff;
            border: 0.18em solid #2e0d30;
            border-radius: 7.5em 7.5em 5.62em 5.62em;
            position: relative;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .ear-l, .ear-r {
            background: #3f3554;
            height: 2.5em;
            width: 2.81em;
            border: 0.18em solid #2e0d30;
            border-radius: 2.5em 2.5em 0 0;
            position: absolute;
            top: -1.2em;
        }

        .ear-l { transform: rotate(-38deg); left: 0.5em; }
        .ear-r { transform: rotate(38deg); right: 0.5em; }

        .blush-l, .blush-r {
            background: #ff8bb1;
            height: 1em;
            width: 1.37em;
            border-radius: 50%;
            position: absolute;
            top: 4em;
        }

        .blush-l { transform: rotate(25deg); left: 1em; }
        .blush-r { transform: rotate(-25deg); right: 1em; }

        .eye-l, .eye-r {
            background: #3f3554;
            height: 2.18em;
            width: 2em;
            border-radius: 2em;
            position: absolute;
            top: 2.18em;
            transition: all 0.3s ease;
        }

        .eye-l { left: 1.37em; transform: rotate(-20deg); }
        .eye-r { right: 1.37em; transform: rotate(20deg); }

        .eyeball-l, .eyeball-r {
            height: 0.6em;
            width: 0.6em;
            background: #fff;
            border-radius: 50%;
            position: absolute;
            left: 0.6em;
            top: 0.6em;
            transition: all 0.3s ease;
        }

        .nose {
            height: 1em;
            width: 1em;
            background: #3f3554;
            position: absolute;
            top: 4.37em;
            left: 50%;
            transform: translateX(-50%) rotate(45deg);
            border-radius: 1.2em 0 0 0.25em;
        }

        .nose:before {
            content: "";
            position: absolute;
            background: #3f3554;
            height: 0.6em;
            width: 0.1em;
            transform: rotate(-45deg);
            top: 0.75em;
            left: 1em;
        }

        .mouth, .mouth:before {
            height: 0.75em;
            width: 0.93em;
            background: transparent;
            position: absolute;
            border-radius: 50%;
            box-shadow: 0 0.18em #3f3554;
        }

        .mouth { top: 5.31em; left: 50%; transform: translateX(-50%); }
        .mouth:before { content: ""; left: 0.87em; }

        .hand-l, .hand-r {
            background: #3f3554;
            height: 2.81em;
            width: 2.5em;
            border: 0.18em solid #2e0d30;
            border-radius: 0.6em 0.6em 2.18em 2.18em;
            transition: all 0.3s ease;
            position: absolute;
            top: 5.5em;
        }

        .hand-l { left: -1em; }
        .hand-r { right: -1em; }

        .form-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .form-header h2 {
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea 0%, var(--neon-purple) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }

        .form-header p {
            color: #6b7280;
            font-size: 1rem;
        }

        .form-group { margin-bottom: 25px; }

        .form-label {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 8px;
            font-size: 0.9rem;
            display: block;
        }

        .input-wrapper { position: relative; }

        .input-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.2rem;
            color: #667eea;
            z-index: 10;
        }

        .form-control {
            width: 100%;
            padding: 15px 20px 15px 50px;
            border: 2px solid #e5e7eb;
            border-radius: 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #fafafa;
            font-weight: 500;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
            background: white;
        }

        .password-toggle {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #9ca3af;
            font-size: 1.2rem;
            transition: all 0.3s ease;
            z-index: 10;
        }

        .password-toggle:hover { color: #667eea; }

        .btn-login {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, var(--neon-purple) 100%);
            border: none;
            border-radius: 15px;
            color: white;
            font-weight: 700;
            font-size: 1.05rem;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.5);
        }

        .divider {
            display: flex;
            align-items: center;
            margin: 25px 0;
            color: #9ca3af;
            font-size: 0.85rem;
        }

        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e5e7eb;
        }

        .divider span { padding: 0 15px; }

        .demo-section {
            background: #f9fafb;
            border-radius: 20px;
            padding: 20px;
            margin-top: 20px;
        }

        .demo-title {
            font-weight: 700;
            font-size: 0.9rem;
            color: #1f2937;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .demo-account {
            background: white;
            padding: 12px 18px;
            border-radius: 12px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 2px solid transparent;
        }

        .demo-account:hover {
            border-color: #667eea;
            transform: translateX(5px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.2);
        }

        .demo-account .username {
            font-weight: 600;
            color: #1f2937;
        }

        .role-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            color: white;
        }

        .badge-owner { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .badge-admin { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .badge-member { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }

        .register-link {
            text-align: center;
            margin-top: 25px;
            font-size: 1rem;
            color: #6b7280;
            font-weight: 500;
        }

        .register-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.3s ease;
        }

        .register-link a:hover {
            color: var(--neon-purple);
            text-decoration: underline;
        }

        .alert {
            border-radius: 15px;
            padding: 15px 20px;
            margin-bottom: 20px;
            border: none;
            font-weight: 600;
            animation: alertSlideIn 0.6s ease;
        }

        @keyframes alertSlideIn {
            0% { opacity: 0; transform: translateY(-20px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        .alert-danger {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
            border-left: 4px solid #dc2626;
        }

        .alert-success {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        @media (max-width: 576px) {
            .glass-card { padding: 30px 25px; }
            .form-header h2 { font-size: 1.6rem; }
            .panda-face { transform: scale(0.9); }
        }
    </style>
</head>
<body>
    <svg class="hidden-svg" viewBox="0 0 600 552">
        <path id="heart-path" d="M300,107.77C284.68,55.67,239.76,0,162.31,0,64.83,0,0,82.08,0,171.71c0,.48,0,.95,0,1.43-.52,19.5,0,217.94,299.87,379.69v0l0,0,.05,0,0,0,0,0v0C600,391.08,600.48,192.64,600,173.14c0-.48,0-.95,0-1.43C600,82.08,535.17,0,437.69,0,360.24,0,315.32,55.67,300,107.77"/>
    </svg>

    <canvas id="background-canvas"></canvas>
    <div class="gradient-overlay"></div>

    <div class="login-container">
        <div class="glass-card">
            <div class="panda-container">
                <div class="panda-face">
                    <div class="ear-l"></div>
                    <div class="ear-r"></div>
                    <div class="blush-l"></div>
                    <div class="blush-r"></div>
                    <div class="eye-l"><div class="eyeball-l"></div></div>
                    <div class="eye-r"><div class="eyeball-r"></div></div>
                    <div class="nose"></div>
                    <div class="mouth"></div>
                    <div class="hand-l"></div>
                    <div class="hand-r"></div>
                </div>
            </div>

            <div class="form-header">
                <h2>ยินดีต้อนรับกลับมา! 👋</h2>
                <p>เข้าสู่ระบบเพื่อเริ่มการทำงาน</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="loginForm">
                <div class="form-group">
                    <label class="form-label">ชื่อผู้ใช้</label>
                    <div class="input-wrapper">
                        <span class="input-icon"><i class="bi bi-person"></i></span>
                        <input type="text" class="form-control" id="username" name="username" placeholder="กรอกชื่อผู้ใช้ของคุณ" required autofocus>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">รหัสผ่าน</label>
                    <div class="input-wrapper">
                        <span class="input-icon"><i class="bi bi-key"></i></span>
                        <input type="password" class="form-control" id="password" name="password" placeholder="กรอกรหัสผ่านของคุณ" required>
                        <span class="password-toggle" onclick="togglePassword()">
                            <i class="bi bi-eye" id="toggleIcon"></i>
                        </span>
                    </div>
                </div>

                <button type="submit" class="btn-login">
                    <i class="bi bi-box-arrow-in-right me-2"></i>เข้าสู่ระบบ
                </button>
            </form>

            <div class="divider"><span>หรือลองใช้บัญชีทดสอบ</span></div>

            <div class="demo-section">
                <div class="demo-title">
                    <i class="bi bi-info-circle-fill"></i>บัญชีสำหรับทดสอบระบบ
                </div>
                <div class="demo-account" onclick="fillLogin('op', '123456')">
                    <span class="username"><i class="bi bi-person-badge me-2"></i>owner</span>
                    <span class="role-badge badge-owner">เจ้าของ</span>
                </div>
                <div class="demo-account" onclick="fillLogin('admin', '123456')">
                    <span class="username"><i class="bi bi-person-badge me-2"></i>admin</span>
                    <span class="role-badge badge-admin">ผู้ดูแล</span>
                </div>
                <div class="demo-account" onclick="fillLogin('lek', '123456')">
                    <span class="username"><i class="bi bi-person-badge me-2"></i>lek</span>
                    <span class="role-badge badge-member">ผู้เช่า</span>
                </div>
            </div>

            <div class="register-link">
                ยังไม่มีบัญชี? <a href="register.php">
                    <i class="bi bi-person-plus"></i> สมัครสมาชิกที่นี่
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script>
        // Panda Animation
        const usernameRef = document.getElementById("username");
        const passwordRef = document.getElementById("password");
        const eyeL = document.querySelector(".eyeball-l");
        const eyeR = document.querySelector(".eyeball-r");
        const handL = document.querySelector(".hand-l");
        const handR = document.querySelector(".hand-r");

        function normalEyeStyle() {
            eyeL.style.cssText = `left: 0.6em; top: 0.6em;`;
            eyeR.style.cssText = `left: 0.6em; top: 0.6em;`;
        }

        function normalHandStyle() {
            handL.style.cssText = `height: 2.81em; top: 5.5em; left: -1em; transform: rotate(0deg);`;
            handR.style.cssText = `height: 2.81em; top: 5.5em; right: -1em; transform: rotate(0deg);`;
        }

        usernameRef.addEventListener("focus", () => {
            eyeL.style.cssText = `left: 0.75em; top: 1.12em;`;
            eyeR.style.cssText = `left: 0.75em; top: 1.12em;`;
            normalHandStyle();
        });

        passwordRef.addEventListener("focus", () => {
            handL.style.cssText = `height: 6.56em; top: 1.5em; left: 1.8em; transform: rotate(-155deg);`;
            handR.style.cssText = `height: 6.56em; top: 1.5em; right: 1.8em; transform: rotate(155deg);`;
            normalEyeStyle();
        });

        document.addEventListener("click", (e) => {
            if (e.target != usernameRef && e.target != passwordRef) {
                normalEyeStyle();
                normalHandStyle();
            }
        });

        function togglePassword() {
            const pwd = document.getElementById('password');
            const icon = document.getElementById('toggleIcon');
            if (pwd.type === 'password') {
                pwd.type = 'text';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                pwd.type = 'password';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        }

        function fillLogin(u, p) {
            usernameRef.value = u;
            passwordRef.value = p;
            usernameRef.focus();
            setTimeout(() => passwordRef.focus(), 500);
        }

        // THREE.JS Animation
        const canvas = document.getElementById('background-canvas');
        const scene = new THREE.Scene();
        const camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 3000);
        camera.position.z = 600;

        const renderer = new THREE.WebGLRenderer({ canvas, alpha: true, antialias: true });
        renderer.setSize(window.innerWidth, window.innerHeight);

        const path = document.getElementById("heart-path");
        const length = path.getTotalLength();
        const vertices = [];

        for (let i = 0; i < length; i += 0.8) {
            const pt = path.getPointAtLength(i);
            vertices.push(new THREE.Vector3(
                pt.x - 300 + (Math.random() - 0.5) * 40,
                -(pt.y - 276) + (Math.random() - 0.5) * 40,
                (Math.random() - 0.5) * 150
            ));
        }

        const geo1 = new THREE.BufferGeometry().setFromPoints(vertices);
        const mat1 = new THREE.PointsMaterial({ color: 0xee5282, size: 2.5, transparent: true, opacity: 0.7 });
        const particles1 = new THREE.Points(geo1, mat1);
        particles1.position.set(-250, 150, 0);
        scene.add(particles1);

        const vertices2 = [];
        for (let i = 0; i < 800; i++) {
            const a = i * 0.25;
            const r = i * 0.25;
            vertices2.push(new THREE.Vector3(Math.cos(a) * r, Math.sin(a) * r, i * 0.4 - 200));
        }

        const geo2 = new THREE.BufferGeometry().setFromPoints(vertices2);
        const mat2 = new THREE.PointsMaterial({ color: 0x00d4ff, size: 2, transparent: true, opacity: 0.6 });
        const particles2 = new THREE.Points(geo2, mat2);
        particles2.position.set(250, 0, 0);
        scene.add(particles2);

        const vertices3 = [];
        for (let i = 0; i < 400; i++) {
            vertices3.push(new THREE.Vector3(
                (Math.random() - 0.5) * 1200,
                (Math.random() - 0.5) * 1200,
                (Math.random() - 0.5) * 800
            ));
        }

        const geo3 = new THREE.BufferGeometry().setFromPoints(vertices3);
        const mat3 = new THREE.PointsMaterial({ color: 0xa855f7, size: 2.5, transparent: true, opacity: 0.5 });
        const particles3 = new THREE.Points(geo3, mat3);
        scene.add(particles3);

        let time = 0;
        let mouseX = 0, mouseY = 0;

        document.addEventListener('mousemove', (e) => {
            mouseX = (e.clientX / window.innerWidth) * 2 - 1;
            mouseY = -(e.clientY / window.innerHeight) * 2 + 1;
        });

        function animate() {
            requestAnimationFrame(animate);
            time += 0.008;

            particles1.rotation.y += 0.001;
            particles1.rotation.x = Math.sin(time * 0.5) * 0.1;

            particles2.rotation.y += 0.002;
            particles2.rotation.z = Math.cos(time * 0.3) * 0.1;

            particles3.rotation.y -= 0.0008;
            particles3.rotation.x = Math.sin(time * 0.4) * 0.08;

            camera.position.x += (mouseX * 40 - camera.position.x) * 0.05;
            camera.position.y += (mouseY * 40 - camera.position.y) * 0.05;
            camera.lookAt(scene.position);

            scene.rotation.y = Math.sin(time * 0.15) * 0.05;

            renderer.render(scene, camera);
        }

        window.addEventListener('resize', () => {
            camera.aspect = window.innerWidth / window.innerHeight;
            camera.updateProjectionMatrix();
            renderer.setSize(window.innerWidth, window.innerHeight);
        });

        animate();
    </script>
</body>
</html>