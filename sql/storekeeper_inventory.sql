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
    quantity DECIMAL(15,3) NOT NULL DEFAULT 0,
    unit VARCHAR(50) NOT NULL DEFAULT 'pcs',
    reorder_level DECIMAL(15,3) DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category_id (category_id),
    INDEX idx_item_name (item_name),
    INDEX idx_status (status),
    FOREIGN KEY (category_id) REFERENCES store_categories(id) ON DELETE CASCADE
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
INSERT IGNORE INTO store_inventory (category_id, item_name, description, quantity, unit, reorder_level) VALUES
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Surgical Gloves', 'Disposable surgical gloves for medical use', 100, 'boxes', 10),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Binding tape', 'Adhesive binding tape for documents', 50, 'rolls', 5),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Examination Gloves', 'Disposable examination gloves', 150, 'boxes', 15),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Masking tape', 'Paper masking tape for various uses', 75, 'rolls', 8),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Sink Pumps', 'Manual sink pumps for drainage', 20, 'pcs', 2),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Ruled reams', 'Ruled paper reams for writing', 30, 'reams', 3),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Requirements Clearance Books', 'Books for student requirements clearance', 25, 'pcs', 3),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Receipt books', 'Official receipt books for transactions', 40, 'pcs', 4),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Photocopying Reams', 'A4 paper for photocopying', 100, 'reams', 10),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Payment Voucher Books', 'Books for payment voucher recording', 35, 'pcs', 4),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Omo', 'Laundry detergent powder', 60, 'kg', 6),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Binding rings', 'Plastic binding rings for documents', 200, 'pcs', 20),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Vim', 'Cleaning paste for surfaces', 40, 'tins', 4),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Ring binder files', 'Files with ring binding mechanism', 80, 'pcs', 8),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Jik', 'Bleach solution for disinfection', 50, 'liters', 5),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Envelops', 'Various sizes of envelopes for mail', 500, 'pcs', 50),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'A3', 'A3 size paper for drawings and prints', 15, 'reams', 2),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'A4', 'A4 size paper for documents', 200, 'reams', 20),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'A5', 'A5 size paper for notes and booklets', 100, 'reams', 10),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Box files', 'Storage box files for documents', 60, 'pcs', 6),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Switches', 'Electrical switches for installations', 40, 'pcs', 4),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Double gang switches', 'Double electrical switches', 25, 'pcs', 3),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Single gang switches', 'Single electrical switches', 35, 'pcs', 4),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Counter books', 'Books for counting and recording', 30, 'pcs', 3),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Lamp holders', 'Fixtures for holding light bulbs', 50, 'pcs', 5),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Scrubbing Brushes', 'Brushes for cleaning surfaces', 40, 'pcs', 4),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Sockets', 'Electrical socket outlets', 60, 'pcs', 6),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Single', 'Single electrical sockets', 30, 'pcs', 3),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Double', 'Double electrical sockets', 30, 'pcs', 3),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Squeezers', 'Manual juice squeezers', 15, 'pcs', 2),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Bulbs', 'Light bulbs for illumination', 100, 'pcs', 10),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Mops', 'Cleaning mops for floors', 25, 'pcs', 3),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Mounding boxes', 'Electrical mounting boxes', 40, 'pcs', 4),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Toilet brushes', 'Brushes for toilet cleaning', 30, 'pcs', 3),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Markers', 'Permanent and whiteboard markers', 100, 'pcs', 10),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Color papers', 'Colored paper for decorations', 50, 'reams', 5),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Layer File Trays', 'Multi-layer file trays for organization', 20, 'pcs', 2),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Cobweb brushes', 'Brushes for removing cobwebs', 20, 'pcs', 2),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Laminating Paper', 'Paper for laminating documents', 30, 'packs', 3),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Soft Brooms', 'Soft brooms for indoor sweeping', 30, 'pcs', 3),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Staple wires', 'Wires for stapling machines', 80, 'boxes', 8),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Compound brooms', 'Heavy-duty brooms for outdoor use', 20, 'pcs', 2),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Paper clips', 'Metal clips for holding papers', 200, 'boxes', 20),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Rakes', 'Garden rakes for landscaping', 15, 'pcs', 2),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'PVC Covers', 'Protective PVC covers', 40, 'pcs', 4),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Chalk', 'Writing chalk for blackboards', 100, 'boxes', 10),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Atlas files', 'Files for storing atlas documents', 15, 'pcs', 2),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Dormeciliary Kit Bags', 'Bedside care kits', 25, 'pcs', 3),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Carbon papers', 'Carbon paper for duplicating documents', 20, 'packs', 2),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Blackboard Dusters', 'Erasers for blackboards', 30, 'pcs', 3),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Highlighter Markers', 'Highlighting pens for text marking', 80, 'pcs', 8),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Liquid soap', 'Hand washing liquid soap', 60, 'liters', 6),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Pens', 'Ballpoint and gel pens for writing', 200, 'pcs', 20),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Rubbers', 'Pencil erasers', 150, 'pcs', 15),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Ofice glue', 'Adhesive glue for office use', 40, 'bottles', 4),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Sticker notes', 'Adhesive notes for reminders', 100, 'pads', 10),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Stick Glue', 'Solid glue sticks', 50, 'pcs', 5),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Toilet Papers', 'Toilet tissue rolls', 120, 'rolls', 12),
((SELECT id FROM store_categories WHERE category_name = 'General Utilities'), 'Insulation Tape', 'Electrical insulation tape', 50, 'rolls', 5);

-- Insert sample inventory items for Food Store Supplies
INSERT IGNORE INTO store_inventory (category_id, item_name, description, quantity, unit, reorder_level) VALUES
((SELECT id FROM store_categories WHERE category_name = 'Food Store Supplies'), 'Posho', 'Maize flour for posho preparation', 50, 'kg', 5),
((SELECT id FROM store_categories WHERE category_name = 'Food Store Supplies'), 'Rice', 'White rice for meals', 100, 'kg', 10),
((SELECT id FROM store_categories WHERE category_name = 'Food Store Supplies'), 'Beans', 'Beans for protein source', 80, 'kg', 8),
((SELECT id FROM store_categories WHERE category_name = 'Food Store Supplies'), 'Salt', 'Cooking salt for seasoning', 30, 'kg', 3),
((SELECT id FROM store_categories WHERE category_name = 'Food Store Supplies'), 'Cooking oil', 'Vegetable oil for cooking', 60, 'liters', 6),
((SELECT id FROM store_categories WHERE category_name = 'Food Store Supplies'), 'Sugar', 'Granulated sugar for sweetening', 70, 'kg', 7),
((SELECT id FROM store_categories WHERE category_name = 'Food Store Supplies'), 'Plates', 'Ceramic plates for serving', 200, 'pcs', 20),
((SELECT id FROM store_categories WHERE category_name = 'Food Store Supplies'), 'Charcoal', 'Charcoal for cooking fuel', 40, 'bags', 4);