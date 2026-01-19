<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// ตรวจสอบการ login
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// ตรวจสอบ Role
function hasRole($roles): bool {
    if (!isLoggedIn()) {
        return false;
    }
    
    if (is_array(value: $roles)) {
        return in_array(needle: $_SESSION['role'], haystack: $roles);
    }
    
    return $_SESSION['role'] === $roles;
}

// Redirect ถ้าไม่ได้ login
function requireLogin(): void {
    if (!isLoggedIn()) {
        header(header: "Location: login.php");
        exit();
    }
}

// Redirect ถ้าไม่มีสิทธิ์
function requireRole($roles): void {
    requireLogin();
    if (!hasRole(roles: $roles)) {
        header(header: "Location: unauthorized.php");
        exit();
    }
}
?>