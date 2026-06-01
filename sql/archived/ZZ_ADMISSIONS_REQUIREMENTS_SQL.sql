-- ═══════════════════════════════════════════════════════════════════════════════
-- ISNM — Admissions, Requirements & Clearances: SQL Additions
-- Target DB : igangaschoolofl_staffs_db   (role + staff record)
-- Target DB : igangaschoolofl_students_db (requirements, clearances, messages)
-- ═══════════════════════════════════════════════════════════════════════════════

USE igangaschoolofl_staffs_db;

-- ── 1. New staff role ───────────────────────────────────────────────────────
INSERT IGNORE INTO staff_roles
    (role_name, role_description, role_level, dashboard_path, permissions)
VALUES
    ('Director Admissions & Requirements',
     'Oversees student admissions processing and all equipment/requirement
      clearances. Receives messages from Matron and School Secretary.
      Has printable receipts, clearance forms and student profiles.',
     'Management',
     'dashboards/admissions-requirements.php',
     '{"admissions": true, "requirements": true, "clearance": true,
       "can_approve_admissions": true, "can_print_receipts": true,
       "can_view_finances": true, "can_manage_requirements": true,
       "can_view_messages": true, "can_view_all_students": true}'
    );

-- ── 2. Staff account ─────────────────────────────────────────────────────────
-- Password: 2268926931  (plain — handled by AuthenticationService default fallback)
INSERT IGNORE INTO staff
    (staff_id, full_name, email, password, position, department, role_id,
     status, hire_date, password_changed, is_first_login, created_at)
SELECT
    'ADMREQ001',
    'Director Admissions & Requirements',
    'admissions@igangaschoolofnursingandmidwifery.ac.ug',
    '2268926931',
    'Director Admissions & Requirements',
    'Admissions & Requirements Office',
    (SELECT id FROM staff_roles WHERE role_name = 'Director Admissions & Requirements' LIMIT 1),
    'Active',
    CURDATE(),
    FALSE, TRUE, NOW()
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM staff WHERE email = 'admissions@igangaschoolofnursingandmidwifery.ac.ug'
);

-- ── 3. Dashboard-access row ─────────────────────────────────────────────────
INSERT IGNORE INTO staff_dashboard_access
    (staff_id, dashboard_path, access_level, granted_by)
SELECT
    s.id,
    'dashboards/admissions-requirements.php',
    'Full',
    1
FROM staff s
JOIN staff_roles sr ON s.role_id = sr.id
WHERE sr.role_name = 'Director Admissions & Requirements';

-- ── 4. Update staff_dashboard_access for existing "Director General"
--     (she must also see the admissions & requirements dashboard)
INSERT IGNORE INTO staff_dashboard_access
    (staff_id, dashboard_path, access_level, granted_by)
SELECT
    s.id,
    'dashboards/admissions-requirements.php',
    'Full',
    1
FROM staff s
JOIN staff_roles sr ON s.role_id = sr.id
WHERE sr.role_name IN ('Director General', 'CEO', 'Director Academics');

-- ── 5. Update staff_dashboard_access for Director General finance-dashboard
INSERT IGNORE INTO staff_dashboard_access
    (staff_id, dashboard_path, access_level, granted_by)
SELECT
    s.id,
    'dashboards/director-finance.php',
    'Full',
    1
FROM staff s
JOIN staff_roles sr ON s.role_id = sr.id
WHERE sr.role_name IN ('Director General', 'CEO');

-- ═══════════════════════════════════════════════════════════════════════════════
USE igangaschoolofl_students_db;

-- ── 6. Requirement items master table ──────────────────────────────────────
DROP TABLE IF EXISTS requirement_items;
CREATE TABLE requirement_items (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    item_name     VARCHAR(120) NOT NULL UNIQUE,
    item_category ENUM('General Supplies','Cleaning Materials','Stationery','Personal Protective Equipment','Other') NOT NULL DEFAULT 'General Supplies',
    description   TEXT,
    is_mandatory  BOOLEAN DEFAULT TRUE,
    display_order INT DEFAULT 0,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_item_name (item_name),
    INDEX idx_category  (item_category)
) ENGINE=InnoDB;

-- ── 7. Student requirement clearances ───────────────────────────────────────
DROP TABLE IF EXISTS requirement_clearances;
CREATE TABLE requirement_clearances (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    student_id      INT   NOT NULL,
    item_id         INT   NOT NULL,
    cleared         BOOLEAN DEFAULT FALSE,
    cleared_by      INT   NULL,
    cleared_at      TIMESTAMP NULL,
    notes           TEXT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id)  ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (item_id)    REFERENCES requirement_items(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    UNIQUE KEY uq_student_item (student_id, item_id),
    INDEX idx_student_id (student_id),
    INDEX idx_cleared    (cleared)
) ENGINE=InnoDB;

-- Trigger: set cleared_at timestamp when cleared flips to TRUE
DROP TRIGGER IF EXISTS trg_req_cleared_at;
DELIMITER $$
CREATE TRIGGER trg_req_cleared_at
BEFORE UPDATE ON requirement_clearances
FOR EACH ROW
BEGIN
    IF NEW.cleared = TRUE AND OLD.cleared = FALSE THEN
        SET NEW.cleared_at = NOW();
    END IF;
END$$
DELIMITER ;

-- ── 8. Admissions requirement messages ──────────────────────────────────────
DROP TABLE IF EXISTS requirement_messages;
CREATE TABLE requirement_messages (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    sender_type ENUM('matron','secretary','admissions') NOT NULL,
    sender_id   INT   NOT NULL,
    recipient_type ENUM('matron','secretary','admissions') NOT NULL,
    recipient_id INT  NOT NULL,
    message     TEXT  NOT NULL,
    is_read     BOOLEAN DEFAULT FALSE,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sender      (sender_type, sender_id),
    INDEX idx_recipient   (recipient_type, recipient_id),
    INDEX idx_created_at  (created_at)
) ENGINE=InnoDB;

-- ── 9. Seed all 20 requirement items ────────────────────────────────────────
INSERT INTO requirement_items (item_name, item_category, display_order) VALUES
    ('Surgical Gloves',         'Personal Protective Equipment',  1),
    ('Examination Gloves',      'Personal Protective Equipment',  2),
    ('Photocopying Ream',       'Stationery',                     3),
    ('Ruled Paper Reams',       'Stationery',                     4),
    ('Omo',                     'Cleaning Materials',             5),
    ('Toilet Papers',           'Cleaning Materials',             6),
    ('Compound Brooms',         'Cleaning Materials',             7),
    ('Soft Brooms',             'Cleaning Materials',             8),
    ('Rake',                    'Cleaning Materials',             9),
    ('Cobweb Brush',            'Cleaning Materials',            10),
    ('Scrubbing Brush',         'Cleaning Materials',            11),
    ('Squeezer',                'Cleaning Materials',            12),
    ('Toilet Brush',            'Cleaning Materials',            13),
    ('JIK',                     'Cleaning Materials',            14),
    ('Vim',                     'Cleaning Materials',            15),
    ('Mops',                    'Cleaning Materials',            16),
    ('Sanitizer',               'General Supplies',              17),
    ('Liquid Soap',             'General Supplies',              18),
    ('Face Masks',              'Personal Protective Equipment', 19),
    ('Heavy Duty Gloves',       'Personal Protective Equipment', 20);

-- ── 10. Update staff_dashboard_access for Director General ───────────────────
--     so the DG can also jump to the admissions dashboard
INSERT IGNORE INTO staff_dashboard_access (staff_id, dashboard_path, access_level, granted_by)
SELECT
    s.id, 'dashboards/admissions-requirements.php', 'Full', 1
FROM staff s
JOIN staff_roles sr ON s.role_id = sr.id
WHERE sr.role_name IN ('Director General', 'CEO', 'Director Academics');
