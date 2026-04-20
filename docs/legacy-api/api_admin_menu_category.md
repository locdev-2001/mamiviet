# Tài liệu API Quản lý Menu Category (Admin)

## 1. Lấy danh sách category

**Endpoint:**
```
GET {{domain}}/admin/menu-categories?is_active=1&name=main&page=1&per_page=10
```

**Curl:**
```
curl --location --request GET '{{domain}}/admin/menu-categories?is_active=1&page=1&per_page=10' \
--header 'Authorization: Bearer {{access_token}}'
```

**Query Params:**
- `is_active=1` (lọc category đang active)
- `name=main` (tìm kiếm theo tên)
- `page`, `per_page` (phân trang)

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Main Course",
      "description": "Các món chính",
      "is_active": true,
      "position": 1,
      "created_at": "2025-07-22T08:00:00Z",
      "updated_at": "2025-07-22T08:00:00Z"
    }
  ],
  "links": { ... },
  "meta": { ... }
}
```

---

## 2. Tạo mới category

**Endpoint:**
```
POST {{domain}}/admin/menu-categories
```

**Curl:**
```
curl --location --request POST '{{domain}}/admin/menu-categories' \
--header 'Authorization: Bearer {{access_token}}' \
--header 'Content-Type: application/json' \
--data-raw '{
  "name": "Main Course",
  "description": "Các món chính",
  "is_active": true,
  "position": 1
}'
```

**Body (raw JSON):**
```json
{
  "name": "Main Course",
  "description": "Các món chính",
  "is_active": true,
  "position": 1
}
```

**Response:**
```json
{
  "data": {
    "id": 1,
    "name": "Main Course",
    "description": "Các món chính",
    "is_active": true,
    "position": 1,
    "created_at": "2025-07-22T08:00:00Z",
    "updated_at": "2025-07-22T08:00:00Z"
  }
}
```

---

## 3. Lấy chi tiết category

**Endpoint:**
```
GET {{domain}}/admin/menu-categories/{id}
```

**Curl:**
```
curl --location --request GET '{{domain}}/admin/menu-categories/1' \
--header 'Authorization: Bearer {{access_token}}'
```

**Response:**
```json
{
  "data": {
    "id": 1,
    "name": "Main Course",
    "description": "Các món chính",
    "is_active": true,
    "position": 1,
    "created_at": "2025-07-22T08:00:00Z",
    "updated_at": "2025-07-22T08:00:00Z"
  }
}
```

---

## 4. Cập nhật category

**Endpoint:**
```
PUT {{domain}}/admin/menu-categories/{id}
```

**Curl:**
```
curl --location --request PUT '{{domain}}/admin/menu-categories/1' \
--header 'Authorization: Bearer {{access_token}}' \
--header 'Content-Type: application/json' \
--data-raw '{
  "name": "Món chính",
  "description": "Món chính nhà hàng",
  "is_active": true,
  "position": 2
}'
```

**Body (raw JSON):**
```json
{
  "name": "Món chính",
  "description": "Món chính nhà hàng",
  "is_active": true,
  "position": 2
}
```

**Response:**
```json
{
  "data": {
    "id": 1,
    "name": "Món chính",
    "description": "Món chính nhà hàng",
    "is_active": true,
    "position": 2,
    "created_at": "2025-07-22T08:00:00Z",
    "updated_at": "2025-07-22T08:10:00Z"
  }
}
```

---

## 5. Xóa category

**Endpoint:**
```
DELETE {{domain}}/admin/menu-categories/{id}
```

**Curl:**
```
curl --location --request DELETE '{{domain}}/admin/menu-categories/1' \
--header 'Authorization: Bearer {{access_token}}'
```

**Response:**
```json
{
  "success": true
}
```

---

## 6. Đổi vị trí 1 category

**Endpoint:**
```
PUT {{domain}}/admin/menu-categories/{id}/position
```

**Curl:**
```
curl --location --request PUT '{{domain}}/admin/menu-categories/1/position' \
--header 'Authorization: Bearer {{access_token}}' \
--header 'Content-Type: application/json' \
--data-raw '{
  "position": 3
}'
```

**Body (raw JSON):**
```json
{
  "position": 3
}
```

**Response:**
```json
{
  "data": {
    "id": 1,
    "name": "Món chính",
    "description": "Món chính nhà hàng",
    "is_active": true,
    "position": 3,
    "created_at": "2025-07-22T08:00:00Z",
    "updated_at": "2025-07-22T08:15:00Z"
  }
}
```

---

## 7. Đổi vị trí hàng loạt (bulk sort)

**Endpoint:**
```
PUT {{domain}}/admin/menu-categories/sort
```

**Curl:**
```
curl --location --request PUT '{{domain}}/admin/menu-categories/sort' \
--header 'Authorization: Bearer {{access_token}}' \
--header 'Content-Type: application/json' \
--data-raw '[
  { "id": 1, "position": 1 },
  { "id": 2, "position": 2 },
  { "id": 3, "position": 3 }
]'
```

**Body (raw JSON):**
```json
[
  { "id": 1, "position": 1 },
  { "id": 2, "position": 2 },
  { "id": 3, "position": 3 }
]
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
- Body gửi dạng raw JSON, copy-paste trực tiếp vào Postman
- Có thể filter, sort, phân trang danh sách
- Response luôn chuẩn hóa qua Resource 