<?php
// ============================================
// ไฟล์: includes/functions.php
// คำอธิบาย: ฟังก์ชันช่วยเหลือทั่วไป
// ============================================

// แปลงเดือนเป็นภาษาไทย
function getThaiMonth($month) {
    $months = [
        1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 
        4 => 'เมษายน', 5 => 'พฤษภาคม', 6 => 'มิถุนายน',
        7 => 'กรกฎาคม', 8 => 'สิงหาคม', 9 => 'กันยายน',
        10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
    ];
    return $months[$month] ?? '';
}

// แปลงปี ค.ศ. เป็น พ.ศ.
function toBuddhistYear($year) {
    return $year + 543;
}

// Format เงิน
function formatMoney($amount) {
    return number_format($amount, 2);
}

// Format วันที่
function formatThaiDate($date) {
    if (!$date) return '-';
    $timestamp = strtotime($date);
    $day = date('j', $timestamp);
    $month = getThaiMonth(date('n', $timestamp));
    $year = toBuddhistYear(date('Y', $timestamp));
    return "$day $month $year";
}

// แปลงสถานะเป็นภาษาไทย
function getPaymentStatusText($status) {
    $statuses = [
        'pending' => 'รอชำระเงิน',
        'checking' => 'รอตรวจสอบ',
        'paid' => 'ชำระแล้ว',
        'overdue' => 'เกินกำหนด'
    ];
    return $statuses[$status] ?? $status;
}

// แปลงสถานะเป็นสี Badge
function getPaymentStatusBadge($status) {
    $badges = [
        'pending' => '<span class="badge bg-warning">รอชำระเงิน</span>',
        'checking' => '<span class="badge bg-info">รอตรวจสอบ</span>',
        'paid' => '<span class="badge bg-success">ชำระแล้ว</span>',
        'overdue' => '<span class="badge bg-danger">เกินกำหนด</span>'
    ];
    return $badges[$status] ?? $status;
}

// Upload ไฟล์
function uploadFile($file, $targetDir = 'uploads/slips/') {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
    if (!in_array($file['type'], $allowedTypes)) {
        return false;
    }
    
    $maxSize = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $maxSize) {
        return false;
    }
    
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $targetPath = $targetDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return $filename;
    }
    
    return false;
}
?>