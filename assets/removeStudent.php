<?php
include("config.php");
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo 'Method not allowed'; exit; }

$studentid = intval($_POST['studentid'] ?? 0);
if ($studentid <= 0) { echo 'Invalid student ID'; exit; }

try {
    $conn->begin_transaction();
    $stmt = $conn->prepare("DELETE FROM student_guardian WHERE student_id=?");
    if ($stmt) { $stmt->bind_param('i', $studentid); $stmt->execute(); $stmt->close(); }

    $stmt2 = $conn->prepare("DELETE FROM students WHERE id=?");
    if ($stmt2) { $stmt2->bind_param('i', $studentid); $ok = $stmt2->execute(); $stmt2->close(); } else { $ok = false; }

    if ($ok) { $conn->commit(); echo 'success'; } else { $conn->rollback(); echo 'Failed to delete student'; }
} catch (Exception $e) { $conn->rollback(); error_log('removeStudent error: ' . $e->getMessage()); echo 'Database error'; }
