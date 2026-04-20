-- Add payment fields to orders table
ALTER TABLE `orders` 
ADD COLUMN `payment_status` enum('pending','completed','failed','refunded') NOT NULL DEFAULT 'pending' AFTER `status`,
ADD COLUMN `payment_method` enum('cash','stripe','paypal') NOT NULL DEFAULT 'cash' AFTER `payment_status`,
ADD INDEX `orders_payment_status_index` (`payment_status`),
ADD INDEX `orders_payment_method_index` (`payment_method`);