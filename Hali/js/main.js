// 1. Chuyển đổi các tab trên thanh điều hướng
function switchTab(tabId, el) {
    document.querySelectorAll('.section-view').forEach(sec => sec.classList.remove('active'));
    document.querySelectorAll('.nav-links a').forEach(link => link.classList.remove('active'));

    const target = document.getElementById(tabId);
    if (target) target.classList.add('active');
    if (el) el.classList.add('active');

    // Tự đóng menu mobile sau khi chọn tab
    const navLinks = document.querySelector('.nav-links');
    if (navLinks) navLinks.classList.remove('open');
}

// Bật/tắt menu trên mobile
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

// Chuyển đổi nhanh giữa Modal Đăng nhập và Modal Đăng ký
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

// 6. Xử lý POPUP CHỌN TRÒNG KÍNH TỪNG BƯỚC (3 Bước)
let currentFramePrice = 0;
let currentLensPrice = 0;
let currentStep = 1;

function openLensWizard(maSP, tenSP, giaGong) {
    currentFramePrice = parseFloat(giaGong);
    currentLensPrice = 0;
    document.getElementById('wizardProductName').innerText = tenSP + ' (' + maSP + ')';
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
    updateWizardTotal();
}

function updateWizardTotal() {
    const total = currentFramePrice + currentLensPrice;
    document.getElementById('wizardTotalPrice').innerText = total.toLocaleString('vi-VN') + ' đ';
}

function finishLensWizard() {
    alert('Đã cấu hình gọng + tròng kính thành công! Tổng tạm tính: ' + document.getElementById('wizardTotalPrice').innerText);
    closeModal('lensWizardModal');
}
