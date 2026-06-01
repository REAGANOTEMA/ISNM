-- ============================================================
-- ISNM STOREKEEPER INVENTORY MANAGEMENT SYSTEM
-- Comprehensive SQL Schema for Store Inventory Management
-- Database: igangaschoolofl_staffs_db
-- ============================================================

USE igangaschoolofl_staffs_db;

-- Drop tables in correct order to avoid foreign key constraints
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS store_inventory_transactions;
DROP TABLE IF EXISTS store_inventory;
DROP TABLE IF EXISTS store_categories;
SET FOREIGN_KEY_CHECKS = 1;

-- Store Categories Table
CREATE TABLE IF NOT EXISTS store_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category_name (category_name),
    INDEX idx_status (status)
);

-- Store Inventory Table
CREATE TABLE IF NOT EXISTS store_inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    description TEXT,
    department VARCHAR(100) DEFAULT 'Store',
    report_to VARCHAR(100) DEFAULT 'HR Manager',
    quantity DECIMAL(15,3) NOT NULL DEFAULT 0,
    unit VARCHAR(50) NOT NULL DEFAULT 'pcs',
    reorder_level DECIMAL(15,3) DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category_id (category_id),
    INDEX idx_item_name (item_name),
    INDEX idx_department (department),
    INDEX idx_report_to (report_to),
    INDEX idx_status (status),
    FOREIGN KEY (category_id) REFERENCES store_categories(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS store_inventory_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    reported_by INT NOT NULL,
    report_to VARCHAR(100) NOT NULL,
    department VARCHAR(100) DEFAULT 'Store',
    report_type ENUM('Low Stock','Damage','Request','Adjustment','Transfer','Other') NOT NULL DEFAULT 'Request',
    notes TEXT,
    status ENUM('Open','In Review','Resolved','Closed') DEFAULT 'Open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES store_inventory(id) ON DELETE CASCADE,
    INDEX idx_item_id (item_id),
    INDEX idx_report_to (report_to),
    INDEX idx_report_status (status)
);

-- Store Inventory Transactions Table (for audit trail)
CREATE TABLE IF NOT EXISTS store_inventory_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    transaction_type ENUM('add', 'remove', 'adjust') NOT NULL,
    quantity DECIMAL(15,3) NOT NULL,
    reason VARCHAR(255),
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_item_id (item_id),
    INDEX idx_transaction_type (transaction_type),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (item_id) REFERENCES store_inventory(id) ON DELETE CASCADE
);

-- Insert default categories and sample items
INSERT IGNORE INTO store_categories (category_name, description) VALUES
('General Utilities', 'Office supplies, cleaning materials, electrical items, etc.'),
('Food Store Supplies', 'Food items, cooking ingredients, kitchen supplies, etc.');

-- ... sample insertions ...
