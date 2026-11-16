<?php
require_once 'xuly/functions.php';

$dsThuongHieu = layDanhSachThuongHieu($conn);
$dsSanPham = layDanhSachSanPham($conn);
$dsNhanVien = isAdmin() ? layDanhSachNhanVien($conn) : [];
$dsVoucher = isStaffOrAdmin() ? layDanhSachVoucher($conn) : [];
$dsLichSuVoucher = isAdmin() ? layLichSuVoucher($conn) : [];

$activeTab = $_GET['tab'] ?? (isStaffOrAdmin() ? 'admin-product' : 'shop-view');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Việt MAC - Hệ Thống Cửa Hàng Mắt Kính</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <!-- THANH ĐIỀU HƯỚNG TỰ ĐỘNG HIỂN THỊ MENU QUẢN TRỊ KHI ĐĂNG NHẬP -->
    <nav class="navbar">
        <div style="display:flex; align-items:center; gap:12px;">
            <button class="menu-toggle" onclick="toggleMobileMenu()">☰</button>
            <a href="index.php" class="brand-logo">Việt MAC</a>
        </div>

        <ul class="nav-links">
            <li><a class="<?= $activeTab === 'shop-view' ? 'active' : '' ?>" onclick="switchTab('shop-view', this)">Kính Cận / Kính Mát (Shop)</a></li>
            <li><a onclick="alert('Tính năng Tra cứu đơn kính sẽ mở rộng ở giai đoạn tiếp theo!')">Đơn Kính Của Bạn</a></li>

            <?php if (isStaffOrAdmin()): ?>
                <li>
                    <a class="nav-admin-badge <?= $activeTab === 'admin-product' ? 'active' : '' ?>" 
                       onclick="switchTab('admin-product', this)">Quản Lý Sản Phẩm</a>
                </li>
                <li>
                    <a class="nav-admin-badge <?= $activeTab === 'admin-brand' ? 'active' : '' ?>" 
                       onclick="switchTab('admin-brand', this)">Quản Lý Thương Hiệu</a>
                </li>
                <li>
                    <a class="nav-admin-badge <?= $activeTab === 'admin-voucher' ? 'active' : '' ?>" 
                       onclick="switchTab('admin-voucher', this)">Quản Lý Voucher</a>
                </li>
            <?php endif; ?>

            <?php if (isAdmin()): ?>
                <li>
                    <a class="nav-admin-badge <?= $activeTab === 'admin-staff' ? 'active' : '' ?>" 
                       onclick="switchTab('admin-staff', this)">Quản Lý Nhân Viên</a>
                </li>
            <?php endif; ?>
        </ul>

        <div class="user-Area">
            <?php if (isLogged()): ?>
                <span>Xin chào, <strong><?= htmlspecialchars($_SESSION['user']['hoTen']) ?></strong></span>
                <span class="role-tag"><?= htmlspecialchars($_SESSION['user']['tenVaiTro']) ?></span>
                <a href="xuly/auth_action.php?action=logout" class="btn btn-outline btn-sm">Đăng xuất</a>
            <?php else: ?>
                <button class="btn btn-outline btn-sm" onclick="openModal('registerModal')">Đăng ký</button>
                <button class="btn btn-dark btn-sm" onclick="openModal('loginModal')">Đăng nhập</button>
            <?php endif; ?>
        </div>
    </nav>

    <div class="container">
        <!-- HIỂN THỊ THÔNG BÁO XỬ LÝ -->
        <?php if (!empty($_SESSION['msg'])): ?>
            <div class="alert alert-success"><?= $_SESSION['msg']; unset($_SESSION['msg']); ?></div>
        <?php endif; ?>
        <?php if (!empty($_SESSION['err'])): ?>
            <div class="alert alert-error"><?= $_SESSION['err']; unset($_SESSION['err']); ?></div>
        <?php endif; ?>

        <!-- =======================================================
             PHÂN HỆ 1: QUẢN LÝ SẢN PHẨM (DÀNH CHO ADMIN & NHÂN VIÊN)
             ======================================================= -->
        <?php if (isStaffOrAdmin()): ?>
        <section id="admin-product" class="section-view <?= $activeTab === 'admin-product' ? 'active' : '' ?>">
            <div class="section-header">
                <div>
                    <h2>Quản Lý Sản Phẩm & Tồn Kho - Việt MAC</h2>
                    <p style="font-size:13px; color:#6b7280;">Ảnh sản phẩm được lưu tại thư mục: <strong>images/anh_sp/</strong></p>
                </div>
                <button class="btn btn-dark" onclick="openProductModal('add')">+ Thêm Sản Phẩm Mới</button>
            </div>

            <div class="card">
                <table>
                    <thead>
                        <tr>
                            <th>Mã SP</th>
                            <th>Ảnh</th>
                            <th>Tên Sản Phẩm</th>
                            <th>Thương Hiệu</th>
                            <th>Thông Số Gọng</th>
                            <th>Giá Bán</th>
                            <th>Tồn Thực / Giữ Tạm</th>
                            <th>Trạng Thái</th>
                            <th>Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($dsSanPham)): ?>
                            <tr><td colspan="9" style="text-align:center;">Chưa có sản phẩm nào. Hãy thêm thương hiệu và sản phẩm mới!</td></tr>
                        <?php else: ?>
                            <?php foreach ($dsSanPham as $sp): ?>
                            <tr>
                                <td><strong><?= $sp['maSanPham'] ?></strong></td>
                                <td>
                                    <?php if (!empty($sp['hinhAnh'])): ?>
                                        <img src="images/anh_sp/<?= htmlspecialchars($sp['hinhAnh']) ?>" class="thumb-img" alt="">
                                    <?php else: ?>
                                        <span style="font-size:12px;color:#9ca3af;">Chưa có ảnh</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($sp['tenSanPham']) ?></td>
                                <td><?= htmlspecialchars($sp['tenThuongHieu'] ?? '') ?></td>
                                <td style="font-size:12px; color:#4b5563;">
                                    Size: <?= htmlspecialchars($sp['kichThuoc'] ?? '-') ?> | Màu: <?= htmlspecialchars($sp['mauSac'] ?? '-') ?><br>
                                    Chất liệu: <?= htmlspecialchars($sp['vatLieu'] ?? '-') ?> (<?= htmlspecialchars($sp['gioiTinh'] ?? '-') ?>)
                                </td>
                                <td><strong><?= number_format($sp['gia'], 0, ',', '.') ?> đ</strong></td>
                                <td>
                                    Tồn: <strong><?= $sp['soLuong'] ?></strong> | Giữ 15p: <strong><?= $sp['soLuongDat'] ?></strong>
                                </td>
                                <td>
                                    <?php if ($sp['trangThai'] === 'Đang bán'): ?>
                                        <span class="status-badge status-active">Đang bán</span>
                                    <?php elseif ($sp['trangThai'] === 'Tạm hết' || $sp['trangThai'] === 'Tạm ngưng bán'): ?>
                                        <span class="status-badge status-warning"><?= htmlspecialchars($sp['trangThai']) ?></span>
                                    <?php else: ?>
                                        <span class="status-badge status-stopped"><?= htmlspecialchars($sp['trangThai']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-outline btn-sm" 
                                            onclick='openProductModal("edit", <?= json_encode($sp, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>Sửa</button>
                                    <?php if (isAdmin()): ?>
                                        <a href="xuly/sanpham_action.php?action=delete&id=<?= $sp['maSanPham'] ?>" 
                                           class="btn btn-danger btn-sm"
                                           onclick="return confirm('Xác nhận xóa sản phẩm <?= $sp['maSanPham'] ?>?')">Xóa</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- =======================================================
             PHÂN HỆ 2: QUẢN LÝ THƯƠNG HIỆU (THUONGHIEU + LOGO)
             ======================================================= -->
        <section id="admin-brand" class="section-view <?= $activeTab === 'admin-brand' ? 'active' : '' ?>">
            <div class="section-header">
                <div>
                    <h2>Quản Lý Thương Hiệu Đối Tác</h2>
                    <p style="font-size:13px; color:#6b7280;">Logo thương hiệu được lưu tại thư mục: <strong>images/logo_thuonghieu/</strong></p>
                </div>
                <button class="btn btn-dark" onclick="openBrandModal('add')">+ Thêm Thương Hiệu</button>
            </div>

            <div class="card">
                <table>
                    <thead>
                        <tr>
                            <th>Mã TH</th>
                            <th>Logo</th>
                            <th>Tên Thương Hiệu</th>
                            <th>Mô Tả</th>
                            <th>Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($dsThuongHieu)): ?>
                            <tr><td colspan="5" style="text-align:center;">Chưa có thương hiệu nào.</td></tr>
                        <?php else: ?>
                            <?php foreach ($dsThuongHieu as $th): ?>
                            <tr>
                                <td><strong><?= $th['maThuongHieu'] ?></strong></td>
                                <td>
                                    <?php if (!empty($th['logo'])): ?>
                                        <img src="images/logo_thuonghieu/<?= htmlspecialchars($th['logo']) ?>" class="thumb-img" alt="">
                                    <?php else: ?>
                                        <span style="font-size:12px;color:#9ca3af;">Không có logo</span>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?= htmlspecialchars($th['tenThuongHieu']) ?></strong></td>
                                <td><?= htmlspecialchars($th['moTa'] ?? '') ?></td>
                                <td>
                                    <button class="btn btn-outline btn-sm" 
                                            onclick='openBrandModal("edit", <?= json_encode($th, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>Sửa</button>
                                    <?php if (isAdmin()): ?>
                                        <a href="xuly/thuonghieu_action.php?action=delete&id=<?= $th['maThuongHieu'] ?>" 
                                           class="btn btn-danger btn-sm"
                                           onclick="return confirm('Xóa thương hiệu <?= $th['tenThuongHieu'] ?>?')">Xóa</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- =======================================================
             PHÂN HỆ 3: QUẢN LÝ VOUCHER KHUYẾN MÃI (VOUCHER + LICHSUVOUCHER)
             Bám sát Quy trình 2.2.8, Bảng 3.16, Bảng 3.17 và Use case Bảng 3.33
             ======================================================= -->
        <section id="admin-voucher" class="section-view <?= $activeTab === 'admin-voucher' ? 'active' : '' ?>">
            <div class="section-header">
                <div>
                    <h2>Quản Lý Chiến Dịch Voucher Khuyến Mãi</h2>
                    <p style="font-size:13px; color:#6b7280;">
                        Bảng <strong>VOUCHER</strong> &amp; <strong>LICHSUVOUCHER</strong> (Admin phát hành/chỉnh sửa; Nhân viên tra cứu để áp dụng cho khách)
                    </p>
                </div>
                <?php if (isAdmin()): ?>
                    <button class="btn btn-dark" onclick="openVoucherModal('add')">+ Phát Hành Voucher Mới</button>
                <?php endif; ?>
            </div>

            <div class="card" style="margin-bottom: 24px;">
                <table>
                    <thead>
                        <tr>
                            <th>Mã VC</th>
                            <th>Mã Code</th>
                            <th>Loại Giảm</th>
                            <th>Giá Trị Giảm</th>
                            <th>Đơn Tối Thiểu</th>
                            <th>Lượt Dùng (Đã dùng / Giới hạn)</th>
                            <th>Ngày Hết Hạn</th>
                            <th>Trạng Thái</th>
                            <?php if (isAdmin()): ?>
                                <th>Thao Tác</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($dsVoucher)): ?>
                            <tr><td colspan="9" style="text-align:center;">Chưa có chiến dịch mã Voucher nào được phát hành.</td></tr>
                        <?php else: ?>
                            <?php 
                            $nowObj = new DateTime();
                            foreach ($dsVoucher as $vc): 
                                $hetHanObj = new DateTime($vc['ngayHetHan']);
                                $isExpired = ($hetHanObj <= $nowObj);
                                $isOut = ($vc['gioiHanDung'] !== null && (int)$vc['daDung'] >= (int)$vc['gioiHanDung']);
                            ?>
                            <tr>
                                <td><strong><?= $vc['maVoucher'] ?></strong></td>
                                <td><span class="role-tag" style="background:#111827;color:#fff;letter-spacing:1px;"><?= htmlspecialchars($vc['code']) ?></span></td>
                                <td><?= htmlspecialchars($vc['loaiGiam']) ?></td>
                                <td>
                                    <?php if ($vc['loaiGiam'] === 'Phần trăm' || $vc['loaiGiam'] === '%'): ?>
                                        <strong><?= (float)$vc['giaTriGiam'] ?>%</strong>
                                    <?php else: ?>
                                        <strong><?= number_format($vc['giaTriGiam'], 0, ',', '.') ?> đ</strong>
                                    <?php endif; ?>
                                </td>
                                <td><?= number_format($vc['donHangToiThieu'], 0, ',', '.') ?> đ</td>
                                <td>
                                    <strong><?= (int)$vc['daDung'] ?></strong> / 
                                    <?= $vc['gioiHanDung'] !== null ? (int)$vc['gioiHanDung'] : 'Không giới hạn' ?>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($vc['ngayHetHan'])) ?></td>
                                <td>
                                    <?php if ($isExpired): ?>
                                        <span class="status-badge status-stopped">Hết hạn</span>
                                    <?php elseif ($isOut): ?>
                                        <span class="status-badge status-warning">Hết lượt dùng</span>
                                    <?php else: ?>
                                        <span class="status-badge status-active">Đang hiệu lực</span>
                                    <?php endif; ?>
                                </td>
                                <?php if (isAdmin()): ?>
                                <td>
                                    <button class="btn btn-outline btn-sm" 
                                            onclick='openVoucherModal("edit", <?= json_encode($vc, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>Sửa</button>
                                    <?php if (!$isExpired && !$isOut): ?>
                                        <a href="xuly/voucher_action.php?action=disable&id=<?= $vc['maVoucher'] ?>" 
                                           class="btn btn-outline btn-sm"
                                           onclick="return confirm('Vô hiệu hóa sớm mã <?= $vc['code'] ?>?')">Vô hiệu</a>
                                    <?php endif; ?>
                                    <a href="xuly/voucher_action.php?action=delete&id=<?= $vc['maVoucher'] ?>" 
                                       class="btn btn-danger btn-sm"
                                       onclick="return confirm('Xác nhận xóa hoặc hủy mã Voucher <?= $vc['code'] ?>?')">Xóa</a>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if (isAdmin()): ?>
            <div class="section-header" style="margin-top:28px;">
                <div>
                    <h3>Lịch Sử Sử Dụng Voucher (Bảng LICHSUVOUCHER)</h3>
                    <p style="font-size:13px; color:#6b7280;">Truy vết các đơn hàng đã áp dụng mã giảm giá nhằm chống lạm dụng khuyến mãi</p>
                </div>
            </div>
            <div class="card">
                <table>
                    <thead>
                        <tr>
                            <th>Mã Lịch Sử</th>
                            <th>Mã Code</th>
                            <th>Mã Đơn Hàng</th>
                            <th>Khách Nhận Hàng</th>
                            <th>Tổng Tiền Đơn</th>
                            <th>Thời Điểm Dùng</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($dsLichSuVoucher)): ?>
                            <tr><td colspan="6" style="text-align:center;">Chưa có đơn hàng nào ghi nhận trong bảng LICHSUVOUCHER.</td></tr>
                        <?php else: ?>
                            <?php foreach ($dsLichSuVoucher as $ls): ?>
                            <tr>
                                <td><strong><?= $ls['maLichSu'] ?></strong></td>
                                <td><strong><?= htmlspecialchars($ls['code']) ?></strong></td>
                                <td><?= htmlspecialchars($ls['maDonHang']) ?></td>
                                <td><?= htmlspecialchars($ls['tenNN']) ?> (<?= htmlspecialchars($ls['soDienThoai']) ?>)</td>
                                <td><strong><?= number_format($ls['tongTien'], 0, ',', '.') ?> đ</strong></td>
                                <td><?= date('d/m/Y H:i:s', strtotime($ls['ngayDung'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </section>
        <?php endif; ?>

        <!-- =======================================================
             PHÂN HỆ 4: QUẢN LÝ NHÂN VIÊN (CHỈ DÀNH CHO ADMIN VT01)
             ======================================================= -->
        <?php if (isAdmin()): ?>
        <section id="admin-staff" class="section-view <?= $activeTab === 'admin-staff' ? 'active' : '' ?>">
            <div class="section-header">
                <div>
                    <h2>Quản Lý Nhân Viên - Việt MAC</h2>
                    <p style="font-size:13px; color:#6b7280;">Phân quyền quản lý tài khoản nhân viên (Vai trò: NhanVien - VT02)</p>
                </div>
                <button class="btn btn-dark" onclick="openStaffModal('add')">+ Thêm Nhân Viên Mới</button>
            </div>

            <div class="card">
                <table>
                    <thead>
                        <tr>
                            <th>Mã Người Dùng</th>
                            <th>Họ và Tên</th>
                            <th>Email Đăng Nhập</th>
                            <th>Vai Trò</th>
                            <th>Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($dsNhanVien)): ?>
                            <tr><td colspan="5" style="text-align:center;">Chưa có nhân viên nào trong hệ thống.</td></tr>
                        <?php else: ?>
                            <?php foreach ($dsNhanVien as $nv): ?>
                            <tr>
                                <td><strong><?= $nv['maNguoiDung'] ?></strong></td>
                                <td><?= htmlspecialchars($nv['hoTen']) ?></td>
                                <td><?= htmlspecialchars($nv['email']) ?></td>
                                <td><span class="role-tag">Nhân Viên (VT02)</span></td>
                                <td>
                                    <button class="btn btn-outline btn-sm" 
                                            onclick='openStaffModal("edit", <?= json_encode($nv, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>Sửa</button>
                                    <a href="xuly/nhanvien_action.php?action=delete&id=<?= $nv['maNguoiDung'] ?>" 
                                       class="btn btn-danger btn-sm"
                                       onclick="return confirm('Xác nhận xóa tài khoản nhân viên <?= htmlspecialchars($nv['hoTen']) ?>?')">Xóa</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <?php endif; ?>

        <!-- =======================================================
             PHÂN HỆ 5: TRANG CỬA HÀNG VIỆT MAC & POPUP CHỌN TRÒNG
             ======================================================= -->
        <section id="shop-view" class="section-view <?= $activeTab === 'shop-view' ? 'active' : '' ?>">
            <div class="section-header">
                <div>
                    <h2>Bộ Sưu Tập Mắt Kính Việt MAC</h2>
                    <p style="font-size:13px; color:#6b7280;">Chọn gọng kính chính hãng, cấu hình tròng kính theo độ cận và áp dụng mã Voucher ưu đãi</p>
                </div>
            </div>

            <div class="product-grid">
                <?php 
                $soSanPhamHienThi = 0;
                foreach ($dsSanPham as $sp): 
                    if (in_array($sp['trangThai'], ['Ngừng bán', 'Tạm ngưng bán'])) {
                        continue;
                    }
                    $soSanPhamHienThi++;
                    $tonKhaDung = (int)$sp['soLuong'] - (int)$sp['soLuongDat'];
                    $coTheMua = ($sp['trangThai'] === 'Đang bán' && $tonKhaDung > 0);
                ?>
                    <div class="product-card <?= !$coTheMua ? 'product-out' : '' ?>">
                        <img src="<?= !empty($sp['hinhAnh']) ? 'images/anh_sp/' . htmlspecialchars($sp['hinhAnh']) : 'https://via.placeholder.com/260x140?text=Viet+MAC' ?>" alt="">
                        <div class="product-brand"><?= htmlspecialchars($sp['tenThuongHieu'] ?? 'Việt MAC') ?></div>
                        <div class="product-title"><?= htmlspecialchars($sp['tenSanPham']) ?></div>
                        <div style="font-size:12px; color:#6b7280; margin-bottom:8px;">
                            Màu: <?= htmlspecialchars($sp['mauSac'] ?? 'Đen') ?> | Size: <?= htmlspecialchars($sp['kichThuoc'] ?? 'M') ?>
                        </div>
                        <div class="product-price"><?= number_format($sp['gia'], 0, ',', '.') ?> đ</div>

                        <?php if ($coTheMua): ?>
                            <button class="btn btn-dark" style="width:100%;" 
                                    onclick="openLensWizard('<?= $sp['maSanPham'] ?>', '<?= htmlspecialchars($sp['tenSanPham'], ENT_QUOTES) ?>', <?= $sp['gia'] ?>)">
                                Chọn Tròng Kính & Mua
                            </button>
                        <?php else: ?>
                            <button class="btn btn-disabled" style="width:100%;" disabled>
                                Tạm Hết Hàng
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <?php if ($soSanPhamHienThi === 0): ?>
                    <p style="grid-column: 1 / -1; text-align:center; color:#6b7280; padding: 40px 0;">
                        Hiện chưa có sản phẩm nào đang mở bán.
                    </p>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <!-- =======================================================
         MODAL 1: ĐĂNG NHẬP HỆ THỐNG
         ======================================================= -->
    <div id="loginModal" class="modal-overlay">
        <div class="modal-box" style="max-width: 420px;">
            <div class="modal-header">
                <h3>Đăng Nhập Việt MAC</h3>
                <button class="btn btn-outline btn-sm" onclick="closeModal('loginModal')">✕</button>
            </div>
            <form action="xuly/auth_action.php" method="POST">
                <input type="hidden" name="action" value="login">
                <div class="form-group">
                    <label>Email đăng nhập</label>
                    <input type="email" name="email" placeholder="admin@kinhmat.vn" required>
                </div>
                <div class="form-group">
                    <label>Mật khẩu</label>
                    <input type="password" name="matKhau" placeholder="Mật khẩu mẫu: 123" required>
                </div>
                <p style="font-size:12px; color:#6b7280; margin-bottom:12px;">
                    TK mẫu: <strong>admin@kinhmat.vn</strong>, <strong>nhanvien@kinhmat.vn</strong>, <strong>nguyenvanan@gmail.com</strong> (Pass: <strong>123</strong>)
                </p>
                <button type="submit" class="btn btn-dark" style="width:100%;">Đăng Nhập</button>
                <div class="auth-switch-text">
                    Chưa có tài khoản thành viên? <a onclick="switchAuthModal('loginModal', 'registerModal')">Đăng ký ngay</a>
                </div>
            </form>
        </div>
    </div>

    <!-- =======================================================
         MODAL 2: ĐĂNG KÝ TÀI KHOẢN KHÁCH HÀNG (NGUOIDUNG + KHACHHANG)
         ======================================================= -->
    <div id="registerModal" class="modal-overlay">
        <div class="modal-box" style="max-width: 560px;">
            <div class="modal-header">
                <div>
                    <h3>Đăng Ký Thành Viên Việt MAC</h3>
                    <p style="font-size:12px; color:#6b7280;">Lưu trữ thông tin giao hàng, đơn kính thuốc & nhận ưu đãi Voucher</p>
                </div>
                <button class="btn btn-outline btn-sm" onclick="closeModal('registerModal')">✕</button>
            </div>
            <form action="xuly/auth_action.php" method="POST">
                <input type="hidden" name="action" value="register">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Họ và tên *</label>
                        <input type="text" name="hoTen" required placeholder="VD: Nguyễn Văn A">
                    </div>
                    <div class="form-group">
                        <label>Số điện thoại liên lạc *</label>
                        <input type="text" name="soDienThoai" required placeholder="VD: 0901234567">
                    </div>
                    <div class="form-group">
                        <label>Địa chỉ Email *</label>
                        <input type="email" name="email" required placeholder="khachhang@gmail.com">
                    </div>
                    <div class="form-group">
                        <label>Mật khẩu *</label>
                        <input type="password" name="matKhau" required placeholder="Nhập mật khẩu bảo mật">
                    </div>
                    <div class="form-group">
                        <label>Giới tính</label>
                        <select name="gioiTinh">
                            <option value="Nam">Nam</option>
                            <option value="Nữ">Nữ</option>
                            <option value="Khác">Khác</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Ngày sinh</label>
                        <input type="date" name="ngaySinh">
                    </div>
                    <div class="form-group full">
                        <label>Địa chỉ giao hàng mặc định *</label>
                        <input type="text" name="diaChi" required placeholder="Số nhà, Tên đường, Phường/Xã, Quận/Huyện, Tỉnh/TP">
                    </div>
                </div>
                <button type="submit" class="btn btn-dark" style="width:100%; margin-top:6px;">Đăng Ký Tài Khoản</button>
                <div class="auth-switch-text">
                    Đã có tài khoản? <a onclick="switchAuthModal('registerModal', 'loginModal')">Đăng nhập ngay</a>
                </div>
            </form>
        </div>
    </div>

    <!-- =======================================================
         MODAL 3: THÊM / SỬA THƯƠNG HIỆU
         ======================================================= -->
    <div id="brandModal" class="modal-overlay">
        <div class="modal-box" style="max-width: 480px;">
            <div class="modal-header">
                <h3 id="brandModalTitle">Thêm Thương Hiệu</h3>
                <button class="btn btn-outline btn-sm" onclick="closeModal('brandModal')">✕</button>
            </div>
            <form id="brandForm" action="xuly/thuonghieu_action.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" id="brandFormAction" value="add">
                <input type="hidden" name="maThuongHieu" id="maThuongHieu">

                <div class="form-group">
                    <label>Tên thương hiệu *</label>
                    <input type="text" name="tenThuongHieu" id="tenThuongHieu" required placeholder="VD: Ray-Ban, Gentle Monster...">
                </div>
                <div class="form-group">
                    <label>Logo thương hiệu (Lưu vào images/logo_thuonghieu/)</label>
                    <input type="file" name="logo" accept="image/*">
                </div>
                <div class="form-group">
                    <label>Mô tả thương hiệu</label>
                    <textarea name="moTa" id="moTaTH" rows="3"></textarea>
                </div>
                <button type="submit" class="btn btn-dark" style="width:100%;">Lưu Thương Hiệu</button>
            </form>
        </div>
    </div>

    <!-- =======================================================
         MODAL 4: THÊM / SỬA SẢN PHẨM (SANPHAM + CHITIETSANPHAM + ANHSP + TONKO)
         ======================================================= -->
    <div id="productModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <h3 id="productModalTitle">Thêm Sản Phẩm Kính</h3>
                <button class="btn btn-outline btn-sm" onclick="closeModal('productModal')">✕</button>
            </div>
            <form id="productForm" action="xuly/sanpham_action.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" id="productFormAction" value="add">
                <input type="hidden" name="maSanPham" id="maSanPham">

                <div class="form-grid">
                    <div class="form-group">
                        <label>Tên sản phẩm *</label>
                        <input type="text" name="tenSanPham" id="tenSanPham" required>
                    </div>
                    <div class="form-group">
                        <label>Thương hiệu *</label>
                        <select name="maThuongHieu" id="maThuongHieuSP" required>
                            <option value="">-- Chọn thương hiệu --</option>
                            <?php foreach ($dsThuongHieu as $th): ?>
                                <option value="<?= $th['maThuongHieu'] ?>"><?= htmlspecialchars($th['tenThuongHieu']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Giá bán niêm yết (VNĐ) *</label>
                        <input type="number" name="gia" id="gia" required min="0">
                    </div>
                    <div class="form-group">
                        <label>Số lượng tồn kho vật lý (TONKO) *</label>
                        <input type="number" name="soLuong" id="soLuong" required min="0" value="10">
                    </div>
                    <div class="form-group">
                        <label>Màu sắc gọng *</label>
                        <input type="text" name="mauSac" id="mauSac" required placeholder="Đen nhám, Bạc, Đồi mồi...">
                    </div>
                    <div class="form-group">
                        <label>Chất liệu (vatLieu)</label>
                        <input type="text" name="vatLieu" id="vatLieu" placeholder="Titanium, Nhựa Acetate...">
                    </div>
                    <div class="form-group">
                        <label>Kích thước chung</label>
                        <select name="kichThuoc" id="kichThuoc">
                            <option value="S">Size S (Nhỏ)</option>
                            <option value="M" selected>Size M (Vừa)</option>
                            <option value="L">Size L (Rộng)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Giới tính</label>
                        <select name="gioiTinh" id="gioiTinh">
                            <option value="Unisex">Unisex</option>
                            <option value="Nam">Nam</option>
                            <option value="Nữ">Nữ</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Rộng khung / Cầu mũi / Thấu kính (mm)</label>
                        <div style="display:flex; gap:6px;">
                            <input type="number" step="0.1" name="chieuRongKhung" id="chieuRongKhung" placeholder="Khung (140)">
                            <input type="number" step="0.1" name="chieuRongCau" id="chieuRongCau" placeholder="Cầu (18)">
                            <input type="number" step="0.1" name="chieuRongThauKinh" id="chieuRongThauKinh" placeholder="Tròng (52)">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Cao ống kính / Dài càng kính (mm)</label>
                        <div style="display:flex; gap:6px;">
                            <input type="number" step="0.1" name="chieuCaoOngKinh" id="chieuCaoOngKinh" placeholder="Cao (42)">
                            <input type="number" step="0.1" name="chieuDaiThaiDuong" id="chieuDaiThaiDuong" placeholder="Càng (145)">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Trạng thái kinh doanh</label>
                        <select name="trangThai" id="trangThai">
                            <option value="Đang bán">Đang bán (Hiện & Cho phép mua)</option>
                            <option value="Tạm hết">Tạm hết (Hiện & Khóa nút mua)</option>
                            <option value="Tạm ngưng bán">Tạm ngưng bán (Ẩn khỏi trang Shop)</option>
                            <option value="Ngừng bán">Ngừng bán (Ẩn khỏi trang Shop)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Ảnh sản phẩm (Lưu vào images/anh_sp/)</label>
                        <input type="file" name="hinhAnh" accept="image/*">
                    </div>
                    <div class="form-group full">
                        <label>Mô tả sản phẩm</label>
                        <textarea name="moTa" id="moTaSP" rows="2"></textarea>
                    </div>
                </div>
                <button type="submit" class="btn btn-dark" style="width:100%;">Lưu Sản Phẩm</button>
            </form>
        </div>
    </div>

    <!-- =======================================================
         MODAL 5: THÊM / SỬA NHÂN VIÊN (CHỈ ADMIN VT01)
         ======================================================= -->
    <div id="staffModal" class="modal-overlay">
        <div class="modal-box" style="max-width: 480px;">
            <div class="modal-header">
                <h3 id="staffModalTitle">Thêm Nhân Viên Mới</h3>
                <button class="btn btn-outline btn-sm" onclick="closeModal('staffModal')">✕</button>
            </div>
            <form id="staffForm" action="xuly/nhanvien_action.php" method="POST">
                <input type="hidden" name="action" id="staffFormAction" value="add">
                <input type="hidden" name="maNguoiDung" id="maNguoiDungStaff">

                <div class="form-group">
                    <label>Họ và tên nhân viên *</label>
                    <input type="text" name="hoTen" id="hoTenStaff" required placeholder="VD: Trần Văn B">
                </div>
                <div class="form-group">
                    <label>Email đăng nhập *</label>
                    <input type="email" name="email" id="emailStaff" required placeholder="nhanvien2@kinhmat.vn">
                </div>
                <div class="form-group" id="matKhauGroup">
                    <label>Mật khẩu đăng nhập *</label>
                    <input type="password" name="matKhau" id="matKhauStaff" placeholder="Nhập mật khẩu (Mặc định: 123)">
                    <small style="color:#6b7280; font-size:11px;">Khi sửa thông tin, có thể để trống nếu không muốn đổi mật khẩu.</small>
                </div>
                <button type="submit" class="btn btn-dark" style="width:100%; margin-top:10px;">Lưu Thông Tin</button>
            </form>
        </div>
    </div>

    <!-- =======================================================
         MODAL 6: PHÁT HÀNH / SỬA VOUCHER KHUYẾN MÃI (BẢNG VOUCHER)
         Bám sát Bảng 3.16 & Bảng 3.33 trong báo cáo đồ án
         ======================================================= -->
    <div id="voucherModal" class="modal-overlay">
        <div class="modal-box" style="max-width: 560px;">
            <div class="modal-header">
                <div>
                    <h3 id="voucherModalTitle">Phát Hành Mã Voucher Mới</h3>
                    <p style="font-size:12px; color:#6b7280;">Thiết lập chiến dịch giảm giá theo tiền mặt hoặc phần trăm (Bảng VOUCHER)</p>
                </div>
                <button class="btn btn-outline btn-sm" onclick="closeModal('voucherModal')">✕</button>
            </div>
            <form id="voucherForm" action="xuly/voucher_action.php" method="POST">
                <input type="hidden" name="action" id="voucherFormAction" value="add">
                <input type="hidden" name="maVoucher" id="maVoucher">

                <div class="form-grid">
                    <div class="form-group">
                        <label>Chuỗi mã Code khuyến mãi *</label>
                        <input type="text" name="code" id="codeVoucher" required placeholder="VD: VIETMAC2026, TRIANKH01" style="text-transform:uppercase;">
                    </div>
                    <div class="form-group">
                        <label>Hình thức chiết khấu (loaiGiam) *</label>
                        <select name="loaiGiam" id="loaiGiam" required>
                            <option value="Tiền mặt">Theo số tiền cố định (VNĐ)</option>
                            <option value="Phần trăm">Theo phần trăm (%)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Giá trị giảm (giaTriGiam) *</label>
                        <input type="number" step="0.01" min="1" name="giaTriGiam" id="giaTriGiam" required placeholder="VD: 150000 hoặc 15">
                    </div>
                    <div class="form-group">
                        <label>Đơn hàng tối thiểu (donHangToiThieu - VNĐ)</label>
                        <input type="number" step="1000" min="0" name="donHangToiThieu" id="donHangToiThieu" value="0" placeholder="VD: 500000">
                    </div>
                    <div class="form-group">
                        <label>Giới hạn lượt dùng tối đa (gioiHanDung)</label>
                        <input type="number" min="1" name="gioiHanDung" id="gioiHanDung" placeholder="Để trống nếu không giới hạn">
                    </div>
                    <div class="form-group">
                        <label>Thời hạn hiệu lực cuối (ngayHetHan) *</label>
                        <input type="datetime-local" name="ngayHetHan" id="ngayHetHan" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-dark" style="width:100%; margin-top:8px;">Lưu Chiến Dịch Voucher</button>
            </form>
        </div>
    </div>

    <!-- =======================================================
         MODAL 7: POPUP CHỌN TRÒNG KÍNH TỪNG BƯỚC + ÁP DỤNG VOUCHER
         ======================================================= -->
    <div id="lensWizardModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <div>
                    <h3>Cấu Hình Tròng Kính & Độ Mắt - Việt MAC</h3>
                    <p id="wizardProductName" style="font-size:13px; color:#6b7280;"></p>
                </div>
                <button class="btn btn-outline btn-sm" onclick="closeModal('lensWizardModal')">✕</button>
            </div>

            <div class="step-indicator">
                <div class="step-dot active">Bước 1: Nhu cầu</div>
                <div class="step-dot">Bước 2: Độ mắt (SPH/CYL/PD)</div>
                <div class="step-dot">Bước 3: Chọn tròng & Voucher</div>
            </div>

            <!-- BƯỚC 1 -->
            <div id="step1" class="lens-step active">
                <div class="option-box" onclick="goToStep(2)">
                    <div>
                        <strong>Kính có độ (Cận / Viễn / Loạn thị)</strong>
                        <p style="font-size:12px; color:#6b7280;">Cắt tròng theo thông số đo mắt cá nhân (KETQUADOMAT)</p>
                    </div>
                    <span>→</span>
                </div>
                <div class="option-box" onclick="goToStep(3)">
                    <div>
                        <strong>Kính không độ (Bảo vệ mắt / Chống ánh sáng xanh)</strong>
                        <p style="font-size:12px; color:#6b7280;">Bỏ qua bước nhập độ mắt, chuyển thẳng tới chọn lớp phủ tròng</p>
                    </div>
                    <span>→</span>
                </div>
            </div>

            <!-- BƯỚC 2 -->
            <div id="step2" class="lens-step">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Mắt Phải (OD) - Độ cận/viễn (SPH)</label>
                        <input type="text" placeholder="VD: -2.50">
                    </div>
                    <div class="form-group">
                        <label>Mắt Trái (OS) - Độ cận/viễn (SPH)</label>
                        <input type="text" placeholder="VD: -2.75">
                    </div>
                    <div class="form-group">
                        <label>Độ loạn (CYL) & Trục (AXIS)</label>
                        <input type="text" placeholder="VD: -0.50 / 180">
                    </div>
                    <div class="form-group">
                        <label>Khoảng cách đồng tử (PD - mm)</label>
                        <input type="number" placeholder="VD: 62">
                    </div>
                </div>
                <div style="display:flex; justify-content:space-between; margin-top:12px;">
                    <button class="btn btn-outline" onclick="goToStep(1)">← Quay lại</button>
                    <button class="btn btn-dark" onclick="goToStep(3)">Tiếp tục chọn tròng →</button>
                </div>
            </div>

            <!-- BƯỚC 3 -->
            <div id="step3" class="lens-step">
                <div class="option-box selected" onclick="selectLensOption(350000, this)">
                    <div>
                        <strong>Tròng Tiêu Chuẩn Chiết Suất 1.56</strong>
                        <p style="font-size:12px; color:#6b7280;">Chống phản quang, chống tia UV400 cơ bản</p>
                    </div>
                    <strong>+ 350.000 đ</strong>
                </div>
                <div class="option-box" onclick="selectLensOption(650000, this)">
                    <div>
                        <strong>Tròng Mỏng 1.60 + Chống Ánh Sáng Xanh</strong>
                        <p style="font-size:12px; color:#6b7280;">Bảo vệ mắt khi dùng máy tính, mỏng nhẹ hơn 20%</p>
                    </div>
                    <strong>+ 650.000 đ</strong>
                </div>
                <div class="option-box" onclick="selectLensOption(1200000, this)">
                    <div>
                        <strong>Tròng Siêu Mỏng 1.67 Đổi Màu Khói</strong>
                        <p style="font-size:12px; color:#6b7280;">Tự động sẫm màu khi ra nắng, trong suốt khi ở trong nhà</p>
                    </div>
                    <strong>+ 1.200.000 đ</strong>
                </div>

                <!-- KHUNG NHẬP MÃ KHUYẾN MÃI VOUCHER (Theo đúng Bảng 3.24) -->
                <div style="margin-top: 14px; padding: 12px; background: #f9fafb; border: 1px dashed #d1d5db; border-radius: 8px;">
                    <label style="font-size:13px; font-weight:600; display:block; margin-bottom:6px;">Mã khuyến mãi Voucher</label>
                    <div style="display:flex; gap:8px;">
                        <input type="text" id="wizardVoucherInput" placeholder="Nhập mã (VD: VIETMAC2026)" style="flex:1; padding:8px 10px; border:1px solid #d1d5db; border-radius:6px; text-transform:uppercase;">
                        <button type="button" class="btn btn-dark btn-sm" onclick="applyVoucherInWizard()">Áp dụng</button>
                        <button type="button" class="btn btn-outline btn-sm" onclick="removeVoucherInWizard()">Gỡ bỏ</button>
                    </div>
                    <p id="wizardVoucherMsg" style="font-size:12px; margin-top:6px;"></p>
                </div>

                <div style="display:flex; justify-content:space-between; align-items:center; margin-top:18px; padding-top:12px; border-top:1px solid #e5e7eb;">
                    <div>
                        <span style="font-size:13px; color:#6b7280;">Tổng cộng (Gọng + Tròng):</span>
                        <h3 id="wizardTotalPrice">0 đ</h3>
                        <p id="wizardDiscountInfo" style="display:none; font-size:12px; color:#065f46; font-weight:600;"></p>
                    </div>
                    <div style="display:flex; gap:8px;">
                        <button class="btn btn-outline" onclick="goToStep(2)">← Quay lại</button>
                        <button class="btn btn-dark" onclick="finishLensWizard()">Thêm Vào Giỏ Hàng</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/main.js"></script>
</body>
</html>
