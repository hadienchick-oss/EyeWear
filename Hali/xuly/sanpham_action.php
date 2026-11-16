<?php
require_once 'functions.php';

if (!isStaffOrAdmin()) {
    $_SESSION['err'] = "Bạn không có quyền thực hiện thao tác này!";
    header("Location: ../index.php");
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$uploadDir = '../images/anh_sp/';

// Tự động tạo thư mục nếu chưa tồn tại
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// 1. THÊM SẢN PHẨM MỚI
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->beginTransaction();

        $maSP = taoMaTuDong($conn, 'SANPHAM', 'maSanPham', 'SP');
        $tenSP = trim($_POST['tenSanPham']);
        $gia = (float)$_POST['gia'];
        $trangThai = $_POST['trangThai'] ?? 'Đang bán';
        $moTa = trim($_POST['moTa'] ?? '');
        $maThuongHieu = $_POST['maThuongHieu'];

        // 1.1. Lưu bảng SANPHAM
        $sqlSP = "INSERT INTO SANPHAM (maSanPham, tenSanPham, gia, trangThai, moTa, maThuongHieu) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        $conn->prepare($sqlSP)->execute([$maSP, $tenSP, $gia, $trangThai, $moTa, $maThuongHieu]);

        // 1.2. Lưu bảng CHITIETSANPHAM
        $maCT = taoMaTuDong($conn, 'CHITIETSANPHAM', 'maCTSanPham', 'CTSP');
        $sqlCT = "INSERT INTO CHITIETSANPHAM 
                  (maCTSanPham, maSanPham, kichThuoc, chieuRongKhung, chieuRongCau, chieuRongThauKinh, chieuCaoOngKinh, chieuDaiThaiDuong, mauSac, gioiTinh, vatLieu, moTa) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $conn->prepare($sqlCT)->execute([
            $maCT,
            $maSP,
            $_POST['kichThuoc'] ?: 'M',
            $_POST['chieuRongKhung'] ?: null,
            $_POST['chieuRongCau'] ?: null,
            $_POST['chieuRongThauKinh'] ?: null,
            $_POST['chieuCaoOngKinh'] ?: null,
            $_POST['chieuDaiThaiDuong'] ?: null,
            trim($_POST['mauSac'] ?: 'Đen'),
            $_POST['gioiTinh'] ?: 'Unisex',
            trim($_POST['vatLieu'] ?: 'Acetate'),
            $moTa
        ]);

        // 1.3. Lưu bảng TONKO
        $maTK = taoMaTuDong($conn, 'TONKO', 'maTonKho', 'TK');
        $soLuong = (int)($_POST['soLuong'] ?? 0);
        $sqlTK = "INSERT INTO TONKO (maTonKho, maSanPham, soLuong, soLuongDat) VALUES (?, ?, ?, 0)";
        $conn->prepare($sqlTK)->execute([$maTK, $maSP, $soLuong]);

        // 1.4. Upload ảnh vào images/anh_sp/ và lưu bảng ANHSP
        if (!empty($_FILES['hinhAnh']['name'])) {
            $cleanFileName = preg_replace('/[^a-zA-Z0-9._-]/', '', basename($_FILES['hinhAnh']['name']));
            $tenFileAnh = $maSP . '_' . $cleanFileName;
            move_uploaded_file($_FILES['hinhAnh']['tmp_name'], $uploadDir . $tenFileAnh);

            $maAnh = taoMaTuDong($conn, 'ANHSP', 'maAnh', 'ANH');
            $sqlAnh = "INSERT INTO ANHSP (maAnh, maSanPham, hinhAnh, anhChinh) VALUES (?, ?, ?, 1)";
            $conn->prepare($sqlAnh)->execute([$maAnh, $maSP, $tenFileAnh]);
        }

        $conn->commit();
        $_SESSION['msg'] = "Đã thêm sản phẩm [$maSP - $tenSP] thành công!";
    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['err'] = "Lỗi thêm sản phẩm: " . $e->getMessage();
    }
    header("Location: ../index.php?tab=admin-product");
    exit;
}

// 2. SỬA SẢN PHẨM
if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->beginTransaction();

        $maSP = $_POST['maSanPham'];
        $tenSP = trim($_POST['tenSanPham']);
        $gia = (float)$_POST['gia'];
        $trangThai = $_POST['trangThai'];
        $moTa = trim($_POST['moTa'] ?? '');
        $maThuongHieu = $_POST['maThuongHieu'];

        // 2.1. Cập nhật SANPHAM
        $sqlSP = "UPDATE SANPHAM SET tenSanPham=?, gia=?, trangThai=?, moTa=?, maThuongHieu=? WHERE maSanPham=?";
        $conn->prepare($sqlSP)->execute([$tenSP, $gia, $trangThai, $moTa, $maThuongHieu, $maSP]);

        // 2.2. Cập nhật CHITIETSANPHAM
        $sqlCT = "UPDATE CHITIETSANPHAM 
                  SET kichThuoc=?, chieuRongKhung=?, chieuRongCau=?, chieuRongThauKinh=?, 
                      chieuCaoOngKinh=?, chieuDaiThaiDuong=?, mauSac=?, gioiTinh=?, vatLieu=?, moTa=? 
                  WHERE maSanPham=?";
        $conn->prepare($sqlCT)->execute([
            $_POST['kichThuoc'] ?: 'M',
            $_POST['chieuRongKhung'] ?: null,
            $_POST['chieuRongCau'] ?: null,
            $_POST['chieuRongThauKinh'] ?: null,
            $_POST['chieuCaoOngKinh'] ?: null,
            $_POST['chieuDaiThaiDuong'] ?: null,
            trim($_POST['mauSac'] ?: 'Đen'),
            $_POST['gioiTinh'] ?: 'Unisex',
            trim($_POST['vatLieu'] ?: 'Acetate'),
            $moTa,
            $maSP
        ]);

        // 2.3. Cập nhật số lượng tồn vật lý trong bảng TONKO
        $soLuong = (int)($_POST['soLuong'] ?? 0);
        $conn->prepare("UPDATE TONKO SET soLuong = ? WHERE maSanPham = ?")->execute([$soLuong, $maSP]);

        // 2.4. Nếu có tải ảnh mới thì lưu vào images/anh_sp/ và cập nhật bảng ANHSP
        if (!empty($_FILES['hinhAnh']['name'])) {
            $cleanFileName = preg_replace('/[^a-zA-Z0-9._-]/', '', basename($_FILES['hinhAnh']['name']));
            $tenFileAnh = $maSP . '_' . time() . '_' . $cleanFileName;
            move_uploaded_file($_FILES['hinhAnh']['tmp_name'], $uploadDir . $tenFileAnh);

            $checkAnh = $conn->prepare("SELECT maAnh FROM ANHSP WHERE maSanPham = ? AND anhChinh = 1");
            $checkAnh->execute([$maSP]);
            if ($checkAnh->fetch()) {
                $conn->prepare("UPDATE ANHSP SET hinhAnh = ? WHERE maSanPham = ? AND anhChinh = 1")
                     ->execute([$tenFileAnh, $maSP]);
            } else {
                $maAnh = taoMaTuDong($conn, 'ANHSP', 'maAnh', 'ANH');
                $conn->prepare("INSERT INTO ANHSP (maAnh, maSanPham, hinhAnh, anhChinh) VALUES (?, ?, ?, 1)")
                     ->execute([$maAnh, $maSP, $tenFileAnh]);
            }
        }

        $conn->commit();
        $_SESSION['msg'] = "Cập nhật sản phẩm [$maSP] thành công!";
    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['err'] = "Lỗi cập nhật sản phẩm: " . $e->getMessage();
    }
    header("Location: ../index.php?tab=admin-product");
    exit;
}

// 3. XÓA SẢN PHẨM (Chỉ Admin VT01 mới được xóa)
if ($action === 'delete') {
    if (!isAdmin()) {
        $_SESSION['err'] = "Nhân viên không có quyền xóa sản phẩm!";
        header("Location: ../index.php?tab=admin-product");
        exit;
    }

    $maSP = $_GET['id'] ?? '';
    $checkDH = $conn->prepare("SELECT COUNT(*) FROM CHITIETDONHANG WHERE maSanPham = ?");
    $checkDH->execute([$maSP]);

    if ($checkDH->fetchColumn() > 0) {
        $conn->prepare("UPDATE SANPHAM SET trangThai = 'Ngừng bán' WHERE maSanPham = ?")->execute([$maSP]);
        $_SESSION['msg'] = "Sản phẩm [$maSP] đã có lịch sử đơn hàng nên được chuyển sang trạng thái 'Ngừng bán' (Xóa mềm).";
    } else {
        $conn->prepare("DELETE FROM SANPHAM WHERE maSanPham = ?")->execute([$maSP]);
        $_SESSION['msg'] = "Đã xóa vĩnh viễn sản phẩm [$maSP] khỏi hệ thống.";
    }
    header("Location: ../index.php?tab=admin-product");
    exit;
}
?>
