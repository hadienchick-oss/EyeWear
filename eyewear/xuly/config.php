<?php
session_start();
date_default_timezone_set('Asia/Ho_Chi_Minh');

$host = 'localhost';
$dbname = 'db_kinh_mat';
$username = 'root';
$password = ''; // Đổi mật khẩu nếu máy bạn có cài pass cho MySQL/MariaDB

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Lỗi kết nối CSDL: " . $e->getMessage());
}
?>
