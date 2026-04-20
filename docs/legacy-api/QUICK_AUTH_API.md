# Quick Authentication API Documentation

## Tổng quan

API Quick Authentication cho phép đăng ký hoặc đăng nhập nhanh chỉ bằng email, với password mặc định là `123456`. Hệ thống sẽ tự động:
- Tạo tài khoản mới nếu email chưa tồn tại
- Đăng nhập trực tiếp nếu email đã có trong hệ thống

## Endpoints

### 1. Quick Auth (Đăng ký/Đăng nhập tự động)

**URL:** `POST /api/quick-auth`

**Mô tả:** API thông minh tự động xử lý cả đăng ký và đăng nhập. Nếu email chưa tồn tại sẽ tạo tài khoản mới, nếu đã tồn tại sẽ đăng nhập.

**Headers:**
```
Content-Type: application/json
Accept: application/json
Accept-Language: vi|en|de (optional)
```

**Request Body:**
```json
{
    "email": "user@example.com"
}
```

**Response Success (Tài khoản mới):**
```json
{
    "success": true,
    "message": "Tài khoản đã được tạo thành công với password mặc định: 123456",
    "data": {
        "id": 1,
        "name": "User",
        "email": "user@example.com",
        "phone": null,
        "address": null,
        "avatar": null,
        "spin_attempts": 3,
        "email_verified_at": null,
        "created_at": "2024-01-01T00:00:00.000000Z",
        "updated_at": "2024-01-01T00:00:00.000000Z",
        "access_token": "1|aBcDeFgHiJkLmNoPqRsTuVwXyZ...",
        "is_new_user": true,
        "default_password": "123456"
    }
}
```

**Response Success (Đăng nhập):**
```json
{
    "success": true,
    "message": "Đăng nhập thành công",
    "data": {
        "id": 1,
        "name": "User",
        "email": "user@example.com",
        "phone": null,
        "address": null,
        "avatar": null,
        "spin_attempts": 3,
        "email_verified_at": null,
        "created_at": "2024-01-01T00:00:00.000000Z",
        "updated_at": "2024-01-01T00:00:00.000000Z",
        "access_token": "2|XyZwVuTsRqPoNmLkJiHgFeDcBa...",
        "is_new_user": false,
        "default_password": "123456"
    }
}
```

**Response Error:**
```json
{
    "success": false,
    "message": "Có lỗi xảy ra trong quá trình xử lý",
    "error": "Error details (chỉ hiện khi debug mode)"
}
```

**Status Codes:**
- `201`: Tài khoản mới được tạo thành công
- `200`: Đăng nhập thành công
- `422`: Validation error
- `500`: Server error

---

### 2. Quick Login (Đăng nhập với password mặc định)

**URL:** `POST /api/quick-login`

**Mô tả:** Đăng nhập với email và password mặc định `123456`. Chỉ áp dụng cho tài khoản đã tồn tại.

**Headers:**
```
Content-Type: application/json
Accept: application/json
Accept-Language: vi|en|de (optional)
```

**Request Body:**
```json
{
    "email": "user@example.com"
}
```

**Response Success:**
```json
{
    "success": true,
    "message": "Đăng nhập thành công",
    "data": {
        "id": 1,
        "name": "User",
        "email": "user@example.com",
        "phone": null,
        "address": null,
        "avatar": null,
        "spin_attempts": 3,
        "email_verified_at": null,
        "created_at": "2024-01-01T00:00:00.000000Z",
        "updated_at": "2024-01-01T00:00:00.000000Z",
        "access_token": "3|FeDcBaZyXwVuTsRqPoNmLkJiHg...",
        "default_password": "123456"
    }
}
```

**Response Error:**
```json
{
    "success": false,
    "message": "Email không tồn tại hoặc password không đúng (password mặc định: 123456)"
}
```

**Status Codes:**
- `200`: Đăng nhập thành công
- `401`: Email không tồn tại hoặc password sai
- `422`: Validation error
- `500`: Server error

---

## Cách sử dụng

### JavaScript/TypeScript Example:

```javascript
// Quick Auth (Recommended)
const quickAuth = async (email) => {
    try {
        const response = await fetch('/api/quick-auth', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Accept-Language': 'vi'
            },
            body: JSON.stringify({ email })
        });

        const data = await response.json();

        if (data.success) {
            // Lưu token và thông tin user
            localStorage.setItem('access_token', data.data.access_token);
            localStorage.setItem('user', JSON.stringify(data.data));

            if (data.data.is_new_user) {
                console.log('Tài khoản mới được tạo!');
                // Hiển thị thông báo cho user về password mặc định
                alert('Tài khoản đã được tạo với password mặc định: 123456');
            }

            return data.data;
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Quick auth failed:', error);
        throw error;
    }
};

// Sử dụng
quickAuth('user@example.com')
    .then(user => {
        console.log('User logged in:', user);
        // Redirect hoặc cập nhật UI
    })
    .catch(error => {
        console.error('Error:', error);
    });
```

### Axios Example:

```javascript
import axios from 'axios';

const api = axios.create({
    baseURL: '/api',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Accept-Language': 'vi'
    }
});

const quickAuth = async (email) => {
    try {
        const response = await api.post('/quick-auth', { email });

        // Lưu token cho các request tiếp theo
        api.defaults.headers.common['Authorization'] = `Bearer ${response.data.data.access_token}`;

        return response.data;
    } catch (error) {
        throw error.response.data;
    }
};
```

### React Hook Example:

```javascript
import { useState } from 'react';

const useQuickAuth = () => {
    const [loading, setLoading] = useState(false);
    const [user, setUser] = useState(null);

    const quickAuth = async (email) => {
        setLoading(true);
        try {
            const response = await fetch('/api/quick-auth', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ email })
            });

            const data = await response.json();

            if (data.success) {
                setUser(data.data);
                localStorage.setItem('access_token', data.data.access_token);
                return data.data;
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            throw error;
        } finally {
            setLoading(false);
        }
    };

    return { quickAuth, loading, user };
};
```

---

## Lưu ý quan trọng

1. **Password mặc định:** Tất cả tài khoản được tạo qua API này sẽ có password mặc định là `123456`

2. **Token Authentication:** Sau khi đăng nhập thành công, sử dụng `access_token` trong header Authorization cho các request tiếp theo:
   ```
   Authorization: Bearer {access_token}
   ```

3. **User Data:** Response trả về đầy đủ thông tin user theo cấu trúc `UserResource`

4. **New User Detection:** Sử dụng field `is_new_user` để xác định tài khoản mới và hiển thị hướng dẫn phù hợp

5. **Error Handling:** Luôn kiểm tra field `success` trong response để xử lý lỗi đúng cách

6. **Spin Attempts:** Tài khoản mới được tạo với 3 lượt quay may mắn mặc định

7. **Multi-language:** API hỗ trợ đa ngôn ngữ thông qua header `Accept-Language`

---

## Test với cURL

```bash
# Quick Auth
curl -X POST http://your-domain.com/api/quick-auth \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"test@example.com"}'

# Quick Login
curl -X POST http://your-domain.com/api/quick-login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"test@example.com"}'
```