-- Add missing columns to existing tables (if they don't exist)
-- This is safe to run multiple times

-- Add columns to lab_computers if missing
SET @schema = (SELECT DATABASE());
CALL AddColIfMissing(@schema, 'lab_computers', 'lab_name', 'VARCHAR(100) DEFAULT NULL AFTER `computer_name`');
CALL AddColIfMissing(@schema, 'lab_computers', 'operating_system', 'VARCHAR(100) DEFAULT NULL AFTER `os_installed`');
CALL AddColIfMissing(@schema, 'lab_computers', 'assigned_to', 'VARCHAR(200) DEFAULT NULL');
CALL AddColIfMissing(@schema, 'lab_computers', 'purchase_date', 'DATE DEFAULT NULL');
CALL AddColIfMissing(@schema, 'lab_computers', 'warranty_expiry', 'DATE DEFAULT NULL');

-- Add columns to lab_bookings if missing
CALL AddColIfMissing(@schema, 'lab_bookings', 'lab_room_id', 'INT DEFAULT NULL');
CALL AddColIfMissing(@schema, 'lab_bookings', 'user_id', 'INT DEFAULT NULL');
CALL AddColIfMissing(@schema, 'lab_bookings', 'semester', 'VARCHAR(20) DEFAULT NULL');

-- Add columns to it_support_tickets if missing
CALL AddColIfMissing(@schema, 'it_support_tickets', 'assigned_to', 'INT DEFAULT NULL');
CALL AddColIfMissing(@schema, 'it_support_tickets', 'resolution_notes', 'TEXT DEFAULT NULL');
CALL AddColIfMissing(@schema, 'it_support_tickets', 'resolved_at', 'TIMESTAMP NULL DEFAULT NULL');
