# PayPal PHP Server SDK Integration Guide

## 📦 Installation & Setup

### Composer Installation
```bash
composer require paypal/paypal-server-sdk:1.1.0
```

### Environment Variables
```env
PAYPAL_CLIENT_ID=your_paypal_client_id
PAYPAL_CLIENT_SECRET=your_paypal_client_secret
PAYPAL_MODE=sandbox  # sandbox or live
```

---

## 🔧 Implementation Details

### SDK Initialization
```php
use PaypalServerSdk\PaypalServerSdkClientBuilder;
use PaypalServerSdk\Authentication\ClientCredentialsAuthCredentialsBuilder;
use PaypalServerSdk\Environment;

$client = PaypalServerSdkClientBuilder::init()
    ->clientCredentialsAuthCredentials(
        ClientCredentialsAuthCredentialsBuilder::init(
            $clientId,
            $clientSecret
        )
    )
    ->environment(Environment::SANDBOX) // or Environment::PRODUCTION
    ->build();

$ordersController = $client->getOrdersController();
```

### Creating PayPal Order
```php
use PaypalServerSdk\Models\OrderRequest;
use PaypalServerSdk\Models\PurchaseUnitRequest;
use PaypalServerSdk\Models\AmountWithBreakdown;
use PaypalServerSdk\Models\OrderApplicationContext;

// Build Purchase Unit
$purchaseUnit = PurchaseUnitRequest::builder()
    ->referenceId((string) $orderId)
    ->amount(
        AmountWithBreakdown::builder()
            ->currencyCode('USD')
            ->value('25.50')
            ->build()
    )
    ->description('Order MV20240907...')
    ->build();

// Build Application Context
$applicationContext = OrderApplicationContext::builder()
    ->returnUrl('https://your-domain.com/payment/success')
    ->cancelUrl('https://your-domain.com/payment/cancel')
    ->build();

// Build Order Request
$orderRequest = OrderRequest::builder()
    ->intent('CAPTURE')
    ->purchaseUnits([$purchaseUnit])
    ->applicationContext($applicationContext)
    ->build();

// Create Order
$response = $ordersController->ordersCreate($orderRequest);
$paypalOrder = $response->getResult();

// Get approval URL
$approvalUrl = null;
foreach ($paypalOrder->getLinks() as $link) {
    if ($link->getRel() === 'approve') {
        $approvalUrl = $link->getHref();
        break;
    }
}
```

### Capturing Payment
```php
// After customer approves payment
$response = $ordersController->ordersCapture($paypalOrderId);
$result = $response->getResult();
```

---

## 🔗 API Endpoints

### Create PayPal Order
```bash
POST /api/payment/paypal/create-order
Authorization: Bearer {token}
Content-Type: application/json

{
    "amount": 25.50,
    "order_id": 123,
    "currency": "USD"
}
```

**Success Response:**
```json
{
    "status": "success",
    "data": {
        "paypal_order_id": "8XH12345ABC123456",
        "transaction_id": 789,
        "approval_url": "https://www.sandbox.paypal.com/checkoutnow?token=...",
        "message": "PayPal-Bestellung erfolgreich erstellt"
    }
}
```

---

## 🎯 Supported Features

### Current SDK Coverage
The PayPal PHP Server SDK currently supports:
- ✅ **Orders Controller** - Create, show, update, capture orders
- ✅ **Payments Controller** - Handle payment operations  
- ✅ **Vault Controller** - Store payment methods

### Available Methods
```php
// Orders Controller
$ordersController->ordersCreate($orderRequest);
$ordersController->ordersGet($orderId);
$ordersController->ordersCapture($orderId);
$ordersController->ordersPatch($orderId, $patchRequest);

// Future: Payments and Vault controllers
```

---

## 🧪 Testing

### Test PayPal Order Creation
```bash
curl -X POST "http://localhost:8000/api/payment/paypal/create-order" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 25.50,
    "order_id": 123,
    "currency": "USD"
  }'
```

### PayPal Sandbox Testing
1. Use PayPal Developer sandbox accounts
2. Test approval URL flow
3. Verify webhook delivery
4. Check transaction records

### Test Cards
- **Personal Sandbox Account**: sb-test@personal.example.com
- **Business Sandbox Account**: sb-test@business.example.com

---

## 🔄 Migration from Checkout SDK

### Old Checkout SDK (Removed)
```php
// OLD - paypal/paypal-checkout-sdk
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;

$request = new OrdersCreateRequest();
$request->body = [...];
$response = $client->execute($request);
```

### New Server SDK (Current)
```php
// NEW - paypal/paypal-server-sdk
use PaypalServerSdk\PaypalServerSdkClientBuilder;
use PaypalServerSdk\Models\OrderRequest;

$orderRequest = OrderRequest::builder()->...->build();
$response = $ordersController->ordersCreate($orderRequest);
```

### Key Differences
1. **Builder Pattern**: New SDK uses fluent builder pattern
2. **Strong Typing**: Better type safety with dedicated model classes  
3. **Environment Enum**: Environment::SANDBOX vs Environment::PRODUCTION
4. **Method Names**: `ordersCreate()` vs `execute(OrdersCreateRequest)`
5. **Response Handling**: `getResult()` vs direct access

---

## 🔒 Security & Best Practices

### Authentication
- Uses OAuth 2 Client Credentials Grant
- Automatically handles token refresh
- Credentials passed in constructor

### Error Handling
```php
try {
    $response = $ordersController->ordersCreate($orderRequest);
    $paypalOrder = $response->getResult();
} catch (ApiException $e) {
    Log::error('PayPal API Error', [
        'status_code' => $e->getResponseCode(),
        'error_message' => $e->getMessage(),
        'response_body' => $e->getResponseBody()
    ]);
} catch (Exception $e) {
    Log::error('PayPal Error', ['error' => $e->getMessage()]);
}
```

### Configuration
```php
$client = PaypalServerSdkClientBuilder::init()
    ->clientCredentialsAuthCredentials($credentials)
    ->environment(Environment::SANDBOX)
    ->timeout(30)           // Request timeout
    ->numberOfRetries(3)    // Retry failed requests
    ->build();
```

---

## 📋 Implementation Checklist

- [x] ✅ Update composer.json with correct SDK
- [x] ✅ Rewrite PaymentService initialization  
- [x] ✅ Update createPayPalOrder method
- [x] ✅ Update webhook handling methods
- [x] ✅ Remove old checkout SDK references
- [x] ✅ Test PayPal order creation
- [ ] 🧪 Test PayPal webhooks
- [ ] 🚀 Production deployment

---

## 🔍 Troubleshooting

### Common Issues

**Issue 1: Class Not Found**
```bash
Error: Class 'PaypalServerSdk\PaypalServerSdkClientBuilder' not found

Solution: 
composer require paypal/paypal-server-sdk:1.1.0
composer dump-autoload
```

**Issue 2: Authentication Failed**
```bash  
Error: Client authentication failed

Solution:
- Check PAYPAL_CLIENT_ID and PAYPAL_CLIENT_SECRET
- Verify credentials are for correct environment (sandbox/live)
- Check PayPal Developer Dashboard
```

**Issue 3: Order Creation Failed**
```bash
Error: Invalid request. See details.

Solution:
- Check amount format (must be string with 2 decimals)
- Verify currency code is supported
- Check required fields are provided
```

**Issue 4: SSL Certificate Issues**
```bash
Error: SSL certificate problem

Solution: 
- Already handled in PaymentService with environment check
- SSL verification disabled for local development
- Use proper certificates in production
```

### Debug Mode
```php
// Enable debug logging
$client = PaypalServerSdkClientBuilder::init()
    ->clientCredentialsAuthCredentials($credentials)
    ->environment(Environment::SANDBOX)
    ->enableLogging(true)
    ->build();
```

---

## 🚀 Production Considerations

### Going Live
1. **Update Environment**: `PAYPAL_MODE=live`
2. **Get Live Credentials**: From PayPal Developer Dashboard
3. **Update Environment**: `Environment::PRODUCTION`
4. **Test with Real Account**: Verify live payments work
5. **Monitor Transactions**: Set up logging and alerts

### Performance
- SDK handles connection pooling
- Automatic OAuth token management
- Built-in retry mechanism
- Configurable timeouts