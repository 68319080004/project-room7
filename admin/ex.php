<?php
// ============================================
// วิธีตั้งค่า Gmail App Password
// ============================================
?>
/*
ขั้นตอนสร้าง App Password สำหรับ Gmail:

1. ไปที่: https://myaccount.google.com/security
2. เปิด "2-Step Verification" (ถ้ายังไม่เปิด)
3. ค้นหา "App passwords"
4. เลือก App: "Mail"
5. เลือก Device: "Windows Computer"
6. คัดลอก Password 16 ตัว
7. วางใน send_email.php ที่บรรทัด:
   $mail->Password = 'xxxx xxxx xxxx xxxx';
*/?>
<?php
// ============================================