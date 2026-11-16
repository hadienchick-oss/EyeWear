<?php
require_once 'functions.php';

if (!isAdmin()) {
    $_SESSION['err'] = "Chỉ Quản trị viên (Admin) mới có quyền quản lý tài khoản khách hàng!";
    header("Location: ../index.php");
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $maKH = $_POST['maKhachHang'];
    $maND = $_POST['maNguoiDung'];
    $hoTen = trim($_POST['hoTen']);
    $email = trim($_POST['email']);
    $soDienThoai = trim($_POST['soDienThoai']);
    $gioiTinh = $_POST['gioiTinh'] ?? 'Nam';
    $ngaySinh = !empty($_POST['ngaySinh']) ? $_POST['ngaySinh'] . ' 00:00:00' : null;
    $diaChi = trim($_POST['diaChi'] ?? '');
    $matKhauMoi = trim($_POST['matKhau'] ?? '');

    try {
        $conn->beginTransaction();

        if ($matKhauMoi !== '') {
            $conn->prepare("UPDATE NGUOIDUNG SET hoTen = ?, email = ?, matKhau = ? WHERE maNguoiDung = ?")
                 ->execute([$hoTen, $email, md5($matKhauMoi), $maND]);
        } else {
            $conn->prepare("UPDATE NGUOIDUNG SET hoTen = ?, email = ? WHERE maNguoiDung = ?")
                 ->execute([$hoTen, $email, $maND]);
        }

        $conn->prepare("UPDATE KHACHHANG SET gioiTinh = ?, ngaySinh = ?, soDienThoai = ?, diaChi = ? WHERE maKhachHang = ?")
             ->execute([$gioiTinh, $ngaySinh, $soDienThoai, $diaChi, $maKH]);

        $conn->commit();
        $_SESSION['msg'] = "Đã cập nhật hồ sơ khách hàng [$maKH - $hoTen]!";
    } catch (Exception $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        $_SESSION['err'] = "Lỗi cập nhật khách hàng: " . $e->getMessage();
    }
    header("Location: ../index.php?tab=admin-customer");
    exit;
}

if ($action === 'delete') {
    $maND = $_GET['id'] ?? '';
    try {
        // ON DELETE CASCADE trên KHACHHANG sẽ tự dọn hồ sơ KHACHHANG, đồng thời ON DELETE SET NULL giữ nguyên lịch sử DONHANG
        $conn->prepare("DELETE FROM NGUOIDUNG WHERE maNguoiDung = ? AND maVaiTro = 'VT03'")->execute([$maND]);
        $_SESSION['msg'] = "Đã xóa tài khoản khách hàng [$maND] (Lịch sử đơn hàng vẫn được bảo toàn).";
    } catch (PDOException $e) {
        $_SESSION['err'] = "Lỗi xóa tài khoản khách hàng: " . $e->getMessage();
    }
    header("Location: ../index.php?tab=admin-customer");
    exit;
}
?>
