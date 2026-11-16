<?php
require_once 'functions.php';

if (!isStaffOrAdmin()) {
    $_SESSION['err'] = "Bạn không có quyền thực hiện thao tác này!";
    header("Location: ../index.php");
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$uploadDir = '../images/anh_sp/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->beginTransaction();

        $maSP = taoMaTuDong($conn, 'SANPHAM', 'maSanPham', 'SP');
        $tenSP = trim($_POST['tenSanPham']);
        $gia = (float)$_POST['gia'];
        $trangThai = $_POST['trangThai'] ?? 'Đang bán';
        $moTa = trim($_POST['moTa'] ?? '');
        $maThuongHieu = $_POST['maThuongHieu'];

        $conn->prepare("INSERT INTO SANPHAM (maSanPham, tenSanPham, gia, trangThai, moTa, maThuongHieu) VALUES (?, ?, ?, ?, ?, ?)")
             ->execute([$maSP, $tenSP, $gia, $trangThai, $moTa, $maThuongHieu]);

        $maCT = taoMaTuDong($conn, 'CHITIETSANPHAM', 'maCTSanPham', 'CTSP');
        $conn->prepare("INSERT INTO CHITIETSANPHAM 
                  (maCTSanPham, maSanPham, kichThuoc, chieuRongKhung, chieuRongCau, chieuRongThauKinh, chieuCaoOngKinh, chieuDaiThaiDuong, mauSac, gioiTinh, vatLieu, moTa) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
             ->execute([
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

        $maTK = taoMaTuDong($conn, 'TONKO', 'maTonKho', 'TK');
        $soLuong = (int)($_POST['soLuong'] ?? 0);
        $conn->prepare("INSERT INTO TONKO (maTonKho, maSanPham, soLuong, soLuongDat) VALUES (?, ?, ?, 0)")
             ->execute([$maTK, $maSP, $soLuong]);

        if (!empty($_FILES['hinhAnh']['name'][0])) {
            $totalFiles = count($_FILES['hinhAnh']['name']);
            for ($i = 0; $i < $totalFiles; $i++) {
                if ($_FILES['hinhAnh']['error'][$i] === UPLOAD_ERR_OK) {
                    $rawName = basename($_FILES['hinhAnh']['name'][$i]);
                    $cleanFileName = preg_replace('/[^a-zA-Z0-9._-]/', '', $rawName);
                    $tenFileAnh = $maSP . '_' . ($i + 1) . '_' . $cleanFileName;
                    move_uploaded_file($_FILES['hinhAnh']['tmp_name'][$i], $uploadDir . $tenFileAnh);

                    $maAnh = taoMaTuDong($conn, 'ANHSP', 'maAnh', 'ANH');
                    $anhChinh = ($i === 0) ? 1 : 0;
                    $conn->prepare("INSERT INTO ANHSP (maAnh, maSanPham, hinhAnh, anhChinh) VALUES (?, ?, ?, ?)")
                         ->execute([$maAnh, $maSP, $tenFileAnh, $anhChinh]);
                }
            }
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

if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->beginTransaction();

        $maSP = $_POST['maSanPham'];
        $tenSP = trim($_POST['tenSanPham']);
        $gia = (float)$_POST['gia'];
        $trangThai = $_POST['trangThai'];
        $moTa = trim($_POST['moTa'] ?? '');
        $maThuongHieu = $_POST['maThuongHieu'];

        $conn->prepare("UPDATE SANPHAM SET tenSanPham=?, gia=?, trangThai=?, moTa=?, maThuongHieu=? WHERE maSanPham=?")
             ->execute([$tenSP, $gia, $trangThai, $moTa, $maThuongHieu, $maSP]);

        $conn->prepare("UPDATE CHITIETSANPHAM 
                  SET kichThuoc=?, chieuRongKhung=?, chieuRongCau=?, chieuRongThauKinh=?, 
                      chieuCaoOngKinh=?, chieuDaiThaiDuong=?, mauSac=?, gioiTinh=?, vatLieu=?, moTa=? 
                  WHERE maSanPham=?")
             ->execute([
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

        $soLuong = (int)($_POST['soLuong'] ?? 0);
        $conn->prepare("UPDATE TONKO SET soLuong = ? WHERE maSanPham = ?")->execute([$soLuong, $maSP]);

        if (!empty($_FILES['hinhAnh']['name'][0])) {
            $cheDoAnh = $_POST['cheDoAnh'] ?? 'append';

            if ($cheDoAnh === 'replace') {
                $conn->prepare("DELETE FROM ANHSP WHERE maSanPham = ?")->execute([$maSP]);
                $hasMainImage = false;
            } else {
                $checkMain = $conn->prepare("SELECT COUNT(*) FROM ANHSP WHERE maSanPham = ? AND anhChinh = 1");
                $checkMain->execute([$maSP]);
                $hasMainImage = ($checkMain->fetchColumn() > 0);
            }

            $totalFiles = count($_FILES['hinhAnh']['name']);
            for ($i = 0; $i < $totalFiles; $i++) {
                if ($_FILES['hinhAnh']['error'][$i] === UPLOAD_ERR_OK) {
                    $rawName = basename($_FILES['hinhAnh']['name'][$i]);
                    $cleanFileName = preg_replace('/[^a-zA-Z0-9._-]/', '', $rawName);
                    $tenFileAnh = $maSP . '_' . time() . '_' . ($i + 1) . '_' . $cleanFileName;
                    move_uploaded_file($_FILES['hinhAnh']['tmp_name'][$i], $uploadDir . $tenFileAnh);

                    $maAnh = taoMaTuDong($conn, 'ANHSP', 'maAnh', 'ANH');
                    $anhChinh = (!$hasMainImage && $i === 0) ? 1 : 0;
                    if ($anhChinh === 1) $hasMainImage = true;

                    $conn->prepare("INSERT INTO ANHSP (maAnh, maSanPham, hinhAnh, anhChinh) VALUES (?, ?, ?, ?)")
                         ->execute([$maAnh, $maSP, $tenFileAnh, $anhChinh]);
                }
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

if ($action === 'delete_img') {
    $maAnh = $_GET['maAnh'] ?? '';
    $maSP = $_GET['maSP'] ?? '';
    if ($maAnh && $maSP) {
        $conn->prepare("DELETE FROM ANHSP WHERE maAnh = ?")->execute([$maAnh]);
        $checkMain = $conn->prepare("SELECT COUNT(*) FROM ANHSP WHERE maSanPham = ? AND anhChinh = 1");
        $checkMain->execute([$maSP]);
        if ($checkMain->fetchColumn() == 0) {
            $conn->prepare("UPDATE ANHSP SET anhChinh = 1 WHERE maSanPham = ? LIMIT 1")->execute([$maSP]);
        }
        $_SESSION['msg'] = "Đã xóa ảnh [$maAnh] khỏi sản phẩm [$maSP].";
    }
    header("Location: ../index.php?tab=admin-product");
    exit;
}

if ($action === 'set_main_img') {
    $maAnh = $_GET['maAnh'] ?? '';
    $maSP = $_GET['maSP'] ?? '';
    if ($maAnh && $maSP) {
        $conn->prepare("UPDATE ANHSP SET anhChinh = 0 WHERE maSanPham = ?")->execute([$maSP]);
        $conn->prepare("UPDATE ANHSP SET anhChinh = 1 WHERE maAnh = ? AND maSanPham = ?")->execute([$maAnh, $maSP]);
        $_SESSION['msg'] = "Đã đổi ảnh đại diện chính cho sản phẩm [$maSP].";
    }
    header("Location: ../index.php?tab=admin-product");
    exit;
}

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
