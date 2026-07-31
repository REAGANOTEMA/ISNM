<?php
/**
 * Authoritative, idempotent schema for `student_invoices` (students DB).
 *
 * Historically the table was created by several competing DDLs (school-bursar,
 * director-finance, student-management, setup-staff, run_migrations) with
 * incompatible column sets. Every module now routes through
 * ensureStudentInvoicesSchema() so fresh installs and existing databases
 * converge on one schema that satisfies all consumers:
 *
 *   - fee-payment portal  : fee_type, total_amount, amount_paid, status, issue_date
 *   - school-bursar       : amount_paid, net_amount (generated, read-only)
 *   - director-finance    : balance (generated), tuition_amount, accommodation_amount
 *   - student-management  : student_id, status
 */

if (!function_exists('student_invoices_authoritative_ddl')) {
    function student_invoices_authoritative_ddl(): string {
        return "CREATE TABLE IF NOT EXISTS `student_invoices` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `invoice_number` varchar(50) NOT NULL,
            `student_id` int(11) NOT NULL,
            `fee_assignment_id` int(11) DEFAULT NULL,
            `fee_type` varchar(100) NOT NULL,
            `description` text DEFAULT NULL,
            `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
            `tuition_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
            `accommodation_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
            `discount_amount` decimal(12,2) DEFAULT 0.00,
            `net_amount` decimal(12,2) GENERATED ALWAYS AS (`total_amount` - `discount_amount`) STORED,
            `amount_paid` decimal(12,2) DEFAULT 0.00,
            `balance` decimal(12,2) GENERATED ALWAYS AS (`net_amount` - `amount_paid`) STORED,
            `status` enum('Draft','Pending','Partially Paid','Paid','Overdue','Cancelled','Waived') DEFAULT 'Pending',
            `due_date` date DEFAULT NULL,
            `issue_date` date DEFAULT curdate(),
            `payment_method` varchar(50) DEFAULT NULL,
            `created_by` int(11) DEFAULT NULL,
            `created_at` timestamp NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_invoice_number` (`invoice_number`),
            INDEX `idx_si_student` (`student_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    }
}

if (!function_exists('ensureStudentInvoicesSchema')) {
    /**
     * Create and/or repair the student_invoices table so it matches the
     * authoritative schema. Safe to call on every request.
     *
     * @param mysqli|null $conn Students-database connection.
     * @return bool True when the schema is in a usable state.
     */
    function ensureStudentInvoicesSchema($conn): bool {
        if (!$conn) return false;
        try {
            $conn->query(student_invoices_authoritative_ddl());

            // 1) id must be PRIMARY KEY + AUTO_INCREMENT
            $pk = $conn->query("SHOW INDEX FROM student_invoices WHERE Key_name = 'PRIMARY'");
            if (!$pk || $pk->num_rows === 0) {
                $conn->query("ALTER TABLE student_invoices MODIFY id int(11) NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (id)");
            } else {
                $col = $conn->query("SHOW COLUMNS FROM student_invoices LIKE 'id'");
                $f = $col ? $col->fetch_assoc() : null;
                if ($f && stripos((string)($f['Extra'] ?? ''), 'auto_increment') === false) {
                    $conn->query("ALTER TABLE student_invoices MODIFY id int(11) NOT NULL AUTO_INCREMENT");
                }
            }

            // 2) invoice_number must be unique; backfill legacy duplicate values first
            $uniq = $conn->query("SHOW INDEX FROM student_invoices WHERE Key_name = 'uk_invoice_number'");
            if (!$uniq || $uniq->num_rows === 0) {
                $conn->query(
                    "UPDATE student_invoices SET invoice_number = CONCAT('INV-', id)
                     WHERE invoice_number = '' OR invoice_number IS NULL
                        OR invoice_number IN (
                            SELECT inv FROM (
                                SELECT invoice_number inv FROM student_invoices
                                GROUP BY invoice_number HAVING COUNT(*) > 1
                            ) d
                        )"
                );
                $conn->query("ALTER TABLE student_invoices ADD UNIQUE KEY uk_invoice_number (invoice_number)");
            }

            // 3) finance columns required by director-finance reports
            foreach (['tuition_amount' => 'decimal(12,2) NOT NULL DEFAULT 0.00',
                      'accommodation_amount' => 'decimal(12,2) NOT NULL DEFAULT 0.00'] as $colName => $colDef) {
                $cols = $conn->query("SHOW COLUMNS FROM student_invoices LIKE '$colName'");
                if (!$cols || $cols->num_rows === 0) {
                    $conn->query("ALTER TABLE student_invoices ADD COLUMN `$colName` $colDef AFTER `total_amount`");
                }
            }

            // 4) student_id index (school-bursar / finance lookups)
            $idx = $conn->query("SHOW INDEX FROM student_invoices WHERE Key_name = 'idx_si_student'");
            if (!$idx || $idx->num_rows === 0) {
                $conn->query("ALTER TABLE student_invoices ADD INDEX idx_si_student (student_id)");
            }

            // 5) fee_type must be NOT NULL (portal writes it)
            $ft = $conn->query("SHOW COLUMNS FROM student_invoices LIKE 'fee_type'");
            if ($ft && $ft->num_rows > 0) {
                $row = $ft->fetch_assoc();
                if (strtoupper((string)$row['Null']) === 'YES') {
                    $conn->query("ALTER TABLE student_invoices MODIFY fee_type varchar(100) NOT NULL");
                }
            }
            return true;
        } catch (\Throwable $e) {
            error_log('ensureStudentInvoicesSchema failed: ' . $e->getMessage());
            return false;
        }
    }
}
