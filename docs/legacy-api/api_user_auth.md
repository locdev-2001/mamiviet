# Tài liệu API Xác thực & Quản lý tài khoản User

## 1. Đăng ký tài khoản

**Endpoint:**
```
POST /api/user/auth/register
```

**Request Body (multipart/form-data):**
| Trường                | Kiểu dữ liệu | Bắt buộc | Mô tả                                  |
|-----------------------|--------------|----------|----------------------------------------|
| name                  | string       | Có       | Tên người dùng                         |
| email                 | string       | Có       | Email duy nhất                         |
| password              | string       | Có       | Mật khẩu (min 6 ký tự)                 |
| password_confirmation | string       | Có       | Xác nhận mật khẩu                      |
| phone                 | string       | Không    | Số điện thoại                          |
| address[street]       | string       | Không    | Địa chỉ - đường                        |
| address[city]         | string       | Không    | Địa chỉ - thành phố                    |
| address[country]      | string       | Không    | Địa chỉ - quốc gia                     |
| address[postal_code]  | string       | Không    | Địa chỉ - mã bưu điện                  |
| avatar                | file (image) | Không    | Ảnh đại diện (jpeg, png, jpg, gif, <=2MB) |

**Response thành công:**
```json
{
  "success": true,
  "data": {
    "user_id": 1,
    "name": "Nguyen Van A",
    "email": "user@example.com",
    "phone": "+84901234567",
    "address": {
      "street": "123 Main St",
      "city": "Ho Chi Minh",
      "country": "Vietnam",
      "postal_code": "700000"
    },
    "avatar": "/storage/avatars/abcxyz.jpg",
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOi..."
  }
}
```

**Response lỗi validate:**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email has already been taken."],
    "password": ["The password must be at least 6 characters."]
  }
}
```

---

## 2. Đăng nhập

**Endpoint:**
```
POST /api/user/auth/login
```

**Request Body (application/json):**
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

**Response thành công:**
```json
{
  "success": true,
  "data": {
    "user_id": 1,
    "name": "Nguyen Van A",
    "email": "user@example.com",
    "phone": "+84901234567",
    "address": {
      "street": "123 Main St",
      "city": "Ho Chi Minh",
      "country": "Vietnam",
      "postal_code": "700000"
    },
    "avatar": "/storage/avatars/abcxyz.jpg",
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOi..."
  }
}
```

**Response thất bại:**
```json
{
  "success": false,
  "message": "Sai thông tin đăng nhập"
}
```

**Response lỗi validate:**
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

## 3. Lấy thông tin profile

**Endpoint:**
```
GET /api/user/profile
```

**Yêu cầu:**
- Header: `Authorization: Bearer <access_token>`

**Response thành công:**
```json
{
  "success": true,
  "data": {
    "user_id": 1,
    "name": "Nguyen Van A",
    "email": "user@example.com",
    "phone": "+84901234567",
    "address": {
      "street": "123 Main St",
      "city": "Ho Chi Minh",
      "country": "Vietnam",
      "postal_code": "700000"
    },
    "avatar": "/storage/avatars/abcxyz.jpg"
  }
}
```

**Response lỗi (chưa đăng nhập):**
```json
{
  "message": "Unauthenticated."
}
```

## 4. Cập nhật thông tin profile

**Endpoint:**
```
PUT /api/user/profile
```

**Yêu cầu:**
- Header: `Authorization: Bearer <access_token>`
- Body: multipart/form-data

| Trường                | Kiểu dữ liệu | Bắt buộc | Mô tả                                  |
|-----------------------|--------------|----------|----------------------------------------|
| name                  | string       | Không    | Tên người dùng                         |
| phone                 | string       | Không    | Số điện thoại                          |
| address[street]       | string       | Không    | Địa chỉ - đường                        |
| address[city]         | string       | Không    | Địa chỉ - thành phố                    |
| address[country]      | string       | Không    | Địa chỉ - quốc gia                     |
| address[postal_code]  | string       | Không    | Địa chỉ - mã bưu điện                  |
| avatar                | file (image) | Không    | Ảnh đại diện mới (jpeg, png, jpg, gif, <=2MB) |

**Response thành công:**
```json
{
  "success": true,
  "data": {
    "user_id": 1,
    "name": "Nguyen Van A",
    "email": "user@example.com",
    "phone": "+84901234567",
    "address": {
      "street": "123 Main St",
      "city": "Ho Chi Minh",
      "country": "Vietnam",
      "postal_code": "700000"
    },
    "avatar": "/storage/avatars/abcxyz.jpg"
  }
}
```

**Response lỗi (chưa đăng nhập):**
```json
{
  "message": "Unauthenticated."
}
```

## 5. Đổi mật khẩu

**Endpoint:**
```
PUT /api/user/change-password
```

**Yêu cầu:**
- Header: `Authorization: Bearer <access_token>`
- Body: application/json

| Trường        | Kiểu dữ liệu | Bắt buộc | Mô tả                |
|---------------|--------------|----------|----------------------|
| old_password  | string       | Có       | Mật khẩu hiện tại    |
| new_password  | string       | Có       | Mật khẩu mới         |
| new_password_confirmation | string | Có | Xác nhận mật khẩu mới|

**Response thành công:**
```json
{
  "success": true,
  "message": "Đổi mật khẩu thành công"
}
```

**Response lỗi (mật khẩu cũ sai):**
```json
{
  "success": false,
  "message": "Mật khẩu hiện tại không đúng"
}
```

**Response lỗi validate:**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "old_password": ["The old password field is required."],
    "new_password": ["The new password must be at least 6 characters."]
  }
}
```

## 6. Đăng xuất

**Endpoint:**
```
POST /api/user/logout
```

**Yêu cầu:**
- Header: `Authorization: Bearer <access_token>`

**Response thành công:**
```json
{
  "success": true,
  "message": "Đăng xuất thành công"
}
```

**Response lỗi (chưa đăng nhập):**
```json
{
  "message": "Unauthenticated."
}
```

## Ghi chú
- Tất cả response đều trả về chuẩn hóa qua Resource.
- access_token dùng để xác thực các API cần đăng nhập (gửi qua header: `Authorization: Bearer <access_token>`)
- address là object JSON gồm street, city, country, postal_code
- avatar là đường dẫn ảnh đại diện (nếu có)
- Các API khác (profile, update, đổi mật khẩu, logout) sẽ bổ sung tiếp sau. 