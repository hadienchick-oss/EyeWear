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

// 2. Đóng / Mở Modal bất kỳ
function openModal(modalId) {
    document.getElementById(modalId).classList.add('open');
}
function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('open');
}

function switchAuthModal(fromModalId, toModalId) {
    closeModal(fromModalId);
    openModal(toModalId);
}

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

// 4. Mở Modal Thêm / Sửa Sản Phẩm
function openProductModal(mode, data = null) {
    document.getElementById('productFormAction').value = mode;
    if (mode === 'add') {
        document.getElementById('productModalTitle').innerText = 'Thêm Sản Phẩm Gọng Kính Mới';
        document.getElementById('productForm').reset();
        document.getElementById('maSanPham').value = '';
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
    }
    openModal('productModal');
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
            // Chuyển YYYY-MM-DD HH:MM:SS sang định dạng datetime-local YYYY-MM-DDTHH:MM
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

    // Đặt mặc định chọn tròng đầu tiên (350.000đ)
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
    // Nếu đã áp mã voucher, tính lại hoặc reset nếu thay đổi tổng tiền
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
