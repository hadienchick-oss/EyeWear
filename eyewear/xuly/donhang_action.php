<?php
require_once 'functions.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

/**
 * Hàm phụ trợ: kiểm tra một trạng thái đã trừ tồn kho vật lý (soLuong) hay chưa
 */
function daTruTonVatLy($trangThai) {
    return in_array($trangThai, [
        'Đã xác nhận (COD)',
        'Đã thanh toán',
        'Đang xử lý',
        'Đang mài lắp',
        'Đang giao hàng',
        'Đã giao'
    ]);
}

// =========================================================================
// 1. ĐẶT HÀNG & THANH TOÁN TỪ POPUP KHÁCH HÀNG (COD DUYỆT LUÔN + QR CHỜ DUYỆT)
// =========================================================================
if ($action === 'checkout' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_SESSION['cart'])) {
        $_SESSION['err'] = "Giỏ hàng của bạn đang trống!";
        header("Location: ../index.php?tab=shop-view");
        exit;
    }

    $tenNN = trim($_POST['tenNN'] ?? '');
    $soDienThoai = trim($_POST['soDienThoai'] ?? '');
    $diaChi = trim($_POST['diaChi'] ?? '');
    $phuongThuc = $_POST['phuongThuc'] ?? 'Chuyển khoản QR';
    $voucherCode = strtoupper(trim($_POST['voucherCode'] ?? ''));

    if ($tenNN === '' || $soDienThoai === '' || $diaChi === '') {
        $_SESSION['err'] = "Vui lòng điền đầy đủ Họ tên, Số điện thoại và Địa chỉ nhận hàng!";
        header("Location: ../index.php?tab=cart-view");
        exit;
    }

    try {
        $conn->beginTransaction();

        // Kiểm tra tồn kho & tính tổng tiền
        $tongTamTinh = 0;
        foreach ($_SESSION['cart'] as $item) {
            $stmtCheck = $conn->prepare("SELECT soLuong, soLuongDat FROM TONKO WHERE maSanPham = ? FOR UPDATE");
            $stmtCheck->execute([$item['maSanPham']]);
            $tkRow = $stmtCheck->fetch();
            $lockedByMe = !empty($_SESSION['stock_locked']) ? (int)$item['soLuong'] : 0;
            $avail = $tkRow ? ((int)$tkRow['soLuong'] - (int)$tkRow['soLuongDat'] + $lockedByMe) : 0;

            if ($avail < (int)$item['soLuong']) {
                $conn->rollBack();
                $_SESSION['err'] = "Sản phẩm [" . $item['tenSanPham'] . "] vừa hết hàng hoặc không đủ số lượng trong kho!";
                header("Location: ../index.php?tab=cart-view");
                exit;
            }

            $donGiaUnit = (float)$item['giaGong'] + (float)$item['giaTrong'];
            $tongTamTinh += $donGiaUnit * (int)$item['soLuong'];
        }

        // Kiểm tra & áp dụng Voucher nếu có
        $maVoucher = null;
        $soTienGiam = 0;
        if ($voucherCode !== '') {
            $stmtVC = $conn->prepare("SELECT * FROM VOUCHER WHERE code = ? FOR UPDATE");
            $stmtVC->execute([$voucherCode]);
            $vc = $stmtVC->fetch();

            if ($vc && new DateTime($vc['ngayHetHan']) > new DateTime() &&
                ($vc['gioiHanDung'] === null || $vc['daDung'] < $vc['gioiHanDung']) &&
                $tongTamTinh >= (float)$vc['donHangToiThieu']) {

                $maVoucher = $vc['maVoucher'];
                if ($vc['loaiGiam'] === 'Phần trăm' || $vc['loaiGiam'] === '%') {
                    $soTienGiam = round($tongTamTinh * ((float)$vc['giaTriGiam'] / 100));
                } else {
                    $soTienGiam = (float)$vc['giaTriGiam'];
                }
                if ($soTienGiam > $tongTamTinh) $soTienGiam = $tongTamTinh;
            }
        }

        $tongThanhToan = max(0, $tongTamTinh - $soTienGiam);
        $maKH = layMaKhachHangHienTai($conn);
        $linkedExistingByPhone = false;

        // Kiểm tra SĐT khách: nếu số điện thoại đã có trong danh sách KHACHHANG thì gắn thẳng vào tài khoản đó luôn
        if (!$maKH && $soDienThoai !== '') {
            $stmtFindKH = $conn->prepare("SELECT maKhachHang FROM KHACHHANG WHERE soDienThoai = ? LIMIT 1");
            $stmtFindKH->execute([$soDienThoai]);
            $foundMaKH = $stmtFindKH->fetchColumn();
            if ($foundMaKH) {
                $maKH = $foundMaKH;
                $linkedExistingByPhone = true;
            }
        }

        $maDH = taoMaTuDong($conn, 'DONHANG', 'maDonHang', 'DH');

        // 1.1. Tạo bản ghi DONHANG
        $conn->prepare("INSERT INTO DONHANG (maDonHang, maKhachHang, maVoucher, ngayDat, tongTien, tenNN, soDienThoai, diaChi) 
                        VALUES (?, ?, ?, NOW(), ?, ?, ?, ?)")
             ->execute([$maDH, $maKH, $maVoucher, $tongThanhToan, $tenNN, $soDienThoai, $diaChi]);

        // 1.2. Tạo CHITIETDONHANG & xử lý tồn kho theo phương thức thanh toán
        $isCOD = ($phuongThuc === 'COD');
        $wasLocked = !empty($_SESSION['stock_locked']);

        foreach ($_SESSION['cart'] as $item) {
            $donGiaUnit = (float)$item['giaGong'] + (float)$item['giaTrong'];
            $qty = (int)$item['soLuong'];
            $maTKToSave = $item['maTrongKinh'];
            $ghiChuRx = trim($item['ghiChuDoMat'] ?? '');

            // Nếu đơn có chọn tròng kính kèm độ mắt, lưu vào KETQUADOMAT & TRONGKINH để kỹ thuật viên xem thông số cắt kính
            if ($maTKToSave && $ghiChuRx !== '' && $ghiChuRx !== 'Kính không độ') {
                $maKQ = $item['maKetQuaDo'] ?? null;
                $rxMaKH = $maKH;
                if (!$rxMaKH) {
                    // Nếu khách vãng lai chưa có maKhachHang, lấy KH01 làm hồ sơ tạm để lưu KETQUADOMAT (vẫn giữ maKhachHang = NULL trên DONHANG)
                    $rxMaKH = $conn->query("SELECT maKhachHang FROM KHACHHANG ORDER BY maKhachHang ASC LIMIT 1")->fetchColumn();
                }
                if (!$maKQ && $rxMaKH) {
                    $maKQ = taoMaTuDong($conn, 'KETQUADOMAT', 'maKetQuaDo', 'KQ');
                    $conn->prepare("INSERT INTO KETQUADOMAT (maKetQuaDo, maKhachHang, ngayDo, ketQua) VALUES (?, ?, NOW(), ?)")
                         ->execute([$maKQ, $rxMaKH, $ghiChuRx]);
                }
                if ($maKQ) {
                    $stmtOrigTK = $conn->prepare("SELECT * FROM TRONGKINH WHERE maTrongKinh = ? LIMIT 1");
                    $stmtOrigTK->execute([$maTKToSave]);
                    $origTK = $stmtOrigTK->fetch();
                    if ($origTK) {
                        $newMaTK = taoMaTuDong($conn, 'TRONGKINH', 'maTrongKinh', 'TRK');
                        $conn->prepare("INSERT INTO TRONGKINH (maTrongKinh, maSanPham, maKetQuaDo, loai, chietSuat, tinhNang, gia, vatLieu) 
                                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
                             ->execute([
                                $newMaTK,
                                $item['maSanPham'],
                                $maKQ,
                                $origTK['loai'],
                                $origTK['chietSuat'],
                                $origTK['tinhNang'],
                                $origTK['gia'],
                                $origTK['vatLieu']
                             ]);
                        $maTKToSave = $newMaTK;
                    }
                }
            }

            for ($q = 0; $q < $qty; $q++) {
                $maCTDH = taoMaTuDong($conn, 'CHITIETDONHANG', 'maCTDDonHang', 'CTDH');
                $conn->prepare("INSERT INTO CHITIETDONHANG (maCTDDonHang, maDonHang, maSanPham, maTrongKinh, donGia) 
                                VALUES (?, ?, ?, ?, ?)")
                     ->execute([$maCTDH, $maDH, $item['maSanPham'], $maTKToSave, $donGiaUnit]);
            }

            if ($isCOD) {
                // COD không cần chờ duyệt: trừ thẳng tồn thực (soLuong), nhả giữ chỗ (nếu có) và tự chuyển 'Tạm hết' nếu hết hàng!
                $deltaDat = $wasLocked ? -$qty : 0;
                capNhatTonKhoVaTrangThaiSP($conn, $item['maSanPham'], -$qty, $deltaDat);
            } else {
                // Chuyển khoản QR: giữ chỗ soLuongDat chờ Admin/Nhân viên xác nhận thanh toán
                if (!$wasLocked) {
                    capNhatTonKhoVaTrangThaiSP($conn, $item['maSanPham'], 0, $qty);
                }
            }
        }

        // 1.3. Tạo bản ghi THANHTOAN
        $maTT = taoMaTuDong($conn, 'THANHTOAN', 'maThanhToan', 'TT');
        $trangThaiTT = $isCOD ? 'Đã xác nhận (COD)' : 'Chờ xác nhận thanh toán';
        $conn->prepare("INSERT INTO THANHTOAN (maThanhToan, maDonHang, phuongThuc, trangThai, soTien) 
                        VALUES (?, ?, ?, ?, ?)")
             ->execute([$maTT, $maDH, $phuongThuc, $trangThaiTT, $tongThanhToan]);

        // 1.4. Ghi LICHSUVOUCHER & tăng daDung nếu có dùng Voucher
        if ($maVoucher !== null) {
            $maLSV = taoMaTuDong($conn, 'LICHSUVOUCHER', 'maLichSu', 'LSV');
            $conn->prepare("INSERT INTO LICHSUVOUCHER (maLichSu, maVoucher, maDonHang, ngayDung) VALUES (?, ?, ?, NOW())")
                 ->execute([$maLSV, $maVoucher, $maDH]);
            $conn->prepare("UPDATE VOUCHER SET daDung = daDung + 1 WHERE maVoucher = ?")->execute([$maVoucher]);
        }

        // 1.5. Dọn sạch giỏ hàng trong DB & Session
        if ($maKH) {
            $stmtGH = $conn->prepare("SELECT maGioHang FROM GIOHANG WHERE maKhachHang = ? LIMIT 1");
            $stmtGH->execute([$maKH]);
            $maGH = $stmtGH->fetchColumn();
            if ($maGH) {
                $conn->prepare("DELETE FROM CHITIETGIOHANG WHERE maGioHang = ?")->execute([$maGH]);
            }
        }

        $conn->commit();
        unset($_SESSION['cart']);
        unset($_SESSION['stock_locked']);

        // Chỉ hiện Popup mời tạo tài khoản nếu chưa đăng nhập VÀ SĐT chưa có trong danh sách khách hàng
        if (!isLogged() && !$maKH) {
            $_SESSION['guest_just_ordered'] = [
                'maDonHang'   => $maDH,
                'tenNN'       => $tenNN,
                'soDienThoai' => $soDienThoai,
                'diaChi'      => $diaChi
            ];
        }

        $linkedNote = $linkedExistingByPhone ? " (Đơn hàng đã tự động lưu vào tài khoản thành viên gắn với SĐT $soDienThoai)" : "";
        if ($isCOD) {
            $_SESSION['msg'] = "Đặt hàng COD [$maDH] thành công! Đơn hàng đã được xác nhận tự động." . $linkedNote;
        } else {
            $_SESSION['msg'] = "Đặt hàng [$maDH] thành công! Hệ thống đang chờ Nhân viên/Admin xác nhận giao dịch chuyển khoản QR." . $linkedNote;
        }

        $redirectUrl = "../index.php?tab=customer-orders" . (!$maKH ? "&sdt_tracuu=" . urlencode($soDienThoai) : "");
        header("Location: " . $redirectUrl);
        exit;
    } catch (Exception $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        $_SESSION['err'] = "Lỗi xử lý đơn hàng: " . $e->getMessage();
        header("Location: ../index.php?tab=cart-view");
        exit;
    }
}

// =========================================================================
// 2. NHÂN VIÊN / ADMIN XÁC NHẬN THANH TOÁN QR (TRỪ TỒN KHO THỰC & AUTO TẠM HẾT)
// =========================================================================
if ($action === 'confirm_payment') {
    if (!isStaffOrAdmin()) {
        $_SESSION['err'] = "Bạn không có quyền xác nhận thanh toán!";
        header("Location: ../index.php");
        exit;
    }

    $maDH = $_GET['id'] ?? '';
    try {
        $conn->beginTransaction();

        $stmtTT = $conn->prepare("SELECT trangThai FROM THANHTOAN WHERE maDonHang = ? FOR UPDATE");
        $stmtTT->execute([$maDH]);
        $currentStatus = $stmtTT->fetchColumn();

        if ($currentStatus === 'Chờ xác nhận thanh toán') {
            $conn->prepare("UPDATE THANHTOAN SET trangThai = 'Đã thanh toán' WHERE maDonHang = ?")->execute([$maDH]);

            // Trừ tồn kho vật lý (soLuong), nhả giữ chỗ (soLuongDat) và tự chuyển 'Tạm hết' nếu hết hàng
            $stmtItems = $conn->prepare("SELECT maSanPham, COUNT(*) AS qty FROM CHITIETDONHANG WHERE maDonHang = ? GROUP BY maSanPham");
            $stmtItems->execute([$maDH]);
            foreach ($stmtItems->fetchAll() as $row) {
                capNhatTonKhoVaTrangThaiSP($conn, $row['maSanPham'], -(int)$row['qty'], -(int)$row['qty']);
            }
            $_SESSION['msg'] = "Đã xác nhận thanh toán QR cho đơn [$maDH]! Số lượng tồn kho đã được trừ trực tiếp.";
        } else {
            $_SESSION['err'] = "Đơn hàng [$maDH] không ở trạng thái chờ xác nhận thanh toán.";
        }

        $conn->commit();
        if ($currentStatus === 'Chờ xác nhận thanh toán' && isLogged()) {
            ghiNhatKyNhanVien($_SESSION['user']['maNguoiDung'], 'process_order', $maDH, "Xác nhận thanh toán QR đơn hàng #$maDH");
        }
    } catch (Exception $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        $_SESSION['err'] = "Lỗi xác nhận đơn: " . $e->getMessage();
    }
    header("Location: ../index.php?tab=admin-orders");
    exit;
}

// =========================================================================
// 3. QUẢN TRỊ VIÊN / NHÂN VIÊN SỬA THÔNG TIN ĐƠN HÀNG CỦA KHÁCH
// =========================================================================
if ($action === 'edit_order' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isStaffOrAdmin()) {
        $_SESSION['err'] = "Bạn không có quyền chỉnh sửa đơn hàng!";
        header("Location: ../index.php");
        exit;
    }

    $maDH = $_POST['maDonHang'] ?? '';
    $tenNN = trim($_POST['tenNN'] ?? '');
    $soDienThoai = trim($_POST['soDienThoai'] ?? '');
    $diaChi = trim($_POST['diaChi'] ?? '');
    $phuongThuc = trim($_POST['phuongThuc'] ?? 'COD');
    $newStatus = trim($_POST['trangThaiDon'] ?? '');

    try {
        $conn->beginTransaction();

        $stmtOld = $conn->prepare("SELECT trangThai FROM THANHTOAN WHERE maDonHang = ? FOR UPDATE");
        $stmtOld->execute([$maDH]);
        $oldStatus = $stmtOld->fetchColumn();

        // Cập nhật thông tin giao nhận trong bảng DONHANG
        $conn->prepare("UPDATE DONHANG SET tenNN = ?, soDienThoai = ?, diaChi = ? WHERE maDonHang = ?")
             ->execute([$tenNN, $soDienThoai, $diaChi, $maDH]);

        // Cập nhật phương thức và trạng thái trong bảng THANHTOAN
        $conn->prepare("UPDATE THANHTOAN SET phuongThuc = ?, trangThai = ? WHERE maDonHang = ?")
             ->execute([$phuongThuc, $newStatus, $maDH]);

        // Đồng bộ tồn kho nếu trạng thái thay đổi qua lại giữa chưa trừ kho <-> đã trừ kho <-> hủy
        if ($oldStatus && $oldStatus !== $newStatus) {
            $stmtItems = $conn->prepare("SELECT maSanPham, COUNT(*) AS qty FROM CHITIETDONHANG WHERE maDonHang = ? GROUP BY maSanPham");
            $stmtItems->execute([$maDH]);
            $items = $stmtItems->fetchAll();

            $wasDeducted = daTruTonVatLy($oldStatus) || ($oldStatus === 'Chờ xác nhận hủy');
            $willDeduct = daTruTonVatLy($newStatus);

            if (!$wasDeducted && $willDeduct) {
                $deltaDat = ($oldStatus === 'Chờ xác nhận thanh toán') ? -1 : 0;
                foreach ($items as $row) {
                    $q = (int)$row['qty'];
                    capNhatTonKhoVaTrangThaiSP($conn, $row['maSanPham'], -$q, $deltaDat * $q);
                }
            } elseif ($newStatus === 'Đã hủy' && $oldStatus !== 'Đã hủy') {
                if ($wasDeducted) {
                    foreach ($items as $row) {
                        capNhatTonKhoVaTrangThaiSP($conn, $row['maSanPham'], (int)$row['qty'], 0);
                    }
                } elseif ($oldStatus === 'Chờ xác nhận thanh toán') {
                    foreach ($items as $row) {
                        capNhatTonKhoVaTrangThaiSP($conn, $row['maSanPham'], 0, -(int)$row['qty']);
                    }
                }
            }
        }

        $conn->commit();
        if (isLogged()) {
            ghiNhatKyNhanVien($_SESSION['user']['maNguoiDung'], 'process_order', $maDH, "Cập nhật đơn hàng #$maDH → Trạng thái: $newStatus (Khách: $tenNN - $soDienThoai)");
        }
        $_SESSION['msg'] = "Đã cập nhật thông tin đơn hàng [$maDH] thành công!";
    } catch (Exception $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        $_SESSION['err'] = "Lỗi cập nhật đơn hàng: " . $e->getMessage();
    }
    header("Location: ../index.php?tab=admin-orders");
    exit;
}

// =========================================================================
// 4. HỦY ĐƠN HÀNG PHÍA KHÁCH (CHỜ XÁC NHẬN TỪ QUẢN TRỊ) & PHÍA QUẢN TRỊ (ĐƠN PHƯƠNG HỦY / DUYỆT HỦY)
// =========================================================================
// 4A. Khách hàng yêu cầu hủy đơn -> chuyển sang 'Chờ xác nhận hủy'
if ($action === 'request_cancel') {
    $maDH = $_GET['id'] ?? '';
    $sdtTrace = $_GET['sdt'] ?? '';

    try {
        $stmtTT = $conn->prepare("SELECT trangThai FROM THANHTOAN WHERE maDonHang = ?");
        $stmtTT->execute([$maDH]);
        $st = $stmtTT->fetchColumn();

        if (!$st || in_array($st, ['Đang mài lắp', 'Đang giao hàng', 'Đã giao', 'Đã hủy', 'Chờ xác nhận hủy'])) {
            $_SESSION['err'] = "Đơn hàng [$maDH] đang ở trạng thái '$st' nên không thể hủy!";
        } else {
            // Lưu ghi chú trạng thái trước khi hủy vào session hoặc cập nhật thẳng trạng thái chờ duyệt hủy
            $_SESSION['pre_cancel_status'][$maDH] = $st;
            $conn->prepare("UPDATE THANHTOAN SET trangThai = 'Chờ xác nhận hủy' WHERE maDonHang = ?")->execute([$maDH]);
            $_SESSION['msg'] = "Đã gửi yêu cầu hủy đơn hàng [$maDH]. Vui lòng chờ Quản trị viên / Nhân viên xác nhận!";
        }
    } catch (Exception $e) {
        $_SESSION['err'] = "Lỗi gửi yêu cầu hủy đơn: " . $e->getMessage();
    }

    $redirect = "../index.php?tab=customer-orders" . ($sdtTrace !== '' ? "&sdt_tracuu=" . urlencode($sdtTrace) : "");
    header("Location: " . $redirect);
    exit;
}

// 4B. Quản trị viên / Nhân viên đơn phương hủy đơn hoặc duyệt yêu cầu hủy của khách -> Hoàn trả tồn kho
if ($action === 'admin_cancel' || $action === 'approve_cancel') {
    if (!isStaffOrAdmin()) {
        $_SESSION['err'] = "Bạn không có quyền hủy đơn hàng!";
        header("Location: ../index.php");
        exit;
    }

    $maDH = $_GET['id'] ?? '';
    try {
        $conn->beginTransaction();

        $stmtTT = $conn->prepare("SELECT phuongThuc, trangThai FROM THANHTOAN WHERE maDonHang = ? FOR UPDATE");
        $stmtTT->execute([$maDH]);
        $ttRow = $stmtTT->fetch();

        if ($ttRow && in_array($ttRow['trangThai'], ['Đang mài lắp', 'Đang giao hàng', 'Đã giao'])) {
            $conn->rollBack();
            $_SESSION['err'] = "Đơn hàng [$maDH] đang ở trạng thái '" . $ttRow['trangThai'] . "' nên không thể hủy!";
            header("Location: ../index.php?tab=admin-orders");
            exit;
        }

        if ($ttRow && $ttRow['trangThai'] !== 'Đã hủy') {
            $oldSt = $ttRow['trangThai'];
            $prevSt = $_SESSION['pre_cancel_status'][$maDH] ?? null;

            $conn->prepare("UPDATE THANHTOAN SET trangThai = 'Đã hủy' WHERE maDonHang = ?")->execute([$maDH]);

            $stmtItems = $conn->prepare("SELECT maSanPham, COUNT(*) AS qty FROM CHITIETDONHANG WHERE maDonHang = ? GROUP BY maSanPham");
            $stmtItems->execute([$maDH]);
            $items = $stmtItems->fetchAll();

            // Xác định đơn này trước đó đã trừ soLuong chưa hay mới chỉ giữ chỗ soLuongDat
            $isOnlyReserved = ($oldSt === 'Chờ xác nhận thanh toán') ||
                              ($oldSt === 'Chờ xác nhận hủy' && $prevSt === 'Chờ xác nhận thanh toán');

            foreach ($items as $row) {
                $q = (int)$row['qty'];
                if ($isOnlyReserved) {
                    capNhatTonKhoVaTrangThaiSP($conn, $row['maSanPham'], 0, -$q);
                } else {
                    capNhatTonKhoVaTrangThaiSP($conn, $row['maSanPham'], $q, 0);
                }
            }

            unset($_SESSION['pre_cancel_status'][$maDH]);
            $cancelLabel = ($action === 'approve_cancel') ? "Duyệt yêu cầu hủy đơn #$maDH" : "Đơn phương hủy đơn #$maDH";
            $_SESSION['msg'] = ($action === 'approve_cancel')
                ? "Đã duyệt yêu cầu hủy đơn [$maDH] của khách và hoàn trả số lượng tồn kho!"
                : "Quản trị viên đã đơn phương hủy đơn hàng [$maDH] và hoàn trả số lượng về kho!";
        }

        $conn->commit();
        if (!empty($cancelLabel) && isLogged()) {
            ghiNhatKyNhanVien($_SESSION['user']['maNguoiDung'], 'process_order', $maDH, $cancelLabel);
        }
    } catch (Exception $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        $_SESSION['err'] = "Lỗi khi hủy đơn hàng: " . $e->getMessage();
    }
    header("Location: ../index.php?tab=admin-orders");
    exit;
}

// 4C. Quản trị viên từ chối yêu cầu hủy đơn của khách -> Đưa đơn về lại Đang xử lý
if ($action === 'reject_cancel') {
    if (!isStaffOrAdmin()) {
        $_SESSION['err'] = "Bạn không có quyền thực hiện thao tác này!";
        header("Location: ../index.php");
        exit;
    }
    $maDH = $_GET['id'] ?? '';
    $restoreSt = $_SESSION['pre_cancel_status'][$maDH] ?? 'Đang xử lý';
    $conn->prepare("UPDATE THANHTOAN SET trangThai = ? WHERE maDonHang = ? AND trangThai = 'Chờ xác nhận hủy'")
         ->execute([$restoreSt, $maDH]);
    unset($_SESSION['pre_cancel_status'][$maDH]);
    if (isLogged()) {
        ghiNhatKyNhanVien($_SESSION['user']['maNguoiDung'], 'process_order', $maDH, "Từ chối yêu cầu hủy đơn #$maDH (Tiếp tục xử lý)");
    }
    $_SESSION['msg'] = "Đã từ chối yêu cầu hủy đơn [$maDH]. Đơn hàng tiếp tục được xử lý.";
    header("Location: ../index.php?tab=admin-orders");
    exit;
}

// =========================================================================
// 5. TẠO ĐƠN HÀNG TẠI CỬA HÀNG (POS) CHO NHÂN VIÊN & ADMIN
// - Tự động lưu tài khoản khách hàng mới (NGUOIDUNG + KHACHHANG)
// - Áp dụng Voucher chọn từ danh sách
// - Xác nhận là Đã thanh toán luôn & trừ kho thực + auto Tạm hết nếu hết hàng
// =========================================================================
if ($action === 'create_pos_order' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isStaffOrAdmin()) {
        $_SESSION['err'] = "Chỉ Nhân viên hoặc Admin mới được tạo đơn tại cửa hàng!";
        header("Location: ../index.php");
        exit;
    }

    $loaiKhach = $_POST['loaiKhach'] ?? 'member';
    $maKH = null;
    $tenNN = '';
    $soDienThoai = '';
    $diaChi = trim($_POST['diaChi'] ?? '');
    if ($diaChi === '') $diaChi = 'Mua trực tiếp tại cửa hàng Việt MAC';
    $newAccountNote = '';

    try {
        $conn->beginTransaction();

        if ($loaiKhach === 'member' && !empty($_POST['maKhachHang'])) {
            $maKH = $_POST['maKhachHang'];
            $stmtKH = $conn->prepare("SELECT kh.soDienThoai, kh.diaChi, nd.hoTen 
                                      FROM KHACHHANG kh JOIN NGUOIDUNG nd ON kh.maNguoiDung = nd.maNguoiDung 
                                      WHERE kh.maKhachHang = ? LIMIT 1");
            $stmtKH->execute([$maKH]);
            $khRow = $stmtKH->fetch();
            if ($khRow) {
                $tenNN = $khRow['hoTen'];
                $soDienThoai = $khRow['soDienThoai'];
                if (!empty($khRow['diaChi'])) $diaChi = $khRow['diaChi'];
            }
        } else {
            // Khách hàng mới tại quầy -> Kiểm tra SĐT, nếu chưa có thì TỰ ĐỘNG TẠO TÀI KHOẢN & LƯU VÀO DB!
            $tenNN = trim($_POST['tenNN'] ?? '');
            $soDienThoai = trim($_POST['soDienThoai'] ?? '');
            $emailMoi = trim($_POST['emailKhachMoi'] ?? '');
            $gioiTinhMoi = $_POST['gioiTinhKhachMoi'] ?? 'Nam';

            if ($tenNN === '' || $soDienThoai === '') {
                $conn->rollBack();
                $_SESSION['err'] = "Vui lòng nhập đầy đủ Họ tên và Số điện thoại của khách hàng mới!";
                header("Location: ../index.php?tab=admin-pos");
                exit;
            }

            // Kiểm tra SĐT đã tồn tại trong bảng KHACHHANG chưa
            $stmtExist = $conn->prepare("SELECT kh.maKhachHang, nd.hoTen FROM KHACHHANG kh JOIN NGUOIDUNG nd ON kh.maNguoiDung = nd.maNguoiDung WHERE kh.soDienThoai = ? LIMIT 1");
            $stmtExist->execute([$soDienThoai]);
            $existKH = $stmtExist->fetch();

            if ($existKH) {
                $maKH = $existKH['maKhachHang'];
                $newAccountNote = " (Đã liên kết với tài khoản sẵn có: $maKH)";
            } else {
                if ($emailMoi === '') {
                    $emailMoi = preg_replace('/[^0-9]/', '', $soDienThoai) . '@vietmac.vn';
                }
                // Đảm bảo email không trùng
                $chkMail = $conn->prepare("SELECT COUNT(*) FROM NGUOIDUNG WHERE email = ?");
                $chkMail->execute([$emailMoi]);
                if ($chkMail->fetchColumn() > 0) {
                    $emailMoi = preg_replace('/[^0-9]/', '', $soDienThoai) . '_' . rand(10, 99) . '@vietmac.vn';
                }

                $maND = taoMaTuDong($conn, 'NGUOIDUNG', 'maNguoiDung', 'ND');
                $conn->prepare("INSERT INTO NGUOIDUNG (maNguoiDung, hoTen, email, matKhau, maVaiTro) VALUES (?, ?, ?, ?, 'VT03')")
                     ->execute([$maND, $tenNN, $emailMoi, md5('123')]);

                $maKH = taoMaTuDong($conn, 'KHACHHANG', 'maKhachHang', 'KH');
                $conn->prepare("INSERT INTO KHACHHANG (maKhachHang, maNguoiDung, gioiTinh, ngaySinh, soDienThoai, diaChi) VALUES (?, ?, ?, NULL, ?, ?)")
                     ->execute([$maKH, $maND, $gioiTinhMoi, $soDienThoai, $diaChi]);

                $newAccountNote = " | Đã tự động tạo tài khoản [$maKH] (Email: $emailMoi, Pass mặc định: 123)";
            }
        }

        $maSPList = $_POST['maSanPham'] ?? [];
        $maTKList = $_POST['maTrongKinh'] ?? [];
        $qtyList  = $_POST['soLuong'] ?? [];
        $voucherCode = strtoupper(trim($_POST['voucherCode'] ?? ''));
        $phuongThuc = $_POST['phuongThuc'] ?? 'Tiền mặt tại quầy';

        // Thông số độ mắt nhập tại quầy (SPH / CYL / AXIS / PD)
        $posCoDoMat = $_POST['posCoDoMat'] ?? 'no_rx';
        $posSphOD   = trim($_POST['posSphOD'] ?? '');
        $posSphOS   = trim($_POST['posSphOS'] ?? '');
        $posCylAxis = trim($_POST['posCylAxis'] ?? '');
        $posPD      = trim($_POST['posPD'] ?? '');

        $maKetQuaDoPOS = null;
        if ($posCoDoMat === 'with_rx' && ($posSphOD !== '' || $posSphOS !== '' || $posCylAxis !== '' || $posPD !== '')) {
            $odVal = $posSphOD !== '' ? $posSphOD : '0.00';
            $osVal = $posSphOS !== '' ? $posSphOS : '0.00';
            $cylVal = $posCylAxis !== '' ? $posCylAxis : 'Không loạn';
            $pdVal = $posPD !== '' ? $posPD : '62';
            $ketQuaText = "OD(Phải): $odVal | OS(Trái): $osVal | Loạn/Trục: $cylVal | PD: {$pdVal}mm";

            $targetMaKH = $maKH ?: $conn->query("SELECT maKhachHang FROM KHACHHANG ORDER BY maKhachHang ASC LIMIT 1")->fetchColumn();
            if ($targetMaKH) {
                $maKetQuaDoPOS = taoMaTuDong($conn, 'KETQUADOMAT', 'maKetQuaDo', 'KQ');
                $conn->prepare("INSERT INTO KETQUADOMAT (maKetQuaDo, maKhachHang, ngayDo, ketQua) VALUES (?, ?, NOW(), ?)")
                     ->execute([$maKetQuaDoPOS, $targetMaKH, $ketQuaText]);
            }
        }

        $tongTamTinh = 0;
        $orderLines = [];

        for ($i = 0; $i < count($maSPList); $i++) {
            $msp = $maSPList[$i];
            $mtk = $maTKList[$i] ?? '';
            $qty = max(1, (int)($qtyList[$i] ?? 1));
            if ($msp === '') continue;

            $stmtSP = $conn->prepare("SELECT sp.tenSanPham, sp.gia, COALESCE(tk.soLuong, 0) AS soLuong, COALESCE(tk.soLuongDat, 0) AS soLuongDat
                                      FROM SANPHAM sp LEFT JOIN TONKO tk ON sp.maSanPham = tk.maSanPham
                                      WHERE sp.maSanPham = ? FOR UPDATE");
            $stmtSP->execute([$msp]);
            $sp = $stmtSP->fetch();

            if (!$sp || ($sp['soLuong'] - $sp['soLuongDat']) < $qty) {
                $conn->rollBack();
                $_SESSION['err'] = "Sản phẩm [" . ($sp['tenSanPham'] ?? $msp) . "] không đủ số lượng tồn kho khả dụng!";
                header("Location: ../index.php?tab=admin-pos");
                exit;
            }

            $giaTrong = 0;
            $validMTK = null;
            if ($mtk !== '') {
                $stmtLens = $conn->prepare("SELECT * FROM TRONGKINH WHERE maTrongKinh = ? LIMIT 1");
                $stmtLens->execute([$mtk]);
                $lensRow = $stmtLens->fetch();
                if ($lensRow) {
                    $giaTrong = (float)$lensRow['gia'];
                    if ($maKetQuaDoPOS) {
                        $newMaTK = taoMaTuDong($conn, 'TRONGKINH', 'maTrongKinh', 'TRK');
                        $conn->prepare("INSERT INTO TRONGKINH (maTrongKinh, maSanPham, maKetQuaDo, loai, chietSuat, tinhNang, gia, vatLieu) 
                                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
                             ->execute([
                                $newMaTK,
                                $msp,
                                $maKetQuaDoPOS,
                                $lensRow['loai'],
                                $lensRow['chietSuat'],
                                $lensRow['tinhNang'],
                                $lensRow['gia'],
                                $lensRow['vatLieu']
                             ]);
                        $validMTK = $newMaTK;
                    } else {
                        $validMTK = $mtk;
                    }
                }
            } elseif ($maKetQuaDoPOS) {
                // Nếu quản trị nhập độ mắt nhưng chưa chọn loại tròng, tự gắn vào tròng đơn tiêu chuẩn để lưu thông số đo mắt vào đơn
                $newMaTK = taoMaTuDong($conn, 'TRONGKINH', 'maTrongKinh', 'TRK');
                $conn->prepare("INSERT INTO TRONGKINH (maTrongKinh, maSanPham, maKetQuaDo, loai, chietSuat, tinhNang, gia, vatLieu) 
                                VALUES (?, ?, ?, 'Đơn tròng theo toa', 1.56, 'Cắt theo thông số đo mắt tại quầy', 0, 'Nhựa hữu cơ CR-39')")
                     ->execute([$newMaTK, $msp, $maKetQuaDoPOS]);
                $validMTK = $newMaTK;
            }

            $donGiaUnit = (float)$sp['gia'] + $giaTrong;
            $tongTamTinh += $donGiaUnit * $qty;
            $orderLines[] = [
                'maSanPham'   => $msp,
                'maTrongKinh' => $validMTK,
                'donGia'      => $donGiaUnit,
                'soLuong'     => $qty
            ];
        }

        if (empty($orderLines)) {
            $conn->rollBack();
            $_SESSION['err'] = "Vui lòng chọn ít nhất 1 sản phẩm hợp lệ!";
            header("Location: ../index.php?tab=admin-pos");
            exit;
        }

        // Kiểm tra Voucher nếu có
        $maVoucher = null;
        $soTienGiam = 0;
        if ($voucherCode !== '') {
            $stmtVC = $conn->prepare("SELECT * FROM VOUCHER WHERE code = ? FOR UPDATE");
            $stmtVC->execute([$voucherCode]);
            $vc = $stmtVC->fetch();
            if ($vc && new DateTime($vc['ngayHetHan']) > new DateTime() &&
                ($vc['gioiHanDung'] === null || $vc['daDung'] < $vc['gioiHanDung']) &&
                $tongTamTinh >= (float)$vc['donHangToiThieu']) {
                $maVoucher = $vc['maVoucher'];
                if ($vc['loaiGiam'] === 'Phần trăm' || $vc['loaiGiam'] === '%') {
                    $soTienGiam = round($tongTamTinh * ((float)$vc['giaTriGiam'] / 100));
                } else {
                    $soTienGiam = (float)$vc['giaTriGiam'];
                }
                if ($soTienGiam > $tongTamTinh) $soTienGiam = $tongTamTinh;
            }
        }

        $tongThanhToan = max(0, $tongTamTinh - $soTienGiam);
        $maDH = taoMaTuDong($conn, 'DONHANG', 'maDonHang', 'DH');

        $conn->prepare("INSERT INTO DONHANG (maDonHang, maKhachHang, maVoucher, ngayDat, tongTien, tenNN, soDienThoai, diaChi) 
                        VALUES (?, ?, ?, NOW(), ?, ?, ?, ?)")
             ->execute([$maDH, $maKH, $maVoucher, $tongThanhToan, $tenNN, $soDienThoai, $diaChi]);

        foreach ($orderLines as $line) {
            for ($q = 0; $q < $line['soLuong']; $q++) {
                $maCTDH = taoMaTuDong($conn, 'CHITIETDONHANG', 'maCTDDonHang', 'CTDH');
                $conn->prepare("INSERT INTO CHITIETDONHANG (maCTDDonHang, maDonHang, maSanPham, maTrongKinh, donGia) 
                                VALUES (?, ?, ?, ?, ?)")
                     ->execute([$maCTDH, $maDH, $line['maSanPham'], $line['maTrongKinh'], $line['donGia']]);
            }
            // Trừ trực tiếp tồn kho thực & tự động chuyển 'Tạm hết' nếu hết hàng
            capNhatTonKhoVaTrangThaiSP($conn, $line['maSanPham'], -$line['soLuong'], 0);
        }

        $maTT = taoMaTuDong($conn, 'THANHTOAN', 'maThanhToan', 'TT');
        $conn->prepare("INSERT INTO THANHTOAN (maThanhToan, maDonHang, phuongThuc, trangThai, soTien) 
                        VALUES (?, ?, ?, 'Đã thanh toán', ?)")
             ->execute([$maTT, $maDH, $phuongThuc, $tongThanhToan]);

        if ($maVoucher !== null) {
            $maLSV = taoMaTuDong($conn, 'LICHSUVOUCHER', 'maLichSu', 'LSV');
            $conn->prepare("INSERT INTO LICHSUVOUCHER (maLichSu, maVoucher, maDonHang, ngayDung) VALUES (?, ?, ?, NOW())")
                 ->execute([$maLSV, $maVoucher, $maDH]);
            $conn->prepare("UPDATE VOUCHER SET daDung = daDung + 1 WHERE maVoucher = ?")->execute([$maVoucher]);
        }

        $conn->commit();
        if (isLogged()) {
            ghiNhatKyNhanVien(
                $_SESSION['user']['maNguoiDung'],
                'pos_order',
                $maDH,
                "Tạo đơn tại quầy #$maDH cho khách: $tenNN ($soDienThoai) — Tổng: " . number_format($tongThanhToan, 0, ',', '.') . " đ"
            );
        }
        $_SESSION['msg'] = "Tạo đơn tại cửa hàng [$maDH] cho khách [$tenNN] thành công (Đã thanh toán)!" . $newAccountNote;
    } catch (Exception $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        $_SESSION['err'] = "Lỗi tạo đơn tại quầy: " . $e->getMessage();
    }
    header("Location: ../index.php?tab=admin-orders");
    exit;
}

// =========================================================================
// 6. KHÁCH HÀNG GỬI ĐÁNH GIÁ SẢN PHẨM (BẢNG DANHGIA)
// =========================================================================
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

    $maDG = taoMaTuDong($conn, 'DANHGIA', 'maDanhGia', 'DG');
    $conn->prepare("INSERT INTO DANHGIA (maDanhGia, maSanPham, maKhachHang, sao, binhLuan) VALUES (?, ?, ?, ?, ?)")
         ->execute([$maDG, $maSP, $maKH, $sao, $binhLuan]);

    $_SESSION['msg'] = "Cảm ơn bạn đã gửi đánh giá $sao sao cho sản phẩm!";
    header("Location: ../index.php?tab=shop-view");
    exit;
}

// 7. KHÁCH HÀNG SỬA ĐÁNH GIÁ CỦA CHÍNH MÌNH
if ($action === 'edit_review' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $maKH = layMaKhachHangHienTai($conn);
    $maDanhGia = $_POST['maDanhGia'] ?? '';
    $sao = max(1, min(5, (int)($_POST['sao'] ?? 5)));
    $binhLuan = trim($_POST['binhLuan'] ?? '');

    if (!$maKH || $maDanhGia === '') {
        $_SESSION['err'] = "Không thể cập nhật đánh giá!";
        header("Location: ../index.php?tab=shop-view");
        exit;
    }

    $conn->prepare("UPDATE DANHGIA SET sao = ?, binhLuan = ? WHERE maDanhGia = ? AND maKhachHang = ?")
         ->execute([$sao, $binhLuan, $maDanhGia, $maKH]);

    $_SESSION['msg'] = "✓ Đã cập nhật đánh giá của bạn!";
    header("Location: ../index.php?tab=shop-view");
    exit;
}

// 8. KHÁCH HÀNG XÓA ĐÁNH GIÁ CỦA CHÍNH MÌNH
if ($action === 'delete_review') {
    $maKH = layMaKhachHangHienTai($conn);
    $maDanhGia = $_GET['id'] ?? '';

    if ($maKH && $maDanhGia !== '') {
        $conn->prepare("DELETE FROM DANHGIA WHERE maDanhGia = ? AND maKhachHang = ?")
             ->execute([$maDanhGia, $maKH]);
        $_SESSION['msg'] = "✓ Đã xóa đánh giá của bạn.";
    }
    header("Location: ../index.php?tab=shop-view");
    exit;
}
?>
