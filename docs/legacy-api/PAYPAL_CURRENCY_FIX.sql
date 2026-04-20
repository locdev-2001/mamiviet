-- Fix PayPal to only support USD (EUR causing auth issues)
UPDATE payment_gateways 
SET config = JSON_SET(config, '$.supported_currencies', JSON_ARRAY('USD'))
WHERE name = 'paypal';

-- Verify the update
SELECT 
    name,
    JSON_EXTRACT(config, '$.supported_currencies') as supported_currencies,
    JSON_EXTRACT(config, '$.mode') as mode
FROM payment_gateways 
WHERE name = 'paypal';