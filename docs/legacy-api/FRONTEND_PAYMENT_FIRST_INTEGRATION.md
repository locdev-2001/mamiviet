# Frontend Payment-First Integration Guide

## Overview
This document outlines the NEW **Payment-First** approach for integrating payment functionality into the ReactJS frontend. In this flow, payment is processed BEFORE order creation, ensuring clean data and better user experience.

## 🔄 New Flow Summary

### **Old Flow (Deprecated):**
```
Cart → Create Order → Payment (with order_id) → Webhook updates Order
```

### **New Payment-First Flow:**
```
Cart → Payment (amount, currency) → Payment Success → Create Order (with payment data)
```

## API Endpoints Summary

### ⚠️ **BREAKING CHANGES - APIs Updated**

#### **Payment Gateway APIs**
- `GET /api/payment/gateways` - Get available payment methods (unchanged)
- `POST /api/payment/stripe/create-intent` - ⚡ **UPDATED** - No longer requires order_id
- `POST /api/payment/paypal/create-order` - ⚡ **UPDATED** - No longer requires order_id  
- `POST /api/webhook/stripe` - Stripe webhook (backend only, no auth)
- `POST /api/webhook/paypal` - PayPal webhook (backend only, no auth)

#### **Order APIs (Updated)**
- `POST /api/user/orders` - ⚡ **UPDATED** - Now accepts optional payment_data field
- `PUT /api/user/orders/{order_code}` - Update order (unchanged)
- `GET /api/user/orders/{order_code}` - Get order details (unchanged)

### **Key Changes:**

#### **Before (OLD):**
```javascript
// ❌ Required order_id - Don't use anymore
POST /api/payment/stripe/create-intent
{
  "amount": 100.00,
  "order_id": 123,     // REMOVED
  "currency": "usd"
}
```

#### **After (NEW):**
```javascript  
// ✅ Simplified - Use this
POST /api/payment/stripe/create-intent
{
  "amount": 100.00,
  "currency": "usd"    // optional, defaults to 'usd'
}
```

## 1. Payment Processing Flow

### Frontend Implementation Strategy

```javascript
// services/checkoutService.js
export class CheckoutService {
  static async processCheckout(cartData, customerInfo, paymentMethod) {
    try {
      // Step 1: Calculate total from cart
      const total = this.calculateCartTotal(cartData);
      
      // Step 2: Process payment based on method
      let paymentResult = null;
      
      switch (paymentMethod) {
        case 'stripe':
          paymentResult = await this.processStripePayment(total);
          break;
        case 'paypal':  
          paymentResult = await this.processPayPalPayment(total);
          break;
        case 'cash':
          // No payment processing needed
          break;
        default:
          throw new Error('Invalid payment method');
      }
      
      // Step 3: Create order with payment data
      const order = await this.createOrderWithPayment(
        cartData, 
        customerInfo, 
        paymentMethod, 
        paymentResult
      );
      
      return order;
      
    } catch (error) {
      console.error('Checkout failed:', error);
      throw error;
    }
  }
}
```

### 1.1 Cash Payment (No Processing)

```javascript
// No payment processing needed - direct order creation
const handleCashCheckout = async (cartData, customerInfo) => {
  try {
    const orderData = {
      ...customerInfo,
      payment_method: 'cash',
      items: cartData.items
    };
    
    const order = await createOrder(orderData);
    
    // Redirect to success page
    router.push(`/orders/${order.order_code}`);
    return order;
    
  } catch (error) {
    throw new Error(`Cash checkout failed: ${error.message}`);
  }
};
```

### 1.2 Stripe Payment Processing

#### Step 1: Create Payment Intent
```javascript
// services/stripeService.js
export const processStripePayment = async (amount, currency = 'USD') => {
  try {
    // Create payment intent
    const response = await apiClient.post('/payment/stripe/create-intent', {
      amount: amount,
      currency: currency.toLowerCase()
    });
    
    const { client_secret, payment_intent_id, publishable_key } = response.data;
    
    // Initialize Stripe with publishable key
    const stripe = await loadStripe(publishable_key);
    
    // Show payment form and process
    const paymentResult = await showStripePaymentForm(stripe, client_secret);
    
    if (paymentResult.error) {
      throw new Error(paymentResult.error.message);
    }
    
    return {
      success: true,
      gateway: 'stripe',
      transaction_id: payment_intent_id,
      currency: currency.toUpperCase()
    };
    
  } catch (error) {
    throw new Error(`Stripe payment failed: ${error.message}`);
  }
};

const showStripePaymentForm = (stripe, clientSecret) => {
  return new Promise((resolve, reject) => {
    // Show Stripe Elements form
    const elements = stripe.elements();
    const cardElement = elements.create('card');
    
    // Mount card element to DOM
    cardElement.mount('#card-element');
    
    // Handle form submission
    const form = document.getElementById('payment-form');
    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      
      const { error, paymentIntent } = await stripe.confirmCardPayment(clientSecret, {
        payment_method: {
          card: cardElement,
          billing_details: {
            name: customerInfo.name,
            email: customerInfo.email,
          },
        }
      });
      
      if (error) {
        reject(error);
      } else {
        resolve(paymentIntent);
      }
    });
  });
};
```

#### Step 2: Stripe Payment Component
```jsx
// components/StripeCheckout.jsx
import React, { useState, useEffect } from 'react';
import { Elements, CardElement, useStripe, useElements } from '@stripe/react-stripe-js';
import { loadStripe } from '@stripe/stripe-js';

const StripePaymentForm = ({ amount, onSuccess, onError }) => {
  const stripe = useStripe();
  const elements = useElements();
  const [clientSecret, setClientSecret] = useState('');
  const [processing, setProcessing] = useState(false);

  useEffect(() => {
    // Create payment intent when component mounts
    const createPaymentIntent = async () => {
      try {
        const response = await apiClient.post('/payment/stripe/create-intent', {
          amount: amount,
          currency: 'usd'
        });
        
        setClientSecret(response.data.client_secret);
      } catch (error) {
        onError(error.message);
      }
    };

    createPaymentIntent();
  }, [amount, onError]);

  const handleSubmit = async (event) => {
    event.preventDefault();

    if (!stripe || !elements || !clientSecret) {
      return;
    }

    setProcessing(true);

    const card = elements.getElement(CardElement);

    const { error, paymentIntent } = await stripe.confirmCardPayment(clientSecret, {
      payment_method: {
        card: card,
        billing_details: {
          name: 'Customer Name', // Get from form
          email: 'customer@email.com', // Get from form
        },
      }
    });

    setProcessing(false);

    if (error) {
      onError(error.message);
    } else {
      // Payment successful - return payment data
      onSuccess({
        gateway: 'stripe',
        transaction_id: paymentIntent.id,
        currency: 'USD',
        status: 'completed'
      });
    }
  };

  if (!clientSecret) {
    return <div>Loading payment form...</div>;
  }

  return (
    <form onSubmit={handleSubmit}>
      <CardElement 
        options={{
          style: {
            base: {
              fontSize: '16px',
              color: '#424770',
              '::placeholder': {
                color: '#aab7c4',
              },
            },
          },
        }}
      />
      
      <button 
        type="submit" 
        disabled={!stripe || processing}
        className="payment-button"
      >
        {processing ? 'Processing...' : `Pay $${amount}`}
      </button>
    </form>
  );
};

// Main Stripe Checkout Component
const StripeCheckout = ({ amount, onSuccess, onError }) => {
  const [stripePromise, setStripePromise] = useState(null);

  useEffect(() => {
    // Get publishable key from payment gateways
    const initializeStripe = async () => {
      try {
        const response = await apiClient.get('/payment/gateways');
        const stripeGateway = response.data.gateways.find(g => g.name === 'stripe');
        
        if (stripeGateway?.publishable_key) {
          setStripePromise(loadStripe(stripeGateway.publishable_key));
        }
      } catch (error) {
        onError('Failed to initialize Stripe');
      }
    };

    initializeStripe();
  }, [onError]);

  if (!stripePromise) {
    return <div>Loading Stripe...</div>;
  }

  return (
    <Elements stripe={stripePromise}>
      <StripePaymentForm 
        amount={amount}
        onSuccess={onSuccess}
        onError={onError}
      />
    </Elements>
  );
};

export default StripeCheckout;
```

### 1.3 PayPal Payment Processing

```javascript
// services/paypalService.js
export const processPayPalPayment = async (amount, currency = 'USD') => {
  try {
    // Create PayPal order
    const response = await apiClient.post('/payment/paypal/create-order', {
      amount: amount,
      currency: currency.toUpperCase()
    });
    
    const { paypal_order_id, approval_url } = response.data;
    
    // Store order ID for return handling
    localStorage.setItem('paypal_order_id', paypal_order_id);
    localStorage.setItem('cart_amount', amount.toString());
    
    // Redirect to PayPal for approval
    window.location.href = approval_url;
    
    // This won't execute due to redirect, but needed for type consistency
    return {
      success: true,
      gateway: 'paypal',
      transaction_id: paypal_order_id,
      currency: currency.toUpperCase()
    };
    
  } catch (error) {
    throw new Error(`PayPal payment failed: ${error.message}`);
  }
};

// Handle PayPal return
export const handlePayPalReturn = () => {
  const urlParams = new URLSearchParams(window.location.search);
  const token = urlParams.get('token'); // PayPal order ID
  const payerId = urlParams.get('PayerID');
  
  if (token && payerId) {
    // Payment approved
    const storedOrderId = localStorage.getItem('paypal_order_id');
    const amount = localStorage.getItem('cart_amount');
    
    if (token === storedOrderId) {
      // Clean up storage
      localStorage.removeItem('paypal_order_id');
      localStorage.removeItem('cart_amount');
      
      return {
        success: true,
        gateway: 'paypal',
        transaction_id: token,
        currency: 'USD',
        payer_id: payerId
      };
    }
  }
  
  return null;
};
```

#### PayPal Return Handling Pages
```jsx
// pages/PaymentSuccess.jsx
import React, { useEffect, useState } from 'react';
import { useRouter } from 'next/router';

const PaymentSuccess = () => {
  const router = useRouter();
  const [processing, setProcessing] = useState(true);

  useEffect(() => {
    const handlePayPalReturn = async () => {
      try {
        const paymentResult = handlePayPalReturn();
        
        if (paymentResult?.success) {
          // Payment successful - get cart and customer data from storage
          const cartData = JSON.parse(localStorage.getItem('checkout_cart') || '{}');
          const customerInfo = JSON.parse(localStorage.getItem('checkout_customer') || '{}');
          
          // Create order with payment data
          const order = await createOrderWithPayment(
            cartData,
            customerInfo,
            'paypal',
            paymentResult
          );
          
          // Clean up storage
          localStorage.removeItem('checkout_cart');
          localStorage.removeItem('checkout_customer');
          
          // Redirect to order confirmation
          router.push(`/orders/${order.order_code}?payment_success=true`);
          
        } else {
          throw new Error('PayPal payment verification failed');
        }
        
      } catch (error) {
        console.error('PayPal return handling failed:', error);
        router.push('/checkout?error=payment_failed');
      } finally {
        setProcessing(false);
      }
    };

    handlePayPalReturn();
  }, [router]);

  if (processing) {
    return (
      <div className="payment-processing">
        <h2>Processing your payment...</h2>
        <p>Please wait while we confirm your PayPal payment.</p>
      </div>
    );
  }

  return (
    <div className="payment-error">
      <h2>Payment Processing Failed</h2>
      <p>There was an issue processing your payment. Please try again.</p>
    </div>
  );
};

export default PaymentSuccess;

// pages/PaymentCancel.jsx
const PaymentCancel = () => {
  const router = useRouter();

  useEffect(() => {
    // Clean up any stored data
    localStorage.removeItem('paypal_order_id');
    localStorage.removeItem('cart_amount');
    
    // Redirect back to checkout after 3 seconds
    setTimeout(() => {
      router.push('/checkout?cancelled=true');
    }, 3000);
  }, [router]);

  return (
    <div className="payment-cancelled">
      <h2>Payment Cancelled</h2>
      <p>Your PayPal payment was cancelled. Redirecting back to checkout...</p>
    </div>
  );
};
```

## 2. Order Creation with Payment Data

### Updated Order Service
```javascript
// services/orderService.js
export const createOrderWithPayment = async (cartData, customerInfo, paymentMethod, paymentResult = null) => {
  try {
    const orderData = {
      // Customer information
      type: customerInfo.type,
      name: customerInfo.name,
      phone: customerInfo.phone,
      email: customerInfo.email,
      address: customerInfo.address,
      note: customerInfo.note,
      discount_code: customerInfo.discount_code,
      
      // Cart items
      items: cartData.items.map(item => ({
        menu_item_id: item.id,
        quantity: item.quantity
      })),
      
      // Payment information
      payment_method: paymentMethod
    };

    // Add payment data if payment was processed
    if (paymentResult?.success && paymentMethod !== 'cash') {
      orderData.payment_data = {
        method: paymentMethod,
        gateway: paymentResult.gateway,
        transaction_id: paymentResult.transaction_id,
        currency: paymentResult.currency
      };
    }

    const response = await apiClient.post('/user/orders', orderData);
    return response.data.data;
    
  } catch (error) {
    if (error.response?.data?.errors) {
      throw new Error(Object.values(error.response.data.errors).flat().join(', '));
    }
    throw new Error(error.response?.data?.message || 'Failed to create order');
  }
};
```

## 3. Complete Checkout Component

```jsx
// components/Checkout.jsx
import React, { useState, useEffect } from 'react';
import StripeCheckout from './StripeCheckout';

const Checkout = ({ cartItems, onOrderCreated }) => {
  const [paymentMethod, setPaymentMethod] = useState('cash');
  const [paymentMethods, setPaymentMethods] = useState([]);
  const [customerInfo, setCustomerInfo] = useState({
    type: 'delivery',
    name: '',
    phone: '',
    email: '',
    address: '',
    note: '',
    discount_code: ''
  });
  const [processing, setProcessing] = useState(false);
  const [total, setTotal] = useState(0);

  useEffect(() => {
    // Fetch available payment methods
    const fetchPaymentMethods = async () => {
      try {
        const response = await apiClient.get('/payment/gateways');
        const gateways = response.data.gateways;
        
        setPaymentMethods([
          { name: 'cash', label: 'Cash on Delivery' },
          ...gateways.map(gateway => ({
            name: gateway.name,
            label: gateway.name === 'stripe' ? 'Credit Card' : 'PayPal'
          }))
        ]);
      } catch (error) {
        console.error('Failed to load payment methods:', error);
        setPaymentMethods([{ name: 'cash', label: 'Cash on Delivery' }]);
      }
    };

    // Calculate total
    const calculateTotal = () => {
      const itemsTotal = cartItems.reduce((sum, item) => 
        sum + (item.price * item.quantity), 0
      );
      setTotal(itemsTotal);
    };

    fetchPaymentMethods();
    calculateTotal();
  }, [cartItems]);

  const handleCheckout = async () => {
    if (processing) return;
    
    setProcessing(true);
    
    try {
      let paymentResult = null;
      
      // Process payment based on selected method
      switch (paymentMethod) {
        case 'stripe':
          // Stripe payment will be handled by StripeCheckout component
          return;
          
        case 'paypal':
          // Store cart and customer data for PayPal return
          localStorage.setItem('checkout_cart', JSON.stringify({ items: cartItems }));
          localStorage.setItem('checkout_customer', JSON.stringify(customerInfo));
          
          // Process PayPal payment (will redirect)
          await processPayPalPayment(total);
          return;
          
        case 'cash':
        default:
          // Create order directly for cash payments
          const order = await createOrderWithPayment(
            { items: cartItems },
            customerInfo,
            'cash'
          );
          
          onOrderCreated(order);
          return;
      }
      
    } catch (error) {
      console.error('Checkout failed:', error);
      alert(`Checkout failed: ${error.message}`);
    } finally {
      setProcessing(false);
    }
  };

  const handleStripeSuccess = async (paymentResult) => {
    try {
      setProcessing(true);
      
      const order = await createOrderWithPayment(
        { items: cartItems },
        customerInfo,
        'stripe',
        paymentResult
      );
      
      onOrderCreated(order);
      
    } catch (error) {
      console.error('Order creation failed:', error);
      alert(`Order creation failed: ${error.message}`);
    } finally {
      setProcessing(false);
    }
  };

  const handlePaymentError = (error) => {
    console.error('Payment failed:', error);
    alert(`Payment failed: ${error}`);
    setProcessing(false);
  };

  return (
    <div className="checkout-container">
      <div className="checkout-form">
        {/* Customer Information Form */}
        <div className="customer-info">
          <h3>Customer Information</h3>
          
          <div className="form-group">
            <label>Order Type</label>
            <select 
              value={customerInfo.type} 
              onChange={(e) => setCustomerInfo({...customerInfo, type: e.target.value})}
            >
              <option value="delivery">Delivery</option>
              <option value="onsite">Pickup</option>
            </select>
          </div>

          <div className="form-group">
            <label>Name *</label>
            <input
              type="text"
              value={customerInfo.name}
              onChange={(e) => setCustomerInfo({...customerInfo, name: e.target.value})}
              required
            />
          </div>

          <div className="form-group">
            <label>Phone *</label>
            <input
              type="tel"
              value={customerInfo.phone}
              onChange={(e) => setCustomerInfo({...customerInfo, phone: e.target.value})}
              required
            />
          </div>

          <div className="form-group">
            <label>Email</label>
            <input
              type="email"
              value={customerInfo.email}
              onChange={(e) => setCustomerInfo({...customerInfo, email: e.target.value})}
            />
          </div>

          {customerInfo.type === 'delivery' && (
            <div className="form-group">
              <label>Address *</label>
              <textarea
                value={customerInfo.address}
                onChange={(e) => setCustomerInfo({...customerInfo, address: e.target.value})}
                required
              />
            </div>
          )}

          <div className="form-group">
            <label>Note</label>
            <textarea
              value={customerInfo.note}
              onChange={(e) => setCustomerInfo({...customerInfo, note: e.target.value})}
            />
          </div>

          <div className="form-group">
            <label>Discount Code</label>
            <input
              type="text"
              value={customerInfo.discount_code}
              onChange={(e) => setCustomerInfo({...customerInfo, discount_code: e.target.value})}
            />
          </div>
        </div>

        {/* Payment Method Selection */}
        <div className="payment-methods">
          <h3>Payment Method</h3>
          
          {paymentMethods.map(method => (
            <label key={method.name} className="payment-method-option">
              <input
                type="radio"
                value={method.name}
                checked={paymentMethod === method.name}
                onChange={(e) => setPaymentMethod(e.target.value)}
              />
              <span>{method.label}</span>
            </label>
          ))}
        </div>

        {/* Order Summary */}
        <div className="order-summary">
          <h3>Order Summary</h3>
          
          {cartItems.map(item => (
            <div key={item.id} className="order-item">
              <span>{item.name} x {item.quantity}</span>
              <span>${(item.price * item.quantity).toFixed(2)}</span>
            </div>
          ))}
          
          <div className="total">
            <strong>Total: ${total.toFixed(2)}</strong>
          </div>
        </div>

        {/* Payment Processing */}
        <div className="payment-processing">
          {paymentMethod === 'stripe' ? (
            <StripeCheckout
              amount={total}
              onSuccess={handleStripeSuccess}
              onError={handlePaymentError}
            />
          ) : (
            <button
              onClick={handleCheckout}
              disabled={processing}
              className="checkout-button"
            >
              {processing ? 'Processing...' : 
               paymentMethod === 'paypal' ? 'Pay with PayPal' : 
               'Place Order'}
            </button>
          )}
        </div>
      </div>
    </div>
  );
};

export default Checkout;
```

## 4. Error Handling & Validation

### Payment Error Handling
```javascript
// utils/paymentErrorHandler.js
export const handlePaymentError = (error, paymentMethod) => {
  const errorMessages = {
    stripe: {
      card_declined: 'Your card was declined. Please try another payment method.',
      insufficient_funds: 'Insufficient funds. Please use another card.',
      invalid_cvc: 'Invalid security code. Please check and try again.',
      expired_card: 'Your card has expired. Please use another card.',
      processing_error: 'Payment processing error. Please try again.',
      default: 'Payment failed. Please check your card details and try again.'
    },
    paypal: {
      INSTRUMENT_DECLINED: 'PayPal payment declined. Please try another payment method.',
      PAYER_CANNOT_PAY: 'PayPal account cannot process this payment.',
      default: 'PayPal payment failed. Please try again.'
    },
    general: {
      network_error: 'Network error. Please check your connection and try again.',
      server_error: 'Server error. Please try again later.',
      default: 'Payment processing failed. Please try again.'
    }
  };

  const methodErrors = errorMessages[paymentMethod] || errorMessages.general;
  
  // Extract error code/type from error object
  let errorKey = 'default';
  if (error.code) {
    errorKey = error.code;
  } else if (error.type) {
    errorKey = error.type;
  } else if (error.message?.includes('network')) {
    errorKey = 'network_error';
  } else if (error.message?.includes('server')) {
    errorKey = 'server_error';
  }

  return methodErrors[errorKey] || methodErrors.default;
};
```

### Form Validation
```javascript
// utils/checkoutValidation.js
export const validateCustomerInfo = (customerInfo) => {
  const errors = {};

  if (!customerInfo.name?.trim()) {
    errors.name = 'Name is required';
  }

  if (!customerInfo.phone?.trim()) {
    errors.phone = 'Phone number is required';
  } else if (!/^\+?[\d\s\-\(\)]{10,}$/.test(customerInfo.phone)) {
    errors.phone = 'Please enter a valid phone number';
  }

  if (customerInfo.email && !/\S+@\S+\.\S+/.test(customerInfo.email)) {
    errors.email = 'Please enter a valid email address';
  }

  if (customerInfo.type === 'delivery' && !customerInfo.address?.trim()) {
    errors.address = 'Delivery address is required';
  }

  return {
    isValid: Object.keys(errors).length === 0,
    errors
  };
};

export const validateCart = (cartItems) => {
  if (!cartItems || cartItems.length === 0) {
    return {
      isValid: false,
      error: 'Cart is empty'
    };
  }

  const hasInvalidItems = cartItems.some(item => 
    !item.id || !item.quantity || item.quantity <= 0 || !item.price
  );

  if (hasInvalidItems) {
    return {
      isValid: false,
      error: 'Cart contains invalid items'
    };
  }

  return { isValid: true };
};
```

## 5. Testing Guide

### Test Scenarios

#### Cash Payment
1. Fill customer information
2. Select "Cash on Delivery"
3. Click "Place Order"
4. Verify order created with `payment_status: 'pending'`
5. Verify no transaction record created

#### Stripe Payment
1. Fill customer information  
2. Select "Credit Card"
3. Use test card: `4242 4242 4242 4242`
4. Complete payment form
5. Verify payment processed
6. Verify order created with `payment_status: 'completed'`
7. Verify transaction record created

#### PayPal Payment
1. Fill customer information
2. Select "PayPal"
3. Click "Pay with PayPal" (redirects to PayPal)
4. Login and approve payment
5. Return to success page
6. Verify order created with payment data
7. Verify transaction record created

### Error Test Cases
- Invalid card details (Stripe)
- Insufficient funds (Stripe)
- PayPal payment cancellation
- Network timeouts
- Server errors
- Invalid form data

## 6. Implementation Checklist

### Backend Updates
- [x] Remove order_id requirement from payment APIs
- [x] Update PaymentService methods
- [x] Update OrderService to accept payment_data
- [x] Create transaction records when order is created

### Frontend Implementation  
- [ ] Update payment service calls
- [ ] Implement payment-first checkout flow
- [ ] Create Stripe payment component
- [ ] Handle PayPal redirect flow
- [ ] Add payment success/cancel pages
- [ ] Implement error handling
- [ ] Add form validation
- [ ] Test all payment scenarios

## 7. Benefits of Payment-First Approach

✅ **Clean Data**: Only create orders for successful payments  
✅ **Better UX**: Payment commitment before order creation  
✅ **Simplified Logic**: No order updates via webhooks  
✅ **Faster Processing**: Direct payment-to-order flow  
✅ **Easier Testing**: Frontend controls entire flow  
✅ **Better Error Handling**: Payment errors don't create orphaned orders