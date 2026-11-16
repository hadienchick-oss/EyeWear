<?php
require_once 'functions.php';

if (!isAdmin()) {
    $_SESSION['err'] = "Bạn không có quyền thực hiện thao tác này!";
    header("Location: ../index.php");
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $hoTen = trim($_POST['hoTen']);
    $email = trim($_POST['email']);
    $matKhau = trim($_POST['matKhau']);
    
    try {
        $check = $conn->prepare("SELECT COUNT(*) FROM NGUOIDUNG WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetchColumn() > 0) {
            $_SESSION['err'] = "Email [$email] đã được sử dụng!";
            header("Location: ../index.php?tab=admin-staff");
            exit;
        }

        $maND = taoMaTuDong($conn, 'NGUOIDUNG', 'maNguoiDung', 'ND');
        $conn->prepare("INSERT INTO NGUOIDUNG (maNguoiDung, hoTen, email, matKhau, maVaiTro) VALUES (?, ?, ?, ?, 'VT02')")
             ->execute([$maND, $hoTen, $email, md5($matKhau)]);

        $_SESSION['msg'] = "Thêm nhân viên [$hoTen] thành công!";
    } catch (PDOException $e) {
        $_SESSION['err'] = "Lỗi thêm nhân viên: " . $e->getMessage();
    }
    header("Location: ../index.php?tab=admin-staff");
    exit;
}

if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $maND = $_POST['maNguoiDung'];
    $hoTen = trim($_POST['hoTen']);
    $email = trim($_POST['email']);
    $matKhauNew = trim($_POST['matKhau'] ?? '');

    try {
        if (!empty($matKhauNew)) {
            $conn->prepare("UPDATE NGUOIDUNG SET hoTen = ?, email = ?, matKhau = ? WHERE maNguoiDung = ?")
                 ->execute([$hoTen, $email, md5($matKhauNew), $maND]);
        } else {
            $conn->prepare("UPDATE NGUOIDUNG SET hoTen = ?, email = ? WHERE maNguoiDung = ?")
                 ->execute([$hoTen, $email, $maND]);
        }
        $_SESSION['msg'] = "Cập nhật nhân viên [$maND] thành công!";
    } catch (PDOException $e) {
        $_SESSION['err'] = "Lỗi cập nhật nhân viên: " . $e->getMessage();
    }
    header("Location: ../index.php?tab=admin-staff");
    exit;
}

if ($action === 'delete') {
    $maND = $_GET['id'] ?? '';
    if ($maND === $_SESSION['user']['maNguoiDung']) {
        $_SESSION['err'] = "Bạn không thể tự xóa tài khoản quản trị của chính mình!";
        header("Location: ../index.php?tab=admin-staff");
        exit;
    }

    try {
        $conn->prepare("DELETE FROM NGUOIDUNG WHERE maNguoiDung = ? AND maVaiTro = 'VT02'")->execute([$maND]);
        $_SESSION['msg'] = "Đã xóa tài khoản nhân viên [$maND].";
    } catch (PDOException $e) {
        $_SESSION['err'] = "Không thể xóa nhân viên này: " . $e->getMessage();
    }
    header("Location: ../index.php?tab=admin-staff");
    exit;
}
?>
