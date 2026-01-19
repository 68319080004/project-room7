<?php
// ============================================
// ไฟล์: logout.php
// คำอธิบาย: ออกจากระบบ
// ============================================

session_start();
session_unset();
session_destroy();

header(header: "Location: login.php");
exit();
?>