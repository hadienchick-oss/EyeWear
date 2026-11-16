<?php
require_once 'xuly/functions.php';

$dsThuongHieu = layDanhSachThuongHieu($conn);
$dsSanPham = layDanhSachSanPham($conn);
$dsTrongKinh = layDanhSachTrongKinh($conn);
$dsVoucher = layDanhSachVoucher($conn);
$hoSoHienTai = layHoSoNguoiDungHienTai($conn);
$maKhachHienTai = layMaKhachHangHienTai($conn);

// Tạo map sản phẩm theo maSanPham để mở Modal Chi Tiết Sản Phẩm mượt mà 100% không lỗi ký tự
$productsMap = [];
foreach ($dsSanPham as $spItem) {
    $productsMap[$spItem['maSanPham']] = $spItem;
}

// Danh sách sản phẩm bán chạy hàng đầu (Top 4 sản phẩm đang mở bán có lượt bán cao nhất)
$dsBanChay = [];
foreach ($dsSanPham as $spItem) {
    if (!in_array($spItem['trangThai'], ['Ngừng bán', 'Tạm ngưng bán'])) {
        $dsBanChay[] = $spItem;
    }
}
$dsBanChay = array_slice($dsBanChay, 0, 4);

// Dữ liệu dành riêng cho Quản trị / Nhân viên
$dsNhanVien = isAdmin() ? layDanhSachNhanVien($conn) : [];
$dsKhachHang = isAdmin() ? layDanhSachKhachHang($conn) : [];
$dsKhachVangLai = isAdmin() ? layDanhSachKhachVangLai($conn) : [];
$dsLichSuVoucher = isAdmin() ? layLichSuVoucher($conn) : [];
$dsDonHangQuanTri = isStaffOrAdmin() ? layDanhSachDonHang($conn) : [];
$dsKhieuNaiQuanTri = isStaffOrAdmin() ? layDanhSachKhieuNai($conn) : [];
$tbQuanTri = isStaffOrAdmin() ? demThongBaoQuanTri($conn) : ['choThanhToan' => 0, 'choHuyDon' => 0, 'choKhieuNai' => 0, 'tongThongBao' => 0];

// Dữ liệu dành riêng cho Khách hàng
$sdtTraCuuKhach = trim($_GET['sdt_tracuu'] ?? '');
if ($maKhachHienTai) {
    $dsDonHangKhach = layDanhSachDonHang($conn, $maKhachHienTai);
    $dsKhieuNaiKhach = layDanhSachKhieuNai($conn, $maKhachHienTai);
} elseif ($sdtTraCuuKhach !== '') {
    $dsDonHangKhach = layDanhSachDonHang($conn, null, $sdtTraCuuKhach);
    $dsKhieuNaiKhach = [];
} else {
    $dsDonHangKhach = [];
    $dsKhieuNaiKhach = [];
}

// Thông tin khách vãng lai vừa đặt hàng xong (để hiện Popup mời tạo tài khoản theo dõi kiện hàng)
$guestJustOrdered = null;
if (!isLogged() && !empty($_SESSION['guest_just_ordered'])) {
    $guestJustOrdered = $_SESSION['guest_just_ordered'];
    unset($_SESSION['guest_just_ordered']);
}

// Tính giỏ hàng hiện tại
$cartItems = $_SESSION['cart'] ?? [];
$cartCount = 0;
$cartRawTotal = 0;
foreach ($cartItems as $cItem) {
    $cartCount += (int)$cItem['soLuong'];
    $cartRawTotal += ((float)$cItem['giaGong'] + (float)$cItem['giaTrong']) * (int)$cItem['soLuong'];
}

$savedVoucherCodes = $_SESSION['saved_vouchers'] ?? [];
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

    <!-- =================================================================
         THANH ĐIỀU HƯỚNG GỌN GÀNG + GOM MENU QUẢN TRỊ THÀNH 1 MENU DUY NHẤT
         ================================================================= -->
    <nav class="navbar">
        <div class="navbar-left">
            <button class="menu-toggle" onclick="toggleMobileMenu()" aria-label="Mở menu">☰</button>
            <a href="index.php" class="brand-logo">Việt MAC</a>
        </div>

        <ul class="nav-links">
            <li><a class="<?= $activeTab === 'shop-view' ? 'active' : '' ?>" onclick="switchTab('shop-view', this)">Cửa Hàng</a></li>
            <li><a class="<?= $activeTab === 'voucher-wallet' ? 'active' : '' ?>" onclick="switchTab('voucher-wallet', this)">Kho Voucher</a></li>
            <li><a class="<?= $activeTab === 'customer-orders' ? 'active' : '' ?>" onclick="switchTab('customer-orders', this)">Đơn Hàng &amp; Khiếu Nại</a></li>
        </ul>

        <div class="user-Area">
            <button class="btn btn-outline btn-sm" onclick="switchTab('cart-view')">
                🛒 Giỏ (<strong><?= $cartCount ?></strong>)
            </button>

            <?php if (isLogged()): ?>
                <div class="user-dropdown" id="userDropdown">
                    <button type="button" class="user-trigger" onclick="toggleUserDropdown(event)">
                        <span><?= htmlspecialchars($_SESSION['user']['hoTen']) ?></span>
                        <span style="font-size:11px;">▾</span>
                    </button>
                    <div class="user-dropdown-menu" id="userDropdownMenu">
                        <div class="user-dropdown-header">
                            Vai trò: <span class="role-tag"><?= htmlspecialchars($_SESSION['user']['tenVaiTro']) ?></span>
                        </div>
                        <button type="button" class="user-dropdown-item" onclick="openModal('profileModal')">
                            ✏️ Sửa thông tin cá nhân
                        </button>
                        <a href="xuly/auth_action.php?action=logout" class="user-dropdown-item danger">
                            🚪 Đăng xuất
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <button class="btn btn-outline btn-sm" onclick="openModal('registerModal')">Đăng ký</button>
                <button class="btn btn-dark btn-sm" onclick="openModal('loginModal')">Đăng nhập</button>
            <?php endif; ?>
        </div>
    </nav>

    <div class="container <?= isStaffOrAdmin() ? 'has-right-sidebar' : '' ?>">
        <main class="main-content-area">
        <!-- HIỂN THỊ THÔNG BÁO HỆ THỐNG -->
        <?php if (!empty($_SESSION['msg'])): ?>
            <div class="alert alert-success"><?= $_SESSION['msg']; unset($_SESSION['msg']); ?></div>
        <?php endif; ?>
        <?php if (!empty($_SESSION['err'])): ?>
            <div class="alert alert-error"><?= $_SESSION['err']; unset($_SESSION['err']); ?></div>
        <?php endif; ?>

        <!-- THÔNG BÁO DUYỆT THANH TOÁN QR / DUYỆT HỦY ĐƠN / KHIẾU NẠI CHO NHÂN VIÊN & ADMIN -->
        <?php if (isStaffOrAdmin() && ($tbQuanTri['choThanhToan'] > 0 || $tbQuanTri['choKhieuNai'] > 0)): ?>
            <div class="alert alert-warning-banner">
                <div>
                    🔔 <strong>Thông báo vận hành Việt MAC:</strong>
                    <?php if ($tbQuanTri['choThanhToan'] > 0): ?>
                        Có <strong><?= $tbQuanTri['choThanhToan'] ?></strong> đơn hàng đang chờ duyệt (xác nhận thanh toán QR hoặc yêu cầu hủy đơn).
                    <?php endif; ?>
                    <?php if ($tbQuanTri['choKhieuNai'] > 0): ?>
                        Có <strong><?= $tbQuanTri['choKhieuNai'] ?></strong> khiếu nại mới chờ phản hồi.
                    <?php endif; ?>
                </div>
                <div style="display:flex; gap:8px;">
                    <?php if ($tbQuanTri['choThanhToan'] > 0): ?>
                        <button class="btn btn-dark btn-sm" onclick="switchTab('admin-orders')">Xem Đơn Chờ Duyệt</button>
                    <?php endif; ?>
                    <?php if ($tbQuanTri['choKhieuNai'] > 0): ?>
                        <button class="btn btn-outline btn-sm" onclick="switchTab('admin-complaints')">Xem Khiếu Nại</button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- =================================================================
             1. CỬA HÀNG VIỆT MAC (DANH SÁCH BÁN CHẠY HÀNG ĐẦU + TÌM KIẾM + CHI TIẾT SP)
             ================================================================= -->
        <section id="shop-view" class="section-view <?= $activeTab === 'shop-view' ? 'active' : '' ?>">
            <div class="section-header">
                <div>
                    <h2>Bộ Sưu Tập Mắt Kính Việt MAC</h2>
                    <p style="font-size:13px; color:#64748b;">Tìm kiếm theo tên, màu sắc, thương hiệu và ấn vào sản phẩm để xem thông số chi tiết</p>
                </div>
            </div>

            <!-- 1. THANH TÌM KIẾM & BỘ LỌC Ở TRÊN CÙNG -->
            <div class="search-filter-bar">
                <div class="search-input-wrap">
                    <input type="text" id="shopSearchInput" oninput="filterShopProducts()" 
                           placeholder="Tìm kiếm thông minh (VD: kính màu đen, kính hãng Prada, Titanium Nam...)">
                </div>
                <select id="shopBrandFilter" class="filter-select" onchange="filterShopProducts()">
                    <option value="">Tất cả thương hiệu</option>
                    <?php foreach ($dsThuongHieu as $th): ?>
                        <option value="<?= htmlspecialchars(strtolower($th['tenThuongHieu'])) ?>"><?= htmlspecialchars($th['tenThuongHieu']) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="color-chips">
                    <span style="font-size:12px; color:#64748b; align-self:center; margin-right:4px;">Lọc nhanh màu:</span>
                    <button type="button" class="color-chip active" onclick="selectColorChip('', this)">Tất cả</button>
                    <button type="button" class="color-chip" onclick="selectColorChip('đen', this)">Màu Đen</button>
                    <button type="button" class="color-chip" onclick="selectColorChip('bạc', this)">Màu Bạc</button>
                    <button type="button" class="color-chip" onclick="selectColorChip('đồi mồi', this)">Đồi Mồi</button>
                    <button type="button" class="color-chip" onclick="selectColorChip('vàng', this)">Vàng / Gold</button>
                    <button type="button" class="color-chip" onclick="selectColorChip('trong', this)">Trong Suốt</button>
                </div>
            </div>

            <!-- 2. DANH SÁCH SẢN PHẨM BÁN CHẠY HIỆN NGAY BÊN DƯỚI THANH TÌM KIẾM & BỘ LỌC -->
            <?php if (!empty($dsBanChay)): ?>
            <div class="bestseller-section" id="bestsellerSection">
                <div class="section-header" style="margin-bottom:14px;">
                    <div>
                        <h2 style="font-size:18px;">🔥 Sản Phẩm Bán Chạy Hàng Đầu</h2>
                        <p style="font-size:12.5px; color:#64748b;">Những mẫu gọng kính được khách hàng Việt MAC lựa chọn nhiều nhất</p>
                    </div>
                </div>
                <div class="product-grid">
                    <?php foreach ($dsBanChay as $rankIdx => $spTop): 
                        $tonTop = (int)$spTop['soLuong'] - (int)$spTop['soLuongDat'];
                        $muaDuocTop = ($spTop['trangThai'] === 'Đang bán' && $tonTop > 0);
                        $imgTop = !empty($spTop['hinhAnh']) ? 'images/anh_sp/' . htmlspecialchars($spTop['hinhAnh']) : 'https://via.placeholder.com/260x140?text=Viet+MAC';
                    ?>
                        <div class="product-card <?= !$muaDuocTop ? 'product-out' : '' ?>">
                            <div class="product-clickable" onclick="openProductDetail('<?= $spTop['maSanPham'] ?>')">
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                                    <span class="bestseller-badge">Top #<?= $rankIdx + 1 ?> Bán Chạy</span>
                                    <span style="font-size:11.5px; color:#64748b; font-weight:600;">Đã bán: <?= (int)$spTop['daBan'] ?></span>
                                </div>
                                <img src="<?= $imgTop ?>" class="product-main-img" alt="">
                                <div class="product-brand"><?= htmlspecialchars($spTop['tenThuongHieu'] ?? 'Việt MAC') ?></div>
                                <div class="product-title"><?= htmlspecialchars($spTop['tenSanPham']) ?></div>
                                <div style="font-size:12px; color:#64748b; margin-bottom:6px;">
                                    Màu: <?= htmlspecialchars($spTop['mauSac'] ?? 'Đen') ?> | Size: <?= htmlspecialchars($spTop['kichThuoc'] ?? 'M') ?>
                                </div>
                            </div>
                            <div>
                                <div class="product-price"><?= number_format($spTop['gia'], 0, ',', '.') ?> đ</div>
                                <div style="display:flex; gap:6px;">
                                    <button type="button" class="btn btn-outline btn-sm" style="flex:1;" onclick="openProductDetail('<?= $spTop['maSanPham'] ?>')">Chi Tiết</button>
                                    <?php if ($muaDuocTop): ?>
                                        <button type="button" class="btn btn-dark btn-sm" style="flex:1.4;" onclick="openLensWizard('<?= $spTop['maSanPham'] ?>', '<?= htmlspecialchars($spTop['tenSanPham'], ENT_QUOTES) ?>', <?= $spTop['gia'] ?>)">Chọn Tròng &amp; Mua</button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-disabled btn-sm" style="flex:1.4;" disabled>Tạm Hết</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="section-header">
                <div>
                    <h2 style="font-size:18px;">Tất Cả Mẫu Kính Tại Việt MAC</h2>
                </div>
            </div>

            <div class="product-grid" id="shopProductGrid">
                <?php 
                $soSanPhamHienThi = 0;
                foreach ($dsSanPham as $sp): 
                    if (in_array($sp['trangThai'], ['Ngừng bán', 'Tạm ngưng bán'])) {
                        continue;
                    }
                    $soSanPhamHienThi++;
                    $tonKhaDung = (int)$sp['soLuong'] - (int)$sp['soLuongDat'];
                    $coTheMua = ($sp['trangThai'] === 'Đang bán' && $tonKhaDung > 0);
                    $mainImgPath = !empty($sp['hinhAnh']) ? 'images/anh_sp/' . htmlspecialchars($sp['hinhAnh']) : 'https://via.placeholder.com/260x140?text=Viet+MAC';
                    $searchBlob = implode(' ', [
                        $sp['maSanPham'],
                        $sp['tenSanPham'],
                        $sp['tenThuongHieu'] ?? '',
                        $sp['mauSac'] ?? '',
                        $sp['vatLieu'] ?? '',
                        $sp['gioiTinh'] ?? '',
                        $sp['kichThuoc'] ?? ''
                    ]);
                ?>
                    <div class="product-card <?= !$coTheMua ? 'product-out' : '' ?>"
                         data-search="<?= htmlspecialchars($searchBlob) ?>"
                         data-brand="<?= htmlspecialchars(strtolower($sp['tenThuongHieu'] ?? '')) ?>"
                         data-color="<?= htmlspecialchars(strtolower($sp['mauSac'] ?? '')) ?>">
                        <div class="product-clickable" onclick="openProductDetail('<?= $sp['maSanPham'] ?>')">
                            <img id="main-img-<?= $sp['maSanPham'] ?>" src="<?= $mainImgPath ?>" class="product-main-img" alt="">
                            
                            <?php if (!empty($sp['dsAnh']) && count($sp['dsAnh']) > 1): ?>
                                <div class="product-thumbs">
                                    <?php foreach ($sp['dsAnh'] as $idx => $anh): ?>
                                        <img src="images/anh_sp/<?= htmlspecialchars($anh['hinhAnh']) ?>" 
                                             class="product-thumb-item <?= $idx === 0 ? 'active-thumb' : '' ?>" 
                                             onclick="changeProductCardImage('<?= $sp['maSanPham'] ?>', 'images/anh_sp/<?= htmlspecialchars($anh['hinhAnh']) ?>', this, event)"
                                             alt="">
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <div class="product-brand"><?= htmlspecialchars($sp['tenThuongHieu'] ?? 'Việt MAC') ?></div>
                            <div class="product-title"><?= htmlspecialchars($sp['tenSanPham']) ?></div>
                            <div style="font-size:12px; color:#64748b; margin-bottom:6px;">
                                Màu: <?= htmlspecialchars($sp['mauSac'] ?? 'Đen') ?> | Size: <?= htmlspecialchars($sp['kichThuoc'] ?? 'M') ?> | ★ <?= $sp['diemTrungBinh'] ?> (Đã bán <?= (int)$sp['daBan'] ?>)
                            </div>
                        </div>

                        <div>
                            <div class="product-price"><?= number_format($sp['gia'], 0, ',', '.') ?> đ</div>
                            <div style="display:flex; gap:6px;">
                                <button type="button" class="btn btn-outline btn-sm" style="flex:1;"
                                        onclick="openProductDetail('<?= $sp['maSanPham'] ?>')">
                                    Chi Tiết
                                </button>
                                <?php if ($coTheMua): ?>
                                    <button type="button" class="btn btn-dark btn-sm" style="flex:1.4;"
                                            onclick="openLensWizard('<?= $sp['maSanPham'] ?>', '<?= htmlspecialchars($sp['tenSanPham'], ENT_QUOTES) ?>', <?= $sp['gia'] ?>)">
                                        Chọn Tròng &amp; Mua
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="btn btn-disabled btn-sm" style="flex:1.4;" disabled>
                                        Tạm Hết Hàng
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <p id="shopEmptySearch" style="display:<?= $soSanPhamHienThi === 0 ? 'block' : 'none' ?>; text-align:center; color:#64748b; padding:40px 0;">
                Không tìm thấy mẫu kính phù hợp với từ khóa hoặc bộ lọc hiện tại.
            </p>
        </section>

        <!-- =================================================================
             2. KHO VOUCHER CỦA KHÁCH HÀNG
             ================================================================= -->
        <section id="voucher-wallet" class="section-view <?= $activeTab === 'voucher-wallet' ? 'active' : '' ?>">
            <div class="section-header">
                <div>
                    <h2>Săn &amp; Lưu Mã Khuyến Mãi Voucher - Việt MAC</h2>
                    <p style="font-size:13px; color:#64748b;">Lưu mã ưu đãi về ví cá nhân để chọn áp dụng ngay tại bước thanh toán đơn hàng</p>
                </div>
            </div>

            <div class="voucher-grid">
                <?php 
                $nowObj = new DateTime();
                foreach ($dsVoucher as $vc): 
                    $hetHanObj = new DateTime($vc['ngayHetHan']);
                    $isExpired = ($hetHanObj <= $nowObj);
                    $isOut = ($vc['gioiHanDung'] !== null && (int)$vc['daDung'] >= (int)$vc['gioiHanDung']);
                    if ($isExpired || $isOut) continue;
                    $isSaved = in_array($vc['code'], $savedVoucherCodes);
                ?>
                    <div class="voucher-ticket">
                        <div>
                            <span class="role-tag" style="background:#0f172a; color:#fff; letter-spacing:1px;"><?= htmlspecialchars($vc['code']) ?></span>
                            <h3 style="margin:8px 0 4px; font-size:17px;">
                                Giảm <?= ($vc['loaiGiam'] === 'Phần trăm' || $vc['loaiGiam'] === '%') ? ((float)$vc['giaTriGiam'] . '%') : (number_format($vc['giaTriGiam'], 0, ',', '.') . ' đ') ?>
                            </h3>
                            <p style="font-size:12.5px; color:#475569;">
                                Đơn tối thiểu: <strong><?= number_format($vc['donHangToiThieu'], 0, ',', '.') ?> đ</strong><br>
                                HSD: <?= date('d/m/Y H:i', strtotime($vc['ngayHetHan'])) ?>
                            </p>
                        </div>
                        <div>
                            <?php if ($isSaved): ?>
                                <span class="status-badge status-active" style="display:block; margin-bottom:6px; text-align:center;">✓ Đã lưu</span>
                                <a href="xuly/voucher_action.php?action=remove_saved_voucher&code=<?= urlencode($vc['code']) ?>" class="btn btn-outline btn-sm">Gỡ mã</a>
                            <?php else: ?>
                                <a href="xuly/voucher_action.php?action=save_voucher&code=<?= urlencode($vc['code']) ?>" class="btn btn-dark btn-sm">+ Lưu Voucher</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- =================================================================
             3. GIỎ HÀNG CỦA BẠN
             ================================================================= -->
        <section id="cart-view" class="section-view <?= $activeTab === 'cart-view' ? 'active' : '' ?>">
            <div class="section-header">
                <div>
                    <h2>Giỏ Hàng Của Bạn</h2>
                    <p style="font-size:13px; color:#64748b;">Kiểm tra gọng kính, tròng kính cấu hình và nhấn Thanh toán để hoàn tất đơn hàng</p>
                </div>
                <button class="btn btn-outline" onclick="switchTab('shop-view')">← Tiếp tục mua sắm</button>
            </div>

            <?php if (empty($cartItems)): ?>
                <div class="card" style="padding:40px; text-align:center;">
                    <p style="color:#64748b; margin-bottom:14px;">Giỏ hàng của bạn hiện đang trống.</p>
                    <button class="btn btn-dark" onclick="switchTab('shop-view')">Khám Phá Bộ Sưu Tập Kính</button>
                </div>
            <?php else: ?>
                <div class="card" style="margin-bottom:20px;">
                    <table>
                        <thead>
                            <tr>
                                <th>Sản Phẩm</th>
                                <th>Cấu Hình Tròng &amp; Độ Mắt</th>
                                <th>Đơn Giá</th>
                                <th>Số Lượng</th>
                                <th>Thành Tiền</th>
                                <th>Xóa</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cartItems as $item): 
                                $unitPrice = (float)$item['giaGong'] + (float)$item['giaTrong'];
                                $lineTotal = $unitPrice * (int)$item['soLuong'];
                            ?>
                            <tr>
                                <td data-label="Sản Phẩm">
                                    <div style="display:flex; align-items:center; gap:10px;">
                                        <img src="<?= !empty($item['hinhAnh']) ? 'images/anh_sp/' . htmlspecialchars($item['hinhAnh']) : 'https://via.placeholder.com/80x50?text=MAC' ?>" class="thumb-img" alt="">
                                        <div>
                                            <strong><?= htmlspecialchars($item['tenSanPham']) ?></strong><br>
                                            <small style="color:#64748b;">Gọng: <?= number_format($item['giaGong'], 0, ',', '.') ?> đ</small>
                                        </div>
                                    </div>
                                </td>
                                <td data-label="Cấu Hình Tròng" style="font-size:12.5px;">
                                    <strong><?= htmlspecialchars($item['tenTrong']) ?></strong><br>
                                    <?php if (!empty($item['ghiChuDoMat'])): ?>
                                        <span style="color:#0369a1;">Thông số: <?= htmlspecialchars($item['ghiChuDoMat']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Đơn Giá"><?= number_format($unitPrice, 0, ',', '.') ?> đ</td>
                                <td data-label="Số Lượng">
                                    <form action="xuly/giohang_action.php" method="POST" style="display:inline-flex; gap:4px; align-items:center;">
                                        <input type="hidden" name="action" value="update_qty">
                                        <input type="hidden" name="cartKey" value="<?= htmlspecialchars($item['cartKey']) ?>">
                                        <input type="number" name="soLuong" value="<?= (int)$item['soLuong'] ?>" min="1" max="50" style="width:62px; padding:5px 8px; border:1px solid #cbd5e1; border-radius:6px;">
                                        <button type="submit" class="btn btn-outline btn-sm">Cập nhật</button>
                                    </form>
                                </td>
                                <td data-label="Thành Tiền"><strong><?= number_format($lineTotal, 0, ',', '.') ?> đ</strong></td>
                                <td data-label="Xóa">
                                    <a href="xuly/giohang_action.php?action=remove_item&key=<?= urlencode($item['cartKey']) ?>" class="btn btn-danger btn-sm">Xóa</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="card" style="padding:20px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
                    <div>
                        <span style="font-size:14px; color:#64748b;">Tổng tạm tính giỏ hàng:</span>
                        <h2 style="font-size:24px; color:#0f172a;"><?= number_format($cartRawTotal, 0, ',', '.') ?> đ</h2>
                    </div>
                    <button type="button" class="btn btn-dark" style="padding:12px 24px; font-size:15px;"
                            onclick="openCheckoutModal(<?= $cartRawTotal ?>)">
                        Tiến Hành Thanh Toán →
                    </button>
                </div>
            <?php endif; ?>
        </section>

        <!-- =================================================================
             4. ĐƠN HÀNG CỦA KHÁCH & YÊU CẦU HỦY ĐƠN / KHIẾU NẠI
             ================================================================= -->
        <section id="customer-orders" class="section-view <?= $activeTab === 'customer-orders' ? 'active' : '' ?>">
            <div class="section-header">
                <div>
                    <h2>Theo Dõi Đơn Hàng &amp; Khiếu Nại</h2>
                    <p style="font-size:13px; color:#64748b;">Xem chi tiết đơn đã đặt, gửi yêu cầu hủy đơn hoặc gửi khiếu nại hỗ trợ</p>
                </div>
            </div>

            <?php if (!$maKhachHienTai): ?>
                <form method="GET" action="index.php" class="search-filter-bar">
                    <input type="hidden" name="tab" value="customer-orders">
                    <div class="search-input-wrap">
                        <input type="text" name="sdt_tracuu" value="<?= htmlspecialchars($sdtTraCuuKhach) ?>" placeholder="Nhập Số điện thoại đặt hàng để tra cứu đơn của bạn...">
                    </div>
                    <button type="submit" class="btn btn-dark">Tra Cứu Đơn Hàng</button>
                </form>
            <?php endif; ?>

            <div class="card" style="margin-bottom:28px;">
                <table>
                    <thead>
                        <tr>
                            <th>Mã Đơn</th>
                            <th>Ngày Đặt</th>
                            <th>Sản Phẩm</th>
                            <th>Tổng Tiền</th>
                            <th>Thanh Toán</th>
                            <th>Trạng Thái</th>
                            <th>Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($dsDonHangKhach)): ?>
                            <tr><td colspan="7" style="text-align:center;">Bạn chưa có đơn hàng nào.</td></tr>
                        <?php else: ?>
                            <?php foreach ($dsDonHangKhach as $od): 
                                $st = $od['trangThaiDon'] ?? 'Đang xử lý';
                                $daXacNhanTT = in_array($st, ['Đã xác nhận (COD)', 'Đã thanh toán', 'Đang mài lắp', 'Đang giao hàng', 'Đã giao']);
                                $coTheYeuCauHuy = !in_array($st, ['Đã giao', 'Đã hủy', 'Chờ xác nhận hủy']);
                            ?>
                            <tr>
                                <td data-label="Mã Đơn"><strong><?= $od['maDonHang'] ?></strong></td>
                                <td data-label="Ngày Đặt"><?= date('d/m/Y H:i', strtotime($od['ngayDat'])) ?></td>
                                <td data-label="Sản Phẩm">
                                    <?php foreach ($od['chiTiet'] as $ct): ?>
                                        <div style="font-size:12.5px;">• <?= htmlspecialchars($ct['tenSanPham']) ?></div>
                                    <?php endforeach; ?>
                                </td>
                                <td data-label="Tổng Tiền"><strong><?= number_format($od['tongTien'], 0, ',', '.') ?> đ</strong></td>
                                <td data-label="Thanh Toán"><?= htmlspecialchars($od['phuongThuc'] ?? 'COD') ?></td>
                                <td data-label="Trạng Thái">
                                    <?php if (in_array($st, ['Chờ xác nhận thanh toán', 'Chờ xác nhận hủy'])): ?>
                                        <span class="status-badge status-warning"><?= htmlspecialchars($st) ?></span>
                                    <?php elseif ($st === 'Đã hủy'): ?>
                                        <span class="status-badge status-stopped"><?= htmlspecialchars($st) ?></span>
                                    <?php else: ?>
                                        <span class="status-badge status-active"><?= htmlspecialchars($st) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Thao Tác">
                                    <div class="action-group">
                                        <button type="button" class="btn btn-outline btn-sm"
                                                onclick='openOrderDetailModal(<?= json_encode($od, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>Xem chi tiết</button>

                                        <?php if ($coTheYeuCauHuy): ?>
                                            <a href="xuly/donhang_action.php?action=request_cancel&id=<?= $od['maDonHang'] ?>&sdt=<?= urlencode($od['soDienThoai']) ?>" 
                                               class="btn btn-outline btn-sm" style="color:#dc2626; border-color:#fca5a5;"
                                               onclick="return confirm('Gửi yêu cầu hủy đơn hàng <?= $od['maDonHang'] ?>? Yêu cầu sẽ được chuyển tới Quản trị viên xác nhận.')">
                                               Yêu cầu hủy
                                            </a>
                                        <?php endif; ?>

                                        <?php if ($daXacNhanTT && $maKhachHienTai): ?>
                                            <button type="button" class="btn btn-danger btn-sm"
                                                    onclick="openComplaintModal('<?= $od['maDonHang'] ?>')">Khiếu nại</button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($maKhachHienTai): ?>
                <div class="section-header">
                    <div>
                        <h3>Lịch Sử Khiếu Nại &amp; Phản Hồi Từ Việt MAC</h3>
                    </div>
                </div>
                <div class="card">
                    <table>
                        <thead>
                            <tr>
                                <th>Mã KN</th>
                                <th>Đơn Hàng</th>
                                <th>Loại</th>
                                <th>Tiêu Đề &amp; Nội Dung</th>
                                <th>Ảnh Minh Chứng</th>
                                <th>Trạng Thái</th>
                                <th>Phản Hồi Từ Cửa Hàng</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($dsKhieuNaiKhach)): ?>
                                <tr><td colspan="7" style="text-align:center;">Bạn chưa gửi khiếu nại nào.</td></tr>
                            <?php else: ?>
                                <?php foreach ($dsKhieuNaiKhach as $kn): ?>
                                <tr>
                                    <td data-label="Mã KN"><strong><?= $kn['maKhieuNai'] ?></strong></td>
                                    <td data-label="Đơn Hàng"><?= htmlspecialchars($kn['maDonHang'] ?? '-') ?></td>
                                    <td data-label="Loại"><span class="role-tag"><?= htmlspecialchars($kn['loaiKhieuNai']) ?></span></td>
                                    <td data-label="Nội Dung">
                                        <strong><?= htmlspecialchars($kn['tieuDe']) ?></strong><br>
                                        <span style="font-size:12.5px; color:#475569;"><?= htmlspecialchars($kn['noiDung']) ?></span>
                                    </td>
                                    <td data-label="Ảnh">
                                        <?php if (!empty($kn['hinhAnh'])): ?>
                                            <img src="images/anh_khieunai/<?= htmlspecialchars($kn['hinhAnh']) ?>" class="thumb-img" alt="">
                                        <?php else: ?>
                                            <span style="font-size:12px; color:#94a3b8;">Không có</span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Trạng Thái">
                                        <span class="status-badge <?= $kn['trangThai'] === 'Đã giải quyết' ? 'status-active' : 'status-warning' ?>">
                                            <?= htmlspecialchars($kn['trangThai']) ?>
                                        </span>
                                    </td>
                                    <td data-label="Phản Hồi">
                                        <?= !empty($kn['phanHoi']) ? '<strong style="color:#065f46;">' . htmlspecialchars($kn['phanHoi']) . '</strong>' : '<span style="color:#94a3b8;">Đang chờ nhân viên phản hồi...</span>' ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <!-- =================================================================
             5. PHÂN HỆ QUẢN TRỊ (NHÂN VIÊN & ADMIN)
             ================================================================= -->
        <?php if (isStaffOrAdmin()): ?>

        <!-- 5.1. QUẢN LÝ SẢN PHẨM -->
        <section id="admin-product" class="section-view <?= $activeTab === 'admin-product' ? 'active' : '' ?>">
            <div class="section-header">
                <div>
                    <h2>Quản Lý Sản Phẩm &amp; Tồn Kho</h2>
                    <p style="font-size:13px; color:#64748b;">Tự động chuyển sang trạng thái 'Tạm hết' khi tồn kho về 0</p>
                </div>
                <button class="btn btn-dark" onclick="openProductModal('add')">+ Thêm Sản Phẩm</button>
            </div>

            <div class="search-filter-bar">
                <div class="search-input-wrap">
                    <input type="text" id="searchAdminProduct" oninput="filterAdminTable('searchAdminProduct', 'tbodyAdminProduct')" 
                           placeholder="Tìm sản phẩm theo Tên sản phẩm hoặc Mã SP (VD: SP01, Prada)...">
                </div>
            </div>

            <div class="card">
                <table>
                    <thead>
                        <tr>
                            <th>Mã SP</th>
                            <th>Bộ Ảnh</th>
                            <th>Tên Sản Phẩm</th>
                            <th>Thương Hiệu</th>
                            <th>Thông Số Gọng</th>
                            <th>Giá Bán</th>
                            <th>Tồn Kho / Đã Bán</th>
                            <th>Trạng Thái</th>
                            <th>Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyAdminProduct">
                        <?php foreach ($dsSanPham as $sp): 
                            $rowSearch = $sp['maSanPham'] . ' ' . $sp['tenSanPham'] . ' ' . ($sp['tenThuongHieu'] ?? '');
                        ?>
                        <tr data-search="<?= htmlspecialchars($rowSearch) ?>">
                            <td data-label="Mã SP"><strong><?= $sp['maSanPham'] ?></strong></td>
                            <td data-label="Bộ Ảnh">
                                <?php if (!empty($sp['dsAnh'])): ?>
                                    <div class="admin-img-stack">
                                        <?php foreach (array_slice($sp['dsAnh'], 0, 2) as $anh): ?>
                                            <img src="images/anh_sp/<?= htmlspecialchars($anh['hinhAnh']) ?>" class="thumb-img" alt="">
                                        <?php endforeach; ?>
                                        <?php if (count($sp['dsAnh']) > 2): ?>
                                            <span class="admin-img-count">+<?= count($sp['dsAnh']) - 2 ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <span style="font-size:12px;color:#94a3b8;">Chưa có ảnh</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Tên SP"><strong><?= htmlspecialchars($sp['tenSanPham']) ?></strong></td>
                            <td data-label="Thương Hiệu"><?= htmlspecialchars($sp['tenThuongHieu'] ?? '') ?></td>
                            <td data-label="Thông Số" style="font-size:12px; color:#475569;">
                                Size: <?= htmlspecialchars($sp['kichThuoc'] ?? '-') ?> | Màu: <?= htmlspecialchars($sp['mauSac'] ?? '-') ?><br>
                                <?= htmlspecialchars($sp['vatLieu'] ?? '-') ?> (<?= htmlspecialchars($sp['gioiTinh'] ?? '-') ?>)
                            </td>
                            <td data-label="Giá Bán"><strong><?= number_format($sp['gia'], 0, ',', '.') ?> đ</strong></td>
                            <td data-label="Tồn Kho">
                                Tồn: <strong><?= $sp['soLuong'] ?></strong> | Giữ: <strong><?= $sp['soLuongDat'] ?></strong><br>
                                <small style="color:#059669;">Đã bán: <?= (int)$sp['daBan'] ?></small>
                            </td>
                            <td data-label="Trạng Thái">
                                <span class="status-badge <?= $sp['trangThai'] === 'Đang bán' ? 'status-active' : ($sp['trangThai'] === 'Ngừng bán' ? 'status-stopped' : 'status-warning') ?>">
                                    <?= htmlspecialchars($sp['trangThai']) ?>
                                </span>
                            </td>
                            <td data-label="Thao Tác">
                                <div class="action-group">
                                    <button class="btn btn-outline btn-sm" onclick='openProductModal("edit", <?= json_encode($sp, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>Sửa</button>
                                    <?php if (isAdmin()): ?>
                                        <a href="xuly/sanpham_action.php?action=delete&id=<?= $sp['maSanPham'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Xóa sản phẩm <?= $sp['maSanPham'] ?>?')">Xóa</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- 5.2. QUẢN LÝ THƯƠNG HIỆU -->
        <section id="admin-brand" class="section-view <?= $activeTab === 'admin-brand' ? 'active' : '' ?>">
            <div class="section-header">
                <div>
                    <h2>Quản Lý Thương Hiệu</h2>
                    <p style="font-size:13px; color:#64748b;">Logo lưu tại <strong>images/logo_thuonghieu/</strong></p>
                </div>
                <button class="btn btn-dark" onclick="openBrandModal('add')">+ Thêm Thương Hiệu</button>
            </div>

            <div class="search-filter-bar">
                <div class="search-input-wrap">
                    <input type="text" id="searchAdminBrand" oninput="filterAdminTable('searchAdminBrand', 'tbodyAdminBrand')" 
                           placeholder="Tìm thương hiệu theo Tên hãng hoặc Mã TH (VD: TH01, Prada)...">
                </div>
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
                    <tbody id="tbodyAdminBrand">
                        <?php foreach ($dsThuongHieu as $th): ?>
                        <tr data-search="<?= htmlspecialchars($th['maThuongHieu'] . ' ' . $th['tenThuongHieu']) ?>">
                            <td data-label="Mã TH"><strong><?= $th['maThuongHieu'] ?></strong></td>
                            <td data-label="Logo">
                                <?php if (!empty($th['logo'])): ?>
                                    <img src="images/logo_thuonghieu/<?= htmlspecialchars($th['logo']) ?>" class="thumb-img" alt="">
                                <?php else: ?>
                                    <span style="font-size:12px;color:#94a3b8;">Không logo</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Tên Hãng"><strong><?= htmlspecialchars($th['tenThuongHieu']) ?></strong></td>
                            <td data-label="Mô Tả"><?= htmlspecialchars($th['moTa'] ?? '') ?></td>
                            <td data-label="Thao Tác">
                                <div class="action-group">
                                    <button class="btn btn-outline btn-sm" onclick='openBrandModal("edit", <?= json_encode($th, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>Sửa</button>
                                    <?php if (isAdmin()): ?>
                                        <a href="xuly/thuonghieu_action.php?action=delete&id=<?= $th['maThuongHieu'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Xóa thương hiệu <?= $th['tenThuongHieu'] ?>?')">Xóa</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- 5.3. QUẢN LÝ ĐƠN HÀNG (DUYỆT QR, DUYỆT YÊU CẦU HỦY, ĐƠN PHƯƠNG HỦY, SỬA THÔNG TIN ĐƠN) -->
        <section id="admin-orders" class="section-view <?= $activeTab === 'admin-orders' ? 'active' : '' ?>">
            <div class="section-header">
                <div>
                    <h2>Quản Lý Đơn Hàng Khách Hàng</h2>
                    <p style="font-size:13px; color:#64748b;">Xác nhận thanh toán QR, sửa thông tin đơn hàng, duyệt yêu cầu hủy hoặc đơn phương hủy đơn</p>
                </div>
                <button class="btn btn-dark" onclick="switchTab('admin-pos')">+ Tạo Đơn Tại Quầy</button>
            </div>

            <div class="search-filter-bar">
                <div class="search-input-wrap">
                    <input type="text" id="searchAdminOrder" oninput="filterAdminTable('searchAdminOrder', 'tbodyAdminOrder')" 
                           placeholder="Tìm đơn theo Mã đơn (DH01), Tên khách nhận, Số điện thoại hoặc Trạng thái...">
                </div>
            </div>

            <div class="card">
                <table>
                    <thead>
                        <tr>
                            <th>Mã Đơn</th>
                            <th>Khách Nhận Hàng</th>
                            <th>Sản Phẩm &amp; Tròng</th>
                            <th>Tổng Tiền</th>
                            <th>Phương Thức</th>
                            <th>Trạng Thái</th>
                            <th>Thao Tác Quản Trị</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyAdminOrder">
                        <?php if (empty($dsDonHangQuanTri)): ?>
                            <tr><td colspan="7" style="text-align:center;">Chưa có đơn hàng nào.</td></tr>
                        <?php else: ?>
                            <?php foreach ($dsDonHangQuanTri as $od): 
                                $st = $od['trangThaiDon'] ?? 'Đang xử lý';
                                $isPendingQR = ($st === 'Chờ xác nhận thanh toán');
                                $isPendingCancel = ($st === 'Chờ xác nhận hủy');
                                $isCancelled = ($st === 'Đã hủy');
                                $searchOd = $od['maDonHang'] . ' ' . $od['tenNN'] . ' ' . $od['soDienThoai'] . ' ' . $st;
                            ?>
                            <tr data-search="<?= htmlspecialchars($searchOd) ?>">
                                <td data-label="Mã Đơn">
                                    <strong><?= $od['maDonHang'] ?></strong><br>
                                    <small style="color:#64748b;"><?= date('d/m H:i', strtotime($od['ngayDat'])) ?></small>
                                </td>
                                <td data-label="Khách Nhận">
                                    <strong><?= htmlspecialchars($od['tenNN']) ?></strong> (<?= htmlspecialchars($od['soDienThoai']) ?>)<br>
                                    <small style="color:#64748b;"><?= htmlspecialchars($od['diaChi']) ?></small>
                                </td>
                                <td data-label="Chi Tiết">
                                    <?php foreach ($od['chiTiet'] as $ct): ?>
                                        <div style="font-size:12px;">
                                            • <strong><?= htmlspecialchars($ct['tenSanPham']) ?></strong>
                                            <?= $ct['loaiTrong'] ? ' + ' . htmlspecialchars($ct['loaiTrong']) . ' (' . $ct['chietSuat'] . ')' : '' ?>
                                        </div>
                                    <?php endforeach; ?>
                                </td>
                                <td data-label="Tổng Tiền">
                                    <strong><?= number_format($od['tongTien'], 0, ',', '.') ?> đ</strong>
                                    <?= !empty($od['maCodeVoucher']) ? '<br><small style="color:#065f46;">Mã: ' . htmlspecialchars($od['maCodeVoucher']) . '</small>' : '' ?>
                                </td>
                                <td data-label="Thanh Toán"><?= htmlspecialchars($od['phuongThuc'] ?? 'COD') ?></td>
                                <td data-label="Trạng Thái">
                                    <span class="status-badge <?= ($isPendingQR || $isPendingCancel) ? 'status-warning' : ($isCancelled ? 'status-stopped' : 'status-active') ?>">
                                        <?= htmlspecialchars($st) ?>
                                    </span>
                                </td>
                                <td data-label="Thao Tác">
                                    <div style="display:flex; flex-direction:column; gap:6px;">
                                        <?php if ($isPendingQR): ?>
                                            <a href="xuly/donhang_action.php?action=confirm_payment&id=<?= $od['maDonHang'] ?>" 
                                               class="btn btn-success btn-sm"
                                               onclick="return confirm('Xác nhận khách đã chuyển khoản QR cho đơn <?= $od['maDonHang'] ?>?')">
                                               ✓ Xác nhận thanh toán QR
                                            </a>
                                        <?php endif; ?>

                                        <?php if ($isPendingCancel): ?>
                                            <div style="display:flex; gap:4px;">
                                                <a href="xuly/donhang_action.php?action=approve_cancel&id=<?= $od['maDonHang'] ?>" 
                                                   class="btn btn-danger btn-sm" style="flex:1;"
                                                   onclick="return confirm('Duyệt yêu cầu hủy đơn <?= $od['maDonHang'] ?> và hoàn kho?')">
                                                   ✓ Duyệt hủy
                                                </a>
                                                <a href="xuly/donhang_action.php?action=reject_cancel&id=<?= $od['maDonHang'] ?>" 
                                                   class="btn btn-outline btn-sm" style="flex:1;">
                                                   Từ chối
                                                </a>
                                            </div>
                                        <?php endif; ?>

                                        <div class="action-group">
                                            <button type="button" class="btn btn-outline btn-sm"
                                                    onclick='openEditOrderModal(<?= json_encode($od, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                                ✏️ Sửa đơn
                                            </button>

                                            <?php if (!$isCancelled && !$isPendingCancel && $st !== 'Đã giao'): ?>
                                                <a href="xuly/donhang_action.php?action=admin_cancel&id=<?= $od['maDonHang'] ?>" 
                                                   class="btn btn-danger btn-sm"
                                                   onclick="return confirm('Bạn chắc chắn muốn đơn phương HỦY đơn hàng <?= $od['maDonHang'] ?>? Tồn kho sẽ tự động được hoàn lại.')">
                                                   ✕ Hủy đơn
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- 5.4. TẠO ĐƠN TẠI CỬA HÀNG (POS) - HIỆN DANH SÁCH VOUCHER + TỰ LƯU TÀI KHOẢN KHÁCH MỚI -->
        <section id="admin-pos" class="section-view <?= $activeTab === 'admin-pos' ? 'active' : '' ?>">
            <div class="section-header">
                <div>
                    <h2>Tạo Đơn Hàng Tại Cửa Hàng (POS - Việt MAC)</h2>
                    <p style="font-size:13px; color:#64748b;">Tự động lưu tài khoản khách hàng mới, chọn Voucher khuyến mãi và xác nhận Đã thanh toán ngay</p>
                </div>
            </div>

            <div class="card" style="padding:22px;">
                <form action="xuly/donhang_action.php" method="POST">
                    <input type="hidden" name="action" value="create_pos_order">

                    <div class="form-group">
                        <label>1. Đối tượng khách hàng tại quầy:</label>
                        <div style="display:flex; gap:18px; flex-wrap:wrap; margin-top:6px;">
                            <label style="font-weight:600; cursor:pointer;">
                                <input type="radio" name="loaiKhach" value="member" checked onchange="togglePosCustomerType()"> Khách thành viên đã có tài khoản
                            </label>
                            <label style="font-weight:600; cursor:pointer;">
                                <input type="radio" name="loaiKhach" value="walkin" onchange="togglePosCustomerType()"> Khách hàng mới (Tự động lưu tài khoản mới)
                            </label>
                        </div>
                    </div>

                    <div id="posMemberBox" class="form-group">
                        <label>Chọn khách hàng thành viên *</label>
                        <select name="maKhachHang">
                            <option value="">-- Chọn khách hàng theo Tên / Số điện thoại --</option>
                            <?php 
                            $allMembers = layDanhSachKhachHang($conn);
                            foreach ($allMembers as $m): 
                            ?>
                                <option value="<?= $m['maKhachHang'] ?>">
                                    <?= htmlspecialchars($m['hoTen']) ?> — SĐT: <?= htmlspecialchars($m['soDienThoai']) ?> (<?= $m['maKhachHang'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="posWalkinBox" class="form-grid" style="display:none; background:#f8fafc; padding:14px; border-radius:10px; border:1px solid #e2e8f0; margin-bottom:14px;">
                        <div class="form-group" style="margin-bottom:0;">
                            <label>Họ tên khách hàng mới *</label>
                            <input type="text" name="tenNN" placeholder="Nhập họ tên khách">
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label>Số điện thoại *</label>
                            <input type="text" name="soDienThoai" placeholder="Nhập SĐT liên hệ">
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label>Email đăng nhập (Tự tạo nếu để trống)</label>
                            <input type="email" name="emailKhachMoi" placeholder="VD: khachmoi@gmail.com">
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label>Giới tính</label>
                            <select name="gioiTinhKhachMoi">
                                <option value="Nam">Nam</option>
                                <option value="Nữ">Nữ</option>
                            </select>
                        </div>
                        <div class="form-group full" style="margin-bottom:0;">
                            <label>Địa chỉ khách hàng</label>
                            <input type="text" name="diaChi" placeholder="Nhập địa chỉ khách hàng (hoặc để trống nếu nhận tại quầy)">
                        </div>
                        <p style="grid-column: span 2; font-size:12px; color:#065f46; margin:0;">
                            * Hệ thống sẽ tự động tạo tài khoản thành viên (Mật khẩu mặc định: <strong>123</strong>) và lưu vào danh sách Khách hàng.
                        </p>
                    </div>

                    <div class="form-group">
                        <label>2. Chọn sản phẩm gọng kính &amp; tròng kính kèm theo:</label>
                        <div id="posItemsContainer">
                            <div class="pos-item-row" style="display:grid; grid-template-columns: 2fr 1.8fr 80px 42px; gap:8px; margin-bottom:8px;">
                                <select name="maSanPham[]" onchange="recalcPosTotal()" required>
                                    <option value="" data-price="0">-- Chọn gọng kính --</option>
                                    <?php foreach ($dsSanPham as $sp): 
                                        $avail = (int)$sp['soLuong'] - (int)$sp['soLuongDat'];
                                        if ($sp['trangThai'] === 'Đang bán' && $avail > 0):
                                    ?>
                                        <option value="<?= $sp['maSanPham'] ?>" data-price="<?= $sp['gia'] ?>">
                                            [<?= $sp['maSanPham'] ?>] <?= htmlspecialchars($sp['tenSanPham']) ?> (Còn <?= $avail ?>) — <?= number_format($sp['gia'], 0, ',', '.') ?> đ
                                        </option>
                                    <?php endif; endforeach; ?>
                                </select>

                                <select name="maTrongKinh[]" onchange="recalcPosTotal()">
                                    <option value="" data-price="0">Chỉ lấy gọng (0 đ)</option>
                                    <?php foreach ($dsTrongKinh as $tk): ?>
                                        <option value="<?= $tk['maTrongKinh'] ?>" data-price="<?= $tk['gia'] ?>">
                                            <?= htmlspecialchars($tk['loai']) ?> CS <?= $tk['chietSuat'] ?> (+<?= number_format($tk['gia'], 0, ',', '.') ?> đ)
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <input type="number" name="soLuong[]" value="1" min="1" onchange="recalcPosTotal()" oninput="recalcPosTotal()">
                                <button type="button" class="btn btn-outline btn-sm" onclick="removePosItemRow(this)">✕</button>
                            </div>
                        </div>
                        <button type="button" class="btn btn-outline btn-sm" onclick="addPosItemRow()">+ Thêm dòng sản phẩm</button>
                    </div>

                    <div class="form-grid" style="margin-top:14px;">
                        <div class="form-group">
                            <label>3. Chọn mã Voucher khuyến mãi đang hiệu lực:</label>
                            <select id="posVoucherSelect" name="voucherCode" onchange="recalcPosTotal()">
                                <option value="" data-min="0" data-type="" data-val="0">-- Không áp dụng Voucher --</option>
                                <?php 
                                $nowPos = new DateTime();
                                foreach ($dsVoucher as $vcPos): 
                                    $expPos = new DateTime($vcPos['ngayHetHan']);
                                    $outPos = ($vcPos['gioiHanDung'] !== null && (int)$vcPos['daDung'] >= (int)$vcPos['gioiHanDung']);
                                    if ($expPos <= $nowPos || $outPos) continue;
                                    $descGiam = ($vcPos['loaiGiam'] === 'Phần trăm' || $vcPos['loaiGiam'] === '%')
                                        ? ('Giảm ' . (float)$vcPos['giaTriGiam'] . '%')
                                        : ('Giảm ' . number_format($vcPos['giaTriGiam'], 0, ',', '.') . ' đ');
                                ?>
                                    <option value="<?= htmlspecialchars($vcPos['code']) ?>"
                                            data-min="<?= (float)$vcPos['donHangToiThieu'] ?>"
                                            data-type="<?= htmlspecialchars($vcPos['loaiGiam']) ?>"
                                            data-val="<?= (float)$vcPos['giaTriGiam'] ?>">
                                        <?= htmlspecialchars($vcPos['code']) ?> — <?= $descGiam ?> (Đơn tối thiểu <?= number_format($vcPos['donHangToiThieu'], 0, ',', '.') ?> đ)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small id="posVoucherNote" style="display:block; margin-top:4px; font-weight:600;"></small>
                        </div>

                        <div class="form-group">
                            <label>4. Hình thức thu tiền tại quầy:</label>
                            <select name="phuongThuc">
                                <option value="Tiền mặt tại quầy">Tiền mặt tại quầy</option>
                                <option value="Chuyển khoản QR">Chuyển khoản QR Techcombank</option>
                            </select>
                        </div>
                    </div>

                    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; padding-top:14px; border-top:1px solid #e2e8f0;">
                        <div>
                            <span style="font-size:13px; color:#64748b;">Tổng tiền khách thanh toán:</span>
                            <h3 id="posEstimatedTotal" style="font-size:23px; color:#0f172a;">0 đ</h3>
                        </div>
                        <button type="submit" class="btn btn-success" style="padding:12px 22px;">
                            ✓ Xác Nhận &amp; Hoàn Tất Đơn (Đã Thanh Toán)
                        </button>
                    </div>
                </form>
            </div>
        </section>

        <!-- 5.5. QUẢN LÝ & PHẢN HỒI KHIẾU NẠI -->
        <section id="admin-complaints" class="section-view <?= $activeTab === 'admin-complaints' ? 'active' : '' ?>">
            <div class="section-header">
                <div>
                    <h2>Quản Lý Khiếu Nại Khách Hàng</h2>
                    <p style="font-size:13px; color:#64748b;">Nhân viên và Admin tiếp nhận, kiểm tra ảnh minh chứng và phản hồi giải quyết khiếu nại</p>
                </div>
            </div>

            <div class="search-filter-bar">
                <div class="search-input-wrap">
                    <input type="text" id="searchAdminComplaint" oninput="filterAdminTable('searchAdminComplaint', 'tbodyAdminComplaint')" 
                           placeholder="Tìm khiếu nại theo Mã KN, Mã đơn hàng, Tên khách hoặc Tiêu đề...">
                </div>
            </div>

            <div class="card">
                <table>
                    <thead>
                        <tr>
                            <th>Mã KN</th>
                            <th>Khách Hàng</th>
                            <th>Đơn Liên Quan</th>
                            <th>Loại &amp; Tiêu Đề</th>
                            <th>Ảnh Minh Chứng</th>
                            <th>Trạng Thái</th>
                            <th>Phản Hồi</th>
                            <th>Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyAdminComplaint">
                        <?php if (empty($dsKhieuNaiQuanTri)): ?>
                            <tr><td colspan="8" style="text-align:center;">Chưa có khiếu nại nào từ khách hàng.</td></tr>
                        <?php else: ?>
                            <?php foreach ($dsKhieuNaiQuanTri as $kn): 
                                $searchKn = $kn['maKhieuNai'] . ' ' . ($kn['maDonHang'] ?? '') . ' ' . $kn['tenKhachHang'] . ' ' . $kn['tieuDe'];
                            ?>
                            <tr data-search="<?= htmlspecialchars($searchKn) ?>">
                                <td data-label="Mã KN"><strong><?= $kn['maKhieuNai'] ?></strong></td>
                                <td data-label="Khách Hàng">
                                    <strong><?= htmlspecialchars($kn['tenKhachHang']) ?></strong><br>
                                    <small><?= htmlspecialchars($kn['soDienThoai']) ?></small>
                                </td>
                                <td data-label="Đơn Hàng"><?= htmlspecialchars($kn['maDonHang'] ?? '-') ?></td>
                                <td data-label="Nội Dung">
                                    <span class="role-tag"><?= htmlspecialchars($kn['loaiKhieuNai']) ?></span><br>
                                    <strong><?= htmlspecialchars($kn['tieuDe']) ?></strong>
                                </td>
                                <td data-label="Ảnh">
                                    <?php if (!empty($kn['hinhAnh'])): ?>
                                        <img src="images/anh_khieunai/<?= htmlspecialchars($kn['hinhAnh']) ?>" class="thumb-img" alt="">
                                    <?php else: ?>
                                        <span style="font-size:12px; color:#94a3b8;">Không có</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Trạng Thái">
                                    <span class="status-badge <?= $kn['trangThai'] === 'Đã giải quyết' ? 'status-active' : 'status-warning' ?>">
                                        <?= htmlspecialchars($kn['trangThai']) ?>
                                    </span>
                                </td>
                                <td data-label="Phản Hồi" style="font-size:12.5px; max-width:200px;">
                                    <?= htmlspecialchars($kn['phanHoi'] ?? 'Chưa phản hồi') ?>
                                </td>
                                <td data-label="Thao Tác">
                                    <button type="button" class="btn btn-dark btn-sm" 
                                            onclick='openReplyComplaintModal(<?= json_encode($kn, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>Phản hồi</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- 5.6. QUẢN LÝ VOUCHER -->
        <section id="admin-voucher" class="section-view <?= $activeTab === 'admin-voucher' ? 'active' : '' ?>">
            <div class="section-header">
                <div>
                    <h2>Quản Lý Voucher Khuyến Mãi</h2>
                    <p style="font-size:13px; color:#64748b;">Quản trị mã giảm giá &amp; lịch sử áp dụng</p>
                </div>
                <?php if (isAdmin()): ?>
                    <button class="btn btn-dark" onclick="openVoucherModal('add')">+ Phát Hành Voucher</button>
                <?php endif; ?>
            </div>

            <div class="search-filter-bar">
                <div class="search-input-wrap">
                    <input type="text" id="searchAdminVoucher" oninput="filterAdminTable('searchAdminVoucher', 'tbodyAdminVoucher')" 
                           placeholder="Tìm Voucher theo Mã VC hoặc Chuỗi mã Code (VD: VIETMAC15)...">
                </div>
            </div>

            <div class="card">
                <table>
                    <thead>
                        <tr>
                            <th>Mã VC</th>
                            <th>Mã Code</th>
                            <th>Loại Giảm</th>
                            <th>Giá Trị</th>
                            <th>Đơn Tối Thiểu</th>
                            <th>Lượt Dùng</th>
                            <th>Hết Hạn</th>
                            <?php if (isAdmin()): ?><th>Thao Tác</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody id="tbodyAdminVoucher">
                        <?php foreach ($dsVoucher as $vc): ?>
                        <tr data-search="<?= htmlspecialchars($vc['maVoucher'] . ' ' . $vc['code']) ?>">
                            <td data-label="Mã VC"><strong><?= $vc['maVoucher'] ?></strong></td>
                            <td data-label="Mã Code"><span class="role-tag" style="background:#0f172a;color:#fff;"><?= htmlspecialchars($vc['code']) ?></span></td>
                            <td data-label="Loại Giảm"><?= htmlspecialchars($vc['loaiGiam']) ?></td>
                            <td data-label="Giá Trị"><strong><?= ($vc['loaiGiam'] === 'Phần trăm' || $vc['loaiGiam'] === '%') ? ((float)$vc['giaTriGiam'] . '%') : (number_format($vc['giaTriGiam'], 0, ',', '.') . ' đ') ?></strong></td>
                            <td data-label="Đơn Tối Thiểu"><?= number_format($vc['donHangToiThieu'], 0, ',', '.') ?> đ</td>
                            <td data-label="Lượt Dùng"><?= (int)$vc['daDung'] ?> / <?= $vc['gioiHanDung'] ?? '∞' ?></td>
                            <td data-label="Hết Hạn"><?= date('d/m/Y H:i', strtotime($vc['ngayHetHan'])) ?></td>
                            <?php if (isAdmin()): ?>
                            <td data-label="Thao Tác">
                                <div class="action-group">
                                    <button class="btn btn-outline btn-sm" onclick='openVoucherModal("edit", <?= json_encode($vc, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>Sửa</button>
                                    <a href="xuly/voucher_action.php?action=delete&id=<?= $vc['maVoucher'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Xóa hoặc ngừng hiệu lực mã <?= $vc['code'] ?>?')">Xóa</a>
                                </div>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <?php endif; ?>

        <!-- =================================================================
             6. QUẢN LÝ KHÁCH HÀNG & NHÂN VIÊN (CHỈ ADMIN VT01)
             ================================================================= -->
        <?php if (isAdmin()): ?>
        <section id="admin-customer" class="section-view <?= $activeTab === 'admin-customer' ? 'active' : '' ?>">
            <div class="section-header">
                <div>
                    <h2>Quản Lý Khách Hàng - Việt MAC</h2>
                    <p style="font-size:13px; color:#64748b;">Quản lý hồ sơ khách hàng thành viên và danh sách số điện thoại khách mua vãng lai</p>
                </div>
            </div>

            <div class="search-filter-bar">
                <div class="search-input-wrap">
                    <input type="text" id="searchAdminCustomer" oninput="filterAdminTable('searchAdminCustomer', 'tbodyAdminCustomer')" 
                           placeholder="Tìm khách hàng theo Mã KH, Họ tên, Email hoặc Số điện thoại...">
                </div>
            </div>

            <div class="card" style="margin-bottom:24px;">
                <table>
                    <thead>
                        <tr>
                            <th>Mã KH</th>
                            <th>Họ và Tên</th>
                            <th>Email</th>
                            <th>Số Điện Thoại</th>
                            <th>Địa Chỉ</th>
                            <th>Đơn Đã Đặt</th>
                            <th>Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyAdminCustomer">
                        <?php foreach ($dsKhachHang as $kh): 
                            $searchKh = $kh['maKhachHang'] . ' ' . $kh['hoTen'] . ' ' . $kh['email'] . ' ' . $kh['soDienThoai'];
                        ?>
                        <tr data-search="<?= htmlspecialchars($searchKh) ?>">
                            <td data-label="Mã KH"><strong><?= $kh['maKhachHang'] ?></strong></td>
                            <td data-label="Họ Tên"><strong><?= htmlspecialchars($kh['hoTen']) ?></strong></td>
                            <td data-label="Email"><?= htmlspecialchars($kh['email']) ?></td>
                            <td data-label="SĐT"><?= htmlspecialchars($kh['soDienThoai']) ?></td>
                            <td data-label="Địa Chỉ"><?= htmlspecialchars($kh['diaChi'] ?? '-') ?></td>
                            <td data-label="Đơn Đã Đặt"><?= (int)$kh['soDonHang'] ?> đơn (<?= number_format($kh['tongChiTieu'], 0, ',', '.') ?> đ)</td>
                            <td data-label="Thao Tác">
                                <div class="action-group">
                                    <button class="btn btn-outline btn-sm" onclick='openCustomerEditModal(<?= json_encode($kh, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>Sửa</button>
                                    <a href="xuly/khachhang_action.php?action=delete&id=<?= $kh['maNguoiDung'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Xóa tài khoản khách hàng <?= htmlspecialchars($kh['hoTen']) ?>?')">Xóa</a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if (!empty($dsKhachVangLai)): ?>
            <div class="section-header">
                <div>
                    <h3>Danh Sách Số Điện Thoại Khách Vãng Lai</h3>
                </div>
            </div>
            <div class="card">
                <table>
                    <thead>
                        <tr>
                            <th>Số Điện Thoại</th>
                            <th>Tên Người Nhận</th>
                            <th>Địa Chỉ Gần Nhất</th>
                            <th>Số Đơn</th>
                            <th>Tổng Chi Tiêu</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dsKhachVangLai as $vl): ?>
                        <tr>
                            <td data-label="SĐT"><strong><?= htmlspecialchars($vl['soDienThoai']) ?></strong></td>
                            <td data-label="Tên"><?= htmlspecialchars($vl['tenNN']) ?></td>
                            <td data-label="Địa Chỉ"><?= htmlspecialchars($vl['diaChi']) ?></td>
                            <td data-label="Số Đơn"><?= (int)$vl['soDonDat'] ?> đơn</td>
                            <td data-label="Chi Tiêu"><strong><?= number_format($vl['tongChiTieu'], 0, ',', '.') ?> đ</strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </section>

        <section id="admin-staff" class="section-view <?= $activeTab === 'admin-staff' ? 'active' : '' ?>">
            <div class="section-header">
                <div>
                    <h2>Quản Lý Nhân Viên - Việt MAC</h2>
                    <p style="font-size:13px; color:#64748b;">Quản trị tài khoản phân quyền nhân viên bán hàng &amp; kho (VT02)</p>
                </div>
                <button class="btn btn-dark" onclick="openStaffModal('add')">+ Thêm Nhân Viên</button>
            </div>

            <div class="search-filter-bar">
                <div class="search-input-wrap">
                    <input type="text" id="searchAdminStaff" oninput="filterAdminTable('searchAdminStaff', 'tbodyAdminStaff')" 
                           placeholder="Tìm nhân viên theo Mã ND, Họ tên hoặc Email...">
                </div>
            </div>

            <div class="card">
                <table>
                    <thead>
                        <tr>
                            <th>Mã ND</th>
                            <th>Họ và Tên</th>
                            <th>Email</th>
                            <th>Vai Trò</th>
                            <th>Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyAdminStaff">
                        <?php foreach ($dsNhanVien as $nv): ?>
                        <tr data-search="<?= htmlspecialchars($nv['maNguoiDung'] . ' ' . $nv['hoTen'] . ' ' . $nv['email']) ?>">
                            <td data-label="Mã ND"><strong><?= $nv['maNguoiDung'] ?></strong></td>
                            <td data-label="Họ Tên"><?= htmlspecialchars($nv['hoTen']) ?></td>
                            <td data-label="Email"><?= htmlspecialchars($nv['email']) ?></td>
                            <td data-label="Vai Trò"><span class="role-tag">Nhân Viên (VT02)</span></td>
                            <td data-label="Thao Tác">
                                <div class="action-group">
                                    <button class="btn btn-outline btn-sm" onclick='openStaffModal("edit", <?= json_encode($nv, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>Sửa</button>
                                    <a href="xuly/nhanvien_action.php?action=delete&id=<?= $nv['maNguoiDung'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Xóa nhân viên <?= htmlspecialchars($nv['hoTen']) ?>?')">Xóa</a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <?php endif; ?>
        </main>

        <!-- =================================================================
             THANH QUẢN TRỊ HỆ THỐNG HIỆN MẶC ĐỊNH BÊN PHẢI
             ================================================================= -->
        <?php if (isStaffOrAdmin()): ?>
        <aside class="admin-right-sidebar" id="adminRightSidebar">
            <div class="sidebar-header">
                <div>
                    <div class="sidebar-title">⚙️ Quản Trị Hệ Thống</div>
                    <div class="sidebar-subtitle"><?= isAdmin() ? 'Tài khoản Quản Trị Viên (Admin)' : 'Tài khoản Nhân Viên' ?></div>
                </div>
            </div>

            <nav class="sidebar-nav">
                <button type="button" class="sidebar-nav-item <?= $activeTab === 'admin-product' ? 'active' : '' ?>" 
                        onclick="switchTab('admin-product', this)">
                    <span>📦 Quản Lý Sản Phẩm</span>
                </button>
                <button type="button" class="sidebar-nav-item <?= $activeTab === 'admin-brand' ? 'active' : '' ?>" 
                        onclick="switchTab('admin-brand', this)">
                    <span>🏷️ Quản Lý Thương Hiệu</span>
                </button>
                <button type="button" class="sidebar-nav-item <?= $activeTab === 'admin-orders' ? 'active' : '' ?>" 
                        onclick="switchTab('admin-orders', this)">
                    <span>📋 Quản Lý Đơn Hàng</span>
                    <?php if ($tbQuanTri['choThanhToan'] > 0): ?>
                        <span class="notif-dot"><?= $tbQuanTri['choThanhToan'] ?></span>
                    <?php endif; ?>
                </button>
                <button type="button" class="sidebar-nav-item <?= $activeTab === 'admin-pos' ? 'active' : '' ?>" 
                        onclick="switchTab('admin-pos', this)">
                    <span>🧾 Tạo Đơn Tại Quầy (POS)</span>
                </button>
                <button type="button" class="sidebar-nav-item <?= $activeTab === 'admin-complaints' ? 'active' : '' ?>" 
                        onclick="switchTab('admin-complaints', this)">
                    <span>💬 Quản Lý Khiếu Nại</span>
                    <?php if ($tbQuanTri['choKhieuNai'] > 0): ?>
                        <span class="notif-dot"><?= $tbQuanTri['choKhieuNai'] ?></span>
                    <?php endif; ?>
                </button>
                <button type="button" class="sidebar-nav-item <?= $activeTab === 'admin-voucher' ? 'active' : '' ?>" 
                        onclick="switchTab('admin-voucher', this)">
                    <span>🎟️ Quản Lý Voucher</span>
                </button>

                <?php if (isAdmin()): ?>
                    <div class="sidebar-divider"></div>
                    <div class="sidebar-group-label">Phân Quyền Admin</div>
                    <button type="button" class="sidebar-nav-item <?= $activeTab === 'admin-customer' ? 'active' : '' ?>" 
                            onclick="switchTab('admin-customer', this)">
                        <span>👥 Quản Lý Khách Hàng</span>
                    </button>
                    <button type="button" class="sidebar-nav-item <?= $activeTab === 'admin-staff' ? 'active' : '' ?>" 
                            onclick="switchTab('admin-staff', this)">
                        <span>🧑‍💼 Quản Lý Nhân Viên</span>
                    </button>
                <?php endif; ?>
            </nav>
        </aside>
        <?php endif; ?>
    </div>

    <!-- =================================================================
         MODAL A: BẢN CHI TIẾT SẢN PHẨM KHI ẤN VÀO SẢN PHẨM
         ================================================================= -->
    <div id="productDetailModal" class="modal-overlay">
        <div class="modal-box" style="max-width: 850px;">
            <div class="modal-header">
                <div>
                    <span id="detailBrand" class="product-brand">Việt MAC</span>
                    <h3 id="detailName" style="font-size:20px;">Tên sản phẩm</h3>
                </div>
                <button type="button" class="btn btn-outline btn-sm" onclick="closeModal('productDetailModal')">✕</button>
            </div>

            <div class="detail-grid">
                <div>
                    <img id="detailMainImg" src="" style="width:100%; height:250px; object-fit:contain; border:1px solid #e2e8f0; border-radius:10px; background:#f8fafc; padding:10px;" alt="">
                    <div id="detailThumbs" class="product-thumbs" style="margin-top:10px; justify-content:flex-start; flex-wrap:wrap;"></div>
                </div>

                <div>
                    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:6px; margin-bottom:8px;">
                        <span id="detailRating" style="font-weight:700; color:#d97706; font-size:13.5px;">★ 5.0</span>
                        <span id="detailStockBadge" class="status-badge status-active">Còn hàng</span>
                    </div>
                    <h2 id="detailPrice" style="font-size:24px; color:#0f172a; margin-bottom:8px;">0 đ</h2>
                    <p id="detailDesc" style="font-size:13px; color:#475569; margin-bottom:12px;"></p>

                    <h4 style="font-size:13px; text-transform:uppercase; color:#334155; margin-bottom:6px;">📋 Bảng Thông Số Kỹ Thuật Chi Tiết</h4>
                    <table class="detail-spec-table">
                        <tr><td><strong>Mã sản phẩm:</strong></td><td id="specMaSP">-</td><td><strong>Thương hiệu:</strong></td><td id="specBrand">-</td></tr>
                        <tr><td><strong>Kích thước khung:</strong></td><td id="specSize">M</td><td><strong>Màu sắc:</strong></td><td id="specColor">Đen</td></tr>
                        <tr><td><strong>Chất liệu:</strong></td><td id="specMaterial">Titanium</td><td><strong>Giới tính:</strong></td><td id="specGender">Unisex</td></tr>
                        <tr><td><strong>Rộng khung:</strong></td><td id="specFrameWidth">140 mm</td><td><strong>Rộng cầu mũi:</strong></td><td id="specBridgeWidth">18 mm</td></tr>
                        <tr><td><strong>Rộng thấu kính:</strong></td><td id="specLensWidth">52 mm</td><td><strong>Cao ống kính:</strong></td><td id="specLensHeight">42 mm</td></tr>
                        <tr><td><strong>Dài càng kính:</strong></td><td colspan="3" id="specTempleLength">145 mm</td></tr>
                    </table>

                    <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:14px;">
                        <form action="xuly/giohang_action.php" method="POST" style="flex:1; min-width:170px;">
                            <input type="hidden" name="action" value="add_to_cart">
                            <input type="hidden" name="maSanPham" id="detailFrameOnlyMaSP">
                            <input type="hidden" name="maTrongKinh" value="">
                            <input type="hidden" name="soLuong" value="1">
                            <button type="submit" id="btnDetailAddFrame" class="btn btn-outline" style="width:100%;">
                                🛒 Mua Riêng Gọng Kính
                            </button>
                        </form>

                        <button type="button" id="btnDetailSelectLens" class="btn btn-dark" style="flex:1.2; min-width:190px;">
                            👓 Chọn Tròng Kính Có Độ
                        </button>
                    </div>
                </div>
            </div>

            <!-- Khu vực Đánh giá sản phẩm (DANHGIA) -->
            <div style="margin-top:20px; padding-top:16px; border-top:1px solid #e2e8f0;">
                <h4 style="margin-bottom:10px;">Đánh Giá Từ Khách Hàng</h4>
                <div id="detailReviewList" style="max-height:150px; overflow-y:auto; margin-bottom:12px;"></div>

                <?php if ($maKhachHienTai): ?>
                <form action="xuly/donhang_action.php" method="POST" style="display:flex; gap:8px; flex-wrap:wrap;">
                    <input type="hidden" name="action" value="submit_review">
                    <input type="hidden" name="maSanPham" id="reviewMaSP">
                    <select name="sao" style="padding:8px; border:1px solid #cbd5e1; border-radius:8px;">
                        <option value="5">5 ★ (Tuyệt vời)</option>
                        <option value="4">4 ★ (Tốt)</option>
                        <option value="3">3 ★ (Bình thường)</option>
                        <option value="2">2 ★ (Tệ)</option>
                        <option value="1">1 ★ (Rất tệ)</option>
                    </select>
                    <input type="text" name="binhLuan" placeholder="Viết nhận xét của bạn về mẫu kính này..." style="flex:1; min-width:200px; padding:8px 12px; border:1px solid #cbd5e1; border-radius:8px;" required>
                    <button type="submit" class="btn btn-dark btn-sm">Gửi Đánh Giá</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- =================================================================
         MODAL B: POPUP CHỌN TRÒNG KÍNH 3 BƯỚC -> THÊM VÀO GIỎ HÀNG
         ================================================================= -->
    <div id="lensWizardModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <div>
                    <h3>Cấu Hình Tròng Kính &amp; Độ Mắt - Việt MAC</h3>
                    <p id="wizardProductName" style="font-size:13px; color:#64748b;"></p>
                </div>
                <button type="button" class="btn btn-outline btn-sm" onclick="closeModal('lensWizardModal')">✕</button>
            </div>

            <div class="step-indicator">
                <div class="step-dot active">Bước 1: Nhu cầu</div>
                <div class="step-dot">Bước 2: Độ mắt (SPH/CYL/PD)</div>
                <div class="step-dot">Bước 3: Chọn tròng kính</div>
            </div>

            <!-- BƯỚC 1 -->
            <div id="step1" class="lens-step active">
                <div class="option-box" onclick="goToStep(2, true)">
                    <div>
                        <strong>Kính có độ (Cận / Viễn / Loạn thị)</strong>
                        <p style="font-size:12px; color:#64748b;">Nhập thông số đo mắt cá nhân (Lưu vào KETQUADOMAT)</p>
                    </div>
                    <span>→</span>
                </div>
                <div class="option-box" onclick="goToStep(3, false)">
                    <div>
                        <strong>Kính không độ (Chống ánh sáng xanh / Chống tia UV)</strong>
                        <p style="font-size:12px; color:#64748b;">Chuyển thẳng tới bước chọn tròng bảo vệ mắt</p>
                    </div>
                    <span>→</span>
                </div>
            </div>

            <!-- BƯỚC 2 -->
            <div id="step2" class="lens-step">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Mắt Phải (OD) - Độ cận/viễn (SPH)</label>
                        <input type="text" id="wizardSphOD" placeholder="VD: -2.50">
                    </div>
                    <div class="form-group">
                        <label>Mắt Trái (OS) - Độ cận/viễn (SPH)</label>
                        <input type="text" id="wizardSphOS" placeholder="VD: -2.75">
                    </div>
                    <div class="form-group">
                        <label>Độ loạn (CYL) &amp; Trục (AXIS)</label>
                        <input type="text" id="wizardCylAxis" placeholder="VD: -0.50 / 180">
                    </div>
                    <div class="form-group">
                        <label>Khoảng cách đồng tử (PD - mm)</label>
                        <input type="number" id="wizardPD" placeholder="VD: 62">
                    </div>
                </div>
                <div style="display:flex; justify-content:space-between; margin-top:12px;">
                    <button type="button" class="btn btn-outline" onclick="goToStep(1)">← Quay lại</button>
                    <button type="button" class="btn btn-dark" onclick="goToStep(3)">Tiếp tục chọn tròng →</button>
                </div>
            </div>

            <!-- BƯỚC 3 -->
            <div id="step3" class="lens-step">
                <form action="xuly/giohang_action.php" method="POST" onsubmit="return prepareWizardSubmit()">
                    <input type="hidden" name="action" value="add_to_cart">
                    <input type="hidden" name="maSanPham" id="wizardMaSP">
                    <input type="hidden" name="maTrongKinh" id="wizardMaTrongKinh" value="<?= $dsTrongKinh[0]['maTrongKinh'] ?? '' ?>">
                    <input type="hidden" name="ghiChuDoMat" id="wizardGhiChuDoMat" value="">
                    <input type="hidden" name="soLuong" value="1">

                    <?php foreach ($dsTrongKinh as $idx => $tk): ?>
                        <div class="option-box <?= $idx === 0 ? 'selected' : '' ?>" 
                             onclick="selectLensOption('<?= $tk['maTrongKinh'] ?>', <?= $tk['gia'] ?>, this)">
                            <div>
                                <strong><?= htmlspecialchars($tk['loai']) ?> — Chiết suất <?= $tk['chietSuat'] ?></strong>
                                <p style="font-size:12px; color:#64748b;"><?= htmlspecialchars($tk['tinhNang'] ?? '') ?> (<?= htmlspecialchars($tk['vatLieu'] ?? '') ?>)</p>
                            </div>
                            <strong>+ <?= number_format($tk['gia'], 0, ',', '.') ?> đ</strong>
                        </div>
                    <?php endforeach; ?>

                    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-top:18px; padding-top:12px; border-top:1px solid #e2e8f0;">
                        <div>
                            <span style="font-size:13px; color:#64748b;">Tổng cộng (Gọng + Tròng):</span>
                            <h3 id="wizardTotalPrice">0 đ</h3>
                        </div>
                        <div style="display:flex; gap:8px;">
                            <button type="button" class="btn btn-outline" onclick="goToStep(2)">← Quay lại</button>
                            <button type="submit" class="btn btn-dark">🛒 Thêm Vào Giỏ Hàng</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- =================================================================
         MODAL C: POPUP THANH TOÁN (COD DUYỆT LUÔN + QR TECHCOMBANK CHỜ DUYỆT)
         ================================================================= -->
    <div id="checkoutModal" class="modal-overlay">
        <div class="modal-box" style="max-width: 660px;">
            <div class="modal-header">
                <div>
                    <h3>Thanh Toán Đơn Hàng - Việt MAC</h3>
                    <p style="font-size:12.5px; color:#64748b;">Hoàn tất thông tin nhận hàng và hình thức thanh toán</p>
                </div>
                <button type="button" class="btn btn-outline btn-sm" onclick="cancelCheckoutModal()">✕</button>
            </div>

            <div class="countdown-pill">
                <span>⏳ Hệ thống đang tạm khóa tồn kho giữ hàng cho bạn:</span>
                <span id="checkoutTimerDisplay">15:00</span>
            </div>

            <form action="xuly/donhang_action.php" method="POST">
                <input type="hidden" name="action" value="checkout">

                <div class="form-grid">
                    <div class="form-group">
                        <label>Họ tên người nhận hàng *</label>
                        <input type="text" name="tenNN" required value="<?= htmlspecialchars($hoSoHienTai['hoTen'] ?? '') ?>" placeholder="Nhập họ tên người nhận">
                    </div>
                    <div class="form-group">
                        <label>Số điện thoại nhận hàng *</label>
                        <input type="text" name="soDienThoai" required value="<?= htmlspecialchars($hoSoHienTai['soDienThoai'] ?? '') ?>" placeholder="Nhập SĐT liên hệ">
                    </div>
                    <div class="form-group full">
                        <label>Địa chỉ giao hàng tận nơi *</label>
                        <input type="text" name="diaChi" required value="<?= htmlspecialchars($hoSoHienTai['diaChi'] ?? '') ?>" placeholder="Số nhà, Đường, Phường/Xã, Quận/Huyện, Tỉnh/TP">
                    </div>
                </div>

                <!-- Chọn Voucher đã lưu hoặc nhập mã Voucher -->
                <div style="background:#f8fafc; border:1px dashed #cbd5e1; border-radius:10px; padding:12px; margin-bottom:14px;">
                    <label style="font-size:13px; font-weight:700; display:block; margin-bottom:6px;">🎟️ Chọn Voucher đã lưu hoặc nhập mã:</label>
                    
                    <?php if (!empty($savedVoucherCodes)): ?>
                        <div style="display:flex; gap:6px; flex-wrap:wrap; margin-bottom:8px;">
                            <span style="font-size:12px; color:#64748b; align-self:center;">Voucher trong ví của bạn:</span>
                            <?php foreach ($savedVoucherCodes as $svCode): ?>
                                <button type="button" class="btn btn-outline btn-sm" onclick="selectSavedVoucherInCheckout('<?= htmlspecialchars($svCode) ?>')">
                                    Dùng mã: <strong><?= htmlspecialchars($svCode) ?></strong>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div style="display:flex; gap:8px;">
                        <input type="text" id="checkoutVoucherInput" name="voucherCode" placeholder="Nhập hoặc chọn mã Voucher..." style="flex:1; padding:8px 10px; border:1px solid #cbd5e1; border-radius:6px; text-transform:uppercase;">
                        <button type="button" class="btn btn-dark btn-sm" onclick="applyVoucherInCheckout()">Áp dụng</button>
                    </div>
                    <p id="checkoutVoucherMsg" style="font-size:12px; margin-top:5px;"></p>
                </div>

                <!-- Chọn 1 trong 2 phương thức thanh toán -->
                <div class="form-group">
                    <label>Chọn phương thức thanh toán *</label>
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                        <label class="option-box" style="margin-bottom:0; justify-content:flex-start; gap:8px;">
                            <input type="radio" name="phuongThuc" value="Chuyển khoản QR" checked onchange="togglePaymentMethodUI()" style="width:auto;">
                            <div>
                                <strong>Chuyển khoản QR Techcombank</strong>
                                <p style="font-size:11.5px; color:#64748b;">Quét mã VietQR &amp; chờ xác nhận</p>
                            </div>
                        </label>
                        <label class="option-box" style="margin-bottom:0; justify-content:flex-start; gap:8px;">
                            <input type="radio" name="phuongThuc" value="COD" onchange="togglePaymentMethodUI()" style="width:auto;">
                            <div>
                                <strong>Thanh toán khi nhận hàng (COD)</strong>
                                <p style="font-size:11.5px; color:#64748b;">Duyệt đơn tự động không cần chờ</p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Khung hiển thị mã QR Techcombank -->
                <div id="qrTechcombankBox" class="qr-payment-box">
                    <p style="font-size:13.5px; font-weight:700; color:#92400e;">
                        Quét mã VietQR dưới đây để chuyển khoản số tiền: <span id="qrTransferAmount" style="font-size:16px; color:#dc2626;">0 đ</span>
                    </p>
                    <img src="images/qr_techcombank.jpg" alt="QR Techcombank LY VAN HAI">
                    <p style="font-size:12.5px; color:#78350f;">
                        Chủ TK: <strong>LY VAN HAI</strong> — STK: <strong>6030 3637 38</strong> (Techcombank)<br>
                        Sau khi chuyển khoản xong, bấm nút bên dưới để hệ thống gửi thông báo cho Nhân viên/Admin xác nhận thanh toán.
                    </p>
                </div>

                <!-- Tổng kết thanh toán -->
                <div style="background:#f8fafc; padding:12px 14px; border-radius:8px; margin:14px 0; font-size:13.5px;">
                    <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
                        <span>Tạm tính:</span> <strong id="checkoutSubTotal">0 đ</strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; margin-bottom:6px; color:#065f46;">
                        <span>Giảm giá Voucher:</span> <strong id="checkoutDiscountTotal">-0 đ</strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; font-size:17px; border-top:1px solid #e2e8f0; padding-top:6px;">
                        <strong>Tổng thanh toán:</strong> <strong id="checkoutFinalTotal" style="color:#0f172a;">0 đ</strong>
                    </div>
                </div>

                <div style="display:flex; gap:10px;">
                    <button type="button" class="btn btn-outline" style="flex:1;" onclick="cancelCheckoutModal()">Hủy &amp; Nhả Giữ Hàng</button>
                    <button type="submit" id="btnSubmitCheckout" class="btn btn-dark" style="flex:2;">Tôi Đã Chuyển Khoản - Chờ Xác Nhận</button>
                </div>
            </form>
        </div>
    </div>

    <!-- =================================================================
         MODAL C2: POPUP MỜI KHÁCH VÃNG LAI TẠO TÀI KHOẢN THEO DÕI KIỆN HÀNG
         (Tự động mở sau khi khách vãng lai đặt hàng xong, điền sẵn Tên, SĐT, Địa chỉ)
         ================================================================= -->
    <?php if ($guestJustOrdered): ?>
    <div id="guestConvertAccountModal" class="modal-overlay open">
        <div class="modal-box" style="max-width: 540px;">
            <div class="modal-header">
                <div>
                    <h3>🎉 Đặt Hàng Thành Công (#<?= htmlspecialchars($guestJustOrdered['maDonHang']) ?>)</h3>
                    <p style="font-size:12.5px; color:#065f46; font-weight:600;">Tạo tài khoản thành viên ngay để theo dõi kiện hàng &amp; nhận ưu đãi!</p>
                </div>
                <button type="button" class="btn btn-outline btn-sm" onclick="closeModal('guestConvertAccountModal')">✕</button>
            </div>
            <p style="font-size:13px; color:#475569; margin-bottom:14px;">
                Hệ thống đã điền sẵn <strong>Tên, Số điện thoại và Địa chỉ</strong> từ đơn hàng bạn vừa đặt. Chỉ cần bổ sung Email và Mật khẩu để liên kết đơn hàng này vào tài khoản của bạn:
            </p>
            <form action="xuly/auth_action.php" method="POST" autocomplete="off">
                <input type="hidden" name="action" value="register">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Họ và tên *</label>
                        <input type="text" name="hoTen" autocomplete="off" required value="<?= htmlspecialchars($guestJustOrdered['tenNN']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Số điện thoại *</label>
                        <input type="text" name="soDienThoai" autocomplete="off" required value="<?= htmlspecialchars($guestJustOrdered['soDienThoai']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Email đăng nhập *</label>
                        <input type="email" name="email" autocomplete="new-email" readonly onfocus="this.removeAttribute('readonly');" required placeholder="Nhập email mới của bạn">
                    </div>
                    <div class="form-group">
                        <label>Tạo mật khẩu *</label>
                        <input type="password" name="matKhau" autocomplete="new-password" readonly onfocus="this.removeAttribute('readonly');" required placeholder="Tạo mật khẩu mới">
                    </div>
                    <div class="form-group full">
                        <label>Địa chỉ nhận hàng *</label>
                        <input type="text" name="diaChi" required value="<?= htmlspecialchars($guestJustOrdered['diaChi']) ?>">
                    </div>
                </div>
                <div style="display:flex; gap:10px; margin-top:6px;">
                    <button type="button" class="btn btn-outline" style="flex:1;" onclick="closeModal('guestConvertAccountModal')">Để sau</button>
                    <button type="submit" class="btn btn-dark" style="flex:2;">Tạo Tài Khoản &amp; Theo Dõi Đơn</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- =================================================================
         MODAL D: QUẢN TRỊ VIÊN SỬA THÔNG TIN ĐƠN HÀNG CỦA KHÁCH
         ================================================================= -->
    <div id="editOrderModal" class="modal-overlay">
        <div class="modal-box" style="max-width: 520px;">
            <div class="modal-header">
                <h3 id="editOdTitle">Sửa Thông Tin Đơn Hàng</h3>
                <button type="button" class="btn btn-outline btn-sm" onclick="closeModal('editOrderModal')">✕</button>
            </div>
            <form action="xuly/donhang_action.php" method="POST">
                <input type="hidden" name="action" value="edit_order">
                <input type="hidden" name="maDonHang" id="editOdMaDonHang">

                <div class="form-grid">
                    <div class="form-group">
                        <label>Tên người nhận hàng *</label>
                        <input type="text" name="tenNN" id="editOdTenNN" required>
                    </div>
                    <div class="form-group">
                        <label>Số điện thoại nhận hàng *</label>
                        <input type="text" name="soDienThoai" id="editOdSdt" required>
                    </div>
                    <div class="form-group full">
                        <label>Địa chỉ giao hàng *</label>
                        <input type="text" name="diaChi" id="editOdDiaChi" required>
                    </div>
                    <div class="form-group">
                        <label>Phương thức thanh toán</label>
                        <select name="phuongThuc" id="editOdPhuongThuc">
                            <option value="COD">COD</option>
                            <option value="Chuyển khoản QR">Chuyển khoản QR</option>
                            <option value="Tiền mặt tại quầy">Tiền mặt tại quầy</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Trạng thái đơn hàng</label>
                        <select name="trangThaiDon" id="editOdTrangThai">
                            <option value="Chờ xác nhận thanh toán">Chờ xác nhận thanh toán</option>
                            <option value="Đã xác nhận (COD)">Đã xác nhận (COD)</option>
                            <option value="Đã thanh toán">Đã thanh toán</option>
                            <option value="Đang xử lý">Đang xử lý</option>
                            <option value="Đang mài lắp">Đang mài lắp</option>
                            <option value="Đang giao hàng">Đang giao hàng</option>
                            <option value="Đã giao">Đã giao</option>
                            <option value="Chờ xác nhận hủy">Chờ xác nhận hủy</option>
                            <option value="Đã hủy">Đã hủy</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-dark" style="width:100%; margin-top:6px;">Lưu Thay Đổi Đơn Hàng</button>
            </form>
        </div>
    </div>

    <!-- =================================================================
         MODAL E: SỬA THÔNG TIN CÁ NHÂN (KHI BẤM VÀO TÊN TRÊN MENU)
         ================================================================= -->
    <?php if (isLogged() && $hoSoHienTai): ?>
    <div id="profileModal" class="modal-overlay">
        <div class="modal-box" style="max-width: 540px;">
            <div class="modal-header">
                <h3>Sửa Thông Tin Cá Nhân</h3>
                <button type="button" class="btn btn-outline btn-sm" onclick="closeModal('profileModal')">✕</button>
            </div>
            <form action="xuly/auth_action.php" method="POST">
                <input type="hidden" name="action" value="update_profile">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Họ và tên *</label>
                        <input type="text" name="hoTen" required value="<?= htmlspecialchars($hoSoHienTai['hoTen']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Email đăng nhập *</label>
                        <input type="email" name="email" required value="<?= htmlspecialchars($hoSoHienTai['email']) ?>">
                    </div>
                    <?php if (!empty($hoSoHienTai['maKhachHang'])): ?>
                    <div class="form-group">
                        <label>Số điện thoại *</label>
                        <input type="text" name="soDienThoai" required value="<?= htmlspecialchars($hoSoHienTai['soDienThoai'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Giới tính</label>
                        <select name="gioiTinh">
                            <option value="Nam" <?= ($hoSoHienTai['gioiTinh'] ?? '') === 'Nam' ? 'selected' : '' ?>>Nam</option>
                            <option value="Nữ" <?= ($hoSoHienTai['gioiTinh'] ?? '') === 'Nữ' ? 'selected' : '' ?>>Nữ</option>
                            <option value="Khác" <?= ($hoSoHienTai['gioiTinh'] ?? '') === 'Khác' ? 'selected' : '' ?>>Khác</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Ngày sinh</label>
                        <input type="date" name="ngaySinh" value="<?= !empty($hoSoHienTai['ngaySinh']) ? substr($hoSoHienTai['ngaySinh'], 0, 10) : '' ?>">
                    </div>
                    <div class="form-group">
                        <label>Mật khẩu mới (để trống nếu không đổi)</label>
                        <input type="password" name="matKhauMoi" placeholder="Nhập mật khẩu mới...">
                    </div>
                    <div class="form-group full">
                        <label>Địa chỉ giao hàng mặc định</label>
                        <input type="text" name="diaChi" value="<?= htmlspecialchars($hoSoHienTai['diaChi'] ?? '') ?>">
                    </div>
                    <?php else: ?>
                    <div class="form-group full">
                        <label>Mật khẩu mới (để trống nếu không đổi)</label>
                        <input type="password" name="matKhauMoi" placeholder="Nhập mật khẩu mới...">
                    </div>
                    <?php endif; ?>
                </div>
                <button type="submit" class="btn btn-dark" style="width:100%; margin-top:6px;">Lưu Thay Đổi</button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- =================================================================
         MODAL F: XEM CHI TIẾT ĐƠN HÀNG & GỬI KHIẾU NẠI KHÁCH HÀNG
         ================================================================= -->
    <div id="orderDetailModal" class="modal-overlay">
        <div class="modal-box" style="max-width: 620px;">
            <div class="modal-header">
                <h3 id="odModalTitle">Chi Tiết Đơn Hàng</h3>
                <button type="button" class="btn btn-outline btn-sm" onclick="closeModal('orderDetailModal')">✕</button>
            </div>
            <div id="odCustomerInfo" style="font-size:13.5px; margin-bottom:14px; line-height:1.7;"></div>
            <table>
                <thead>
                    <tr><th>Sản Phẩm</th><th>Tròng Kính</th><th>Đơn Giá</th></tr>
                </thead>
                <tbody id="odItemsBody"></tbody>
            </table>
            <div style="text-align:right; margin-top:14px; font-size:17px;">
                Tổng thanh toán: <strong id="odTotalAmount">0 đ</strong>
            </div>
        </div>
    </div>

    <div id="complaintModal" class="modal-overlay">
        <div class="modal-box" style="max-width: 520px;">
            <div class="modal-header">
                <div>
                    <h3>Gửi Khiếu Nại Hỗ Trợ</h3>
                    <p id="complaintOrderBadge" style="font-size:12px; color:#64748b;"></p>
                </div>
                <button type="button" class="btn btn-outline btn-sm" onclick="closeModal('complaintModal')">✕</button>
            </div>
            <form action="xuly/khieunai_action.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="maDonHang" id="complaintMaDonHang">

                <div class="form-group">
                    <label>Phân loại khiếu nại *</label>
                    <select name="loaiKhieuNai" required>
                        <option value="đơn hàng">Khiếu nại về đơn hàng / giao vận</option>
                        <option value="thanh toán">Khiếu nại về thanh toán / chuyển khoản</option>
                        <option value="sản phẩm">Khiếu nại về chất lượng gọng / tròng kính</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Tiêu đề khiếu nại *</label>
                    <input type="text" name="tieuDe" required placeholder="VD: Gọng kính bị lệch bản lề khi nhận hàng">
                </div>
                <div class="form-group">
                    <label>Nội dung chi tiết *</label>
                    <textarea name="noiDung" rows="3" required placeholder="Mô tả rõ vấn đề bạn gặp phải để Việt MAC hỗ trợ đổi/bảo hành..."></textarea>
                </div>
                <div class="form-group">
                    <label>Ảnh minh chứng (Lưu vào images/anh_khieunai/)</label>
                    <input type="file" name="hinhAnh" accept="image/*">
                </div>
                <button type="submit" class="btn btn-danger" style="width:100%;">Gửi Khiếu Nại</button>
            </form>
        </div>
    </div>

    <div id="replyComplaintModal" class="modal-overlay">
        <div class="modal-box" style="max-width: 540px;">
            <div class="modal-header">
                <h3 id="replyComplaintTitle">Phản Hồi Khiếu Nại</h3>
                <button type="button" class="btn btn-outline btn-sm" onclick="closeModal('replyComplaintModal')">✕</button>
            </div>
            <div id="replyComplaintInfo" style="font-size:13px; margin-bottom:14px;"></div>
            <form action="xuly/khieunai_action.php" method="POST">
                <input type="hidden" name="action" value="reply">
                <input type="hidden" name="maKhieuNai" id="replyMaKhieuNai">

                <div class="form-group">
                    <label>Cập nhật trạng thái xử lý *</label>
                    <select name="trangThai" id="replyTrangThai">
                        <option value="Chờ xử lý">Chờ xử lý</option>
                        <option value="Đang xử lý">Đang xử lý</option>
                        <option value="Đã giải quyết">Đã giải quyết</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Nội dung phản hồi tới khách hàng *</label>
                    <textarea name="phanHoi" id="replyPhanHoi" rows="3" required placeholder="Nhập hướng giải quyết từ Việt MAC..."></textarea>
                </div>
                <button type="submit" class="btn btn-dark" style="width:100%;">Lưu Phản Hồi</button>
            </form>
        </div>
    </div>

    <!-- =================================================================
         MODAL G: ĐĂNG NHẬP & ĐĂNG KÝ
         ================================================================= -->
    <div id="loginModal" class="modal-overlay">
        <div class="modal-box" style="max-width: 410px;">
            <div class="modal-header">
                <h3>Đăng Nhập Việt MAC</h3>
                <button type="button" class="btn btn-outline btn-sm" tabindex="-1" onclick="closeModal('loginModal')">✕</button>
            </div>
            <form action="xuly/auth_action.php" method="POST" autocomplete="on">
                <input type="hidden" name="action" value="login">
                <div class="form-group">
                    <label for="loginEmail">Email đăng nhập</label>
                    <input type="email" id="loginEmail" name="email" tabindex="1" placeholder="Nhập địa chỉ email của bạn" autocomplete="email" required>
                    <small id="emailSuggestHint" style="display:none; color:#0f172a; font-size:12px; margin-top:5px;"></small>
                </div>
                <div class="form-group">
                    <label for="loginPassword">Mật khẩu</label>
                    <input type="password" id="loginPassword" name="matKhau" tabindex="2" placeholder="Nhập mật khẩu" autocomplete="current-password" required>
                </div>
                <button type="submit" tabindex="3" class="btn btn-dark" style="width:100%; margin-top:4px;">Đăng Nhập</button>
                <div class="auth-switch-text">
                    Chưa có tài khoản thành viên? <a onclick="switchAuthModal('loginModal', 'registerModal')">Đăng ký ngay</a>
                </div>
            </form>
        </div>
    </div>

    <div id="registerModal" class="modal-overlay">
        <div class="modal-box" style="max-width: 550px;">
            <div class="modal-header">
                <div>
                    <h3>Đăng Ký Thành Viên Việt MAC</h3>
                    <p style="font-size:12px; color:#64748b;">Lưu trữ thông tin giao hàng, đơn kính thuốc &amp; nhận ưu đãi Voucher</p>
                </div>
                <button type="button" class="btn btn-outline btn-sm" onclick="closeModal('registerModal')">✕</button>
            </div>
            <form action="xuly/auth_action.php" method="POST" autocomplete="off">
                <input type="hidden" name="action" value="register">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Họ và tên *</label>
                        <input type="text" name="hoTen" autocomplete="off" required placeholder="VD: Nguyễn Văn A">
                    </div>
                    <div class="form-group">
                        <label>Số điện thoại liên lạc *</label>
                        <input type="text" name="soDienThoai" autocomplete="off" required placeholder="VD: 0901234567">
                    </div>
                    <div class="form-group">
                        <label>Địa chỉ Email *</label>
                        <input type="email" name="email" autocomplete="new-email" readonly onfocus="this.removeAttribute('readonly');" required placeholder="khachhang@gmail.com">
                    </div>
                    <div class="form-group">
                        <label>Mật khẩu *</label>
                        <input type="password" name="matKhau" autocomplete="new-password" readonly onfocus="this.removeAttribute('readonly');" required placeholder="Nhập mật khẩu">
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
                        <input type="text" name="diaChi" required placeholder="Số nhà, Đường, Phường/Xã, Quận/Huyện, Tỉnh/TP">
                    </div>
                </div>
                <button type="submit" class="btn btn-dark" style="width:100%; margin-top:6px;">Đăng Ký Tài Khoản</button>
                <div class="auth-switch-text">
                    Đã có tài khoản? <a onclick="switchAuthModal('registerModal', 'loginModal')">Đăng nhập ngay</a>
                </div>
            </form>
        </div>
    </div>

    <!-- =================================================================
         MODAL H: CÁC MODAL CRUD QUẢN TRỊ (THƯƠNG HIỆU, SẢN PHẨM, KHÁCH HÀNG, NHÂN VIÊN, VOUCHER)
         ================================================================= -->
    <div id="brandModal" class="modal-overlay">
        <div class="modal-box" style="max-width: 480px;">
            <div class="modal-header">
                <h3 id="brandModalTitle">Thêm Thương Hiệu</h3>
                <button type="button" class="btn btn-outline btn-sm" onclick="closeModal('brandModal')">✕</button>
            </div>
            <form id="brandForm" action="xuly/thuonghieu_action.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" id="brandFormAction" value="add">
                <input type="hidden" name="maThuongHieu" id="maThuongHieu">
                <div class="form-group"><label>Tên thương hiệu *</label><input type="text" name="tenThuongHieu" id="tenThuongHieu" required></div>
                <div class="form-group"><label>Logo thương hiệu</label><input type="file" name="logo" accept="image/*"></div>
                <div class="form-group"><label>Mô tả thương hiệu</label><textarea name="moTa" id="moTaTH" rows="3"></textarea></div>
                <button type="submit" class="btn btn-dark" style="width:100%;">Lưu Thương Hiệu</button>
            </form>
        </div>
    </div>

    <div id="productModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <h3 id="productModalTitle">Thêm Sản Phẩm Kính</h3>
                <button type="button" class="btn btn-outline btn-sm" onclick="closeModal('productModal')">✕</button>
            </div>
            <form id="productForm" action="xuly/sanpham_action.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" id="productFormAction" value="add">
                <input type="hidden" name="maSanPham" id="maSanPham">
                <div class="form-grid">
                    <div class="form-group"><label>Tên sản phẩm *</label><input type="text" name="tenSanPham" id="tenSanPham" required></div>
                    <div class="form-group">
                        <label>Thương hiệu *</label>
                        <select name="maThuongHieu" id="maThuongHieuSP" required>
                            <option value="">-- Chọn thương hiệu --</option>
                            <?php foreach ($dsThuongHieu as $th): ?>
                                <option value="<?= $th['maThuongHieu'] ?>"><?= htmlspecialchars($th['tenThuongHieu']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group"><label>Giá bán niêm yết (VNĐ) *</label><input type="number" name="gia" id="gia" required min="0"></div>
                    <div class="form-group"><label>Số lượng tồn kho (TONKO) *</label><input type="number" name="soLuong" id="soLuong" required min="0" value="10"></div>
                    <div class="form-group"><label>Màu sắc gọng *</label><input type="text" name="mauSac" id="mauSac" required placeholder="Đen nhám, Bạc..."></div>
                    <div class="form-group"><label>Chất liệu (vatLieu)</label><input type="text" name="vatLieu" id="vatLieu" placeholder="Titanium, Acetate..."></div>
                    <div class="form-group">
                        <label>Kích thước</label>
                        <select name="kichThuoc" id="kichThuoc"><option value="S">Size S</option><option value="M" selected>Size M</option><option value="L">Size L</option></select>
                    </div>
                    <div class="form-group">
                        <label>Giới tính</label>
                        <select name="gioiTinh" id="gioiTinh"><option value="Unisex">Unisex</option><option value="Nam">Nam</option><option value="Nữ">Nữ</option></select>
                    </div>
                    <div class="form-group">
                        <label>Khung / Cầu / Thấu kính (mm)</label>
                        <div style="display:flex; gap:6px;">
                            <input type="number" step="0.1" name="chieuRongKhung" id="chieuRongKhung" placeholder="140">
                            <input type="number" step="0.1" name="chieuRongCau" id="chieuRongCau" placeholder="18">
                            <input type="number" step="0.1" name="chieuRongThauKinh" id="chieuRongThauKinh" placeholder="52">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Cao ống kính / Dài càng (mm)</label>
                        <div style="display:flex; gap:6px;">
                            <input type="number" step="0.1" name="chieuCaoOngKinh" id="chieuCaoOngKinh" placeholder="42">
                            <input type="number" step="0.1" name="chieuDaiThaiDuong" id="chieuDaiThaiDuong" placeholder="145">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Trạng thái</label>
                        <select name="trangThai" id="trangThai">
                            <option value="Đang bán">Đang bán</option>
                            <option value="Tạm hết">Tạm hết</option>
                            <option value="Tạm ngưng bán">Tạm ngưng bán</option>
                            <option value="Ngừng bán">Ngừng bán</option>
                        </select>
                    </div>
                    <div class="form-group" id="uploadModeSection" style="display:none;">
                        <label>Chế độ cập nhật ảnh</label>
                        <select name="cheDoAnh">
                            <option value="append">Giữ ảnh cũ &amp; Thêm ảnh mới</option>
                            <option value="replace">Thay toàn bộ bằng ảnh mới</option>
                        </select>
                    </div>
                    <div class="form-group full">
                        <label>Tải lên nhiều ảnh sản phẩm (multiple)</label>
                        <input type="file" name="hinhAnh[]" accept="image/*" multiple onchange="previewMultipleImages(this)">
                        <div id="newImagesPreview" class="existing-images-grid"></div>
                    </div>
                    <div class="form-group full" id="existingImagesSection" style="display:none;">
                        <label>Bộ ảnh hiện tại:</label>
                        <div id="existingImagesList" class="existing-images-grid"></div>
                    </div>
                    <div class="form-group full"><label>Mô tả sản phẩm</label><textarea name="moTa" id="moTaSP" rows="2"></textarea></div>
                </div>
                <button type="submit" class="btn btn-dark" style="width:100%;">Lưu Sản Phẩm</button>
            </form>
        </div>
    </div>

    <div id="customerEditModal" class="modal-overlay">
        <div class="modal-box" style="max-width: 520px;">
            <div class="modal-header">
                <h3>Cập Nhật Hồ Sơ Khách Hàng</h3>
                <button type="button" class="btn btn-outline btn-sm" onclick="closeModal('customerEditModal')">✕</button>
            </div>
            <form action="xuly/khachhang_action.php" method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="maKhachHang" id="editMaKhachHang">
                <input type="hidden" name="maNguoiDung" id="editMaNguoiDungKH">
                <div class="form-grid">
                    <div class="form-group"><label>Họ và tên *</label><input type="text" name="hoTen" id="editHoTenKH" required></div>
                    <div class="form-group"><label>Email *</label><input type="email" name="email" id="editEmailKH" required></div>
                    <div class="form-group"><label>Số điện thoại *</label><input type="text" name="soDienThoai" id="editSdtKH" required></div>
                    <div class="form-group">
                        <label>Giới tính</label>
                        <select name="gioiTinh" id="editGioiTinhKH"><option value="Nam">Nam</option><option value="Nữ">Nữ</option><option value="Khác">Khác</option></select>
                    </div>
                    <div class="form-group"><label>Ngày sinh</label><input type="date" name="ngaySinh" id="editNgaySinhKH"></div>
                    <div class="form-group"><label>Mật khẩu mới (nếu đổi)</label><input type="password" name="matKhau" id="editMatKhauKH"></div>
                    <div class="form-group full"><label>Địa chỉ mặc định</label><input type="text" name="diaChi" id="editDiaChiKH"></div>
                </div>
                <button type="submit" class="btn btn-dark" style="width:100%;">Lưu Hồ Sơ Khách Hàng</button>
            </form>
        </div>
    </div>

    <div id="staffModal" class="modal-overlay">
        <div class="modal-box" style="max-width: 460px;">
            <div class="modal-header">
                <h3 id="staffModalTitle">Thêm Nhân Viên</h3>
                <button type="button" class="btn btn-outline btn-sm" onclick="closeModal('staffModal')">✕</button>
            </div>
            <form id="staffForm" action="xuly/nhanvien_action.php" method="POST">
                <input type="hidden" name="action" id="staffFormAction" value="add">
                <input type="hidden" name="maNguoiDung" id="maNguoiDungStaff">
                <div class="form-group"><label>Họ và tên nhân viên *</label><input type="text" name="hoTen" id="hoTenStaff" required></div>
                <div class="form-group"><label>Email đăng nhập *</label><input type="email" name="email" id="emailStaff" required></div>
                <div class="form-group"><label>Mật khẩu *</label><input type="password" name="matKhau" id="matKhauStaff"></div>
                <button type="submit" class="btn btn-dark" style="width:100%;">Lưu Nhân Viên</button>
            </form>
        </div>
    </div>

    <div id="voucherModal" class="modal-overlay">
        <div class="modal-box" style="max-width: 540px;">
            <div class="modal-header">
                <h3 id="voucherModalTitle">Phát Hành Voucher</h3>
                <button type="button" class="btn btn-outline btn-sm" onclick="closeModal('voucherModal')">✕</button>
            </div>
            <form id="voucherForm" action="xuly/voucher_action.php" method="POST">
                <input type="hidden" name="action" id="voucherFormAction" value="add">
                <input type="hidden" name="maVoucher" id="maVoucher">
                <div class="form-grid">
                    <div class="form-group"><label>Mã Code *</label><input type="text" name="code" id="codeVoucher" required style="text-transform:uppercase;"></div>
                    <div class="form-group">
                        <label>Loại giảm *</label>
                        <select name="loaiGiam" id="loaiGiam"><option value="Tiền mặt">Tiền mặt (VNĐ)</option><option value="Phần trăm">Phần trăm (%)</option></select>
                    </div>
                    <div class="form-group"><label>Giá trị giảm *</label><input type="number" step="0.01" min="1" name="giaTriGiam" id="giaTriGiam" required></div>
                    <div class="form-group"><label>Đơn tối thiểu (VNĐ)</label><input type="number" step="1000" min="0" name="donHangToiThieu" id="donHangToiThieu" value="0"></div>
                    <div class="form-group"><label>Giới hạn lượt dùng</label><input type="number" min="1" name="gioiHanDung" id="gioiHanDung"></div>
                    <div class="form-group"><label>Ngày hết hạn *</label><input type="datetime-local" name="ngayHetHan" id="ngayHetHan" required></div>
                </div>
                <button type="submit" class="btn btn-dark" style="width:100%;">Lưu Voucher</button>
            </form>
        </div>
    </div>

    <script>
        window.PRODUCTS_MAP = <?= json_encode($productsMap, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    </script>
    <script src="js/main.js"></script>
</body>
</html>
