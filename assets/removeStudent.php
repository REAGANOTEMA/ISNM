<?php
include("config.php");

if (!function_exists('isnm_require_staff_role')) { require_once __DIR__ . '/config.php'; }
isnm_require_staff_role();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo 'Method not allowed'; exit; }

$studentid = intval($_POST['studentid'] ?? 0);
if ($studentid <= 0) { echo 'Invalid student ID'; exit; }

try {
    $stmt = $conn->prepare("UPDATE students SET status = 'deleted', updated_at = NOW() WHERE id = ?");
    if (!$stmt) { echo 'Database error'; exit; }
    $stmt->bind_param('i', $studentid);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo 'success';
        } else {
            echo 'Student not found or already deleted';
        }
    } else {
        echo 'Failed to delete student';
    }
    $stmt->close();
} catch (Exception $e) {
    error_log('removeStudent error: ' . $e->getMessage());
    echo 'Database error';
}
?>
