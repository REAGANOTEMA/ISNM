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

-- Insert default categories
INSERT IGNORE INTO store_categories (category_name, description) VALUES
('General Utilities', 'Office supplies, cleaning materials, electrical items, etc.'),
('Food Store Supplies', 'Food items, cooking ingredients, kitchen supplies, etc.');

-- Insert sample inventory items for General Utilities
INSERT IGNORE INTO store_inventory (category_id, item_name, description, department, report_to, quantity, unit, reorder_level) VALUES
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Surgical Gloves', 'Disposable surgical gloves for medical use', 'Sickbay', 'School Principal', 100, 'boxes', 10),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Binding tape', 'Adhesive binding tape for documents', 'Administration', 'HR Manager', 50, 'rolls', 5),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Examination Gloves', 'Disposable examination gloves', 'Sickbay', 'School Principal', 150, 'boxes', 15),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Masking tape', 'Paper masking tape for general use', 'Maintenance', 'Director General', 75, 'rolls', 8),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Sink Pumps', 'Manual sink pumps for drainage', 'Maintenance', 'Director General', 20, 'pcs', 2),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Ruled reams', 'Ruled paper reams for writing', 'Administration', 'HR Manager', 30, 'reams', 3),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Receipt books', 'Official receipt books for transactions', 'Finance', 'School Bursar', 40, 'pcs', 4),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Photocopying Reams', 'A4 paper for photocopying', 'Academic Records', 'Academic Registrar', 100, 'reams', 10),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Jik', 'Bleach solution for disinfection', 'Sickbay', 'School Principal', 50, 'liters', 5),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Envelops', 'Various sizes of envelopes for mail', 'Administration', 'HR Manager', 500, 'pcs', 50),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'A4', 'A4 size paper for documents', 'Administration', 'HR Manager', 200, 'reams', 20),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Bulbs', 'Light bulbs for illumination', 'Maintenance', 'Director General', 100, 'pcs', 10),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Mops', 'Cleaning mops for floors', 'Maintenance', 'Director General', 25, 'pcs', 3),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Pens', 'Ballpoint and gel pens for writing', 'Administration', 'HR Manager', 200, 'pcs', 20),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Liquid soap', 'Hand washing liquid soap', 'Sickbay', 'School Principal', 60, 'liters', 6),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Toilet Papers', 'Toilet tissue rolls', 'Maintenance', 'Director General', 120, 'rolls', 12),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Insulation Tape', 'Electrical insulation tape', 'Maintenance', 'Director General', 50, 'rolls', 5);

-- Insert sample inventory items for Food Store Supplies
INSERT IGNORE INTO store_inventory (category_id, item_name, description, department, report_to, quantity, unit, reorder_level) VALUES
((SELECT id FROM store_categories WHERE category_name = 'Food Store Supplies'), 'Posho', 'Maize flour for posho preparation', 'Food Services', 'School Secretary', 50, 'kg', 5),
((SELECT id FROM store_categories WHERE category_name = 'Food Store Supplies'), 'Rice', 'White rice for meals', 'Food Services', 'School Secretary', 100, 'kg', 10),
((SELECT id FROM store_categories WHERE category_name = 'Food Store Supplies'), 'Beans', 'Beans for protein source', 'Food Services', 'School Secretary', 80, 'kg', 8),
((SELECT id FROM store_categories WHERE category_name = 'Food Store Supplies'), 'Salt', 'Cooking salt for seasoning', 'Food Services', 'School Secretary', 30, 'kg', 3),
((SELECT id FROM store_categories WHERE category_name = 'Food Store Supplies'), 'Cooking oil', 'Vegetable oil for cooking', 'Food Services', 'School Secretary', 60, 'liters', 6),
((SELECT id FROM store_categories WHERE category_name = 'Food Store Supplies'), 'Sugar', 'Granulated sugar for sweetening', 'Food Services', 'School Secretary', 70, 'kg', 7),
((SELECT id FROM store_categories WHERE category_name = 'Food Store Supplies'), 'Plates', 'Ceramic plates for serving', 'Food Services', 'School Secretary', 200, 'pcs', 20),
((SELECT id FROM store_categories WHERE category_name = 'Food Store Supplies'), 'Charcoal', 'Charcoal for cooking fuel', 'Food Services', 'School Secretary', 40, 'bags', 4);