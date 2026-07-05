-- ============================================================
-- Payroll Master Setup Migration
-- Target DB: igangaschoolofl_staffs_db
-- Uses stored procedures for safe column additions
-- Run: mysql -u root -p igangaschoolofl_staffs_db < payroll_master_setup.sql
-- ============================================================

-- 1. payroll_runs: add comprehensive total columns
DROP PROCEDURE IF EXISTS MigratePayroll;
DELIMITER ;;
CREATE PROCEDURE MigratePayroll()
BEGIN
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payroll_runs' AND COLUMN_NAME='total_paye') THEN
        ALTER TABLE `payroll_runs` ADD COLUMN `total_paye` DECIMAL(15,2) DEFAULT 0.00 AFTER `total_gross`;
    END IF;
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payroll_runs' AND COLUMN_NAME='total_nssf') THEN
        ALTER TABLE `payroll_runs` ADD COLUMN `total_nssf` DECIMAL(15,2) DEFAULT 0.00 AFTER `total_paye`;
    END IF;
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payroll_runs' AND COLUMN_NAME='run_date') THEN
        ALTER TABLE `payroll_runs` ADD COLUMN `run_date` DATE DEFAULT NULL AFTER `end_date`;
    END IF;
    -- Extend status enum
    ALTER TABLE `payroll_runs` MODIFY COLUMN `status` ENUM('draft','approved','processed','paid','completed','processing') DEFAULT 'draft';

    -- payroll_details: add housing_allowance, transport_allowance
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payroll_details' AND COLUMN_NAME='housing_allowance') THEN
        ALTER TABLE `payroll_details` ADD COLUMN `housing_allowance` DECIMAL(12,2) DEFAULT 0.00 AFTER `basic_salary`;
    END IF;
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payroll_details' AND COLUMN_NAME='transport_allowance') THEN
        ALTER TABLE `payroll_details` ADD COLUMN `transport_allowance` DECIMAL(12,2) DEFAULT 0.00 AFTER `housing_allowance`;
    END IF;
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payroll_details' AND COLUMN_NAME='`status`') THEN
        ALTER TABLE `payroll_details` ADD COLUMN `status` VARCHAR(20) DEFAULT 'calculated' AFTER `payment_status`;
    END IF;

    -- payroll_employees: add allowance columns
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payroll_employees' AND COLUMN_NAME='housing_allowance') THEN
        ALTER TABLE `payroll_employees` ADD COLUMN `housing_allowance` DECIMAL(12,2) DEFAULT 0.00 AFTER `basic_salary`;
    END IF;
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payroll_employees' AND COLUMN_NAME='transport_allowance') THEN
        ALTER TABLE `payroll_employees` ADD COLUMN `transport_allowance` DECIMAL(12,2) DEFAULT 0.00 AFTER `housing_allowance`;
    END IF;

    -- salary_structures: add staff_id FK
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='salary_structures' AND COLUMN_NAME='staff_id') THEN
        ALTER TABLE `salary_structures` ADD COLUMN `staff_id` INT(11) DEFAULT NULL AFTER `id`;
        ALTER TABLE `salary_structures` ADD INDEX `idx_ss_staff_id` (`staff_id`);
    END IF;
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='salary_structures' AND COLUMN_NAME='housing_allowance') THEN
        ALTER TABLE `salary_structures` ADD COLUMN `housing_allowance` DECIMAL(12,2) DEFAULT 0.00 AFTER `base_salary`;
    END IF;
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='salary_structures' AND COLUMN_NAME='transport_allowance') THEN
        ALTER TABLE `salary_structures` ADD COLUMN `transport_allowance` DECIMAL(12,2) DEFAULT 0.00 AFTER `housing_allowance`;
    END IF;

    -- bursar_vat_reports: add created_at
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='bursar_vat_reports' AND COLUMN_NAME='created_at') THEN
        ALTER TABLE `bursar_vat_reports` ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER `status`;
    END IF;

    -- bursar_withholding_tax: add period column
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='bursar_withholding_tax' AND COLUMN_NAME='period') THEN
        ALTER TABLE `bursar_withholding_tax` ADD COLUMN `period` VARCHAR(20) DEFAULT NULL AFTER `tax_date`;
    END IF;

    -- payroll_approvals: extend level enum to include CEO, add comments support
    ALTER TABLE `payroll_approvals` MODIFY COLUMN `level` ENUM('HR','PayrollOfficer','Bursar','DirectorFinance','CEO') NOT NULL;
END;;
DELIMITER ;
CALL MigratePayroll();
DROP PROCEDURE IF EXISTS MigratePayroll;

-- ============================================================
-- Students DB migrations (run separately if needed)
-- ============================================================
-- ALTER TABLE igangaschoolofl_students_db.payments MODIFY COLUMN id INT(11) NOT NULL AUTO_INCREMENT;
-- ALTER TABLE igangaschoolofl_students_db.fee_adjustments MODIFY COLUMN id INT(11) NOT NULL AUTO_INCREMENT;
-- ALTER TABLE igangaschoolofl_students_db.budgets MODIFY COLUMN id INT(11) NOT NULL AUTO_INCREMENT;
-- ALTER TABLE igangaschoolofl_students_db.student_notifications MODIFY COLUMN id INT(11) NOT NULL AUTO_INCREMENT;