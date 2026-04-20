# Stripe Webhook Setup Guide

## 🔗 Tạo Stripe Webhook

### Step 1: Truy cập Stripe Dashboard
1. Đăng nhập vào [Stripe Dashboard](https://dashboard.stripe.com)
2. Chọn **Test mode** (toggle ở góc trái) để test
3. Vào menu **Developers** → **Webhooks**

### Step 2: Tạo Webhook Endpoint
1. Click **Add endpoint**
2. **Endpoint URL**: `https://your-domain.com/api/webhook/stripe`
   ```
   # Ví dụ:
   https://mamiviet-api.com/api/webhook/stripe
   https://api.mamiviet.de/api/webhook/stripe
   ```

3. **Description** (optional): `MamiViet API Payment Webhooks`

### Step 3: Chọn Events to Listen
**Chọn các events sau:**
- ✅ `payment_intent.succeeded` - Thanh toán thành công
- ✅ `payment_intent.payment_failed` - Thanh toán thất bại
- ✅ `payment_intent.canceled` - Thanh toán bị hủy
- ✅ `charge.dispute.created` - Tranh chấp được tạo

**Optional events (nâng cao):**
- `payment_intent.created` - PaymentIntent được tạo
- `payment_intent.requires_action` - Cần xác thực thêm (3D Secure)
- `charge.succeeded` - Charge thành công  
- `charge.failed` - Charge thất bại
- `invoice.payment_succeeded` - Thanh toán hóa đơn thành công

### Step 4: Lấy Webhook Secret
1. Sau khi tạo xong, click vào webhook vừa tạo
2. Trong section **Signing secret**, click **Reveal**
3. Copy secret key có dạng: `whsec_1234567890abcdef...`
4. Thêm vào `.env`:
   ```env
   STRIPE_WEBHOOK_SECRET=whsec_1234567890abcdef...
   ```

---

## 🛠️ Code Implementation Details

### Current Webhook Handler
File: `app/Http/Controllers/PaymentController.php`

```php
public function stripeWebhook(Request $request): JsonResponse
{
    try {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        // Verify webhook signature
        $event = Webhook::constructEvent($payload, $signature, $webhookSecret);

        switch ($event->type) {
            case 'payment_intent.succeeded':
                $this->handleStripePaymentSucceeded($event->data->object);
                break;
            case 'payment_intent.payment_failed':
                $this->handleStripePaymentFailed($event->data->object);
                break;
        }

        return response()->json(['status' => 'success']);
    } catch (Exception $e) {
        Log::error('Stripe webhook failed', ['error' => $e->getMessage()]);
        return response()->json(['status' => 'error'], 400);
    }
}
```

### Webhook Payload Examples

**payment_intent.succeeded:**
```json
{
  "id": "evt_1234567890",
  "object": "event",
  "type": "payment_intent.succeeded",
  "data": {
    "object": {
      "id": "pi_1234567890",
      "object": "payment_intent",
      "amount": 2550,
      "currency": "usd",
      "status": "succeeded",
      "metadata": {
        "order_id": "123",
        "transaction_id": "456"
      }
    }
  }
}
```

**payment_intent.payment_failed:**
```json
{
  "id": "evt_0987654321", 
  "object": "event",
  "type": "payment_intent.payment_failed",
  "data": {
    "object": {
      "id": "pi_0987654321",
      "object": "payment_intent", 
      "amount": 2550,
      "currency": "usd",
      "status": "requires_payment_method",
      "last_payment_error": {
        "code": "card_declined",
        "message": "Your card was declined."
      },
      "metadata": {
        "order_id": "123",
        "transaction_id": "456"
      }
    }
  }
}
```

---

## 🧪 Testing Webhooks

### Option 1: Stripe CLI (Recommended)
1. **Install Stripe CLI:**
   ```bash
   # Windows
   scoop install stripe
   
   # Mac
   brew install stripe/stripe-cli/stripe
   
   # Linux
   wget https://github.com/stripe/stripe-cli/releases/latest/download/stripe_X.X.X_linux_x86_64.tar.gz
   ```

2. **Login to Stripe:**
   ```bash
   stripe login
   ```

3. **Forward events to local server:**
   ```bash
   stripe listen --forward-to localhost:8000/api/webhook/stripe
   ```

4. **Test webhook:**
   ```bash
   stripe trigger payment_intent.succeeded
   stripe trigger payment_intent.payment_failed
   ```

### Option 2: ngrok (Alternative)
1. **Install ngrok:**
   ```bash
   # Download from https://ngrok.com/download
   ./ngrok http 8000
   ```

2. **Use ngrok URL in Stripe Dashboard:**
   ```
   https://abc123.ngrok.io/api/webhook/stripe
   ```

### Option 3: Manual Testing via Stripe Dashboard
1. Go to **Developers** → **Webhooks** → Your webhook
2. Click **Send test webhook**
3. Choose event type: `payment_intent.succeeded`
4. Customize payload if needed
5. Click **Send test webhook**

---

## 🔒 Security Best Practices

### 1. Verify Webhook Signature
```php
// Already implemented in PaymentService
$event = Webhook::constructEvent($payload, $signature, $webhookSecret);
```

### 2. Idempotency Handling
```php
// Add to PaymentService
private function handleStripePaymentSucceeded($paymentIntent): void
{
    $orderId = $paymentIntent->metadata->order_id ?? null;
    $transactionId = $paymentIntent->metadata->transaction_id ?? null;

    if ($orderId && $transactionId) {
        $transaction = Transaction::find($transactionId);
        
        // Prevent duplicate processing
        if ($transaction && $transaction->isCompleted()) {
            Log::info('Payment already processed', ['transaction_id' => $transactionId]);
            return;
        }
        
        // Process payment...
    }
}
```

### 3. Rate Limiting
Add to `routes/api.php`:
```php
Route::post('/webhook/stripe', [PaymentController::class, 'stripeWebhook'])
    ->middleware('throttle:100,1'); // 100 requests per minute
```

### 4. IP Whitelisting (Optional)
```php
// Add middleware to verify Stripe IPs
// Stripe webhook IPs: https://stripe.com/docs/ips
```

---

## 🚨 Error Handling & Monitoring

### 1. Webhook Retry Logic
Stripe automatically retries failed webhooks:
- Initial failure: Retry immediately
- Subsequent failures: Exponential backoff
- Maximum attempts: 3 days

### 2. Monitor Webhook Status
```bash
# Check webhook delivery status in Stripe Dashboard
# Developers → Webhooks → Your webhook → Recent deliveries
```

### 3. Log All Webhook Events
```php
// In PaymentService
public function handleStripeWebhook(string $payload, string $signature): void
{
    try {
        $event = Webhook::constructEvent($payload, $signature, $webhookSecret);
        
        // Log all events
        Log::info('Stripe webhook received', [
            'event_id' => $event->id,
            'type' => $event->type,
            'created' => $event->created
        ]);
        
        // Process event...
        
    } catch (Exception $e) {
        Log::error('Stripe webhook failed', [
            'error' => $e->getMessage(),
            'payload_size' => strlen($payload),
            'has_signature' => !empty($signature)
        ]);
        throw $e;
    }
}
```

---

## 📋 Production Deployment Checklist

### Before Going Live:
- [ ] ✅ Switch to **Live mode** in Stripe Dashboard
- [ ] ✅ Get live API keys (`pk_live_...`, `sk_live_...`)
- [ ] ✅ Create webhook for production domain
- [ ] ✅ Update webhook secret in production `.env`
- [ ] ✅ Test webhook with real payment
- [ ] ✅ Monitor webhook delivery success rate
- [ ] ✅ Set up alerts for webhook failures

### Environment Variables:
```env
# Production
STRIPE_PUBLISHABLE_KEY=pk_live_...
STRIPE_SECRET_KEY=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_live_...

# Test (keep for development)  
STRIPE_TEST_PUBLISHABLE_KEY=pk_test_...
STRIPE_TEST_SECRET_KEY=sk_test_...
STRIPE_TEST_WEBHOOK_SECRET=whsec_test_...
```

---

## 🔍 Troubleshooting Common Issues

### Issue 1: Webhook Signature Verification Failed
**Error:** `Invalid signature for payload`

**Solutions:**
- Check webhook secret is correct
- Ensure raw payload is used (not parsed JSON)
- Verify Stripe-Signature header format

### Issue 2: Webhook Not Receiving Events  
**Error:** No webhook calls in logs

**Solutions:**
- Check webhook URL is publicly accessible
- Verify SSL certificate is valid
- Test URL manually: `curl -X POST https://your-domain.com/api/webhook/stripe`

### Issue 3: Duplicate Event Processing
**Error:** Payment processed multiple times

**Solutions:** 
- Implement idempotency checks
- Check transaction status before processing
- Use database transactions

### Issue 4: Event Not Found
**Error:** `No handler for event type`

**Solutions:**
- Add missing event types to webhook configuration
- Add handler for new event types in code
- Log unhandled events for analysis

---

## 📊 Webhook Monitoring Dashboard

### Key Metrics to Track:
1. **Delivery Success Rate** - Should be >99%
2. **Response Time** - Should be <5 seconds  
3. **Event Processing Time** - Time to update order status
4. **Failed Events** - Events that need manual review

### Stripe Dashboard Locations:
- **Webhook Status**: Developers → Webhooks → [Your webhook] → Recent deliveries
- **Event Logs**: Developers → Events
- **API Logs**: Developers → API logs

### Custom Monitoring (Optional):
```php
// Add to PaymentService for custom metrics
private function recordWebhookMetrics(string $eventType, bool $success, float $processingTime): void
{
    Log::info('Webhook metrics', [
        'event_type' => $eventType,
        'success' => $success,
        'processing_time_ms' => $processingTime * 1000,
        'timestamp' => now()
    ]);
}
```