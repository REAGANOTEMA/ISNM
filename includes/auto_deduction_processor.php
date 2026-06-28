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
            $studentId = $sub['student_id'];
            $installNo = (int)$sub['installments_collected'] + 1;
            $amount = (float)$sub['installment_amount'];
            $dueDate = $sub['next_due_date'];

            $existing = $conn->prepare("SELECT id FROM subscription_deductions WHERE subscription_id = ? AND installment_number = ? AND status != 'skipped'");
            $existing->bind_param('ii', $subId, $installNo);
            $existing->execute();
            $existingResult = $existing->get_result();
            if ($existingResult && $existingResult->num_rows > 0) {
                $results['skipped']++;
                $updStmt = $conn->prepare("UPDATE payment_subscriptions SET next_due_date = DATE_ADD(next_due_date, INTERVAL 1 MONTH) WHERE id = ?");
                $updStmt->bind_param('i', $subId);
                $updStmt->execute();
                $updStmt->close();
                $existing->close();
                continue;
            }
            $existing->close();

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
                    $updateStmt = $conn->prepare("UPDATE payment_subscriptions SET installments_collected = ?, status = 'completed', next_due_date = NULL, end_date = CURDATE() WHERE id = ?");
                    $updateStmt->bind_param('ii', $newCollected, $subId);
                } else {
                    $updateStmt = $conn->prepare("UPDATE payment_subscriptions SET installments_collected = ?, next_due_date = DATE_ADD(next_due_date, INTERVAL 1 MONTH) WHERE id = ?");
                    $updateStmt->bind_param('ii', $newCollected, $subId);
                }
                $updateStmt->execute();
                $updateStmt->close();

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

        $studentId = $data['student_id'];
        $type = $data['subscription_type'] ?? 'fee_installment';
        $totalAmount = (float)($data['total_amount'] ?? 0);
        $installments = (int)($data['total_installments'] ?? 1);
        $installmentAmount = $totalAmount / max($installments, 1);
        $frequency = $data['frequency'] ?? 'monthly';
        $method = $data['payment_method'] ?? 'mobile_money';
        $provider = $data['payment_provider'] ?? '';
        $phone = $data['phone_number'] ?? '';
        $refType = $data['reference_type'] ?? '';
        $refId = (int)($data['reference_id'] ?? 0);
        $notes = $data['notes'] ?? '';

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
        $stmt = $conn->prepare("
            SELECT ps.*,
                (SELECT COUNT(*) FROM subscription_deductions sd WHERE sd.subscription_id = ps.id AND sd.status = 'success') AS successful_deductions,
                (SELECT SUM(sd.amount) FROM subscription_deductions sd WHERE sd.subscription_id = ps.id AND sd.status = 'success') AS total_deducted
            FROM payment_subscriptions ps
            WHERE ps.student_id = ?
            ORDER BY ps.created_at DESC
        ");
        $stmt->bind_param("s", $studentId);
        $stmt->execute();
        $r = $stmt->get_result();
        $result = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
        return $result;
    }
}

if (!function_exists('cancelSubscription')) {
    function cancelSubscription($subscriptionId, $studentId = null) {
        $conn = getStudentsConnection();
        if (!$conn) return false;
        $id = (int)$subscriptionId;
        if ($studentId) {
            $stmt = $conn->prepare("UPDATE payment_subscriptions SET status = 'cancelled' WHERE id = ? AND student_id = ?");
            $stmt->bind_param("is", $id, $studentId);
        } else {
            $stmt = $conn->prepare("UPDATE payment_subscriptions SET status = 'cancelled' WHERE id = ?");
            $stmt->bind_param("i", $id);
        }
        $stmt->execute();
        $affected = $conn->affected_rows;
        $stmt->close();
        return $affected > 0;
    }
}

if (!function_exists('pauseSubscription')) {
    function pauseSubscription($subscriptionId, $studentId = null) {
        $conn = getStudentsConnection();
        if (!$conn) return false;
        $id = (int)$subscriptionId;
        if ($studentId) {
            $stmt = $conn->prepare("UPDATE payment_subscriptions SET status = 'paused' WHERE id = ? AND student_id = ?");
            $stmt->bind_param("is", $id, $studentId);
        } else {
            $stmt = $conn->prepare("UPDATE payment_subscriptions SET status = 'paused' WHERE id = ?");
            $stmt->bind_param("i", $id);
        }
        $stmt->execute();
        $affected = $conn->affected_rows;
        $stmt->close();
        return $affected > 0;
    }
}

if (!function_exists('resumeSubscription')) {
    function resumeSubscription($subscriptionId, $studentId = null) {
        $conn = getStudentsConnection();
        if (!$conn) return false;
        $id = (int)$subscriptionId;
        $freqStmt = $conn->prepare("SELECT frequency FROM payment_subscriptions WHERE id = ?");
        $freqStmt->bind_param("i", $id);
        $freqStmt->execute();
        $q = $freqStmt->get_result();
        $interval = $q ? $q->fetch_assoc() : null;
        $freqStmt->close();
        $freq = $interval['frequency'] ?? 'monthly';
        $sqlFreq = $freq === 'weekly' ? 'INTERVAL 1 WEEK' : ($freq === 'quarterly' ? 'INTERVAL 3 MONTH' : 'INTERVAL 1 MONTH');
        $dateStmt = $conn->prepare("SELECT DATE_ADD(CURDATE(), $sqlFreq) AS nd");
        $dateStmt->execute();
        $dq = $dateStmt->get_result();
        $nextDue = ($dq && $r = $dq->fetch_assoc()) ? $r['nd'] : null;
        $dateStmt->close();
        if ($studentId) {
            $updStmt = $conn->prepare("UPDATE payment_subscriptions SET status = 'active', next_due_date = ? WHERE id = ? AND student_id = ?");
            $updStmt->bind_param("sis", $nextDue, $id, $studentId);
        } else {
            $updStmt = $conn->prepare("UPDATE payment_subscriptions SET status = 'active', next_due_date = ? WHERE id = ?");
            $updStmt->bind_param("si", $nextDue, $id);
        }
        $updStmt->execute();
        $affected = $conn->affected_rows;
        $updStmt->close();
        return $affected > 0;
    }
}

if (!function_exists('getAllSubscriptions')) {
    function getAllSubscriptions($status = null, $limit = 100) {
        $conn = getStudentsConnection();
        if (!$conn) return [];
        $sql = "
            SELECT ps.*, s.full_name, s.program, s.phone AS student_phone, s.student_number,
                (SELECT SUM(sd.amount) FROM subscription_deductions sd WHERE sd.subscription_id = ps.id AND sd.status = 'success') AS total_collected,
                (SELECT COUNT(*) FROM subscription_deductions sd WHERE sd.subscription_id = ps.id) AS total_attempts
            FROM payment_subscriptions ps
            LEFT JOIN students s ON CAST(s.id AS CHAR) = ps.student_id
        ";
        if ($status) {
            $sql .= " WHERE ps.status = ?";
            $stmt = $conn->prepare($sql . " ORDER BY ps.created_at DESC LIMIT " . (int)$limit);
            $stmt->bind_param("s", $status);
        } else {
            $stmt = $conn->prepare($sql . " ORDER BY ps.created_at DESC LIMIT " . (int)$limit);
        }
        $stmt->execute();
        $r = $stmt->get_result();
        $result = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
        return $result;
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
