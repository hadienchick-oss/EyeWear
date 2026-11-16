<div align="center">
  <img src="eyewear/images/logo.png" alt="Việt MAC Logo" width="120" height="120" style="border-radius: 16px;" />

# 👓 VIỆT MAC - WEBSITE THƯƠNG MẠI ĐIỆN TỬ KINH DOANH MẮT KÍNH

**Việt MAC** là nền tảng thương mại điện tử kết hợp bán lẻ trực tiếp (O2O) chuyên biệt cho ngành mắt kính thời trang và kính thuốc, giúp người dùng dễ dàng lựa chọn gọng kính, cấu hình tròng kính theo số đo thị lực và quản lý đơn hàng chuẩn xác.
</div>

---

## 📖 Mục lục

* [📖 Mục lục](#-mục-lục)
* [🚀 Giới thiệu](#-giới-thiệu)
* [✨ Tính năng cốt lõi](#-tính-năng-cốt-lõi)
* [🏗 Kiến trúc dự án](#-kiến-trúc-dự-án)
* [🛠 Công nghệ sử dụng](#-công-nghệ-sử-dụng)
  * [Giao diện người dùng (Frontend)](#giao-diện-người-dùng-frontend)
  * [Phần phụ trợ (Backend)](#phần-phụ-trợ-backend)
  * [Cơ sở dữ liệu (Database)](#cơ-sở-dữ-liệu-database)
  * [Thiết kế & Công cụ](#thiết-kế--công-cụ)
* [💻 Yêu cầu hệ thống](#-yêu-cầu-hệ-thống)
* [⚙️ Hướng dẫn cài đặt và chạy dự án](#️-hướng-dẫn-cài-đặt-và-chạy-dự-án)
* [🔑 Tài khoản trải nghiệm (Demo Accounts)](#-tài-khoản-trải-nghiệm-demo-accounts)
* [👥 Thành viên đóng góp](#-thành-viên-đóng-góp)
* [📄 Giấy phép](#-giấy-phép)

## 🚀 Giới thiệu

Khác với các mặt hàng thời trang thông thường, việc kinh doanh mắt kính trực tuyến đòi hỏi sự kết hợp chặt chẽ giữa **Gọng kính vật lý** và **Cặp tròng kính thuốc** (theo từng thông số độ cận, độ loạn, trục và chiết suất riêng biệt của khách hàng).

**Việt MAC** được xây dựng nhằm mang đến trải nghiệm mua sắm mắt kính hiện đại và chuẩn xác. Nền tảng bao gồm hệ thống **Backend PHP & CSDL MySQL** chuẩn hóa giúp kiểm soát tồn kho chính xác theo thời gian thực (loại trừ triệt để tình trạng bán vượt tồn) cùng giao diện **Web** trực quan phục vụ 3 nhóm đối tượng: **Khách hàng**, **Nhân viên (Bán hàng/Kho)** và **Quản trị viên (Admin)**.

## ✨ Tính năng cốt lõi

* 📋 **Hồ sơ Đơn kính thuốc cá nhân (Prescription Hub):** Cho phép khách hàng lưu trữ, tra cứu và tái sử dụng thông số đo mắt ($\text{SPH}$, $\text{CYL}$, $\text{AXIS}$, $\text{PD}$), tự động gắn vào chi tiết đơn hàng khi đặt cắt tròng kính mà không phải nhập lại nhiều lần.
* 🔒 **Kiểm soát tồn kho thời gian thực (Khóa 15 phút):** Phân định rõ giữa số lượng tồn vật lý (`soLuong`) và số lượng đang giữ tạm thời trong phiên thanh toán (`soLuongDat`). Hệ thống tự động khóa hàng 15 phút khi khách nhấn thanh toán ($\text{soLuong} - \text{soLuongDat} > 0$) và tự động hoàn trả về kho nếu hủy hoặc quá hạn.
* 🔄 **Tiếp nhận Khách vãng lai & Chuyển đổi tài khoản:** Hỗ trợ khách vãng lai đặt mua nhanh chỉ cần cung cấp Số điện thoại và Địa chỉ; khi khách mua từ lần thứ hai, hệ thống tự động nhận diện SĐT cũ để mời đăng ký thành viên nhận Voucher ưu đãi và đồng bộ lịch sử đơn hàng.
* 📦 **Quản lý Sản phẩm, Đa ảnh & Trạng thái linh hoạt:** Hỗ trợ tải lên nhiều hình ảnh cho từng mã sản phẩm (`ANHSP`), quản lý chi tiết kích thước gọng kính, thương hiệu và xử lý trạng thái sản phẩm tạm ngưng/ngừng kinh doanh (Xóa mềm) để bảo toàn lịch sử đơn hàng.
* 🖨️ **Gia công Mài lắp & Giao vận:** Hỗ trợ nhân viên và Admin theo dõi quy trình đơn hàng (*Chờ xử lý* $\rightarrow$ *Đang xử lý* $\rightarrow$ *Mài lắp* $\rightarrow$ *Đóng gói* $\rightarrow$ *Đã giao*), tự động trích xuất thông số quang học để **In phiếu kỹ thuật mài lắp** và **In phiếu giao vận**.
* 🎟️ **Quản lý Voucher & Truy vết chống gian lận:** Phát hành mã giảm giá theo số tiền cố định hoặc phần trăm ($\%$), kiểm tra giá trị đơn tối thiểu, giới hạn lượt dùng và lưu vết chi tiết trong bảng `LICHSUVOUCHER`.
* 📊 **Thống kê doanh thu & Báo cáo:** Tổng hợp trực quan doanh thu bán lẻ, số lượng đơn hàng hoàn tất và biến động xuất nhập tồn kho theo ngày, tháng, năm.


## 🏗 Kiến trúc dự án

Dự án được tổ chức theo cấu trúc Modular gọn gàng, tách biệt rõ ràng giữa giao diện trung tâm (`index.php`), tài nguyên tĩnh (`css`, `js`, `images`) và tầng xử lý nghiệp vụ Backend (`xuly/`):

```text
Hali/
├── css/
│   └── style.css               # Định dạng giao diện người dùng, Admin UI, Popup & Responsive
├── images/
│   ├── anh_sp/                 # Lưu trữ hình ảnh sản phẩm gọng kính & kính mát (<masp>_filename)
│   └── logo_thuonghieu/        # Lưu trữ logo các thương hiệu mắt kính đối tác
├── js/
│   └── main.js                 # Xử lý sự kiện Client, điều hướng menu, step-by-step popup & AJAX
├── xuly/                       # Tầng Backend PHP xử lý nghiệp vụ & CSDL
│   ├── config.php              # Cấu hình kết nối Cơ sở dữ liệu MySQL & khởi tạo Session
│   ├── functions.php           # Các hàm dùng chung (sinh mã tự động taoMaTuDong, truy vấn, kiểm tra quyền)
│   ├── auth_action.php         # Xử lý Đăng nhập, Đăng ký khách hàng, Đăng xuất & Phân quyền
│   ├── sanpham_action.php      # Xử lý CRUD Sản phẩm, Chi tiết SP, Tồn kho & Upload nhiều ảnh (ANHSP)
│   ├── thuonghieu_action.php   # Xử lý CRUD Thương hiệu & tải lên Logo thương hiệu
│   ├── nhanvien_action.php     # Xử lý Quản lý Nhân viên (Thêm/Sửa/Xóa/Phân quyền)
│   └── voucher_action.php      # Xử lý Phát hành, Cập nhật, Xóa & Kiểm tra điều kiện mã Voucher
├── index.php                   # Cổng giao diện trung tâm tích hợp điều hướng tự động theo phân quyền
└── README.md                   # Tài liệu hướng dẫn và mô tả dự án
```

## 🛠 Công nghệ sử dụng

### Giao diện người dùng (Frontend)

* **Ngôn ngữ cốt lõi:** HTML5, CSS3 (`css/style.css`), JavaScript thuần (`js/main.js`)
* **Thiết kế UI/UX:** Giao diện hiện đại, tối giản với thanh điều hướng tự động thích ứng theo vai trò người dùng (Auto-nav menu), kết hợp các hộp thoại thao tác từng bước (Step-by-step Popups) và chuẩn Responsive trên cả Desktop lẫn Mobile.
* **Lộ trình nâng cấp:** Tích hợp thư viện nhận diện khuôn mặt & AR Try-On qua Camera trong bản cập nhật tới.

### Phần phụ trợ (Backend)

* **Ngôn ngữ xử lý máy chủ:** PHP (Native PHP tối ưu tốc độ phản hồi và xử lý giao dịch CSDL)
* **Kiến trúc xử lý (`xuly/`):** Tách biệt từng luồng nghiệp vụ thành các tệp `*_action.php` chuyên biệt, kết hợp bộ hàm tiện ích `functions.php` (hỗ trợ sinh khóa chính tự động `taoMaTuDong` và xử lý Transaction an toàn).
* **Bảo mật & Phân quyền:** Quản lý phiên làm việc qua `PHP Session`, phân quyền hiển thị menu và thao tác chặt chẽ giữa `Admin`, `Nhân viên` và `Khách hàng`.

### Cơ sở dữ liệu (Database)

* **Hệ quản trị CSDL quan hệ (RDBMS):** MySQL / MariaDB
* **Mô hình dữ liệu chuẩn hóa:**
  * *Người dùng & Phân quyền:* `VAITRO`, `NGUOIDUNG`, `KHACHHANG`
  * *Sản phẩm & Cấu hình kính:* `THUONGHIEU`, `SANPHAM`, `CHITIETSANPHAM`, `ANHSP`, `TRONGKINH`, `KETQUADOMAT`
  * *Kho hàng thời gian thực:* `TONKHO` (`soLuong` & `soLuongDat`)
  * *Giao dịch & Mua sắm:* `GIOHANG`, `CHITIETGIOHANG`, `DONHANG`, `CHITIETDONHANG`, `THANHTOAN`
  * *Khuyến mãi & Tương tác:* `VOUCHER`, `LICHSUVOUCHER`, `DANHGIA`

### Thiết kế & Công cụ

* **Môi trường phát triển:** Visual Studio Code, XAMPP / WAMP / Laragon (Apache + MySQL)
* **Quản lý mã nguồn:** Git & GitHub
* **Phân tích & Thiết kế hệ thống:** Draw.io (Sơ đồ ERD, Sơ đồ phân rã chức năng WBS, Sơ đồ Use Case & Quy trình nghiệp vụ)

## 💻 Yêu cầu hệ thống

Trước khi bắt đầu cài đặt, hãy đảm bảo máy tính của bạn đã trang bị:

* **XAMPP**, **WAMP**, hoặc **Laragon** (Khuyến nghị PHP `>= 8.0` & MySQL `>= 5.7` / MariaDB `10.4+`)
* **Git** (Để clone kho lưu trữ)
* **Trình duyệt Web hiện đại** (Google Chrome, Microsoft Edge, Firefox...)

## ⚙️ Hướng dẫn cài đặt và chạy dự án

**1. Clone kho lưu trữ vào thư mục máy chủ Web**

Di chuyển vào thư mục `htdocs` (nếu dùng XAMPP) hoặc `www` (nếu dùng Laragon/WAMP) và clone dự án:

```bash
cd C:/xampp/htdocs
git clone https://github.com/hadienchick-oss/Hali.git
```

**2. Khởi tạo Cơ sở dữ liệu MySQL**

1. Mở bảng điều khiển **XAMPP Control Panel** và nhấn **Start** cho 2 dịch vụ **Apache** và **MySQL**.
2. Truy cập trình quản trị CSDL tại địa chỉ: `http://localhost/phpmyadmin`
3. Tạo một cơ sở dữ liệu mới (ví dụ: `quanly_kinhmat` hoặc tên database cấu hình trong `xuly/config.php` với bảng mã `utf8mb4_unicode_ci`).
4. Chọn tab **Import (Nhập)**, tải lên tệp kịch bản `.sql` của dự án và nhấn **Go (Thực hiện)** để khởi tạo toàn bộ bảng thực thể cùng dữ liệu mẫu.

**3. Kiểm tra cấu hình kết nối (`xuly/config.php`)**

Mở tệp `xuly/config.php` và đảm bảo thông số kết nối khớp với môi trường MySQL trên máy của bạn:

```php
<?php
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "quanly_kinhmat"; // Thay đổi theo tên CSDL của bạn
?>
```

**4. Khởi chạy và trải nghiệm ứng dụng**

Mở trình duyệt web và truy cập trực tiếp vào cổng giao diện chính:

* **Địa chỉ truy cập:** `http://localhost/Hali/` (hoặc `http://localhost/Hali/index.php`)

## 🔑 Tài khoản trải nghiệm (Demo Accounts)

Hệ thống đã được thiết lập sẵn dữ liệu tài khoản mẫu đại diện cho 3 nhóm phân quyền để giảng viên và người dùng dễ dàng kiểm thử chức năng ngay sau khi đăng nhập:

| Vai trò (Actor) | Email đăng nhập | Mật khẩu | Quyền hạn chính trong hệ thống |
| :--- | :--- | :---: | :--- |
| **Quản trị viên (Admin)** | `admin@kinhmat.vn` | `123` | Toàn quyền quản trị hệ thống: Quản lý Nhân viên (`nhanvien_action.php`), Sản phẩm & Tồn kho (`sanpham_action.php`), Thương hiệu (`thuonghieu_action.php`), phát hành Voucher (`voucher_action.php`), xử lý đơn hàng và xem thống kê. |
| **Nhân viên (Staff)** | `nhanvien@kinhmat.vn` | `123` | Hiển thị menu theo phân quyền nhân viên: Kiểm soát & cập nhật tồn kho, thêm/sửa sản phẩm, tiếp nhận đơn hàng, in phiếu kỹ thuật/giao vận và hỗ trợ khách hàng. |
| **Khách hàng 1** | `nguyenvanan@gmail.com` | `123` | Tìm kiếm kính, lưu hồ sơ độ mắt (`KETQUADOMAT`), quản lý giỏ hàng, áp dụng mã Voucher, đặt hàng và gửi đánh giá sao. |
| **Khách hàng 2** | `bichngoc@gmail.com` | `123` | Tài khoản khách hàng thành viên dùng để kiểm thử luồng đặt hàng, khóa tồn kho 15 phút và lịch sử mua sắm. |

## 👥 Thành viên đóng góp

Đồ án **Thực Tập Chuyên Ngành (Năm 2026)** được thực hiện tại **Khoa Công Nghệ Thông Tin - Trường Đại Học Công Nghệ Sài Gòn (STU)** dưới sự hướng dẫn tận tình của **ThS. Nguyễn Thị Ngân Hà**.

Cảm ơn các thành viên đã đóng góp để xây dựng nền tảng **Việt MAC**:

| Ảnh đại diện | Họ và Tên | Vai trò | Liên kết Github |
| :---: | :--- | :--- | :--- |
| <img src="https://github.com/hadienchick-oss.png" width="75" height="75" style="border-radius: 50%;" alt="Lý Văn Hải"/> | **Lý Văn Hải** | Fullstack Developer | [@hadienchick-oss](https://github.com/hadienchick-oss) |
| <img src="https://github.com/maianhhao-it.png" width="75" height="75" style="border-radius: 50%;" alt="Mai Anh Hào"/> | **Mai Anh Hào** | Thiết kế sơ đồ hệ thống (System Analyst) | [@maianhhao-it](https://github.com/maianhhao-it) |

## 📄 Giấy phép

Dự án này được phát triển phục vụ mục đích học tập và nghiên cứu cho học phần **Thực tập Chuyên ngành** tại **Trường Đại học Công nghệ Sài Gòn (STU)**. Mọi quyền đều được bảo vệ theo quy định của nhà trường và nhóm tác giả.


