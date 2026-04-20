# 📋 Order & Coupon API Documentation

## Base URL
```
https://your-domain.com/api/user
```

## Authentication
All endpoints require Bearer token authentication.

```javascript
// Headers
{
  'Authorization': 'Bearer ' + token,
  'Accept': 'application/json',
  'Content-Type': 'application/json'
}
```

---

## 🎫 Coupon API Endpoints

### 1. Get User Coupons List
**Endpoint:** `GET /api/user/coupons`
**Purpose:** Lấy danh sách tất cả coupon của user

#### Request
```javascript
fetch('/api/user/coupons', {
  method: 'GET',
  headers: {
    'Authorization': 'Bearer ' + token,
    'Accept': 'application/json'
  }
})
```

#### Success Response (200)
```json
{
  "data": [
    {
      "id": 1,
      "coupon_id": 5,
      "quantity": 2,
      "is_used": false,
      "assigned_at": "2024-01-15T10:30:00.000000Z",
      "used_at": null,
      "coupon": {
        "id": 5,
        "name": "10% Discount",
        "code": "SAVE10",
        "type": "percentage",
        "value": 10.00,
        "description": "Get 10% off your order",
        "min_order_amount": 50.00,
        "max_discount_amount": 20.00,
        "expires_at": "2024-12-31T23:59:59.000000Z",
        "is_active": true
      }
    }
  ]
}
```

#### Empty Response (200)
```json
{
  "message": "Bạn hiện chưa có phiếu giảm giá nào!",
  "data": []
}
```

---

### 2. Get Coupon Details for Calculation  
**Endpoint:** `GET /api/user/coupons/{code}`
**Purpose:** Lấy chi tiết 1 coupon cụ thể để frontend tính toán discount

#### Request
```javascript
fetch('/api/user/coupons/SAVE10', {
  method: 'GET',
  headers: {
    'Authorization': 'Bearer ' + token,
    'Accept': 'application/json'
  }
})
```

#### Success Response (200)
```json
{
  "success": true,
  "message": "Coupon details retrieved successfully",
  "data": {
    "id": 5,
    "code": "SAVE10",
    "name": "10% Discount",
    "type": "percentage",
    "value": 10.0,
    "max_uses": 100,
    "quantity_available": 2,
    "user_coupon_id": 1,
    "created_at": "2024-01-01T00:00:00.000Z",
    "updated_at": "2024-01-15T10:30:00.000Z"
  }
}
```

#### Error Responses
**Coupon Not Found (200)**
```json
{
  "success": false,
  "message": "Coupon not found",
  "data": null
}
```

**User Doesn't Have Coupon (200)**
```json
{
  "success": false,
  "message": "You do not have this coupon or it has been used up",
  "data": null
}
```

---

### 3. Validate Coupon
**Endpoint:** `POST /api/user/coupons/validate`
**Purpose:** Validate coupon trước khi apply (không trừ lượt sử dụng)

#### Request
```javascript
fetch('/api/user/coupons/validate', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer ' + token,
    'Accept': 'application/json',
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    code: 'SAVE10',
    order_total: 100.00  // optional
  })
})
```

#### Request Body
```json
{
  "code": "SAVE10",
  "order_total": 100.00
}
```

#### Success Response (200)
```json
{
  "success": true,
  "message": "Coupon is valid",
  "data": {
    "id": 5,
    "code": "SAVE10",
    "name": "10% Discount",
    "type": "percentage",
    "value": 10.0,
    "max_uses": 100,
    "quantity_available": 2,
    "user_coupon_id": 1
  }
}
```

#### Error Responses (422)
**Invalid Coupon**
```json
{
  "success": false,
  "message": "Coupon not found",
  "errors": {
    "code": "Coupon code does not exist"
  }
}
```

**User Doesn't Have Coupon**
```json
{
  "success": false,
  "message": "You do not have this coupon or it has been used up",
  "errors": {
    "code": "You do not have this coupon or it has been used up"
  }
}
```

---

## 📦 Order API Endpoints

### 1. Create Order
**Endpoint:** `POST /api/user/orders/store`
**Purpose:** Tạo đơn hàng mới với discount đã tính từ frontend

#### Request
```javascript
fetch('/api/user/orders/store', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer ' + token,
    'Accept': 'application/json',
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    type: 'delivery',
    name: 'John Doe',
    phone: '+1234567890',
    email: 'john@example.com',
    address: '123 Main St, City',
    note: 'Please ring the bell',
    discount_code: 'SAVE10',        // từ frontend
    discount_amount: 10.00,         // từ frontend  
    total_amount: 90.00,           // từ frontend (đã trừ discount)
    payment_method: 'cash',
    items: [
      {
        menu_item_id: 1,
        quantity: 2,
        price: 25.00
      },
      {
        menu_item_id: 3,
        quantity: 1,
        price: 50.00
      }
    ]
  })
})
```

#### Request Body Fields
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `type` | string | ✅ | `delivery` or `onsite` |
| `name` | string | ✅ | Customer name |
| `phone` | string | ✅ | Customer phone |
| `email` | string | ❌ | Customer email |
| `address` | string | ✅* | Required if type=delivery |
| `note` | string | ❌ | Order notes |
| `discount_code` | string | ❌ | Coupon code used |
| `discount_amount` | number | ❌ | Discount amount (calculated by frontend) |
| `total_amount` | number | ✅ | Final total (after discount) |
| `payment_method` | string | ❌ | Payment method |
| `items` | array | ✅ | Order items |
| `items[].menu_item_id` | number | ✅ | Menu item ID |
| `items[].quantity` | number | ✅ | Item quantity |
| `items[].price` | number | ✅ | Item price |

#### Success Response (200)
```json
{
  "id": 123,
  "order_code": "MV20240115103000ABC123",
  "user_id": 1,
  "name": "John Doe",
  "phone": "+1234567890",
  "email": "john@example.com",
  "address": "123 Main St, City",
  "note": "Please ring the bell",
  "type": "delivery",
  "discount_code": "SAVE10",
  "discount_amount": "10.00",
  "total_amount": "90.00",
  "status": "pending",
  "payment_status": "pending",
  "payment_method": "cash",
  "created_at": "2024-01-15T10:30:00.000000Z",
  "updated_at": "2024-01-15T10:30:00.000000Z",
  "cancelled_at": null,
  "order_items": [
    {
      "id": 1,
      "menu_item_id": 1,
      "name": "Burger Deluxe",
      "price": "25.00",
      "quantity": 2,
      "total": "50.00",
      "menu_item": {
        "id": 1,
        "name": "Burger Deluxe",
        "price": "25.00",
        "description": "Delicious burger"
      }
    }
  ]
}
```

---

### 2. Get User Orders
**Endpoint:** `GET /api/user/orders`
**Purpose:** Lấy danh sách đơn hàng của user

#### Request
```javascript
fetch('/api/user/orders?per_page=10&page=1', {
  method: 'GET',
  headers: {
    'Authorization': 'Bearer ' + token,
    'Accept': 'application/json'
  }
})
```

#### Success Response (200)
```json
{
  "data": [
    {
      "id": 123,
      "order_code": "MV20240115103000ABC123",
      "name": "John Doe",
      "phone": "+1234567890",
      "type": "delivery",
      "discount_code": "SAVE10",
      "discount_amount": "10.00",
      "total_amount": "90.00",
      "status": "pending",
      "payment_status": "pending",
      "created_at": "2024-01-15T10:30:00.000000Z",
      "order_items": [...]
    }
  ],
  "links": {...},
  "meta": {...}
}
```

---

## 🔧 Frontend Integration Examples

### React Hook for Coupon Management
```javascript
import { useState, useEffect } from 'react';

const useCoupons = (token) => {
  const [coupons, setCoupons] = useState([]);
  const [loading, setLoading] = useState(false);

  const headers = {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json',
    'Content-Type': 'application/json'
  };

  // Lấy danh sách coupons
  const loadCoupons = async () => {
    setLoading(true);
    try {
      const response = await fetch('/api/user/coupons', {
        headers
      });
      const data = await response.json();
      setCoupons(data.data || []);
    } catch (error) {
      console.error('Error loading coupons:', error);
    } finally {
      setLoading(false);
    }
  };

  // Validate coupon
  const validateCoupon = async (code, orderTotal) => {
    try {
      const response = await fetch('/api/user/coupons/validate', {
        method: 'POST',
        headers,
        body: JSON.stringify({
          code,
          order_total: orderTotal
        })
      });
      return await response.json();
    } catch (error) {
      return {
        success: false,
        message: 'Network error',
        errors: { code: 'Failed to validate coupon' }
      };
    }
  };

  // Tính discount amount
  const calculateDiscount = (coupon, orderTotal) => {
    if (!coupon || !coupon.data) return 0;
    
    const { type, value } = coupon.data;
    
    let discount = 0;
    
    if (type === 'percentage') {
      discount = (orderTotal * value) / 100;
    } else if (type === 'fixed') {
      discount = Math.min(value, orderTotal);
    }
    
    return Number(discount.toFixed(2));
  };

  useEffect(() => {
    if (token) {
      loadCoupons();
    }
  }, [token]);

  return {
    coupons,
    loading,
    loadCoupons,
    validateCoupon,
    calculateDiscount
  };
};
```

### Order Creation with Discount
```javascript
const createOrder = async (orderData, couponCode = null, token) => {
  let totalAmount = orderData.items.reduce((sum, item) => 
    sum + (item.price * item.quantity), 0
  );
  
  let discountAmount = 0;
  
  // Apply coupon if provided
  if (couponCode) {
    const couponResult = await validateCoupon(couponCode, totalAmount);
    if (couponResult.success) {
      discountAmount = calculateDiscount(couponResult, totalAmount);
      totalAmount -= discountAmount;
    } else {
      throw new Error(couponResult.message);
    }
  }
  
  const orderPayload = {
    ...orderData,
    discount_code: couponCode,
    discount_amount: discountAmount,
    total_amount: totalAmount
  };
  
  const response = await fetch('/api/user/orders/store', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json',
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(orderPayload)
  });
  
  return await response.json();
};
```

---

## 🔄 Order Flow with Discount

```
1. User adds items to cart
2. User enters coupon code
3. Frontend validates coupon → POST /api/user/coupons/validate
4. Frontend calculates discount amount
5. Frontend shows final total
6. User confirms order
7. Frontend creates order → POST /api/user/orders/store
   - discount_code: from user input
   - discount_amount: calculated by frontend  
   - total_amount: subtotal - discount_amount
8. Backend validates coupon exists and user has it
9. Backend decreases coupon usage count
10. Backend saves order with provided amounts
```

---

## 📋 Error Handling

### Common Error Responses

**Authentication Error (401)**
```json
{
  "success": false,
  "message": "User not authenticated"
}
```

**Validation Error (422)**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "total_amount": ["The total amount field is required."],
    "items": ["The items field is required."]
  }
}
```

**Server Error (500)**
```json
{
  "success": false,
  "message": "Error creating order",
  "error": "Internal server error"
}
```

## 🎯 Key Points

1. **Frontend Responsibility**: 
   - Validate coupons before checkout
   - Calculate discount amounts
   - Send final `total_amount` to backend

2. **Backend Responsibility**:
   - Validate coupon exists and user owns it
   - Decrease coupon usage count
   - Save order with provided discount info
   - Generate order code and manage status

3. **No Double Calculation**: 
   - Backend trusts frontend calculations
   - Backend only validates coupon ownership
   - Prevents calculation inconsistencies

4. **Coupon Usage**:
   - Only decremented after successful order creation
   - Automatic quantity management
   - Prevents double usage