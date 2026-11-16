<?php
require_once 'functions.php';

if (!isStaffOrAdmin()) {
    $_SESSION['err'] = "Bạn không có quyền thực hiện thao tác này!";
    header("Location: ../index.php");
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$uploadDir = '../images/logo_thuonghieu/';

// Tự động tạo thư mục nếu chưa tồn tại
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// 1. THÊM THƯƠNG HIỆU
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $maTH = taoMaTuDong($conn, 'THUONGHIEU', 'maThuongHieu', 'TH');
    $tenTH = trim($_POST['tenThuongHieu']);
    $moTa = trim($_POST['moTa'] ?? '');
    $logoName = null;

    if (!empty($_FILES['logo']['name'])) {
        $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
        $logoName = $maTH . '_logo.' . $ext;
        move_uploaded_file($_FILES['logo']['tmp_name'], $uploadDir . $logoName);
    }

    try {
        $sql = "INSERT INTO THUONGHIEU (maThuongHieu, tenThuongHieu, moTa, logo) VALUES (?, ?, ?, ?)";
        $conn->prepare($sql)->execute([$maTH, $tenTH, $moTa, $logoName]);
        $_SESSION['msg'] = "Thêm thương hiệu [$tenTH] thành công!";
    } catch (PDOException $e) {
        $_SESSION['err'] = "Lỗi: Tên thương hiệu đã tồn tại hoặc dữ liệu không hợp lệ.";
    }
    header("Location: ../index.php?tab=admin-brand");
    exit;
}

// 2. SỬA THƯƠNG HIỆU
if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $maTH = $_POST['maThuongHieu'];
    $tenTH = trim($_POST['tenThuongHieu']);
    $moTa = trim($_POST['moTa'] ?? '');

    try {
        if (!empty($_FILES['logo']['name'])) {
            $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $logoName = $maTH . '_logo_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['logo']['tmp_name'], $uploadDir . $logoName);

            $sql = "UPDATE THUONGHIEU SET tenThuongHieu = ?, moTa = ?, logo = ? WHERE maThuongHieu = ?";
            $conn->prepare($sql)->execute([$tenTH, $moTa, $logoName, $maTH]);
        } else {
            $sql = "UPDATE THUONGHIEU SET tenThuongHieu = ?, moTa = ? WHERE maThuongHieu = ?";
            $conn->prepare($sql)->execute([$tenTH, $moTa, $maTH]);
        }
        $_SESSION['msg'] = "Cập nhật thương hiệu [$maTH] thành công!";
    } catch (PDOException $e) {
        $_SESSION['err'] = "Không thể cập nhật thương hiệu: " . $e->getMessage();
    }
    header("Location: ../index.php?tab=admin-brand");
    exit;
}

// 3. XÓA THƯƠNG HIỆU (Chỉ Admin VT01 mới có quyền xóa)
if ($action === 'delete') {
    if (!isAdmin()) {
        $_SESSION['err'] = "Nhân viên không có quyền xóa thương hiệu!";
        header("Location: ../index.php?tab=admin-brand");
        exit;
    }

    $maTH = $_GET['id'] ?? '';
    $check = $conn->prepare("SELECT COUNT(*) FROM SANPHAM WHERE maThuongHieu = ?");
    $check->execute([$maTH]);

    if ($check->fetchColumn() > 0) {
        $_SESSION['err'] = "Không thể xóa thương hiệu [$maTH] vì vẫn còn sản phẩm kính trực thuộc!";
    } else {
        $conn->prepare("DELETE FROM THUONGHIEU WHERE maThuongHieu = ?")->execute([$maTH]);
        $_SESSION['msg'] = "Đã xóa thương hiệu [$maTH].";
    }
    header("Location: ../index.php?tab=admin-brand");
    exit;
}
?>
