-- Create payment_gateways table
CREATE TABLE `payment_gateways` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `config` json NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_gateways_name_unique` (`name`)
);

-- Insert default payment gateways with configurations
INSERT INTO `payment_gateways` (`name`, `is_active`, `config`, `created_at`, `updated_at`) VALUES
('stripe', 1, '{"publishable_key": "", "secret_key": "", "webhook_secret": "", "currency": "usd", "supported_currencies": ["usd", "eur"]}', NOW(), NOW()),
('paypal', 1, '{"client_id": "", "client_secret": "", "mode": "sandbox", "currency": "USD", "supported_currencies": ["USD", "EUR"]}', NOW(), NOW());