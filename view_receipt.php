<?php
require_once __DIR__ . '/includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard([]);
require_once __DIR__ . '/includes/receipt_generator.php';

$code = trim($_GET['code'] ?? '');
$id = (int)($_GET['id'] ?? 0);

if (!empty($code)) {
    $conn = $ctx['staff'];
    if ($conn) {
        $stmt = $conn->prepare("SELECT id FROM generated_documents WHERE document_code = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $code);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($row) {
                $id = (int)$row['id'];
            }
        }
    }
}

if ($id > 0) {
    $conn = $ctx['staff'];
    if ($conn) {
        $stmt = $conn->prepare("SELECT document_type, document_data, document_path FROM generated_documents WHERE id = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $doc = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($doc) {
                if (!empty($doc['document_data'])) {
                    echo $doc['document_data'];
                    exit();
                }
                if (!empty($doc['document_path']) && file_exists(__DIR__ . '/' . $doc['document_path'])) {
                    readfile(__DIR__ . '/' . $doc['document_path']);
                    exit();
                }
            }
        }
    }
}

if ($id > 0) {
    $receiptHtml = ReceiptGenerator::generateReceiptHTML($id);
    if ($receiptHtml) {
        echo $receiptHtml;
        exit();
    }
}

echo '<!DOCTYPE html><html><head><title>Receipt Not Found</title></head><body>';
echo '<div style="text-align:center;padding:50px;font-family:sans-serif;">';
echo '<h2>Receipt Not Found</h2>';
echo '<p>The requested receipt could not be found.</p>';
echo '<a href="../dashboards/school-bursar.php">Back to Bursar Dashboard</a>';
echo '</div></body></html>';
