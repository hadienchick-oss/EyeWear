<?php
require_once 'config.php';

/**
 * 1. Hàm tự động sinh mã (VD: SP01, TH01, ND01, KH01, ANH01...)
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
 * VT01: Admin (Toàn quyền) | VT02: Nhân viên (Thêm/Sửa, không được Xóa) | VT03: Khách hàng
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
 * 4. Lấy danh sách sản phẩm kèm Thương hiệu, Ảnh chính, Thông số chi tiết và Tồn kho
 */
function layDanhSachSanPham($conn) {
    $sql = "SELECT sp.*, 
                   th.tenThuongHieu, 
                   anh.hinhAnh,
                   ct.kichThuoc, ct.chieuRongKhung, ct.chieuRongCau, ct.chieuRongThauKinh, 
                   ct.chieuCaoOngKinh, ct.chieuDaiThaiDuong, ct.mauSac, ct.gioiTinh, ct.vatLieu,
                   COALESCE(tk.soLuong, 0) AS soLuong, 
                   COALESCE(tk.soLuongDat, 0) AS soLuongDat
            FROM SANPHAM sp
            LEFT JOIN THUONGHIEU th ON sp.maThuongHieu = th.maThuongHieu
            LEFT JOIN ANHSP anh ON sp.maSanPham = anh.maSanPham AND anh.anhChinh = 1
            LEFT JOIN CHITIETSANPHAM ct ON sp.maSanPham = ct.maSanPham
            LEFT JOIN TONKO tk ON sp.maSanPham = tk.maSanPham
            ORDER BY sp.maSanPham DESC";
    return $conn->query($sql)->fetchAll();
}

/**
 * 5. Lấy danh sách nhân viên (maVaiTro = 'VT02')
 */
function layDanhSachNhanVien($conn) {
    $sql = "SELECT * FROM NGUOIDUNG WHERE maVaiTro = 'VT02' ORDER BY maNguoiDung DESC";
    return $conn->query($sql)->fetchAll();
}
?>
