<?php
/**
 * ISNM Centralized Finance Synchronization
 *
 * Provides a single source of truth for all financial operations across the
 * students and staffs databases. Every payment, expense, and fee-status query
 * goes through this class so that the bursar, director-general, director-finance
 * and every other dashboard always sees the same data.
 *
 * Usage:
 *   $fs = getFinanceSync();                       // singleton
 *   $id = $fs->recordPayment([ ... ]);            // dual-write
 *   $summary = $fs->getPaymentSummary();
 *
 * Tables auto-created on first use (both DBs):
 *   payments | expenses | fee_structures | student_fee_tracking
 *
 * @package ISNM\Finance
 */

require_once __DIR__ . '/../config/database.php';

/* ───────────────────────────────────────────────────────────────────────
   Singleton accessor
   ─────────────────────────────────────────────────────────────────────── */

if (!function_exists('getFinanceSync')) {
    /**
     * Return the singleton FinanceSync instance.
     *
     * @return FinanceSync
     */
    function getFinanceSync(): FinanceSync
    {
        static $instance = null;
        if ($instance === null) {
            $instance = new FinanceSync();
        }
        return $instance;
    }
}

/* ───────────────────────────────────────────────────────────────────────
   FinanceSync class
   ─────────────────────────────────────────────────────────────────────── */

class FinanceSync
{
    /** @var mysqli|null Students database connection */
    private $studentsDb;

    /** @var mysqli|null Staffs database connection */
    private $staffsDb;

    /** @var bool Whether we have already attempted to ensure tables exist */
    private $tablesEnsured = false;

    /* ───────────────────────────────────────────────────────────────────
       Constructor – lazy-connect to both databases
       ─────────────────────────────────────────────────────────────────── */

    public function __construct()
    {
        $this->connect();
    }

    /* ───────────────────────────────────────────────────────────────────
       Connection helpers
       ─────────────────────────────────────────────────────────────────── */

    /**
     * Establish (or reuse cached) connections to both databases.
     */
    private function connect(): void
    {
        $this->studentsDb = function_exists('getStudentsConnection') ? getStudentsConnection() : null;
        $this->staffsDb   = function_exists('getStaffConnection')   ? getStaffConnection()   : null;

        $this->log('Connections established', [
            'students' => $this->studentsDb ? 'ok' : 'failed',
            'staffs'   => $this->staffsDb   ? 'ok' : 'failed',
        ]);
    }

    /**
     * Return the students DB connection, reconnecting if necessary.
     *
     * @return mysqli|null
     */
    private function sDb()
    {
        if ($this->studentsDb === null || !($this->studentsDb instanceof mysqli)) {
            $this->studentsDb = function_exists('getStudentsConnection') ? getStudentsConnection() : null;
        }
        return $this->studentsDb;
    }

    /**
     * Return the staffs DB connection, reconnecting if necessary.
     *
     * @return mysqli|null
     */
    private function sTf()
    {
        if ($this->staffsDb === null || !($this->staffsDb instanceof mysqli)) {
            $this->staffsDb = function_exists('getStaffConnection') ? getStaffConnection() : null;
        }
        return $this->staffsDb;
    }

    /* ───────────────────────────────────────────────────────────────────
       Table auto-creation
       ─────────────────────────────────────────────────────────────────── */

    /**
     * Ensure all finance tables exist in both databases.
     * Called once per request on first operation.
     */
    private function ensureTables(): void
    {
        if ($this->tablesEnsured) {
            return;
        }
        $this->tablesEnsured = true;

        $paymentsTable = "CREATE TABLE IF NOT EXISTS `payments` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `payment_reference` VARCHAR(50) NOT NULL,
            `student_id` INT(11) NOT NULL,
            `amount_received` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            `payment_method` VARCHAR(50) DEFAULT 'Cash',
            `transaction_ref` VARCHAR(100) DEFAULT NULL,
            `slip_number` VARCHAR(100) DEFAULT NULL,
            `payment_date` DATE DEFAULT NULL,
            `status` VARCHAR(20) DEFAULT 'completed',
            `received_by` INT(11) DEFAULT NULL,
            `notes` TEXT DEFAULT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uniq_pay_ref` (`payment_reference`),
            KEY `idx_pay_student` (`student_id`),
            KEY `idx_pay_date` (`payment_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

        $expensesTable = "CREATE TABLE IF NOT EXISTS `expenses` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `title` VARCHAR(255) NOT NULL,
            `amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            `category` VARCHAR(100) DEFAULT NULL,
            `description` TEXT DEFAULT NULL,
            `expense_date` DATE DEFAULT NULL,
            `status` VARCHAR(20) DEFAULT 'pending',
            `created_by` INT(11) DEFAULT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_exp_date` (`expense_date`),
            KEY `idx_exp_category` (`category`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

        $feeStructuresTable = "CREATE TABLE IF NOT EXISTS `fee_structures` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `fee_name` VARCHAR(200) NOT NULL,
            `fee_type` VARCHAR(50) DEFAULT NULL,
            `amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            `program_id` INT(11) DEFAULT NULL,
            `academic_year` VARCHAR(20) DEFAULT NULL,
            `is_active` TINYINT(1) DEFAULT 1,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_fs_program` (`program_id`),
            KEY `idx_fs_active` (`is_active`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

        $feeTrackingTable = "CREATE TABLE IF NOT EXISTS `student_fee_tracking` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `student_id` INT(11) NOT NULL,
            `fee_type` VARCHAR(100) NOT NULL,
            `amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            `amount_paid` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            `balance` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            `academic_year` VARCHAR(20) DEFAULT NULL,
            `semester` VARCHAR(20) DEFAULT NULL,
            `status` VARCHAR(20) DEFAULT 'pending',
            `due_date` DATE DEFAULT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_sft_student` (`student_id`),
            KEY `idx_sft_status` (`status`),
            KEY `idx_sft_year` (`academic_year`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

        $sqls = [
            'payments'               => $paymentsTable,
            'expenses'               => $expensesTable,
            'fee_structures'         => $feeStructuresTable,
            'student_fee_tracking'   => $feeTrackingTable,
        ];

        foreach (['students' => $this->sDb(), 'staffs' => $this->sTf()] as $label => $conn) {
            if (!$conn) {
                $this->log("Cannot create tables: {$label} connection unavailable");
                continue;
            }
            foreach ($sqls as $table => $sql) {
                if (!$conn->query($sql)) {
                    $this->log("Table ensure failed", [
                        'db'    => $label,
                        'table' => $table,
                        'error' => $conn->error,
                    ]);
                }
            }
        }
    }

    /* ───────────────────────────────────────────────────────────────────
       Payment reference generator
       ─────────────────────────────────────────────────────────────────── */

    /**
     * Generate a unique payment reference: PAY-YYYYMMDD-XXXX
     *
     * Checks both databases for collisions and increments until unique.
     *
     * @return string
     */
    private function generatePaymentReference(): string
    {
        $prefix = 'PAY-' . date('Ymd') . '-';
        $maxAttempts = 100;

        for ($i = 1; $i <= $maxAttempts; $i++) {
            $candidate = $prefix . str_pad($i, 4, '0', STR_PAD_LEFT);
            if (!$this->referenceExistsInAnyDb($candidate)) {
                return $candidate;
            }
        }

        // Fallback: use microsecond timestamp
        return $prefix . substr(microtime(true) * 1000, -4);
    }

    /**
     * Check whether a payment reference already exists in either database.
     *
     * @param string $reference
     * @return bool
     */
    private function referenceExistsInAnyDb(string $reference): bool
    {
        foreach ([$this->sDb(), $this->sTf()] as $conn) {
            if (!$conn) {
                continue;
            }
            $stmt = $conn->prepare("SELECT 1 FROM `payments` WHERE `payment_reference` = ? LIMIT 1");
            if (!$stmt) {
                continue;
            }
            $stmt->bind_param('s', $reference);
            if (!$stmt->execute()) {
                error_log('FinanceSync: ref check execute failed: ' . ($stmt->error ?? 'unknown'));
            }
            $result = $stmt->get_result();
            $exists = ($result && $result->num_rows > 0);
            $stmt->close();
            if ($exists) {
                return true;
            }
        }
        return false;
    }

    /* ───────────────────────────────────────────────────────────────────
       Prepared-statement helpers
       ─────────────────────────────────────────────────────────────────── */

    /**
     * Execute a prepared INSERT and return the insert_id, or false on failure.
     *
     * @param mysqli|null $conn
     * @param string      $sql
     * @param array       $params
     * @param string      $types  bind_param type string
     * @return int|false  insert_id or false
     */
    private function execInsert(?mysqli $conn, string $sql, array $params, string $types)
    {
        if (!$conn) {
            return false;
        }
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $this->log('Prepare failed', ['error' => $conn->error, 'sql' => $sql]);
            return false;
        }
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        if (!$stmt->execute()) {
            error_log('FinanceSync: execute failed: ' . ($stmt->error ?? 'unknown'));
            $stmt->close();
            return false;
        }
        $id = $conn->insert_id;
        $stmt->close();
        return $id;
    }

    /**
     * Execute a prepared SELECT and return the result object or false.
     *
     * @param mysqli|null $conn
     * @param string      $sql
     * @param array       $params
     * @param string      $types
     * @return mysqli_result|bool
     */
    private function execSelect(?mysqli $conn, string $sql, array $params, string $types)
    {
        if (!$conn) {
            return false;
        }
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $this->log('Prepare failed', ['error' => $conn->error, 'sql' => $sql]);
            return false;
        }
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        if (!$stmt->execute()) {
            error_log('FinanceSync: select execute failed: ' . ($stmt->error ?? 'unknown'));
            $stmt->close();
            return false;
        }
        $result = $stmt->get_result();
        $stmt->close();
        return $result;
    }

    /**
     * Execute a prepared UPDATE/DELETE and return affected_rows or false.
     *
     * @param mysqli|null $conn
     * @param string      $sql
     * @param array       $params
     * @param string      $types
     * @return int|false  affected rows or false
     */
    private function execUpdate(?mysqli $conn, string $sql, array $params, string $types)
    {
        if (!$conn) {
            return false;
        }
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $this->log('Prepare failed', ['error' => $conn->error, 'sql' => $sql]);
            return false;
        }
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        if (!$stmt->execute()) {
            error_log('FinanceSync: update execute failed: ' . ($stmt->error ?? 'unknown'));
            $stmt->close();
            return false;
        }
        $affected = $conn->affected_rows;
        $stmt->close();
        return $affected;
    }

    /**
     * Fetch all rows from a mysqli_result as an array of associative arrays.
     *
     * @param mysqli_result|bool $result
     * @return array
     */
    private function fetchAll($result): array
    {
        if (!$result || !($result instanceof mysqli_result)) {
            return [];
        }
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * Fetch a single row from a mysqli_result.
     *
     * @param mysqli_result|bool $result
     * @return array|null
     */
    private function fetchOne($result): ?array
    {
        if (!$result || !($result instanceof mysqli_result)) {
            return null;
        }
        return $result->fetch_assoc() ?: null;
    }

    /* ───────────────────────────────────────────────────────────────────
       Logging
       ─────────────────────────────────────────────────────────────────── */

    /**
     * Log a finance-sync operation.
     *
     * @param string $message
     * @param array  $context
     */
    private function log(string $message, array $context = []): void
    {
        $entry = '[FinanceSync] ' . $message;
        if (!empty($context)) {
            $entry .= ' | ' . json_encode($context);
        }
        error_log($entry);
    }

    /* ═══════════════════════════════════════════════════════════════════
       PUBLIC API
       ═══════════════════════════════════════════════════════════════════ */

    /* ───────────────────────────────────────────────────────────────────
       recordPayment
       ─────────────────────────────────────────────────────────────────── */

    /**
     * Record a payment in BOTH databases (dual-write).
     *
     * Expected keys in $data:
     *   student_id        (int, required)
     *   amount_received   (float, required)
     *   payment_method    (string, default 'Cash')
     *   transaction_ref   (string, optional)
     *   slip_number       (string, optional)
     *   payment_date      (string YYYY-MM-DD, default today)
     *   status            (string, default 'completed')
     *   received_by       (int, optional – staff id)
     *   notes             (string, optional)
     *
     * @param array $data
     * @return array{success: bool, payment_id_students: int|false, payment_id_staffs: int|false, payment_reference: string, errors: array}
     */
    public function recordPayment(array $data): array
    {
        $this->ensureTables();

        // ── Validate required fields ──
        if (empty($data['student_id']) || !isset($data['amount_received'])) {
            return [
                'success'               => false,
                'payment_id_students'   => false,
                'payment_id_staffs'     => false,
                'payment_reference'     => '',
                'errors'                => ['validation' => 'student_id and amount_received are required'],
            ];
        }

        $studentId      = (int) $data['student_id'];
        $amount         = (float) $data['amount_received'];
        $method         = $data['payment_method']    ?? 'Cash';
        $txnRef          = $data['transaction_ref']   ?? null;
        $slip           = $data['slip_number']       ?? null;
        $payDate        = $data['payment_date']      ?? date('Y-m-d');
        $status         = $data['status']            ?? 'completed';
        $receivedBy     = !empty($data['received_by']) ? (int) $data['received_by'] : null;
        $notes          = $data['notes']             ?? null;

        $reference = $this->generatePaymentReference();

        $sql = "INSERT INTO `payments`
                    (`payment_reference`, `student_id`, `amount_received`, `payment_method`,
                     `transaction_ref`, `slip_number`, `payment_date`, `status`,
                     `received_by`, `notes`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $types = 'sidssssiss';
        $params = [
            $reference,
            $studentId,
            $amount,
            $method,
            $txnRef,
            $slip,
            $payDate,
            $status,
            $receivedBy,
            $notes,
        ];

        $idStudents = $this->execInsert($this->sDb(), $sql, $params, $types);
        $idStaffs   = $this->execInsert($this->sTf(), $sql, $params, $types);

        $success = ($idStudents !== false || $idStaffs !== false);

        $this->log('recordPayment', [
            'reference'   => $reference,
            'student_id'  => $studentId,
            'amount'      => $amount,
            'students_id' => $idStudents,
            'staffs_id'   => $idStaffs,
        ]);

        return [
            'success'             => $success,
            'payment_id_students' => $idStudents,
            'payment_id_staffs'   => $idStaffs,
            'payment_reference'   => $reference,
            'errors'              => $success ? [] : ['db' => 'Failed to insert into both databases'],
        ];
    }

    /* ───────────────────────────────────────────────────────────────────
       getStudentPayments
       ─────────────────────────────────────────────────────────────────── */

    /**
     * Get all payments for a specific student across both databases.
     * Results are merged and deduplicated by payment_reference.
     *
     * @param int $studentId
     * @return array  merged payment rows ordered by payment_date DESC
     */
    public function getStudentPayments(int $studentId): array
    {
        $this->ensureTables();

        $sql = "SELECT * FROM `payments` WHERE `student_id` = ? ORDER BY `payment_date` DESC, `id` DESC";
        $types = 'i';
        $params = [$studentId];

        $rowsStudents = $this->fetchAll($this->execSelect($this->sDb(), $sql, $params, $types));
        $rowsStaffs   = $this->fetchAll($this->execSelect($this->sTf(), $sql, $params, $types));

        // Deduplicate by payment_reference (students DB takes priority)
        $merged = [];
        foreach (array_merge($rowsStudents, $rowsStaffs) as $row) {
            $ref = $row['payment_reference'] ?? ('_fallback_' . ($row['id'] ?? uniqid()));
            if (!isset($merged[$ref])) {
                $merged[$ref] = $row;
            }
        }

        usort($merged, function ($a, $b) {
            $dateCmp = strcmp($b['payment_date'] ?? '', $a['payment_date'] ?? '');
            return $dateCmp !== 0 ? $dateCmp : (int)($b['id'] ?? 0) - (int)($a['id'] ?? 0);
        });

        $this->log('getStudentPayments', [
            'student_id' => $studentId,
            'count'      => count($merged),
        ]);

        return array_values($merged);
    }

    /* ───────────────────────────────────────────────────────────────────
       getPaymentSummary
       ─────────────────────────────────────────────────────────────────── */

    /**
     * Get overall financial summary across both databases.
     *
     * @return array{
     *     total_collected: float,
     *     total_pending: float,
     *     total_expenses: float,
     *     net_revenue: float,
     *     total_payments_count: int,
     *     total_expenses_count: int,
     *     collected_by_method: array
     * }
     */
    public function getPaymentSummary(): array
    {
        $this->ensureTables();

        $totalCollected = 0.0;
        $totalPending   = 0.0;
        $totalPayments  = 0;
        $byMethod       = [];

        // ── Payments ──
        $sqlCompleted = "SELECT COALESCE(SUM(`amount_received`),0) AS total, COUNT(*) AS cnt FROM `payments` WHERE `status` = 'completed'";
        $sqlPending   = "SELECT COALESCE(SUM(`amount_received`),0) AS total, COUNT(*) AS cnt FROM `payments` WHERE `status` = 'Pending'";
        $sqlByMethod  = "SELECT `payment_method`, COALESCE(SUM(`amount_received`),0) AS total, COUNT(*) AS cnt FROM `payments` WHERE `status` = 'completed' GROUP BY `payment_method`";

        foreach ([$this->sDb(), $this->sTf()] as $conn) {
            if (!$conn) {
                continue;
            }

            $row = $this->fetchOne($this->execSelect($conn, $sqlCompleted, [], ''));
            if ($row) {
                $totalCollected += (float) $row['total'];
                $totalPayments  += (int) $row['cnt'];
            }

            $row = $this->fetchOne($this->execSelect($conn, $sqlPending, [], ''));
            if ($row) {
                $totalPending += (float) $row['total'];
            }

            $methodRows = $this->fetchAll($this->execSelect($conn, $sqlByMethod, [], ''));
            foreach ($methodRows as $m) {
                $key = $m['payment_method'] ?? 'Unknown';
                if (!isset($byMethod[$key])) {
                    $byMethod[$key] = ['total' => 0.0, 'count' => 0];
                }
                $byMethod[$key]['total'] += (float) $m['total'];
                $byMethod[$key]['count'] += (int) $m['cnt'];
            }
        }

        // ── Expenses ──
        $totalExpenses   = 0.0;
        $totalExpCount   = 0;
        $sqlExpenses     = "SELECT COALESCE(SUM(`amount`),0) AS total, COUNT(*) AS cnt FROM `expenses` WHERE `status` IN ('approved','paid')";

        foreach ([$this->sDb(), $this->sTf()] as $conn) {
            if (!$conn) {
                continue;
            }
            $row = $this->fetchOne($this->execSelect($conn, $sqlExpenses, [], ''));
            if ($row) {
                $totalExpenses += (float) $row['total'];
                $totalExpCount += (int) $row['cnt'];
            }
        }

        $this->log('getPaymentSummary', [
            'collected' => $totalCollected,
            'pending'   => $totalPending,
            'expenses'  => $totalExpenses,
        ]);

        return [
            'total_collected'         => $totalCollected,
            'total_pending'           => $totalPending,
            'total_expenses'          => $totalExpenses,
            'net_revenue'             => $totalCollected - $totalExpenses,
            'total_payments_count'    => $totalPayments,
            'total_expenses_count'    => $totalExpCount,
            'collected_by_method'     => $byMethod,
        ];
    }

    /* ───────────────────────────────────────────────────────────────────
       getFeeStatus
       ─────────────────────────────────────────────────────────────────── */

    /**
     * Get the fee status for a student.
     *
     * Checks (in order):
     *   1. student_fee_tracking table
     *   2. student_fees table (legacy)
     *   3. Payments table (aggregated)
     *
     * @param int    $studentId
     * @param string $academicYear  optional – filter by year
     * @param string $semester      optional – filter by semester
     * @return array{
     *     student_id: int,
     *     total_fees: float,
     *     amount_paid: float,
     *     balance: float,
     *     fee_items: array,
     *     status: string,
     *     source: string
     * }
     */
    public function getFeeStatus(int $studentId, string $academicYear = '', string $semester = ''): array
    {
        $this->ensureTables();

        $result = [
            'student_id' => $studentId,
            'total_fees' => 0.0,
            'amount_paid' => 0.0,
            'balance'    => 0.0,
            'fee_items'  => [],
            'status'     => 'unknown',
            'source'     => 'none',
        ];

        // ── 1. student_fee_tracking ──
        $sql = "SELECT * FROM `student_fee_tracking` WHERE `student_id` = ?";
        $types = 'i';
        $params = [$studentId];

        if (!empty($academicYear)) {
            $sql .= " AND `academic_year` = ?";
            $types .= 's';
            $params[] = $academicYear;
        }
        if (!empty($semester)) {
            $sql .= " AND `semester` = ?";
            $types .= 's';
            $params[] = $semester;
        }
        $sql .= " ORDER BY `id` ASC";

        $rows = $this->fetchAll($this->execSelect($this->sDb(), $sql, $params, $types));
        if (empty($rows)) {
            $rows = $this->fetchAll($this->execSelect($this->sTf(), $sql, $params, $types));
        }

        if (!empty($rows)) {
            foreach ($rows as $r) {
                $result['total_fees']  += (float) ($r['amount'] ?? 0);
                $result['amount_paid'] += (float) ($r['amount_paid'] ?? 0);
                $result['fee_items'][] = $r;
            }
            $result['balance'] = $result['total_fees'] - $result['amount_paid'];
            $result['status']  = $result['balance'] <= 0 ? 'paid' : ($result['amount_paid'] > 0 ? 'partial' : 'unpaid');
            $result['source']  = 'student_fee_tracking';
            return $result;
        }

        // ── 2. student_fees (legacy) ──
        $sql = "SELECT * FROM `student_fees` WHERE `student_id` = ?";
        $types = 'i';
        $params = [$studentId];

        foreach ([$this->sDb(), $this->sTf()] as $conn) {
            if (!$conn) {
                continue;
            }
            $rows2 = $this->fetchAll($this->execSelect($conn, $sql, $params, $types));
            if (!empty($rows2)) {
                foreach ($rows2 as $r) {
                    $result['total_fees'] += (float) ($r['amount'] ?? 0);
                    // If student_fees has a paid portion, use it
                    if (isset($r['paid_amount'])) {
                        $result['amount_paid'] += (float) $r['paid_amount'];
                    }
                    $result['fee_items'][] = $r;
                }
                $result['source'] = 'student_fees';
                break;
            }
        }

        // ── 3. Aggregate from payments if still no fee structure ──
        if (empty($result['fee_items'])) {
            $sqlPay = "SELECT COALESCE(SUM(`amount_received`),0) AS total_paid
                       FROM `payments`
                       WHERE `student_id` = ? AND `status` = 'completed'";
            $typesPay = 'i';
            $paramsPay = [$studentId];

            if (!empty($academicYear)) {
                // Filter by payment_date within academic year range
                $startYear = substr($academicYear, 0, 4) . '-01-01';
                $endYear   = substr($academicYear, 0, 4) . '-12-31';
                $sqlPay   .= " AND `payment_date` BETWEEN ? AND ?";
                $typesPay .= 'ss';
                $paramsPay[] = $startYear;
                $paramsPay[] = $endYear;
            }

            foreach ([$this->sDb(), $this->sTf()] as $conn) {
                if (!$conn) {
                    continue;
                }
                $payRow = $this->fetchOne($this->execSelect($conn, $sqlPay, $paramsPay, $typesPay));
                if ($payRow && (float) $payRow['total_paid'] > 0) {
                    $result['amount_paid'] = (float) $payRow['total_paid'];
                    $result['source'] = 'payments';
                    break;
                }
            }

            $result['status'] = $result['amount_paid'] > 0 ? 'partial' : 'no_record';
        } else {
            $result['balance'] = $result['total_fees'] - $result['amount_paid'];
            $result['status']  = $result['balance'] <= 0 ? 'paid' : ($result['amount_paid'] > 0 ? 'partial' : 'unpaid');
        }

        $this->log('getFeeStatus', [
            'student_id' => $studentId,
            'total'      => $result['total_fees'],
            'paid'       => $result['amount_paid'],
            'status'     => $result['status'],
        ]);

        return $result;
    }

    /* ───────────────────────────────────────────────────────────────────
       getDashboardStats
       ─────────────────────────────────────────────────────────────────── */

    /**
     * Get aggregated statistics suitable for any finance-related dashboard.
     *
     * @return array{
     *     total_revenue: float,
     *     monthly_revenue: float,
     *     pending_fees: float,
     *     total_expenses: float,
     *     net_income: float,
     *     total_students_with_fees: int,
     *     payments_today: int,
     *     payments_this_month: int,
     *     expenses_this_month: float,
     *     collection_rate: float
     * }
     */
    public function getDashboardStats(): array
    {
        $this->ensureTables();

        $stats = [
            'total_revenue'              => 0.0,
            'monthly_revenue'            => 0.0,
            'pending_fees'               => 0.0,
            'total_expenses'             => 0.0,
            'net_income'                 => 0.0,
            'total_students_with_fees'   => 0,
            'payments_today'             => 0,
            'payments_this_month'        => 0,
            'expenses_this_month'        => 0.0,
            'collection_rate'            => 0.0,
        ];

        $today       = date('Y-m-d');
        $monthStart  = date('Y-m-01');
        $monthEnd    = date('Y-m-t');

        $queries = [
            // Total revenue (completed payments)
            'total_rev' => [
                'sql'    => "SELECT COALESCE(SUM(`amount_received`),0) AS v FROM `payments` WHERE `status` = 'completed'",
                'types'  => '',
                'params' => [],
            ],
            // Monthly revenue
            'month_rev' => [
                'sql'    => "SELECT COALESCE(SUM(`amount_received`),0) AS v FROM `payments` WHERE `status` = 'completed' AND `payment_date` BETWEEN ? AND ?",
                'types'  => 'ss',
                'params' => [$monthStart, $monthEnd],
            ],
            // Pending payments
            'pending' => [
                'sql'    => "SELECT COALESCE(SUM(`amount_received`),0) AS v FROM `payments` WHERE `status` = 'Pending'",
                'types'  => '',
                'params' => [],
            ],
            // Total expenses
            'expenses' => [
                'sql'    => "SELECT COALESCE(SUM(`amount`),0) AS v FROM `expenses` WHERE `status` IN ('approved','paid')",
                'types'  => '',
                'params' => [],
            ],
            // Expenses this month
            'expenses_month' => [
                'sql'    => "SELECT COALESCE(SUM(`amount`),0) AS v FROM `expenses` WHERE `status` IN ('approved','paid') AND `expense_date` BETWEEN ? AND ?",
                'types'  => 'ss',
                'params' => [$monthStart, $monthEnd],
            ],
            // Payments today
            'pay_today' => [
                'sql'    => "SELECT COUNT(*) AS v FROM `payments` WHERE `payment_date` = ?",
                'types'  => 's',
                'params' => [$today],
            ],
            // Payments this month
            'pay_month' => [
                'sql'    => "SELECT COUNT(*) AS v FROM `payments` WHERE `payment_date` BETWEEN ? AND ?",
                'types'  => 'ss',
                'params' => [$monthStart, $monthEnd],
            ],
            // Distinct students who paid
            'students_paid' => [
                'sql'    => "SELECT COUNT(DISTINCT `student_id`) AS v FROM `payments` WHERE `status` = 'completed'",
                'types'  => '',
                'params' => [],
            ],
        ];

        foreach ([$this->sDb(), $this->sTf()] as $conn) {
            if (!$conn) {
                continue;
            }
            foreach ($queries as $key => $q) {
                $row = $this->fetchOne($this->execSelect($conn, $q['sql'], $q['params'], $q['types']));
                if ($row) {
                    $val = (float) $row['v'];
                    switch ($key) {
                        case 'total_rev':
                            $stats['total_revenue'] += $val;
                            break;
                        case 'month_rev':
                            $stats['monthly_revenue'] += $val;
                            break;
                        case 'pending':
                            $stats['pending_fees'] += $val;
                            break;
                        case 'expenses':
                            $stats['total_expenses'] += $val;
                            break;
                        case 'expenses_month':
                            $stats['expenses_this_month'] += $val;
                            break;
                        case 'pay_today':
                            $stats['payments_today'] += (int) $val;
                            break;
                        case 'pay_month':
                            $stats['payments_this_month'] += (int) $val;
                            break;
                        case 'students_paid':
                            $stats['total_students_with_fees'] += (int) $val;
                            break;
                    }
                }
            }
        }

        // Derived metrics
        $stats['net_income'] = $stats['total_revenue'] - $stats['total_expenses'];

        // Collection rate: total collected / (total collected + pending)
        $denominator = $stats['total_revenue'] + $stats['pending_fees'];
        $stats['collection_rate'] = $denominator > 0
            ? round(($stats['total_revenue'] / $denominator) * 100, 2)
            : 0.0;

        $this->log('getDashboardStats', [
            'revenue'   => $stats['total_revenue'],
            'expenses'  => $stats['total_expenses'],
            'pending'   => $stats['pending_fees'],
        ]);

        return $stats;
    }

    /* ───────────────────────────────────────────────────────────────────
       syncPaymentToAll
       ─────────────────────────────────────────────────────────────────── */

    /**
     * Ensure a payment record exists in all relevant databases.
     * If the payment exists in staffs but not students, copy it, and vice versa.
     *
     * @param int    $paymentId     The id in the source database
     * @param string $sourceDb      'students' or 'staffs'
     * @return array{success: bool, synced_to: array, errors: array}
     */
    public function syncPaymentToAll(int $paymentId, string $sourceDb = 'students'): array
    {
        $this->ensureTables();

        $result = [
            'success'   => false,
            'synced_to' => [],
            'errors'    => [],
        ];

        $sourceConn = ($sourceDb === 'students') ? $this->sDb() : $this->sTf();
        if (!$sourceConn) {
            $result['errors'][] = "Source database '{$sourceDb}' connection unavailable";
            return $result;
        }

        // Fetch from source
        $sql = "SELECT * FROM `payments` WHERE `id` = ? LIMIT 1";
        $row = $this->fetchOne($this->execSelect($sourceConn, $sql, [$paymentId], 'i'));
        if (!$row) {
            $result['errors'][] = "Payment #{$paymentId} not found in {$sourceDb}";
            return $result;
        }

        $targetDb   = ($sourceDb === 'students') ? 'staffs' : 'students';
        $targetConn = ($sourceDb === 'students') ? $this->sTf() : $this->sDb();

        if (!$targetConn) {
            $result['errors'][] = "Target database '{$targetDb}' connection unavailable";
            return $result;
        }

        // Check if already exists in target
        $checkSql = "SELECT 1 FROM `payments` WHERE `payment_reference` = ? LIMIT 1";
        $existing = $this->fetchOne($this->execSelect($targetConn, $checkSql, [$row['payment_reference']], 's'));

        if ($existing) {
            $result['synced_to'][] = $targetDb;
            $result['success'] = true;
            $this->log('syncPaymentToAll – already exists', [
                'payment_id' => $paymentId,
                'target'     => $targetDb,
            ]);
            return $result;
        }

        // Insert into target
        $insSql = "INSERT INTO `payments`
                        (`payment_reference`, `student_id`, `amount_received`, `payment_method`,
                         `transaction_ref`, `slip_number`, `payment_date`, `status`,
                         `received_by`, `notes`)
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $insParams = [
            $row['payment_reference'],
            (int) $row['student_id'],
            (float) $row['amount_received'],
            $row['payment_method'],
            $row['transaction_ref'],
            $row['slip_number'],
            $row['payment_date'],
            $row['status'],
            $row['received_by'] ? (int) $row['received_by'] : null,
            $row['notes'],
        ];

        $insId = $this->execInsert($targetConn, $insSql, $insParams, 'sidssssiss');
        if ($insId !== false) {
            $result['synced_to'][] = $targetDb;
            $result['success'] = true;
        } else {
            $result['errors'][] = "Insert into {$targetDb} failed";
        }

        $this->log('syncPaymentToAll', [
            'payment_id' => $paymentId,
            'source'     => $sourceDb,
            'synced_to'  => $result['synced_to'],
        ]);

        return $result;
    }

    /* ───────────────────────────────────────────────────────────────────
       recordExpense
       ─────────────────────────────────────────────────────────────────── */

    /**
     * Record an expense in both databases (dual-write).
     *
     * Expected keys in $data:
     *   title         (string, required)
     *   amount        (float, required)
     *   category      (string, optional)
     *   description   (string, optional)
     *   expense_date  (string YYYY-MM-DD, default today)
     *   status        (string, default 'pending')
     *   created_by    (int, optional – staff id)
     *
     * @param array $data
     * @return array{success: bool, expense_id_students: int|false, expense_id_staffs: int|false, errors: array}
     */
    public function recordExpense(array $data): array
    {
        $this->ensureTables();

        if (empty($data['title']) || !isset($data['amount'])) {
            return [
                'success'             => false,
                'expense_id_students' => false,
                'expense_id_staffs'   => false,
                'errors'              => ['validation' => 'title and amount are required'],
            ];
        }

        $title       = $data['title'];
        $amount      = (float) $data['amount'];
        $category    = $data['category']    ?? null;
        $description = $data['description'] ?? null;
        $expDate     = $data['expense_date'] ?? date('Y-m-d');
        $status      = $data['status']      ?? 'pending';
        $createdBy   = !empty($data['created_by']) ? (int) $data['created_by'] : null;

        $sql = "INSERT INTO `expenses`
                    (`title`, `amount`, `category`, `description`,
                     `expense_date`, `status`, `created_by`)
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        $types  = 'sdssssi';
        $params = [$title, $amount, $category, $description, $expDate, $status, $createdBy];

        $idStudents = $this->execInsert($this->sDb(), $sql, $params, $types);
        $idStaffs   = $this->execInsert($this->sTf(), $sql, $params, $types);

        $success = ($idStudents !== false || $idStaffs !== false);

        $this->log('recordExpense', [
            'title'      => $title,
            'amount'     => $amount,
            'students_id' => $idStudents,
            'staffs_id'   => $idStaffs,
        ]);

        return [
            'success'             => $success,
            'expense_id_students' => $idStudents,
            'expense_id_staffs'   => $idStaffs,
            'errors'              => $success ? [] : ['db' => 'Failed to record expense in both databases'],
        ];
    }

    /* ───────────────────────────────────────────────────────────────────
       getRecentTransactions
       ─────────────────────────────────────────────────────────────────── */

    /**
     * Get recent payments and expenses combined, sorted by date descending.
     *
     * @param int $limit  Max number of each type (default 20)
     * @return array  Merged transactions with a 'type' key ('payment' or 'expense')
     */
    public function getRecentTransactions(int $limit = 20): array
    {
        $this->ensureTables();

        $limitSql = " LIMIT " . (int) $limit;

        $paySql    = "SELECT *, 'payment' AS _type FROM `payments` ORDER BY `created_at` DESC" . $limitSql;
        $expSql    = "SELECT *, 'expense' AS _type FROM `expenses` ORDER BY `created_at` DESC" . $limitSql;

        $payments = [];
        $expenses = [];

        foreach ([$this->sDb(), $this->sTf()] as $conn) {
            if (!$conn) {
                continue;
            }
            $pRows = $this->fetchAll($this->execSelect($conn, $paySql, [], ''));
            foreach ($pRows as $r) {
                $key = ($r['payment_reference'] ?? '') . '_pay';
                if (!isset($payments[$key])) {
                    $payments[$key] = $r;
                }
            }
            $eRows = $this->fetchAll($this->execSelect($conn, $expSql, [], ''));
            foreach ($eRows as $r) {
                $key = 'exp_' . ($r['id'] ?? uniqid()) . '_' . ($r['created_at'] ?? '');
                if (!isset($expenses[$key])) {
                    $expenses[$key] = $r;
                }
            }
        }

        $all = array_merge(
            array_values($payments),
            array_values($expenses)
        );

        // Sort by created_at descending
        usort($all, function ($a, $b) {
            return strcmp($b['created_at'] ?? '', $a['created_at'] ?? '');
        });

        // Limit total combined
        $all = array_slice($all, 0, $limit * 2);

        $this->log('getRecentTransactions', ['count' => count($all)]);

        return $all;
    }

    /* ───────────────────────────────────────────────────────────────────
       getMonthlyRevenue
       ─────────────────────────────────────────────────────────────────── */

    /**
     * Get revenue data for the last N months (for charts/graphs).
     *
     * @param int $months  Number of months to look back (default 12)
     * @return array  Each entry: [month => 'YYYY-MM', revenue => float, expenses => float, count => int]
     */
    public function getMonthlyRevenue(int $months = 12): array
    {
        $this->ensureTables();

        $results = [];

        // Build month buckets
        for ($i = $months - 1; $i >= 0; $i--) {
            $date       = new DateTime("-{$i} months");
            $yearMonth  = $date->format('Y-m');
            $start      = $date->format('Y-m-01');
            $end        = $date->format('Y-m-t');

            $results[$yearMonth] = [
                'month'    => $yearMonth,
                'revenue'  => 0.0,
                'expenses' => 0.0,
                'count'    => 0,
            ];
        }

        // ── Payments per month ──
        $paySql = "SELECT DATE_FORMAT(`payment_date`, '%Y-%m') AS ym,
                          COALESCE(SUM(`amount_received`),0) AS total,
                          COUNT(*) AS cnt
                   FROM `payments`
                   WHERE `status` = 'completed'
                     AND `payment_date` >= ?
                   GROUP BY ym";

        $startDate = date('Y-m-01', strtotime("-" . ($months - 1) . " months"));

        foreach ([$this->sDb(), $this->sTf()] as $conn) {
            if (!$conn) {
                continue;
            }
            $rows = $this->fetchAll($this->execSelect($conn, $paySql, [$startDate], 's'));
            foreach ($rows as $r) {
                $ym = $r['ym'] ?? '';
                if (isset($results[$ym])) {
                    $results[$ym]['revenue'] += (float) $r['total'];
                    $results[$ym]['count']   += (int) $r['cnt'];
                }
            }
        }

        // ── Expenses per month ──
        $expSql = "SELECT DATE_FORMAT(`expense_date`, '%Y-%m') AS ym,
                          COALESCE(SUM(`amount`),0) AS total
                   FROM `expenses`
                   WHERE `status` IN ('approved','paid')
                     AND `expense_date` >= ?
                   GROUP BY ym";

        foreach ([$this->sDb(), $this->sTf()] as $conn) {
            if (!$conn) {
                continue;
            }
            $rows = $this->fetchAll($this->execSelect($conn, $expSql, [$startDate], 's'));
            foreach ($rows as $r) {
                $ym = $r['ym'] ?? '';
                if (isset($results[$ym])) {
                    $results[$ym]['expenses'] += (float) $r['total'];
                }
            }
        }

        // Re-index as sequential array
        $output = array_values($results);

        $this->log('getMonthlyRevenue', ['months' => $months, 'data_points' => count($output)]);

        return $output;
    }

    /* ═══════════════════════════════════════════════════════════════════
       UTILITY / STATUS
       ═══════════════════════════════════════════════════════════════════ */

    /**
     * Return the connection status of both databases.
     *
     * @return array{students: string, staffs: string}
     */
    public function getConnectionStatus(): array
    {
        return [
            'students' => ($this->sDb() && $this->sDb()->ping()) ? 'connected' : 'disconnected',
            'staffs'   => ($this->sTf() && $this->sTf()->ping()) ? 'connected' : 'disconnected',
        ];
    }

    /**
     * Force re-creation of tables (useful after schema changes).
     */
    public function recreateTables(): void
    {
        $this->tablesEnsured = false;
        $this->ensureTables();
    }
}
