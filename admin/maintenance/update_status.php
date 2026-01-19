<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';

// 1. สร้างตัวเชื่อมต่อฐานข้อมูล
$db = new Database();
$pdo = $db->getConnection();

if (!isset($_POST['id']) || !isset($_POST['status'])) {
    die("ข้อมูลไม่ครบถ้วน");
}

$id = $_POST['id'];
$status = $_POST['status'];
$note = $_POST['note'] ?? ''; // ใช้ technician_notes ตามที่เพิ่มใน DB

try {
    // 2. อัปเดตข้อมูล 
    // - เปลี่ยน status -> request_status
    // - เปลี่ยน note -> technician_notes (ตามคอลัมน์ที่เพิ่มไป)
    // - เพิ่มวันที่เริ่มซ่อม/เสร็จสิ้น อัตโนมัติ (Option เสริม)
    
    $sql = "UPDATE maintenance_requests SET request_status = ?, technician_notes = ? ";
    
    // ถ้าสถานะเป็น 'in_progress' ให้ใส่วันที่เริ่ม
    if ($status == 'in_progress') {
        $sql .= ", started_at = NOW() ";
    }
    // ถ้าสถานะเป็น 'done' หรือ 'completed' ให้ใส่วันที่เสร็จ
    if ($status == 'done' || $status == 'completed') {
        $sql .= ", completed_at = NOW() ";
    }

    $sql .= "WHERE request_id = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$status, $note, $id]);

    header("Location: view.php?id=".$id);
    exit;

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}