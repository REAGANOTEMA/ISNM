-- ═══════════════════════════════════════════════════════════════
-- ISNM STORE REQUISITION SYSTEM
-- Categories, Inventory Items, and Requisition Tables
-- Run: C:\xampp\mysql\bin\mysql.exe -u root -P 3307 igangaschoolofl_staffs_db < STORE_REQUISITION_SYSTEM.sql
-- ═══════════════════════════════════════════════════════════════

-- ── 1. ENSURE CATEGORIES EXIST ──────────────────────────────────
INSERT IGNORE INTO store_categories (id, category_name, description, icon, status) VALUES
(1, 'General Utilities', 'Stationery, electrical, office supplies, cleaning materials', 'fas fa-tools', 'active'),
(2, 'Food Store', 'Food items, kitchen supplies, dining items', 'fas fa-utensils', 'active'),
(3, 'Cleaning & Hygiene', 'Cleaning products, hygiene supplies, sanitation items', 'fas fa-broom', 'active'),
(4, 'Medical Supplies', 'Medical equipment, PPE, pharmaceutical items', 'fas fa-medkit', 'active'),
(5, 'Maintenance', 'Hardware, tools, spare parts, building materials', 'fas fa-wrench', 'active'),
(6, 'ICT Equipment', 'Computers, peripherals, networking equipment', 'fas fa-laptop', 'active'),
(7, 'Furniture', 'Desks, chairs, cabinets, fittings', 'fas fa-chair', 'active'),
(8, 'Vehicles & Transport', 'Vehicle parts, fuel, transport equipment', 'fas fa-truck', 'active');

-- ── 2. CLEAR EXISTING INVENTORY (fresh start) ──────────────────
DELETE FROM store_request_items WHERE request_id IN (SELECT id FROM store_requests);
DELETE FROM store_requests;
DELETE FROM store_inventory;
ALTER TABLE store_requests AUTO_INCREMENT = 1;
ALTER TABLE store_inventory AUTO_INCREMENT = 1;

-- ── 3. GENERAL UTILITIES STORE ──────────────────────────────────
INSERT INTO store_inventory (category_id, item_name, item_code, description, unit, quantity, reorder_level, unit_cost, status) VALUES
-- Cleaning & Hygiene Items (Matron/Warden requisitions)
(3, 'OMO', 'CU-001', 'Washing detergent/powder', 'packet', 100, 20, 5000.00, 'active'),
(3, 'JIK', 'CU-002', 'Bleach/disinfectant', 'bottle', 80, 15, 3500.00, 'active'),
(3, 'VIM', 'CU-003', 'Scouring powder', 'packet', 60, 15, 2500.00, 'active'),
(3, 'Examination Gloves', 'CU-004', 'Disposable examination gloves (box of 100)', 'box', 50, 10, 25000.00, 'active'),
(3, 'Surgical Gloves', 'CU-005', 'Sterile surgical gloves (pair)', 'pair', 40, 10, 8000.00, 'active'),
(3, 'Scrubbing Brushes', 'CU-006', 'Heavy duty scrubbing brush', 'piece', 30, 10, 5000.00, 'active'),
(3, 'Squeezers', 'CU-007', 'Mop wringer/squeezer', 'piece', 15, 5, 15000.00, 'active'),
(3, 'Mops', 'CU-008', 'Floor mop with handle', 'piece', 25, 8, 12000.00, 'active'),
(3, 'Soft Brooms', 'CU-009', 'Soft bristle broom', 'piece', 30, 10, 8000.00, 'active'),
(3, 'Compound Brooms', 'CU-010', 'Heavy duty compound broom', 'piece', 25, 8, 10000.00, 'active'),
(3, 'Ruled Reams', 'CU-011', 'A4 ruled paper ream', 'ream', 50, 15, 15000.00, 'active'),
(3, 'Toilet Brushes', 'CU-012', 'Toilet cleaning brush', 'piece', 20, 8, 6000.00, 'active'),
(3, 'High Dusters (Cobweb Brushes)', 'CU-013', 'Extended reach cobweb brush', 'piece', 10, 5, 15000.00, 'active'),
(3, 'Sink Pumps', 'CU-014', 'Manual sink/drainer pump', 'piece', 8, 3, 25000.00, 'active'),
(3, 'Liquid Soap', 'CU-015', 'Hand washing liquid soap', 'liter', 40, 10, 8000.00, 'active'),
(3, 'Sanitizer', 'CU-016', 'Hand sanitizer (500ml)', 'bottle', 30, 10, 12000.00, 'active'),
(3, 'Toilet Papers', 'CU-017', 'Toilet roll (pack of 4)', 'pack', 50, 15, 10000.00, 'active'),
(3, 'Face Masks', 'CU-018', 'Disposable face masks (box of 50)', 'box', 40, 10, 15000.00, 'active'),
(3, 'Heavy Duty Gloves', 'CU-019', 'Rubber cleaning gloves', 'pair', 30, 10, 8000.00, 'active'),
(3, 'Rake', 'CU-020', 'Garden rake', 'piece', 8, 3, 20000.00, 'active'),
(3, 'Photocopying Reams', 'CU-021', 'A4 plain paper ream', 'ream', 100, 20, 18000.00, 'active'),
(3, 'Blackboard Dusters', 'CU-022', 'Chalkboard duster/eraser', 'piece', 15, 5, 5000.00, 'active'),
(3, 'Chalk', 'CU-023', 'Whiteboard/chalk chalk (box)', 'box', 30, 10, 3000.00, 'active'),
(3, 'Markers', 'CU-024', 'Whiteboard markers (pack of 4)', 'pack', 25, 8, 8000.00, 'active'),
(3, 'Highlighter Markers', 'CU-025', 'Highlighter pen set', 'set', 20, 5, 6000.00, 'active'),
(3, 'Pens', 'CU-026', 'Ballpoint pen (box of 50)', 'box', 30, 10, 12000.00, 'active'),
(3, 'Rubbers', 'CU-027', 'Eraser/rubber', 'piece', 40, 10, 2000.00, 'active'),
(3, 'Office Glue', 'CU-028', 'Liquid adhesive glue', 'bottle', 25, 8, 3000.00, 'active'),
(3, 'Sticker Notes', 'CU-029', 'Sticky notes (pack of 12)', 'pack', 20, 5, 5000.00, 'active'),
(3, 'Stick Glue', 'CU-030', 'Glue stick', 'piece', 30, 10, 2000.00, 'active'),
(3, 'Insulation Tape', 'CU-031', 'Electrical insulation tape', 'roll', 20, 5, 3000.00, 'active'),
(3, 'Binding Tape', 'CU-032', 'Document binding tape', 'roll', 15, 5, 4000.00, 'active'),
(3, 'Masking Tape', 'CU-033', 'Masking tape roll', 'roll', 15, 5, 4000.00, 'active'),
(3, 'Binding Rings', 'CU-034', 'Document binding rings (pack)', 'pack', 20, 5, 5000.00, 'active'),
(3, 'Ring Binder Files', 'CU-035', 'Ring binder folder', 'piece', 30, 10, 8000.00, 'active'),
(3, 'Box Files', 'CU-036', 'Document box file', 'piece', 25, 8, 10000.00, 'active'),
(3, 'Counter Books', 'CU-037', 'Counter exercise book', 'piece', 50, 15, 3000.00, 'active'),
(3, 'Envelops A3', 'CU-038', 'A3 envelope', 'piece', 100, 20, 1500.00, 'active'),
(3, 'Envelops A4', 'CU-039', 'A4 envelope', 'piece', 200, 30, 1000.00, 'active'),
(3, 'Envelops A5', 'CU-040', 'A5 envelope', 'piece', 150, 25, 800.00, 'active'),
(3, 'Color Papers', 'CU-041', 'Colored A4 paper (pack)', 'pack', 20, 5, 12000.00, 'active'),
(3, 'Layer File Trays', 'CU-042', 'Document tray/organizer', 'piece', 15, 5, 15000.00, 'active'),
(3, 'Laminating Paper', 'CU-043', 'Laminating pouch A4 (pack of 100)', 'pack', 10, 3, 25000.00, 'active'),
(3, 'Staple Wires', 'CU-044', 'Stapler refill wires (pack)', 'pack', 30, 10, 3000.00, 'active'),
(3, 'Paper Clips', 'CU-045', 'Paper clips (box)', 'box', 25, 8, 2000.00, 'active'),
(3, 'PVC Covers', 'CU-046', 'Document PVC cover', 'piece', 100, 20, 500.00, 'active'),
(3, 'Atlas Files', 'CU-047', 'Atlas file folder', 'piece', 20, 5, 12000.00, 'active'),
(3, 'Carbon Papers', 'CU-048', 'Carbon paper (pack of 100)', 'pack', 10, 3, 8000.00, 'active'),
(3, 'Receipt Books', 'CU-049', 'Duplicate receipt book', 'book', 20, 5, 15000.00, 'active'),
(3, 'Payment Voucher Books', 'CU-050', 'Payment voucher book', 'book', 10, 3, 20000.00, 'active'),
(3, 'Requirements Clearance Books', 'CU-051', 'Clearance/requirements record book', 'book', 10, 3, 18000.00, 'active'),
(3, 'Dormeciliary Kit Bags', 'CU-052', 'Dormitory kit bag', 'piece', 15, 5, 25000.00, 'active'),

-- Electrical Items
(1, 'Switches Double Gang', 'EL-001', 'Double gang electrical switch', 'piece', 20, 5, 15000.00, 'active'),
(1, 'Switches Single Gang', 'EL-002', 'Single gang electrical switch', 'piece', 25, 8, 10000.00, 'active'),
(1, 'Sockets Single', 'EL-003', 'Single electrical socket', 'piece', 20, 5, 12000.00, 'active'),
(1, 'Sockets Double', 'EL-004', 'Double electrical socket', 'piece', 15, 5, 18000.00, 'active'),
(1, 'Bulbs', 'EL-005', 'LED light bulb', 'piece', 50, 15, 5000.00, 'active'),
(1, 'Lamp Holders', 'EL-006', 'Bulb lamp holder', 'piece', 20, 5, 8000.00, 'active'),
(1, 'Mounding Boxes', 'EL-007', 'Electrical mounding box', 'piece', 15, 5, 6000.00, 'active');

-- ── 4. FOOD STORE SUPPLIES ──────────────────────────────────────
INSERT INTO store_inventory (category_id, item_name, item_code, description, unit, quantity, reorder_level, unit_cost, status) VALUES
(2, 'Posho', 'FD-001', 'Maize flour/posho', 'kg', 500, 100, 3500.00, 'active'),
(2, 'Rice', 'FD-002', 'White rice', 'kg', 200, 50, 8000.00, 'active'),
(2, 'Beans', 'FD-003', 'Dried beans', 'kg', 150, 40, 5000.00, 'active'),
(2, 'Salt', 'FD-004', 'Table salt', 'kg', 50, 15, 2000.00, 'active'),
(2, 'Cooking Oil', 'FD-005', 'Vegetable cooking oil', 'liter', 100, 25, 8000.00, 'active'),
(2, 'Sugar', 'FD-006', 'White sugar', 'kg', 100, 25, 4000.00, 'active'),
(2, 'Plates', 'FD-007', 'Dining plate', 'piece', 100, 20, 8000.00, 'active'),
(2, 'Charcoal', 'FD-008', 'Cooking charcoal', 'bag', 30, 10, 25000.00, 'active');

-- ── 5. ENSURE REQUEST TABLES HAVE PROPER STRUCTURE ─────────────
-- Columns (requester_role, priority) already exist — skip ALTER

-- ── 6. ADD MODULES (use correct department_ids from module_departments) ──
INSERT IGNORE INTO system_modules (id, name, label, department_id, icon, route, tables_json, description, sort_order, is_active, is_student_module, is_document_module) VALUES
(114, 'store_requisition', 'Store Requisition', 6, 'shopping-cart', '../dashboards/matrons.php?page=store_requisition', '{"main":["store_requests","store_request_items"]}', 'Submit store requisitions for hostels and departments', 1, 1, 0, 0),
(115, 'warden_requisition', 'Warden Requisition', 6, 'shopping-cart', '../dashboards/wardens.php?page=warden_requisition', '{"main":["store_requests","store_request_items"]}', 'Submit store requisitions from hostel wardens', 2, 1, 0, 0),
(116, 'inventory_management', 'Inventory Management', 6, 'boxes', '../dashboards/storekeeper.php?tab=inventory', '{"main":["store_inventory","store_categories","store_transactions"]}', 'Manage store inventory, categories and stock levels', 3, 1, 0, 0),
(117, 'requisition_processing', 'Requisition Processing', 6, 'clipboard-list', '../dashboards/storekeeper.php?tab=requests', '{"main":["store_requests","store_request_items"]}', 'Process and fulfill incoming requisitions', 4, 1, 0, 0),
(118, 'store_approval', 'Store Approval', 1, 'check-circle', '../dashboards/director-general.php?page=store', '{"main":["store_requests","store_request_items"]}', 'DG approval for store requisitions', 5, 1, 0, 0);

-- ── 7. ASSIGN PERMISSIONS (correct role IDs) ──────────────────────
-- Matron (35) - view + create store_requisition
-- Warden (36) - view + create warden_requisition
-- Storekeeper (21) - full CRUD inventory_management, view+edit+approve requisition_processing
-- DG (1) - view + approve store_approval
DELETE FROM module_permissions WHERE module_id IN (114,115,116,117,118);
INSERT INTO module_permissions (role_id, module_id, can_view, can_create, can_edit, can_delete, can_approve, can_export) VALUES
(35, 114, 1, 1, 0, 0, 0, 0),
(36, 115, 1, 1, 0, 0, 0, 0),
(21, 116, 1, 1, 1, 1, 0, 1),
(21, 117, 1, 0, 1, 0, 1, 0),
(1, 118, 1, 0, 0, 0, 1, 0);

-- ── 8. SEED SAMPLE REQUISITIONS ────────────────────────────────────
INSERT INTO store_requests (request_number, requested_by, requester_name, requester_role, department, items, urgency, status, notes, created_at) VALUES
('REQ-2026-001', 22, 'Matron Grace', 'matron', 'Dormitory', 'OMO, JIK, Mops, Toilet Brushes', 'medium', 'pending', 'Monthly cleaning supplies for female dormitory', NOW()),
('REQ-2026-002', 23, 'Warden James', 'warden', 'Hostel A', 'Surgical Gloves, Examination Gloves, Liquid Soap', 'high', 'pending', 'Urgent hygiene supplies for hostel A sickbay', NOW()),
('REQ-2026-003', 22, 'Matron Grace', 'matron', 'Dormitory', 'Toilet Papers, Sanitizer, Face Masks', 'medium', 'approved', 'Weekly hygiene supplies', DATE_SUB(NOW(), INTERVAL 2 DAY)),
('REQ-2026-004', 20, 'Storekeeper Peter', 'storekeeper', 'Store', 'Posho, Rice, Beans, Cooking Oil', 'high', 'fulfilled', 'Food store restocking', DATE_SUB(NOW(), INTERVAL 3 DAY)),
('REQ-2026-005', 23, 'Warden James', 'warden', 'Hostel B', 'Bulbs, Switches, Sockets', 'low', 'rejected', 'Electrical maintenance items', DATE_SUB(NOW(), INTERVAL 5 DAY));

-- Add request items for sample requisitions
INSERT INTO store_request_items (request_id, item_id, quantity_requested, quantity_fulfilled, notes, status) VALUES
(1, 1, 10, 10, 'OMO packets for cleaning', 'fulfilled'),
(1, 2, 5, 5, 'JIK bleach bottles', 'fulfilled'),
(1, 8, 8, 8, 'Mops for dormitory', 'fulfilled'),
(1, 12, 10, 10, 'Toilet brushes', 'fulfilled'),
(2, 5, 20, 20, 'Surgical gloves pairs', 'fulfilled'),
(2, 4, 10, 10, 'Examination gloves boxes', 'fulfilled'),
(2, 15, 5, 5, 'Liquid soap liters', 'fulfilled'),
(3, 17, 20, 20, 'Toilet paper packs', 'fulfilled'),
(3, 16, 10, 10, 'Sanitizer bottles', 'fulfilled'),
(3, 18, 5, 5, 'Face mask boxes', 'fulfilled'),
(4, 33, 100, 100, 'Posho kg', 'fulfilled'),
(4, 34, 50, 50, 'Rice kg', 'fulfilled'),
(4, 35, 30, 30, 'Beans kg', 'fulfilled'),
(4, 36, 20, 20, 'Cooking oil liters', 'fulfilled'),
(5, 39, 20, 0, 'Bulbs for hostel', 'pending'),
(5, 40, 10, 0, 'Double gang switches', 'pending'),
(5, 42, 5, 0, 'Double sockets', 'pending');

SELECT 'STORE REQUISITION SYSTEM COMPLETE' as Status, 
       (SELECT COUNT(*) FROM store_categories) as Categories,
       (SELECT COUNT(*) FROM store_inventory) as InventoryItems,
       (SELECT COUNT(*) FROM store_requests) as Requisitions,
       (SELECT COUNT(*) FROM store_request_items) as RequisitionItems;
