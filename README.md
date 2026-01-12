# PointC – Student Performance Tracking System

Hệ thống quản lý và theo dõi kết quả học tập sinh viên được xây dựng trên Laravel Framework.

## Mục lục

- [Giới thiệu](#giới-thiệu)
- [Mục tiêu](#mục-tiêu)
- [Phạm vi](#phạm-vi)
- [Công nghệ sử dụng](#công-nghệ-sử-dụng)
- [Yêu cầu hệ thống](#yêu-cầu-hệ-thống)
- [Hướng dẫn cài đặt](#hướng-dẫn-cài-đặt)
- [Cấu hình dự án](#cấu-hình-dự-án)
- [Chạy dự án](#chạy-dự-án)
- [Cấu trúc thư mục](#cấu-trúc-thư-mục)
- [Troubleshooting](#troubleshooting)

---

## Giới thiệu

PointC (Student Performance Tracking System) là hệ thống quản lý điểm số sinh viên được xây dựng trên nền tảng web với các chức năng:

- **Admin**: Quản lý người dùng, môn học, phân quyền hệ thống
- **Giảng viên**: Nhập điểm, quản lý lớp học, theo dõi sinh viên
- **Sinh viên**: Xem điểm, theo dõi tiến độ học tập

---

## Mục tiêu

### Mục tiêu chung

Xây dựng hệ thống quản lý điểm số sinh viên số hóa, tập trung và minh bạch, thay thế phương thức quản lý thủ công truyền thống.

### Mục tiêu cụ thể

**Về kỹ thuật:**
- Xây dựng cơ sở dữ liệu quan hệ đảm bảo tính toàn vẹn và nhất quán của dữ liệu
- Áp dụng kiến trúc MVC (Model-View-Controller) để tổ chức mã nguồn khoa học
- Triển khai hệ thống xác thực và phân quyền dựa trên vai trò (Role-Based Access Control)
- Tối ưu hóa trải nghiệm người dùng với giao diện responsive

**Về nghiệp vụ:**
- Cho phép Admin quản lý toàn bộ người dùng và môn học trong hệ thống
- Hỗ trợ Giảng viên nhập và quản lý điểm số một cách nhanh chóng, chính xác
- Giúp Sinh viên tra cứu điểm số và theo dõi kết quả học tập một cách kịp thời
- Giảm thiểu sai sót trong quy trình nhập liệu và tính toán điểm

---

## Phạm vi

### Phạm vi chức năng

Hệ thống PointC bao gồm các chức năng chính:

**Quản lý người dùng:**
- Đăng ký, đăng nhập, phân quyền tài khoản
- Quản lý thông tin sinh viên, giảng viên
- Đổi mật khẩu, cập nhật thông tin cá nhân

**Quản lý môn học:**
- Thêm, sửa, xóa môn học
- Phân công giảng viên phụ trách môn học
- Quản lý danh sách sinh viên theo lớp

**Quản lý điểm số:**
- Nhập điểm thành phần (chuyên cần, giữa kỳ, cuối kỳ)
- Tự động tính điểm tổng kết theo công thức
- Xem bảng điểm theo môn học, theo học kỳ
- Xuất báo cáo điểm

**Ngoài phạm vi:**

Hệ thống không bao gồm các chức năng:
- Quản lý học phí và thanh toán
- Hệ thống thư viện số
- E-learning và bài giảng trực tuyến
- Quản lý lịch học và thời khóa biểu
- Quản lý ký túc xá

### Phạm vi công nghệ

- **Frontend**: HTML5, CSS3, JavaScript, Blade Template Engine
- **Backend**: PHP 8.2+, Laravel Framework 12.x
- **Database**: MySQL 8.0+
- **Server**: Apache (XAMPP) hoặc Laravel Built-in Server
- **Tools**: Composer, Git, NPM

---

## Công nghệ sử dụng

- **Backend**: Laravel 12.x, PHP 8.2+
- **Frontend**: Blade Template, HTML5, CSS3, JavaScript
- **Database**: MySQL 8.0+
- **Server**: Apache (XAMPP) hoặc Laravel Built-in Server
- **Package Manager**: Composer, NPM

---

## Yêu cầu hệ thống

Đảm bảo máy tính đã cài đặt:

- **PHP >= 8.2** (đi kèm XAMPP)
- **Composer** (quản lý dependencies PHP)
- **MySQL** (đi kèm XAMPP)
- **Node.js & NPM** (nếu sử dụng Vite/frontend assets)
- **Git** (để clone và quản lý code)
- **XAMPP** (môi trường phát triển)

---

## Hướng dẫn cài đặt

### Bước 1: Kiểm tra môi trường

Mở **Terminal/PowerShell** và kiểm tra các công cụ đã cài đặt:

```bash
php -v          # PHP 8.2.12 hoặc cao hơn
composer -V     # Composer 2.x
mysql --version # MySQL 8.0+
node -v         # Node.js 18+ (nếu dùng)
git --version   # Git 2.x+
```

### Bước 2: Clone hoặc tải project

**Nếu có repository Git:**

```bash
# Clone dự án
git clone <repository-url> PointC

# Di chuyển vào thư mục dự án
cd PointC
```

**Nếu nhận dự án từ đồng đội:**

- Giải nén file zip vào thư mục `D:\Download\Program\xampp\htdocs\PointC`
- Mở Terminal tại thư mục dự án

### Bước 3: Cài đặt dependencies

```bash
# Cài đặt PHP dependencies
composer install

# Cài đặt Node dependencies (nếu có)
npm install
```

> **Lưu ý**: Quá trình `composer install` có thể mất 2-5 phút tùy tốc độ mạng.

---

## Cấu hình dự án

### Bước 1: Tạo file môi trường

```bash
# Copy file .env.example thành .env
copy .env.example .env
```

### Bước 2: Tạo Application Key

```bash
php artisan key:generate
```

### Bước 3: Cấu hình Database

1. **Khởi động XAMPP** → Bật **Apache** và **MySQL**
2. **Truy cập phpMyAdmin**: http://localhost/phpmyadmin
3. **Tạo database mới** với tên: `PointC_db` 

### Bước 4: Cập nhật file `.env`

Mở file `.env` và chỉnh sửa thông tin database:

```env
APP_NAME=PointC
APP_ENV=local
APP_KEY=base64:xxxxxxxxxxxx
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=PointC_db          # Tên database vừa tạo
DB_USERNAME=root             # Username MySQL
DB_PASSWORD=                 # Password
```

### Bước 5: Chạy Migration

```bash
# Tạo các bảng trong database
php artisan migrate

# (Nếu cần) Chạy seeder để tạo dữ liệu mẫu
php artisan db:seed
```

### Bước 6: Tạo Storage Link

```bash
php artisan storage:link
```

---

## Chạy dự án

### Laravel Development Server

```bash
# Chạy server tại port 8000
php artisan serve

# Hoặc chỉ định port khác
php artisan serve --port=8080
```

Truy cập: **http://localhost:8000**

## Cấu trúc thư mục

```
PointC/
├── app/                    # Mã nguồn ứng dụng
│   ├── Http/Controllers/   # Controllers (MVC)
│   ├── Models/             # Models (Eloquent ORM)
│   └── ...
├── bootstrap/              # Khởi tạo framework
├── config/                 # File cấu hình
├── database/
│   ├── migrations/         # Database migrations
│   └── seeders/            # Database seeders
├── public/                 # Entry point (index.php)
├── resources/
│   ├── views/              # Blade templates
│   └── js/css/             # Frontend assets
├── routes/
│   ├── web.php             # Định tuyến web
│   └── api.php             # Định tuyến API
├── storage/                # File tạm, logs
├── tests/                  # Unit tests
├── .env                    # Cấu hình môi trường (KHÔNG commit)
├── artisan                 # Laravel CLI
├── composer.json           # PHP dependencies
└── package.json            # Node dependencies
```

---

## Troubleshooting

### Lỗi: Class not found

```bash
composer dump-autoload
```

### Lỗi: Permission denied (storage/logs)

```bash
# Windows (PowerShell - Run as Admin)
icacls "storage" /grant Users:F /t
icacls "bootstrap\cache" /grant Users:F /t
```

### Lỗi: SQLSTATE Connection refused

- Kiểm tra XAMPP MySQL đã chạy chưa
- Kiểm tra thông tin trong `.env` đúng chưa
- Thử restart MySQL trong XAMPP

### Xóa cache Laravel

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

---

## Đội ngũ phát triển

---

## Hỗ trợ

Nếu gặp vấn đề, liên hệ qua:
- Email:
---

## License

Dự án này thuộc về nhóm phát triển PointC - Sử dụng cho mục đích học tập.

