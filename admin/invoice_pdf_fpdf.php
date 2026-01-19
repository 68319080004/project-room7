<?php
// ============================================
// ไฟล์: admin/invoice_pdf_fpdf.php
// คำอธิบาย: สร้าง PDF ด้วย FPDF (ไม่ต้องใช้ Composer)
// ============================================

require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';
require_once '../models/Invoice.php';
require_once '../models/SystemSettings.php';

// ⭐ ดาวน์โหลด FPDF จาก: http://www.fpdf.org/en/download.php
// วางไฟล์ fpdf.php ในโฟลเดอร์ includes/
$fpdfPath = __DIR__ . '/../includes/fpdf.php';

if (!file_exists($fpdfPath)) {
    die('
    <html>
    <head><meta charset="utf-8"><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head>
    <body>
        <div class="container mt-5">
            <div class="alert alert-warning">
                <h4>ไม่พบ FPDF Library</h4>
                <hr>
                <p><strong>วิธีติดตั้ง:</strong></p>
                <ol>
                    <li>ดาวน์โหลด FPDF: <a href="http://www.fpdf.org/en/download.php" target="_blank">คลิกที่นี่</a></li>
                    <li>แตกไฟล์ Zip</li>
                    <li>คัดลอกไฟล์ <code>fpdf.php</code></li>
                    <li>วางใน: <code>C:\xampp\htdocs\cns68-1\Roomrentalsystem\includes\</code></li>
                </ol>
                <hr>
                <p>หรือดาวน์โหลดโดยตรง: <a href="http://www.fpdf.org/en/dl.php?v=186&f=zip" target="_blank">FPDF 1.86</a></p>
            </div>
            <a href="invoices.php" class="btn btn-secondary">กลับ</a>
        </div>
    </body>
    </html>
    ');
}

require_once $fpdfPath;

$database = new Database();
$db = $database->getConnection();

$invoice = new Invoice($db);
$settings = new SystemSettings($db);

$invoice_id = $_GET['id'] ?? 0;
$invoiceData = $invoice->getById($invoice_id);

if (!$invoiceData) {
    die('ไม่พบใบเสร็จ');
}

// ดึงข้อมูลหอพัก
$dormName = $settings->get('dormitory_name');
$dormAddress = $settings->get('dormitory_address');
$dormPhone = $settings->get('dormitory_phone');

// สร้าง PDF
$pdf = new FPDF('P', 'mm', 'A4');
$pdf->AddPage();

// ⭐ แก้ไข: ใช้ฟอนต์ที่มีใน FPDF อยู่แล้ว
$pdf->SetFont('Arial', 'B', 16);

// Header
$pdf->Cell(0, 10, iconv('UTF-8', 'TIS-620', $dormName), 0, 1, 'L');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, iconv('UTF-8', 'TIS-620', $dormAddress), 0, 1, 'L');
$pdf->Cell(0, 6, iconv('UTF-8', 'TIS-620', 'Tel: ' . $dormPhone), 0, 1, 'L');

// เลขที่บิล
$pdf->SetFont('Arial', 'B', 20);
$pdf->SetTextColor(220, 53, 69);
$pdf->Cell(0, 10, iconv('UTF-8', 'TIS-620', 'INVOICE'), 0, 1, 'R');
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, $invoiceData['invoice_number'], 0, 1, 'R');

$pdf->Ln(5);

// ข้อมูลผู้เช่า
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(95, 6, iconv('UTF-8', 'TIS-620', 'Customer Information:'), 0, 0, 'L');
$pdf->Cell(95, 6, iconv('UTF-8', 'TIS-620', 'Invoice Details:'), 0, 1, 'R');

$pdf->SetFont('Arial', '', 10);
$pdf->Cell(95, 6, iconv('UTF-8', 'TIS-620', 'Name: ' . $invoiceData['tenant_name']), 0, 0, 'L');
$pdf->Cell(95, 6, iconv('UTF-8', 'TIS-620', 'Month: ' . getThaiMonth($invoiceData['invoice_month']) . ' ' . toBuddhistYear($invoiceData['invoice_year'])), 0, 1, 'R');

$pdf->Cell(95, 6, iconv('UTF-8', 'TIS-620', 'Room: ' . $invoiceData['room_number']), 0, 0, 'L');
$pdf->Cell(95, 6, iconv('UTF-8', 'TIS-620', 'Due: ' . formatThaiDate($invoiceData['due_date'])), 0, 1, 'R');

$pdf->Ln(10);

// ตาราง
$pdf->SetFillColor(13, 110, 253);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('Arial', 'B', 10);

$pdf->Cell(90, 10, 'Description', 1, 0, 'L', true);
$pdf->Cell(40, 10, 'Quantity', 1, 0, 'C', true);
$pdf->Cell(50, 10, 'Amount (THB)', 1, 1, 'R', true);

$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Arial', '', 10);

// รายการ
$items = [
    ['Monthly Rent', '1 month', $invoiceData['monthly_rent']],
    ['Water (' . formatMoney($invoiceData['water_usage'] ?? 0) . ' units)', formatMoney($invoiceData['water_usage'] ?? 0) . ' units', $invoiceData['water_charge']],
    ['Electric (' . formatMoney($invoiceData['electric_usage'] ?? 0) . ' units)', formatMoney($invoiceData['electric_usage'] ?? 0) . ' units', $invoiceData['electric_charge']],
    ['Garbage Fee', '1 month', $invoiceData['garbage_fee']]
];

if ($invoiceData['previous_balance'] > 0) {
    $items[] = ['Previous Balance', '-', $invoiceData['previous_balance']];
}

if ($invoiceData['discount'] > 0) {
    $items[] = ['Discount', '-', -$invoiceData['discount']];
}

foreach ($items as $item) {
    $pdf->Cell(90, 8, $item[0], 1, 0, 'L');
    $pdf->Cell(40, 8, $item[1], 1, 0, 'C');
    $pdf->Cell(50, 8, number_format($item[2], 2), 1, 1, 'R');
}

// รวม
$pdf->SetFillColor(231, 243, 255);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(130, 10, 'TOTAL', 1, 0, 'R', true);
$pdf->SetTextColor(13, 110, 253);
$pdf->Cell(50, 10, number_format($invoiceData['total_amount'], 2) . ' THB', 1, 1, 'R', true);

$pdf->Ln(10);

// หมายเหตุ
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Arial', 'I', 10);
$pdf->Cell(0, 6, '*** Please pay within the due date ***', 0, 1, 'C');
$pdf->Cell(0, 6, 'Thank you for your business', 0, 1, 'C');

// ส่งออก PDF
$filename = 'Invoice_' . $invoiceData['invoice_number'] . '.pdf';

if (isset($_GET['download'])) {
    $pdf->Output('D', $filename);
} elseif (isset($_GET['save'])) {
    $savePath = __DIR__ . '/../uploads/invoices/';
    if (!file_exists($savePath)) {
        mkdir($savePath, 0777, true);
    }
    $pdf->Output('F', $savePath . $filename);
    echo json_encode(['success' => true, 'file' => $filename]);
} else {
    $pdf->Output('I', $filename);
}
?>

<!-- 
📌 วิธีติดตั้ง FPDF:
1. ดาวน์โหลด: http://www.fpdf.org/en/dl.php?v=186&f=zip
2. แตกไฟล์
3. คัดลอก fpdf.php
4. วางใน: C:\xampp\htdocs\cns68-1\Roomrentalsystem\includes\

✅ ข้อ