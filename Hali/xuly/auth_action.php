<?php
require_once 'functions.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// 1. ĐĂNG NHẬP HỆ THỐNG
if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $matKhau = trim($_POST['matKhau'] ?? '');

    $sql = "SELECT nd.*, vt.tenVaiTro 
            FROM NGUOIDUNG nd 
            JOIN VAITRO vt ON nd.maVaiTro = vt.maVaiTro 
            WHERE nd.email = :email LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    // Kiểm tra mật khẩu bằng MD5 (khớp file wam.sql) hoặc Bcrypt
    if ($user && ($user['matKhau'] === md5($matKhau) || password_verify($matKhau, $user['matKhau']))) {
        $_SESSION['user'] = [
            'maNguoiDung' => $user['maNguoiDung'],
            'hoTen'       => $user['hoTen'],
            'email'       => $user['email'],
            'maVaiTro'    => $user['maVaiTro'],
            'tenVaiTro'   => $user['tenVaiTro']
        ];
        $_SESSION['msg'] = "Xin chào " . $user['hoTen'] . " (" . $user['tenVaiTro'] . ")!";
    } else {
        $_SESSION['err'] = "Thông tin đăng nhập không chính xác, vui lòng thử lại!";
    }
    header("Location: ../index.php");
    exit;
}

// 2. ĐĂNG KÝ TÀI KHOẢN KHÁCH HÀNG (Lưu đồng thời vào NGUOIDUNG và KHACHHANG)
if ($action === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $hoTen       = trim($_POST['hoTen'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $matKhau     = trim($_POST['matKhau'] ?? '');
    $soDienThoai = trim($_POST['soDienThoai'] ?? '');
    $gioiTinh    = $_POST['gioiTinh'] ?? null;
    $ngaySinh    = !empty($_POST['ngaySinh']) ? $_POST['ngaySinh'] . ' 00:00:00' : null;
    $diaChi      = trim($_POST['diaChi'] ?? '');

    // Kiểm tra định dạng cơ bản
    if (strlen($matKhau) < 3 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['err'] = "Email không hợp lệ hoặc mật khẩu quá ngắn!";
        header("Location: ../index.php");
        exit;
    }

    try {
        // Kiểm tra trùng Email trong bảng NGUOIDUNG hoặc trùng SĐT trong bảng KHACHHANG (Theo đúng quy trình đồ án)
        $stmtEmail = $conn->prepare("SELECT COUNT(*) FROM NGUOIDUNG WHERE email = ?");
        $stmtEmail->execute([$email]);

        $stmtPhone = $conn->prepare("SELECT COUNT(*) FROM KHACHHANG WHERE soDienThoai = ?");
        $stmtPhone->execute([$soDienThoai]);

        if ($stmtEmail->fetchColumn() > 0 || $stmtPhone->fetchColumn() > 0) {
            $_SESSION['err'] = "Email hoặc Số điện thoại đã được sử dụng, vui lòng kiểm tra lại!";
            header("Location: ../index.php");
            exit;
        }

        $conn->beginTransaction();

        // 2.1. Tạo bản ghi trong bảng NGUOIDUNG (Vai trò khách hàng: VT03)
        $maND = taoMaTuDong($conn, 'NGUOIDUNG', 'maNguoiDung', 'ND');
        $matKhauMaHoa = md5($matKhau); // Đồng bộ chuẩn MD5 trong wam.sql
        $sqlND = "INSERT INTO NGUOIDUNG (maNguoiDung, hoTen, email, matKhau, maVaiTro) VALUES (?, ?, ?, ?, 'VT03')";
        $conn->prepare($sqlND)->execute([$maND, $hoTen, $email, $matKhauMaHoa]);

        // 2.2. Tạo hồ sơ tương ứng trong bảng KHACHHANG
        $maKH = taoMaTuDong($conn, 'KHACHHANG', 'maKhachHang', 'KH');
        $sqlKH = "INSERT INTO KHACHHANG (maKhachHang, maNguoiDung, gioiTinh, ngaySinh, soDienThoai, diaChi) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        $conn->prepare($sqlKH)->execute([$maKH, $maND, $gioiTinh, $ngaySinh, $soDienThoai, $diaChi]);

        // 2.3. Chuyển các đơn hàng vãng lai trước đó (nếu có cùng SĐT) về maKhachHang mới
        $sqlUpdateOrders = "UPDATE DONHANG SET maKhachHang = ? WHERE soDienThoai = ? AND maKhachHang IS NULL";
        $conn->prepare($sqlUpdateOrders)->execute([$maKH, $soDienThoai]);

        $conn->commit();

        // Tự động đăng nhập ngay sau khi đăng ký thành công (Theo đúng Luồng cơ bản bước 7 Use case Đăng ký)
        $_SESSION['user'] = [
            'maNguoiDung' => $maND,
            'maKhachHang' => $maKH,
            'hoTen'       => $hoTen,
            'email'       => $email,
            'maVaiTro'    => 'VT03',
            'tenVaiTro'   => 'KhachHang'
        ];
        $_SESSION['msg'] = "Đăng ký tài khoản thành viên thành công! Chào mừng $hoTen.";
    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $_SESSION['err'] = "Lỗi đăng ký tài khoản: " . $e->getMessage();
    }
    header("Location: ../index.php");
    exit;
}

// 3. ĐĂNG XUẤT
if ($action === 'logout') {
    unset($_SESSION['user']);
    $_SESSION['msg'] = "Đã đăng xuất thành công.";
    header("Location: ../index.php");
    exit;
}
?>
