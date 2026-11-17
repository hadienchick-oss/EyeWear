<?php
require_once 'config.php';

/**
 * 1. Hàm tự động sinh mã khóa chính (VD: SP01, TH01, ND01, KH01, VC01, DH01, KN01...)
 */
function taoMaTuDong($conn, $table, $column, $prefix) {
    $sql = "SELECT $column FROM $table WHERE $column LIKE :prefix ORDER BY LENGTH($column) DESC, $column DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['prefix' => $prefix . '%']);
    $row = $stmt->fetch();

    if ($row) {
        $lastId = $row[$column];
        $number = (int)substr($lastId, strlen($prefix)) + 1;
    } else {
        $number = 1;
    }
    return $prefix . str_pad($number, 2, '0', STR_PAD_LEFT);
}

/**
 * 2. Kiểm tra quyền đăng nhập
 * VT01: Admin | VT02: Nhân viên | VT03: Khách hàng
 */
function isLogged() {
    return isset($_SESSION['user']);
}

function isAdmin() {
    return isLogged() && $_SESSION['user']['maVaiTro'] === 'VT01';
}

function isStaffOrAdmin() {
    return isLogged() && in_array($_SESSION['user']['maVaiTro'], ['VT01', 'VT02']);
}

/**
 * 3. Ghi & đọc nhật ký hoạt động Nhân viên / Quản trị (Tạo đơn POS & Phản hồi khiếu nại)
 */
function ghiNhatKyNhanVien($maNguoiDung, $loaiHoatDong, $maDoiTuong, $moTaChiTiet) {
    $logFile = __DIR__ . '/staff_activity_log.json';
    $logs = [];
    if (file_exists($logFile)) {
        $decoded = json_decode(file_get_contents($logFile), true);
        if (is_array($decoded)) $logs = $decoded;
    }
    $logs[] = [
        'maNguoiDung' => $maNguoiDung,
        'loai'        => $loaiHoatDong, // 'pos_order' | 'reply_complaint'
        'maDoiTuong'  => $maDoiTuong,
        'moTa'        => $moTaChiTiet,
        'thoiGian'    => date('Y-m-d H:i:s')
    ];
    file_put_contents($logFile, json_encode($logs, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

function layNhatKyNhanVien($conn) {
    $logFile = __DIR__ . '/staff_activity_log.json';
    $logs = [];
    if (file_exists($logFile)) {
        $decoded = json_decode(file_get_contents($logFile), true);
        if (is_array($decoded)) $logs = $decoded;
    }

    // Gom nhóm theo maNguoiDung
    $grouped = [];
    foreach ($logs as $entry) {
        $uid = $entry['maNguoiDung'] ?? '';
        if ($uid === '') continue;
        if (!isset($grouped[$uid])) {
            $grouped[$uid] = [
                'donDaTao'     => [],
                'donDaXuLy'    => [],
                'khieuNaiXuLy' => []
            ];
        }
        if ($entry['loai'] === 'pos_order') {
            $grouped[$uid]['donDaTao'][] = $entry;
        } elseif ($entry['loai'] === 'process_order') {
            $grouped[$uid]['donDaXuLy'][] = $entry;
        } elseif ($entry['loai'] === 'reply_complaint') {
            $grouped[$uid]['khieuNaiXuLy'][] = $entry;
        }
    }
    return $grouped;
}

/**
 * 4. Lấy maKhachHang & Hồ sơ tài khoản hiện tại
 */
function layMaKhachHangHienTai($conn) {
    if (!isLogged()) return null;
    if (!empty($_SESSION['user']['maKhachHang'])) {
        return $_SESSION['user']['maKhachHang'];
    }
    $stmt = $conn->prepare("SELECT maKhachHang FROM KHACHHANG WHERE maNguoiDung = ? LIMIT 1");
    $stmt->execute([$_SESSION['user']['maNguoiDung']]);
    $mkh = $stmt->fetchColumn();
    if ($mkh) {
        $_SESSION['user']['maKhachHang'] = $mkh;
        return $mkh;
    }
    return null;
}

function layHoSoNguoiDungHienTai($conn) {
    if (!isLogged()) return null;
    $sql = "SELECT nd.*, vt.tenVaiTro, kh.maKhachHang, kh.gioiTinh, kh.ngaySinh, kh.soDienThoai, kh.diaChi
            FROM NGUOIDUNG nd
            JOIN VAITRO vt ON nd.maVaiTro = vt.maVaiTro
            LEFT JOIN KHACHHANG kh ON nd.maNguoiDung = kh.maNguoiDung
            WHERE nd.maNguoiDung = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$_SESSION['user']['maNguoiDung']]);
    return $stmt->fetch();
}

/**
 * 5. Đồng bộ tồn kho & tự động chuyển trạng thái 'Tạm hết' khi hết hàng
 */
function capNhatTonKhoVaTrangThaiSP($conn, $maSanPham, $deltaSoLuong, $deltaSoLuongDat = 0) {
    $stmt = $conn->prepare("SELECT soLuong, soLuongDat FROM TONKO WHERE maSanPham = ? FOR UPDATE");
    $stmt->execute([$maSanPham]);
    $tk = $stmt->fetch();

    if ($tk) {
        $newSoLuong = max(0, (int)$tk['soLuong'] + (int)$deltaSoLuong);
        $newSoLuongDat = max(0, (int)$tk['soLuongDat'] + (int)$deltaSoLuongDat);

        $conn->prepare("UPDATE TONKO SET soLuong = ?, soLuongDat = ? WHERE maSanPham = ?")
             ->execute([$newSoLuong, $newSoLuongDat, $maSanPham]);

        $stmtSP = $conn->prepare("SELECT trangThai FROM SANPHAM WHERE maSanPham = ?");
        $stmtSP->execute([$maSanPham]);
        $trangThaiHienTai = $stmtSP->fetchColumn();

        if ($trangThaiHienTai && !in_array($trangThaiHienTai, ['Ngừng bán', 'Tạm ngưng bán'])) {
            if ($newSoLuong <= 0) {
                $conn->prepare("UPDATE SANPHAM SET trangThai = 'Tạm hết' WHERE maSanPham = ?")->execute([$maSanPham]);
            } elseif ($trangThaiHienTai === 'Tạm hết' && $newSoLuong > 0) {
                $conn->prepare("UPDATE SANPHAM SET trangThai = 'Đang bán' WHERE maSanPham = ?")->execute([$maSanPham]);
            }
        }
    }
}

/**
 * 6. Lấy danh sách Thương hiệu
 */
function layDanhSachThuongHieu($conn) {
    return $conn->query("SELECT * FROM THUONGHIEU ORDER BY maThuongHieu DESC")->fetchAll();
}

/**
 * 7. Lấy danh sách Sản phẩm
 */
function layDanhSachSanPham($conn) {
    $sql = "SELECT sp.*, 
                   th.tenThuongHieu, 
                   th.logo AS logoThuongHieu,
                   th.moTa AS moTaThuongHieu,
                   (SELECT hinhAnh FROM ANHSP a WHERE a.maSanPham = sp.maSanPham ORDER BY a.anhChinh DESC, a.maAnh ASC LIMIT 1) AS hinhAnh,
                   ct.kichThuoc, ct.chieuRongKhung, ct.chieuRongCau, ct.chieuRongThauKinh, 
                   ct.chieuCaoOngKinh, ct.chieuDaiThaiDuong, ct.mauSac, ct.gioiTinh, ct.vatLieu,
                   COALESCE(tk.soLuong, 0) AS soLuong, 
                   COALESCE(tk.soLuongDat, 0) AS soLuongDat,
                   (SELECT COUNT(*) 
                    FROM CHITIETDONHANG ctdh 
                    JOIN DONHANG dh ON ctdh.maDonHang = dh.maDonHang
                    LEFT JOIN THANHTOAN tt ON dh.maDonHang = tt.maDonHang
                    WHERE ctdh.maSanPham = sp.maSanPham 
                      AND (tt.trangThai IS NULL OR tt.trangThai != 'Đã hủy')
                   ) AS daBan
            FROM SANPHAM sp
            LEFT JOIN THUONGHIEU th ON sp.maThuongHieu = th.maThuongHieu
            LEFT JOIN CHITIETSANPHAM ct ON sp.maSanPham = ct.maSanPham
            LEFT JOIN TONKO tk ON sp.maSanPham = tk.maSanPham
            ORDER BY daBan DESC, sp.maSanPham DESC";
    $products = $conn->query($sql)->fetchAll();

    $stmtImgs = $conn->prepare("SELECT maAnh, hinhAnh, anhChinh FROM ANHSP WHERE maSanPham = ? ORDER BY anhChinh DESC, maAnh ASC");
    $stmtReviews = $conn->prepare("SELECT dg.*, nd.hoTen 
                                   FROM DANHGIA dg 
                                   JOIN KHACHHANG kh ON dg.maKhachHang = kh.maKhachHang
                                   JOIN NGUOIDUNG nd ON kh.maNguoiDung = nd.maNguoiDung
                                   WHERE dg.maSanPham = ? ORDER BY dg.maDanhGia DESC");

    foreach ($products as &$sp) {
        $stmtImgs->execute([$sp['maSanPham']]);
        $sp['dsAnh'] = $stmtImgs->fetchAll();

        $stmtReviews->execute([$sp['maSanPham']]);
        $reviews = $stmtReviews->fetchAll();
        $sp['dsDanhGia'] = $reviews;
        if (count($reviews) > 0) {
            $sum = array_sum(array_column($reviews, 'sao'));
            $sp['diemTrungBinh'] = round($sum / count($reviews), 1);
        } else {
            $sp['diemTrungBinh'] = 5.0;
        }
    }
    return $products;
}

/**
 * 8. Lấy danh sách Tròng kính (Bảng TRONGKINH)
 */
function layDanhSachTrongKinh($conn) {
    $list = $conn->query("SELECT * FROM TRONGKINH WHERE maKetQuaDo IS NULL ORDER BY gia ASC")->fetchAll();
    if (empty($list)) {
        $conn->exec("INSERT IGNORE INTO `TRONGKINH` (`maTrongKinh`, `loai`, `chietSuat`, `tinhNang`, `gia`, `vatLieu`) VALUES
            ('TRK01', 'Đơn tròng', 1.56, 'Chống phản quang, chống tia UV400 cơ bản', 350000.00, 'Nhựa hữu cơ CR-39'),
            ('TRK02', 'Đơn tròng', 1.60, 'Chống ánh sáng xanh máy tính, mỏng nhẹ hơn 20%', 650000.00, 'High-Index Plastic'),
            ('TRK03', 'Đa tròng', 1.67, 'Siêu mỏng, Đổi màu khói khi ra nắng', 1200000.00, 'Polycarbonate cao cấp')");
        $list = $conn->query("SELECT * FROM TRONGKINH WHERE maKetQuaDo IS NULL ORDER BY gia ASC")->fetchAll();
    }
    return $list;
}

/**
 * 9. Lấy danh sách Nhân viên (VT02) kèm lịch sử hoạt động (tạo đơn POS & phản hồi khiếu nại)
 */
function layDanhSachNhanVien($conn) {
    $staffList = $conn->query("SELECT * FROM NGUOIDUNG WHERE maVaiTro = 'VT02' ORDER BY maNguoiDung DESC")->fetchAll();
    $activityMap = layNhatKyNhanVien($conn);
    foreach ($staffList as &$st) {
        $uid = $st['maNguoiDung'];
        $st['donDaTao'] = $activityMap[$uid]['donDaTao'] ?? [];
        $st['donDaXuLy'] = $activityMap[$uid]['donDaXuLy'] ?? [];
        $st['khieuNaiXuLy'] = $activityMap[$uid]['khieuNaiXuLy'] ?? [];
    }
    return $staffList;
}

/**
 * 10. Lấy danh sách Khách hàng thành viên & Khách vãng lai
 */
function layDanhSachKhachHang($conn) {
    $sql = "SELECT kh.*, nd.hoTen, nd.email,
                   (SELECT COALESCE(SUM(dh.tongTien), 0) FROM DONHANG dh WHERE dh.maKhachHang = kh.maKhachHang OR (dh.maKhachHang IS NULL AND dh.soDienThoai = kh.soDienThoai)) AS tongChiTieu,
                   (SELECT COUNT(*) FROM DONHANG dh WHERE dh.maKhachHang = kh.maKhachHang OR (dh.maKhachHang IS NULL AND dh.soDienThoai = kh.soDienThoai)) AS soDonHang
            FROM KHACHHANG kh
            JOIN NGUOIDUNG nd ON kh.maNguoiDung = nd.maNguoiDung
            ORDER BY kh.maKhachHang DESC";
    return $conn->query($sql)->fetchAll();
}

function layDanhSachKhachVangLai($conn) {
    $sql = "SELECT soDienThoai, MAX(tenNN) AS tenNN, MAX(diaChi) AS diaChi, 
                   COUNT(*) AS soDonDat, SUM(tongTien) AS tongChiTieu, MAX(ngayDat) AS lanMuaCuoi
            FROM DONHANG
            WHERE maKhachHang IS NULL
            GROUP BY soDienThoai
            ORDER BY lanMuaCuoi DESC";
    return $conn->query($sql)->fetchAll();
}

/**
 * 11. Lấy danh sách Voucher & Lịch sử sử dụng Voucher
 */
function layDanhSachVoucher($conn) {
    return $conn->query("SELECT * FROM VOUCHER ORDER BY maVoucher DESC")->fetchAll();
}

function layLichSuVoucher($conn) {
    $sql = "SELECT ls.*, v.code, dh.tenNN, dh.soDienThoai, dh.tongTien
            FROM LICHSUVOUCHER ls
            JOIN VOUCHER v ON ls.maVoucher = v.maVoucher
            JOIN DONHANG dh ON ls.maDonHang = dh.maDonHang
            ORDER BY ls.ngayDung DESC";
    return $conn->query($sql)->fetchAll();
}

/**
 * 12. Lấy danh sách Đơn hàng kèm Chi tiết sản phẩm, Tròng kính, Thông số độ mắt (KETQUADOMAT) & Thanh toán
 */
function layDanhSachDonHang($conn, $maKhachHang = null, $soDienThoai = null) {
    $where = "";
    $params = [];
    if ($maKhachHang !== null) {
        $where = "WHERE dh.maKhachHang = ?";
        $params[] = $maKhachHang;
    } elseif ($soDienThoai !== null && $soDienThoai !== '') {
        $where = "WHERE dh.soDienThoai = ?";
        $params[] = $soDienThoai;
    }

    $sql = "SELECT dh.*, 
                   tt.maThanhToan, tt.phuongThuc, tt.trangThai AS trangThaiDon, tt.soTien,
                   vc.code AS maCodeVoucher
            FROM DONHANG dh
            LEFT JOIN THANHTOAN tt ON dh.maDonHang = tt.maDonHang
            LEFT JOIN VOUCHER vc ON dh.maVoucher = vc.maVoucher
            $where
            ORDER BY dh.ngayDat DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();

    $stmtCT = $conn->prepare("SELECT ct.*, sp.tenSanPham, sp.gia AS giaGong,
                                     (SELECT hinhAnh FROM ANHSP a WHERE a.maSanPham = sp.maSanPham ORDER BY a.anhChinh DESC, a.maAnh ASC LIMIT 1) AS hinhAnh,
                                     tk.loai AS loaiTrong, tk.chietSuat, tk.tinhNang, tk.gia AS giaTrong,
                                     kq.ketQua AS thongSoDoMat
                              FROM CHITIETDONHANG ct
                              JOIN SANPHAM sp ON ct.maSanPham = sp.maSanPham
                              LEFT JOIN TRONGKINH tk ON ct.maTrongKinh = tk.maTrongKinh
                              LEFT JOIN KETQUADOMAT kq ON tk.maKetQuaDo = kq.maKetQuaDo
                              WHERE ct.maDonHang = ?");
    foreach ($orders as &$od) {
        $stmtCT->execute([$od['maDonHang']]);
        $od['chiTiet'] = $stmtCT->fetchAll();
    }
    return $orders;
}

/**
 * 13. Lấy danh sách Khiếu nại (Bảng KHIEUNAI)
 */
function layDanhSachKhieuNai($conn, $maKhachHang = null) {
    $where = "";
    $params = [];
    if ($maKhachHang !== null) {
        $where = "WHERE kn.maKhachHang = ?";
        $params[] = $maKhachHang;
    }
    $sql = "SELECT kn.*, nd.hoTen AS tenKhachHang, kh.soDienThoai, dh.tongTien AS tongTienDon
            FROM KHIEUNAI kn
            JOIN KHACHHANG kh ON kn.maKhachHang = kh.maKhachHang
            JOIN NGUOIDUNG nd ON kh.maNguoiDung = nd.maNguoiDung
            LEFT JOIN DONHANG dh ON kn.maDonHang = dh.maDonHang
            $where
            ORDER BY kn.ngayTao DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * 14. Đếm thông báo vận hành cho Quản trị / Nhân viên:
 * - Chỉ khi 'Đã giao' hoặc 'Đã hủy' mới mất thông báo!
 * - `donMoiCanXuLy` (Huy hiệu số đỏ): Đơn mới cần xử lý cắt tròng / duyệt thanh toán / duyệt hủy ('Chờ xác nhận thanh toán', 'Đã xác nhận (COD)', 'Đã thanh toán', 'Đang xử lý', 'Chờ xác nhận hủy')
 * - `donDangMaiGiao` (Chấm cam): Đơn đang trong quá trình mài lắp hoặc giao hàng ('Đang mài lắp', 'Đang giao hàng')
 */
function demThongBaoQuanTri($conn) {
    $donMoiCanXuLy = (int)$conn->query("SELECT COUNT(*) FROM THANHTOAN WHERE trangThai NOT IN ('Đã giao', 'Đã hủy', 'Đang mài lắp', 'Đang giao hàng')")->fetchColumn();
    $donDangMaiGiao = (int)$conn->query("SELECT COUNT(*) FROM THANHTOAN WHERE trangThai IN ('Đang mài lắp', 'Đang giao hàng')")->fetchColumn();
    $choKhieuNai = (int)$conn->query("SELECT COUNT(*) FROM KHIEUNAI WHERE trangThai = 'Chờ xử lý'")->fetchColumn();

    return [
        'donMoiCanXuLy'  => $donMoiCanXuLy,
        'donDangMaiGiao' => $donDangMaiGiao,
        'choKhieuNai'    => $choKhieuNai,
        'tongThongBao'   => $donMoiCanXuLy + $choKhieuNai
    ];
}
?>
