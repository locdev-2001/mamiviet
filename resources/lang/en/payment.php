<?php

return [
    // Gateway messages
    'gateway_not_available' => 'Payment gateway is not available',
    'gateways_retrieved' => 'Payment gateways retrieved successfully',
    'gateways_error' => 'Error retrieving payment gateways',
    
    // Validation messages
    'validation_failed' => 'Validation failed',
    'invalid_amount' => 'Invalid amount',
    'currency_not_supported' => 'Currency is not supported',
    'order_description' => 'Order :order_code',
    
    // Validation specific
    'validation' => [
        'amount_required' => 'Amount is required',
        'amount_numeric' => 'Amount must be a number',
        'amount_min' => 'Amount must be greater than 0',
        'amount_invalid' => 'Invalid amount',
        'order_id_required' => 'Order ID is required',
        'order_id_exists' => 'Order does not exist',
        'currency_format' => 'Currency must be 3 characters',
        'currency_not_supported' => 'This currency is not supported',
        'payment_method_invalid' => 'Invalid payment method',
    ],
    
    // Stripe specific
    'stripe' => [
        'intent_created' => 'Stripe PaymentIntent created successfully',
        'intent_failed' => 'Failed to create Stripe PaymentIntent',
    ],
    
    // PayPal specific
    'paypal' => [
        'order_created' => 'PayPal order created successfully',
        'order_failed' => 'Failed to create PayPal order',
    ],
    
    // Payment status
    'status' => [
        'pending' => 'Pending',
        'completed' => 'Completed',
        'failed' => 'Failed',
        'refunded' => 'Refunded',
    ],
    
    // Payment methods
    'method' => [
        'cash' => 'Cash',
        'stripe' => 'Credit Card (Stripe)',
        'paypal' => 'PayPal',
    ],
    
    // General messages
    'payment_processing' => 'Processing payment...',
    'payment_successful' => 'Payment successful',
    'payment_failed' => 'Payment failed',
    'payment_cancelled' => 'Payment cancelled',
    'payment_refunded' => 'Payment refunded',
    
    // Error messages
    'webhook_processing_failed' => 'Webhook processing failed',
    'transaction_not_found' => 'Transaction not found',
    'order_not_found' => 'Order not found',
];