# Tài liệu API Hệ thống Web Nhà hàng

## I. Phân tích tính năng Web Nhà hàng

### 1. Giao diện & Trải nghiệm người dùng
- Giao diện trực quan với nhiều hình ảnh hấp dẫn
- Hệ thống đặt món, giao hàng, đặt bàn dễ sử dụng
- Hiển thị vòng quay may mắn
- Tích hợp social media (Instagram, TikTok)

### 2. Danh sách tính năng chính

#### **A. Tính năng đặt hàng & đặt bàn**
- Đặt món ăn trực tuyến
- Đặt giao hàng
- Đặt bàn trước
- Quản lý giỏ hàng

#### **B. Hệ thống khuyến mãi**
- Vòng quay may mắn (dựa trên hóa đơn)
- Chương trình Happy Hour (17h-18h)
- Áp dụng mã giảm giá/coupon
- Tích hợp ưu đãi 10% từ khách sạn

#### **C. Quản lý nội dung**
- Blog & tin tức
- Meet our manager
- Video quảng cáo (6 phút)
- Tích hợp Instagram, TikTok

#### **D. Tính năng marketing**
- Mã QR trên Instagram
- SEO tối ưu
- Tích hợp Google Ads, Facebook Ads

---

## II. API Documentation

### Base URL
```
Production: https://api.restaurant.com/v1
Development: https://dev-api.restaurant.com/v1
```

### Authentication
```json
{
  "Authorization": "Bearer <access_token>",
  "Content-Type": "application/json"
}
```

---

## III. USER APIs

### 1. Authentication & User Management

#### 1.1 Đăng ký khách hàng
```http
POST /auth/register
```

**Request Body:**
```json
{
  "name": "Nguyen Van A",
  "email": "user@example.com",
  "password": "password123",
  "phone": "+84901234567",
  "address": {
    "street": "123 Main St",
    "city": "Ho Chi Minh",
    "country": "Vietnam",
    "postal_code": "700000"
  }
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "user_id": "usr_123",
    "access_token": "jwt_token_here",
    "refresh_token": "refresh_token_here",
    "expires_in": 3600
  }
}
```

#### 1.2 Đăng nhập
```http
POST /auth/login
```

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

#### 1.3 Lấy thông tin profile
```http
GET /user/profile
```

**Response:**
```json
{
  "success": true,
  "data": {
    "user_id": "usr_123",
    "name": "Nguyen Van A",
    "email": "user@example.com",
    "phone": "+84901234567",
    "loyalty_points": 150,
    "lucky_wheel_spins": 2,
    "created_at": "2025-01-15T10:30:00Z"
  }
}
```

### 2. Menu & Food APIs

#### 2.1 Lấy danh sách menu
```http
GET /menu?category=appetizer&available=true&page=1&limit=20
```

**Response:**
```json
{
  "success": true,
  "data": {
    "items": [
      {
        "item_id": "item_123",
        "name": "Beef Steak",
        "description": "Premium beef steak with sauce",
        "category": "main_course",
        "price": 25.99,
        "images": ["image1.jpg", "image2.jpg"],
        "available": true,
        "preparation_time": 15,
        "allergens": ["gluten"],
        "nutrition_info": {
          "calories": 450,
          "protein": 35,
          "carbs": 10,
          "fat": 28
        }
      }
    ],
    "pagination": {
      "current_page": 1,
      "total_pages": 5,
      "total_items": 95
    }
  }
}
```

#### 2.2 Lấy chi tiết món ăn
```http
GET /menu/{item_id}
```

#### 2.3 Tìm kiếm món ăn
```http
GET /menu/search?q=beef&category=main_course
```

### 3. Cart & Order APIs

#### 3.1 Thêm vào giỏ hàng
```http
POST /cart/add
```

**Request Body:**
```json
{
  "item_id": "item_123",
  "quantity": 2,
  "customizations": {
    "spice_level": "medium",
    "notes": "No onions please"
  }
}
```

#### 3.2 Lấy giỏ hàng
```http
GET /cart
```

**Response:**
```json
{
  "success": true,
  "data": {
    "cart_id": "cart_456",
    "items": [
      {
        "item_id": "item_123",
        "name": "Beef Steak",
        "quantity": 2,
        "unit_price": 25.99,
        "total_price": 51.98,
        "customizations": {
          "spice_level": "medium",
          "notes": "No onions please"
        }
      }
    ],
    "subtotal": 51.98,
    "tax": 5.20,
    "delivery_fee": 3.00,
    "total": 60.18,
    "applied_coupons": []
  }
}
```

#### 3.3 Áp dụng coupon
```http
POST /cart/apply-coupon
```

**Request Body:**
```json
{
  "coupon_code": "HAPPY10"
}
```

#### 3.4 Tạo đơn hàng
```http
POST /orders
```

**Request Body:**
```json
{
  "order_type": "delivery", // "delivery", "pickup", "dine_in"
  "delivery_address": {
    "street": "123 Main St",
    "city": "Ho Chi Minh",
    "country": "Vietnam",
    "postal_code": "700000",
    "notes": "2nd floor, ring bell"
  },
  "payment_method": "card", // "card", "cash", "wallet"
  "special_instructions": "Please call when arrived",
  "scheduled_time": "2025-07-22T19:30:00Z" // null for ASAP
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "order_id": "ord_789",
    "order_number": "R2025-001234",
    "status": "confirmed",
    "estimated_delivery": "2025-07-22T19:45:00Z",
    "total_amount": 60.18,
    "payment_status": "paid",
    "lucky_wheel_spins_earned": 2
  }
}
```

#### 3.5 Lấy lịch sử đơn hàng
```http
GET /orders?page=1&limit=10&status=completed
```

#### 3.6 Lấy chi tiết đơn hàng
```http
GET /orders/{order_id}
```

#### 3.7 Hủy đơn hàng
```http
POST /orders/{order_id}/cancel
```

### 4. Table Booking APIs

#### 4.1 Kiểm tra bàn trống
```http
GET /tables/availability?date=2025-07-22&time=19:30&party_size=4
```

**Response:**
```json
{
  "success": true,
  "data": {
    "available_slots": [
      {
        "time": "19:30",
        "available_tables": 3,
        "table_types": ["standard", "window", "private"]
      },
      {
        "time": "20:00",
        "available_tables": 2,
        "table_types": ["standard", "private"]
      }
    ]
  }
}
```

#### 4.2 Đặt bàn
```http
POST /bookings
```

**Request Body:**
```json
{
  "date": "2025-07-22",
  "time": "19:30",
  "party_size": 4,
  "table_preference": "window",
  "special_requests": "Birthday celebration",
  "contact_phone": "+84901234567"
}
```

#### 4.3 Lấy danh sách đặt bàn
```http
GET /bookings
```

#### 4.4 Hủy đặt bàn
```http
POST /bookings/{booking_id}/cancel
```

### 5. Lucky Wheel APIs

#### 5.1 Lấy thông tin vòng quay
```http
GET /lucky-wheel/info
```

**Response:**
```json
{
  "success": true,
  "data": {
    "available_spins": 2,
    "spin_rules": {
      "min_order_amount": 30.00,
      "spins_per_30_euro": 1,
      "spins_per_50_euro": 2
    },
    "prizes": [
      {
        "prize_id": "prize_1",
        "name": "10% Discount",
        "probability": 0.3,
        "value": "10% off next order"
      },
      {
        "prize_id": "prize_2",
        "name": "Free Dessert",
        "probability": 0.2,
        "value": "Free dessert with next order"
      }
    ]
  }
}
```

#### 5.2 Quay vòng may mắn
```http
POST /lucky-wheel/spin
```

**Response:**
```json
{
  "success": true,
  "data": {
    "spin_id": "spin_123",
    "prize_won": {
      "prize_id": "prize_1",
      "name": "10% Discount",
      "value": "10% off next order",
      "coupon_code": "LUCKY10-ABC123",
      "expires_at": "2025-08-22T23:59:59Z"
    },
    "remaining_spins": 1
  }
}
```

#### 5.3 Lịch sử quay
```http
GET /lucky-wheel/history
```

### 6. Promotions & Coupons APIs

#### 6.1 Lấy danh sách khuyến mãi
```http
GET /promotions?active=true
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "promotion_id": "promo_123",
      "name": "Happy Hour",
      "description": "20% off all drinks from 5-6 PM",
      "type": "happy_hour",
      "discount_percentage": 20,
      "valid_from": "2025-07-22T17:00:00Z",
      "valid_to": "2025-07-22T18:00:00Z",
      "applicable_categories": ["drinks"],
      "active": true
    }
  ]
}
```

#### 6.2 Kiểm tra coupon hợp lệ
```http
GET /coupons/validate/{coupon_code}
```

#### 6.3 Lấy coupon của user
```http
GET /user/coupons?status=active
```

### 7. Content & Social Media APIs

#### 7.1 Lấy blog posts
```http
GET /content/blog?page=1&limit=10
```

#### 7.2 Lấy tin tức mới nhất
```http
GET /content/news?limit=5
```

#### 7.3 Lấy thông tin manager
```http
GET /content/managers
```

#### 7.4 Lấy social media content
```http
GET /social/instagram?limit=10
```

#### 7.5 Lấy video quảng cáo
```http
GET /content/promotional-video
```

---

## IV. ADMIN APIs

### 1. Admin Authentication

#### 1.1 Admin Login
```http
POST /admin/auth/login
```

**Request Body:**
```json
{
  "username": "admin@restaurant.com",
  "password": "admin_password",
  "role": "admin" // "admin", "manager", "staff"
}
```

### 2. Menu Management APIs

#### 2.1 Tạo món ăn mới
```http
POST /admin/menu
```

**Request Body:**
```json
{
  "name": "New Dish",
  "description": "Description of the dish",
  "category": "main_course",
  "price": 29.99,
  "preparation_time": 20,
  "available": true,
  "ingredients": ["beef", "potato", "onion"],
  "allergens": ["gluten"],
  "nutrition_info": {
    "calories": 500,
    "protein": 30,
    "carbs": 20,
    "fat": 25
  }
}
```

#### 2.2 Cập nhật món ăn
```http
PUT /admin/menu/{item_id}
```

#### 2.3 Xóa món ăn
```http
DELETE /admin/menu/{item_id}
```

#### 2.4 Lấy danh sách menu (admin)
```http
GET /admin/menu?page=1&limit=20&category=all&status=all
```

### 3. Order Management APIs

#### 3.1 Lấy danh sách đơn hàng
```http
GET /admin/orders?date=2025-07-22&status=pending&page=1&limit=20
```

#### 3.2 Cập nhật trạng thái đơn hàng
```http
PUT /admin/orders/{order_id}/status
```

**Request Body:**
```json
{
  "status": "preparing", // "confirmed", "preparing", "ready", "out_for_delivery", "delivered", "cancelled"
  "estimated_completion": "2025-07-22T19:45:00Z",
  "notes": "Extra spicy as requested"
}
```

#### 3.3 Lấy thống kê đơn hàng
```http
GET /admin/orders/stats?period=today
```

**Response:**
```json
{
  "success": true,
  "data": {
    "total_orders": 145,
    "completed_orders": 132,
    "pending_orders": 8,
    "cancelled_orders": 5,
    "total_revenue": 3250.75,
    "average_order_value": 24.64,
    "top_dishes": [
      {
        "item_id": "item_123",
        "name": "Beef Steak",
        "orders_count": 23
      }
    ]
  }
}
```

### 4. Table & Booking Management APIs

#### 4.1 Quản lý bàn
```http
GET /admin/tables
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "table_id": "table_01",
      "table_number": "T01",
      "capacity": 4,
      "type": "standard", // "standard", "window", "private", "outdoor"
      "status": "available", // "available", "occupied", "reserved", "maintenance"
      "location": "main_hall"
    }
  ]
}
```

#### 4.2 Cập nhật trạng thái bàn
```http
PUT /admin/tables/{table_id}/status
```

#### 4.3 Quản lý đặt bàn
```http
GET /admin/bookings?date=2025-07-22&status=confirmed
```

#### 4.4 Xác nhận đặt bàn
```http
PUT /admin/bookings/{booking_id}/confirm
```

### 5. Promotion & Coupon Management APIs

#### 5.1 Tạo khuyến mãi
```http
POST /admin/promotions
```

**Request Body:**
```json
{
  "name": "Weekend Special",
  "description": "20% off all main courses on weekends",
  "type": "percentage", // "percentage", "fixed_amount", "buy_one_get_one"
  "discount_value": 20,
  "valid_from": "2025-07-26T00:00:00Z",
  "valid_to": "2025-07-27T23:59:59Z",
  "applicable_categories": ["main_course"],
  "minimum_order_amount": 50.00,
  "max_uses": 100,
  "active": true
}
```

#### 5.2 Tạo coupon
```http
POST /admin/coupons
```

**Request Body:**
```json
{
  "code": "SUMMER25",
  "type": "percentage",
  "value": 25,
  "description": "25% off summer special",
  "valid_from": "2025-07-01T00:00:00Z",
  "valid_to": "2025-08-31T23:59:59Z",
  "usage_limit": 200,
  "minimum_order_amount": 30.00,
  "active": true
}
```

#### 5.3 Thống kê sử dụng coupon
```http
GET /admin/coupons/{coupon_id}/stats
```

### 6. Lucky Wheel Management APIs

#### 6.1 Cấu hình vòng quay
```http
PUT /admin/lucky-wheel/config
```

**Request Body:**
```json
{
  "spin_rules": {
    "min_order_amount": 30.00,
    "spins_per_30_euro": 1,
    "spins_per_50_euro": 2
  },
  "prizes": [
    {
      "name": "10% Discount",
      "probability": 0.3,
      "prize_type": "coupon",
      "value": "10% off next order"
    }
  ]
}
```

#### 6.2 Lịch sử quay (admin)
```http
GET /admin/lucky-wheel/spins?date=2025-07-22&page=1&limit=20
```

#### 6.3 Thống kê vòng quay
```http
GET /admin/lucky-wheel/stats?period=last_7_days
```

### 7. Customer Management APIs

#### 7.1 Lấy danh sách khách hàng
```http
GET /admin/customers?page=1&limit=20&search=john
```

#### 7.2 Lấy chi tiết khách hàng
```http
GET /admin/customers/{user_id}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "user_id": "usr_123",
    "name": "Nguyen Van A",
    "email": "user@example.com",
    "phone": "+84901234567",
    "registration_date": "2025-01-15T10:30:00Z",
    "total_orders": 15,
    "total_spent": 450.75,
    "loyalty_points": 150,
    "favorite_dishes": ["Beef Steak", "Pasta"],
    "order_frequency": "weekly",
    "last_order_date": "2025-07-20T18:30:00Z"
  }
}
```

#### 7.3 Cập nhật loyalty points
```http
PUT /admin/customers/{user_id}/loyalty-points
```

### 8. Content Management APIs

#### 8.1 Quản lý blog
```http
POST /admin/content/blog
```

**Request Body:**
```json
{
  "title": "New Recipe: Summer Salad",
  "content": "Here's how to make our famous summer salad...",
  "author": "Chef Maria",
  "featured_image": "salad.jpg",
  "tags": ["recipe", "salad", "healthy"],
  "published": true,
  "publish_date": "2025-07-22T10:00:00Z"
}
```

#### 8.2 Quản lý tin tức
```http
POST /admin/content/news
```

#### 8.3 Cập nhật thông tin manager
```http
PUT /admin/content/managers/{manager_id}
```

#### 8.4 Upload promotional video
```http
POST /admin/content/promotional-video
```

### 9. Analytics & Reports APIs

#### 9.1 Dashboard tổng quan
```http
GET /admin/analytics/dashboard?period=today
```

**Response:**
```json
{
  "success": true,
  "data": {
    "today_orders": 145,
    "today_revenue": 3250.75,
    "active_customers": 89,
    "table_occupancy": 0.75,
    "popular_items": [
      {
        "name": "Beef Steak",
        "orders_today": 23
      }
    ],
    "hourly_orders": [
      {"hour": "12:00", "orders": 15},
      {"hour": "13:00", "orders": 22}
    ]
  }
}
```

#### 9.2 Báo cáo doanh thu
```http
GET /admin/reports/revenue?from=2025-07-01&to=2025-07-31&group_by=day
```

#### 9.3 Báo cáo món ăn phổ biến
```http
GET /admin/reports/popular-dishes?period=last_30_days
```

#### 9.4 Báo cáo khách hàng
```http
GET /admin/reports/customers?period=last_30_days
```

### 10. System Configuration APIs

#### 10.1 Cấu hình hệ thống
```http
GET /admin/settings
```

#### 10.2 Cập nhật cấu hình
```http
PUT /admin/settings
```

**Request Body:**
```json
{
  "restaurant_info": {
    "name": "Delicious Restaurant",
    "address": "123 Food Street",
    "phone": "+84901234567",
    "email": "info@restaurant.com",
    "opening_hours": {
      "monday": {"open": "10:00", "close": "22:00"},
      "tuesday": {"open": "10:00", "close": "22:00"}
    }
  },
  "delivery_settings": {
    "delivery_fee": 3.00,
    "free_delivery_threshold": 50.00,
    "delivery_radius": 10,
    "estimated_delivery_time": 30
  },
  "payment_settings": {
    "accepted_methods": ["card", "cash", "wallet"],
    "tax_rate": 0.1
  }
}
```

---

## V. Webhook Events

### 1. Order Events
```json
{
  "event": "order.created",
  "data": {
    "order_id": "ord_789",
    "customer_id": "usr_123",
    "total_amount": 60.18,
    "timestamp": "2025-07-22T19:30:00Z"
  }
}
```

### 2. Payment Events
```json
{
  "event": "payment.completed",
  "data": {
    "order_id": "ord_789",
    "payment_id": "pay_456",
    "amount": 60.18,
    "method": "card",
    "timestamp": "2025-07-22T19:30:00Z"
  }
}
```

---

## VI. Error Handling

### Standard Error Response
```json
{
  "success": false,
  "error": {
    "code": "INVALID_REQUEST",
    "message": "The request parameters are invalid",
    "details": {
      "field": "email",
      "issue": "Email format is invalid"
    }
  },
  "timestamp": "2025-07-22T19:30:00Z"
}
```

### Common Error Codes
- `UNAUTHORIZED` - 401: Không có quyền truy cập
- `FORBIDDEN` - 403: Bị cấm truy cập
- `NOT_FOUND` - 404: Không tìm thấy resource
- `VALIDATION_ERROR` - 422: Lỗi validation
- `RATE_LIMIT_EXCEEDED` - 429: Vượt quá giới hạn request
- `INTERNAL_ERROR` - 500: Lỗi hệ thống

---

## VII. Rate Limiting

### User APIs
- 100 requests per minute per IP
- 1000 requests per hour per authenticated user

### Admin APIs
- 200 requests per minute per admin
- No hourly limit for admin users

### Special Endpoints
- Lucky Wheel Spin: 10 spins per hour per user
- Order Creation: 5 orders per hour per user