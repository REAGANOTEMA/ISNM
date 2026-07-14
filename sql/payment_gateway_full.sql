-- ============================================================
-- ISNM PAYMENT GATEWAY — COMPREHENSIVE DATABASE SCHEMA
-- Modular payment architecture supporting multiple providers
-- ============================================================

SET NAMES utf8mb4;

-- ------------------------------------------------------------
-- 1. Payment Providers — stores all provider configurations
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payment_providers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `provider_key` varchar(50) NOT NULL,
  `provider_name` varchar(100) NOT NULL,
  `provider_type` enum('mobile_money','bank_card','bank_transfer','card_gateway','wallet','custom') NOT NULL DEFAULT 'custom',
  `provider_category` enum('local','international','bank','mobile_money') NOT NULL DEFAULT 'local',
  `logo_url` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `api_base_url` varchar(255) DEFAULT NULL,
  `api_key` text DEFAULT NULL,
  `api_secret` text DEFAULT NULL,
  `merchant_id` varchar(100) DEFAULT NULL,
  `public_key` text DEFAULT NULL,
  `private_key` text DEFAULT NULL,
  `callback_url` varchar(500) DEFAULT NULL,
  `webhook_url` varchar(500) DEFAULT NULL,
  `return_url` varchar(500) DEFAULT NULL,
  `currency` varchar(10) DEFAULT 'UGX',
  `supported_currencies` text DEFAULT NULL,
  `fee_type` enum('fixed','percentage','both','none') DEFAULT 'none',
  `fee_fixed` decimal(10,2) DEFAULT 0.00,
  `fee_percentage` decimal(5,2) DEFAULT 0.00,
  `min_amount` decimal(15,2) DEFAULT 0.00,
  `max_amount` decimal(15,2) DEFAULT 999999999.99,
  `status` enum('active','inactive','suspended','testing') NOT NULL DEFAULT 'inactive',
  `is_test_mode` tinyint(1) DEFAULT 1,
  `test_api_base_url` varchar(255) DEFAULT NULL,
  `test_api_key` text DEFAULT NULL,
  `test_api_secret` text DEFAULT NULL,
  `test_merchant_id` varchar(100) DEFAULT NULL,
  `hmac_secret` text DEFAULT NULL,
  `certificate_path` varchar(255) DEFAULT NULL,
  `config_data` longtext DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `total_transactions` int(11) DEFAULT 0,
  `total_volume` decimal(15,2) DEFAULT 0.00,
  `last_transaction_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `provider_key` (`provider_key`),
  KEY `idx_provider_type` (`provider_type`),
  KEY `idx_provider_status` (`status`),
  KEY `idx_provider_category` (`provider_category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 2. Payment Transactions — every payment attempt
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payment_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_ref` varchar(100) NOT NULL,
  `provider_key` varchar(50) NOT NULL,
  `provider_transaction_id` varchar(100) DEFAULT NULL,
  `transaction_type` enum('payment','refund','reversal','withdrawal','topup') NOT NULL DEFAULT 'payment',
  `payment_for` enum('tuition','application','admission','graduation','hostel','library_fine','donation','volunteer','staff','miscellaneous') NOT NULL DEFAULT 'tuition',
  `student_id` int(11) DEFAULT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `applicant_id` int(11) DEFAULT NULL,
  `payer_name` varchar(200) DEFAULT NULL,
  `payer_phone` varchar(30) DEFAULT NULL,
  `payer_email` varchar(150) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'UGX',
  `amount_received` decimal(15,2) DEFAULT NULL,
  `fee_amount` decimal(10,2) DEFAULT 0.00,
  `tax_amount` decimal(10,2) DEFAULT 0.00,
  `status` enum('pending','processing','successful','failed','cancelled','expired','reversed','refunded') NOT NULL DEFAULT 'pending',
  `status_reason` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `metadata` longtext DEFAULT NULL,
  `initiated_at` datetime DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `verification_attempts` int(11) DEFAULT 0,
  `last_verification_at` datetime DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `idempotency_key` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `transaction_ref` (`transaction_ref`),
  KEY `idx_payment_provider` (`provider_key`),
  KEY `idx_payment_student` (`student_id`),
  KEY `idx_payment_staff` (`staff_id`),
  KEY `idx_payment_status` (`status`),
  KEY `idx_payment_date` (`created_at`),
  KEY `idx_payment_type` (`payment_for`),
  KEY `idx_idempotency` (`idempotency_key`),
  KEY `idx_provider_txn` (`provider_transaction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 3. Payment Callbacks — raw callback/webhook logs
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payment_callbacks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_id` int(11) DEFAULT NULL,
  `provider_key` varchar(50) NOT NULL,
  `callback_type` enum('callback','webhook','return','notification') NOT NULL DEFAULT 'callback',
  `request_method` varchar(10) DEFAULT 'POST',
  `request_headers` longtext DEFAULT NULL,
  `request_body` longtext DEFAULT NULL,
  `request_ip` varchar(45) DEFAULT NULL,
  `response_code` int(11) DEFAULT NULL,
  `response_body` text DEFAULT NULL,
  `processed` tinyint(1) DEFAULT 0,
  `processed_at` datetime DEFAULT NULL,
  `processing_error` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_cb_transaction` (`transaction_id`),
  KEY `idx_cb_provider` (`provider_key`),
  KEY `idx_cb_date` (`created_at`),
  KEY `idx_cb_processed` (`processed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 4. Payment Refunds — refund tracking
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payment_refunds` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `refund_ref` varchar(100) NOT NULL,
  `original_transaction_id` int(11) NOT NULL,
  `provider_key` varchar(50) NOT NULL,
  `provider_refund_id` varchar(100) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'UGX',
  `reason` text DEFAULT NULL,
  `status` enum('pending','processing','successful','failed') NOT NULL DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `processed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `refund_ref` (`refund_ref`),
  KEY `idx_refund_original` (`original_transaction_id`),
  KEY `idx_refund_provider` (`provider_key`),
  KEY `idx_refund_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 5. Payment Reconciliation — daily reconciliation records
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payment_reconciliation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reconciliation_date` date NOT NULL,
  `provider_key` varchar(50) NOT NULL,
  `expected_amount` decimal(15,2) DEFAULT 0.00,
  `actual_amount` decimal(15,2) DEFAULT 0.00,
  `difference` decimal(15,2) DEFAULT 0.00,
  `expected_count` int(11) DEFAULT 0,
  `actual_count` int(11) DEFAULT 0,
  `status` enum('pending','matched','discrepancy','resolved') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `reconciled_by` int(11) DEFAULT NULL,
  `reconciled_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `reconciliation_date_provider` (`reconciliation_date`, `provider_key`),
  KEY `idx_recon_date` (`reconciliation_date`),
  KEY `idx_recon_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 6. Payment Webhook Logs — detailed webhook processing logs
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payment_webhook_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `provider_key` varchar(50) NOT NULL,
  `event_type` varchar(100) DEFAULT NULL,
  `signature` varchar(500) DEFAULT NULL,
  `signature_valid` tinyint(1) DEFAULT NULL,
  `payload` longtext DEFAULT NULL,
  `headers` longtext DEFAULT NULL,
  `processing_status` enum('received','processing','processed','failed','ignored') DEFAULT 'received',
  `error_message` text DEFAULT NULL,
  `processing_time_ms` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_wh_provider` (`provider_key`),
  KEY `idx_wh_status` (`processing_status`),
  KEY `idx_wh_date` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 7. Payment Receipts — auto-generated receipts
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payment_receipts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `receipt_number` varchar(50) NOT NULL,
  `transaction_id` int(11) NOT NULL,
  `receipt_type` enum('payment','refund','invoice') NOT NULL DEFAULT 'payment',
  `student_name` varchar(200) DEFAULT NULL,
  `student_number` varchar(50) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `currency` varchar(10) DEFAULT 'UGX',
  `payment_method` varchar(100) DEFAULT NULL,
  `payment_date` datetime DEFAULT NULL,
  `description` text DEFAULT NULL,
  `pdf_path` varchar(255) DEFAULT NULL,
  `emailed` tinyint(1) DEFAULT 0,
  `emailed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `receipt_number` (`receipt_number`),
  KEY `idx_receipt_transaction` (`transaction_id`),
  KEY `idx_receipt_student` (`student_number`),
  KEY `idx_receipt_date` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 8. Payment Audit Log — all payment-related actions
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payment_audit_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `user_type` enum('staff','student','system') DEFAULT 'staff',
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `old_values` longtext DEFAULT NULL,
  `new_values` longtext DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_audit_user` (`user_id`),
  KEY `idx_audit_action` (`action`),
  KEY `idx_audit_entity` (`entity_type`, `entity_id`),
  KEY `idx_audit_date` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SEED DATA: Default Payment Providers
-- ============================================================

INSERT IGNORE INTO `payment_providers` (`provider_key`, `provider_name`, `provider_type`, `provider_category`, `description`, `currency`, `status`, `is_test_mode`, `sort_order`) VALUES
('mtn_momo', 'MTN Mobile Money', 'mobile_money', 'mobile_money', 'MTN MoMo mobile money payments for Uganda', 'UGX', 'inactive', 1, 1),
('airtel_money', 'Airtel Money', 'mobile_money', 'mobile_money', 'Airtel Money mobile payments for Uganda', 'UGX', 'inactive', 1, 2),
('stanbic_bank', 'Stanbic Bank', 'bank_transfer', 'bank', 'Stanbic Bank direct transfers and EFT', 'UGX', 'inactive', 1, 3),
('centenary_bank', 'Centenary Bank', 'bank_transfer', 'bank', 'Centenary Bank transfers', 'UGX', 'inactive', 1, 4),
('stanbic_card', 'Stanbic Card Services', 'bank_card', 'international', 'Visa/Mastercard via Stanbic', 'UGX', 'inactive', 1, 5),
('flutterwave', 'Flutterwave', 'card_gateway', 'international', 'Flutterwave - Cards, Mobile Money, Bank', 'UGX', 'inactive', 1, 6),
('pesapal', 'PesaPal', 'card_gateway', 'local', 'PesaPal - Cards and Mobile Money', 'UGX', 'inactive', 1, 7),
('stripe', 'Stripe', 'card_gateway', 'international', 'Stripe - International card payments', 'USD', 'inactive', 1, 8),
('paypal', 'PayPal', 'wallet', 'international', 'PayPal wallet payments', 'USD', 'inactive', 1, 9),
('direct_bank', 'Direct Bank Transfer', 'bank_transfer', 'bank', 'Manual bank transfer with verification', 'UGX', 'active', 0, 10),
('cash', 'Cash Payment', 'custom', 'local', 'Cash payments recorded at finance office', 'UGX', 'active', 0, 11),
('cheque', 'Cheque Payment', 'custom', 'local', 'Cheque payments', 'UGX', 'active', 0, 12);
