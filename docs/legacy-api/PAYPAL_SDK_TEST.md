# PayPal SDK Integration Test Guide

## ✅ **Fixed PayPal PHP Server SDK Implementation**

### 🔧 **Corrected Syntax:**
```php
// CORRECT - Using Builder classes with init() methods
use PaypalServerSdkLib\Models\Builders\OrderRequestBuilder;
use PaypalServerSdkLib\Models\Builders\PurchaseUnitRequestBuilder;
use PaypalServerSdkLib\Models\Builders\AmountWithBreakdownBuilder;
use PaypalServerSdkLib\Models\CheckoutPaymentIntent;

$purchaseUnit = PurchaseUnitRequestBuilder::init(
    AmountWithBreakdownBuilder::init(
        'USD',
        '25.50'
    )->build()
)
->referenceId('123')
->description('Order MV20240907...')
->build();

$orderRequest = OrderRequestBuilder::init(
    CheckoutPaymentIntent::CAPTURE,
    [$purchaseUnit]
)->build();

$collect = [
    'body' => $orderRequest,
    'prefer' => 'return=representation'
];

$response = $ordersController->createOrder($collect);
```

---

## 🧪 **Test PayPal Integration**

### Step 1: Composer Install
```bash
composer install
# Make sure paypal/paypal-server-sdk:1.1.0 is installed
```

### Step 2: Environment Setup
```env
PAYPAL_CLIENT_ID=your_sandbox_client_id
PAYPAL_CLIENT_SECRET=your_sandbox_client_secret  
PAYPAL_MODE=sandbox
```

### Step 3: Database Setup
```sql
-- Update PayPal gateway config
UPDATE payment_gateways 
SET config = JSON_SET(config,
    '$.client_id', 'your_paypal_client_id',
    '$.client_secret', 'your_paypal_client_secret',
    '$.mode', 'sandbox'
) 
WHERE name = 'paypal';

-- Check if gateway is active
SELECT name, is_active FROM payment_gateways WHERE name = 'paypal';
```

### Step 4: Test PayPal Order Creation
```bash
curl -X POST "http://localhost:8000/api/payment/paypal/create-order" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "X-Locale: en" \
  -d '{
    "amount": 25.50,
    "order_id": 123,
    "currency": "USD"
  }'
```

**Expected Success Response:**
```json
{
    "status": "success", 
    "data": {
        "paypal_order_id": "8XH12345ABC123456",
        "transaction_id": 789,
        "approval_url": "https://www.sandbox.paypal.com/checkoutnow?token=8XH12345ABC123456",
        "message": "PayPal order created successfully"
    }
}
```

---

## 🔍 **Debugging Common Issues**

### Issue 1: Class Not Found
**Error:** `Class 'PaypalServerSdkLib\Models\Builders\OrderRequestBuilder' not found`

**Solution:**
```bash
composer dump-autoload
composer show paypal/paypal-server-sdk
```

### Issue 2: Method Not Found  
**Error:** `Call to undefined method builder()`

**Solution:** Use `Builder::init()` instead of `Model::builder()`
```php
// WRONG
$purchaseUnit = PurchaseUnitRequest::builder()

// CORRECT  
$purchaseUnit = PurchaseUnitRequestBuilder::init(...)
```

### Issue 3: Invalid Order Request
**Error:** `Invalid request. See details.`

**Check:**
- Amount format: string with 2 decimals (`"25.50"`)
- Currency code: 3 uppercase letters (`"USD"`)
- Required fields are provided

### Issue 4: Gateway Not Available
**Error:** `Payment gateway is not available`

**Check:**
```sql
SELECT * FROM payment_gateways WHERE name = 'paypal';
-- Verify is_active = 1 and config has client_id/client_secret
```

---

## 📊 **Testing Workflow**

### Complete Test Scenario
```bash
# 1. Create order with PayPal payment method
ORDER_RESPONSE=$(curl -s -X POST "http://localhost:8000/api/user/orders" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "delivery",
    "name": "PayPal Test User",
    "phone": "1234567890",
    "address": "PayPal Test Address",
    "payment_method": "paypal",
    "items": [{"menu_item_id": 1, "quantity": 1}]
  }')

ORDER_ID=$(echo $ORDER_RESPONSE | jq -r '.data.id')
TOTAL_AMOUNT=$(echo $ORDER_RESPONSE | jq -r '.data.total_amount')

echo "Created Order ID: $ORDER_ID"
echo "Total Amount: $TOTAL_AMOUNT"

# 2. Create PayPal order
PAYPAL_RESPONSE=$(curl -s -X POST "http://localhost:8000/api/payment/paypal/create-order" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d "{
    \"amount\": $TOTAL_AMOUNT,
    \"order_id\": $ORDER_ID,
    \"currency\": \"USD\"
  }")

PAYPAL_ORDER_ID=$(echo $PAYPAL_RESPONSE | jq -r '.data.paypal_order_id')
APPROVAL_URL=$(echo $PAYPAL_RESPONSE | jq -r '.data.approval_url')

echo "PayPal Order ID: $PAYPAL_ORDER_ID"
echo "Approval URL: $APPROVAL_URL"

# 3. Check transaction record
mysql -u your_user -p your_database -e "
SELECT t.*, o.order_code 
FROM transactions t 
JOIN orders o ON t.order_id = o.id 
WHERE t.gateway_transaction_id = '$PAYPAL_ORDER_ID';
"
```

---

## 🎯 **Success Criteria**

### PayPal Order Creation ✅
- [ ] No `Class not found` errors
- [ ] No `Method not found` errors  
- [ ] Returns valid PayPal order ID
- [ ] Returns approval URL
- [ ] Transaction record created in database
- [ ] Order payment_method set to 'paypal'
- [ ] Order payment_status set to 'pending'

### PayPal Approval Flow ✅
- [ ] Approval URL opens PayPal checkout
- [ ] Customer can complete payment in sandbox
- [ ] Webhook fires after payment completion
- [ ] Order payment_status updates to 'completed'
- [ ] Transaction status updates to 'completed'

### Error Handling ✅
- [ ] Invalid amount validation works
- [ ] Invalid currency validation works
- [ ] Gateway not available error works
- [ ] Non-existent order validation works

---

## 🔄 **PayPal Sandbox Testing**

### Sandbox Test Accounts
1. **Personal Account** (Buyer):
   - Email: `sb-buyer@personal.example.com`
   - Password: Check PayPal Developer Dashboard

2. **Business Account** (Merchant):
   - Email: `sb-merchant@business.example.com` 
   - Used for receiving payments

### Test Payment Flow
1. Create PayPal order via API
2. Open approval URL in browser
3. Login with sandbox buyer account
4. Complete payment
5. Verify webhook received
6. Check order status updated

---

## 📝 **Debug Logs to Check**

### Laravel Logs
```bash
tail -f storage/logs/laravel.log | grep -i paypal
```

**Look for:**
- `PayPal Order created` - Order creation success
- `PayPal webhook received` - Webhook delivery  
- `PayPal payment completed` - Payment success
- `PayPal order capture failed` - Capture errors

### Database Verification
```sql
-- Check recent PayPal transactions
SELECT 
    t.id,
    t.gateway_transaction_id,
    t.amount,
    t.currency,
    t.status,
    o.order_code,
    o.payment_status,
    o.payment_method,
    t.created_at
FROM transactions t
JOIN orders o ON t.order_id = o.id  
WHERE t.gateway = 'paypal'
ORDER BY t.created_at DESC
LIMIT 5;
```

---

## 🚀 **Production Deployment Notes**

### Environment Changes
```env
PAYPAL_MODE=live
PAYPAL_CLIENT_ID=live_client_id
PAYPAL_CLIENT_SECRET=live_client_secret
```

### Database Updates
```sql
UPDATE payment_gateways 
SET config = JSON_SET(config,
    '$.client_id', 'live_client_id',
    '$.client_secret', 'live_client_secret', 
    '$.mode', 'live'
)
WHERE name = 'paypal';
```

### Testing Checklist
- [ ] Live PayPal credentials configured
- [ ] Webhook URLs point to production domain
- [ ] SSL certificates valid
- [ ] Test with real PayPal account
- [ ] Monitor logs for errors
- [ ] Verify webhook delivery success rate