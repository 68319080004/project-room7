<?php
session_start();
include 'config/db.php';

$username = $_POST['username'];
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);
$room_id = $_POST['room_id'];

// 1. เพิ่มสมาชิก
$sql = "INSERT INTO members (username, password) VALUES (?, ?)";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ss", $username, $password);
mysqli_stmt_execute($stmt);

// 2. เอา user_id ที่เพิ่งสมัคร
$user_id = mysqli_insert_id($conn);

// 3. ผูกห้องกับสมาชิก
$sql2 = "UPDATE rooms SET member_id = ? WHERE room_id = ?";
$stmt2 = mysqli_prepare($conn, $sql2);
mysqli_stmt_bind_param($stmt2, "ii", $user_id, $room_id);
mysqli_stmt_execute($stmt2);

// login อัตโนมัติ
$_SESSION['user_id'] = $user_id;

header("Location: member/dashboard.php");
