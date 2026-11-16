<?php
require_once 'config.php';

/**
 * 1. Hàm tự động sinh mã (VD: SP01, TH01, ND01, KH01, VC01, ANH01...)
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
 * 2. Các hàm kiểm tra quyền đăng nhập
 * VT01: Admin (Toàn quyền) | VT02: Nhân viên | VT03: Khách hàng
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
 * 3. Lấy danh sách toàn bộ thương hiệu
 */
function layDanhSachThuongHieu($conn) {
    $sql = "SELECT * FROM THUONGHIEU ORDER BY maThuongHieu DESC";
    return $conn->query($sql)->fetchAll();
}

/**
 * 4. Lấy danh sách sản phẩm kèm Thương hiệu, Ảnh chính, Danh sách nhiều ảnh, Thông số chi tiết và Tồn kho
 */
function layDanhSachSanPham($conn) {
    $sql = "SELECT sp.*, 
                   th.tenThuongHieu, 
                   (SELECT hinhAnh FROM ANHSP a WHERE a.maSanPham = sp.maSanPham ORDER BY a.anhChinh DESC, a.maAnh ASC LIMIT 1) AS hinhAnh,
                   ct.kichThuoc, ct.chieuRongKhung, ct.chieuRongCau, ct.chieuRongThauKinh, 
                   ct.chieuCaoOngKinh, ct.chieuDaiThaiDuong, ct.mauSac, ct.gioiTinh, ct.vatLieu,
                   COALESCE(tk.soLuong, 0) AS soLuong, 
                   COALESCE(tk.soLuongDat, 0) AS soLuongDat
            FROM SANPHAM sp
            LEFT JOIN THUONGHIEU th ON sp.maThuongHieu = th.maThuongHieu
            LEFT JOIN CHITIETSANPHAM ct ON sp.maSanPham = ct.maSanPham
            LEFT JOIN TONKO tk ON sp.maSanPham = tk.maSanPham
            ORDER BY sp.maSanPham DESC";
    $products = $conn->query($sql)->fetchAll();

    // Lấy toàn bộ danh sách ảnh phụ/chính của từng sản phẩm trong bảng ANHSP
    $stmtImgs = $conn->prepare("SELECT maAnh, hinhAnh, anhChinh FROM ANHSP WHERE maSanPham = ? ORDER BY anhChinh DESC, maAnh ASC");
    foreach ($products as &$sp) {
        $stmtImgs->execute([$sp['maSanPham']]);
        $sp['dsAnh'] = $stmtImgs->fetchAll();
    }
    return $products;
}

/**
 * 5. Lấy danh sách nhân viên (maVaiTro = 'VT02')
 */
function layDanhSachNhanVien($conn) {
    $sql = "SELECT * FROM NGUOIDUNG WHERE maVaiTro = 'VT02' ORDER BY maNguoiDung DESC";
    return $conn->query($sql)->fetchAll();
}

/**
 * 6. Lấy danh sách toàn bộ Voucher khuyến mãi (Bảng VOUCHER)
 */
function layDanhSachVoucher($conn) {
    $sql = "SELECT * FROM VOUCHER ORDER BY maVoucher DESC";
    return $conn->query($sql)->fetchAll();
}

/**
 * 7. Lấy lịch sử sử dụng Voucher (Bảng LICHSUVOUCHER)
 */
function layLichSuVoucher($conn) {
    $sql = "SELECT ls.*, v.code, dh.tenNN, dh.soDienThoai, dh.tongTien
            FROM LICHSUVOUCHER ls
            JOIN VOUCHER v ON ls.maVoucher = v.maVoucher
            JOIN DONHANG dh ON ls.maDonHang = dh.maDonHang
            ORDER BY ls.ngayDung DESC";
    return $conn->query($sql)->fetchAll();
}
?>
