// Payment error handling utilities
export interface PaymentError {
  code: string;
  message: string;
  type: 'network' | 'validation' | 'payment' | 'server';
}

export const handlePaymentError = (error: any, paymentMethod: string): PaymentError => {
  // Network errors
  if (!error.response && error.request) {
    return {
      code: 'NETWORK_ERROR',
      message: 'Network connection failed. Please check your internet connection.',
      type: 'network'
    };
  }

  // Server errors
  if (error.response) {
    const { status, data } = error.response;
    
    switch (status) {
      case 422:
        // Validation errors
        const validationMessage = data.errors ? 
          Object.values(data.errors).flat().join(', ') : 
          'Invalid input data';
        return {
          code: 'VALIDATION_ERROR',
          message: validationMessage,
          type: 'validation'
        };
        
      case 401:
        return {
          code: 'UNAUTHORIZED',
          message: 'Authentication required. Please log in and try again.',
          type: 'server'
        };
        
      case 403:
        return {
          code: 'FORBIDDEN',
          message: 'Permission denied. You are not authorized to perform this action.',
          type: 'server'
        };
        
      case 404:
        return {
          code: 'NOT_FOUND',
          message: 'Payment service not available. Please try again later.',
          type: 'server'
        };
        
      case 429:
        return {
          code: 'RATE_LIMITED',
          message: 'Too many requests. Please wait a moment before trying again.',
          type: 'server'
        };
        
      case 500:
        return {
          code: 'SERVER_ERROR',
          message: 'Internal server error. Please try again later.',
          type: 'server'
        };
        
      case 503:
        return {
          code: 'SERVICE_UNAVAILABLE',
          message: 'Payment service is temporarily unavailable. Please try again later.',
          type: 'server'
        };
    }
  }

  // Payment-specific errors
  if (paymentMethod === 'stripe') {
    return handleStripeError(error);
  } else if (paymentMethod === 'paypal') {
    return handlePayPalError(error);
  }

  // Default error
  return {
    code: 'UNKNOWN_ERROR',
    message: error.message || 'An unexpected error occurred. Please try again.',
    type: 'payment'
  };
};

const handleStripeError = (error: any): PaymentError => {
  const errorCode = error.code || error.decline_code;
  
  const stripeErrors: Record<string, string> = {
    'card_declined': 'Your card was declined. Please try a different payment method.',
    'insufficient_funds': 'Insufficient funds on your card. Please try a different card.',
    'invalid_number': 'Your card number is invalid. Please check and try again.',
    'invalid_expiry_month': 'Your card expiry month is invalid.',
    'invalid_expiry_year': 'Your card expiry year is invalid.',
    'invalid_cvc': 'Your card security code is invalid.',
    'expired_card': 'Your card has expired. Please use a different card.',
    'incorrect_cvc': 'Your card security code is incorrect.',
    'incorrect_number': 'Your card number is incorrect.',
    'processing_error': 'An error occurred while processing your card. Try again.',
    'rate_limit': 'Too many requests. Please wait a moment before trying again.',
  };

  return {
    code: errorCode || 'STRIPE_ERROR',
    message: stripeErrors[errorCode] || 'Payment failed. Please check your card details and try again.',
    type: 'payment'
  };
};

const handlePayPalError = (error: any): PaymentError => {
  const errorCode = error.name || error.code;
  
  const paypalErrors: Record<string, string> = {
    'INSTRUMENT_DECLINED': 'PayPal payment was declined. Please try another payment method.',
    'PAYER_CANNOT_PAY': 'PayPal account cannot be used for this payment. Please try another method.',
    'INVALID_REQUEST': 'Invalid PayPal request. Please try again.',
    'AUTHENTICATION_FAILURE': 'PayPal authentication failed. Please try again.',
    'INSUFFICIENT_FUNDS': 'Insufficient funds in PayPal account. Please add funds or try another method.',
    'PAYEE_ACCOUNT_LOCKED': 'PayPal merchant account is temporarily unavailable.',
    'TRANSACTION_REFUSED': 'PayPal transaction was refused. Please contact PayPal support.',
  };

  return {
    code: errorCode || 'PAYPAL_ERROR',
    message: paypalErrors[errorCode] || 'PayPal payment failed. Please try again or use a different payment method.',
    type: 'payment'
  };
};

export const getRetryDelay = (attempt: number): number => {
  // Exponential backoff: 1s, 2s, 4s, 8s, max 30s
  return Math.min(1000 * Math.pow(2, attempt), 30000);
};

export const shouldRetry = (error: PaymentError, attempt: number): boolean => {
  const maxRetries = 3;
  
  if (attempt >= maxRetries) return false;
  
  // Don't retry validation or authentication errors
  if (['VALIDATION_ERROR', 'UNAUTHORIZED', 'FORBIDDEN'].includes(error.code)) {
    return false;
  }
  
  // Don't retry card-specific errors
  const noRetryStripeCodes = [
    'card_declined', 'insufficient_funds', 'invalid_number', 
    'invalid_cvc', 'expired_card', 'incorrect_cvc', 'incorrect_number'
  ];
  if (noRetryStripeCodes.includes(error.code)) {
    return false;
  }
  
  // Don't retry PayPal account issues
  const noRetryPayPalCodes = [
    'INSTRUMENT_DECLINED', 'PAYER_CANNOT_PAY', 'INSUFFICIENT_FUNDS'
  ];
  if (noRetryPayPalCodes.includes(error.code)) {
    return false;
  }
  
  // Retry network and temporary server errors
  return ['NETWORK_ERROR', 'SERVER_ERROR', 'SERVICE_UNAVAILABLE', 'RATE_LIMITED'].includes(error.code);
};