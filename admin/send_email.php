<?php
// ============================================
// ไฟล์: admin/send_email.php
// คำอธิบาย: ส่งบิล PDF ทาง Email (ไม่ต้องใช้ LINE)
// ============================================

require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';
require_once '../models/Invoice.php';

// ติดตั้ง PHPMailer: composer require phpmailer/phpmailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

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

// ตรวจสอบ Email
$recipientEmail = $_POST['email'] ?? null;
if (!$recipientEmail) {
    echo json_encode(['success' => false, 'message' => 'กรุณาระบุอีเมลผู้รับ']);
    exit;
}

// สร้าง PDF
$pdfPath = __DIR__ . '/../uploads/invoices/';
$pdfFilename = 'Invoice_' . $invoiceData['invoice_number'] . '.pdf';

if (!file_exists($pdfPath . $pdfFilename)) {
    // สร้าง PDF ใหม่
    file_get_contents('http://localhost/cns68-1/Roomrentalsystem/admin/invoice_pdf.php?id=' . $invoice_id . '&save=1');
}

// ตั้งค่า Email
$mail = new PHPMailer(true);

try {
    // ตั้งค่า SMTP (ใช้ Gmail)
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'your-email@gmail.com'; // เปลี่ยนเป็น Email ของคุณ
    $mail->Password = 'your-app-password';    // ใช้ App Password จาก Gmail
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->CharSet = 'UTF-8';
    
    // ผู้ส่ง
    $mail->setFrom('your-email@gmail.com', 'ระบบจัดการหอพัก');
    
    // ผู้รับ
    $mail->addAddress($recipientEmail, $invoiceData['tenant_name']);
    
    // แนบไฟล์ PDF
    $mail->addAttachment($pdfPath . $pdfFilename);
    
    // เนื้อหา Email
    $mail->isHTML(true);
    $mail->Subject = 'ใบเสร็จรับเงิน - ' . $invoiceData['invoice_number'];
    $mail->Body = '
        <html>
        <body style="font-family: Arial, sans-serif;">
            <h2>ใบเสร็จรับเงิน</h2>
            <p>เรียน คุณ' . $invoiceData['tenant_name'] . '</p>
            <p>ส่งใบเสร็จค่าเช่าห้อง <strong>' . $invoiceData['room_number'] . '</strong></p>
            <ul>
                <li><strong>เดือน:</strong> ' . getThaiMonth($invoiceData['invoice_month']) . ' ' . toBuddhistYear($invoiceData['invoice_year']) . '</li>
                <li><strong>ยอดรวม:</strong> ฿' . number_format($invoiceData['total_amount'], 2) . '</li>
                <li><strong>กำหนดชำระ:</strong> ' . formatThaiDate($invoiceData['due_date']) . '</li>
            </ul>
            <p style="color: red;"><strong>กรุณาชำระเงินภายในกำหนด</strong></p>
            <p>รายละเอียดเพิ่มเติมในไฟล์แนบ</p>
            <hr>
            <p style="color: gray; font-size: 12px;">Email นี้ส่งอัตโนมัติจากระบบ กรุณาอย่า Reply</p>
        </body>
        </html>
    ';
    
    $mail->send();
    echo json_encode(['success' => true, 'message' => 'ส่ง Email สำเร็จ!']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'ส่ง Email ไม่สำเร็จ: ' . $mail->ErrorInfo]);
}
?>