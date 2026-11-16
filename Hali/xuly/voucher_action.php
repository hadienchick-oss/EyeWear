<?php
require_once 'functions.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// =========================================================================
// A. API KIỂM TRA & ÁP DỤNG MÃ VOUCHER CHO KHÁCH HÀNG / NHÂN VIÊN (AJAX JSON)
// Bám sát Bảng 3.24: Use case "Áp dụng mã Voucher khuyến mãi"
// =========================================================================
if ($action === 'check_voucher') {
    header('Content-Type: application/json; charset=utf-8');
    $code = strtoupper(trim($_POST['code'] ?? $_GET['code'] ?? ''));
    $tongTamTinh = (float)($_POST['tongTamTinh'] ?? $_GET['tongTamTinh'] ?? 0);

    if ($code === '') {
        echo json_encode(['success' => false, 'message' => 'Vui lòng nhập mã giảm giá Voucher!']);
        exit;
    }

    $stmt = $conn->prepare("SELECT * FROM VOUCHER WHERE code = ? LIMIT 1");
    $stmt->execute([$code]);
    $vc = $stmt->fetch();

    // Ngoại lệ E1: Mã không tồn tại, hết hạn hoặc hết lượt dùng
    if (!$vc) {
        echo json_encode(['success' => false, 'message' => 'Mã voucher không tồn tại, vui lòng kiểm tra lại!']);
        exit;
    }

    $now = new DateTime();
    $hetHan = new DateTime($vc['ngayHetHan']);
    if ($hetHan <= $now) {
        echo json_encode(['success' => false, 'message' => 'Mã voucher đã hết hạn sử dụng!']);
        exit;
    }

    if ($vc['gioiHanDung'] !== null && (int)$vc['daDung'] >= (int)$vc['gioiHanDung']) {
        echo json_encode(['success' => false, 'message' => 'Mã voucher không hợp lệ hoặc đã hết lượt sử dụng!']);
        exit;
    }

    // Ngoại lệ E2: Đơn hàng chưa đạt mức chi tiêu tối thiểu
    $donToiThieu = (float)$vc['donHangToiThieu'];
    if ($tongTamTinh < $donToiThieu) {
        echo json_encode([
            'success' => false,
            'message' => 'Đơn hàng chưa đạt giá trị tối thiểu ' . number_format($donToiThieu, 0, ',', '.') . ' đ để sử dụng mã này!'
        ]);
        exit;
    }

    // Luồng cơ bản bước 4: Tính số tiền giảm (Theo số tiền cố định hoặc Theo phần trăm)
    $giaTriGiam = (float)$vc['giaTriGiam'];
    if ($vc['loaiGiam'] === 'Phần trăm' || $vc['loaiGiam'] === '%') {
        $soTienGiam = round($tongTamTinh * ($giaTriGiam / 100));
    } else {
        $soTienGiam = $giaTriGiam;
    }

    if ($soTienGiam > $tongTamTinh) {
        $soTienGiam = $tongTamTinh;
    }

    $tongSauGiam = $tongTamTinh - $soTienGiam;

    echo json_encode([
        'success'     => true,
        'message'     => 'Áp dụng mã giảm giá [' . $vc['code'] . '] thành công!',
        'maVoucher'   => $vc['maVoucher'],
        'code'        => $vc['code'],
        'loaiGiam'    => $vc['loaiGiam'],
        'giaTriGiam'  => $giaTriGiam,
        'soTienGiam'  => $soTienGiam,
        'tongSauGiam' => $tongSauGiam
    ]);
    exit;
}

// =========================================================================
// B. CÁC THAO TÁC QUẢN TRỊ VOUCHER (CHỈ DÀNH CHO ADMIN VT01)
// Bám sát Bảng 1-1 & Bảng 3.33: Nhân viên chỉ được áp dụng, không được tự tạo/xóa Voucher
// =========================================================================
if (!isAdmin()) {
    $_SESSION['err'] = "Chỉ Quản trị viên (Admin) mới có quyền phát hành, chỉnh sửa hoặc xóa Voucher!";
    header("Location: ../index.php");
    exit;
}

// 1. THÊM VOUCHER MỚI
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $loaiGiam = $_POST['loaiGiam'] ?? 'Tiền mặt';
    $giaTriGiam = (float)($_POST['giaTriGiam'] ?? 0);
    $donHangToiThieu = (float)($_POST['donHangToiThieu'] ?? 0);
    $gioiHanDung = ($_POST['gioiHanDung'] !== '' && $_POST['gioiHanDung'] !== null) ? (int)$_POST['gioiHanDung'] : null;
    $ngayHetHanInput = $_POST['ngayHetHan'] ?? '';

    try {
        // Ngoại lệ E1 (Bảng 3.33): Ngày hết hạn nhỏ hơn hoặc bằng thời điểm hiện tại
        $ngayHetHanObj = new DateTime($ngayHetHanInput);
        $now = new DateTime();
        if ($ngayHetHanObj <= $now) {
            $_SESSION['err'] = "Thời hạn hiệu lực của voucher không hợp lệ (Ngày hết hạn phải lớn hơn thời điểm hiện tại)!";
            header("Location: ../index.php?tab=admin-voucher");
            exit;
        }

        // Kiểm tra giá trị phần trăm hợp lệ
        if (($loaiGiam === 'Phần trăm' || $loaiGiam === '%') && ($giaTriGiam <= 0 || $giaTriGiam > 100)) {
            $_SESSION['err'] = "Giá trị giảm theo phần trăm phải nằm trong khoảng từ 1% đến 100%!";
            header("Location: ../index.php?tab=admin-voucher");
            exit;
        }

        // Ngoại lệ E2 (Bảng 3.33): Kiểm tra trùng mã code trong CSDL
        $checkCode = $conn->prepare("SELECT COUNT(*) FROM VOUCHER WHERE code = ?");
        $checkCode->execute([$code]);
        if ($checkCode->fetchColumn() > 0) {
            $_SESSION['err'] = "Mã code [$code] đã tồn tại trong CSDL, vui lòng đổi chuỗi mã khác!";
            header("Location: ../index.php?tab=admin-voucher");
            exit;
        }

        $maVoucher = taoMaTuDong($conn, 'VOUCHER', 'maVoucher', 'VC');
        $ngayHetHanFormat = $ngayHetHanObj->format('Y-m-d H:i:s');

        $sql = "INSERT INTO VOUCHER (maVoucher, code, loaiGiam, giaTriGiam, donHangToiThieu, gioiHanDung, daDung, ngayHetHan) 
                VALUES (?, ?, ?, ?, ?, ?, 0, ?)";
        $conn->prepare($sql)->execute([
            $maVoucher,
            $code,
            $loaiGiam,
            $giaTriGiam,
            $donHangToiThieu,
            $gioiHanDung,
            $ngayHetHanFormat
        ]);

        $_SESSION['msg'] = "Phát hành mã Voucher [$code] (Mã: $maVoucher) thành công!";
    } catch (Exception $e) {
        $_SESSION['err'] = "Lỗi tạo Voucher: " . $e->getMessage();
    }
    header("Location: ../index.php?tab=admin-voucher");
    exit;
}

// 2. SỬA THÔNG TIN VOUCHER
if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $maVoucher = $_POST['maVoucher'] ?? '';
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $loaiGiam = $_POST['loaiGiam'] ?? 'Tiền mặt';
    $giaTriGiam = (float)($_POST['giaTriGiam'] ?? 0);
    $donHangToiThieu = (float)($_POST['donHangToiThieu'] ?? 0);
    $gioiHanDung = ($_POST['gioiHanDung'] !== '' && $_POST['gioiHanDung'] !== null) ? (int)$_POST['gioiHanDung'] : null;
    $ngayHetHanInput = $_POST['ngayHetHan'] ?? '';

    try {
        // Kiểm tra trùng mã code với voucher khác
        $checkCode = $conn->prepare("SELECT COUNT(*) FROM VOUCHER WHERE code = ? AND maVoucher != ?");
        $checkCode->execute([$code, $maVoucher]);
        if ($checkCode->fetchColumn() > 0) {
            $_SESSION['err'] = "Mã code [$code] đã trùng với một Voucher khác trong CSDL!";
            header("Location: ../index.php?tab=admin-voucher");
            exit;
        }

        if (($loaiGiam === 'Phần trăm' || $loaiGiam === '%') && ($giaTriGiam <= 0 || $giaTriGiam > 100)) {
            $_SESSION['err'] = "Giá trị giảm theo phần trăm phải từ 1% đến 100%!";
            header("Location: ../index.php?tab=admin-voucher");
            exit;
        }

        $ngayHetHanObj = new DateTime($ngayHetHanInput);
        $ngayHetHanFormat = $ngayHetHanObj->format('Y-m-d H:i:s');

        $sql = "UPDATE VOUCHER 
                SET code = ?, loaiGiam = ?, giaTriGiam = ?, donHangToiThieu = ?, gioiHanDung = ?, ngayHetHan = ? 
                WHERE maVoucher = ?";
        $conn->prepare($sql)->execute([
            $code,
            $loaiGiam,
            $giaTriGiam,
            $donHangToiThieu,
            $gioiHanDung,
            $ngayHetHanFormat,
            $maVoucher
        ]);

        $_SESSION['msg'] = "Cập nhật Voucher [$code] thành công!";
    } catch (Exception $e) {
        $_SESSION['err'] = "Lỗi cập nhật Voucher: " . $e->getMessage();
    }
    header("Location: ../index.php?tab=admin-voucher");
    exit;
}

// 3. VÔ HIỆU HÓA SỚM / XÓA VOUCHER (Theo đúng Quy trình 2.2.10 & Bảng 3.33)
if ($action === 'disable') {
    $maVoucher = $_GET['id'] ?? '';
    try {
        // Vô hiệu hóa chiến dịch kết thúc sớm bằng cách đặt ngayHetHan về thời điểm quá khứ và gioiHanDung = daDung
        $sql = "UPDATE VOUCHER SET ngayHetHan = DATE_SUB(NOW(), INTERVAL 1 SECOND), gioiHanDung = daDung WHERE maVoucher = ?";
        $conn->prepare($sql)->execute([$maVoucher]);
        $_SESSION['msg'] = "Đã vô hiệu hóa sớm Voucher [$maVoucher] để ngừng áp dụng mới.";
    } catch (PDOException $e) {
        $_SESSION['err'] = "Lỗi vô hiệu hóa Voucher: " . $e->getMessage();
    }
    header("Location: ../index.php?tab=admin-voucher");
    exit;
}

if ($action === 'delete') {
    $maVoucher = $_GET['id'] ?? '';
    try {
        // Kiểm tra xem voucher đã phát sinh trong DONHANG hoặc LICHSUVOUCHER chưa
        $checkDH = $conn->prepare("SELECT COUNT(*) FROM DONHANG WHERE maVoucher = ?");
        $checkDH->execute([$maVoucher]);
        $usedInOrder = (int)$checkDH->fetchColumn();

        $checkLS = $conn->prepare("SELECT COUNT(*) FROM LICHSUVOUCHER WHERE maVoucher = ?");
        $checkLS->execute([$maVoucher]);
        $usedInHistory = (int)$checkLS->fetchColumn();

        if ($usedInOrder > 0 || $usedInHistory > 0) {
            // Nếu đã có giao dịch liên quan: Vô hiệu hóa (Xóa mềm) để bảo toàn lịch sử chống gian lận
            $sql = "UPDATE VOUCHER SET ngayHetHan = DATE_SUB(NOW(), INTERVAL 1 SECOND), gioiHanDung = daDung WHERE maVoucher = ?";
            $conn->prepare($sql)->execute([$maVoucher]);
            $_SESSION['msg'] = "Voucher [$maVoucher] đã có lịch sử sử dụng nên được chuyển sang trạng thái 'Ngừng hiệu lực' (Bảo toàn lịch sử).";
        } else {
            // Hard-delete khi chưa liên quan đến bất kỳ giao dịch nào
            $conn->prepare("DELETE FROM VOUCHER WHERE maVoucher = ?")->execute([$maVoucher]);
            $_SESSION['msg'] = "Đã xóa vĩnh viễn Voucher [$maVoucher] khỏi hệ thống.";
        }
    } catch (PDOException $e) {
        $_SESSION['err'] = "Lỗi xóa Voucher: " . $e->getMessage();
    }
    header("Location: ../index.php?tab=admin-voucher");
    exit;
}
?>
