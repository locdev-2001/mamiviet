# Tài liệu API Quản lý Menu (Admin)

## 1. Lấy danh sách món ăn

**Endpoint:**
```
GET {{domain}}/admin/menu?menu_category_id=1&featured=true&discount=true&page=1&per_page=10
```

**Curl:**
```
curl --location --request GET '{{domain}}/admin/menu?menu_category_id=1&featured=true&discount=true&page=1&per_page=10' \
--header 'Authorization: Bearer {{access_token}}'
```

**Query Params:**
- `menu_category_id=1` (lọc theo category)
- `featured=true` (lọc món nổi bật)
- `discount=true` (lọc món giảm giá)
- `best_seller=true` (lọc món bán chạy)
- `name=beef` (tìm kiếm theo tên)
- `page`, `per_page` (phân trang)

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Beef Steak",
      "description": "Premium beef steak with sauce",
      "menu_category_id": 1,
      "category": {
        "id": 1,
        "name": "Main Course",
        "description": "Các món chính",
        "is_active": true,
        "position": 1,
        "created_at": "2025-07-22T08:00:00Z",
        "updated_at": "2025-07-22T08:00:00Z"
      },
      "price": 25.99,
      "discount_price": 20.99,
      "available": true,
      "is_featured": true,
      "order_count": 23,
      "ingredients": ["beef", "potato", "onion"],
      "images": [
        "/storage/1/image1.jpg",
        "/storage/1/image2.jpg"
      ]
    }
  ],
  "links": { ... },
  "meta": { ... }
}
```

---

## 2. Thêm món ăn mới

**Endpoint:**
```
POST {{domain}}/admin/menu
```

**Curl:**
```
curl --location --request POST '{{domain}}/admin/menu' \
--header 'Authorization: Bearer {{access_token}}' \
--form 'name="Beef Steak"' \
--form 'description="Premium beef steak with sauce"' \
--form 'menu_category_id=1' \
--form 'price=25.99' \
--form 'discount_price=20.99' \
--form 'available=true' \
--form 'is_featured=true' \
--form 'ingredients[]="beef"' \
--form 'ingredients[]="potato"' \
--form 'images[]=@"/path/to/image1.jpg"' \
--form 'images[]=@"/path/to/image2.jpg"'
```

**Body (multipart/form-data):**
- name: string, required
- description: string
- menu_category_id: integer, required
- price: number, required
- discount_price: number
- available: boolean
- is_featured: boolean
- ingredients[]: array (nhiều nguyên liệu)
- images[]: file (nhiều ảnh)

**Body (raw JSON, nếu dùng API test tool hỗ trợ):**
```json
{
  "name": "Beef Steak",
  "description": "Premium beef steak with sauce",
  "menu_category_id": 1,
  "price": 25.99,
  "discount_price": 20.99,
  "available": true,
  "is_featured": true,
  "ingredients": ["beef", "potato"]
}
```

**Response:**
```json
{
  "data": {
    "id": 1,
    "name": "Beef Steak",
    "description": "Premium beef steak with sauce",
    "menu_category_id": 1,
    "category": {
      "id": 1,
      "name": "Main Course",
      "description": "Các món chính",
      "is_active": true,
      "position": 1,
      "created_at": "2025-07-22T08:00:00Z",
      "updated_at": "2025-07-22T08:00:00Z"
    },
    "price": 25.99,
    "discount_price": 20.99,
    "available": true,
    "is_featured": true,
    "order_count": 0,
    "ingredients": ["beef", "potato"],
    "images": [
      "/storage/1/image1.jpg",
      "/storage/1/image2.jpg"
    ]
  }
}
```

---

## 3. Lấy chi tiết món ăn

**Endpoint:**
```
GET {{domain}}/admin/menu/{id}
```

**Curl:**
```
curl --location --request GET '{{domain}}/admin/menu/1' \
--header 'Authorization: Bearer {{access_token}}'
```

**Response:**
```json
{
  "data": {
    "id": 1,
    "name": "Beef Steak",
    "description": "Premium beef steak with sauce",
    "menu_category_id": 1,
    "category": {
      "id": 1,
      "name": "Main Course",
      "description": "Các món chính",
      "is_active": true,
      "position": 1,
      "created_at": "2025-07-22T08:00:00Z",
      "updated_at": "2025-07-22T08:00:00Z"
    },
    "price": 25.99,
    "discount_price": 20.99,
    "available": true,
    "is_featured": true,
    "order_count": 23,
    "ingredients": ["beef", "potato", "onion"],
    "images": [
      "/storage/1/image1.jpg",
      "/storage/1/image2.jpg"
    ]
  }
}
```

---

## 4. Cập nhật món ăn

**Endpoint:**
```
PUT {{domain}}/admin/menu/{id}
```

**Curl:**
```
curl --location --request PUT '{{domain}}/admin/menu/1' \
--header 'Authorization: Bearer {{access_token}}' \
--form 'name="Beef Steak Updated"' \
--form 'menu_category_id=2' \
--form 'price=30.00' \
--form 'ingredients[]="beef"' \
--form 'images[]=@"/path/to/newimage.jpg"'
```

**Body (multipart/form-data):**
- name: string
- description: string
- menu_category_id: integer
- price: number
- discount_price: number
- available: boolean
- is_featured: boolean
- ingredients[]: array
- images[]: file (nhiều ảnh, nếu gửi sẽ thay thế toàn bộ ảnh cũ)

**Body (raw JSON, nếu dùng API test tool hỗ trợ):**
```json
{
  "name": "Beef Steak Updated",
  "menu_category_id": 2,
  "price": 30.00,
  "ingredients": ["beef"]
}
```

**Response:**
```json
{
  "data": {
    "id": 1,
    "name": "Beef Steak Updated",
    "description": "Premium beef steak with sauce",
    "menu_category_id": 2,
    "category": {
      "id": 2,
      "name": "Appetizer",
      "description": "Món khai vị",
      "is_active": true,
      "position": 2,
      "created_at": "2025-07-22T08:00:00Z",
      "updated_at": "2025-07-22T08:00:00Z"
    },
    "price": 30.00,
    "discount_price": 20.99,
    "available": true,
    "is_featured": true,
    "order_count": 23,
    "ingredients": ["beef"],
    "images": [
      "/storage/1/newimage.jpg"
    ]
  }
}
```

---

## 5. Xóa món ăn

**Endpoint:**
```
DELETE {{domain}}/admin/menu/{id}
```

**Curl:**
```
curl --location --request DELETE '{{domain}}/admin/menu/1' \
--header 'Authorization: Bearer {{access_token}}'
```

**Response:**
```json
{
  "success": true
}
```

---

## Ghi chú
- Tất cả API đều cần header: `Authorization: Bearer {{access_token}}`
- Có thể filter theo menu_category_id, sort, phân trang danh sách
- Body gửi dạng raw JSON hoặc form-data, copy-paste trực tiếp vào Postman
- Response luôn chuẩn hóa qua Resource, trả về cả object category 