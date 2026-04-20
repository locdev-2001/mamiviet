# API Giỏ Hàng (Cart) - User

## 1. Lấy giỏ hàng hiện tại
**GET** `/api/user/cart`
- Yêu cầu: Đăng nhập (Bearer Token)

**Response mẫu:**
```json
{
  "data": {
    "id": 1,
    "user_id": 1,
    "items": [
      {
        "id": 10,
        "name": "omnis nulla",
        "price": 37.09,
        "quantity": 3,
        "images": [
          "https://domain.com/storage/menu/abc.jpg",
          "https://domain.com/storage/menu/xyz.jpg"
        ]
      }
    ],
    "total": 111.27
  }
}
```

---

## 2. Thêm món vào giỏ hàng
**POST** `/api/user/cart/add`
- Yêu cầu: Đăng nhập

**Body:**
```json
{
  "id": 10,
  "quantity": 2
}
```
- id: ID của món ăn (MenuItem)
- quantity: Số lượng muốn thêm

**Response:**
```json
{
  "data": {
    "id": 1,
    "user_id": 1,
    "items": [
      {
        "id": 10,
        "name": "omnis nulla",
        "price": 37.09,
        "quantity": 5,
        "images": [
          "https://domain.com/storage/menu/abc.jpg"
        ]
      }
    ],
    "total": 185.45
  }
}
```
> Nếu đã có item trong cart, quantity sẽ được cộng dồn.

---

## 3. Cập nhật số lượng món trong giỏ
**PUT** `/api/user/cart/update`
**Body:**
```json
{
  "id": 10,
  "quantity": 1
}
```
**Response:** (giống như trên, quantity sẽ được cập nhật đúng)

---

## 4. Xóa một món khỏi giỏ hàng
**DELETE** `/api/user/cart/remove`
**Body:**
```json
{
  "id": 10
}
```
**Response:** (giỏ hàng sau khi xóa item)

---

## 5. Xóa toàn bộ giỏ hàng
**DELETE** `/api/user/cart/clear`
**Response:** (giỏ hàng rỗng)

---

## 6. Merge cart khi đăng nhập
**POST** `/api/user/cart/merge`
**Body:**
```json
{
  "items": [
    { "id": 10, "quantity": 2 },
    { "id": 11, "quantity": 1 }
  ]
}
```
**Response:** (giỏ hàng sau khi merge, quantity sẽ cộng dồn nếu trùng id)

---

### Lưu ý:
- Tất cả API đều yêu cầu user đã đăng nhập (Bearer Token).
- Chỉ truyền id và quantity, không truyền name, price.
- Các trường name, price, images luôn lấy từ DB (MenuItem).
- Nếu add nhiều lần cùng 1 món, quantity sẽ cộng dồn.
- Mọi response đều trả về chuẩn hóa qua Resource. 