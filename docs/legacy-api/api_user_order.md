# API Đặt hàng (Order) - User

## 1. Tạo đơn hàng mới

**Endpoint:** `POST /api/user/orders`

**Yêu cầu đăng nhập:** Không bắt buộc (guest cũng đặt được)

**Payload mẫu:**
```json
{
  "type": "delivery",
  "name": "Nguyễn Văn A",
  "phone": "0987654321",
  "email": "a@gmail.com",
  "address": "123 Đường ABC, Quận 1, TP.HCM",
  "note": "Giao giờ hành chính",
  "discount_code": "SUMMER2024",
  "items": [
    { "menu_item_id": 1, "quantity": 2 },
    { "menu_item_id": 5, "quantity": 1 }
  ]
}
```
> Nếu `type` là `onsite` thì có thể bỏ trường `address`.

**Response mẫu:**
```json
{
  "data": {
    "id": 123,
    "order_code": "MV20240601ABC",
    "status": "pending",
    "type": "delivery",
    "name": "Nguyễn Văn A",
    "phone": "0987654321",
    "email": "a@gmail.com",
    "address": "123 Đường ABC, Quận 1, TP.HCM",
    "note": "Giao giờ hành chính",
    "discount_code": "SUMMER2024",
    "discount_amount": 50000,
    "total_amount": 300000,
    "items": [
      {
        "menu_item_id": 1,
        "name": "Cơm gà",
        "quantity": 2,
        "price": 100000,
        "total": 200000,
        "image": "https://domain.com/storage/menu_items/1.jpg"
      },
      {
        "menu_item_id": 5,
        "name": "Nước cam",
        "quantity": 1,
        "price": 150000,
        "total": 150000,
        "image": "https://domain.com/storage/menu_items/5.jpg"
      }
    ],
    "created_at": "2024-06-01 10:00:00",
    "cancelled_at": null
  }
}
```

---

## 2. Lấy danh sách đơn hàng của user (lịch sử)

**Endpoint:** `GET /api/user/orders`

**Yêu cầu đăng nhập:** Bắt buộc (Bearer Token)

**Cách test:**
- Method: `GET`
- Header: `Authorization: Bearer <token>`
- Không cần body

**Response mẫu:**
```json
{
  "data": [
    {
      "id": 123,
      "order_code": "MV20240601ABC",
      "status": "pending",
      "type": "delivery",
      "name": "Nguyễn Văn A",
      "phone": "0987654321",
      "email": "a@gmail.com",
      "address": "123 Đường ABC, Quận 1, TP.HCM",
      "note": "Giao giờ hành chính",
      "discount_code": "SUMMER2024",
      "discount_amount": 50000,
      "total_amount": 300000,
      "items": [ ... ],
      "created_at": "2024-06-01 10:00:00",
      "cancelled_at": null
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 10,
    "total": 2
  }
}
```

---

## 3. Xem chi tiết đơn hàng

**Endpoint:** `GET /api/user/orders/{order_code}`

**Yêu cầu đăng nhập:** Không bắt buộc

**Cách test:**
- Method: `GET`
- Thay `{order_code}` bằng mã đơn thực tế
- Không cần body

**Response:**
Giống response của tạo đơn hàng.

---

## 4. Cập nhật thông tin người nhận/địa chỉ

**Endpoint:** `PUT /api/user/orders/{order_code}`

**Yêu cầu đăng nhập:** Không bắt buộc

**Payload mẫu:**
```json
{
  "type": "onsite",
  "name": "Nguyễn Văn B",
  "phone": "0912345678",
  "email": "b@gmail.com",
  "note": "Ăn tại quán, bàn số 5"
}
```
> Chỉ truyền các trường muốn cập nhật. Nếu `type` là `delivery` thì cần truyền `address`.

**Response:**
Giống response của tạo đơn hàng.

---

## 5. Hủy đơn hàng

**Endpoint:** `POST /api/user/orders/{order_code}/cancel`

**Yêu cầu đăng nhập:** Không bắt buộc

**Cách test:**
- Method: `POST`
- Thay `{order_code}` bằng mã đơn thực tế
- Không cần body

**Response:**
Giống response của tạo đơn hàng, với `status: "cancelled"` và có `cancelled_at`.

---

## Lưu ý chung
- Tất cả response đều chuẩn hóa theo Laravel Resource.
- Nếu type là `onsite` thì không cần truyền address.
- Lịch sử đơn hàng chỉ xem được khi đăng nhập.
- Các API khác chỉ cần biết mã đơn hàng (`order_code`) là thao tác được.

---

## Hướng dẫn test với Postman
1. Chọn method đúng với từng API (`POST`, `GET`, `PUT`).
2. URL: `http://localhost:8000/api/user/orders` (hoặc domain thực tế)
3. Header:
   - Nếu cần đăng nhập: `Authorization: Bearer <token>`
   - Content-Type: `application/json`
4. Body: Chọn `raw` và định dạng `JSON`, dán payload mẫu vào. 