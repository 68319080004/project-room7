<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php'; // เพิ่มความปลอดภัย

// 1. สร้างตัวเชื่อมต่อฐานข้อมูล (ส่วนที่ขาดหายไป)
$db = new Database();
$pdo = $db->getConnection();

// ตรวจสอบข้อมูลนำเข้า
if (!isset($_POST['id']) || !isset($_POST['assigned_to'])) {
    die("ข้อมูลไม่ครบถ้วน");
}

$id = $_POST['id'];
$tech = $_POST['assigned_to'];

try {
    // 2. อัปเดตข้อมูล (ใช้ request_status แทน status)
    $stmt = $pdo->prepare("
        UPDATE maintenance_requests 
        SET assigned_to = ?, request_status = 'assigned'
        WHERE request_id = ?
    ");
    $stmt->execute([$tech, $id]);

    // กลับไปหน้า view
    header("Location: view.php?id=".$id);
    exit;
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}