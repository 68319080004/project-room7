<?php
// ============================================
// ไฟล์: admin/send_line.php
// คำอธิบาย: ส่งบิลทาง LINE Messaging API
// ============================================

require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';
require_once '../models/Invoice.php';

requireRole(['admin', 'owner']);

$database = new Database();
$db = $database->getConnection();
$invoice = new Invoice($db);

$invoice_id = $_POST['invoice_id'] ?? 0;
$invoiceData = $invoice->getById($invoice_id);

if (!$invoiceData) {
    echo json_encode(['success' => false, 'message' => 'ไม่พบใบเสร็จ']);
    exit;
}

// ตั้งค่า LINE Messaging API
// 1. สมัครที่: https://developers.line.biz/
// 2. สร้าง Channel
// 3. นำ Channel Access Token มาใส่ที่นี่

$channelAccessToken = 'YOUR_CHANNEL_ACCESS_TOKEN'; // เปลี่ยนเป็นของคุณ
$userId = $invoiceData['line_id']; // LINE User ID ของผู้เช่า

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'ผู้เช่าไม่มี LINE ID']);
    exit;
}

// สร้าง PDF
$pdfUrl = 'http://localhost/cns68-1/Roomrentalsystem/admin/invoice_pdf.php?id=' . $invoice_id . '&save=1';
$pdfData = file_get_contents($pdfUrl);
$pdfInfo = json_decode($pdfData, true);

if (!$pdfInfo['success']) {
    echo json_encode(['success' => false, 'message' => 'สร้าง PDF ไม่สำเร็จ']);
    exit;
}

// ส่งข้อความทาง LINE
$messages = [
    [
        'type' => 'text',
        'text' => "🧾 ใบเสร็จรับเงิน\n\n" .
                  "ห้อง: " . $invoiceData['room_number'] . "\n" .
                  "เดือน: " . getThaiMonth($invoiceData['invoice_month']) . " " . toBuddhistYear($invoiceData['invoice_year']) . "\n" .
                  "ยอดรวม: ฿" . number_format($invoiceData['total_amount'], 2) . "\n" .
                  "กำหนดชำระ: " . formatThaiDate($invoiceData['due_date']) . "\n\n" .
                  "กรุณาชำระเงินภายในกำหนด"
    ],
    [
        'type' => 'image',
        'originalContentUrl' => 'http://yourdomain.com/uploads/invoices/' . $pdfInfo['file'] . '.png',
        'previewImageUrl' => 'http://yourdomain.com/uploads/invoices/' . $pdfInfo['file'] . '.png'
    ]
];

$data = [
    'to' => $userId,
    'messages' => $messages
];

$ch = curl_init('https://api.line.me/v2/bot/message/push');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $channelAccessToken
]);

$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200) {
    echo json_encode(['success' => true, 'message' => 'ส่งบิลทาง LINE สำเร็จ']);
} else {
    echo json_encode(['success' => false, 'message' => 'ส่ง LINE ไม่สำเร็จ: ' . $result]);
}
?>
