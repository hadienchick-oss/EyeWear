<?php
require_once 'functions.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// =========================================================================
// A. KHÁCH HÀNG LƯU VOUCHER VÀO VÍ CÁ NHÂN (SESSION + LOCALSTORAGE)
// =========================================================================
if ($action === 'save_voucher') {
    $code = strtoupper(trim($_POST['code'] ?? $_GET['code'] ?? ''));
    if ($code !== '') {
        if (!isset($_SESSION['saved_vouchers'])) {
            $_SESSION['saved_vouchers'] = [];
        }
        if (!in_array($code, $_SESSION['saved_vouchers'])) {
            $_SESSION['saved_vouchers'][] = $code;
            $_SESSION['msg'] = "Đã lưu mã Voucher [$code] vào ví ưu đãi của bạn!";
        } else {
            $_SESSION['msg'] = "Mã Voucher [$code] đã có sẵn trong ví của bạn.";
        }
    }
    header("Location: ../index.php?tab=voucher-wallet");
    exit;
}

if ($action === 'remove_saved_voucher') {
    $code = strtoupper(trim($_GET['code'] ?? ''));
    if (!empty($_SESSION['saved_vouchers'])) {
        $_SESSION['saved_vouchers'] = array_values(array_diff($_SESSION['saved_vouchers'], [$code]));
        $_SESSION['msg'] = "Đã gỡ mã [$code] khỏi danh sách đã lưu.";
    }
    header("Location: ../index.php?tab=voucher-wallet");
    exit;
}

// =========================================================================
// B. API KIỂM TRA & ÁP DỤNG MÃ VOUCHER (AJAX JSON)
// =========================================================================
if ($action === 'check_voucher') {
    header('Content-Type: application/json; charset=utf-8');
    $code = strtoupper(trim($_POST['code'] ?? $_GET['code'] ?? ''));
    $tongTamTinh = (float)($_POST['tongTamTinh'] ?? $_GET['tongTamTinh'] ?? 0);

    if ($code === '') {
        echo json_encode(['success' => false, 'message' => 'Vui lòng chọn hoặc nhập mã Voucher!']);
        exit;
    }

    $stmt = $conn->prepare("SELECT * FROM VOUCHER WHERE code = ? LIMIT 1");
    $stmt->execute([$code]);
    $vc = $stmt->fetch();

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
        echo json_encode(['success' => false, 'message' => 'Mã voucher đã hết lượt sử dụng!']);
        exit;
    }

    $donToiThieu = (float)$vc['donHangToiThieu'];
    if ($tongTamTinh < $donToiThieu) {
        echo json_encode([
            'success' => false,
            'message' => 'Đơn hàng chưa đạt giá trị tối thiểu ' . number_format($donToiThieu, 0, ',', '.') . ' đ để dùng mã này!'
        ]);
        exit;
    }

    $giaTriGiam = (float)$vc['giaTriGiam'];
    if ($vc['loaiGiam'] === 'Phần trăm' || $vc['loaiGiam'] === '%') {
        $soTienGiam = round($tongTamTinh * ($giaTriGiam / 100));
    } else {
        $soTienGiam = $giaTriGiam;
    }

    if ($soTienGiam > $tongTamTinh) $soTienGiam = $tongTamTinh;
    $tongSauGiam = $tongTamTinh - $soTienGiam;

    echo json_encode([
        'success'     => true,
        'message'     => 'Áp dụng mã [' . $vc['code'] . '] thành công (-' . number_format($soTienGiam, 0, ',', '.') . ' đ)',
        'maVoucher'   => $vc['maVoucher'],
        'code'        => $vc['code'],
        'soTienGiam'  => $soTienGiam,
        'tongSauGiam' => $tongSauGiam
    ]);
    exit;
}

// =========================================================================
// C. QUẢN TRỊ VOUCHER (CHỈ ADMIN VT01)
// =========================================================================
if (!isAdmin()) {
    $_SESSION['err'] = "Chỉ Quản trị viên (Admin) mới có quyền thêm, sửa hoặc xóa Voucher!";
    header("Location: ../index.php");
    exit;
}

if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $loaiGiam = $_POST['loaiGiam'] ?? 'Tiền mặt';
    $giaTriGiam = (float)($_POST['giaTriGiam'] ?? 0);
    $donHangToiThieu = (float)($_POST['donHangToiThieu'] ?? 0);
    $gioiHanDung = ($_POST['gioiHanDung'] !== '' && $_POST['gioiHanDung'] !== null) ? (int)$_POST['gioiHanDung'] : null;
    $ngayHetHanInput = $_POST['ngayHetHan'] ?? '';

    try {
        $ngayHetHanObj = new DateTime($ngayHetHanInput);
        if ($ngayHetHanObj <= new DateTime()) {
            $_SESSION['err'] = "Thời hạn hiệu lực của voucher phải lớn hơn thời điểm hiện tại!";
            header("Location: ../index.php?tab=admin-voucher");
            exit;
        }

        if (($loaiGiam === 'Phần trăm' || $loaiGiam === '%') && ($giaTriGiam <= 0 || $giaTriGiam > 100)) {
            $_SESSION['err'] = "Giá trị giảm theo phần trăm phải nằm trong khoảng từ 1% đến 100%!";
            header("Location: ../index.php?tab=admin-voucher");
            exit;
        }

        $checkCode = $conn->prepare("SELECT COUNT(*) FROM VOUCHER WHERE code = ?");
        $checkCode->execute([$code]);
        if ($checkCode->fetchColumn() > 0) {
            $_SESSION['err'] = "Mã code [$code] đã tồn tại trong CSDL!";
            header("Location: ../index.php?tab=admin-voucher");
            exit;
        }

        $maVoucher = taoMaTuDong($conn, 'VOUCHER', 'maVoucher', 'VC');
        $conn->prepare("INSERT INTO VOUCHER (maVoucher, code, loaiGiam, giaTriGiam, donHangToiThieu, gioiHanDung, daDung, ngayHetHan) VALUES (?, ?, ?, ?, ?, ?, 0, ?)")
             ->execute([$maVoucher, $code, $loaiGiam, $giaTriGiam, $donHangToiThieu, $gioiHanDung, $ngayHetHanObj->format('Y-m-d H:i:s')]);

        $_SESSION['msg'] = "Phát hành mã Voucher [$code] thành công!";
    } catch (Exception $e) {
        $_SESSION['err'] = "Lỗi tạo Voucher: " . $e->getMessage();
    }
    header("Location: ../index.php?tab=admin-voucher");
    exit;
}

if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $maVoucher = $_POST['maVoucher'] ?? '';
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $loaiGiam = $_POST['loaiGiam'] ?? 'Tiền mặt';
    $giaTriGiam = (float)($_POST['giaTriGiam'] ?? 0);
    $donHangToiThieu = (float)($_POST['donHangToiThieu'] ?? 0);
    $gioiHanDung = ($_POST['gioiHanDung'] !== '' && $_POST['gioiHanDung'] !== null) ? (int)$_POST['gioiHanDung'] : null;
    $ngayHetHanInput = $_POST['ngayHetHan'] ?? '';

    try {
        $checkCode = $conn->prepare("SELECT COUNT(*) FROM VOUCHER WHERE code = ? AND maVoucher != ?");
        $checkCode->execute([$code, $maVoucher]);
        if ($checkCode->fetchColumn() > 0) {
            $_SESSION['err'] = "Mã code [$code] đã trùng với Voucher khác!";
            header("Location: ../index.php?tab=admin-voucher");
            exit;
        }

        $ngayHetHanObj = new DateTime($ngayHetHanInput);
        $conn->prepare("UPDATE VOUCHER SET code = ?, loaiGiam = ?, giaTriGiam = ?, donHangToiThieu = ?, gioiHanDung = ?, ngayHetHan = ? WHERE maVoucher = ?")
             ->execute([$code, $loaiGiam, $giaTriGiam, $donHangToiThieu, $gioiHanDung, $ngayHetHanObj->format('Y-m-d H:i:s'), $maVoucher]);

        $_SESSION['msg'] = "Cập nhật Voucher [$code] thành công!";
    } catch (Exception $e) {
        $_SESSION['err'] = "Lỗi cập nhật Voucher: " . $e->getMessage();
    }
    header("Location: ../index.php?tab=admin-voucher");
    exit;
}

if ($action === 'disable') {
    $maVoucher = $_GET['id'] ?? '';
    $conn->prepare("UPDATE VOUCHER SET ngayHetHan = DATE_SUB(NOW(), INTERVAL 1 SECOND), gioiHanDung = daDung WHERE maVoucher = ?")->execute([$maVoucher]);
    $_SESSION['msg'] = "Đã vô hiệu hóa sớm Voucher [$maVoucher].";
    header("Location: ../index.php?tab=admin-voucher");
    exit;
}

if ($action === 'delete') {
    $maVoucher = $_GET['id'] ?? '';
    $checkDH = $conn->prepare("SELECT COUNT(*) FROM DONHANG WHERE maVoucher = ?");
    $checkDH->execute([$maVoucher]);
    $checkLS = $conn->prepare("SELECT COUNT(*) FROM LICHSUVOUCHER WHERE maVoucher = ?");
    $checkLS->execute([$maVoucher]);

    if ($checkDH->fetchColumn() > 0 || $checkLS->fetchColumn() > 0) {
        $conn->prepare("UPDATE VOUCHER SET ngayHetHan = DATE_SUB(NOW(), INTERVAL 1 SECOND), gioiHanDung = daDung WHERE maVoucher = ?")->execute([$maVoucher]);
        $_SESSION['msg'] = "Voucher [$maVoucher] đã có lịch sử giao dịch nên được chuyển sang 'Ngừng hiệu lực'.";
    } else {
        $conn->prepare("DELETE FROM VOUCHER WHERE maVoucher = ?")->execute([$maVoucher]);
        $_SESSION['msg'] = "Đã xóa vĩnh viễn Voucher [$maVoucher].";
    }
    header("Location: ../index.php?tab=admin-voucher");
    exit;
}
?>
