-- ISNM Payment Gateway Schema
-- Modular payment provider system

CREATE TABLE IF NOT EXISTS payment_providers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provider_key VARCHAR(50) NOT NULL UNIQUE,
    provider_name VARCHAR(100) NOT NULL,
    provider_type ENUM('mobile_money','card','bank','wallet','crypto') NOT NULL,
    is_enabled TINYINT(1) DEFAULT 0,
    merchant_id VARCHAR(255) DEFAULT '',
    api_key VARCHAR(255) DEFAULT '',
    api_secret VARCHAR(512) DEFAULT '',
    api_url VARCHAR(500) DEFAULT '',
    callback_url VARCHAR(500) DEFAULT '',
    webhook_secret VARCHAR(255) DEFAULT '',
    config_json JSON,
    supported_currencies VARCHAR(255) DEFAULT 'UGX',
    transaction_fee_percent DECIMAL(5,2) DEFAULT 0.00,
    transaction_fee_fixed DECIMAL(10,2) DEFAULT 0.00,
    min_amount DECIMAL(12,2) DEFAULT 0.00,
    max_amount DECIMAL(12,2) DEFAULT 10000000.00,
    status ENUM('active','inactive','sandbox') DEFAULT 'sandbox',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_provider_type (provider_type),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS payment_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_ref VARCHAR(100) NOT NULL UNIQUE,
    provider_key VARCHAR(50) NOT NULL,
    provider_transaction_id VARCHAR(255) DEFAULT '',
    payment_type ENUM('student_fees','application','admission','graduation','hostel','library_fine','donation','volunteer','staff','misc') NOT NULL,
    reference_type VARCHAR(50) DEFAULT '',
    reference_id INT DEFAULT 0,
    student_id INT DEFAULT 0,
    staff_id INT DEFAULT 0,
    payer_name VARCHAR(255) DEFAULT '',
    payer_phone VARCHAR(50) DEFAULT '',
    payer_email VARCHAR(255) DEFAULT '',
    amount DECIMAL(12,2) NOT NULL,
    currency VARCHAR(10) DEFAULT 'UGX',
    fee_amount DECIMAL(12,2) DEFAULT 0.00,
    net_amount DECIMAL(12,2) DEFAULT 0.00,
    status ENUM('pending','processing','successful','failed','cancelled','refunded','expired') DEFAULT 'pending',
    status_message VARCHAR(500) DEFAULT '',
    metadata_json JSON,
    initiated_by INT DEFAULT 0,
    ip_address VARCHAR(45) DEFAULT '',
    user_agent TEXT,
    callback_received_at TIMESTAMP NULL,
    reconciled_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_student (student_id),
    INDEX idx_staff (staff_id),
    INDEX idx_provider (provider_key),
    INDEX idx_status (status),
    INDEX idx_payment_type (payment_type),
    INDEX idx_reference (reference_type, reference_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS payment_callbacks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT NOT NULL,
    provider_key VARCHAR(50) NOT NULL,
    callback_type ENUM('webhook','return_url','polling') NOT NULL,
    request_method VARCHAR(10) DEFAULT 'POST',
    request_headers TEXT,
    request_body LONGTEXT,
    response_code INT DEFAULT 0,
    response_body LONGTEXT,
    processed TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_transaction (transaction_id),
    INDEX idx_provider (provider_key),
    INDEX idx_processed (processed)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS payment_refunds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    refund_ref VARCHAR(100) NOT NULL UNIQUE,
    original_transaction_id INT NOT NULL,
    provider_key VARCHAR(50) NOT NULL,
    provider_refund_id VARCHAR(255) DEFAULT '',
    amount DECIMAL(12,2) NOT NULL,
    reason TEXT,
    status ENUM('pending','processing','successful','failed') DEFAULT 'pending',
    initiated_by INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_original (original_transaction_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS payment_reconciliation (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reconciliation_date DATE NOT NULL,
    provider_key VARCHAR(50) NOT NULL,
    total_transactions INT DEFAULT 0,
    successful_count INT DEFAULT 0,
    failed_count INT DEFAULT 0,
    total_amount DECIMAL(14,2) DEFAULT 0.00,
    total_fees DECIMAL(12,2) DEFAULT 0.00,
    total_refunds DECIMAL(12,2) DEFAULT 0.00,
    net_amount DECIMAL(14,2) DEFAULT 0.00,
    status ENUM('pending','completed','discrepancy') DEFAULT 'pending',
    notes TEXT,
    reconciled_by INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_date_provider (reconciliation_date, provider_key),
    INDEX idx_date (reconciliation_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS payment_webhook_logs (
    id INT AUTO_INCREMENT KEY,
    provider_key VARCHAR(50) NOT NULL,
    event_type VARCHAR(100) DEFAULT '',
    payload LONGTEXT,
    signature VARCHAR(512) DEFAULT '',
    signature_valid TINYINT(1) DEFAULT NULL,
    processed TINYINT(1) DEFAULT 0,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_provider (provider_key),
    INDEX idx_event (event_type),
    INDEX idx_processed (processed)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
