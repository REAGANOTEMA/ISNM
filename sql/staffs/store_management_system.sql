-- ============================================================
-- ISNM STORE MANAGEMENT SYSTEM
-- Complete SQL schema for store inventory, requests, and orders
-- Database: igangaschoolofl_staffs_db
-- ============================================================

USE `igangaschoolofl_staffs_db`;

SET FOREIGN_KEY_CHECKS = 0;

-- Drop existing tables (if any) for fresh install
DROP TABLE IF EXISTS store_order_items;
DROP TABLE IF EXISTS store_orders;
DROP TABLE IF EXISTS store_request_items;
DROP TABLE IF EXISTS store_requests;
DROP TABLE IF EXISTS store_inventory_transactions;
DROP TABLE IF EXISTS store_inventory;
DROP TABLE IF EXISTS store_categories;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- 1. STORE CATEGORIES
-- ============================================================
CREATE TABLE store_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(50) DEFAULT 'fas fa-box',
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category_name (category_name),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 2. STORE INVENTORY
-- ============================================================
CREATE TABLE store_inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    description TEXT,
    quantity DECIMAL(15,3) NOT NULL DEFAULT 0,
    unit VARCHAR(50) NOT NULL DEFAULT 'pcs',
    reorder_level DECIMAL(15,3) DEFAULT 10,
    unit_price DECIMAL(15,2) DEFAULT 0,
    location VARCHAR(100) DEFAULT 'Main Store',
    status ENUM('active','inactive','discontinued') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category_id (category_id),
    INDEX idx_item_name (item_name),
    INDEX idx_status (status),
    FOREIGN KEY (category_id) REFERENCES store_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 3. STORE INVENTORY TRANSACTIONS (audit trail)
-- ============================================================
CREATE TABLE store_inventory_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    transaction_type ENUM('add','remove','adjust','request_fulfilled','order_received','returned','damaged') NOT NULL,
    quantity DECIMAL(15,3) NOT NULL,
    quantity_before DECIMAL(15,3) DEFAULT 0,
    quantity_after DECIMAL(15,3) DEFAULT 0,
    reference_type VARCHAR(50) DEFAULT NULL,
    reference_id INT DEFAULT NULL,
    reason TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_item_id (item_id),
    INDEX idx_transaction_type (transaction_type),
    INDEX idx_created_at (created_at),
    INDEX idx_reference (reference_type, reference_id),
    FOREIGN KEY (item_id) REFERENCES store_inventory(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 4. STORE REQUESTS (staff request items from store)
-- ============================================================
CREATE TABLE store_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_number VARCHAR(50) NOT NULL UNIQUE,
    requested_by INT NOT NULL,
    department VARCHAR(200) DEFAULT NULL,
    notes TEXT,
    urgency ENUM('low','medium','high','urgent') DEFAULT 'medium',
    status ENUM('pending','approved','partially_fulfilled','fulfilled','rejected','forwarded') DEFAULT 'pending',
    forwarded_to INT DEFAULT NULL,
    forwarded_to_role VARCHAR(100) DEFAULT NULL,
    approved_by INT DEFAULT NULL,
    approved_at DATETIME DEFAULT NULL,
    fulfilled_by INT DEFAULT NULL,
    fulfilled_at DATETIME DEFAULT NULL,
    rejection_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_request_number (request_number),
    INDEX idx_requested_by (requested_by),
    INDEX idx_status (status),
    INDEX idx_forwarded_to (forwarded_to),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 5. STORE REQUEST ITEMS (line items in each request)
-- ============================================================
CREATE TABLE store_request_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    item_id INT NOT NULL,
    quantity_requested DECIMAL(15,3) NOT NULL,
    quantity_fulfilled DECIMAL(15,3) DEFAULT 0,
    unit_price DECIMAL(15,2) DEFAULT 0,
    notes TEXT,
    status ENUM('pending','fulfilled','cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_request_id (request_id),
    INDEX idx_item_id (item_id),
    FOREIGN KEY (request_id) REFERENCES store_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES store_inventory(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 6. STORE ORDERS (storekeeper orders replenishment)
-- ============================================================
CREATE TABLE store_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(50) NOT NULL UNIQUE,
    supplier VARCHAR(200) DEFAULT 'Internal Requisition',
    notes TEXT,
    total_amount DECIMAL(15,2) DEFAULT 0,
    status ENUM('draft','pending_approval','approved','ordered','partially_received','received','cancelled') DEFAULT 'draft',
    requested_by INT NOT NULL,
    approved_by INT DEFAULT NULL,
    approved_at DATETIME DEFAULT NULL,
    received_by INT DEFAULT NULL,
    received_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_order_number (order_number),
    INDEX idx_status (status),
    INDEX idx_requested_by (requested_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 7. STORE ORDER ITEMS
-- ============================================================
CREATE TABLE store_order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    item_id INT NOT NULL,
    quantity_ordered DECIMAL(15,3) NOT NULL,
    quantity_received DECIMAL(15,3) DEFAULT 0,
    unit_price DECIMAL(15,2) DEFAULT 0,
    notes TEXT,
    status ENUM('pending','received','cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_order_id (order_id),
    INDEX idx_item_id (item_id),
    FOREIGN KEY (order_id) REFERENCES store_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES store_inventory(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- POPULATE CATEGORIES
-- ============================================================
INSERT IGNORE INTO store_categories (category_name, description, icon) VALUES
('General Utilities', 'Office supplies, cleaning, electrical, and general maintenance items', 'fas fa-tools'),
('Food Store Supplies', 'Food items, cooking ingredients, and kitchen supplies', 'fas fa-utensils'),
('Medical Supplies', 'Medical consumables, gloves, dressings, and clinical items', 'fas fa-kit-medical'),
('Cleaning & Hygiene', 'Cleaning agents, sanitizers, and hygiene products', 'fas fa-pump-soap'),
('Office Stationery', 'Paper, writing materials, filing and office stationery', 'fas fa-pen-ruler'),
('Electrical & Hardware', 'Electrical fittings, tools, and hardware items', 'fas fa-bolt'),
('Kitchen & Dining', 'Kitchen utensils, dining items, and catering supplies', 'fas fa-kitchen-set'),
('Furniture & Storage', 'Furniture, shelves, filing cabinets, and storage items', 'fas fa-couch'),
('ICT Supplies', 'Computer consumables, printer supplies, and ICT accessories', 'fas fa-laptop'),
('Teaching & Training', 'Teaching aids, simulation supplies, and training materials', 'fas fa-chalkboard-user');

-- ============================================================
-- POPULATE ITEMS (222 items across 10 categories)
-- ============================================================

-- Helper: Get category IDs
SET @gen_util = (SELECT id FROM store_categories WHERE category_name = 'General Utilities');
SET @food     = (SELECT id FROM store_categories WHERE category_name = 'Food Store Supplies');
SET @medical  = (SELECT id FROM store_categories WHERE category_name = 'Medical Supplies');
SET @cleaning = (SELECT id FROM store_categories WHERE category_name = 'Cleaning & Hygiene');
SET @stationery = (SELECT id FROM store_categories WHERE category_name = 'Office Stationery');
SET @electrical = (SELECT id FROM store_categories WHERE category_name = 'Electrical & Hardware');
SET @kitchen    = (SELECT id FROM store_categories WHERE category_name = 'Kitchen & Dining');
SET @furniture  = (SELECT id FROM store_categories WHERE category_name = 'Furniture & Storage');
SET @ict        = (SELECT id FROM store_categories WHERE category_name = 'ICT Supplies');
SET @teaching   = (SELECT id FROM store_categories WHERE category_name = 'Teaching & Training');

-- General Utilities (17 items)
INSERT IGNORE INTO store_inventory (category_id, item_name, unit, reorder_level) VALUES
(@gen_util, 'Surgical Gloves', 'boxes', 50),
(@gen_util, 'Binding Tape', 'rolls', 20),
(@gen_util, 'Examination Gloves', 'boxes', 50),
(@gen_util, 'Masking Tape', 'rolls', 30),
(@gen_util, 'Sink Pumps', 'pcs', 5),
(@gen_util, 'Ruled Reams', 'reams', 20),
(@gen_util, 'Requirements Clearance Books', 'books', 50),
(@gen_util, 'Receipt Books', 'books', 50),
(@gen_util, 'Photocopying Reams', 'reams', 50),
(@gen_util, 'Payment Voucher Books', 'books', 30),
(@gen_util, 'Binding Rings', 'packs', 20),
(@gen_util, 'Ring Binder Files', 'pcs', 30),
(@gen_util, 'Box Files', 'pcs', 30),
(@gen_util, 'Counter Books', 'books', 20),
(@gen_util, 'Layer File Trays', 'pcs', 10),
(@gen_util, 'Atlas Files', 'pcs', 20),
(@gen_util, 'Domiciliary Kit Bags', 'pcs', 30),
(@gen_util, 'PVC Covers', 'pcs', 50),
(@gen_util, 'Laminating Paper', 'packs', 15),
(@gen_util, 'Liquid Soap', 'liters', 50),
(@gen_util, 'Toilet Papers', 'rolls', 100),
(@gen_util, 'Insulation Tape', 'rolls', 20),
(@gen_util, 'Carbon Papers', 'packs', 20),
(@gen_util, 'Blackboard Dusters', 'pcs', 15);

-- Cleaning & Hygiene (20 items)
INSERT IGNORE INTO store_inventory (category_id, item_name, unit, reorder_level) VALUES
(@cleaning, 'Omo (Detergent)', 'kg', 50),
(@cleaning, 'Vim (Cleaning Powder)', 'pcs', 30),
(@cleaning, 'Jik (Bleach)', 'liters', 30),
(@cleaning, 'Scrubbing Brushes', 'pcs', 20),
(@cleaning, 'Squeezers', 'pcs', 15),
(@cleaning, 'Mops', 'pcs', 20),
(@cleaning, 'Toilet Brushes', 'pcs', 20),
(@cleaning, 'Cobweb Brushes', 'pcs', 15),
(@cleaning, 'Soft Brooms', 'pcs', 20),
(@cleaning, 'Compound Brooms', 'pcs', 15),
(@cleaning, 'Rakes', 'pcs', 10),
(@cleaning, 'Stainless Steel Cleaner', 'liters', 10),
(@cleaning, 'Floor Polish', 'liters', 15),
(@cleaning, 'Air Freshener', 'pcs', 20),
(@cleaning, 'Hand Sanitizer', 'liters', 30),
(@cleaning, 'Disposable Gloves (Cleaning)', 'pairs', 100),
(@cleaning, 'Dustbins', 'pcs', 20),
(@cleaning, 'Dustpans', 'pcs', 15),
(@cleaning, 'Buckets', 'pcs', 20),
(@cleaning, 'Wheelbarrows', 'pcs', 5);

-- Office Stationery (30 items)
INSERT IGNORE INTO store_inventory (category_id, item_name, unit, reorder_level) VALUES
(@stationery, 'A3 Envelopes', 'packs', 20),
(@stationery, 'A4 Envelopes', 'packs', 30),
(@stationery, 'A5 Envelopes', 'packs', 20),
(@stationery, 'Markers (Permanent)', 'pcs', 30),
(@stationery, 'Markers (Whiteboard)', 'pcs', 30),
(@stationery, 'Color Papers', 'packs', 20),
(@stationery, 'Staple Wires', 'boxes', 30),
(@stationery, 'Paper Clips', 'boxes', 30),
(@stationery, 'Chalk (White)', 'boxes', 50),
(@stationery, 'Chalk (Colored)', 'boxes', 30),
(@stationery, 'Pens (Blue)', 'pcs', 100),
(@stationery, 'Pens (Black)', 'pcs', 100),
(@stationery, 'Pens (Red)', 'pcs', 50),
(@stationery, 'Pencils', 'pcs', 100),
(@stationery, 'Rubbers (Erasers)', 'pcs', 50),
(@stationery, 'Office Glue', 'pcs', 30),
(@stationery, 'Stick Glue', 'pcs', 30),
(@stationery, 'Sticky Notes', 'pads', 30),
(@stationery, 'Stapler Machines', 'pcs', 15),
(@stationery, 'Stapler Removers', 'pcs', 15),
(@stationery, 'Hole Punchers', 'pcs', 15),
(@stationery, 'Rulers (30cm)', 'pcs', 30),
(@stationery, 'Scissors', 'pcs', 20),
(@stationery, 'Calculators (Basic)', 'pcs', 10),
(@stationery, 'Bulldog Clips', 'pcs', 30),
(@stationery, 'Highlighter Markers', 'pcs', 30),
(@stationery, 'Correction Fluid', 'pcs', 20),
(@stationery, 'Correction Tape', 'pcs', 20),
(@stationery, 'Manila Envelopes', 'packs', 20),
(@stationery, 'Sticker Labels', 'sheets', 30);

-- Electrical & Hardware (24 items)
INSERT IGNORE INTO store_inventory (category_id, item_name, unit, reorder_level) VALUES
(@electrical, 'Double Gang Switches', 'pcs', 20),
(@electrical, 'Single Gang Switches', 'pcs', 20),
(@electrical, 'Lamp Holders', 'pcs', 30),
(@electrical, 'Single Sockets', 'pcs', 20),
(@electrical, 'Double Sockets', 'pcs', 20),
(@electrical, 'Bulbs (LED 10W)', 'pcs', 50),
(@electrical, 'Bulbs (LED 20W)', 'pcs', 30),
(@electrical, 'Bulbs (LED 40W)', 'pcs', 20),
(@electrical, 'Mounting Boxes', 'pcs', 30),
(@electrical, 'PVC Conduit Pipes', 'pcs', 20),
(@electrical, 'Electrical Cables (1.5mm)', 'meters', 100),
(@electrical, 'Electrical Cables (2.5mm)', 'meters', 100),
(@electrical, 'Socket Spanners', 'sets', 5),
(@electrical, 'Screwdrivers Set', 'sets', 10),
(@electrical, 'Hammers', 'pcs', 10),
(@electrical, 'Combination Pliers', 'pcs', 10),
(@electrical, 'Long Nose Pliers', 'pcs', 10),
(@electrical, 'Measuring Tapes', 'pcs', 10),
(@electrical, 'Padlocks', 'pcs', 20),
(@electrical, 'Door Handles', 'pcs', 20),
(@electrical, 'Door Hinges', 'pcs', 30),
(@electrical, 'WD-40 Lubricant', 'cans', 10),
(@electrical, 'Painter Masking Tape', 'rolls', 20),
(@electrical, 'PVC Glue', 'cans', 10),
(@electrical, 'Super Glue', 'pcs', 20);

-- Food Store Supplies (37 items)
INSERT IGNORE INTO store_inventory (category_id, item_name, unit, reorder_level) VALUES
(@food, 'Posho (Maize Flour)', 'kg', 500),
(@food, 'Rice', 'kg', 300),
(@food, 'Beans', 'kg', 300),
(@food, 'Salt', 'kg', 50),
(@food, 'Cooking Oil', 'liters', 100),
(@food, 'Sugar', 'kg', 100),
(@food, 'Plates (Melamine)', 'pcs', 100),
(@food, 'Plates (Ceramic)', 'pcs', 50),
(@food, 'Cups (Plastic)', 'pcs', 100),
(@food, 'Cups (Ceramic)', 'pcs', 50),
(@food, 'Tablespoons', 'pcs', 100),
(@food, 'Teaspoons', 'pcs', 100),
(@food, 'Forks', 'pcs', 50),
(@food, 'Kitchen Knives', 'pcs', 20),
(@food, 'Sauce Pans', 'pcs', 20),
(@food, 'Cooking Pots (Large)', 'pcs', 10),
(@food, 'Cooking Pots (Medium)', 'pcs', 15),
(@food, 'Frying Pans', 'pcs', 10),
(@food, 'Thermos Flasks', 'pcs', 20),
(@food, 'Water Jugs', 'pcs', 20),
(@food, 'Charcoal', 'bags', 50),
(@food, 'Firewood', 'bundles', 30),
(@food, 'Tea Leaves', 'kg', 20),
(@food, 'Milk Powder', 'kg', 20),
(@food, 'Baking Flour', 'kg', 30),
(@food, 'Tomato Paste', 'cans', 50),
(@food, 'Onions', 'kg', 50),
(@food, 'Irish Potatoes', 'kg', 100),
(@food, 'Matooke (Green Bananas)', 'bunches', 50),
(@food, 'Cassava Flour', 'kg', 50),
(@food, 'Ghee', 'kg', 15),
(@food, 'Groundnut Paste', 'kg', 30),
(@food, 'Soy Flour', 'kg', 30),
(@food, 'Cabbage', 'pcs', 30),
(@food, 'Dried Fish', 'kg', 30),
(@food, 'Pasta (Spaghetti/Macaroni)', 'kg', 30);

-- Kitchen & Dining (10 items)
INSERT IGNORE INTO store_inventory (category_id, item_name, unit, reorder_level) VALUES
(@kitchen, 'Chopping Boards', 'pcs', 10),
(@kitchen, 'Kitchen Towels', 'pcs', 30),
(@kitchen, 'Kitchen Aprons', 'pcs', 20),
(@kitchen, 'Oven Gloves', 'pairs', 10),
(@kitchen, 'Colanders', 'pcs', 10),
(@kitchen, 'Measuring Cups', 'sets', 10),
(@kitchen, 'Water Dispensers', 'pcs', 10),
(@kitchen, 'Ice Cube Trays', 'pcs', 15),
(@kitchen, 'Food Storage Containers', 'pcs', 30),
(@kitchen, 'Serving Trays', 'pcs', 20);

-- Medical Supplies (29 items)
INSERT IGNORE INTO store_inventory (category_id, item_name, unit, reorder_level) VALUES
(@medical, 'Sterile Surgical Gloves', 'boxes', 50),
(@medical, 'Latex Examination Gloves', 'boxes', 100),
(@medical, 'Nitrile Examination Gloves', 'boxes', 50),
(@medical, 'Surgical Face Masks', 'boxes', 100),
(@medical, 'N95 Face Masks', 'boxes', 50),
(@medical, 'Syringes (5ml)', 'packs', 100),
(@medical, 'Syringes (10ml)', 'packs', 50),
(@medical, 'Cotton Wool', 'rolls', 50),
(@medical, 'Gauze Swabs', 'packs', 100),
(@medical, 'Crepe Bandages', 'rolls', 50),
(@medical, 'Elastic Bandages', 'rolls', 30),
(@medical, 'Medical Adhesive Tape', 'rolls', 50),
(@medical, 'Wound Dressings (Plaster)', 'packs', 50),
(@medical, 'Dettol Antiseptic', 'liters', 30),
(@medical, 'Methylated Spirit', 'liters', 30),
(@medical, 'Hydrogen Peroxide', 'liters', 20),
(@medical, 'Betadine Solution', 'liters', 20),
(@medical, 'Digital Thermometers', 'pcs', 20),
(@medical, 'Manual BP Machines', 'pcs', 10),
(@medical, 'Digital BP Machines', 'pcs', 5),
(@medical, 'Stethoscopes', 'pcs', 15),
(@medical, 'Tongue Depressors', 'packs', 50),
(@medical, 'Urine Test Strips', 'packs', 20),
(@medical, 'Specimen Containers', 'pcs', 100),
(@medical, 'Sharps Disposal Containers', 'pcs', 30),
(@medical, 'Disposable Bed Sheets', 'packs', 50),
(@medical, 'Disposable Protective Gowns', 'pcs', 100),
(@medical, 'Disposable Shoe Covers', 'pairs', 100),
(@medical, 'Disposable Hair Caps', 'pcs', 100);

-- ICT Supplies (20 items)
INSERT IGNORE INTO store_inventory (category_id, item_name, unit, reorder_level) VALUES
(@ict, 'HP Toner Cartridges', 'pcs', 10),
(@ict, 'Canon Toner Cartridges', 'pcs', 10),
(@ict, 'Epson Toner Cartridges', 'pcs', 10),
(@ict, 'A4 Printing Paper', 'reams', 100),
(@ict, 'A3 Printing Paper', 'reams', 30),
(@ict, 'Flash Drives (16GB)', 'pcs', 20),
(@ict, 'Flash Drives (32GB)', 'pcs', 10),
(@ict, 'External Hard Drives (1TB)', 'pcs', 5),
(@ict, 'USB Keyboards', 'pcs', 20),
(@ict, 'USB Mice', 'pcs', 20),
(@ict, 'Mouse Pads', 'pcs', 30),
(@ict, 'USB Cables', 'pcs', 20),
(@ict, 'HDMI Cables', 'pcs', 10),
(@ict, 'VGA Cables', 'pcs', 10),
(@ict, 'Power Extension Strips', 'pcs', 20),
(@ict, 'UPS Batteries', 'pcs', 5),
(@ict, 'Cat6 Ethernet Cables', 'pcs', 20),
(@ict, 'Webcams', 'pcs', 5),
(@ict, 'Headphones', 'pcs', 10),
(@ict, 'Printer Label Sheets', 'packs', 15);

-- Furniture & Storage (12 items)
INSERT IGNORE INTO store_inventory (category_id, item_name, unit, reorder_level) VALUES
(@furniture, 'Office Desks', 'pcs', 10),
(@furniture, 'Office Chairs', 'pcs', 20),
(@furniture, 'Visitor Chairs', 'pcs', 20),
(@furniture, '4-Drawer Filing Cabinets', 'pcs', 10),
(@furniture, 'Bookshelves', 'pcs', 10),
(@furniture, 'Large Whiteboards', 'pcs', 10),
(@furniture, 'Small Whiteboards', 'pcs', 15),
(@furniture, 'Cork Notice Boards', 'pcs', 15),
(@furniture, 'Conference Tables', 'pcs', 5),
(@furniture, 'Metal Storage Shelves', 'pcs', 10),
(@furniture, 'Personal Lockers', 'pcs', 20),
(@furniture, 'Waste Paper Baskets', 'pcs', 30);

-- Teaching & Training (15 items)
INSERT IGNORE INTO store_inventory (category_id, item_name, unit, reorder_level) VALUES
(@teaching, 'Skeleton Anatomical Models', 'pcs', 3),
(@teaching, 'Organ Anatomical Models', 'sets', 3),
(@teaching, 'Resuscitation Mannequins', 'pcs', 5),
(@teaching, 'Injection Practice Pads', 'pcs', 20),
(@teaching, 'IV Training Arms', 'pcs', 5),
(@teaching, 'Catheterization Models', 'pcs', 5),
(@teaching, 'Baby Delivery Simulators', 'pcs', 3),
(@teaching, 'First Aid Kits', 'kits', 20),
(@teaching, 'Portable Projectors', 'pcs', 5),
(@teaching, 'Projector Screens', 'pcs', 5),
(@teaching, 'Flip Chart Stands', 'pcs', 10),
(@teaching, 'Flip Chart Pads', 'pads', 30),
(@teaching, 'Nursing Wall Charts', 'sets', 10),
(@teaching, 'Midwifery Wall Charts', 'sets', 10),
(@teaching, 'Educational DVDs', 'pcs', 20);

SELECT CONCAT('Store system setup complete. Total items: ', COUNT(*)) AS status FROM store_inventory;

-- ============================================================
-- END OF STORE MANAGEMENT SYSTEM SCHEMA
-- ============================================================
