<?php
require_once 'functions.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// 1. GỬI ĐÁNH GIÁ
if ($action === 'submit_review' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $maKH = layMaKhachHangHienTai($conn);
    if (!$maKH) {
        $_SESSION['err'] = "Vui lòng đăng nhập tài khoản khách hàng để gửi đánh giá!";
        header("Location: ../index.php");
        exit;
    }

    $maSP = $_POST['maSanPham'] ?? '';
    $sao = max(1, min(5, (int)($_POST['sao'] ?? 5)));
    $binhLuan = trim($_POST['binhLuan'] ?? '');

    $checkBought = $conn->prepare("SELECT COUNT(*) 
                                   FROM CHITIETDONHANG ct 
                                   JOIN DONHANG dh ON ct.maDonHang = dh.maDonHang 
                                   WHERE dh.maKhachHang = ? AND ct.maSanPham = ?");
    $checkBought->execute([$maKH, $maSP]);

    if ($checkBought->fetchColumn() == 0) {
        $_SESSION['err'] = "Bạn chỉ có thể đánh giá sản phẩm sau khi đã đặt mua sản phẩm này!";
        header("Location: ../index.php?tab=shop-view");
        exit;
    }

    // Kiểm tra xem khách đã đánh giá sản phẩm này chưa, nếu rồi thì cập nhật, chưa thì thêm mới (hoặc cho phép nhiều đánh giá)
    $maDG = taoMaTuDong($conn, 'DANHGIA', 'maDanhGia', 'DG');
    $conn->prepare("INSERT INTO DANHGIA (maDanhGia, maSanPham, maKhachHang, sao, binhLuan) VALUES (?, ?, ?, ?, ?)")
         ->execute([$maDG, $maSP, $maKH, $sao, $binhLuan]);

    $_SESSION['msg'] = "Cảm ơn bạn đã gửi đánh giá sản phẩm!";
    header("Location: ../index.php?tab=shop-view");
    exit;
}

// 2. SỬA ĐÁNH GIÁ
if ($action === 'edit_review' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $maKH = layMaKhachHangHienTai($conn);
    $maDanhGia = $_POST['maDanhGia'] ?? '';
    $sao = max(1, min(5, (int)($_POST['sao'] ?? 5)));
    $binhLuan = trim($_POST['binhLuan'] ?? '');

    if ($maKH) {
        $conn->prepare("UPDATE DANHGIA SET sao = ?, binhLuan = ? WHERE maDanhGia = ? AND maKhachHang = ?")
             ->execute([$sao, $binhLuan, $maDanhGia, $maKH]);
        $_SESSION['msg'] = "Đã cập nhật đánh giá của bạn.";
    }
    header("Location: ../index.php?tab=shop-view");
    exit;
}

// 3. XÓA ĐÁNH GIÁ
if ($action === 'delete_review') {
    $maKH = layMaKhachHangHienTai($conn);
    $maDanhGia = $_GET['id'] ?? '';
    if ($maKH) {
        $conn->prepare("DELETE FROM DANHGIA WHERE maDanhGia = ? AND maKhachHang = ?")->execute([$maDanhGia, $maKH]);
        $_SESSION['msg'] = "Đã xóa đánh giá của bạn.";
    }
    header("Location: ../index.php?tab=shop-view");
    exit;
}
?>
