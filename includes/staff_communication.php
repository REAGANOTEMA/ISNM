<?php
/**
 * Staff Communication System
 * Allows all staff to send internal communications to departments using their auth email.
 * Available in all dashboards via dashboard_footer.php
 */

// Ensure database config is loaded (safe even if already loaded)
if (!function_exists('getStaffConnection')) {
    $dbConfig = __DIR__ . '/../config/database.php';
    if (file_exists($dbConfig)) require_once $dbConfig;
}

if (!function_exists('ensureCommunicationTables')) {
    function ensureCommunicationTables($conn) {
        if (!$conn) return false;
        $tables_ok = true;

        // Create communication_channels table if missing
        $r = $conn->query("SHOW TABLES LIKE 'communication_channels'");
        if (!$r || $r->num_rows === 0) {
            $sql = "CREATE TABLE IF NOT EXISTS `communication_channels` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `department_code` VARCHAR(20) NOT NULL,
                `department_name` VARCHAR(255) NOT NULL,
                `routing_email` VARCHAR(255) DEFAULT NULL,
                `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_department_code` (`department_code`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
            if (!$conn->query($sql)) {
                error_log('Failed to create communication_channels: ' . $conn->error);
                $tables_ok = false;
            }
        }

        // Create staff_communications table if missing
        $r2 = $conn->query("SHOW TABLES LIKE 'staff_communications'");
        if (!$r2 || $r2->num_rows === 0) {
            $sql = "CREATE TABLE IF NOT EXISTS `staff_communications` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `sender_id` INT(11) NOT NULL,
                `sender_email` VARCHAR(255) NOT NULL,
                `sender_name` VARCHAR(255) NOT NULL,
                `recipient_type` ENUM('department','all_staff') NOT NULL DEFAULT 'department',
                `recipient_id` VARCHAR(50) DEFAULT NULL,
                `recipient_name` VARCHAR(255) DEFAULT NULL,
                `subject` VARCHAR(255) NOT NULL,
                `message_body` TEXT NOT NULL,
                `priority` ENUM('Low','Normal','High','Urgent') NOT NULL DEFAULT 'Normal',
                `email_status` ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_sender_id` (`sender_id`),
                KEY `idx_recipient_type` (`recipient_type`),
                KEY `idx_recipient_id` (`recipient_id`),
                KEY `idx_created_at` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
            if (!$conn->query($sql)) {
                error_log('Failed to create staff_communications: ' . $conn->error);
                $tables_ok = false;
            }
        }

        // Seed departments if channels table is empty
        $r3 = $conn->query("SELECT COUNT(*) AS cnt FROM communication_channels");
        if ($r3 && ($row3 = $r3->fetch_assoc()) && (int)$row3['cnt'] === 0) {
            $seed = "INSERT IGNORE INTO communication_channels (department_code, department_name, routing_email)
                     SELECT department_code, department_name,
                            COALESCE(NULLIF(TRIM(contact_email),''),
                                     CONCAT(LOWER(department_code),'@igangaschoolofnursingandmidwifery.ac.ug'))
                     FROM staff_departments";
            $conn->query($seed);
        }

        return $tables_ok;
    }
}

if (!function_exists('getCommunicationChannels')) {
    function getCommunicationChannels($conn) {
        $channels = [];
        if (!$conn) return $channels;
        $r = $conn->query("SELECT department_code, department_name, routing_email FROM communication_channels WHERE is_active = 1 ORDER BY department_name");
        if ($r) while ($row = $r->fetch_assoc()) $channels[] = $row;
        return $channels;
    }
}

if (!function_exists('sendStaffCommunication')) {
    function sendStaffCommunication($conn, $sender_id, $sender_email, $sender_name, $recipient_type, $recipient_id, $recipient_name, $subject, $message, $priority = 'Normal') {
        try {
            if (!$conn) return ['success' => false, 'error' => 'Database connection not available'];

            $stmt = $conn->prepare("INSERT INTO staff_communications (sender_id, sender_email, sender_name, recipient_type, recipient_id, recipient_name, subject, message_body, priority, email_status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
            if (!$stmt) return ['success' => false, 'error' => 'Database prepare failed: ' . $conn->error];

            $stmt->bind_param("issssssss", $sender_id, $sender_email, $sender_name, $recipient_type, $recipient_id, $recipient_name, $subject, $message, $priority);
            if (!$stmt->execute()) return ['success' => false, 'error' => $stmt->error];

            $comm_id = $stmt->insert_id;
            $stmt->close();

            $to_emails = [];

            if ($recipient_type === 'department' && $recipient_id) {
                $stmt1 = $conn->prepare("SELECT routing_email FROM communication_channels WHERE department_code = ? AND is_active = 1 LIMIT 1");
                if ($stmt1) {
                    $stmt1->bind_param("s", $recipient_id);
                    if (!$stmt1->execute()) { error_log('$stmt1 execute failed: ' . ($stmt1->error ?? 'unknown')); };
                    $r = $stmt1->get_result();
                    if ($r && ($row = $r->fetch_assoc()) && !empty($row['routing_email'])) {
                        $to_emails[] = $row['routing_email'];
                    }
                    $stmt1->close();
                }
                $stmt2 = $conn->prepare("SELECT email FROM staff WHERE department = ? AND email IS NOT NULL AND email != '' AND status = 'Active'");
                if ($stmt2) {
                    $stmt2->bind_param("s", $recipient_name);
                    if (!$stmt2->execute()) { error_log('$stmt2 execute failed: ' . ($stmt2->error ?? 'unknown')); };
                    $sr = $stmt2->get_result();
                    if ($sr) while ($srow = $sr->fetch_assoc()) $to_emails[] = $srow['email'];
                    $stmt2->close();
                }
            } elseif ($recipient_type === 'all_staff') {
                $sr = $conn->query("SELECT email FROM staff WHERE email IS NOT NULL AND email != '' AND status = 'Active'");
                if ($sr) while ($srow = $sr->fetch_assoc()) $to_emails[] = $srow['email'];
            }

            $to_emails = array_unique(array_filter($to_emails));
            $email_sent = false;

            if (!empty($to_emails)) {
                $headers = "From: $sender_name <$sender_email>\r\n";
                $headers .= "Reply-To: $sender_email\r\n";
                $headers .= "MIME-Version: 1.0\r\n";
                $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

                $html_body = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="font-family:Arial,Helvetica,sans-serif;padding:20px;color:#333">';
                $html_body .= '<div style="max-width:600px;margin:0 auto;border:1px solid #e0e0e0;border-radius:8px;overflow:hidden">';
                $html_body .= '<div style="background:#1a237e;color:#fff;padding:15px 20px"><h2 style="margin:0;font-size:18px">' . htmlspecialchars($subject) . '</h2></div>';
                $html_body .= '<div style="padding:20px">';
                $html_body .= '<p style="color:#666;font-size:13px"><strong>From:</strong> ' . htmlspecialchars($sender_name) . ' (' . htmlspecialchars($sender_email) . ')</p>';
                $html_body .= '<p style="color:#666;font-size:13px"><strong>To:</strong> ' . htmlspecialchars($recipient_name) . '</p>';
                $html_body .= '<p style="color:#666;font-size:13px"><strong>Priority:</strong> ' . htmlspecialchars($priority) . '</p>';
                $html_body .= '<hr style="border:none;border-top:1px solid #eee">';
                $html_body .= '<div style="line-height:1.6">' . nl2br(htmlspecialchars($message)) . '</div>';
                $html_body .= '</div>';
                $html_body .= '<div style="background:#f5f5f5;padding:10px 20px;text-align:center;font-size:11px;color:#999">';
                $html_body .= 'Sent via ISNM Staff Communication System &mdash; Iganga School of Nursing &amp; Midwifery</div>';
                $html_body .= '</div></body></html>';

                foreach ($to_emails as $to) {
                    @mail(trim($to), $subject, $html_body, $headers);
                }
                $email_sent = true;
                $conn->query("UPDATE staff_communications SET email_status = 'sent' WHERE id = $comm_id");
            }

            return ['success' => true, 'communication_id' => $comm_id, 'email_sent' => $email_sent, 'recipients_count' => count($to_emails)];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}

if (!function_exists('getStaffRecentCommunications')) {
    function getStaffRecentCommunications($conn, $staff_id, $limit = 5) {
        $msgs = [];
        if (!$conn) return $msgs;
        $stmt = $conn->prepare("SELECT id, recipient_type, recipient_name, subject, message_body, priority, email_status, created_at FROM staff_communications WHERE sender_id = ? ORDER BY created_at DESC LIMIT ?");
        if ($stmt) {
            $stmt->bind_param("ii", $staff_id, $limit);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) $msgs[] = $row;
            $stmt->close();
        }
        return $msgs;
    }
}

if (!function_exists('renderCommunicationModal')) {
    function renderCommunicationModal() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (empty($_SESSION['logged_in']) || $_SESSION['type'] !== 'staff') return;

        $staff_id    = (int)($_SESSION['user_id'] ?? 0);
        $staff_email = $_SESSION['email'] ?? '';
        $staff_name  = $_SESSION['full_name'] ?? 'Staff Member';

        if (!$staff_id || !$staff_email) return;

        $conn = null;
        if (function_exists('getStaffConnection')) {
            $conn = getStaffConnection();
        } elseif (function_exists('getDatabaseConnection')) {
            $conn = getDatabaseConnection('staffs');
        }

        // Auto-create tables and seed departments if needed
        if ($conn) ensureCommunicationTables($conn);

        $channels = getCommunicationChannels($conn);
        ?>
        <!-- â•â•â• Staff Communication Modal â•â•â• -->
        <div class="modal fade" id="staffCommModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title"><i class="fas fa-envelope me-2"></i>Send Department Communication</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="staffCommForm" onsubmit="return submitStaffCommunication()" method="POST">
                        <div class="modal-body">
                            <div class="alert alert-info py-2 small d-flex align-items-center">
                                <i class="fas fa-user-circle me-2 fs-5"></i>
                                <span>Sending as: <strong><?= htmlspecialchars($staff_name) ?></strong> &lt;<?= htmlspecialchars($staff_email) ?>&gt;</span>
                            </div>
                            <input type="hidden" name="action" value="send_communication">
                            <input type="hidden" name="sender_id" value="<?= $staff_id ?>">
                            <input type="hidden" name="sender_email" value="<?= htmlspecialchars($staff_email) ?>">
                            <input type="hidden" name="sender_name" value="<?= htmlspecialchars($staff_name) ?>">

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Send To <span class="text-danger">*</span></label>
                                    <select name="recipient_type" id="commRecipientType" class="form-select" onchange="toggleCommDepartmentField()" required>
                                        <option value="department">Department</option>
                                        <option value="all_staff">All Staff</option>
                                    </select>
                                </div>
                                <div class="col-md-6" id="commDepartmentCol">
                                    <label class="form-label fw-semibold">Select Department <span class="text-danger">*</span></label>
                                    <select name="recipient_id" id="commDepartmentId" class="form-select">
                                        <option value="">-- Choose --</option>
                                        <?php foreach ($channels as $ch): ?>
                                        <option value="<?= htmlspecialchars($ch['department_code']) ?>" data-name="<?= htmlspecialchars($ch['department_name']) ?>"><?= htmlspecialchars($ch['department_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3 mt-3">
                                <label class="form-label fw-semibold">Subject <span class="text-danger">*</span></label>
                                <input type="text" name="subject" class="form-control" required maxlength="255" placeholder="Enter message subject...">
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Message <span class="text-danger">*</span></label>
                                <textarea name="message" class="form-control" rows="6" required placeholder="Write your message here..." style="resize:vertical"></textarea>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Priority</label>
                                    <select name="priority" class="form-select">
                                        <option value="Normal">Normal</option>
                                        <option value="Low">Low</option>
                                        <option value="High">High</option>
                                        <option value="Urgent">Urgent</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <div id="commSendStatus" class="me-auto small"></div>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" id="commSendBtn"><i class="fas fa-paper-plane me-1"></i> Send Message</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
        function toggleCommDepartmentField() {
            var t = document.getElementById('commRecipientType').value;
            var col = document.getElementById('commDepartmentCol');
            var sel = document.getElementById('commDepartmentId');
            if (t === 'department') {
                col.style.display = '';
                sel.required = true;
            } else {
                col.style.display = 'none';
                sel.required = false;
            }
        }

        function submitStaffCommunication() {
            var form = document.getElementById('staffCommForm');
            var data = new FormData(form);
            var btn = document.getElementById('commSendBtn');
            var status = document.getElementById('commSendStatus');

            var recipType = data.get('recipient_type');
            var recipId = data.get('recipient_id');
            if (recipType === 'department' && !recipId) {
                status.innerHTML = '<span class="text-danger"><i class="fas fa-exclamation-circle me-1"></i>Please select a department.</span>';
                return false;
            }

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Sending...';
            status.innerHTML = '<span class="text-info"><i class="fas fa-circle-notch fa-spin me-1"></i>Sending...</span>';

            fetch('../includes/ajax_staff_communication.php', {
                method: 'POST',
                body: data
            })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                if (d.success) {
                    var rc = d.recipients_count || 0;
                    status.innerHTML = '<span class="text-success"><i class="fas fa-check-circle me-1"></i>Message sent! Notified ' + rc + ' recipient(s).</span>';
                    form.reset();
                    toggleCommDepartmentField();
                    setTimeout(function() {
                        var el = document.getElementById('staffCommModal');
                        var modal = bootstrap.Modal.getInstance(el);
                        if (modal) modal.hide();
                        status.innerHTML = '';
                    }, 2000);
                } else {
                    status.innerHTML = '<span class="text-danger"><i class="fas fa-exclamation-circle me-1"></i>' + (d.error || 'Failed to send message.') + '</span>';
                }
            })
            .catch(function() {
                status.innerHTML = '<span class="text-danger"><i class="fas fa-exclamation-circle me-1"></i>Network error. Please try again.</span>';
            })
            .finally(function() {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-paper-plane me-1"></i> Send Message';
            });

            return false;
        }
        </script>
        <?php
    }
}
