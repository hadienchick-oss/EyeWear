// 1. Chuyển đổi các tab trên thanh điều hướng
function switchTab(tabId, el) {
    document.querySelectorAll('.section-view').forEach(sec => sec.classList.remove('active'));
    document.querySelectorAll('.nav-links a').forEach(link => link.classList.remove('active'));

    const target = document.getElementById(tabId);
    if (target) target.classList.add('active');
    if (el) el.classList.add('active');

    const navLinks = document.querySelector('.nav-links');
    if (navLinks) navLinks.classList.remove('open');
}

function toggleMobileMenu() {
    const navLinks = document.querySelector('.nav-links');
    if (navLinks) {
        navLinks.classList.toggle('open');
    }
}

// 2. Đóng / Mở Modal bất kỳ & Tự động focus vào ô input đầu tiên để bấm Tab mượt mà
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    modal.classList.add('open');
    setTimeout(() => {
        const firstInput = modal.querySelector('input:not([type="hidden"]), select, textarea');
        if (firstInput) firstInput.focus();
    }, 60);
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.remove('open');
}

function switchAuthModal(fromModalId, toModalId) {
    closeModal(fromModalId);
    openModal(toModalId);
}

// Hỗ trợ bấm phím Tab trong ô input có data-suggest:
// Nếu người dùng gõ vài ký tự đầu (VD: "ad", "nh", "ng") rồi bấm phím Tab, tự động điền nốt gợi ý email và nhảy xuống ô Mật khẩu!
document.addEventListener('DOMContentLoaded', () => {
    const loginEmailInput = document.getElementById('loginEmail');
    const suggestHint = document.getElementById('emailSuggestHint');
    const emailSuggestions = [
        'nguyenvanan@gmail.com',
        'bichngoc@gmail.com',
        'nhanvien@kinhmat.vn',
        'admin@kinhmat.vn'
    ];

    if (loginEmailInput) {
        loginEmailInput.addEventListener('input', function() {
            const val = this.value.trim().toLowerCase();
            if (val.length >= 2) {
                const match = emailSuggestions.find(item => item.startsWith(val) && item !== val);
                if (match && suggestHint) {
                    suggestHint.style.display = 'block';
                    suggestHint.innerHTML = 'Gợi ý: <strong>' + match + '</strong> (Nhấn phím <b>Tab</b> để điền nhanh)';
                    this.dataset.matchedEmail = match;
                    return;
                }
            }
            if (suggestHint) suggestHint.style.display = 'none';
            this.dataset.matchedEmail = '';
        });

        loginEmailInput.addEventListener('keydown', function(e) {
            if (e.key === 'Tab' && !e.shiftKey && this.dataset.matchedEmail) {
                // Tự động lấy gợi ý điền vào ô Email và vẫn cho phép con trỏ nhảy sang ô Mật khẩu
                this.value = this.dataset.matchedEmail;
                this.dataset.matchedEmail = '';
                if (suggestHint) suggestHint.style.display = 'none';
            }
        });
    }
});

// 3. Mở Modal Thêm / Sửa Thương Hiệu
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

// 4. Mở Modal Thêm / Sửa Sản Phẩm (Hỗ trợ hiển thị & quản lý NHIỀU ẢNH)
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

        // Hiển thị danh sách các ảnh đang có của sản phẩm
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
                            ${!isMain ? `<a href="xuly/sanpham_action.php?action=set_main_img&maAnh=${img.maAnh}&maSP=${data.maSanPham}" style="background:#e2e8f0; color:#0f172a;" title="Đặt làm ảnh chính">Đặt chính</a>` : ''}
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

// Xem trước nhiều ảnh ngay khi chọn file từ máy tính
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
                div.innerHTML = `
                    <img src="${e.target.result}" alt="">
                    <span style="font-size:10px; color:#475569;">Ảnh mới #${idx + 1}</span>
                `;
                previewContainer.appendChild(div);
            };
            reader.readAsDataURL(file);
        });
    }
}

// Đổi ảnh hiển thị trên thẻ sản phẩm khi khách bấm vào ảnh thu nhỏ (thumbnail)
function changeProductCardImage(maSP, imgSrc, thumbEl) {
    const mainImg = document.getElementById('main-img-' + maSP);
    if (mainImg) {
        mainImg.src = imgSrc;
    }
    const parentThumbs = thumbEl.parentElement.querySelectorAll('.product-thumb-item');
    parentThumbs.forEach(t => t.classList.remove('active-thumb'));
    thumbEl.classList.add('active-thumb');
}

// 5. Mở Modal Thêm / Sửa Nhân Viên
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

// 6. Mở Modal Thêm / Sửa Voucher Khuyến Mãi (Bảng VOUCHER)
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

// 7. Xử lý POPUP CHỌN TRÒNG KÍNH TỪNG BƯỚC + ÁP DỤNG VOUCHER TRỰC TIẾP
let currentFramePrice = 0;
let currentLensPrice = 350000;
let currentDiscountAmount = 0;
let appliedVoucherCode = '';
let currentStep = 1;

function openLensWizard(maSP, tenSP, giaGong) {
    currentFramePrice = parseFloat(giaGong);
    currentLensPrice = 350000;
    currentDiscountAmount = 0;
    appliedVoucherCode = '';

    document.getElementById('wizardProductName').innerText = tenSP + ' (' + maSP + ')';
    const vcInput = document.getElementById('wizardVoucherInput');
    if (vcInput) vcInput.value = '';
    const vcMsg = document.getElementById('wizardVoucherMsg');
    if (vcMsg) vcMsg.innerText = '';

    document.querySelectorAll('#step3 .option-box').forEach((box, idx) => {
        box.classList.toggle('selected', idx === 0);
    });

    updateWizardTotal();
    goToStep(1);
    openModal('lensWizardModal');
}

function goToStep(step) {
    currentStep = step;
    document.querySelectorAll('.lens-step').forEach((el, idx) => {
        el.classList.toggle('active', idx + 1 === step);
    });
    document.querySelectorAll('.step-dot').forEach((el, idx) => {
        el.classList.toggle('active', idx + 1 === step);
    });
}

function selectLensOption(price, el) {
    currentLensPrice = parseFloat(price);
    document.querySelectorAll('#step3 .option-box').forEach(box => box.classList.remove('selected'));
    el.classList.add('selected');
    if (appliedVoucherCode !== '') {
        applyVoucherInWizard();
    } else {
        updateWizardTotal();
    }
}

function updateWizardTotal() {
    const rawTotal = currentFramePrice + currentLensPrice;
    const finalTotal = Math.max(0, rawTotal - currentDiscountAmount);
    document.getElementById('wizardTotalPrice').innerText = finalTotal.toLocaleString('vi-VN') + ' đ';

    const discountRow = document.getElementById('wizardDiscountInfo');
    if (discountRow) {
        if (currentDiscountAmount > 0) {
            discountRow.style.display = 'block';
            discountRow.innerText = 'Đã giảm: -' + currentDiscountAmount.toLocaleString('vi-VN') + ' đ (Mã: ' + appliedVoucherCode + ')';
        } else {
            discountRow.style.display = 'none';
        }
    }
}

function applyVoucherInWizard() {
    const codeInput = document.getElementById('wizardVoucherInput');
    const msgEl = document.getElementById('wizardVoucherMsg');
    const code = codeInput.value.trim();
    const rawTotal = currentFramePrice + currentLensPrice;

    if (!code) {
        msgEl.style.color = '#991b1b';
        msgEl.innerText = 'Vui lòng nhập mã khuyến mãi Voucher!';
        return;
    }

    const formData = new FormData();
    formData.append('action', 'check_voucher');
    formData.append('code', code);
    formData.append('tongTamTinh', rawTotal);

    fetch('xuly/voucher_action.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            currentDiscountAmount = parseFloat(data.soTienGiam);
            appliedVoucherCode = data.code;
            msgEl.style.color = '#065f46';
            msgEl.innerText = data.message;
        } else {
            currentDiscountAmount = 0;
            appliedVoucherCode = '';
            msgEl.style.color = '#991b1b';
            msgEl.innerText = data.message;
        }
        updateWizardTotal();
    })
    .catch(() => {
        msgEl.style.color = '#991b1b';
        msgEl.innerText = 'Không thể kiểm tra mã Voucher lúc này.';
    });
}

function removeVoucherInWizard() {
    currentDiscountAmount = 0;
    appliedVoucherCode = '';
    document.getElementById('wizardVoucherInput').value = '';
    const msgEl = document.getElementById('wizardVoucherMsg');
    msgEl.style.color = '#4b5563';
    msgEl.innerText = 'Đã gỡ bỏ mã giảm giá.';
    updateWizardTotal();
}

function finishLensWizard() {
    let summary = 'Đã cấu hình gọng + tròng kính thành công!\nTổng thanh toán: ' + document.getElementById('wizardTotalPrice').innerText;
    if (appliedVoucherCode) {
        summary += '\n(Đã áp dụng mã Voucher: ' + appliedVoucherCode + ')';
    }
    alert(summary);
    closeModal('lensWizardModal');
}
