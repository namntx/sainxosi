# Hệ Thống Quản Trị Xổ Số Việt Nam

Hệ thống API và giao diện quản trị để lấy kết quả xổ số từ az24.vn, lưu trữ vào Supabase và quản lý người dùng qua Firebase.

## Tính Năng

### 1. Quản Lý Kết Quả Xổ Số
- ✅ Lấy kết quả xổ số tự động từ az24.vn
- ✅ Hỗ trợ 3 miền: Bắc (MB), Trung (MT), Nam (MN)
- ✅ Lưu trữ và cập nhật tự động vào Supabase
- ✅ Giao diện web hiện đại để fetch kết quả

### 2. Quản Lý Người Dùng
- ✅ CRUD đầy đủ cho người dùng (Create, Read, Update, Delete)
- ✅ Quản lý ngày hết hạn subscription
- ✅ Hiển thị trạng thái còn hạn/hết hạn
- ✅ Tìm kiếm và filter người dùng

### 3. Dashboard Thống Kê
- ✅ Tổng số người dùng
- ✅ Số người dùng còn hạn
- ✅ Số người dùng hết hạn

## Công Nghệ Sử Dụng

- **Backend**: PHP 7.4+
- **Database**:
  - Supabase (PostgreSQL) - Lưu kết quả xổ số
  - Firebase Firestore - Quản lý người dùng
- **Frontend**:
  - Tailwind CSS - Styling
  - Alpine.js - JavaScript framework
  - Font Awesome - Icons

## Cài Đặt

### 1. Yêu Cầu Hệ Thống
```bash
- PHP 7.4 hoặc cao hơn
- Apache/Nginx web server
- Composer (optional, cho Firebase Admin SDK)
- Tài khoản Supabase
- Tài khoản Firebase
```

### 2. Cấu Hình

#### a. Cấu hình Supabase & Firebase
Chỉnh sửa file `config.php`:

```php
define('SUPABASE_URL', 'YOUR_SUPABASE_URL');
define('SUPABASE_KEY', 'YOUR_SUPABASE_ANON_KEY');
define('FIREBASE_PROJECT_ID', 'YOUR_FIREBASE_PROJECT_ID');
define('FIREBASE_API_KEY', 'YOUR_FIREBASE_API_KEY');
```

#### b. Tạo Database Table (Supabase)

Chạy script SQL để tạo bảng `lottery_results`:

```sql
CREATE TABLE lottery_results (
    id SERIAL PRIMARY KEY,
    date VARCHAR(10) NOT NULL,
    region VARCHAR(10) NOT NULL,
    province VARCHAR(50) NOT NULL,
    prizes JSONB NOT NULL,
    created_at TIMESTAMP DEFAULT NOW(),
    UNIQUE(date, region, province)
);
```

Sau đó chạy file `UPDATE_RLS_POLICY.sql` để cấu hình Row Level Security.

#### c. Cấu hình Firebase

Tạo collection `users` trong Firebase Firestore. Cấu hình security rules theo file `FIREBASE_RULES.md`.

### 3. Khởi Động

#### Truy cập giao diện web:
```
http://your-domain.com/index.php
```

Hoặc nếu chạy local:
```
http://localhost/sainxosi/index.php
```

### 4. Deployment trên aaPanel (Optional)

Nếu bạn deploy trên aaPanel và muốn sử dụng Firebase Auth integration:

#### Cài đặt Composer trên aaPanel:
1. SSH vào server
2. Cài đặt Composer:
```bash
cd ~
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

3. Verify cài đặt:
```bash
composer --version
```

#### Cài đặt Firebase Admin SDK:
```bash
cd /www/wwwroot/your-domain.com/api
composer require kreait/firebase-php
```

#### Upload Service Account File:
1. Tải Firebase service account JSON từ Firebase Console
2. Upload vào `/www/wwwroot/your-domain.com/api/`
3. Đảm bảo file được bảo vệ bởi `.htaccess`

**Lưu ý**: Nếu không cài Composer, hệ thống vẫn hoạt động bình thường nhưng:
- Không thể load danh sách users từ Firebase Auth
- Phải nhập User ID thủ công khi thêm user
- Email sẽ không hiển thị trong danh sách users

## Sử Dụng

### Giao Diện Web (index.php)

#### 1. Dashboard
- Xem thống kê tổng quan
- Truy cập nhanh các chức năng

#### 2. Tab "Kết Quả XS"
1. Chọn miền (Bắc/Trung/Nam)
2. Chọn ngày muốn lấy kết quả
3. Click "Lấy Kết Quả"
4. Hệ thống sẽ tự động:
   - Fetch dữ liệu từ az24.vn
   - Parse kết quả
   - Lưu vào Supabase

#### 3. Tab "Người Dùng"
- **Thêm mới**: Nhập User ID và ngày hết hạn, click "Thêm Mới"
- **Cập nhật**: Click "Sửa" trên user cần sửa, chỉnh sửa và click "Cập Nhật"
- **Xóa**: Click "Xóa" và confirm
- **Tìm kiếm**: Gõ User ID vào ô tìm kiếm

### API Endpoints

#### 1. Lottery Results API (`api_fetch.php`)

**GET Request:**
```bash
GET /api_fetch.php?region=mn&date=2025-11-12
```

**POST Request:**
```bash
POST /api_fetch.php
Content-Type: application/json

{
  "region": "mn",
  "date": "2025-11-12"
}
```

**Parameters:**
- `region`: `mb` (Miền Bắc), `mt` (Miền Trung), `mn` (Miền Nam)
- `date`: Format `YYYY-MM-DD` (optional, mặc định hôm nay)

**Response:**
```json
{
  "success": true,
  "region": "mn",
  "date": "2025-11-12",
  "inserted": 3,
  "updated": 0,
  "errors": 0
}
```

#### 2. Users API (`api_users.php`)

**GET - Lấy tất cả users:**
```bash
GET /api_users.php
```

**POST - Tạo/Cập nhật user:**
```bash
POST /api_users.php
Content-Type: application/json

{
  "userId": "user123",
  "expirationDate": "2024-12-31 23:59:59"
}
```

**DELETE - Xóa user:**
```bash
DELETE /api_users.php?userId=user123
```

### Command Line Tool

Fetch kết quả xổ số qua CLI:

```bash
# Lấy kết quả Miền Nam ngày hôm nay
php fetch_lottery_results.php mn

# Lấy kết quả Miền Bắc ngày cụ thể
php fetch_lottery_results.php mb 2025-11-12

# Lấy kết quả Miền Trung
php fetch_lottery_results.php mt 2025-11-12
```

## Cấu Trúc Dự Án

```
sainxosi/
├── index.php                      # Giao diện web quản trị
├── api_fetch.php                  # API lấy kết quả xổ số
├── api_users.php                  # API quản lý người dùng
├── config.php                     # Cấu hình Supabase & Firebase
├── supabase_client.php            # Client cho Supabase
├── firebase_client.php            # Client cho Firebase (REST)
├── firebase_client_admin.php      # Client cho Firebase (Admin SDK)
├── az24_parser.php                # Parser cho az24.vn
├── fetch_lottery_results.php      # CLI tool
├── .htaccess                      # Apache config
├── FIREBASE_RULES.md              # Hướng dẫn Firebase rules
├── UPDATE_RLS_POLICY.sql          # SQL cho Supabase RLS
└── README.md                      # File này
```

## Bảo Mật

### ⚠️ Quan Trọng

1. **Bảo vệ config.php**: File `.htaccess` đã được cấu hình để chặn truy cập trực tiếp
2. **Environment Variables**: Nên chuyển credentials trong `config.php` sang biến môi trường
3. **Firebase Service Account**: File JSON không được commit vào Git
4. **Row Level Security**: Đã được cấu hình cho Supabase
5. **CORS**: API endpoints có CORS headers cho phép cross-origin requests

## Troubleshooting

### Lỗi kết nối Supabase
- Kiểm tra `SUPABASE_URL` và `SUPABASE_KEY` trong `config.php`
- Kiểm tra RLS policies đã được apply chưa

### Lỗi kết nối Firebase
- Kiểm tra `FIREBASE_PROJECT_ID` và `FIREBASE_API_KEY`
- Kiểm tra Firebase security rules
- Nếu dùng Admin SDK, kiểm tra service account JSON file

### UI không load được dữ liệu
- Mở Developer Console (F12) để xem lỗi
- Kiểm tra API endpoints có hoạt động không
- Kiểm tra CORS headers

### Không fetch được kết quả từ az24.vn
- Kiểm tra kết nối internet
- Website az24.vn có thể thay đổi cấu trúc HTML
- Kiểm tra logs trong `az24_parser.php`

## License

MIT License

## Tác Giả

sainxosi

## Đóng Góp

Mọi đóng góp đều được chào đón! Hãy tạo Pull Request hoặc Issue.
