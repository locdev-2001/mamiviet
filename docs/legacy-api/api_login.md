# Tài liệu API Đăng nhập (User & Admin)

## 1. Đăng nhập User

**Endpoint:**
```
POST /api/user/auth/login
```

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

**Response Thành công:**
```json
{
  "success": true,
  "data": {
    "user_id": 1,
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOi...",
    "expires_in": 3600,
    "name": "Nguyen Van A",
    "email": "user@example.com",
    "phone": "+84901234567",
    "address": {
      "street": "123 Main St",
      "city": "Ho Chi Minh",
      "country": "Vietnam",
      "postal_code": "700000"
    },
    "avatar": "https://domain.com/uploads/avatar1.jpg"
  }
}
```

**Response Thất bại (sai thông tin):**
```json
{
  "success": false,
  "message": "Sai thông tin đăng nhập"
}
```

**Response Lỗi validate:**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password field is required."]
  }
}
```

---

## 2. Đăng nhập Admin

**Endpoint:**
```
POST /api/admin/auth/login
```

**Request Body:**
```json
{
  "email": "admin@restaurant.com",
  "password": "admin_password"
}
```

**Response Thành công:**
```json
{
  "success": true,
  "data": {
    "admin_id": 1,
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOi...",
    "expires_in": 3600,
    "name": "Admin Name",
    "email": "admin@restaurant.com",
    "phone": "+84901111222",
    "address": {
      "street": "456 Admin St",
      "city": "Ha Noi",
      "country": "Vietnam",
      "postal_code": "100000"
    },
    "avatar": "https://domain.com/uploads/admin_avatar.jpg",
    "role": "admin"
  }
}
```

**Response Thất bại (sai thông tin):**
```json
{
  "success": false,
  "message": "Sai thông tin đăng nhập"
}
```

**Response Lỗi validate:**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password field is required."]
  }
}
```

---

## Ghi chú
- access_token dùng để xác thực các API cần đăng nhập (gửi qua header: `Authorization: Bearer <access_token>`)
- expires_in: thời gian token hết hạn (giây)
- address là object JSON gồm street, city, country, postal_code
- avatar là đường dẫn ảnh đại diện 