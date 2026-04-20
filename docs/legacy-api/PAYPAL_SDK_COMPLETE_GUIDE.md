# PayPal PHP Server SDK - Complete Implementation Guide

## 📚 **Official Documentation Summary**

### Authentication (OAuth 2 Client Credentials Grant)
- **Required**: Client ID và Client Secret từ PayPal Developer Dashboard
- **Auto Token Management**: SDK tự động fetch và refresh OAuth tokens
- **Environment Support**: SANDBOX (default) và PRODUCTION

### Controllers Available
1. **Orders Controller** - Create, capture, patch, authorize orders
2. **Payments Controller** - Handle payment operations
3. **Vault Controller** - Store payment methods

---

## 🔧 **Correct Implementation**

### 1. Client Initialization
```php
use PaypalServerSdkLib\PaypalServerSdkClientBuilder;
use PaypalServerSdkLib\Authentication\ClientCredentialsAuthCredentialsBuilder;
use PaypalServerSdkLib\Environment;

$client = PaypalServerSdkClientBuilder::init()
    ->clientCredentialsAuthCredentials(
        ClientCredentialsAuthCredentialsBuilder::init(
            'your_client_id',      // From PayPal Developer Dashboard
            'your_client_secret'   // From PayPal Developer Dashboard
        )
    )
    ->environment(Environment::SANDBOX)  // or Environment::PRODUCTION
    ->build();

$ordersController = $client->getOrdersController();
```

### 2. Create PayPal Order
```php
use PaypalServerSdkLib\Models\Builders\OrderRequestBuilder;
use PaypalServerSdkLib\Models\Builders\PurchaseUnitRequestBuilder;
use PaypalServerSdkLib\Models\Builders\AmountWithBreakdownBuilder;
use PaypalServerSdkLib\Models\CheckoutPaymentIntent;

// Build Purchase Unit
$purchaseUnit = PurchaseUnitRequestBuilder::init(
    AmountWithBreakdownBuilder::init(
        'USD',        // Currency code (uppercase)
        '25.50'       // Amount as string with 2 decimals
    )->build()
)
->referenceId('ORDER_123')                    // Your internal order ID
->description('Restaurant Order #123')         // Order description
->build();

// Build Order Request
$orderRequest = OrderRequestBuilder::init(
    CheckoutPaymentIntent::CAPTURE,    // Payment intent
    [$purchaseUnit]                   // Array of purchase units
)->build();

// API Call
$collect = [
    'body' => $orderRequest,
    'prefer' => 'return=representation'  // or 'return=minimal'
];

$response = $ordersController->createOrder($collect);
$paypalOrder = $response->getResult();
```

### 3. Capture Payment
```php
$collect = ['id' => $paypalOrderId];
$response = $ordersController->captureOrder($collect);
$result = $response->getResult();
```

---

## 🚨 **Common Authentication Issues & Solutions**

### Issue 1: "Client is not authorized"
**Cause**: Invalid or missing OAuth credentials

**Solutions:**
1. **Check Credentials in Database:**
```sql
SELECT name, config FROM payment_gateways WHERE name = 'paypal';
```

2. **Verify PayPal Developer Dashboard:**
   - Go to https://developer.paypal.com/developer/applications
   - Check your app's Client ID và Secret
   - Make sure app is for correct environment (Sandbox/Live)

3. **Update Database Config:**
```sql
UPDATE payment_gateways 
SET config = JSON_SET(config,
    '$.client_id', 'AYour_Real_Client_ID',
    '$.client_secret', 'EYour_Real_Client_Secret',
    '$.mode', 'sandbox'
) 
WHERE name = 'paypal';
```

### Issue 2: Environment Mismatch
**Problem**: Using sandbox credentials with production environment

**Solution:**
- Sandbox credentials → Environment::SANDBOX
- Live credentials → Environment::PRODUCTION
- Database `mode` field must match environment

### Issue 3: Malformed Credentials
**Common mistakes:**
- Client ID có spaces hoặc newlines
- Client Secret không đầy đủ
- Case sensitivity issues

**Debug checklist:**
```php
Log::info('PayPal Debug', [
    'client_id_length' => strlen($clientId),
    'client_id_starts_with' => substr($clientId, 0, 5),
    'has_client_secret' => !empty($clientSecret),
    'mode' => $mode,
]);
```

---

## 🏗️ **PaymentService Implementation**

### Complete Working Code
```php
<?php

namespace App\Services;

use PaypalServerSdkLib\PaypalServerSdkClientBuilder;
use PaypalServerSdkLib\Authentication\ClientCredentialsAuthCredentialsBuilder;
use PaypalServerSdkLib\Environment;
use PaypalServerSdkLib\Models\Builders\OrderRequestBuilder;
use PaypalServerSdkLib\Models\Builders\PurchaseUnitRequestBuilder;
use PaypalServerSdkLib\Models\Builders\AmountWithBreakdownBuilder;
use PaypalServerSdkLib\Models\CheckoutPaymentIntent;

class PaymentService 
{
    private $paypalClient;
    private $ordersController;

    private function initializePayPal(): void
    {
        $clientId = config('paypal.client_id');
        $clientSecret = config('paypal.client_secret');
        $mode = config('paypal.mode', 'sandbox');

        if ($clientId && $clientSecret) {
            $environment = $mode === 'live'
                ? Environment::PRODUCTION
                : Environment::SANDBOX;

            $this->paypalClient = PaypalServerSdkClientBuilder::init()
                ->clientCredentialsAuthCredentials(
                    ClientCredentialsAuthCredentialsBuilder::init(
                        $clientId,
                        $clientSecret
                    )
                )
                ->environment($environment)
                ->build();

            $this->ordersController = $this->paypalClient->getOrdersController();
        }
    }

    public function createPayPalOrder(float $amount, int $orderId, string $currency = 'USD'): array
    {
        $purchaseUnit = PurchaseUnitRequestBuilder::init(
            AmountWithBreakdownBuilder::init(
                strtoupper($currency),
                number_format($amount, 2, '.', '')
            )->build()
        )
        ->referenceId((string) $orderId)
        ->description("Order #{$orderId}")
        ->build();

        $orderRequest = OrderRequestBuilder::init(
            CheckoutPaymentIntent::CAPTURE,
            [$purchaseUnit]
        )->build();

        $collect = [
            'body' => $orderRequest,
            'prefer' => 'return=representation'
        ];

        $response = $this->ordersController->createOrder($collect);
        return $response->getResult();
    }
}
```

---

## 🧪 **Testing & Debugging**

### 1. Test PayPal Credentials
```bash
# Test API call to verify credentials work
curl -X POST https://api.sandbox.paypal.com/v1/oauth2/token \
  -H "Accept: application/json" \
  -H "Accept-Language: en_US" \
  -u "CLIENT_ID:CLIENT_SECRET" \
  -d "grant_type=client_credentials"
```

**Expected Response:**
```json
{
    "scope": "...",
    "access_token": "A21AAH...",
    "token_type": "Bearer",
    "app_id": "APP-...",
    "expires_in": 32400
}
```

### 2. Laravel Debug Logs
```bash
tail -f storage/logs/laravel.log | grep -i paypal
```

### 3. Database Verification
```sql
-- Check PayPal gateway config
SELECT 
    name, 
    is_active,
    JSON_EXTRACT(config, '$.client_id') as client_id,
    JSON_EXTRACT(config, '$.mode') as mode
FROM payment_gateways 
WHERE name = 'paypal';
```

---

## 🚀 **Production Deployment**

### 1. Get Live Credentials
1. PayPal Developer Dashboard → Your App
2. Switch to "Live" tab
3. Copy Live Client ID và Secret

### 2. Update Configuration
```sql
UPDATE payment_gateways 
SET config = JSON_SET(config,
    '$.client_id', 'LIVE_CLIENT_ID',
    '$.client_secret', 'LIVE_CLIENT_SECRET',
    '$.mode', 'live'
) 
WHERE name = 'paypal';
```

### 3. Environment Variables
```env
PAYPAL_MODE=live
PAYPAL_CLIENT_ID=LIVE_CLIENT_ID
PAYPAL_CLIENT_SECRET=LIVE_CLIENT_SECRET
```

### 4. Test Live Integration
- Create test order with small amount ($0.01)
- Complete payment with real PayPal account
- Verify webhook delivery
- Check transaction records

---

## 📋 **Troubleshooting Checklist**

### Authentication Issues ❌
- [ ] Client ID is correct and complete
- [ ] Client Secret is correct and complete  
- [ ] Environment matches credentials (sandbox/live)
- [ ] No extra spaces/characters in credentials
- [ ] PayPal app is active in Developer Dashboard

### API Issues ❌
- [ ] Amount format is string with 2 decimals ("25.50")
- [ ] Currency code is 3 uppercase letters ("USD")
- [ ] Purchase units array is not empty
- [ ] Required fields are provided

### Integration Issues ❌
- [ ] PaymentService initialization succeeds
- [ ] OrdersController is available
- [ ] Database gateway is active
- [ ] Transaction records are created
- [ ] Webhook endpoints are configured

### Success Indicators ✅
- [ ] No authentication errors in logs
- [ ] PayPal order ID returned
- [ ] Approval URL generated
- [ ] Customer can complete payment
- [ ] Webhooks fire correctly
- [ ] Order status updates properly

---

## 🔗 **Key Resources**

### PayPal Developer
- **Dashboard**: https://developer.paypal.com/developer/applications
- **Sandbox Testing**: https://developer.paypal.com/developer/accounts
- **Webhook Simulator**: https://developer.paypal.com/developer/notifications/simulate

### SDK Documentation
- **GitHub**: https://github.com/paypal/PayPal-PHP-Server-SDK
- **Authentication**: https://github.com/paypal/PayPal-PHP-Server-SDK/blob/1.1.0/doc/auth/oauth-2-client-credentials-grant.md
- **Orders Controller**: https://github.com/paypal/PayPal-PHP-Server-SDK/blob/1.1.0/doc/controllers/orders.md

---

## ⚡ **Quick Fix Commands**

```bash
# 1. Check current config
mysql -u user -p database -e "SELECT config FROM payment_gateways WHERE name='paypal';"

# 2. Update PayPal credentials (replace with your values)
mysql -u user -p database -e "
UPDATE payment_gateways 
SET config = JSON_SET(config,
    '$.client_id', 'YOUR_CLIENT_ID',
    '$.client_secret', 'YOUR_CLIENT_SECRET',
    '$.mode', 'sandbox'
) 
WHERE name = 'paypal';
"

# 3. Test API endpoint
curl -X POST "http://localhost:8000/api/payment/paypal/create-order" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"amount": 1.00, "order_id": 1, "currency": "USD"}'
```