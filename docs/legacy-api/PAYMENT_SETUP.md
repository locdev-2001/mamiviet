# Payment Integration Setup Guide

## Environment Configuration

Add the following environment variables to your `.env` file:

### Stripe Configuration
```env
# Stripe Keys (Get from https://dashboard.stripe.com/apikeys)
STRIPE_PUBLISHABLE_KEY=pk_test_...
STRIPE_SECRET_KEY=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

### PayPal Configuration
```env
# PayPal Credentials (Get from https://developer.paypal.com)
PAYPAL_CLIENT_ID=your_paypal_client_id
PAYPAL_CLIENT_SECRET=your_paypal_client_secret
PAYPAL_MODE=sandbox  # Use 'live' for production
```

## Database Setup

1. Import the SQL files in this order:
```bash
# 1. Create payment_gateways table
mysql -u your_user -p your_database < database_sql/01_payment_gateways.sql

# 2. Create transactions table  
mysql -u your_user -p your_database < database_sql/02_transactions.sql

# 3. Add payment fields to orders table
mysql -u your_user -p your_database < database_sql/03_orders_payment_fields.sql
```

2. Update the payment gateway configurations in database:
```sql
-- Update Stripe configuration
UPDATE payment_gateways 
SET config = JSON_SET(config, 
    '$.publishable_key', 'your_stripe_publishable_key',
    '$.secret_key', 'your_stripe_secret_key', 
    '$.webhook_secret', 'your_stripe_webhook_secret'
) 
WHERE name = 'stripe';

-- Update PayPal configuration  
UPDATE payment_gateways 
SET config = JSON_SET(config,
    '$.client_id', 'your_paypal_client_id',
    '$.client_secret', 'your_paypal_client_secret',
    '$.mode', 'sandbox'
) 
WHERE name = 'paypal';
```

## Composer Dependencies

Install the required packages:
```bash
composer require stripe/stripe-php paypal/paypal-checkout-sdk
```

Or the packages are already added to `composer.json`, just run:
```bash
composer install
```

## Webhook Setup

### Stripe Webhook Setup
1. Go to [Stripe Dashboard > Webhooks](https://dashboard.stripe.com/webhooks)
2. Create endpoint: `https://your-domain.com/api/webhook/stripe`
3. Select events: `payment_intent.succeeded`, `payment_intent.payment_failed`
4. Copy webhook secret to `STRIPE_WEBHOOK_SECRET`

### PayPal Webhook Setup
1. Go to [PayPal Developer > My Apps](https://developer.paypal.com/developer/applications)
2. Create webhook endpoint: `https://your-domain.com/api/webhook/paypal`
3. Select events: `CHECKOUT.ORDER.APPROVED`, `PAYMENT.CAPTURE.COMPLETED`, `PAYMENT.CAPTURE.DENIED`

## API Endpoints

### Payment Gateway Info
```
GET /api/payment/gateways
```

### Stripe Payment
```
POST /api/payment/stripe/create-intent
Content-Type: application/json
Authorization: Bearer {token}

{
    "amount": 25.50,
    "order_id": 123,
    "currency": "usd"
}
```

### PayPal Payment
```
POST /api/payment/paypal/create-order
Content-Type: application/json
Authorization: Bearer {token}

{
    "amount": 25.50,
    "order_id": 123,
    "currency": "USD"
}
```

### Webhooks (External calls - no auth)
```
POST /api/webhook/stripe
POST /api/webhook/paypal
```

## Testing

### Test with Stripe Test Cards
- Success: `4242424242424242`
- Decline: `4000000000000002`
- Insufficient funds: `4000000000009995`

### Test with PayPal Sandbox
- Use PayPal sandbox accounts for testing
- Mode should be set to 'sandbox' in environment

## Order Integration

### Creating Orders with Payment
When creating orders, set the payment fields:
```php
$order = Order::create([
    // ... existing fields
    'payment_method' => PaymentMethod::STRIPE, // or PaymentMethod::PAYPAL
    'payment_status' => PaymentStatus::PENDING,
]);
```

### Payment Workflow
1. Create order with `payment_status = 'pending'`
2. Create payment intent/order via API
3. Customer completes payment
4. Webhook updates order to `payment_status = 'completed'`
5. Transaction record is created/updated

## Localization

The system supports German (default) and English translations:
- German: `lang/de/payment.php`
- English: `lang/en/payment.php`

Set locale via:
- Query parameter: `?locale=en`
- Header: `X-Locale: en`
- Request body: `"locale": "en"`

## Security Notes

1. **Never expose secret keys** in frontend code
2. **Always verify webhook signatures** 
3. **Use HTTPS** for all payment endpoints
4. **Validate amounts** on server side
5. **Log all transactions** for audit trail

## Troubleshooting

### Common Issues

1. **"Payment gateway not available"**
   - Check database gateway is active
   - Verify environment variables are set

2. **"Currency not supported"**
   - Check gateway configuration in database
   - Ensure currency is in supported_currencies array

3. **Webhook failures**
   - Verify webhook signature validation
   - Check webhook URL is accessible
   - Ensure proper SSL certificate

### Logs Location
- Payment transactions: `storage/logs/laravel.log`
- Search for: `Stripe`, `PayPal`, `Payment`