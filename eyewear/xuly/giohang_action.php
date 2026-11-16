<?php
require_once 'functions.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

function syncDbCartIfLogged($conn) {
    $maKH = layMaKhachHangHienTai($conn);
    if (!$maKH) return;

    $stmtGH = $conn->prepare("SELECT maGioHang FROM GIOHANG WHERE maKhachHang = ? LIMIT 1");
    $stmtGH->execute([$maKH]);
    $maGH = $stmtGH->fetchColumn();

    if (!$maGH) {
        $maGH = taoMaTuDong($conn, 'GIOHANG', 'maGioHang', 'GH');
        $conn->prepare("INSERT INTO GIOHANG (maGioHang, maKhachHang) VALUES (?, ?)")->execute([$maGH, $maKH]);
    }

    $conn->prepare("DELETE FROM CHITIETGIOHANG WHERE maGioHang = ?")->execute([$maGH]);

    $grouped = [];
    foreach ($_SESSION['cart'] as $item) {
        $msp = $item['maSanPham'];
        $grouped[$msp] = ($grouped[$msp] ?? 0) + (int)$item['soLuong'];
    }

    foreach ($grouped as $msp => $qty) {
        $maCT = taoMaTuDong($conn, 'CHITIETGIOHANG', 'maCTGioHang', 'CTGH');
        $conn->prepare("INSERT INTO CHITIETGIOHANG (maCTGioHang, maGioHang, maSanPham, soLuong) VALUES (?, ?, ?, ?)")
             ->execute([$maCT, $maGH, $msp, $qty]);
    }
}

// 1. THÊM SẢN PHẨM VÀO GIỎ HÀNG (Gọng kính đơn lẻ hoặc Gọng + Tròng kính + Độ mắt)
if ($action === 'add_to_cart' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $maSP = $_POST['maSanPham'] ?? '';
    $maTrongKinh = trim($_POST['maTrongKinh'] ?? '');
    $soLuong = max(1, (int)($_POST['soLuong'] ?? 1));
    $ghiChuDoMat = trim($_POST['ghiChuDoMat'] ?? '');

    // Truy vấn thông tin sản phẩm & kiểm tra tồn khả dụng
    $stmtSP = $conn->prepare("SELECT sp.*, th.tenThuongHieu,
                                     (SELECT hinhAnh FROM ANHSP a WHERE a.maSanPham = sp.maSanPham ORDER BY a.anhChinh DESC, a.maAnh ASC LIMIT 1) AS hinhAnh,
                                     COALESCE(tk.soLuong, 0) AS soLuongTon,
                                     COALESCE(tk.soLuongDat, 0) AS soLuongDat
                              FROM SANPHAM sp
                              LEFT JOIN THUONGHIEU th ON sp.maThuongHieu = th.maThuongHieu
                              LEFT JOIN TONKO tk ON sp.maSanPham = tk.maSanPham
                              WHERE sp.maSanPham = ? LIMIT 1");
    $stmtSP->execute([$maSP]);
    $sp = $stmtSP->fetch();

    if (!$sp || $sp['trangThai'] !== 'Đang bán') {
        $_SESSION['err'] = "Sản phẩm này hiện không khả dụng để đặt mua!";
        header("Location: ../index.php");
        exit;
    }

    $tonKhaDung = (int)$sp['soLuongTon'] - (int)$sp['soLuongDat'];
    if ($tonKhaDung < $soLuong) {
        $_SESSION['err'] = "Rất tiếc, số lượng sản phẩm trong kho chỉ còn $tonKhaDung chiếc!";
        header("Location: ../index.php");
        exit;
    }

    $tenTrong = 'Chỉ lấy gọng kính (Không tròng)';
    $giaTrong = 0.0;
    if ($maTrongKinh !== '') {
        $stmtTK = $conn->prepare("SELECT * FROM TRONGKINH WHERE maTrongKinh = ? LIMIT 1");
        $stmtTK->execute([$maTrongKinh]);
        $tk = $stmtTK->fetch();
        if ($tk) {
            $tenTrong = $tk['loai'] . ' (Chiết suất ' . $tk['chietSuat'] . ' - ' . $tk['tinhNang'] . ')';
            $giaTrong = (float)$tk['gia'];
        } else {
            $maTrongKinh = '';
        }
    }

    // Lưu độ mắt vào bảng KETQUADOMAT nếu khách đã đăng nhập và có nhập độ mắt
    $maKH = layMaKhachHangHienTai($conn);
    if ($maKH && $ghiChuDoMat !== '' && $ghiChuDoMat !== 'Kính không độ') {
        $maKQ = taoMaTuDong($conn, 'KETQUADOMAT', 'maKetQuaDo', 'KQ');
        $conn->prepare("INSERT INTO KETQUADOMAT (maKetQuaDo, maKhachHang, ngayDo, ketQua) VALUES (?, ?, NOW(), ?)")
             ->execute([$maKQ, $maKH, $ghiChuDoMat]);
    }

    $cartKey = $maSP . '_' . ($maTrongKinh ?: 'FRAME') . '_' . md5($ghiChuDoMat);
    if (isset($_SESSION['cart'][$cartKey])) {
        $newQty = $_SESSION['cart'][$cartKey]['soLuong'] + $soLuong;
        if ($newQty > $tonKhaDung) {
            $_SESSION['err'] = "Số lượng trong giỏ vượt quá tồn kho khả dụng ($tonKhaDung chiếc)!";
            header("Location: ../index.php?tab=cart-view");
            exit;
        }
        $_SESSION['cart'][$cartKey]['soLuong'] = $newQty;
    } else {
        $_SESSION['cart'][$cartKey] = [
            'cartKey'     => $cartKey,
            'maSanPham'   => $maSP,
            'tenSanPham'  => $sp['tenSanPham'],
            'tenThuongHieu'=> $sp['tenThuongHieu'] ?? 'Việt MAC',
            'hinhAnh'     => $sp['hinhAnh'] ?? '',
            'giaGong'     => (float)$sp['gia'],
            'maTrongKinh' => $maTrongKinh ?: null,
            'tenTrong'    => $tenTrong,
            'giaTrong'    => $giaTrong,
            'ghiChuDoMat' => $ghiChuDoMat,
            'soLuong'     => $soLuong
        ];
    }

    syncDbCartIfLogged($conn);
    $_SESSION['msg'] = "Đã thêm [" . $sp['tenSanPham'] . "] vào giỏ hàng!";
    header("Location: ../index.php?tab=cart-view");
    exit;
}

// 2. CẬP NHẬT SỐ LƯỢNG TRONG GIỎ HÀNG
if ($action === 'update_qty' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $cartKey = $_POST['cartKey'] ?? '';
    $soLuong = (int)($_POST['soLuong'] ?? 1);

    if (isset($_SESSION['cart'][$cartKey])) {
        if ($soLuong <= 0) {
            unset($_SESSION['cart'][$cartKey]);
        } else {
            $maSP = $_SESSION['cart'][$cartKey]['maSanPham'];
            $stmtTon = $conn->prepare("SELECT COALESCE(soLuong - soLuongDat, 0) FROM TONKO WHERE maSanPham = ?");
            $stmtTon->execute([$maSP]);
            $maxAvail = (int)$stmtTon->fetchColumn();

            if ($soLuong > $maxAvail) {
                $_SESSION['err'] = "Số lượng tối đa có thể đặt cho mẫu kính này là $maxAvail chiếc!";
            } else {
                $_SESSION['cart'][$cartKey]['soLuong'] = $soLuong;
                $_SESSION['msg'] = "Đã cập nhật số lượng giỏ hàng.";
            }
        }
        syncDbCartIfLogged($conn);
    }
    header("Location: ../index.php?tab=cart-view");
    exit;
}

// 3. XÓA MÓN KHỎI GIỎ HÀNG
if ($action === 'remove_item') {
    $cartKey = $_GET['key'] ?? '';
    if (isset($_SESSION['cart'][$cartKey])) {
        unset($_SESSION['cart'][$cartKey]);
        syncDbCartIfLogged($conn);
        $_SESSION['msg'] = "Đã xóa sản phẩm khỏi giỏ hàng.";
    }
    header("Location: ../index.php?tab=cart-view");
    exit;
}

// 4. KHÓA TẠM TỒN KHO 15 PHÚT KHI MỞ POPUP THANH TOÁN (AJAX)
if ($action === 'lock_stock') {
    header('Content-Type: application/json; charset=utf-8');
    if (empty($_SESSION['cart'])) {
        echo json_encode(['success' => false, 'message' => 'Giỏ hàng trống!']);
        exit;
    }

    if (!empty($_SESSION['stock_locked'])) {
        echo json_encode(['success' => true, 'message' => 'Đã giữ chỗ kho trước đó.']);
        exit;
    }

    try {
        $conn->beginTransaction();
        foreach ($_SESSION['cart'] as $item) {
            $stmt = $conn->prepare("SELECT soLuong, soLuongDat FROM TONKO WHERE maSanPham = ? FOR UPDATE");
            $stmt->execute([$item['maSanPham']]);
            $tk = $stmt->fetch();
            if (!$tk || ($tk['soLuong'] - $tk['soLuongDat']) < $item['soLuong']) {
                $conn->rollBack();
                echo json_encode(['success' => false, 'message' => 'Sản phẩm [' . $item['tenSanPham'] . '] vừa hết hàng khả dụng!']);
                exit;
            }
            $conn->prepare("UPDATE TONKO SET soLuongDat = soLuongDat + ? WHERE maSanPham = ?")
                 ->execute([(int)$item['soLuong'], $item['maSanPham']]);
        }
        $conn->commit();
        $_SESSION['stock_locked'] = true;
        echo json_encode(['success' => true, 'message' => 'Đã tạm khóa tồn kho trong 15 phút.']);
    } catch (Exception $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// 5. NHẢ TỒN KHO KHI HỦY THANH TOÁN HOẶC HẾT 15 PHÚT (AJAX)
if ($action === 'release_stock') {
    header('Content-Type: application/json; charset=utf-8');
    if (!empty($_SESSION['stock_locked']) && !empty($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $conn->prepare("UPDATE TONKO SET soLuongDat = GREATEST(0, soLuongDat - ?) WHERE maSanPham = ?")
                 ->execute([(int)$item['soLuong'], $item['maSanPham']]);
        }
        unset($_SESSION['stock_locked']);
    }
    echo json_encode(['success' => true]);
    exit;
}
?>
