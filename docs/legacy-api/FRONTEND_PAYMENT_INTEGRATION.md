# Frontend Payment Integration Requirements

## Overview
This document outlines the requirements for integrating payment functionality into the ReactJS frontend for the MamiViet restaurant ordering system. The frontend needs to support multiple payment methods (Cash, Stripe, PayPal) and handle the complete payment flow.

## API Endpoints Summary

### Payment Gateway APIs
- `GET /api/payment/gateways` - Get available payment methods
- `POST /api/payment/stripe/create-intent` - Create Stripe payment intent (requires auth)
- `POST /api/payment/paypal/create-order` - Create PayPal order (requires auth)
- `POST /api/webhook/stripe` - Stripe webhook (backend only, no auth)
- `POST /api/webhook/paypal` - PayPal webhook (backend only, no auth)

### Order APIs (Updated)
- `POST /api/user/orders` - Create order with payment_method field
- `PUT /api/user/orders/{order_code}` - Update order (can include payment_method)
- `GET /api/user/orders/{order_code}` - Get order details with payment info

## 1. Payment Gateway Discovery

### Implementation
Create a service to fetch available payment methods:

```javascript
// services/paymentService.js
export const getAvailableGateways = async () => {
    try {
        const response = await apiClient.get('/payment/gateways');
        return response.data.data; // Array of payment gateways
    } catch (error) {
        throw new Error('Failed to fetch payment gateways');
    }
};
```

### Expected Response
```json
{
    "status": "success",
    "data": {
        "gateways": [
            {
                "name": "stripe",
                "supported_currencies": ["USD", "EUR"]
            },
            {
                "name": "paypal",
                "supported_currencies": ["USD"]
            }
        ],
        "message": "Payment gateways retrieved successfully"
    }
}
```

### Usage in Component
```jsx
// components/PaymentMethods.jsx
const [paymentMethods, setPaymentMethods] = useState([]);
const [loading, setLoading] = useState(true);

useEffect(() => {
    const fetchPaymentMethods = async () => {
        try {
            const response = await getAvailableGateways();
            const gateways = response.data.gateways;
            // Add cash as default option
            setPaymentMethods([
                { name: 'cash', label: t('payment.method.cash') },
                ...gateways.map(gateway => ({
                    name: gateway.name,
                    label: t(`payment.method.${gateway.name}`),
                    currencies: gateway.supported_currencies
                }))
            ]);
        } catch (error) {
            console.error('Failed to load payment methods:', error);
            // Fallback to cash only
            setPaymentMethods([{ name: 'cash', label: t('payment.method.cash') }]);
        } finally {
            setLoading(false);
        }
    };

    fetchPaymentMethods();
}, []);
```

## 2. Updated OrderService Frontend

### Service Implementation
```javascript
// services/orderService.js
export const createOrder = async (orderData) => {
    try {
        // Validate payment_method matches backend enum values
        const validPaymentMethods = ['cash', 'stripe', 'paypal'];
        if (orderData.payment_method && !validPaymentMethods.includes(orderData.payment_method)) {
            throw new Error('Invalid payment method');
        }

        const response = await apiClient.post('/user/orders', {
            type: orderData.type, // 'delivery' | 'onsite'
            name: orderData.name,
            phone: orderData.phone,
            email: orderData.email,
            address: orderData.address, // required if type is 'delivery'
            note: orderData.note,
            discount_code: orderData.discount_code,
            payment_method: orderData.payment_method || 'cash', // default to cash
            items: orderData.items // [{ menu_item_id, quantity }]
        });

        return response.data.data;
    } catch (error) {
        if (error.response?.data?.errors) {
            throw new Error(Object.values(error.response.data.errors).flat().join(', '));
        }
        throw new Error('Failed to create order');
    }
};

export const updateOrder = async (orderCode, updateData) => {
    try {
        const response = await apiClient.put(`/user/orders/${orderCode}`, updateData);
        return response.data.data;
    } catch (error) {
        throw new Error('Failed to update order');
    }
};
```

### Order Creation Component
```jsx
// components/OrderForm.jsx
const OrderForm = ({ cartItems, onOrderCreated }) => {
    const [paymentMethod, setPaymentMethod] = useState('cash');
    const [paymentMethods, setPaymentMethods] = useState([]);
    const [orderData, setOrderData] = useState({
        type: 'delivery',
        name: '',
        phone: '',
        email: '',
        address: '',
        note: '',
        discount_code: '',
        payment_method: 'cash'
    });

    const handleSubmit = async (e) => {
        e.preventDefault();

        try {
            const order = await createOrder({
                ...orderData,
                payment_method: paymentMethod,
                items: cartItems.map(item => ({
                    menu_item_id: item.id,
                    quantity: item.quantity
                }))
            });

            // Handle different payment methods
            switch (paymentMethod) {
                case 'cash':
                    onOrderCreated(order);
                    break;
                case 'stripe':
                    await handleStripePayment(order);
                    break;
                case 'paypal':
                    await handlePayPalPayment(order);
                    break;
                default:
                    onOrderCreated(order);
            }
        } catch (error) {
            console.error('Order creation failed:', error);
            alert(error.message);
        }
    };

    return (
        <form onSubmit={handleSubmit}>
            {/* Order form fields */}

            {/* Payment Method Selection */}
            <div className="payment-methods">
                <h3>Payment Method</h3>
                {paymentMethods.map(method => (
                    <label key={method.name}>
                        <input
                            type="radio"
                            value={method.name}
                            checked={paymentMethod === method.name}
                            onChange={(e) => setPaymentMethod(e.target.value)}
                        />
                        {method.label}
                    </label>
                ))}
            </div>

            <button type="submit">Create Order</button>
        </form>
    );
};
```

## 3. Payment Processing Flow

### 3.1 Cash Payment
**Flow**: Simple order creation → Order completed
- No additional payment processing required
- Order is created with `payment_method: 'cash'` and `payment_status: 'pending'`
- Admin handles payment confirmation manually

```javascript
// No additional processing needed for cash
const handleCashPayment = (order) => {
    // Redirect to order confirmation
    router.push(`/orders/${order.order_code}`);
};
```

### 3.2 Stripe Payment
**Flow**: Create Order → Create Payment Intent → Handle Payment → Webhook Updates Order

#### Step 1: Create Stripe Payment Intent
```javascript
// services/stripeService.js
export const createStripePayment = async (orderId, amount, currency = 'USD') => {
    try {
        const response = await apiClient.post('/payment/stripe/create-intent', {
            order_id: orderId,
            amount: amount,
            currency: currency.toLowerCase()
        });
        return response.data;
    } catch (error) {
        if (error.response?.data?.errors) {
            throw new Error(Object.values(error.response.data.errors).flat().join(', '));
        }
        throw new Error(error.response?.data?.message || 'Failed to create Stripe payment');
    }
};
```

#### Step 2: Process Payment with Stripe Elements
```javascript
// components/StripePayment.jsx
import { Elements, CardElement, useStripe, useElements } from '@stripe/react-stripe-js';
import { loadStripe } from '@stripe/stripe-js';

const stripePromise = loadStripe('pk_test_...'); // From payment gateway config

const StripePaymentForm = ({ order, clientSecret, onSuccess, onError }) => {
    const stripe = useStripe();
    const elements = useElements();

    const handleSubmit = async (event) => {
        event.preventDefault();

        if (!stripe || !elements) {
            return;
        }

        const card = elements.getElement(CardElement);

        const { error, paymentIntent } = await stripe.confirmCardPayment(clientSecret, {
            payment_method: {
                card: card,
                billing_details: {
                    name: order.name,
                    email: order.email,
                },
            }
        });

        if (error) {
            onError(error.message);
        } else {
            // Payment succeeded
            onSuccess(paymentIntent);
        }
    };

    return (
        <form onSubmit={handleSubmit}>
            <CardElement />
            <button type="submit" disabled={!stripe}>
                Pay ${order.total_amount}
            </button>
        </form>
    );
};

// Main Stripe Payment Component
const StripePayment = ({ order, onSuccess, onError }) => {
    const [clientSecret, setClientSecret] = useState('');
    const [publishableKey, setPublishableKey] = useState('');

    useEffect(() => {
        const initializePayment = async () => {
            try {
                const response = await createStripePayment(
                    order.id,
                    order.total_amount,
                    'USD'
                );
                const paymentData = response.data;
                setClientSecret(paymentData.client_secret);
                setPublishableKey(paymentData.publishable_key);
            } catch (error) {
                onError(error.message);
            }
        };

        initializePayment();
    }, [order]);

    if (!clientSecret || !publishableKey) {
        return <div>Loading payment form...</div>;
    }

    const stripePromise = loadStripe(publishableKey);

    return (
        <Elements stripe={stripePromise}>
            <StripePaymentForm
                order={order}
                clientSecret={clientSecret}
                onSuccess={onSuccess}
                onError={onError}
            />
        </Elements>
    );
};
```

#### Step 3: Handle Stripe Payment
```javascript
const handleStripePayment = async (order) => {
    return new Promise((resolve, reject) => {
        const onSuccess = (paymentIntent) => {
            // Payment successful, webhook will update order status
            alert('Payment successful! Your order is being processed.');
            router.push(`/orders/${order.order_code}`);
            resolve(paymentIntent);
        };

        const onError = (error) => {
            alert(`Payment failed: ${error}`);
            reject(new Error(error));
        };

        // Show Stripe payment modal/component
        showStripePaymentModal(order, onSuccess, onError);
    });
};
```

### 3.3 PayPal Payment
**Flow**: Create Order → Create PayPal Order → Redirect to PayPal → Handle Return → Webhook Updates Order

#### Step 1: Create PayPal Order
```javascript
// services/paypalService.js
export const createPayPalOrder = async (orderId, amount, currency = 'USD') => {
    try {
        const response = await apiClient.post('/payment/paypal/create-order', {
            order_id: orderId,
            amount: amount,
            currency: currency.toUpperCase()
        });
        return response.data;
    } catch (error) {
        if (error.response?.data?.errors) {
            throw new Error(Object.values(error.response.data.errors).flat().join(', '));
        }
        throw new Error(error.response?.data?.message || 'Failed to create PayPal order');
    }
};
```

#### Step 2: Handle PayPal Redirect
```javascript
const handlePayPalPayment = async (order) => {
    try {
        const paypalData = await createPayPalOrder(
            order.id,
            order.total_amount,
            'USD'
        );

        // Redirect to PayPal for approval  
        const paypalData = response.data;
        window.location.href = paypalData.approval_url;
    } catch (error) {
        alert(`PayPal payment failed: ${error.message}`);
        throw error;
    }
};
```

#### Step 3: Handle PayPal Return
```javascript
// pages/PaymentSuccess.jsx (Handle return from PayPal)
const PaymentSuccess = () => {
    const [searchParams] = useSearchParams();
    const paypalOrderId = searchParams.get('token');

    useEffect(() => {
        if (paypalOrderId) {
            // PayPal payment approved, webhook will handle capture and order update
            alert('PayPal payment approved! Your order is being processed.');

            // Optionally poll order status or redirect to order page
            const orderCode = localStorage.getItem('pendingOrderCode');
            if (orderCode) {
                router.push(`/orders/${orderCode}`);
                localStorage.removeItem('pendingOrderCode');
            }
        }
    }, [paypalOrderId]);

    return (
        <div>
            <h2>Payment Successful!</h2>
            <p>Your payment has been processed successfully.</p>
        </div>
    );
};

// pages/PaymentCancel.jsx (Handle cancel from PayPal)
const PaymentCancel = () => {
    return (
        <div>
            <h2>Payment Cancelled</h2>
            <p>Your payment was cancelled. You can try again.</p>
            <button onClick={() => router.push('/cart')}>
                Back to Cart
            </button>
        </div>
    );
};
```

## 4. Order Status Polling

Since webhooks update payment status asynchronously, implement polling to check order status:

```javascript
// hooks/useOrderStatus.js
export const useOrderStatus = (orderCode) => {
    const [order, setOrder] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const pollOrderStatus = async () => {
            try {
                const response = await apiClient.get(`/user/orders/${orderCode}`);
                const updatedOrder = response.data.data;
                setOrder(updatedOrder);

                // Stop polling if payment is completed or failed
                if (['completed', 'failed', 'refunded'].includes(updatedOrder.payment_status)) {
                    setLoading(false);
                    return;
                }

                // Continue polling every 3 seconds
                setTimeout(pollOrderStatus, 3000);
            } catch (error) {
                console.error('Failed to fetch order status:', error);
                setLoading(false);
            }
        };

        if (orderCode) {
            pollOrderStatus();
        }
    }, [orderCode]);

    return { order, loading };
};

// Usage in component
const OrderDetails = ({ orderCode }) => {
    const { order, loading } = useOrderStatus(orderCode);

    if (loading) {
        return <div>Loading order status...</div>;
    }

    return (
        <div>
            <h2>Order {order.order_code}</h2>
            <p>Payment Status: {t(`payment.status.${order.payment_status}`)}</p>
            <p>Payment Method: {t(`payment.method.${order.payment_method}`)}</p>
            <p>Total: ${order.total_amount}</p>

            {order.payment_status === 'pending' && order.payment_method !== 'cash' && (
                <div className="alert alert-info">
                    <p>Your payment is being processed. Please wait...</p>
                </div>
            )}

            {order.payment_status === 'completed' && (
                <div className="alert alert-success">
                    <p>Payment completed successfully!</p>
                </div>
            )}

            {order.payment_status === 'failed' && (
                <div className="alert alert-danger">
                    <p>Payment failed. Please try again or contact support.</p>
                </div>
            )}
        </div>
    );
};
```

## 5. Error Handling

### Payment Errors
```javascript
// utils/paymentErrors.js
export const handlePaymentError = (error, paymentMethod) => {
    const errorMessages = {
        'stripe': {
            'card_declined': 'Your card was declined. Please try another card.',
            'insufficient_funds': 'Insufficient funds on your card.',
            'invalid_cvc': 'Invalid security code.',
            'expired_card': 'Your card has expired.',
            'default': 'Payment failed. Please check your card details and try again.'
        },
        'paypal': {
            'INSTRUMENT_DECLINED': 'PayPal payment was declined. Please try another payment method.',
            'PAYER_CANNOT_PAY': 'PayPal account cannot be used for this payment.',
            'default': 'PayPal payment failed. Please try again.'
        },
        'default': 'Payment processing failed. Please try again.'
    };

    const methodErrors = errorMessages[paymentMethod] || {};
    return methodErrors[error.code] || methodErrors.default || errorMessages.default;
};
```

### Network Errors
```javascript
// utils/apiClient.js
const handleApiError = (error) => {
    if (error.response) {
        // Server responded with error status
        const { status, data } = error.response;

        switch (status) {
            case 422:
                // Validation errors
                throw new Error(Object.values(data.errors).flat().join(', '));
            case 404:
                throw new Error('Resource not found');
            case 500:
                throw new Error('Server error. Please try again later.');
            default:
                throw new Error(data.message || 'Something went wrong');
        }
    } else if (error.request) {
        // Network error
        throw new Error('Network error. Please check your connection.');
    } else {
        // Other errors
        throw new Error(error.message || 'Something went wrong');
    }
};
```

## 6. Testing Considerations

### Payment Testing
- **Stripe**: Use test card numbers (4242 4242 4242 4242)
- **PayPal**: Use PayPal sandbox accounts
- **Cash**: Test order flow without payment processing

### Error Scenarios
- Network failures during payment
- Payment gateway timeouts
- Invalid payment methods
- Insufficient funds
- Cancelled payments

## 7. Security Considerations

### Frontend Security
- Never store sensitive payment information
- Use HTTPS for all payment-related requests
- Validate payment amounts on both frontend and backend
- Implement proper error handling without exposing sensitive data

### API Security
- All payment endpoints require authentication
- Webhook endpoints validate signatures
- Payment amounts are validated server-side
- Order ownership is verified before payment processing

## 8. Dependencies

### Required Packages
```json
{
    "dependencies": {
        "@stripe/stripe-js": "^2.0.0",
        "@stripe/react-stripe-js": "^2.0.0",
        "axios": "^1.0.0"
    }
}
```

### Environment Variables
```env
# Frontend .env
REACT_APP_API_BASE_URL=http://localhost:8000/api
REACT_APP_STRIPE_PUBLISHABLE_KEY=pk_test_... # Will be fetched from API
```

## 9. Implementation Checklist

- [ ] Create payment service for gateway discovery
- [ ] Update order service to include payment_method
- [ ] Implement Stripe payment component with Elements
- [ ] Implement PayPal redirect flow
- [ ] Create payment success/cancel pages
- [ ] Add order status polling
- [ ] Implement error handling
- [ ] Add payment method selection to order form
- [ ] Test all payment flows
- [ ] Add loading states and user feedback
- [ ] Implement proper validation
- [ ] Add translation support for payment messages

## 10. Future Enhancements

- Payment method icons and branding
- Saved payment methods for logged-in users
- Partial refunds support
- Payment analytics and reporting
- Multi-currency support
- Mobile payment optimizations (Apple Pay, Google Pay)
