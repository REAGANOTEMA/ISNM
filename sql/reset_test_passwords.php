<?php
require __DIR__ . '/../config/database.php';
$conn = getStaffConnection();

// Reset HR Manager password to a known value
$newPassword = password_hash('HR@2026', PASSWORD_DEFAULT);
$stmt = $conn->prepare("UPDATE staff SET password = ?, is_first_login = 0, password_changed = 1 WHERE email = ? AND role_id = 9");
$email = 'hr-manager@igangaschoolofnursingandmidwifery.ac.ug';
$stmt->bind_param("ss", $newPassword, $email);
if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
echo "HR Manager password reset. Affected: " . $stmt->affected_rows . PHP_EOL;

// Also reset Bursar
$newPassword2 = password_hash('Bursar@2026', PASSWORD_DEFAULT);
$stmt2 = $conn->prepare("UPDATE staff SET password = ?, is_first_login = 0, password_changed = 1 WHERE email = ? AND role_id = 24");
$email2 = 'bursar@igangaschoolofnursingandmidwifery.ac.ug';
$stmt2->bind_param("ss", $newPassword2, $email2);
if (!$stmt2->execute()) { error_log('$stmt2 execute failed: ' . ($stmt2->error ?? 'unknown')); };
echo "Bursar password reset. Affected: " . $stmt2->affected_rows . PHP_EOL;

$conn->close();
echo PHP_EOL . "Credentials:" . PHP_EOL;
echo "  HR Manager: hr-manager@igangaschoolofnursingandmidwifery.ac.ug / HR@2026" . PHP_EOL;
echo "  Bursar: bursar@igangaschoolofnursingandmidwifery.ac.ug / Bursar@2026" . PHP_EOL;
