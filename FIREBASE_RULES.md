# 🔥 Firebase Firestore Security Rules

## Vấn đề

Khi gọi Firestore REST API từ PHP, bạn sẽ gặp lỗi **403: Missing or insufficient permissions** nếu security rules không cho phép.

## Giải pháp 1: Cập nhật Firestore Rules (Đơn giản)

Vào Firebase Console > Firestore Database > Rules và cập nhật như sau:

```javascript
rules_version = '2';
service cloud.firestore {
  match /databases/{database}/documents {
    // Users collection - cho phép đọc/ghi từ server (admin dashboard)
    match /users/{userId} {
      // Cho phép đọc tất cả (tạm thời cho admin dashboard)
      // Trong production, nên thêm authentication
      allow read: if true;
      allow write: if true;
      
      // Hoặc nếu muốn bảo mật hơn, chỉ cho phép từ server:
      // allow read, write: if request.auth != null;
    }
    
    // Customers collection - chỉ user đó mới đọc/ghi được
    match /customers/{customerId} {
      allow read, write: if request.auth != null && 
        resource.data.userId == request.auth.uid;
    }
  }
}
```

**Lưu ý:** Rules trên cho phép đọc/ghi không cần authentication. Để bảo mật hơn, nên sử dụng Admin SDK (xem Giải pháp 2).

## Giải pháp 2: Sử dụng Firebase Admin SDK (Khuyến nghị)

### Bước 1: Tải Service Account Key

1. Vào Firebase Console > **Project Settings** > **Service accounts**
2. Click **"Generate new private key"**
3. Download file JSON (ví dụ: `firebase-service-account.json`)
4. Upload file này lên server vào thư mục `api/` (KHÔNG commit vào git!)

### Bước 2: Cài đặt Firebase Admin SDK cho PHP

```bash
composer require kreait/firebase-php
```

### Bước 3: Cập nhật `firebase_client.php`

Sử dụng Admin SDK thay vì REST API (xem file mẫu bên dưới).

## Giải pháp 3: Sử dụng Firebase REST API với OAuth Token

Phức tạp hơn, cần tạo OAuth token từ service account.

## Khuyến nghị

- **Development/Testing**: Sử dụng Giải pháp 1 (cập nhật rules)
- **Production**: Sử dụng Giải pháp 2 (Admin SDK)

