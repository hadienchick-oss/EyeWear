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

    const bestsellerSec = document.getElementById('bestsellerSection');
    if (bestsellerSec) {
        bestsellerSec.style.display = 'block';
    }

    const emptyNotice = document.getElementById('shopEmptySearch');
    if (emptyNotice) {
        emptyNotice.style.display = visibleCount === 0 ? 'block' : 'none';
    }
}

function filterByBrandFromDetail(brandName) {
    closeModal('productDetailModal');
    switchTab('shop-view');
    const brandSelect = document.getElementById('shopBrandFilter');
    if (brandSelect) {
        brandSelect.value = (brandName || '').toLowerCase().trim();
    }
    const searchInput = document.getElementById('shopSearchInput');
    if (searchInput) {
        searchInput.value = '';
    }
    selectedColorChip = '';
    document.querySelectorAll('.color-chip').forEach((c, idx) => c.classList.toggle('active', idx === 0));
    filterShopProducts();

    const gridTitle = document.getElementById('allProductsHeading');
    if (gridTitle) {
        gridTitle.scrollIntoView({ behavior: 'smooth', block: 'start' });
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

    const brandName = sp.tenThuongHieu || 'Việt MAC';
    document.getElementById('detailBrand').innerText = brandName;
    document.getElementById('detailName').innerText = sp.tenSanPham + ' (' + sp.maSanPham + ')';

    const brandIconBtn = document.getElementById('detailBrandIconBtn');
    const brandLogoEl = document.getElementById('detailBrandLogo');
    const brandFallbackEl = document.getElementById('detailBrandFallback');
    const brandDescEl = document.getElementById('detailBrandDesc');

    if (brandDescEl) {
        brandDescEl.innerText = 'Thương hiệu ' + brandName + ': ' + (sp.moTaThuongHieu || 'Mắt kính chính hãng phân phối tại Việt MAC.');
    }
    if (brandLogoEl && brandFallbackEl) {
        if (sp.logoThuongHieu) {
            brandLogoEl.src = 'images/logo_thuonghieu/' + sp.logoThuongHieu;
            brandLogoEl.style.display = 'block';
            brandFallbackEl.style.display = 'none';
        } else {
            brandLogoEl.style.display = 'none';
            brandFallbackEl.style.display = 'inline-block';
            brandFallbackEl.innerText = brandName.slice(0, 3);
        }
    }
    // Chỉ khi ấn vào icon Logo thương hiệu mới lọc các sản phẩm của hãng đó
    if (brandIconBtn) {
        brandIconBtn.onclick = function(e) {
            e.stopPropagation();
            filterByBrandFromDetail(brandName);
        };
    }
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
            const isOwner = (window.CURRENT_MA_KH && dg.maKhachHang === window.CURRENT_MA_KH);
            const div = document.createElement('div');
            div.style.cssText = 'padding:10px; border-bottom:1px solid #f1f5f9; font-size:13px;';
            const ownerControls = isOwner ? `
                <div style="display:flex; gap:6px;">
                    <button type="button" class="btn btn-outline btn-sm" onclick="toggleEditReviewForm('${dg.maDanhGia}')">Sửa</button>
                    <a href="xuly/donhang_action.php?action=delete_review&id=${dg.maDanhGia}" class="btn btn-danger btn-sm" onclick="return confirm('Xóa đánh giá này?')">Xóa</a>
                </div>
            ` : '';

            const editFormHtml = isOwner ? `
                <form id="edit-rev-form-${dg.maDanhGia}" action="xuly/donhang_action.php" method="POST" style="display:none; margin-top:8px; gap:6px; flex-wrap:wrap;">
                    <input type="hidden" name="action" value="edit_review">
                    <input type="hidden" name="maDanhGia" value="${dg.maDanhGia}">
                    <select name="sao" style="padding:5px 8px; border:1px solid #cbd5e1; border-radius:6px;">
                        <option value="5" ${parseInt(dg.sao)===5?'selected':''}>5 ★</option>
                        <option value="4" ${parseInt(dg.sao)===4?'selected':''}>4 ★</option>
                        <option value="3" ${parseInt(dg.sao)===3?'selected':''}>3 ★</option>
                        <option value="2" ${parseInt(dg.sao)===2?'selected':''}>2 ★</option>
                        <option value="1" ${parseInt(dg.sao)===1?'selected':''}>1 ★</option>
                    </select>
                    <input type="text" name="binhLuan" value="${(dg.binhLuan || '').replace(/"/g, '&quot;')}" style="flex:1; min-width:160px; padding:5px 8px; border:1px solid #cbd5e1; border-radius:6px;" required>
                    <button type="submit" class="btn btn-dark btn-sm">Lưu</button>
                </form>
            ` : '';

            div.innerHTML = `
                <div style="display:flex; justify-content:space-between; align-items:center; gap:8px;">
                    <div><strong>${dg.hoTen}</strong> <span style="color:#f59e0b;">${'★'.repeat(dg.sao)}</span></div>
                    ${ownerControls}
                </div>
                <p style="color:#475569; margin-top:4px;">${dg.binhLuan || ''}</p>
                ${editFormHtml}
            `;
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
function toggleEditReviewForm(maDanhGia) {
    const f = document.getElementById('edit-rev-form-' + maDanhGia);
    if (f) {
        f.style.display = (f.style.display === 'none' || f.style.display === '') ? 'flex' : 'none';
    }
}

function openOrderDetailModal(od) {
    document.getElementById('odModalTitle').innerText = 'Chi Tiết Đơn Hàng & Phiếu Kỹ Thuật #' + od.maDonHang;
    document.getElementById('odCustomerInfo').innerHTML = `
        <p><strong>Khách nhận hàng:</strong> ${od.tenNN} — <strong>SĐT:</strong> ${od.soDienThoai}</p>
        <p><strong>Địa chỉ giao:</strong> ${od.diaChi}</p>
        <p><strong>Ngày đặt:</strong> ${od.ngayDat} | <strong>Phương thức TT:</strong> ${od.phuongThuc || 'COD'}</p>
        <p><strong>Trạng thái đơn:</strong> <span class="status-badge status-info">${od.trangThaiDon || 'Đang xử lý'}</span></p>
    `;

    const tbody = document.getElementById('odItemsBody');
    tbody.innerHTML = '';
    if (od.chiTiet && od.chiTiet.length > 0) {
        od.chiTiet.forEach(item => {
            const lensInfo = item.loaiTrong
                ? `<strong>${item.loaiTrong}</strong> — Chiết suất <strong>${item.chietSuat}</strong> (${item.tinhNang || ''})`
                : 'Chỉ lấy gọng kính (Không tròng)';

            let rxHtml = '';
            if (item.thongSoDoMat) {
                rxHtml = `
                    <div class="rx-detail-grid">
                        <div style="grid-column: span 2; font-weight:800; color:#0f172a;">👓 THÔNG SỐ ĐỘ MẮT (SPH / CYL / AXIS / PD):</div>
                        <div style="grid-column: span 2;">${item.thongSoDoMat}</div>
                    </div>
                `;
            } else if (item.loaiTrong) {
                rxHtml = `<div style="font-size:12px; color:#64748b; margin-top:4px;">Độ mắt: Kính không độ (SPH: 0.00 | CYL: 0.00 | PD: Tiêu chuẩn)</div>`;
            }

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><strong>${item.tenSanPham}</strong><br><small style="color:#64748b;">Mã SP: ${item.maSanPham}</small></td>
                <td>
                    <div>${lensInfo}</div>
                    ${rxHtml}
                </td>
                <td><strong>${parseFloat(item.donGia).toLocaleString('vi-VN')} đ</strong></td>
            `;
            tbody.appendChild(tr);
        });
    }
    document.getElementById('odTotalAmount').innerText = parseFloat(od.tongTien).toLocaleString('vi-VN') + ' đ';
    openModal('orderDetailModal', false);
}

// =========================================================
// 10. CHỈNH SỬA TRÒNG KÍNH / ĐỘ MẮT CỦA SẢN PHẨM TRONG GIỎ HÀNG
// =========================================================
let cartEditFramePrice = 0;

function openCartItemEditModal(item) {
    cartEditFramePrice = parseFloat(item.giaGong || 0);
    document.getElementById('editCartOldKey').value = item.cartKey;
    document.getElementById('editCartTitle').innerText = 'Tùy Chỉnh Gọng & Tròng: ' + item.tenSanPham;
    document.getElementById('editCartFramePriceText').innerText = 'Giá gọng kính: ' + cartEditFramePrice.toLocaleString('vi-VN') + ' đ';

    const rxNote = item.ghiChuDoMat || '';
    let mode = 'frame_only';
    if (item.maTrongKinh) {
        mode = (rxNote && rxNote !== 'Kính không độ') ? 'with_rx' : 'no_rx';
    }

    const radio = document.querySelector(`input[name="loaiNhuCau"][value="${mode}"]`);
    if (radio) radio.checked = true;

    const lensSelect = document.getElementById('editCartLensSelect');
    if (lensSelect && item.maTrongKinh) {
        lensSelect.value = item.maTrongKinh;
    }

    // Parse existing SPH / CYL / PD if present
    document.getElementById('editCartSphOD').value = '';
    document.getElementById('editCartSphOS').value = '';
    document.getElementById('editCartCylAxis').value = '';
    document.getElementById('editCartPD').value = '';

    if (mode === 'with_rx' && rxNote.includes('OD(Phải):')) {
        const mOD = rxNote.match(/OD\(Phải\):\s*([^|]+)/);
        const mOS = rxNote.match(/OS\(Trái\):\s*([^|]+)/);
        const mCyl = rxNote.match(/Loạn\/Trục:\s*([^|]+)/);
        const mPD = rxNote.match(/PD:\s*(\d+)/);
        if (mOD) document.getElementById('editCartSphOD').value = mOD[1].trim();
        if (mOS) document.getElementById('editCartSphOS').value = mOS[1].trim();
        if (mCyl) document.getElementById('editCartCylAxis').value = mCyl[1].trim();
        if (mPD) document.getElementById('editCartPD').value = mPD[1].trim();
    }

    toggleCartEditMode();
    openModal('editCartItemModal', false);
}

function toggleCartEditMode() {
    const mode = document.querySelector('input[name="loaiNhuCau"]:checked')?.value || 'frame_only';
    const rxBox = document.getElementById('editCartRxBox');
    const lensBox = document.getElementById('editCartLensBox');

    if (mode === 'frame_only') {
        if (rxBox) rxBox.style.display = 'none';
        if (lensBox) lensBox.style.display = 'none';
    } else if (mode === 'no_rx') {
        if (rxBox) rxBox.style.display = 'none';
        if (lensBox) lensBox.style.display = 'block';
    } else {
        if (rxBox) rxBox.style.display = 'block';
        if (lensBox) lensBox.style.display = 'block';
    }
    recalcCartEditPrice();
}

function recalcCartEditPrice() {
    const mode = document.querySelector('input[name="loaiNhuCau"]:checked')?.value || 'frame_only';
    let lensPrice = 0;
    if (mode !== 'frame_only') {
        const lensSelect = document.getElementById('editCartLensSelect');
        if (lensSelect && lensSelect.selectedIndex >= 0) {
            lensPrice = parseFloat(lensSelect.options[lensSelect.selectedIndex]?.dataset?.price || 0);
        }
    }
    const total = cartEditFramePrice + lensPrice;
    const totalEl = document.getElementById('editCartNewUnitTotal');
    if (totalEl) totalEl.innerText = total.toLocaleString('vi-VN') + ' đ';
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


// =========================================================
// 11. XEM CHI TIẾT KHÁCH HÀNG & NHÂN VIÊN TRONG QUẢN TRỊ
// =========================================================
function openCustomerDetailModal(kh) {
    document.getElementById('custDetailTitle').innerText = 'Hồ Sơ Khách Hàng: ' + kh.hoTen + ' (' + kh.maKhachHang + ')';
    document.getElementById('custDetailInfo').innerHTML = `
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:8px; background:#f8fafc; padding:12px; border-radius:10px; border:1px solid #e2e8f0;">
            <p><strong>Mã khách hàng:</strong> ${kh.maKhachHang}</p>
            <p><strong>Số điện thoại:</strong> ${kh.soDienThoai}</p>
            <p><strong>Họ và tên:</strong> ${kh.hoTen}</p>
            <p><strong>Email:</strong> ${kh.email}</p>
            <p><strong>Giới tính:</strong> ${kh.gioiTinh || 'Chưa cập nhật'}</p>
            <p><strong>Ngày sinh:</strong> ${kh.ngaySinh ? kh.ngaySinh.slice(0, 10) : 'Chưa cập nhật'}</p>
            <p style="grid-column: span 2;"><strong>Địa chỉ mặc định:</strong> ${kh.diaChi || 'Chưa cập nhật'}</p>
            <p><strong>Tổng số đơn đã mua:</strong> <span class="status-badge status-processing">${kh.soDonHang || 0} đơn</span></p>
            <p><strong>Tổng chi tiêu:</strong> <strong style="color:#15803d;">${parseFloat(kh.tongChiTieu || 0).toLocaleString('vi-VN')} đ</strong></p>
        </div>
    `;

    const ordersList = (window.ALL_ADMIN_ORDERS || []).filter(od => 
        od.maKhachHang === kh.maKhachHang || (!od.maKhachHang && od.soDienThoai === kh.soDienThoai)
    );
    const tbody = document.getElementById('custDetailOrdersBody');
    tbody.innerHTML = '';

    if (ordersList.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; color:#64748b;">Khách hàng này chưa có đơn hàng nào.</td></tr>';
    } else {
        ordersList.forEach(od => {
            const st = od.trangThaiDon || 'Đang xử lý';
            const badgeClass = (st === 'Đã hủy') ? 'status-stopped' : (st === 'Đã giao' ? 'status-active' : 'status-processing');
            const itemsSummary = (od.chiTiet || []).map(c => c.tenSanPham).join(', ');
            const tr = document.createElement('tr');
            tr.className = 'order-row-clickable';
            tr.title = 'Nhấn để xem chi tiết đơn hàng & độ mắt cắt kính';
            tr.onclick = function() {
                openOrderDetailModal(od);
            };
            tr.innerHTML = `
                <td><strong>${od.maDonHang}</strong></td>
                <td>${od.ngayDat}</td>
                <td>${itemsSummary || '-'}</td>
                <td><strong>${parseFloat(od.tongTien).toLocaleString('vi-VN')} đ</strong></td>
                <td><span class="status-badge ${badgeClass}">${st}</span></td>
            `;
            tbody.appendChild(tr);
        });
    }

    openModal('customerDetailModal', false);
}

function openStaffDetailModal(nv) {
    document.getElementById('staffDetailTitle').innerText = 'Chi Tiết Hoạt Động Nhân Viên: ' + nv.hoTen + ' (' + nv.maNguoiDung + ')';
    const donTaoList = nv.donDaTao || [];
    const donXuLyList = nv.donDaXuLy || [];
    const knList = nv.khieuNaiXuLy || [];

    document.getElementById('staffDetailInfo').innerHTML = `
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:8px; background:#f8fafc; padding:12px; border-radius:10px; border:1px solid #e2e8f0;">
            <p><strong>Mã người dùng:</strong> ${nv.maNguoiDung}</p>
            <p><strong>Vai trò:</strong> <span class="role-tag">Nhân Viên (VT02)</span></p>
            <p><strong>Họ và tên:</strong> ${nv.hoTen}</p>
            <p><strong>Email:</strong> ${nv.email}</p>
            <p><strong>Đơn đã tạo (POS):</strong> <strong>${donTaoList.length} đơn</strong></p>
            <p><strong>Đơn đã xử lý / Khiếu nại:</strong> <strong>${donXuLyList.length} đơn</strong> • <strong>${knList.length} khiếu nại</strong></p>
        </div>
    `;

    // 1. Đơn hàng tại quầy đã tạo & bán cho khách hàng nào
    const ordersBody = document.getElementById('staffOrdersBody');
    ordersBody.innerHTML = '';
    if (donTaoList.length === 0) {
        ordersBody.innerHTML = '<tr><td colspan="4" style="text-align:center; color:#64748b;">Nhân viên này chưa tạo đơn tại quầy nào.</td></tr>';
    } else {
        donTaoList.forEach(entry => {
            const matchedOrder = (window.ALL_ADMIN_ORDERS || []).find(o => o.maDonHang === entry.maDoiTuong);
            const tr = document.createElement('tr');
            if (matchedOrder) {
                tr.className = 'order-row-clickable';
                tr.title = 'Nhấn để xem chi tiết đơn hàng';
                tr.onclick = function() {
                    openOrderDetailModal(matchedOrder);
                };
            }
            tr.innerHTML = `
                <td><strong>${entry.maDoiTuong}</strong></td>
                <td>${matchedOrder ? (matchedOrder.tenNN + ' — SĐT: ' + matchedOrder.soDienThoai) : entry.moTa}</td>
                <td>${matchedOrder ? ('<strong>' + parseFloat(matchedOrder.tongTien).toLocaleString('vi-VN') + ' đ</strong>') : '-'}</td>
                <td>${entry.thoiGian}</td>
            `;
            ordersBody.appendChild(tr);
        });
    }

    // 2. Đơn hàng đã xử lý (xác nhận QR, mài lắp, giao hàng, duyệt/từ chối hủy)
    const processedBody = document.getElementById('staffProcessedOrdersBody');
    if (processedBody) {
        processedBody.innerHTML = '';
        if (donXuLyList.length === 0) {
            processedBody.innerHTML = '<tr><td colspan="4" style="text-align:center; color:#64748b;">Nhân viên này chưa xử lý đơn hàng nào.</td></tr>';
        } else {
            donXuLyList.forEach(entry => {
                const matchedOrder = (window.ALL_ADMIN_ORDERS || []).find(o => o.maDonHang === entry.maDoiTuong);
                const tr = document.createElement('tr');
                if (matchedOrder) {
                    tr.className = 'order-row-clickable';
                    tr.title = 'Nhấn để xem chi tiết đơn hàng';
                    tr.onclick = function() {
                        openOrderDetailModal(matchedOrder);
                    };
                }
                tr.innerHTML = `
                    <td><strong>${entry.maDoiTuong}</strong></td>
                    <td>${matchedOrder ? (matchedOrder.tenNN + ' (' + matchedOrder.soDienThoai + ')') : '-'}</td>
                    <td>${entry.moTa}</td>
                    <td>${entry.thoiGian}</td>
                `;
                processedBody.appendChild(tr);
            });
        }
    }

    // 3. Khiếu nại đã xử lý phản hồi
    const knBody = document.getElementById('staffComplaintsBody');
    knBody.innerHTML = '';
    if (knList.length === 0) {
        knBody.innerHTML = '<tr><td colspan="3" style="text-align:center; color:#64748b;">Nhân viên này chưa xử lý phản hồi khiếu nại nào.</td></tr>';
    } else {
        knList.forEach(entry => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><strong>${entry.maDoiTuong}</strong></td>
                <td>${entry.moTa}</td>
                <td>${entry.thoiGian}</td>
            `;
            knBody.appendChild(tr);
        });
    }

    openModal('staffDetailModal', false);
}

function togglePosRxFields() {
    const mode = document.querySelector('input[name="posCoDoMat"]:checked')?.value || 'no_rx';
    const rxBox = document.getElementById('posRxFieldsBox');
    if (rxBox) {
        rxBox.style.display = (mode === 'with_rx') ? 'grid' : 'none';
    }
}
