# SPTS – Student Performance Tracking System

Hệ thống quản lý và theo dõi kết quả học tập sinh viên được xây dựng trên Laravel Framework.

## Mục lục

- [Giới thiệu](#giới-thiệu)
- [Công nghệ sử dụng](#công-nghệ-sử-dụng)
- [Yêu cầu hệ thống](#yêu-cầu-hệ-thống)
- [Hướng dẫn cài đặt](#hướng-dẫn-cài-đặt)
- [Cấu hình dự án](#cấu-hình-dự-án)
- [Chạy dự án](#chạy-dự-án)
- [Cấu trúc thư mục](#cấu-trúc-thư-mục)
- [Troubleshooting](#troubleshooting)

---

## Giới thiệu

SPTS (Student Performance Tracking System) là hệ thống quản lý điểm số sinh viên được xây dựng trên nền tảng web với các chức năng:

- **Admin**: Quản lý người dùng, môn học, phân quyền hệ thống
- **Giảng viên**: Nhập điểm, quản lý lớp học, theo dõi sinh viên
- **Sinh viên**: Xem điểm, theo dõi tiến độ học tập

---

## Công nghệ sử dụng

- **Backend**: Laravel 12.x, PHP 8.2+
- **Frontend**: Blade Template, TailwindCSS, HTML5, CSS3, JavaScript
- **Database**: MySQL 8.0+
- **Build Tool**: Vite
- **Server**: Apache (XAMPP) hoặc Laravel Built-in Server
- **Package Manager**: Composer, NPM

---

## Yêu cầu hệ thống

Đảm bảo máy tính đã cài đặt:

- **PHP >= 8.2** (đi kèm XAMPP)
- **Composer** (quản lý dependencies PHP)
- **MySQL** (đi kèm XAMPP)
- **Node.js & NPM** (để build frontend assets với Vite)
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
git clone https://github.com/Shinnie102/SPTS.git

# Di chuyển vào thư mục dự án
cd SPTS
```

**Nếu nhận dự án từ đồng đội:**

- Giải nén file zip vào thư mục `?:\xampp\htdocs\SPTS`
- Mở Terminal tại thư mục dự án

### Bước 3: Cài đặt dependencies

```bash
# Cài đặt PHP dependencies
composer install

# Cài đặt các thư viện PHP bổ sung (Excel, PDF)
composer require phpoffice/phpspreadsheet dompdf/dompdf

# Cài đặt Node dependencies
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
3. **Tạo database mới** với tên: `academic_management` 

### Bước 4: Cập nhật file `.env`

Mở file `.env` và chỉnh sửa thông tin database:

```env
APP_NAME=SPTS
APP_ENV=local
APP_KEY=base64:xxxxxxxxxxxx
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=academic_management    # Tên database vừa tạo
DB_USERNAME=root                   # Username MySQL
DB_PASSWORD=                       # Password MySQL
```

### Bước 5: Chạy Migration

```bash
# Tạo các bảng trong database
php artisan migrate

# (Nếu cần) Chạy seeder để tạo dữ liệu mẫu
php artisan db:seed
```

### Bước 6: Build frontend assets

```bash
# Build assets cho production
npm run build

# Hoặc chạy development mode (hot reload)
npm run dev
```

### Bước 7: Tạo Storage Link

```bash
php artisan storage:link
```

---

## Chạy dự án

### Cách 1: Laravel Development Server

```bash
# Chạy server tại port 8000
php artisan serve

# Hoặc chỉ định port khác
php artisan serve --port=8080
```

### Cách 2: Sử dụng script tự động (khuyến nghị)

```bash
# Chạy tất cả services cùng lúc (server, queue, logs, vite)
composer run dev
```

Truy cập: **http://localhost:8000**

## Cấu trúc thư mục

```
SPTS/
├── app/                    # Mã nguồn ứng dụng
│   ├── Contracts/          # Interface contracts
│   ├── Http/Controllers/   # Controllers (MVC)
│   ├── Models/             # Models (Eloquent ORM)
│   ├── Repositories/       # Repository pattern
│   └── Services/           # Business logic services
├── bootstrap/              # Khởi tạo framework
├── config/                 # File cấu hình
├── database/
│   ├── migrations/         # Database migrations
│   ├── seeders/            # Database seeders
│   └── *.sql               # Database backup files
├── public/                 # Entry point (index.php)
│   ├── css/                # Compiled CSS
│   ├── js/                 # Compiled JavaScript
│   └── images/             # Static images
├── resources/
│   ├── css/                # Source CSS files
│   └── views/              # Blade templates
├── routes/
│   ├── web.php             # Định tuyến web
│   └── console.php         # Console commands
├── storage/                # File tạm, logs
├── tests/                  # Unit & Feature tests
├── .env                    # Cấu hình môi trường (KHÔNG commit)
├── artisan                 # Laravel CLI
├── composer.json           # PHP dependencies
├── package.json            # Node dependencies
└── vite.config.js          # Vite configuration
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

## Scripts có sẵn

```bash
# Setup dự án từ đầu
composer run setup

# Chạy development mode với hot reload
composer run dev

# Chạy tests
composer run test

# Build frontend assets
npm run build

# Development mode cho frontend
npm run dev
```

---

## License

Dự án này sử dụng cho mục đích học tập.

