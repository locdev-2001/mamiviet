# PayPal Authentication Debug Script

## 🚨 **Current Issue Analysis**
- Client initialization: ✅ SUCCESS
- API call: ❌ "Client is not authorized. An OAuth token is needed"
- Currency: EUR (might be unsupported in sandbox)

## 🧪 **Debug Steps**

### Step 1: Test PayPal Credentials Directly
```bash
# Test if your PayPal credentials actually work
CLIENT_ID="your_client_id_from_database"
CLIENT_SECRET="your_client_secret_from_database"

curl -X POST https://api.sandbox.paypal.com/v1/oauth2/token \
  -H "Accept: application/json" \
  -H "Accept-Language: en_US" \
  -u "$CLIENT_ID:$CLIENT_SECRET" \
  -d "grant_type=client_credentials"
```

**Expected Success Response:**
```json
{
    "scope": "https://uri.paypal.com/services/invoicing https://uri.paypal.com/services/vault/payment-tokens/read...",
    "access_token": "A21AAH9dZXKkTZ...",
    "token_type": "Bearer",
    "app_id": "APP-80W284485P519543T",
    "expires_in": 32400,
    "nonce": "2020-04-03T15:35:36ZtjzWzOKu..."
}
```

**If Failed:**
- Double-check Client ID và Secret từ PayPal Dashboard
- Make sure you're using Sandbox credentials for sandbox API

### Step 2: Check Database Config
```sql
-- Get exact credentials from database
SELECT 
    name,
    is_active,
    JSON_UNQUOTE(JSON_EXTRACT(config, '$.client_id')) as client_id,
    JSON_UNQUOTE(JSON_EXTRACT(config, '$.client_secret')) as client_secret,
    JSON_UNQUOTE(JSON_EXTRACT(config, '$.mode')) as mode,
    JSON_EXTRACT(config, '$.supported_currencies') as currencies
FROM payment_gateways 
WHERE name = 'paypal';
```

### Step 3: Force USD Currency Test
Add this temporary debug to createPayPalOrder method:

```php
public function createPayPalOrder(float $amount, int $orderId, string $currency = 'USD'): array
{
    try {
        // FORCE USD for testing
        $currency = 'USD';
        
        Log::info('PayPal Debug - Using USD', [
            'original_amount' => $amount,
            'forced_currency' => $currency,
            'client_id_length' => strlen($this->paypalGateway->getConfigValue('client_id')),
            'has_orders_controller' => !is_null($this->ordersController)
        ]);
        
        // ... rest of method
    }
}
```

### Step 4: Minimal Test Order
Create the simplest possible PayPal order:

```php
// In createPayPalOrder method, replace complex order with:
$purchaseUnit = PurchaseUnitRequestBuilder::init(
    AmountWithBreakdownBuilder::init('USD', '1.00')->build()
)->build();

$orderRequest = OrderRequestBuilder::init(
    CheckoutPaymentIntent::CAPTURE,
    [$purchaseUnit]
)->build();

$collect = ['body' => $orderRequest];
$response = $this->ordersController->createOrder($collect);
```

### Step 5: Check PayPal Developer App Settings
1. Login to https://developer.paypal.com/developer/applications
2. Click on your app name
3. Verify:
   - App Status: Active ✅
   - Features enabled: Accept Payments ✅
   - Live/Sandbox matches your environment
   - Client ID matches database exactly

### Step 6: Currency Support Check
PayPal Sandbox có thể chỉ support USD. Update database:

```sql
UPDATE payment_gateways 
SET config = JSON_SET(config, '$.supported_currencies', JSON_ARRAY('USD'))
WHERE name = 'paypal';
```

## 🔧 **Quick Fix Commands**

### Get Real Credentials from PayPal Dashboard
1. Go to https://developer.paypal.com/developer/applications
2. Select your app
3. Copy Client ID và Secret exactly

### Update Database with Correct Credentials
```sql
-- Replace with your actual credentials
UPDATE payment_gateways 
SET config = JSON_SET(config,
    '$.client_id', 'AYour_Actual_Client_ID_Here',
    '$.client_secret', 'EYour_Actual_Client_Secret_Here',
    '$.mode', 'sandbox',
    '$.supported_currencies', JSON_ARRAY('USD')
) 
WHERE name = 'paypal';
```

### Test API with USD Only
```bash
curl -X POST "http://localhost:8000/api/payment/paypal/create-order" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 1.00,
    "order_id": 53,
    "currency": "USD"
  }'
```

## 🎯 **Expected Results**

### If Credentials Are Wrong:
```bash
curl response: 
{
    "error": "invalid_client",
    "error_description": "Client Authentication failed"
}
```

### If Credentials Are Correct:
```bash  
curl response:
{
    "access_token": "A21AAH...",
    "expires_in": 32400
}
```

## 📝 **Next Actions Based on Results**

### If curl test fails:
1. Get new credentials from PayPal Dashboard
2. Update database with correct values
3. Test again

### If curl test succeeds but API still fails:
1. Check SDK version compatibility
2. Add more debugging to PaymentService
3. Test with minimal order structure

### If everything works with USD:
1. Update supported currencies to USD only
2. Convert EUR to USD in frontend before API call
3. Or get PayPal approval for multi-currency