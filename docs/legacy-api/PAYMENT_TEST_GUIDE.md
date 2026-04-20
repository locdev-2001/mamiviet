# Payment Integration Testing Guide

## 🔄 Complete Payment Workflow

### 1. Setup & Prerequisites
```bash
# 1. Install dependencies
composer install

# 2. Import SQL files (in order)
mysql -u your_user -p your_database < database_sql/01_payment_gateways.sql
mysql -u your_user -p your_database < database_sql/02_transactions.sql  
mysql -u your_user -p your_database < database_sql/03_orders_payment_fields.sql

# 3. Update .env with payment credentials
STRIPE_PUBLISHABLE_KEY=pk_test_...
STRIPE_SECRET_KEY=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
PAYPAL_CLIENT_ID=your_paypal_client_id
PAYPAL_CLIENT_SECRET=your_paypal_client_secret
PAYPAL_MODE=sandbox

# 4. Update payment gateway configs in database
UPDATE payment_gateways 
SET config = JSON_SET(config, 
    '$.publishable_key', 'pk_test_your_key',
    '$.secret_key', 'sk_test_your_key',
    '$.webhook_secret', 'whsec_your_secret'
) WHERE name = 'stripe';
```

---

## 🧪 Test Scenarios

### Scenario 1: Cash Payment (Default)
**✅ Expected Success Flow:**

```bash
# 1. Create order without payment_method (defaults to cash)
curl -X POST "https://your-domain.com/api/user/orders" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "X-Locale: de" \
  -d '{
    "type": "delivery",
    "name": "Test User",
    "phone": "1234567890",
    "address": "Test Address 123",
    "items": [
        {
            "menu_item_id": 1,
            "quantity": 2
        }
    ]
  }'
```

**Expected Response:**
```json
{
    "data": {
        "id": 123,
        "order_code": "MV20240907...",
        "payment_status": "pending",
        "payment_status_label": "Ausstehend",
        "payment_method": "cash", 
        "payment_method_label": "Bargeld",
        "total_amount": "25.50",
        "requires_online_payment": false,
        "is_payment_completed": false,
        "is_payment_pending": true
    }
}
```

### Scenario 2: Stripe Payment Success Flow
**✅ Expected Success Flow:**

```bash
# 1. Create order with Stripe payment method
curl -X POST "https://your-domain.com/api/user/orders" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "delivery",
    "name": "Stripe Test User",
    "phone": "1234567890", 
    "address": "Stripe Address 123",
    "payment_method": "stripe",
    "items": [
        {"menu_item_id": 1, "quantity": 1}
    ]
  }'

# Response: order_id = 124, payment_method = "stripe", payment_status = "pending"

# 2. Create Stripe Payment Intent
curl -X POST "https://your-domain.com/api/payment/stripe/create-intent" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 15.50,
    "order_id": 124,
    "currency": "usd"
  }'

# Response: client_secret, payment_intent_id, transaction_id

# 3. Frontend confirms payment with Stripe (simulation)
# 4. Stripe webhook fires -> Order payment_status becomes "completed"
```

### Scenario 3: PayPal Payment Success Flow
**✅ Expected Success Flow:**

```bash
# 1. Create order with PayPal payment method  
curl -X POST "https://your-domain.com/api/user/orders" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "onsite",
    "name": "PayPal Test User",
    "phone": "1234567890",
    "payment_method": "paypal", 
    "items": [
        {"menu_item_id": 2, "quantity": 3}
    ]
  }'

# Response: order_id = 125, payment_method = "paypal", payment_status = "pending"

# 2. Create PayPal Order
curl -X POST "https://your-domain.com/api/payment/paypal/create-order" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 45.00,
    "order_id": 125,
    "currency": "USD"
  }'

# Response: paypal_order_id, approval_url, transaction_id

# 3. Customer approves payment via approval_url
# 4. PayPal webhook fires -> Order payment_status becomes "completed"
```

---

## ❌ Error Test Cases

### Test Case 1: Invalid Payment Method
```bash
curl -X POST "https://your-domain.com/api/user/orders" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "X-Locale: de" \
  -d '{
    "name": "Test",
    "phone": "123",
    "address": "Test",
    "payment_method": "bitcoin",
    "items": [{"menu_item_id": 1, "quantity": 1}]
  }'
```

**Expected Error Response (422):**
```json
{
    "status": "error",
    "message": "Validierung fehlgeschlagen",
    "errors": {
        "payment_method": ["Ungültige Zahlungsmethode"]
    }
}
```

### Test Case 2: Missing Required Fields
```bash
curl -X POST "https://your-domain.com/api/payment/stripe/create-intent" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": -5.00,
    "currency": "INVALID"
  }'
```

**Expected Error Response (422):**
```json
{
    "status": "error",
    "message": "Validierung fehlgeschlagen",
    "errors": {
        "amount": ["Betrag muss größer als 0 sein"],
        "order_id": ["Bestell-ID ist erforderlich"],
        "currency": ["Währung muss 3 Zeichen haben"]
    }
}
```

### Test Case 3: Unsupported Currency
```bash
curl -X POST "https://your-domain.com/api/payment/stripe/create-intent" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 25.50,
    "order_id": 123,
    "currency": "VND"
  }'
```

**Expected Error Response (400):**
```json
{
    "status": "error", 
    "message": "Währung wird nicht unterstützt",
    "errors": {
        "currency": ["Diese Währung wird nicht unterstützt"]
    }
}
```

### Test Case 4: Non-existent Order
```bash
curl -X POST "https://your-domain.com/api/payment/stripe/create-intent" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 25.50,
    "order_id": 99999,
    "currency": "usd"
  }'
```

**Expected Error Response (422):**
```json
{
    "status": "error",
    "message": "Validierung fehlgeschlagen", 
    "errors": {
        "order_id": ["Bestellung existiert nicht"]
    }
}
```

### Test Case 5: Payment Gateway Not Available
```bash
# Disable Stripe in database
UPDATE payment_gateways SET is_active = 0 WHERE name = 'stripe';

# Then try to create payment intent
curl -X POST "https://your-domain.com/api/payment/stripe/create-intent" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 25.50,
    "order_id": 123,
    "currency": "usd"
  }'
```

**Expected Error Response (500):**
```json
{
    "status": "error",
    "message": "Fehler beim Erstellen des Stripe PaymentIntent",
    "errors": {
        "general": "Zahlungsgateway ist nicht verfügbar"
    }
}
```

---

## 🔗 Complete Workflow Testing

### Step-by-Step Integration Test

```bash
# 1. Get available payment gateways
curl -X GET "https://your-domain.com/api/payment/gateways" \
  -H "X-Locale: de"

# Expected: List of active gateways (stripe, paypal)

# 2. Create order with specific payment method
ORDER_RESPONSE=$(curl -X POST "https://your-domain.com/api/user/orders" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "delivery",
    "name": "Integration Test",
    "phone": "1234567890",
    "address": "Test Address",
    "payment_method": "stripe",
    "items": [{"menu_item_id": 1, "quantity": 1}]
  }')

ORDER_ID=$(echo $ORDER_RESPONSE | jq -r '.data.id')
echo "Created Order ID: $ORDER_ID"

# 3. Create payment intent
PAYMENT_RESPONSE=$(curl -X POST "https://your-domain.com/api/payment/stripe/create-intent" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d "{
    \"amount\": 15.50,
    \"order_id\": $ORDER_ID,
    \"currency\": \"usd\"
  }")

CLIENT_SECRET=$(echo $PAYMENT_RESPONSE | jq -r '.data.client_secret')
echo "Payment Intent Created: $CLIENT_SECRET"

# 4. Check order status
curl -X GET "https://your-domain.com/api/user/orders" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  | jq '.data[] | select(.id == '$ORDER_ID')'

# Expected: payment_status = "pending", requires_online_payment = true

# 5. Simulate webhook (manual database update for testing)
# UPDATE orders SET payment_status = 'completed' WHERE id = $ORDER_ID;

# 6. Verify final order status  
curl -X GET "https://your-domain.com/api/user/orders" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  | jq '.data[] | select(.id == '$ORDER_ID')'

# Expected: payment_status = "completed", is_payment_completed = true
```

---

## 🌐 Localization Testing

### German (Default)
```bash
curl -X GET "https://your-domain.com/api/payment/gateways" \
  -H "X-Locale: de"

# Expected labels: "Ausstehend", "Bargeld", "Kreditkarte (Stripe)"
```

### English
```bash
curl -X GET "https://your-domain.com/api/payment/gateways" \
  -H "X-Locale: en"

# Expected labels: "Pending", "Cash", "Credit Card (Stripe)"
```

---

## 📊 Database State Verification

### Check Order Payment Fields
```sql
SELECT 
    id, 
    order_code, 
    payment_method, 
    payment_status,
    total_amount,
    created_at 
FROM orders 
ORDER BY created_at DESC 
LIMIT 5;
```

### Check Transaction Records
```sql
SELECT 
    t.id,
    t.order_id,
    t.gateway,
    t.gateway_transaction_id,
    t.amount,
    t.status,
    o.order_code
FROM transactions t
JOIN orders o ON t.order_id = o.id
ORDER BY t.created_at DESC;
```

### Check Gateway Configurations
```sql
SELECT name, is_active, JSON_EXTRACT(config, '$.supported_currencies') as currencies 
FROM payment_gateways;
```

---

## 🔍 Troubleshooting Common Issues

### Issue 1: Webhook Not Firing
**Check:**
- Webhook URL is accessible (not localhost)
- SSL certificate is valid
- Webhook signature verification
- Check logs: `tail -f storage/logs/laravel.log`

### Issue 2: Translation Not Working
**Check:**
- Files are in `resources/lang/de/payment.php`
- `SetLocale` middleware is applied
- `X-Locale` header is set correctly

### Issue 3: Payment Gateway Not Available  
**Check:**
- Database gateway is `is_active = 1`
- Environment variables are set
- Gateway configuration in database is correct

### Issue 4: Enum Validation Failing
**Check:**
- PaymentMethod enum values: `cash`, `stripe`, `paypal`
- Case sensitivity (lowercase)
- Import statements in Request classes

---

## 📋 Testing Checklist

- [ ] ✅ Create order with cash payment (default)
- [ ] ✅ Create order with stripe payment method
- [ ] ✅ Create order with paypal payment method  
- [ ] ✅ Update order payment method
- [ ] ✅ Get payment gateways list
- [ ] ✅ Create Stripe payment intent
- [ ] ✅ Create PayPal order
- [ ] ✅ Handle Stripe webhook (success)
- [ ] ✅ Handle PayPal webhook (success)
- [ ] ❌ Invalid payment method validation
- [ ] ❌ Missing required fields validation
- [ ] ❌ Unsupported currency validation
- [ ] ❌ Non-existent order validation
- [ ] ❌ Gateway not available error
- [ ] 🌐 German localization 
- [ ] 🌐 English localization
- [ ] 🔍 Database state verification
- [ ] 🔍 Log file verification

---

## 🚀 Production Checklist

- [ ] Update `.env` with production credentials
- [ ] Set `PAYPAL_MODE=live` for production
- [ ] Update webhook URLs to production domain
- [ ] Test with real payment methods
- [ ] Monitor logs for errors
- [ ] Set up webhook retry mechanisms
- [ ] Configure rate limiting
- [ ] Set up monitoring alerts