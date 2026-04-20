# Payment API Documentation

## Base URL
```
https://your-domain.com/api
```

## Authentication
Most endpoints require Bearer token authentication:
```
Authorization: Bearer {your_access_token}
```

## Headers
```
Content-Type: application/json
Accept: application/json
X-Locale: de|en  (optional, defaults to 'de')
```

---

## 1. Get Active Payment Gateways

### Endpoint
```
GET /payment/gateways
```

### Headers
```
X-Locale: de
```

### Response
```json
{
    "status": "success",
    "data": {
        "gateways": [
            {
                "name": "stripe",
                "supported_currencies": ["usd", "eur"]
            },
            {
                "name": "paypal", 
                "supported_currencies": ["USD", "EUR"]
            }
        ],
        "message": "Zahlungsgateways erfolgreich abgerufen"
    }
}
```

### cURL Example
```bash
curl -X GET "https://your-domain.com/api/payment/gateways" \
  -H "Accept: application/json" \
  -H "X-Locale: de"
```

---

## 2. Create Stripe Payment Intent

### Endpoint
```
POST /payment/stripe/create-intent
```

### Headers
```
Authorization: Bearer {token}
Content-Type: application/json
X-Locale: de
```

### Request Body
```json
{
    "amount": 25.50,
    "order_id": 123,
    "currency": "usd"
}
```

### Response (Success)
```json
{
    "status": "success",
    "data": {
        "client_secret": "pi_3N4...5dK_secret_...",
        "payment_intent_id": "pi_3N4d5K2eZvKYlo2CaFc5dK",
        "transaction_id": 456,
        "publishable_key": "pk_test_...",
        "message": "Stripe PaymentIntent erfolgreich erstellt"
    }
}
```

### Response (Error)
```json
{
    "status": "error",
    "message": "Validierung fehlgeschlagen",
    "errors": {
        "amount": ["Betrag ist erforderlich"],
        "order_id": ["Bestell-ID ist erforderlich"]
    }
}
```

### cURL Example
```bash
curl -X POST "https://your-domain.com/api/payment/stripe/create-intent" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "X-Locale: de" \
  -d '{
    "amount": 25.50,
    "order_id": 123,
    "currency": "usd"
  }'
```

---

## 3. Create PayPal Order

### Endpoint
```
POST /payment/paypal/create-order
```

### Headers
```
Authorization: Bearer {token}
Content-Type: application/json
X-Locale: de
```

### Request Body
```json
{
    "amount": 25.50,
    "order_id": 123,
    "currency": "USD"
}
```

### Response (Success)
```json
{
    "status": "success",
    "data": {
        "paypal_order_id": "8XH12345ABC123456",
        "transaction_id": 789,
        "approval_url": "https://www.sandbox.paypal.com/checkoutnow?token=8XH12345ABC123456",
        "message": "PayPal-Bestellung erfolgreich erstellt"
    }
}
```

### Response (Error)
```json
{
    "status": "error",
    "message": "Diese Währung wird nicht unterstützt",
    "errors": {
        "currency": ["Diese Währung wird nicht unterstützt"]
    }
}
```

### cURL Example
```bash
curl -X POST "https://your-domain.com/api/payment/paypal/create-order" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "X-Locale: de" \
  -d '{
    "amount": 25.50,
    "order_id": 123,
    "currency": "USD"
  }'
```

---

## 4. Stripe Webhook (External Call)

### Endpoint
```
POST /webhook/stripe
```

### Headers
```
Stripe-Signature: t=1234567890,v1=signature...
Content-Type: application/json
```

### Request Body
```json
{
    "id": "evt_1N4d5K2eZvKYlo2C",
    "object": "event",
    "type": "payment_intent.succeeded",
    "data": {
        "object": {
            "id": "pi_3N4d5K2eZvKYlo2CaFc5dK",
            "metadata": {
                "order_id": "123",
                "transaction_id": "456"
            }
        }
    }
}
```

### Response
```json
{
    "status": "success"
}
```

---

## 5. PayPal Webhook (External Call)

### Endpoint
```
POST /webhook/paypal
```

### Headers
```
Content-Type: application/json
```

### Request Body
```json
{
    "event_type": "PAYMENT.CAPTURE.COMPLETED",
    "resource": {
        "supplementary_data": {
            "related_ids": {
                "order_id": "8XH12345ABC123456"
            }
        }
    }
}
```

### Response
```json
{
    "status": "success"
}
```

---

## Error Responses

### Validation Error (422)
```json
{
    "status": "error",
    "message": "Validierung fehlgeschlagen",
    "errors": {
        "amount": ["Betrag muss größer als 0 sein"],
        "order_id": ["Bestellung existiert nicht"],
        "currency": ["Währung muss 3 Zeichen haben"]
    }
}
```

### Authentication Error (401)
```json
{
    "status": "error",
    "message": "Unauthenticated"
}
```

### Server Error (500)
```json
{
    "status": "error",
    "message": "Fehler beim Erstellen des Stripe PaymentIntent",
    "errors": {
        "general": "Your card was declined."
    }
}
```

---

## Test Data

### Test Orders
Create test orders first via existing order API:
```bash
# Create test order
curl -X POST "https://your-domain.com/api/user/orders" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "phone": "1234567890", 
    "address": "Test Address",
    "total_amount": 25.50,
    "payment_method": "stripe"
  }'
```

### Stripe Test Cards
- **Success**: `4242424242424242`
- **Decline**: `4000000000000002`  
- **Insufficient funds**: `4000000000009995`

### PayPal Test
- Use PayPal Sandbox accounts
- Set `PAYPAL_MODE=sandbox` in environment

---

## Frontend Integration Example

### Stripe Payment Flow
```javascript
// 1. Create payment intent
const response = await fetch('/api/payment/stripe/create-intent', {
    method: 'POST',
    headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
        'X-Locale': 'de'
    },
    body: JSON.stringify({
        amount: 25.50,
        order_id: 123,
        currency: 'usd'
    })
});

const { client_secret, publishable_key } = await response.json();

// 2. Initialize Stripe and confirm payment
const stripe = Stripe(publishable_key);
const { error } = await stripe.confirmCardPayment(client_secret, {
    payment_method: {
        card: cardElement,
    }
});
```

### PayPal Payment Flow
```javascript
// 1. Create PayPal order
const response = await fetch('/api/payment/paypal/create-order', {
    method: 'POST',
    headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        amount: 25.50,
        order_id: 123,
        currency: 'USD'
    })
});

const { approval_url } = await response.json();

// 2. Redirect to PayPal
window.location.href = approval_url;
```

---

## Postman Collection

Import this collection for easy testing:

```json
{
    "info": {
        "name": "MamiViet Payment API",
        "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
    },
    "variable": [
        {
            "key": "base_url",
            "value": "https://your-domain.com/api"
        },
        {
            "key": "token",
            "value": "your_bearer_token"
        }
    ],
    "item": [
        {
            "name": "Get Payment Gateways",
            "request": {
                "method": "GET",
                "url": "{{base_url}}/payment/gateways",
                "header": [
                    {
                        "key": "X-Locale",
                        "value": "de"
                    }
                ]
            }
        },
        {
            "name": "Create Stripe Payment Intent",
            "request": {
                "method": "POST",
                "url": "{{base_url}}/payment/stripe/create-intent",
                "header": [
                    {
                        "key": "Authorization",
                        "value": "Bearer {{token}}"
                    },
                    {
                        "key": "Content-Type",
                        "value": "application/json"
                    },
                    {
                        "key": "X-Locale",
                        "value": "de"
                    }
                ],
                "body": {
                    "mode": "raw",
                    "raw": "{\n    \"amount\": 25.50,\n    \"order_id\": 123,\n    \"currency\": \"usd\"\n}"
                }
            }
        },
        {
            "name": "Create PayPal Order", 
            "request": {
                "method": "POST",
                "url": "{{base_url}}/payment/paypal/create-order",
                "header": [
                    {
                        "key": "Authorization",
                        "value": "Bearer {{token}}"
                    },
                    {
                        "key": "Content-Type",
                        "value": "application/json"
                    },
                    {
                        "key": "X-Locale",
                        "value": "de"
                    }
                ],
                "body": {
                    "mode": "raw",
                    "raw": "{\n    \"amount\": 25.50,\n    \"order_id\": 123,\n    \"currency\": \"USD\"\n}"
                }
            }
        }
    ]
}
```