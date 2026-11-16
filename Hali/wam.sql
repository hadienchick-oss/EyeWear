SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `db_kinh_mat`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `db_kinh_mat`;

-- 1. Bảng VAITRO
DROP TABLE IF EXISTS `VAITRO`;
CREATE TABLE `VAITRO` (
  `maVaiTro` VARCHAR(20) NOT NULL,
  `tenVaiTro` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`maVaiTro`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Bảng NGUOIDUNG
DROP TABLE IF EXISTS `NGUOIDUNG`;
CREATE TABLE `NGUOIDUNG` (
  `maNguoiDung` VARCHAR(20) NOT NULL,
  `hoTen` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `matKhau` VARCHAR(255) NOT NULL,
  `maVaiTro` VARCHAR(20) NOT NULL,
  PRIMARY KEY (`maNguoiDung`),
  UNIQUE KEY `uk_nguoidung_email` (`email`),
  KEY `fk_nguoidung_vaitro` (`maVaiTro`),
  CONSTRAINT `fk_nguoidung_vaitro` FOREIGN KEY (`maVaiTro`) REFERENCES `VAITRO` (`maVaiTro`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Bảng KHACHHANG
DROP TABLE IF EXISTS `KHACHHANG`;
CREATE TABLE `KHACHHANG` (
  `maKhachHang` VARCHAR(20) NOT NULL,
  `maNguoiDung` VARCHAR(20) NOT NULL,
  `gioiTinh` VARCHAR(10) DEFAULT NULL,
  `ngaySinh` DATETIME DEFAULT NULL,
  `soDienThoai` VARCHAR(20) NOT NULL,
  `diaChi` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`maKhachHang`),
  UNIQUE KEY `uk_khachhang_nguoidung` (`maNguoiDung`),
  CONSTRAINT `fk_khachhang_nguoidung` FOREIGN KEY (`maNguoiDung`) REFERENCES `NGUOIDUNG` (`maNguoiDung`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Bảng THUONGHIEU (Đã thêm cột logo)
DROP TABLE IF EXISTS `THUONGHIEU`;
CREATE TABLE `THUONGHIEU` (
  `maThuongHieu` VARCHAR(20) NOT NULL,
  `tenThuongHieu` VARCHAR(100) NOT NULL,
  `moTa` TEXT DEFAULT NULL,
  `logo` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`maThuongHieu`),
  UNIQUE KEY `uk_thuonghieu_ten` (`tenThuongHieu`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Bảng SANPHAM
DROP TABLE IF EXISTS `SANPHAM`;
CREATE TABLE `SANPHAM` (
  `maSanPham` VARCHAR(20) NOT NULL,
  `tenSanPham` VARCHAR(200) NOT NULL,
  `gia` DECIMAL(15,2) NOT NULL,
  `trangThai` VARCHAR(30) NOT NULL,
  `moTa` TEXT DEFAULT NULL,
  `maThuongHieu` VARCHAR(20) NOT NULL,
  PRIMARY KEY (`maSanPham`),
  KEY `fk_sanpham_thuonghieu` (`maThuongHieu`),
  CONSTRAINT `fk_sanpham_thuonghieu` FOREIGN KEY (`maThuongHieu`) REFERENCES `THUONGHIEU` (`maThuongHieu`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5.1. Bảng ANHSP
DROP TABLE IF EXISTS `ANHSP`;
CREATE TABLE `ANHSP` (
  `maAnh` VARCHAR(20) NOT NULL,
  `maSanPham` VARCHAR(20) NOT NULL,
  `hinhAnh` VARCHAR(255) NOT NULL,
  `anhChinh` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`maAnh`),
  KEY `fk_anhsp_sanpham` (`maSanPham`),
  CONSTRAINT `fk_anhsp_sanpham` FOREIGN KEY (`maSanPham`) REFERENCES `SANPHAM` (`maSanPham`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Bảng CHITIETSANPHAM
DROP TABLE IF EXISTS `CHITIETSANPHAM`;
CREATE TABLE `CHITIETSANPHAM` (
  `maCTSanPham` VARCHAR(20) NOT NULL,
  `maSanPham` VARCHAR(20) NOT NULL,
  `kichThuoc` VARCHAR(50) DEFAULT NULL,
  `chieuRongKhung` FLOAT DEFAULT NULL,
  `chieuRongCau` FLOAT DEFAULT NULL,
  `chieuRongThauKinh` FLOAT DEFAULT NULL,
  `chieuCaoOngKinh` FLOAT DEFAULT NULL,
  `chieuDaiThaiDuong` FLOAT DEFAULT NULL,
  `mauSac` VARCHAR(50) NOT NULL,
  `gioiTinh` VARCHAR(20) DEFAULT NULL,
  `vatLieu` VARCHAR(50) DEFAULT NULL,
  `moTa` TEXT DEFAULT NULL,
  PRIMARY KEY (`maCTSanPham`),
  UNIQUE KEY `uk_ctsanpham_sanpham` (`maSanPham`),
  CONSTRAINT `fk_ctsanpham_sanpham` FOREIGN KEY (`maSanPham`) REFERENCES `SANPHAM` (`maSanPham`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Bảng KETQUADOMAT
DROP TABLE IF EXISTS `KETQUADOMAT`;
CREATE TABLE `KETQUADOMAT` (
  `maKetQuaDo` VARCHAR(20) NOT NULL,
  `maKhachHang` VARCHAR(20) NOT NULL,
  `ngayDo` DATETIME NOT NULL,
  `ketQua` TEXT NOT NULL,
  PRIMARY KEY (`maKetQuaDo`),
  KEY `fk_ketquadomat_khachhang` (`maKhachHang`),
  CONSTRAINT `fk_ketquadomat_khachhang` FOREIGN KEY (`maKhachHang`) REFERENCES `KHACHHANG` (`maKhachHang`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Bảng TRONGKINH
DROP TABLE IF EXISTS `TRONGKINH`;
CREATE TABLE `TRONGKINH` (
  `maTrongKinh` VARCHAR(20) NOT NULL,
  `maSanPham` VARCHAR(20) DEFAULT NULL,
  `maKetQuaDo` VARCHAR(20) DEFAULT NULL,
  `loai` VARCHAR(50) NOT NULL,
  `chietSuat` FLOAT NOT NULL,
  `tinhNang` VARCHAR(200) DEFAULT NULL,
  `gia` DECIMAL(15,2) NOT NULL,
  `vatLieu` VARCHAR(100) DEFAULT NULL,
  PRIMARY KEY (`maTrongKinh`),
  KEY `fk_trongkinh_sanpham` (`maSanPham`),
  KEY `fk_trongkinh_ketquado` (`maKetQuaDo`),
  CONSTRAINT `fk_trongkinh_sanpham` FOREIGN KEY (`maSanPham`) REFERENCES `SANPHAM` (`maSanPham`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_trongkinh_ketquado` FOREIGN KEY (`maKetQuaDo`) REFERENCES `KETQUADOMAT` (`maKetQuaDo`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Bảng TONKO
DROP TABLE IF EXISTS `TONKO`;
CREATE TABLE `TONKO` (
  `maTonKho` VARCHAR(20) NOT NULL,
  `maSanPham` VARCHAR(20) NOT NULL,
  `maTrongKinh` VARCHAR(20) DEFAULT NULL,
  `soLuong` INT NOT NULL DEFAULT 0,
  `soLuongDat` INT NOT NULL DEFAULT 0,
  `ngayCapNhat` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`maTonKho`),
  KEY `fk_tonko_sanpham` (`maSanPham`),
  KEY `fk_tonko_trongkinh` (`maTrongKinh`),
  CONSTRAINT `fk_tonko_sanpham` FOREIGN KEY (`maSanPham`) REFERENCES `SANPHAM` (`maSanPham`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_tonko_trongkinh` FOREIGN KEY (`maTrongKinh`) REFERENCES `TRONGKINH` (`maTrongKinh`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Bảng GIOHANG
DROP TABLE IF EXISTS `GIOHANG`;
CREATE TABLE `GIOHANG` (
  `maGioHang` VARCHAR(20) NOT NULL,
  `maKhachHang` VARCHAR(20) NOT NULL,
  `ngayTao` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`maGioHang`),
  UNIQUE KEY `uk_giohang_khachhang` (`maKhachHang`),
  CONSTRAINT `fk_giohang_khachhang` FOREIGN KEY (`maKhachHang`) REFERENCES `KHACHHANG` (`maKhachHang`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Bảng CHITIETGIOHANG
DROP TABLE IF EXISTS `CHITIETGIOHANG`;
CREATE TABLE `CHITIETGIOHANG` (
  `maCTGioHang` VARCHAR(20) NOT NULL,
  `maGioHang` VARCHAR(20) NOT NULL,
  `maSanPham` VARCHAR(20) NOT NULL,
  `soLuong` INT NOT NULL DEFAULT 1,
  PRIMARY KEY (`maCTGioHang`),
  KEY `fk_ctgiohang_giohang` (`maGioHang`),
  KEY `fk_ctgiohang_sanpham` (`maSanPham`),
  CONSTRAINT `fk_ctgiohang_giohang` FOREIGN KEY (`maGioHang`) REFERENCES `GIOHANG` (`maGioHang`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ctgiohang_sanpham` FOREIGN KEY (`maSanPham`) REFERENCES `SANPHAM` (`maSanPham`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Bảng VOUCHER
DROP TABLE IF EXISTS `VOUCHER`;
CREATE TABLE `VOUCHER` (
  `maVoucher` VARCHAR(20) NOT NULL,
  `code` VARCHAR(50) NOT NULL,
  `loaiGiam` VARCHAR(20) NOT NULL,
  `giaTriGiam` FLOAT NOT NULL,
  `donHangToiThieu` DECIMAL(15,2) DEFAULT 0.00,
  `gioiHanDung` INT DEFAULT NULL,
  `daDung` INT NOT NULL DEFAULT 0,
  `ngayHetHan` DATETIME NOT NULL,
  PRIMARY KEY (`maVoucher`),
  UNIQUE KEY `uk_voucher_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Bảng DONHANG
DROP TABLE IF EXISTS `DONHANG`;
CREATE TABLE `DONHANG` (
  `maDonHang` VARCHAR(20) NOT NULL,
  `maKhachHang` VARCHAR(20) DEFAULT NULL,
  `maVoucher` VARCHAR(20) DEFAULT NULL,
  `ngayDat` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `tongTien` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `tenNN` VARCHAR(100) NOT NULL,
  `soDienThoai` VARCHAR(20) NOT NULL,
  `diaChi` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`maDonHang`),
  KEY `fk_donhang_khachhang` (`maKhachHang`),
  KEY `fk_donhang_voucher` (`maVoucher`),
  CONSTRAINT `fk_donhang_khachhang` FOREIGN KEY (`maKhachHang`) REFERENCES `KHACHHANG` (`maKhachHang`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_donhang_voucher` FOREIGN KEY (`maVoucher`) REFERENCES `VOUCHER` (`maVoucher`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Bảng CHITIETDONHANG
DROP TABLE IF EXISTS `CHITIETDONHANG`;
CREATE TABLE `CHITIETDONHANG` (
  `maCTDDonHang` VARCHAR(20) NOT NULL,
  `maDonHang` VARCHAR(20) NOT NULL,
  `maSanPham` VARCHAR(20) NOT NULL,
  `maTrongKinh` VARCHAR(20) DEFAULT NULL,
  `donGia` DECIMAL(15,2) NOT NULL,
  PRIMARY KEY (`maCTDDonHang`),
  KEY `fk_ctdonhang_donhang` (`maDonHang`),
  KEY `fk_ctdonhang_sanpham` (`maSanPham`),
  KEY `fk_ctdonhang_trongkinh` (`maTrongKinh`),
  CONSTRAINT `fk_ctdonhang_donhang` FOREIGN KEY (`maDonHang`) REFERENCES `DONHANG` (`maDonHang`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ctdonhang_sanpham` FOREIGN KEY (`maSanPham`) REFERENCES `SANPHAM` (`maSanPham`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_ctdonhang_trongkinh` FOREIGN KEY (`maTrongKinh`) REFERENCES `TRONGKINH` (`maTrongKinh`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. Bảng THANHTOAN
DROP TABLE IF EXISTS `THANHTOAN`;
CREATE TABLE `THANHTOAN` (
  `maThanhToan` VARCHAR(20) NOT NULL,
  `maDonHang` VARCHAR(20) NOT NULL,
  `phuongThuc` VARCHAR(50) NOT NULL,
  `trangThai` VARCHAR(50) NOT NULL,
  `soTien` DECIMAL(15,2) NOT NULL,
  PRIMARY KEY (`maThanhToan`),
  UNIQUE KEY `uk_thanhtoan_donhang` (`maDonHang`),
  CONSTRAINT `fk_thanhtoan_donhang` FOREIGN KEY (`maDonHang`) REFERENCES `DONHANG` (`maDonHang`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. Bảng DANHGIA
DROP TABLE IF EXISTS `DANHGIA`;
CREATE TABLE `DANHGIA` (
  `maDanhGia` VARCHAR(20) NOT NULL,
  `maSanPham` VARCHAR(20) NOT NULL,
  `maKhachHang` VARCHAR(20) NOT NULL,
  `sao` INT NOT NULL,
  `binhLuan` TEXT DEFAULT NULL,
  PRIMARY KEY (`maDanhGia`),
  KEY `fk_danhgia_sanpham` (`maSanPham`),
  KEY `fk_danhgia_khachhang` (`maKhachHang`),
  CONSTRAINT `fk_danhgia_sanpham` FOREIGN KEY (`maSanPham`) REFERENCES `SANPHAM` (`maSanPham`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_danhgia_khachhang` FOREIGN KEY (`maKhachHang`) REFERENCES `KHACHHANG` (`maKhachHang`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_danhgia_sao` CHECK (`sao` >= 1 AND `sao` <= 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. Bảng LICHSUVOUCHER
DROP TABLE IF EXISTS `LICHSUVOUCHER`;
CREATE TABLE `LICHSUVOUCHER` (
  `maLichSu` VARCHAR(20) NOT NULL,
  `maVoucher` VARCHAR(20) NOT NULL,
  `maDonHang` VARCHAR(20) NOT NULL,
  `ngayDung` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`maLichSu`),
  KEY `fk_lichsuvoucher_voucher` (`maVoucher`),
  KEY `fk_lichsuvoucher_donhang` (`maDonHang`),
  CONSTRAINT `fk_lichsuvoucher_voucher` FOREIGN KEY (`maVoucher`) REFERENCES `VOUCHER` (`maVoucher`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_lichsuvoucher_donhang` FOREIGN KEY (`maDonHang`) REFERENCES `DONHANG` (`maDonHang`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- NẠP SẴN VAI TRÒ & 4 ACCOUNT (Pass: 123 -> MD5: 202cb962ac59075b964b07152d234b70)
-- ============================================================================

INSERT INTO `VAITRO` (`maVaiTro`, `tenVaiTro`) VALUES
('VT01', 'Admin'),
('VT02', 'NhanVien'),
('VT03', 'KhachHang');

INSERT INTO `NGUOIDUNG` (`maNguoiDung`, `hoTen`, `email`, `matKhau`, `maVaiTro`) VALUES
('ND01', 'Quản Trị Viên', 'admin@kinhmat.vn', '202cb962ac59075b964b07152d234b70', 'VT01'),
('ND02', 'Nhân Viên', 'nhanvien@kinhmat.vn', '202cb962ac59075b964b07152d234b70', 'VT02'),
('ND03', 'Nguyễn Văn An', 'nguyenvanan@gmail.com', '202cb962ac59075b964b07152d234b70', 'VT03'),
('ND04', 'Bích Ngọc', 'bichngoc@gmail.com', '202cb962ac59075b964b07152d234b70', 'VT03');

INSERT INTO `KHACHHANG` (`maKhachHang`, `maNguoiDung`, `gioiTinh`, `ngaySinh`, `soDienThoai`, `diaChi`) VALUES
('KH01', 'ND03', 'Nam', '2002-05-15 00:00:00', '0901234567', 'TP. Hồ Chí Minh'),
('KH02', 'ND04', 'Nữ', '2003-10-20 00:00:00', '0912345678', 'TP. Hồ Chí Minh');

SET FOREIGN_KEY_CHECKS = 1;