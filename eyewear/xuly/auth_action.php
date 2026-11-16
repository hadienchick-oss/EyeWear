<?php
require_once 'functions.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// 1. ĐĂNG NHẬP HỆ THỐNG
if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $matKhau = trim($_POST['matKhau'] ?? '');

    $sql = "SELECT nd.*, vt.tenVaiTro, kh.maKhachHang
            FROM NGUOIDUNG nd 
            JOIN VAITRO vt ON nd.maVaiTro = vt.maVaiTro 
            LEFT JOIN KHACHHANG kh ON nd.maNguoiDung = kh.maNguoiDung
            WHERE nd.email = :email LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if ($user && ($user['matKhau'] === md5($matKhau) || password_verify($matKhau, $user['matKhau']))) {
        $_SESSION['user'] = [
            'maNguoiDung' => $user['maNguoiDung'],
            'maKhachHang' => $user['maKhachHang'] ?? null,
            'hoTen'       => $user['hoTen'],
            'email'       => $user['email'],
            'maVaiTro'    => $user['maVaiTro'],
            'tenVaiTro'   => $user['tenVaiTro']
        ];

        // Đồng bộ giỏ hàng Session vào bảng GIOHANG & CHITIETGIOHANG nếu là Khách hàng
        if (!empty($user['maKhachHang']) && !empty($_SESSION['cart'])) {
            dongBoGioHangVaoDB($conn, $user['maKhachHang']);
        }

        $_SESSION['msg'] = "Xin chào " . $user['hoTen'] . "!";
    } else {
        $_SESSION['err'] = "Email hoặc mật khẩu không chính xác, vui lòng thử lại!";
    }
    header("Location: ../index.php");
    exit;
}

// 2. ĐĂNG KÝ TÀI KHOẢN KHÁCH HÀNG
if ($action === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $hoTen       = trim($_POST['hoTen'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $matKhau     = trim($_POST['matKhau'] ?? '');
    $soDienThoai = trim($_POST['soDienThoai'] ?? '');
    $gioiTinh    = $_POST['gioiTinh'] ?? null;
    $ngaySinh    = !empty($_POST['ngaySinh']) ? $_POST['ngaySinh'] . ' 00:00:00' : null;
    $diaChi      = trim($_POST['diaChi'] ?? '');

    if (strlen($matKhau) < 3 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['err'] = "Email không hợp lệ hoặc mật khẩu phải từ 3 ký tự trở lên!";
        header("Location: ../index.php");
        exit;
    }

    try {
        $stmtEmail = $conn->prepare("SELECT COUNT(*) FROM NGUOIDUNG WHERE email = ?");
        $stmtEmail->execute([$email]);

        $stmtPhone = $conn->prepare("SELECT COUNT(*) FROM KHACHHANG WHERE soDienThoai = ?");
        $stmtPhone->execute([$soDienThoai]);

        if ($stmtEmail->fetchColumn() > 0 || $stmtPhone->fetchColumn() > 0) {
            $_SESSION['err'] = "Email hoặc Số điện thoại đã được đăng ký, vui lòng kiểm tra lại!";
            header("Location: ../index.php");
            exit;
        }

        $conn->beginTransaction();

        $maND = taoMaTuDong($conn, 'NGUOIDUNG', 'maNguoiDung', 'ND');
        $matKhauMaHoa = md5($matKhau);
        $conn->prepare("INSERT INTO NGUOIDUNG (maNguoiDung, hoTen, email, matKhau, maVaiTro) VALUES (?, ?, ?, ?, 'VT03')")
             ->execute([$maND, $hoTen, $email, $matKhauMaHoa]);

        $maKH = taoMaTuDong($conn, 'KHACHHANG', 'maKhachHang', 'KH');
        $conn->prepare("INSERT INTO KHACHHANG (maKhachHang, maNguoiDung, gioiTinh, ngaySinh, soDienThoai, diaChi) VALUES (?, ?, ?, ?, ?, ?)")
             ->execute([$maKH, $maND, $gioiTinh, $ngaySinh, $soDienThoai, $diaChi]);

        // Gộp đơn hàng vãng lai trước đó (nếu trùng SĐT) về tài khoản thành viên mới
        $conn->prepare("UPDATE DONHANG SET maKhachHang = ? WHERE soDienThoai = ? AND maKhachHang IS NULL")
             ->execute([$maKH, $soDienThoai]);

        $conn->commit();

        $_SESSION['user'] = [
            'maNguoiDung' => $maND,
            'maKhachHang' => $maKH,
            'hoTen'       => $hoTen,
            'email'       => $email,
            'maVaiTro'    => 'VT03',
            'tenVaiTro'   => 'KhachHang'
        ];
        $_SESSION['msg'] = "Đăng ký tài khoản thành viên thành công! Chào mừng $hoTen đến với Việt MAC.";
    } catch (Exception $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        $_SESSION['err'] = "Lỗi đăng ký tài khoản: " . $e->getMessage();
    }
    header("Location: ../index.php");
    exit;
}

// 3. CẬP NHẬT THÔNG TIN CÁ NHÂN (Bấm vào tên trên thanh Menu -> Sửa thông tin)
if ($action === 'update_profile' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isLogged()) {
        $_SESSION['err'] = "Vui lòng đăng nhập để thực hiện thao tác này!";
        header("Location: ../index.php");
        exit;
    }

    $maND = $_SESSION['user']['maNguoiDung'];
    $hoTen = trim($_POST['hoTen'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $matKhauMoi = trim($_POST['matKhauMoi'] ?? '');

    try {
        // Kiểm tra trùng Email với người dùng khác
        $checkEmail = $conn->prepare("SELECT COUNT(*) FROM NGUOIDUNG WHERE email = ? AND maNguoiDung != ?");
        $checkEmail->execute([$email, $maND]);
        if ($checkEmail->fetchColumn() > 0) {
            $_SESSION['err'] = "Email [$email] đã được tài khoản khác sử dụng!";
            header("Location: ../index.php");
            exit;
        }

        $conn->beginTransaction();

        if ($matKhauMoi !== '') {
            $conn->prepare("UPDATE NGUOIDUNG SET hoTen = ?, email = ?, matKhau = ? WHERE maNguoiDung = ?")
                 ->execute([$hoTen, $email, md5($matKhauMoi), $maND]);
        } else {
            $conn->prepare("UPDATE NGUOIDUNG SET hoTen = ?, email = ? WHERE maNguoiDung = ?")
                 ->execute([$hoTen, $email, $maND]);
        }

        // Nếu có hồ sơ trong bảng KHACHHANG thì cập nhật luôn thông tin giao hàng mặc định
        $maKH = layMaKhachHangHienTai($conn);
        if ($maKH) {
            $soDienThoai = trim($_POST['soDienThoai'] ?? '');
            $gioiTinh = $_POST['gioiTinh'] ?? 'Nam';
            $ngaySinh = !empty($_POST['ngaySinh']) ? $_POST['ngaySinh'] . ' 00:00:00' : null;
            $diaChi = trim($_POST['diaChi'] ?? '');

            $conn->prepare("UPDATE KHACHHANG SET gioiTinh = ?, ngaySinh = ?, soDienThoai = ?, diaChi = ? WHERE maKhachHang = ?")
                 ->execute([$gioiTinh, $ngaySinh, $soDienThoai, $diaChi, $maKH]);
        }

        $conn->commit();

        $_SESSION['user']['hoTen'] = $hoTen;
        $_SESSION['user']['email'] = $email;
        $_SESSION['msg'] = "Cập nhật thông tin tài khoản thành công!";
    } catch (Exception $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        $_SESSION['err'] = "Lỗi cập nhật thông tin: " . $e->getMessage();
    }
    header("Location: ../index.php");
    exit;
}

// 4. ĐĂNG XUẤT
if ($action === 'logout') {
    unset($_SESSION['user']);
    $_SESSION['msg'] = "Đã đăng xuất thành công.";
    header("Location: ../index.php");
    exit;
}

/**
 * Hàm phụ trợ đồng bộ $_SESSION['cart'] vào bảng GIOHANG & CHITIETGIOHANG
 */
function dongBoGioHangVaoDB($conn, $maKhachHang) {
    $stmtGH = $conn->prepare("SELECT maGioHang FROM GIOHANG WHERE maKhachHang = ? LIMIT 1");
    $stmtGH->execute([$maKhachHang]);
    $maGH = $stmtGH->fetchColumn();

    if (!$maGH) {
        $maGH = taoMaTuDong($conn, 'GIOHANG', 'maGioHang', 'GH');
        $conn->prepare("INSERT INTO GIOHANG (maGioHang, maKhachHang) VALUES (?, ?)")->execute([$maGH, $maKhachHang]);
    }

    $conn->prepare("DELETE FROM CHITIETGIOHANG WHERE maGioHang = ?")->execute([$maGH]);

    // Gom số lượng theo maSanPham để lưu vào CHITIETGIOHANG
    $tongHopSP = [];
    foreach ($_SESSION['cart'] as $item) {
        $msp = $item['maSanPham'];
        $tongHopSP[$msp] = ($tongHopSP[$msp] ?? 0) + (int)$item['soLuong'];
    }

    foreach ($tongHopSP as $msp => $sl) {
        $maCTGH = taoMaTuDong($conn, 'CHITIETGIOHANG', 'maCTGioHang', 'CTGH');
        $conn->prepare("INSERT INTO CHITIETGIOHANG (maCTGioHang, maGioHang, maSanPham, soLuong) VALUES (?, ?, ?, ?)")
             ->execute([$maCTGH, $maGH, $msp, $sl]);
    }
}
?>
