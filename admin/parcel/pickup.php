<?php
// admin/parcel/pickup.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../models/Parcel.php';

requireRole(['admin', 'staff', 'owner']);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $db = new Database();
    $pdo = $db->getConnection();
    $parcelModel = new Parcel($pdo);

    $parcelId = $_POST['id'];
    $staffId = $_SESSION['user_id']; // ใครเป็นคนกดส่งมอบ

    if ($parcelModel->markAsPickedUp($parcelId, $staffId)) {
        // สำเร็จ
        header("Location: index.php?msg=pickup_success");
    } else {
        // ผิดพลาด
        header("Location: index.php?msg=error");
    }
} else {
    header("Location: index.php");
}
exit;