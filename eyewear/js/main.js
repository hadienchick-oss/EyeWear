// =========================================================
// 1. ĐIỀU HƯỚNG TAB, MENU MOBILE & DROPDOWN TÀI KHOẢN
// =========================================================
function switchTab(tabId, el) {
    document.querySelectorAll('.section-view').forEach(sec => sec.classList.remove('active'));
    document.querySelectorAll('.nav-links a, .sidebar-nav-item').forEach(link => link.classList.remove('active'));

    const target = document.getElementById(tabId);
    if (target) target.classList.add('active');
    if (el) {
        el.classList.add('active');
    } else {
        document.querySelectorAll(`[onclick*="switchTab('${tabId}'"]`).forEach(btn => btn.classList.add('active'));
    }

    const navLinks = document.querySelector('.nav-links');
    if (navLinks) navLinks.classList.remove('open');
}

function toggleMobileMenu() {
    const navLinks = document.querySelector('.nav-links');
    if (navLinks) navLinks.classList.toggle('open');
}

function toggleUserDropdown(e) {
    if (e) e.stopPropagation();
    const menu = document.getElementById('userDropdownMenu');
    if (menu) menu.classList.toggle('open');
}

document.addEventListener('click', function(e) {
    const userDropdown = document.getElementById('userDropdown');
    const userMenu = document.getElementById('userDropdownMenu');
    if (userDropdown && userMenu && !userDropdown.contains(e.target)) {
        userMenu.classList.remove('open');
    }
});

// =========================================================
// 2. QUẢN LÝ MODAL (LUÔN HIỂN THỊ Ở TRÊN ĐẦU, KHÔNG TỰ LƯỚT XUỐNG)
// =========================================================
function openModal(modalId, autoFocus = true) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    modal.classList.add('open');
    modal.scrollTop = 0;

    const box = modal.querySelector('.modal-box');
    if (box) box.scrollTop = 0;

    if (autoFocus) {
        setTimeout(() => {
            const firstInput = modal.querySelector('input:not([type="hidden"]):not([readonly]), select, textarea');
            if (firstInput) firstInput.focus({ preventScroll: true });
        }, 60);
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.remove('open');
}

function switchAuthModal(fromModalId, toModalId) {
    closeModal(fromModalId);
    openModal(toModalId);
}

// =========================================================
// 3. TÌM KIẾM THÔNG MINH CHO KHÁCH HÀNG & TÌM KIẾM QUẢN TRỊ
// =========================================================
let selectedColorChip = '';

function selectColorChip(colorVal, chipEl) {
    selectedColorChip = colorVal.toLowerCase();
    document.querySelectorAll('.color-chip').forEach(c => c.classList.remove('active'));
    if (chipEl) chipEl.classList.add('active');
    filterShopProducts();
}

function filterShopProducts() {
    const rawQuery = (document.getElementById('shopSearchInput')?.value || '').toLowerCase().trim();
    const brandFilter = (document.getElementById('shopBrandFilter')?.value || '').toLowerCase().trim();

    const stopWords = ['kính', 'mắt', 'gọng', 'màu', 'hãng', 'thương', 'hiệu', 'loại', 'mẫu', 'của', 'cho', 'và'];
    const tokens = rawQuery
        .split(/\s+/)
        .filter(w => w.length > 0 && !stopWords.includes(w));

    const cards = document.querySelectorAll('#shopProductGrid .product-card');
    let visibleCount = 0;

    cards.forEach(card => {
        const searchBlob = (card.dataset.search || '').toLowerCase();
        const cardBrand = (card.dataset.brand || '').toLowerCase();
        const cardColor = (card.dataset.color || '').toLowerCase();

        const matchTokens = tokens.every(tok => searchBlob.includes(tok));
        const matchBrand = !brandFilter || cardBrand === brandFilter;
        const matchColor = !selectedColorChip || cardColor.includes(selectedColorChip);

        if (matchTokens && matchBrand && matchColor) {
            card.style.display = 'flex';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });

    const emptyNotice = document.getElementById('shopEmptySearch');
    if (emptyNotice) {
        emptyNotice.style.display = visibleCount === 0 ? 'block' : 'none';
    }
}

function filterAdminTable(inputId, tableBodyId) {
    const q = (document.getElementById(inputId)?.value || '').toLowerCase().trim();
    const rows = document.querySelectorAll('#' + tableBodyId + ' tr[data-search]');
    rows.forEach(row => {
        const text = (row.dataset.search || '').toLowerCase();
        row.style.display = text.includes(q) ? '' : 'none';
    });
}

// =========================================================
// 4. BẢN CHI TIẾT SẢN PHẨM (XEM ĐƯỢC KỂ CẢ KHI CHƯA ĐĂNG NHẬP & Ở TRÊN ĐẦU)
// =========================================================
function openProductDetail(maSP) {
    const sp = (window.PRODUCTS_MAP && window.PRODUCTS_MAP[maSP]) ? window.PRODUCTS_MAP[maSP] : null;
    if (!sp) return;

    document.getElementById('detailBrand').innerText = sp.tenThuongHieu || 'Việt MAC';
    document.getElementById('detailName').innerText = sp.tenSanPham + ' (' + sp.maSanPham + ')';
    document.getElementById('detailRating').innerText = '★ ' + (sp.diemTrungBinh || '5.0') + ' (' + (sp.dsDanhGia ? sp.dsDanhGia.length : 0) + ' đánh giá) • Đã bán: ' + (sp.daBan || 0);
    document.getElementById('detailPrice').innerText = parseFloat(sp.gia).toLocaleString('vi-VN') + ' đ';

    const tonKhaDung = parseInt(sp.soLuong || 0) - parseInt(sp.soLuongDat || 0);
    const coTheMua = (sp.trangThai === 'Đang bán' && tonKhaDung > 0);
    const stockBadge = document.getElementById('detailStockBadge');
    stockBadge.innerText = coTheMua ? ('Còn hàng (' + tonKhaDung + ' chiếc)') : 'Tạm hết hàng';
    stockBadge.className = 'status-badge ' + (coTheMua ? 'status-active' : 'status-warning');

    const mainImg = document.getElementById('detailMainImg');
    const thumbList = document.getElementById('detailThumbs');
    thumbList.innerHTML = '';

    if (sp.dsAnh && sp.dsAnh.length > 0) {
        mainImg.src = 'images/anh_sp/' + sp.dsAnh[0].hinhAnh;
        sp.dsAnh.forEach((img, idx) => {
            const t = document.createElement('img');
            t.src = 'images/anh_sp/' + img.hinhAnh;
            t.className = 'product-thumb-item' + (idx === 0 ? ' active-thumb' : '');
            t.onclick = function() {
                mainImg.src = t.src;
                thumbList.querySelectorAll('.product-thumb-item').forEach(el => el.classList.remove('active-thumb'));
                t.classList.add('active-thumb');
            };
            thumbList.appendChild(t);
        });
    } else {
        mainImg.src = 'https://via.placeholder.com/340x200?text=Viet+MAC';
    }

    // Bảng thông số kỹ thuật chi tiết (CHITIETSANPHAM)
    document.getElementById('specMaSP').innerText = sp.maSanPham;
    document.getElementById('specBrand').innerText = sp.tenThuongHieu || 'Việt MAC';
    document.getElementById('specSize').innerText = sp.kichThuoc || 'M';
    document.getElementById('specColor').innerText = sp.mauSac || 'Đen';
    document.getElementById('specMaterial').innerText = sp.vatLieu || 'Acetate / Titanium';
    document.getElementById('specGender').innerText = sp.gioiTinh || 'Unisex';
    document.getElementById('specFrameWidth').innerText = (sp.chieuRongKhung || '140') + ' mm';
    document.getElementById('specBridgeWidth').innerText = (sp.chieuRongCau || '18') + ' mm';
    document.getElementById('specLensWidth').innerText = (sp.chieuRongThauKinh || '52') + ' mm';
    document.getElementById('specLensHeight').innerText = (sp.chieuCaoOngKinh || '42') + ' mm';
    document.getElementById('specTempleLength').innerText = (sp.chieuDaiThaiDuong || '145') + ' mm';
    document.getElementById('detailDesc').innerText = sp.moTa || 'Thiết kế tinh tế, nhẹ nhàng ôm sát sống mũi, phù hợp lắp mọi loại tròng cận/loạn.';

    const frameMaSPInput = document.getElementById('detailFrameOnlyMaSP');
    if (frameMaSPInput) frameMaSPInput.value = sp.maSanPham;

    // Kiểm tra tồn tại của #reviewMaSP trước khi gán (tránh lỗi khi khách chưa đăng nhập)
    const reviewMaSPInput = document.getElementById('reviewMaSP');
    if (reviewMaSPInput) reviewMaSPInput.value = sp.maSanPham;

    const btnFrameOnly = document.getElementById('btnDetailAddFrame');
    const btnSelectLens = document.getElementById('btnDetailSelectLens');

    if (coTheMua) {
        btnFrameOnly.disabled = false;
        btnFrameOnly.classList.remove('btn-disabled');
        btnSelectLens.disabled = false;
        btnSelectLens.classList.remove('btn-disabled');
        btnSelectLens.onclick = function() {
            closeModal('productDetailModal');
            openLensWizard(sp.maSanPham, sp.tenSanPham, sp.gia);
        };
    } else {
        btnFrameOnly.disabled = true;
        btnFrameOnly.classList.add('btn-disabled');
        btnSelectLens.disabled = true;
        btnSelectLens.classList.add('btn-disabled');
    }

    const revList = document.getElementById('detailReviewList');
    revList.innerHTML = '';
    if (sp.dsDanhGia && sp.dsDanhGia.length > 0) {
        sp.dsDanhGia.forEach(dg => {
            const div = document.createElement('div');
            div.style.cssText = 'padding:8px 10px; border-bottom:1px solid #f1f5f9; font-size:13px;';
            div.innerHTML = `<strong>${dg.hoTen}</strong> <span style="color:#f59e0b;">${'★'.repeat(dg.sao)}</span><p style="color:#475569; margin-top:2px;">${dg.binhLuan || ''}</p>`;
            revList.appendChild(div);
        });
    } else {
        revList.innerHTML = '<p style="font-size:12.5px; color:#94a3b8;">Chưa có đánh giá nào cho sản phẩm này.</p>';
    }

    // autoFocus = false để modal luôn nằm ở đầu trang, không tự cuộn xuống form đánh giá ở đáy
    openModal('productDetailModal', false);
}

function changeProductCardImage(maSP, imgSrc, thumbEl, e) {
    if (e) e.stopPropagation();
    const mainImg = document.getElementById('main-img-' + maSP);
    if (mainImg) mainImg.src = imgSrc;
    const parentThumbs = thumbEl.parentElement.querySelectorAll('.product-thumb-item');
    parentThumbs.forEach(t => t.classList.remove('active-thumb'));
    thumbEl.classList.add('active-thumb');
}

// =========================================================
// 5. POPUP CHỌN TRÒNG KÍNH 3 BƯỚC -> THÊM VÀO GIỎ HÀNG
// =========================================================
let wizardFramePrice = 0;
let wizardLensPrice = 0;
let wizardHasPrescription = false;

function openLensWizard(maSP, tenSP, giaGong) {
    wizardFramePrice = parseFloat(giaGong);
    wizardLensPrice = 0;
    wizardHasPrescription = false;

    document.getElementById('wizardMaSP').value = maSP;
    document.getElementById('wizardProductName').innerText = tenSP + ' (' + maSP + ')';
    document.getElementById('wizardSphOD').value = '';
    document.getElementById('wizardSphOS').value = '';
    document.getElementById('wizardCylAxis').value = '';
    document.getElementById('wizardPD').value = '';

    const firstLensBox = document.querySelector('#step3 .option-box');
    if (firstLensBox) {
        firstLensBox.click();
    } else {
        updateWizardTotal();
    }

    goToStep(1, false);
    openModal('lensWizardModal', false);
}

function goToStep(step, hasRx = null) {
    if (hasRx !== null) {
        wizardHasPrescription = hasRx;
    }
    document.querySelectorAll('.lens-step').forEach((el, idx) => {
        el.classList.toggle('active', idx + 1 === step);
    });
    document.querySelectorAll('.step-dot').forEach((el, idx) => {
        el.classList.toggle('active', idx + 1 === step);
    });
}

function selectLensOption(maTrongKinh, price, el) {
    wizardLensPrice = parseFloat(price);
    document.getElementById('wizardMaTrongKinh').value = maTrongKinh;
    document.querySelectorAll('#step3 .option-box').forEach(box => box.classList.remove('selected'));
    el.classList.add('selected');
    updateWizardTotal();
}

function updateWizardTotal() {
    const total = wizardFramePrice + wizardLensPrice;
    document.getElementById('wizardTotalPrice').innerText = total.toLocaleString('vi-VN') + ' đ';
}

function prepareWizardSubmit() {
    if (wizardHasPrescription) {
        const od = document.getElementById('wizardSphOD').value.trim() || '0.00';
        const os = document.getElementById('wizardSphOS').value.trim() || '0.00';
        const cyl = document.getElementById('wizardCylAxis').value.trim() || 'Không loạn';
        const pd = document.getElementById('wizardPD').value.trim() || '62';
        document.getElementById('wizardGhiChuDoMat').value = `OD(Phải): ${od} | OS(Trái): ${os} | Loạn/Trục: ${cyl} | PD: ${pd}mm`;
    } else {
        document.getElementById('wizardGhiChuDoMat').value = 'Kính không độ';
    }
    return true;
}

// =========================================================
// 6. POPUP THANH TOÁN (COD DUYỆT LUÔN + QR TECHCOMBANK CHỜ DUYỆT)
// =========================================================
let checkoutRawTotal = 0;
let checkoutDiscount = 0;
let checkoutTimerInterval = null;

function openCheckoutModal(rawTotal) {
    checkoutRawTotal = parseFloat(rawTotal);
    checkoutDiscount = 0;

    fetch('xuly/giohang_action.php?action=lock_stock')
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                alert(data.message);
                return;
            }
            updateCheckoutTotals();
            togglePaymentMethodUI();
            startCheckoutCountdown(15 * 60);
            openModal('checkoutModal', false);
        })
        .catch(() => {
            updateCheckoutTotals();
            togglePaymentMethodUI();
            openModal('checkoutModal', false);
        });
}

function cancelCheckoutModal() {
    if (checkoutTimerInterval) clearInterval(checkoutTimerInterval);
    fetch('xuly/giohang_action.php?action=release_stock');
    closeModal('checkoutModal');
}

function startCheckoutCountdown(seconds) {
    if (checkoutTimerInterval) clearInterval(checkoutTimerInterval);
    let remaining = seconds;
    const displayEl = document.getElementById('checkoutTimerDisplay');

    checkoutTimerInterval = setInterval(() => {
        remaining--;
        if (remaining <= 0) {
            clearInterval(checkoutTimerInterval);
            fetch('xuly/giohang_action.php?action=release_stock');
            alert('Đã hết thời gian 15 phút giữ hàng thanh toán. Số lượng hàng đã được hoàn trả về kho!');
            closeModal('checkoutModal');
            return;
        }
        const m = String(Math.floor(remaining / 60)).padStart(2, '0');
        const s = String(remaining % 60).padStart(2, '0');
        if (displayEl) displayEl.innerText = m + ':' + s;
    }, 1000);
}

function selectSavedVoucherInCheckout(code) {
    const input = document.getElementById('checkoutVoucherInput');
    if (input) {
        input.value = code;
        applyVoucherInCheckout();
    }
}

function applyVoucherInCheckout() {
    const code = (document.getElementById('checkoutVoucherInput')?.value || '').trim();
    const msgEl = document.getElementById('checkoutVoucherMsg');

    if (!code) {
        checkoutDiscount = 0;
        if (msgEl) msgEl.innerText = '';
        updateCheckoutTotals();
        return;
    }

    const formData = new FormData();
    formData.append('action', 'check_voucher');
    formData.append('code', code);
    formData.append('tongTamTinh', checkoutRawTotal);

    fetch('xuly/voucher_action.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            checkoutDiscount = parseFloat(data.soTienGiam);
            if (msgEl) {
                msgEl.style.color = '#065f46';
                msgEl.innerText = data.message;
            }
        } else {
            checkoutDiscount = 0;
            if (msgEl) {
                msgEl.style.color = '#991b1b';
                msgEl.innerText = data.message;
            }
        }
        updateCheckoutTotals();
    });
}

function updateCheckoutTotals() {
    const finalTotal = Math.max(0, checkoutRawTotal - checkoutDiscount);
    const formatted = finalTotal.toLocaleString('vi-VN') + ' đ';

    const subEl = document.getElementById('checkoutSubTotal');
    const discEl = document.getElementById('checkoutDiscountTotal');
    const finalEl = document.getElementById('checkoutFinalTotal');
    const qrAmountEl = document.getElementById('qrTransferAmount');

    if (subEl) subEl.innerText = checkoutRawTotal.toLocaleString('vi-VN') + ' đ';
    if (discEl) discEl.innerText = '-' + checkoutDiscount.toLocaleString('vi-VN') + ' đ';
    if (finalEl) finalEl.innerText = formatted;
    if (qrAmountEl) qrAmountEl.innerText = formatted;
}

function togglePaymentMethodUI() {
    const method = document.querySelector('input[name="phuongThuc"]:checked')?.value || 'Chuyển khoản QR';
    const qrBox = document.getElementById('qrTechcombankBox');
    const submitBtn = document.getElementById('btnSubmitCheckout');

    if (method === 'Chuyển khoản QR') {
        if (qrBox) qrBox.style.display = 'block';
        if (submitBtn) submitBtn.innerText = 'Tôi Đã Chuyển Khoản - Chờ Xác Nhận';
    } else {
        if (qrBox) qrBox.style.display = 'none';
        if (submitBtn) submitBtn.innerText = 'Xác Nhận Đặt Hàng COD (Duyệt Ngay)';
    }
}

// =========================================================
// 7. MODALS QUẢN TRỊ & SỬA ĐƠN HÀNG KHÁCH
// =========================================================
function openEditOrderModal(od) {
    document.getElementById('editOdMaDonHang').value = od.maDonHang;
    document.getElementById('editOdTitle').innerText = 'Sửa Thông Tin Đơn Hàng #' + od.maDonHang;
    document.getElementById('editOdTenNN').value = od.tenNN || '';
    document.getElementById('editOdSdt').value = od.soDienThoai || '';
    document.getElementById('editOdDiaChi').value = od.diaChi || '';
    document.getElementById('editOdPhuongThuc').value = od.phuongThuc || 'COD';
    document.getElementById('editOdTrangThai').value = od.trangThaiDon || 'Đang xử lý';
    openModal('editOrderModal');
}

function openBrandModal(mode, data = null) {
    document.getElementById('brandFormAction').value = mode;
    if (mode === 'add') {
        document.getElementById('brandModalTitle').innerText = 'Thêm Thương Hiệu Mới';
        document.getElementById('brandForm').reset();
        document.getElementById('maThuongHieu').value = '';
    } else {
        document.getElementById('brandModalTitle').innerText = 'Cập Nhật Thương Hiệu: ' + data.maThuongHieu;
        document.getElementById('maThuongHieu').value = data.maThuongHieu;
        document.getElementById('tenThuongHieu').value = data.tenThuongHieu;
        document.getElementById('moTaTH').value = data.moTa || '';
    }
    openModal('brandModal');
}

function openProductModal(mode, data = null) {
    document.getElementById('productFormAction').value = mode;
    const existingBox = document.getElementById('existingImagesSection');
    const existingList = document.getElementById('existingImagesList');
    const uploadModeBox = document.getElementById('uploadModeSection');
    const previewContainer = document.getElementById('newImagesPreview');

    if (previewContainer) previewContainer.innerHTML = '';

    if (mode === 'add') {
        document.getElementById('productModalTitle').innerText = 'Thêm Sản Phẩm Gọng Kính Mới';
        document.getElementById('productForm').reset();
        document.getElementById('maSanPham').value = '';
        if (existingBox) existingBox.style.display = 'none';
        if (uploadModeBox) uploadModeBox.style.display = 'none';
    } else {
        document.getElementById('productModalTitle').innerText = 'Sửa Sản Phẩm: ' + data.maSanPham;
        document.getElementById('maSanPham').value = data.maSanPham;
        document.getElementById('tenSanPham').value = data.tenSanPham;
        document.getElementById('maThuongHieuSP').value = data.maThuongHieu;
        document.getElementById('gia').value = data.gia;
        document.getElementById('soLuong').value = data.soLuong;
        document.getElementById('trangThai').value = data.trangThai;
        document.getElementById('mauSac').value = data.mauSac || '';
        document.getElementById('kichThuoc').value = data.kichThuoc || 'M';
        document.getElementById('gioiTinh').value = data.gioiTinh || 'Unisex';
        document.getElementById('vatLieu').value = data.vatLieu || '';
        document.getElementById('chieuRongKhung').value = data.chieuRongKhung || '';
        document.getElementById('chieuRongCau').value = data.chieuRongCau || '';
        document.getElementById('chieuRongThauKinh').value = data.chieuRongThauKinh || '';
        document.getElementById('chieuCaoOngKinh').value = data.chieuCaoOngKinh || '';
        document.getElementById('chieuDaiThaiDuong').value = data.chieuDaiThaiDuong || '';
        document.getElementById('moTaSP').value = data.moTa || '';

        if (uploadModeBox) uploadModeBox.style.display = 'block';

        if (existingBox && existingList) {
            existingList.innerHTML = '';
            if (data.dsAnh && data.dsAnh.length > 0) {
                existingBox.style.display = 'block';
                data.dsAnh.forEach(img => {
                    const isMain = parseInt(img.anhChinh) === 1;
                    const div = document.createElement('div');
                    div.className = 'existing-img-item' + (isMain ? ' is-main' : '');
                    div.innerHTML = `
                        <img src="images/anh_sp/${img.hinhAnh}" alt="">
                        <div style="font-size:10px; font-weight:700; color:${isMain ? '#0f172a' : '#64748b'}; margin-bottom:4px;">
                            ${isMain ? '★ Ảnh chính' : 'Ảnh phụ'}
                        </div>
                        <div class="existing-img-actions">
                            ${!isMain ? `<a href="xuly/sanpham_action.php?action=set_main_img&maAnh=${img.maAnh}&maSP=${data.maSanPham}" style="background:#e2e8f0; color:#0f172a;">Đặt chính</a>` : ''}
                            <a href="xuly/sanpham_action.php?action=delete_img&maAnh=${img.maAnh}&maSP=${data.maSanPham}" style="background:#fee2e2; color:#991b1b;" onclick="return confirm('Xóa ảnh này?')">Xóa</a>
                        </div>
                    `;
                    existingList.appendChild(div);
                });
            } else {
                existingBox.style.display = 'none';
            }
        }
    }
    openModal('productModal');
}

function previewMultipleImages(input) {
    const previewContainer = document.getElementById('newImagesPreview');
    if (!previewContainer) return;
    previewContainer.innerHTML = '';

    if (input.files && input.files.length > 0) {
        Array.from(input.files).forEach((file, idx) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const div = document.createElement('div');
                div.className = 'existing-img-item' + (idx === 0 ? ' is-main' : '');
                div.innerHTML = `<img src="${e.target.result}" alt=""><span style="font-size:10px; color:#475569;">Ảnh mới #${idx + 1}</span>`;
                previewContainer.appendChild(div);
            };
            reader.readAsDataURL(file);
        });
    }
}

function openStaffModal(mode, data = null) {
    document.getElementById('staffFormAction').value = mode;
    if (mode === 'add') {
        document.getElementById('staffModalTitle').innerText = 'Thêm Nhân Viên Mới';
        document.getElementById('staffForm').reset();
        document.getElementById('maNguoiDungStaff').value = '';
        document.getElementById('matKhauStaff').setAttribute('required', 'required');
    } else {
        document.getElementById('staffModalTitle').innerText = 'Cập Nhật Nhân Viên: ' + data.maNguoiDung;
        document.getElementById('maNguoiDungStaff').value = data.maNguoiDung;
        document.getElementById('hoTenStaff').value = data.hoTen;
        document.getElementById('emailStaff').value = data.email;
        document.getElementById('matKhauStaff').value = '';
        document.getElementById('matKhauStaff').removeAttribute('required');
    }
    openModal('staffModal');
}

function openCustomerEditModal(kh) {
    document.getElementById('editMaKhachHang').value = kh.maKhachHang;
    document.getElementById('editMaNguoiDungKH').value = kh.maNguoiDung;
    document.getElementById('editHoTenKH').value = kh.hoTen;
    document.getElementById('editEmailKH').value = kh.email;
    document.getElementById('editSdtKH').value = kh.soDienThoai;
    document.getElementById('editGioiTinhKH').value = kh.gioiTinh || 'Nam';
    document.getElementById('editNgaySinhKH').value = kh.ngaySinh ? kh.ngaySinh.slice(0, 10) : '';
    document.getElementById('editDiaChiKH').value = kh.diaChi || '';
    document.getElementById('editMatKhauKH').value = '';
    openModal('customerEditModal');
}

function openVoucherModal(mode, data = null) {
    document.getElementById('voucherFormAction').value = mode;
    if (mode === 'add') {
        document.getElementById('voucherModalTitle').innerText = 'Phát Hành Mã Voucher Mới';
        document.getElementById('voucherForm').reset();
        document.getElementById('maVoucher').value = '';
    } else {
        document.getElementById('voucherModalTitle').innerText = 'Cập Nhật Voucher: ' + data.code;
        document.getElementById('maVoucher').value = data.maVoucher;
        document.getElementById('codeVoucher').value = data.code;
        document.getElementById('loaiGiam').value = data.loaiGiam;
        document.getElementById('giaTriGiam').value = data.giaTriGiam;
        document.getElementById('donHangToiThieu').value = data.donHangToiThieu;
        document.getElementById('gioiHanDung').value = data.gioiHanDung !== null ? data.gioiHanDung : '';
        if (data.ngayHetHan) {
            document.getElementById('ngayHetHan').value = data.ngayHetHan.replace(' ', 'T').slice(0, 16);
        }
    }
    openModal('voucherModal');
}

// =========================================================
// 8. XEM CHI TIẾT ĐƠN HÀNG & KHIẾU NẠI
// =========================================================
function openOrderDetailModal(od) {
    document.getElementById('odModalTitle').innerText = 'Chi Tiết Đơn Hàng #' + od.maDonHang;
    document.getElementById('odCustomerInfo').innerHTML = `
        <p><strong>Người nhận:</strong> ${od.tenNN} — <strong>SĐT:</strong> ${od.soDienThoai}</p>
        <p><strong>Địa chỉ giao:</strong> ${od.diaChi}</p>
        <p><strong>Ngày đặt:</strong> ${od.ngayDat} | <strong>Thanh toán:</strong> ${od.phuongThuc || 'COD'}</p>
        <p><strong>Trạng thái hiện tại:</strong> <span class="status-badge status-info">${od.trangThaiDon || 'Đang xử lý'}</span></p>
    `;

    const tbody = document.getElementById('odItemsBody');
    tbody.innerHTML = '';
    if (od.chiTiet && od.chiTiet.length > 0) {
        od.chiTiet.forEach(item => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><strong>${item.tenSanPham}</strong> (${item.maSanPham})</td>
                <td>${item.loaiTrong ? (item.loaiTrong + ' - CS ' + item.chietSuat + ' (' + item.tinhNang + ')') : 'Chỉ gọng kính'}</td>
                <td><strong>${parseFloat(item.donGia).toLocaleString('vi-VN')} đ</strong></td>
            `;
            tbody.appendChild(tr);
        });
    }
    document.getElementById('odTotalAmount').innerText = parseFloat(od.tongTien).toLocaleString('vi-VN') + ' đ';
    openModal('orderDetailModal', false);
}

function openComplaintModal(maDonHang) {
    document.getElementById('complaintMaDonHang').value = maDonHang;
    document.getElementById('complaintOrderBadge').innerText = 'Đơn hàng liên quan: #' + maDonHang;
    openModal('complaintModal');
}

function openReplyComplaintModal(kn) {
    document.getElementById('replyMaKhieuNai').value = kn.maKhieuNai;
    document.getElementById('replyComplaintTitle').innerText = 'Phản Hồi Khiếu Nại #' + kn.maKhieuNai;
    document.getElementById('replyComplaintInfo').innerHTML = `
        <p><strong>Khách hàng:</strong> ${kn.tenKhachHang} (${kn.soDienThoai}) | <strong>Đơn:</strong> ${kn.maDonHang || 'Không gắn đơn'}</p>
        <p><strong>Loại:</strong> ${kn.loaiKhieuNai} — <strong>Tiêu đề:</strong> ${kn.tieuDe}</p>
        <p style="background:#f8fafc; padding:10px; border-radius:6px; margin-top:6px;">${kn.noiDung}</p>
        ${kn.hinhAnh ? `<div style="margin-top:8px;"><img src="images/anh_khieunai/${kn.hinhAnh}" style="max-height:140px; border-radius:8px; border:1px solid #cbd5e1;"></div>` : ''}
    `;
    document.getElementById('replyTrangThai').value = kn.trangThai;
    document.getElementById('replyPhanHoi').value = kn.phanHoi || '';
    openModal('replyComplaintModal');
}

// =========================================================
// 9. TẠO ĐƠN TẠI CỬA HÀNG (POS) + HIỂN THỊ & TÍNH VOUCHER TRỰC TIẾP
// =========================================================
function togglePosCustomerType() {
    const type = document.querySelector('input[name="loaiKhach"]:checked')?.value || 'member';
    document.getElementById('posMemberBox').style.display = type === 'member' ? 'block' : 'none';
    document.getElementById('posWalkinBox').style.display = type === 'walkin' ? 'grid' : 'none';
}

function addPosItemRow() {
    const container = document.getElementById('posItemsContainer');
    const firstRow = container.querySelector('.pos-item-row');
    if (!firstRow) return;
    const clone = firstRow.cloneNode(true);
    clone.querySelectorAll('select').forEach(s => s.selectedIndex = 0);
    clone.querySelector('input[type="number"]').value = 1;
    container.appendChild(clone);
    recalcPosTotal();
}

function removePosItemRow(btn) {
    const container = document.getElementById('posItemsContainer');
    if (container.querySelectorAll('.pos-item-row').length > 1) {
        btn.closest('.pos-item-row').remove();
        recalcPosTotal();
    }
}

function recalcPosTotal() {
    let rawTotal = 0;
    document.querySelectorAll('#posItemsContainer .pos-item-row').forEach(row => {
        const spSel = row.querySelector('select[name="maSanPham[]"]');
        const tkSel = row.querySelector('select[name="maTrongKinh[]"]');
        const qtyInput = row.querySelector('input[name="soLuong[]"]');

        const spPrice = parseFloat(spSel.options[spSel.selectedIndex]?.dataset?.price || 0);
        const tkPrice = parseFloat(tkSel.options[tkSel.selectedIndex]?.dataset?.price || 0);
        const qty = Math.max(1, parseInt(qtyInput?.value || 1));

        rawTotal += (spPrice + tkPrice) * qty;
    });

    let discount = 0;
    const vcSel = document.getElementById('posVoucherSelect');
    const vcNoteEl = document.getElementById('posVoucherNote');
    if (vcSel && vcSel.value !== '') {
        const opt = vcSel.options[vcSel.selectedIndex];
        const minOrder = parseFloat(opt.dataset.min || 0);
        const type = opt.dataset.type || '';
        const val = parseFloat(opt.dataset.val || 0);

        if (rawTotal >= minOrder) {
            if (type === 'Phần trăm' || type === '%') {
                discount = Math.round(rawTotal * (val / 100));
            } else {
                discount = val;
            }
            if (discount > rawTotal) discount = rawTotal;
            if (vcNoteEl) {
                vcNoteEl.style.color = '#065f46';
                vcNoteEl.innerText = '✓ Hợp lệ! Giảm: -' + discount.toLocaleString('vi-VN') + ' đ';
            }
        } else {
            if (vcNoteEl) {
                vcNoteEl.style.color = '#991b1b';
                vcNoteEl.innerText = '⚠ Đơn hàng cần tối thiểu ' + minOrder.toLocaleString('vi-VN') + ' đ để áp dụng mã này.';
            }
        }
    } else if (vcNoteEl) {
        vcNoteEl.innerText = '';
    }

    const finalTotal = Math.max(0, rawTotal - discount);
    const el = document.getElementById('posEstimatedTotal');
    if (el) el.innerText = finalTotal.toLocaleString('vi-VN') + ' đ';
}
