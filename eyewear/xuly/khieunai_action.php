<?php
require_once 'functions.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$uploadDir = '../images/anh_khieunai/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

// 1. KHÁCH HÀNG GỬI KHIẾU NẠI MỚI (BẢNG KHIEUNAI)
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $maKH = layMaKhachHangHienTai($conn);
    if (!$maKH) {
        $_SESSION['err'] = "Vui lòng đăng nhập tài khoản khách hàng để gửi khiếu nại!";
        header("Location: ../index.php");
        exit;
    }

    $maDonHang = !empty($_POST['maDonHang']) ? trim($_POST['maDonHang']) : null;
    $loaiKhieuNai = trim($_POST['loaiKhieuNai'] ?? 'đơn hàng');
    $tieuDe = trim($_POST['tieuDe'] ?? '');
    $noiDung = trim($_POST['noiDung'] ?? '');

    if ($tieuDe === '' || $noiDung === '') {
        $_SESSION['err'] = "Vui lòng nhập đầy đủ Tiêu đề và Nội dung khiếu nại!";
        header("Location: ../index.php?tab=customer-orders");
        exit;
    }

    try {
        $maKN = taoMaTuDong($conn, 'KHIEUNAI', 'maKhieuNai', 'KN');
        $tenFileAnh = null;

        if (!empty($_FILES['hinhAnh']['name']) && $_FILES['hinhAnh']['error'] === UPLOAD_ERR_OK) {
            $rawName = basename($_FILES['hinhAnh']['name']);
            $cleanFileName = preg_replace('/[^a-zA-Z0-9._-]/', '', $rawName);
            $tenFileAnh = $maKN . '_' . $cleanFileName;
            move_uploaded_file($_FILES['hinhAnh']['tmp_name'], $uploadDir . $tenFileAnh);
        }

        $conn->prepare("INSERT INTO KHIEUNAI (maKhieuNai, maKhachHang, maDonHang, loaiKhieuNai, tieuDe, noiDung, hinhAnh, ngayTao, trangThai, phanHoi) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 'Chờ xử lý', NULL)")
             ->execute([$maKN, $maKH, $maDonHang, $loaiKhieuNai, $tieuDe, $noiDung, $tenFileAnh]);

        $_SESSION['msg'] = "Đã gửi khiếu nại [$maKN] thành công! Bộ phận CSKH Việt MAC sẽ phản hồi sớm nhất.";
    } catch (Exception $e) {
        $_SESSION['err'] = "Lỗi gửi khiếu nại: " . $e->getMessage();
    }
    header("Location: ../index.php?tab=customer-orders");
    exit;
}

// 2. NHÂN VIÊN & ADMIN PHẢN HỒI KHIẾU NẠI (Cả VT02 và VT01 đều được phản hồi)
if ($action === 'reply' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isStaffOrAdmin()) {
        $_SESSION['err'] = "Bạn không có quyền xử lý khiếu nại!";
        header("Location: ../index.php");
        exit;
    }

    $maKN = $_POST['maKhieuNai'] ?? '';
    $trangThai = $_POST['trangThai'] ?? 'Đã giải quyết';
    $phanHoi = trim($_POST['phanHoi'] ?? '');

    try {
        $conn->prepare("UPDATE KHIEUNAI SET trangThai = ?, phanHoi = ? WHERE maKhieuNai = ?")
             ->execute([$trangThai, $phanHoi, $maKN]);
        if (isLogged()) {
            ghiNhatKyNhanVien(
                $_SESSION['user']['maNguoiDung'],
                'reply_complaint',
                $maKN,
                "Xử lý khiếu nại #$maKN ($trangThai): $phanHoi"
            );
        }
        $_SESSION['msg'] = "Đã cập nhật phản hồi cho khiếu nại [$maKN]!";
    } catch (Exception $e) {
        $_SESSION['err'] = "Lỗi phản hồi khiếu nại: " . $e->getMessage();
    }
    header("Location: ../index.php?tab=admin-complaints");
    exit;
}
?>
