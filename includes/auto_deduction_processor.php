<?php
/**
 * Auto-Deduction / Subscription Payment Processor
 * Handles creation, management, and processing of recurring payment subscriptions.
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/financial_functions.php';

if (!function_exists('processAutoDeductions')) {
    function processAutoDeductions($limit = 50) {
        $results = ['processed' => 0, 'success' => 0, 'failed' => 0, 'skipped' => 0, 'errors' => []];
        $conn = getStudentsConnection();
        if (!$conn) {
            $results['errors'][] = 'No students DB connection';
            return $results;
        }

        $subscriptions = $conn->query("
            SELECT ps.*, s.full_name, s.phone, s.program, s.student_number
            FROM payment_subscriptions ps
            LEFT JOIN students s ON CAST(s.id AS CHAR) = ps.student_id
            WHERE ps.status = 'active'
              AND ps.next_due_date <= CURDATE()
              AND (ps.end_date IS NULL OR ps.end_date >= CURDATE())
            ORDER BY ps.next_due_date ASC
            LIMIT " . (int)$limit
        );

        if (!$subscriptions || $subscriptions->num_rows === 0) {
            $results['errors'][] = 'No active subscriptions due for processing';
            return $results;
        }

        while ($sub = $subscriptions->fetch_assoc()) {
            $results['processed']++;
            $subId = (int)$sub['id'];
            $studentId = $conn->real_escape_string($sub['student_id']);
            $installNo = (int)$sub['installments_collected'] + 1;
            $amount = (float)$sub['installment_amount'];
            $dueDate = $sub['next_due_date'];

            $existing = $conn->query("SELECT id FROM subscription_deductions WHERE subscription_id = $subId AND installment_number = $installNo AND status != 'skipped'");
            if ($existing && $existing->num_rows > 0) {
                $results['skipped']++;
                $conn->query("UPDATE payment_subscriptions SET next_due_date = DATE_ADD(next_due_date, INTERVAL 1 MONTH) WHERE id = $subId");
                continue;
            }

            $ref = 'AUTO-' . date('Ymd') . '-' . str_pad($subId, 4, '0', STR_PAD_LEFT) . '-' . str_pad($installNo, 3, '0', STR_PAD_LEFT);
            $conn->begin_transaction();
            try {
                $payStmt = $conn->prepare("
                    INSERT INTO payments (student_id, payment_reference, amount_received, payment_method, payment_date, status, notes)
                    VALUES (?, ?, ?, ?, NOW(), 'Pending', ?)
                ");
                $notes = 'Auto-deduction: Subscription #' . $subId . ' Installment ' . $installNo . '/' . $sub['total_installments'];
                $method = $sub['payment_method'] ?? 'mobile_money';
                $payStmt->bind_param('ssdss', $studentId, $ref, $amount, $method, $notes);
                $payStmt->execute();
                $paymentId = $payStmt->insert_id;
                $payStmt->close();

                $dedStmt = $conn->prepare("
                    INSERT INTO subscription_deductions (subscription_id, student_id, installment_number, amount, due_date, processed_date, status, payment_reference, payment_id, attempt_count, last_attempt_date)
                    VALUES (?, ?, ?, ?, ?, NOW(), 'success', ?, ?, 1, NOW())
                ");
                $dedStmt->bind_param('isidssi', $subId, $studentId, $installNo, $amount, $dueDate, $ref, $paymentId);
                $dedStmt->execute();
                $dedStmt->close();

                $newCollected = $installNo;
                $completed = ($newCollected >= (int)$sub['total_installments']);
                if ($completed) {
                    $updateSql = "UPDATE payment_subscriptions SET installments_collected = $newCollected, status = 'completed', next_due_date = NULL, end_date = CURDATE() WHERE id = $subId";
                } else {
                    $updateSql = "UPDATE payment_subscriptions SET installments_collected = $newCollected, next_due_date = DATE_ADD(next_due_date, INTERVAL 1 MONTH) WHERE id = $subId";
                }
                $conn->query($updateSql);

                $conn->commit();
                $results['success']++;
            } catch (Exception $e) {
                $conn->rollback();
                $results['failed']++;
                $results['errors'][] = 'Subscription #' . $subId . ': ' . $e->getMessage();
                try {
                    $failStmt = $conn->prepare("
                        INSERT INTO subscription_deductions (subscription_id, student_id, installment_number, amount, due_date, status, failure_reason, attempt_count, last_attempt_date)
                        VALUES (?, ?, ?, ?, ?, 'failed', ?, 1, NOW())
                    ");
                    $reason = $e->getMessage();
                    $failStmt->bind_param('isidss', $subId, $studentId, $installNo, $amount, $dueDate, $reason);
                    $failStmt->execute();
                    $failStmt->close();
                } catch (Exception $e2) {}
            }
        }

        return $results;
    }
}

if (!function_exists('createSubscription')) {
    function createSubscription($data) {
        $conn = getStudentsConnection();
        if (!$conn) return ['success' => false, 'error' => 'No database connection'];

        $studentId = $conn->real_escape_string($data['student_id']);
        $type = $conn->real_escape_string($data['subscription_type'] ?? 'fee_installment');
        $totalAmount = (float)($data['total_amount'] ?? 0);
        $installments = (int)($data['total_installments'] ?? 1);
        $installmentAmount = $totalAmount / max($installments, 1);
        $frequency = $conn->real_escape_string($data['frequency'] ?? 'monthly');
        $method = $conn->real_escape_string($data['payment_method'] ?? 'mobile_money');
        $provider = $conn->real_escape_string($data['payment_provider'] ?? '');
        $phone = $conn->real_escape_string($data['phone_number'] ?? '');
        $refType = $conn->real_escape_string($data['reference_type'] ?? '');
        $refId = (int)($data['reference_id'] ?? 0);
        $notes = $conn->real_escape_string($data['notes'] ?? '');

        $interval = $frequency === 'weekly' ? 'INTERVAL 1 WEEK' : ($frequency === 'quarterly' ? 'INTERVAL 3 MONTH' : 'INTERVAL 1 MONTH');
        $q = $conn->query("SELECT DATE_ADD(CURDATE(), $interval) AS nd"); $nextDue = ($q && ($r=$q->fetch_assoc())) ? $r['nd'] : null;

        $stmt = $conn->prepare("
            INSERT INTO payment_subscriptions (student_id, subscription_type, reference_type, reference_id, total_amount, installment_amount, frequency, total_installments, installments_collected, start_date, next_due_date, payment_method, payment_provider, phone_number, status, notes, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, CURDATE(), ?, ?, ?, ?, 'active', ?, ?)
        ");
        $stmt->bind_param('ssiiddsssssss', $studentId, $type, $refType, $refId, $totalAmount, $installmentAmount, $frequency, $installments, $nextDue, $method, $provider, $phone, $notes, $studentId);
        if ($stmt->execute()) {
            $id = $stmt->insert_id;
            $stmt->close();
            return ['success' => true, 'subscription_id' => $id, 'installment_amount' => $installmentAmount, 'next_due' => $nextDue, 'total_installments' => $installments];
        }
        $stmt->close();
        return ['success' => false, 'error' => 'Failed to create subscription: ' . $conn->error];
    }
}

if (!function_exists('getStudentSubscriptions')) {
    function getStudentSubscriptions($studentId) {
        $conn = getStudentsConnection();
        if (!$conn) return [];
        $sid = $conn->real_escape_string($studentId);
        $r = $conn->query("
            SELECT ps.*,
                (SELECT COUNT(*) FROM subscription_deductions sd WHERE sd.subscription_id = ps.id AND sd.status = 'success') AS successful_deductions,
                (SELECT SUM(sd.amount) FROM subscription_deductions sd WHERE sd.subscription_id = ps.id AND sd.status = 'success') AS total_deducted
            FROM payment_subscriptions ps
            WHERE ps.student_id = '$sid'
            ORDER BY ps.created_at DESC
        ");
        return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
    }
}

if (!function_exists('cancelSubscription')) {
    function cancelSubscription($subscriptionId, $studentId = null) {
        $conn = getStudentsConnection();
        if (!$conn) return false;
        $id = (int)$subscriptionId;
        $where = $studentId ? "AND student_id = '" . $conn->real_escape_string($studentId) . "'" : '';
        $conn->query("UPDATE payment_subscriptions SET status = 'cancelled' WHERE id = $id $where");
        return $conn->affected_rows > 0;
    }
}

if (!function_exists('pauseSubscription')) {
    function pauseSubscription($subscriptionId, $studentId = null) {
        $conn = getStudentsConnection();
        if (!$conn) return false;
        $id = (int)$subscriptionId;
        $where = $studentId ? "AND student_id = '" . $conn->real_escape_string($studentId) . "'" : '';
        $conn->query("UPDATE payment_subscriptions SET status = 'paused' WHERE id = $id $where");
        return $conn->affected_rows > 0;
    }
}

if (!function_exists('resumeSubscription')) {
    function resumeSubscription($subscriptionId, $studentId = null) {
        $conn = getStudentsConnection();
        if (!$conn) return false;
        $id = (int)$subscriptionId;
        $where = $studentId ? "AND student_id = '" . $conn->real_escape_string($studentId) . "'" : '';
        $q = $conn->query("SELECT frequency FROM payment_subscriptions WHERE id = $id");
        $interval = $q ? $q->fetch_assoc() : null;
        $freq = $interval['frequency'] ?? 'monthly';
        $sqlFreq = $freq === 'weekly' ? 'INTERVAL 1 WEEK' : ($freq === 'quarterly' ? 'INTERVAL 3 MONTH' : 'INTERVAL 1 MONTH');
        $q = $conn->query("SELECT DATE_ADD(CURDATE(), $sqlFreq) AS nd"); $nextDue = ($q && ($r=$q->fetch_assoc())) ? $r['nd'] : null;
        $conn->query("UPDATE payment_subscriptions SET status = 'active', next_due_date = '$nextDue' WHERE id = $id $where");
        return $conn->affected_rows > 0;
    }
}

if (!function_exists('getAllSubscriptions')) {
    function getAllSubscriptions($status = null, $limit = 100) {
        $conn = getStudentsConnection();
        if (!$conn) return [];
        $where = $status ? "WHERE ps.status = '" . $conn->real_escape_string($status) . "'" : '';
        $r = $conn->query("
            SELECT ps.*, s.full_name, s.program, s.phone AS student_phone, s.student_number,
                (SELECT SUM(sd.amount) FROM subscription_deductions sd WHERE sd.subscription_id = ps.id AND sd.status = 'success') AS total_collected,
                (SELECT COUNT(*) FROM subscription_deductions sd WHERE sd.subscription_id = ps.id) AS total_attempts
            FROM payment_subscriptions ps
            LEFT JOIN students s ON CAST(s.id AS CHAR) = ps.student_id
            $where
            ORDER BY ps.created_at DESC
            LIMIT " . (int)$limit
        );
        return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
    }
}

if (!function_exists('getSubscriptionStats')) {
    function getSubscriptionStats() {
        $conn = getStudentsConnection();
        if (!$conn) return [];
        $stats = [];
        $r = $conn->query("SELECT status, COUNT(*) AS cnt FROM payment_subscriptions GROUP BY status");
        if ($r) while ($row = $r->fetch_assoc()) $stats[$row['status']] = (int)$row['cnt'];
        $r = $conn->query("SELECT COALESCE(SUM(installment_amount),0) AS monthly_projected FROM payment_subscriptions WHERE status='active'");
        if ($r) $stats['monthly_projected'] = (float)$r->fetch_assoc()['monthly_projected'];
        $r = $conn->query("SELECT COALESCE(SUM(amount),0) AS total_collected FROM subscription_deductions WHERE status='success'");
        if ($r) $stats['total_collected'] = (float)$r->fetch_assoc()['total_collected'];
        return $stats;
    }
}
