<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/enterprise_auth.php';

$ctx = bootstrapStaffDashboard(['sickbay', 'sickbay nurse']);
$staff_conn = $ctx['staff'];
$students_conn = $ctx['students'];
$user = $ctx['user'];
$user_name = $user['full_name'] ?? 'Sickbay Staff';
$user_role = $user['role'] ?? 'Sickbay';
$user_id = (int)($user['id'] ?? 0);

function sb_q($conn, $sql) {
    if (!$conn) return 0;
    try { $r = $conn->query($sql); if (!$r) return 0; $row = $r->fetch_assoc(); return (int)($row[array_key_first($row)] ?? 0); }
    catch (Exception $e) { error_log('sickbay getCount: ' . $e->getMessage()); return 0; }
}

function sb_fetch($conn, $sql) {
    if (!$conn) return [];
    try { $r = $conn->query($sql); if (!$r) return []; return $r->fetch_all(MYSQLI_ASSOC); }
    catch (Exception $e) { error_log('sickbay getList: ' . $e->getMessage()); return []; }
}

$active_section = $_GET['section'] ?? $_GET['page'] ?? 'dashboard';

// Handle GET actions (AJAX)
$action_get = $_GET['action'] ?? '';
if ($action_get === 'search_student' && $students_conn) {
    header('Content-Type: application/json');
    $q = trim($_GET['q'] ?? '');
    if (strlen($q) >= 2) {
        $like = "%$q%";
        $stmt = $students_conn->prepare("SELECT id, full_name, student_id, student_number, program, phone FROM students WHERE full_name LIKE ? OR student_id LIKE ? OR student_number LIKE ? OR phone LIKE ? LIMIT 10");
        if ($stmt) { $stmt->bind_param('ssss', $like, $like, $like, $like); $result = $stmt->execute() ? $stmt->get_result() : null; $stmt->close(); }
        else $result = null;
        $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        echo json_encode($rows);
    } else { echo json_encode([]); }
    exit;
}
if ($action_get === 'get_transactions' && $staff_conn) {
    $mid = (int)($_GET['id'] ?? 0);
    if ($mid > 0) {
        $stmt = $staff_conn->prepare("SELECT smt.*, sms.medicine_name FROM sickbay_medicine_transactions smt LEFT JOIN sickbay_medicine_stock sms ON smt.medicine_id = sms.id WHERE smt.medicine_id = ? ORDER BY smt.created_at DESC LIMIT 50");
        if ($stmt) { $stmt->bind_param('i', $mid); $stmt->execute(); $r = $stmt->get_result(); $stmt->close(); } else $r = null;
        $txns = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
        if (!empty($txns)) {
            echo '<table class="table table-sm"><thead><tr><th>Date</th><th>Type</th><th>Qty</th><th>Notes</th></tr></thead><tbody>';
            foreach ($txns as $t) { echo '<tr><td>'.htmlspecialchars($t['transaction_date']??$t['created_at']??'').'</td><td><span class="badge bg-info">'.htmlspecialchars($t['transaction_type']??'N/A').'</span></td><td>'.(int)($t['quantity']??0).'</td><td>'.htmlspecialchars($t['notes']??'').'</td></tr>'; }
            echo '</tbody></table>';
        } else { echo '<p class="text-muted">No transactions.</p>'; }
    } else { echo '<p class="text-danger">Invalid medicine ID.</p>'; }
    exit;
}
if ($action_get === 'get_visit' && $staff_conn) {
    $vid = (int)($_GET['id'] ?? 0);
    if ($vid > 0) {
        $stmt = $staff_conn->prepare("SELECT * FROM sickbay_visits WHERE id=?");
        if ($stmt) { $stmt->bind_param('i', $vid); $stmt->execute(); $r = $stmt->get_result(); $stmt->close(); } else $r = null;
        $visit = $r ? $r->fetch_assoc() : null;
        header('Content-Type: application/json');
        echo json_encode($visit ?: new stdClass());
    } else { header('Content-Type: application/json'); echo '{}'; }
    exit;
}
if ($action_get === 'get_sb_medicine' && $staff_conn) {
    $mid = (int)($_GET['id'] ?? 0);
    if ($mid > 0) {
        $stmt = $staff_conn->prepare("SELECT * FROM sickbay_medicine_stock WHERE id=?");
        if ($stmt) { $stmt->bind_param('i', $mid); $stmt->execute(); $r = $stmt->get_result(); $stmt->close(); } else $r = null;
        $med = $r ? $r->fetch_assoc() : null;
        header('Content-Type: application/json');
        echo json_encode($med ?: new stdClass());
    } else { header('Content-Type: application/json'); echo '{}'; }
    exit;
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $staff_conn) {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_sickness') {
        $id = (int)($_POST['id'] ?? 0);
        $code = trim($_POST['sickness_code']);
        $name = trim($_POST['sickness_name']);
        $cat = trim($_POST['category']);
        $symptoms = trim($_POST['common_symptoms']);
        $desc = trim($_POST['description']);
        $contagious = isset($_POST['is_contagious']) ? 1 : 0;
        $treatment = trim($_POST['typical_treatment']);
        $s = trim($_POST['status']);
        if ($id > 0) {
            $stmt = $staff_conn->prepare("UPDATE sickness_directory SET sickness_code=?, sickness_name=?, category=?, common_symptoms=?, description=?, is_contagious=?, typical_treatment=?, status=? WHERE id=?");
            if ($stmt) { $stmt->bind_param('sssssisii', $code, $name, $cat, $symptoms, $desc, $contagious, $treatment, $s, $id); $stmt->execute(); $stmt->close(); }
            $_SESSION['success'] = 'Sickness updated successfully.';
        } else {
            $stmt = $staff_conn->prepare("INSERT INTO sickness_directory (sickness_code, sickness_name, category, common_symptoms, description, is_contagious, typical_treatment, status, created_by) VALUES (?,?,?,?,?,?,?,?,?)");
            if ($stmt) { $stmt->bind_param('sssssissi', $code, $name, $cat, $symptoms, $desc, $contagious, $treatment, $s, $user_id); $stmt->execute(); $stmt->close(); }
            $_SESSION['success'] = 'Sickness added successfully.';
        }
        header('Location: sickbay.php?section=sickness'); exit;
    }

    if ($action === 'delete_sickness') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $staff_conn->prepare("UPDATE sickness_directory SET status='Inactive' WHERE id=?");
            if ($stmt) { $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close(); }
            $_SESSION['success'] = 'Sickness deactivated.';
        }
        header('Location: sickbay.php?section=sickness'); exit;
    }

    if ($action === 'save_sick_record') {
        $rec_num = 'DSR-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
        $sid = (int)($_POST['student_id'] ?? 0);
        $sname = trim($_POST['student_name']);
        $snum = trim($_POST['student_number']);
        $prog = trim($_POST['program']);
        $year = (int)($_POST['year_of_study'] ?? 0);
        $sick_id = !empty($_POST['sickness_id']) ? (int)$_POST['sickness_id'] : null;
        $sick_name = trim($_POST['sickness_name']);
        $temp = trim($_POST['temperature']);
        $bp = trim($_POST['blood_pressure']);
        $symp = trim($_POST['symptoms']);
        $diag = trim($_POST['diagnosis']);
        $treat = trim($_POST['treatment_given']);
        $meds = trim($_POST['medicines_prescribed']);
        $sev = trim($_POST['severity']);
        $stat = trim($_POST['status']);
        $ref = trim($_POST['referred_to']);
        $vdate = trim($_POST['visit_date']);
        $vtime = trim($_POST['visit_time'] ?: date('H:i:s'));
        $fud = !empty($_POST['follow_up_date']) ? trim($_POST['follow_up_date']) : null;
        $notes = trim($_POST['notes']);
        $stmt = $staff_conn->prepare("INSERT INTO daily_sick_records (record_number, student_id, student_name, student_number, program, year_of_study, sickness_id, sickness_name, temperature, blood_pressure, symptoms, diagnosis, treatment_given, medicines_prescribed, severity, status, referred_to, attended_by, visit_date, visit_time, follow_up_date, notes, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        if ($stmt) { $stmt->bind_param('sissssisissssssssssssii', $rec_num, $sid, $sname, $snum, $prog, $year, $sick_id, $sick_name, $temp, $bp, $symp, $diag, $treat, $meds, $sev, $stat, $ref, $user_name, $vdate, $vtime, $fud, $notes, $user_id); $stmt->execute(); $stmt->close(); }
        $_SESSION['success'] = 'Sick record saved. #'.$rec_num;
        header('Location: sickbay.php?section=daily-records'); exit;
    }

    if ($action === 'delete_sick_record') {
        $rid = (int)($_POST['record_id'] ?? 0);
        if ($rid > 0) {
            $stmt = $staff_conn->prepare("UPDATE daily_sick_records SET is_deleted=1, deleted_at=NOW() WHERE id=?");
            if ($stmt) { $stmt->bind_param('i', $rid); $stmt->execute(); $stmt->close(); }
            $_SESSION['success'] = 'Record moved to Recycle Bin.';
        }
        header('Location: sickbay.php?section=daily-records'); exit;
    }

    if ($action === 'save_leave') {
        $leave_num = 'SL-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
        $sid = (int)($_POST['student_id'] ?? 0);
        $sname = trim($_POST['student_name']);
        $snum = trim($_POST['student_number']);
        $prog = trim($_POST['program']);
        $year = (int)($_POST['year_of_study'] ?? 0);
        $sick_id = !empty($_POST['sickness_id']) ? (int)$_POST['sickness_id'] : null;
        $other_sick = trim($_POST['other_sickness']);
        $from = trim($_POST['start_date']);
        $to = trim($_POST['end_date']);
        $bed = ($_POST['bed_rest_required'] ?? 'Yes') === 'Yes' ? 1 : 0;
        $recs = trim($_POST['recommendations']);
        $recommender = trim($_POST['recommended_by'] ?? $user_name);
        $stmt = $staff_conn->prepare("INSERT INTO student_sick_leave (leave_number, student_id, student_name, student_number, program, year_of_study, sickness_id, sickness_name, leave_from, leave_to, status, recommended_by, bed_rest_required, doctor_notes, created_by) VALUES (?,?,?,?,?,?,?,?,?,?, 'Pending', ?, ?, ?, ?)");
        if ($stmt) { $stmt->bind_param('sisssisssssiiii', $leave_num, $sid, $sname, $snum, $prog, $year, $sick_id, $other_sick, $from, $to, $recommender, $bed, $recs, $user_id); $stmt->execute(); $stmt->close(); }
        $_SESSION['success'] = 'Sick leave issued. #'.$leave_num;
        header('Location: sickbay.php?section=leave'); exit;
    }

    if ($action === 'delete_leave') {
        $lid = (int)($_POST['id'] ?? 0);
        if ($lid > 0) {
            $stmt = $staff_conn->prepare("UPDATE student_sick_leave SET is_deleted=1, deleted_at=NOW() WHERE id=?");
            if ($stmt) { $stmt->bind_param('i', $lid); $stmt->execute(); $stmt->close(); }
            $_SESSION['success'] = 'Leave moved to Recycle Bin.';
        }
        header('Location: sickbay.php?section=leave'); exit;
    }

    if ($action === 'save_medicine') {
        $id = (int)($_POST['id'] ?? 0);
        $code = trim($_POST['medicine_code']);
        $mname = trim($_POST['medicine_name']);
        $generic = trim($_POST['generic_name']);
        $cat = trim($_POST['category']);
        $form = trim($_POST['dosage_form']);
        $strength = trim($_POST['strength']);
        $mfr = trim($_POST['manufacturer']);
        $sup = trim($_POST['supplier']);
        $qty = (int)($_POST['quantity_in_stock'] ?? 0);
        $unit = trim($_POST['unit']);
        $rol = (int)($_POST['reorder_level'] ?? 10);
        $uc = (float)($_POST['unit_cost'] ?? 0);
        $sp = (float)($_POST['selling_price'] ?? 0);
        $cur = trim($_POST['currency'] ?? 'UGX');
        $batch = trim($_POST['batch_number']);
        $exp = !empty($_POST['expiry_date']) ? trim($_POST['expiry_date']) : null;
        $loc = trim($_POST['storage_location']);
        $rx = isset($_POST['requires_prescription']) ? 1 : 0;
        $inst = trim($_POST['instructions']);
        $se = trim($_POST['side_effects']);
        $stat = trim($_POST['status']);
        $restocked = !empty($_POST['last_restocked']) ? trim($_POST['last_restocked']) : null;
        $status_calc = $qty <= 0 ? 'Out of Stock' : ($qty <= $rol ? 'Low Stock' : 'In Stock');
        if ($id > 0) {
            $stmt = $staff_conn->prepare("UPDATE sickbay_medicine_stock SET medicine_name=?, category=?, quantity=?, unit=?, expiry_date=?, reorder_level=?, status=? WHERE id=?");
            if ($stmt) { $stmt->bind_param('ssisssii', $mname, $cat, $qty, $unit, $exp, $rol, $ns, $id); $stmt->execute(); $stmt->close(); }
            $_SESSION['success'] = 'Medicine updated.';
        } else {
            $stmt = $staff_conn->prepare("INSERT INTO sickbay_medicine_stock (medicine_name, category, quantity, unit, expiry_date, reorder_level, status) VALUES (?,?,?,?,?,?,?)");
            if ($stmt) { $stmt->bind_param('ssisssi', $mname, $cat, $qty, $unit, $exp, $rol, $ns); $stmt->execute(); $stmt->close(); }
            $_SESSION['success'] = 'Medicine added.';
        }
        header('Location: sickbay.php?section=medicine'); exit;
    }

    if ($action === 'delete_medicine') {
        $mid = (int)($_POST['medicine_id'] ?? 0);
        if ($mid > 0) {
            $stmt = $staff_conn->prepare("DELETE FROM sickbay_medicine_stock WHERE id=?");
            if ($stmt) { $stmt->bind_param('i', $mid); $stmt->execute(); $stmt->close(); }
            $_SESSION['success'] = 'Medicine deleted.';
        }
        header('Location: sickbay.php?section=medicine'); exit;
    }

    if ($action === 'stock_transaction') {
        $mid = (int)($_POST['medicine_id'] ?? 0);
        $ttype = trim($_POST['transaction_type']);
        $qty = (int)($_POST['quantity'] ?? 0);
        $tdate = trim($_POST['transaction_date'] ?: date('Y-m-d'));
        $notes = trim($_POST['notes']);
        $trans_num = 'MST-'.date('Ymd').'-'.strtoupper(substr(uniqid(),-6));
        if ($mid > 0 && $qty > 0) {
            $stmt = $staff_conn->prepare("SELECT quantity FROM sickbay_medicine_stock WHERE id=?");
            if ($stmt) { $stmt->bind_param('i', $mid); $stmt->execute(); $qrMed = $stmt->get_result(); $stmt->close(); } else $qrMed = null;
            $med = $qrMed ? $qrMed->fetch_assoc() : null;
            if ($med) {
                $cq = (int)$med['quantity'];
                $nq = ($ttype === 'Purchase' || $ttype === 'Return') ? $cq + $qty : max(0, $cq - $qty);
                $stmt = $staff_conn->prepare("SELECT reorder_level FROM sickbay_medicine_stock WHERE id=?");
                if ($stmt) { $stmt->bind_param('i', $mid); $stmt->execute(); $qrRl = $stmt->get_result(); $stmt->close(); } else $qrRl = null;
                $rl = $qrRl ? (int)$qrRl->fetch_row()[0] : 0;
                $ns = $nq <= 0 ? 'Out of Stock' : ($nq <= $rl ? 'Low Stock' : 'In Stock');
                $stmt = $staff_conn->prepare("UPDATE sickbay_medicine_stock SET quantity=?, status=? WHERE id=?");
                if ($stmt) { $stmt->bind_param('sii', $ns, $nq, $mid); $stmt->execute(); $stmt->close(); }
                $stmt = $staff_conn->prepare("INSERT INTO sickbay_medicine_transactions (transaction_number, medicine_id, transaction_type, quantity, performed_by, transaction_date, notes) VALUES (?,?,?,?,?,?,?)");
                if ($stmt) { $stmt->bind_param('sisiiis', $trans_num, $mid, $ttype, $qty, $user_id, $tdate, $notes); $stmt->execute(); $stmt->close(); }
                $_SESSION['success'] = "Stock $ttype of $qty recorded. New qty: $nq";
            }
        }
        header('Location: sickbay.php?section=medicine'); exit;
    }

    if ($action === 'restore_record') {
        $rid = (int)($_POST['id'] ?? 0);
        if ($rid > 0) {
            $stmt = $staff_conn->prepare("UPDATE daily_sick_records SET is_deleted=0, deleted_at=NULL WHERE id=?");
            if ($stmt) { $stmt->bind_param('i', $rid); $stmt->execute(); $stmt->close(); }
            $_SESSION['success'] = 'Record restored.';
        }
        header('Location: sickbay.php?section=recycle-bin'); exit;
    }

    if ($action === 'purge_record') {
        $rid = (int)($_POST['id'] ?? 0);
        if ($rid > 0) {
            $stmt = $staff_conn->prepare("DELETE FROM daily_sick_records WHERE id=?");
            if ($stmt) { $stmt->bind_param('i', $rid); $stmt->execute(); $stmt->close(); }
            $_SESSION['success'] = 'Record permanently deleted.';
        }
        header('Location: sickbay.php?section=recycle-bin'); exit;
    }

    if ($action === 'save_health_record') {
        $sid = (int)($_POST['student_id'] ?? 0);
        $sname = trim($_POST['student_name']);
        $snum = trim($_POST['student_number']);
        $bt = trim($_POST['blood_type']);
        $allergies = trim($_POST['allergies']);
        $chronic = trim($_POST['chronic_conditions']);
        $meds = trim($_POST['medications']);
        $ec_name = trim($_POST['emergency_contact_name']);
        $ec_phone = trim($_POST['emergency_contact_phone']);
        $ec_rel = trim($_POST['emergency_contact_relationship']);
        $insurance = trim($_POST['insurance_provider']);
        $ins_num = trim($_POST['insurance_number']);
        $notes = trim($_POST['notes']);
        $stmt = $staff_conn->prepare("SELECT id FROM student_health_records WHERE student_id=?");
        if ($stmt) { $stmt->bind_param('i', $sid); $stmt->execute(); $existing = $stmt->get_result(); $stmt->close(); }
        else $existing = null;
        if ($existing && $existing->num_rows > 0) {
            $row = $existing->fetch_assoc();
            $rid = (int)$row['id'];
            $stmt = $staff_conn->prepare("UPDATE student_health_records SET blood_type=?, allergies=?, chronic_conditions=?, medications=?, emergency_contact_name=?, emergency_contact_phone=?, emergency_contact_relationship=?, insurance_provider=?, insurance_number=?, notes=? WHERE id=?");
            if ($stmt) { $stmt->bind_param('ssssssssssi', $bt, $allergies, $chronic, $meds, $ec_name, $ec_phone, $ec_rel, $insurance, $ins_num, $notes, $rid); $stmt->execute(); $stmt->close(); }
            $_SESSION['success'] = 'Health record updated.';
        } else {
            $rn = 'HR-'.date('Ymd').'-'.strtoupper(substr(uniqid(),-6));
            $stmt = $staff_conn->prepare("INSERT INTO student_health_records (record_number, student_id, blood_type, allergies, chronic_conditions, medications, emergency_contact_name, emergency_contact_phone, emergency_contact_relationship, insurance_provider, insurance_number, notes) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
            if ($stmt) { $stmt->bind_param('sissssssssss', $rn, $sid, $bt, $allergies, $chronic, $meds, $ec_name, $ec_phone, $ec_rel, $insurance, $ins_num, $notes); $stmt->execute(); $stmt->close(); }
            $_SESSION['success'] = 'Health record created.';
        }
        header('Location: sickbay.php?section=health-records'); exit;
    }

    if ($action === 'save_health_incident') {
        $inc_num = 'HI-'.date('Ymd').'-'.strtoupper(substr(uniqid(),-6));
        $sid = (int)($_POST['student_id'] ?? 0);
        $sname = trim($_POST['student_name']);
        $itype = trim($_POST['incident_type']);
        $symptoms = trim($_POST['symptoms']);
        $severity = trim($_POST['severity']);
        $location = trim($_POST['location']);
        $action_taken = trim($_POST['action_taken']);
        $treatment = trim($_POST['treatment_given']);
        $referred = trim($_POST['referred_to']);
        $parent_notified = isset($_POST['parent_notified']) ? 1 : 0;
        $follow_up = !empty($_POST['follow_up_date']) ? trim($_POST['follow_up_date']) : null;
        $notes = trim($_POST['notes']);
        $stmt = $staff_conn->prepare("INSERT INTO health_incidents (incident_number, student_id, incident_type, symptoms, severity, location, action_taken, treatment_given, referred_to, parent_notified, follow_up_date, status, reported_by, notes) VALUES (?,?,?,?,?,?,?,?,?,?,?, 'Reported', ?,?)");
        if ($stmt) { $stmt->bind_param('sisssssssiiii', $inc_num, $sid, $itype, $symptoms, $severity, $location, $action_taken, $treatment, $referred, $parent_notified, $follow_up, $user_id, $notes); $stmt->execute(); $stmt->close(); }
        $_SESSION['success'] = 'Health incident reported. #'.$inc_num;
        header('Location: sickbay.php?section=health-incidents'); exit;
    }

    if ($action === 'resolve_incident') {
        $iid = (int)($_POST['id'] ?? 0);
        if ($iid > 0) {
            $stmt = $staff_conn->prepare("UPDATE health_incidents SET status='Resolved' WHERE id=?");
            if ($stmt) { $stmt->bind_param('i', $iid); $stmt->execute(); $stmt->close(); }
            $_SESSION['success'] = 'Incident resolved.';
        }
        header('Location: sickbay.php?section=health-incidents'); exit;
    }

    if ($action === 'save_settings') {
        $reorder = (int)($_POST['default_reorder'] ?? 10);
        $threshold = (int)($_POST['low_stock_threshold'] ?? 10);
        $auto = (int)($_POST['auto_status'] ?? 1);
        $notify = (int)($_POST['notify_low_stock'] ?? 1);
        $keys = ['reorder_level', 'low_stock_threshold', 'auto_status', 'notify_low_stock'];
        $vals = [$reorder, $threshold, $auto, $notify];
        for ($i = 0; $i < count($keys); $i++) {
            $stmt = $staff_conn->prepare("INSERT INTO sickbay_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
            if ($stmt) { $stmt->bind_param('si', $keys[$i], $vals[$i]); $stmt->execute(); $stmt->close(); }
        }
        $_SESSION['success'] = 'Settings saved.';
        header('Location: sickbay.php?section=settings'); exit;
    }

    if ($action === 'add_visit') {
        $sid = (int)($_POST['student_id'] ?? 0);
        $sname = trim($_POST['student_name']);
        $vdate = trim($_POST['visit_date'] ?: date('Y-m-d'));
        $symptoms = trim($_POST['symptoms']);
        $diagnosis = trim($_POST['diagnosis']);
        $treatment = trim($_POST['treatment']);
        $medication = trim($_POST['medication_given']);
        $status = trim($_POST['status'] ?? 'Pending');
        $fud = !empty($_POST['follow_up_date']) ? trim($_POST['follow_up_date']) : null;
        $notes = trim($_POST['notes']);
        $stmt = $staff_conn->prepare("INSERT INTO sickbay_visits (student_id, student_name, visit_date, symptoms, diagnosis, treatment, medication_given, nurse_id, nurse_name, status, follow_up_date, notes) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
        if ($stmt) { $stmt->bind_param('isssssssssss', $sid, $sname, $vdate, $symptoms, $diagnosis, $treatment, $medication, $user_id, $user_name, $status, $fud, $notes); $stmt->execute(); $stmt->close(); }
        $_SESSION['success'] = 'Sickbay visit recorded.';
        header('Location: sickbay.php?section=visits'); exit;
    }

    if ($action === 'update_visit') {
        $id = (int)($_POST['id'] ?? 0);
        $status = trim($_POST['status']);
        $treatment = trim($_POST['treatment']);
        $medication = trim($_POST['medication_given']);
        $diagnosis = trim($_POST['diagnosis']);
        $notes = trim($_POST['notes']);
        $fud = !empty($_POST['follow_up_date']) ? trim($_POST['follow_up_date']) : null;
        if ($id > 0) {
            $stmt = $staff_conn->prepare("UPDATE sickbay_visits SET status=?, treatment=?, medication_given=?, diagnosis=?, notes=?, follow_up_date=? WHERE id=?");
            if ($stmt) { $stmt->bind_param('ssssssi', $status, $treatment, $medication, $diagnosis, $notes, $fud, $id); $stmt->execute(); $stmt->close(); }
            $_SESSION['success'] = 'Visit updated.';
        }
        header('Location: sickbay.php?section=visits'); exit;
    }

    if ($action === 'delete_visit') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $staff_conn->prepare("DELETE FROM sickbay_visits WHERE id=?");
            if ($stmt) { $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close(); }
            $_SESSION['success'] = 'Visit deleted.';
        }
        header('Location: sickbay.php?section=visits'); exit;
    }

    if ($action === 'add_medicine') {
        $id = (int)($_POST['id'] ?? 0);
        $mname = trim($_POST['medicine_name']);
        $cat = trim($_POST['category']);
        $qty = (int)($_POST['quantity'] ?? 0);
        $unit = trim($_POST['unit']);
        $exp = !empty($_POST['expiry_date']) ? trim($_POST['expiry_date']) : null;
        $rol = (int)($_POST['reorder_level'] ?? 10);
        $ns = $qty <= 0 ? 'Out of Stock' : ($qty <= $rol ? 'Low Stock' : 'In Stock');
        if ($id > 0) {
            $stmt = $staff_conn->prepare("UPDATE sickbay_medicine_stock SET medicine_name=?, category=?, quantity=?, unit=?, expiry_date=?, reorder_level=?, status=? WHERE id=?");
            if ($stmt) { $stmt->bind_param('ssisssii', $mname, $cat, $qty, $unit, $exp, $rol, $ns, $id); $stmt->execute(); $stmt->close(); }
            $_SESSION['success'] = 'Medicine updated.';
        } else {
            $stmt = $staff_conn->prepare("INSERT INTO sickbay_medicine_stock (medicine_name, category, quantity, unit, expiry_date, reorder_level, status) VALUES (?,?,?,?,?,?,?)");
            if ($stmt) { $stmt->bind_param('ssisssi', $mname, $cat, $qty, $unit, $exp, $rol, $ns); $stmt->execute(); $stmt->close(); }
            $_SESSION['success'] = 'Medicine added.';
        }
        header('Location: sickbay.php?section=visits'); exit;
    }

    if ($action === 'update_medicine') {
        $id = (int)($_POST['id'] ?? 0);
        $mname = trim($_POST['medicine_name']);
        $cat = trim($_POST['category']);
        $qty = (int)($_POST['quantity'] ?? 0);
        $unit = trim($_POST['unit']);
        $exp = !empty($_POST['expiry_date']) ? trim($_POST['expiry_date']) : null;
        $rol = (int)($_POST['reorder_level'] ?? 10);
        $ns = $qty <= 0 ? 'Out of Stock' : ($qty <= $rol ? 'Low Stock' : 'In Stock');
        if ($id > 0) {
            $stmt = $staff_conn->prepare("UPDATE sickbay_medicine_stock SET medicine_name=?, category=?, quantity=?, unit=?, expiry_date=?, reorder_level=?, status=? WHERE id=?");
            if ($stmt) { $stmt->bind_param('ssisssii', $mname, $cat, $qty, $unit, $exp, $rol, $ns, $id); $stmt->execute(); $stmt->close(); }
            $_SESSION['success'] = 'Medicine updated.';
        }
        header('Location: sickbay.php?section=visits'); exit;
    }

    if ($action === 'delete_sb_medicine') {
        $mid = (int)($_POST['id'] ?? 0);
        if ($mid > 0) {
            $stmt = $staff_conn->prepare("DELETE FROM sickbay_medicine_stock WHERE id=?");
            if ($stmt) { $stmt->bind_param('i', $mid); $stmt->execute(); $stmt->close(); }
            $_SESSION['success'] = 'Medicine deleted.';
        }
        header('Location: sickbay.php?section=visits'); exit;
    }

    if ($action === 'dispense_medicine') {
        $mid = (int)($_POST['medicine_id'] ?? 0);
        $qty = (int)($_POST['quantity'] ?? 0);
        $vid = !empty($_POST['visit_id']) ? (int)$_POST['visit_id'] : 0;
        $notes = trim($_POST['notes']);
        if ($mid > 0 && $qty > 0) {
            $stmt = $staff_conn->prepare("SELECT quantity, reorder_level FROM sickbay_medicine_stock WHERE id=?");
            if ($stmt) { $stmt->bind_param('i', $mid); $stmt->execute(); $qr = $stmt->get_result(); $stmt->close(); } else $qr = null;
            $med = $qr ? $qr->fetch_assoc() : null;
            if ($med) {
                $cq = (int)$med['quantity'];
                $nq = max(0, $cq - $qty);
                $rl = (int)$med['reorder_level'];
                $ns = $nq <= 0 ? 'Out of Stock' : ($nq <= $rl ? 'Low Stock' : 'In Stock');
                $stmt = $staff_conn->prepare("UPDATE sickbay_medicine_stock SET quantity=?, status=? WHERE id=?");
                if ($stmt) { $stmt->bind_param('isi', $nq, $ns, $mid); $stmt->execute(); $stmt->close(); }
                $stmt = $staff_conn->prepare("INSERT INTO sickbay_medicine_transactions (medicine_id, transaction_type, quantity, visit_id, performed_by, notes) VALUES (?, 'Dispense', ?, ?, ?, ?)");
                if ($stmt) { $stmt->bind_param('iiiis', $mid, $qty, $vid, $user_id, $notes); $stmt->execute(); $stmt->close(); }
                $_SESSION['success'] = "Dispensed $qty. New stock: $nq";
            }
        }
        header('Location: sickbay.php?section=visits'); exit;
    }

    if ($action === 'add_medicine_transaction') {
        $mid = (int)($_POST['medicine_id'] ?? 0);
        $ttype = trim($_POST['transaction_type']);
        $qty = (int)($_POST['quantity'] ?? 0);
        $vid = !empty($_POST['visit_id']) ? (int)$_POST['visit_id'] : 0;
        $notes = trim($_POST['notes']);
        if ($mid > 0 && $qty > 0) {
            $stmt = $staff_conn->prepare("INSERT INTO sickbay_medicine_transactions (medicine_id, transaction_type, quantity, visit_id, performed_by, notes) VALUES (?,?,?,?,?,?)");
            if ($stmt) { $stmt->bind_param('isiiis', $mid, $ttype, $qty, $vid, $user_id, $notes); $stmt->execute(); $stmt->close(); }
            $_SESSION['success'] = 'Transaction recorded.';
        }
        header('Location: sickbay.php?section=visits'); exit;
    }
}

$total_students_db = sb_q($students_conn, "SELECT COUNT(*) FROM students WHERE status = 'Active'");
$total_sick_visits = sb_q($staff_conn, "SELECT COUNT(*) FROM daily_sick_records WHERE (is_deleted = 0 OR is_deleted IS NULL)");
$today_visits = sb_q($staff_conn, "SELECT COUNT(*) FROM daily_sick_records WHERE visit_date = CURDATE() AND (is_deleted = 0 OR is_deleted IS NULL)");
$pending_leave = sb_q($staff_conn, "SELECT COUNT(*) FROM student_sick_leave WHERE status = 'Pending' AND (is_deleted = 0 OR is_deleted IS NULL)");
$active_leave = sb_q($staff_conn, "SELECT COUNT(*) FROM student_sick_leave WHERE status IN ('Approved','Extended') AND leave_to >= CURDATE() AND (is_deleted = 0 OR is_deleted IS NULL)");
$critical_cases = sb_q($staff_conn, "SELECT COUNT(*) FROM daily_sick_records WHERE severity = 'Critical' AND visit_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND (is_deleted = 0 OR is_deleted IS NULL)");
$recent_records = sb_fetch($staff_conn, "SELECT dsr.*, s.full_name as student_full_name FROM daily_sick_records dsr LEFT JOIN igangaschoolofl_students_db.students s ON dsr.student_id = s.id WHERE (dsr.is_deleted = 0 OR dsr.is_deleted IS NULL) ORDER BY dsr.created_at DESC LIMIT 10");
$low_stock_meds = sb_fetch($staff_conn, "SELECT COUNT(*) as cnt FROM sickbay_medicine_stock WHERE status IN ('Low Stock','Out of Stock')");
$low_stock_count = !empty($low_stock_meds) ? (int)$low_stock_meds[0]['cnt'] : 0;
$expiring_meds = sb_fetch($staff_conn, "SELECT COUNT(*) as cnt FROM sickbay_medicine_stock WHERE expiry_date <= DATE_ADD(CURDATE(), INTERVAL 3 MONTH) AND expiry_date >= CURDATE()");
$expiring_count = !empty($expiring_meds) ? (int)$expiring_meds[0]['cnt'] : 0;
$sicknesses = sb_fetch($staff_conn, "SELECT * FROM sickness_directory ORDER BY sickness_name ASC");
$sickness_list = sb_fetch($staff_conn, "SELECT * FROM sickness_directory WHERE status='Active' ORDER BY sickness_name ASC");
$daily_records = sb_fetch($staff_conn, "SELECT dsr.*, s.full_name as student_full_name FROM daily_sick_records dsr LEFT JOIN igangaschoolofl_students_db.students s ON dsr.student_id = s.id WHERE (dsr.is_deleted = 0 OR dsr.is_deleted IS NULL) ORDER BY dsr.visit_date DESC, dsr.visit_time DESC LIMIT 200");
$leave_records = sb_fetch($staff_conn, "SELECT sl.*, s.full_name as student_full_name FROM student_sick_leave sl LEFT JOIN igangaschoolofl_students_db.students s ON sl.student_id = s.id WHERE (sl.is_deleted = 0 OR sl.is_deleted IS NULL) ORDER BY sl.created_at DESC LIMIT 200");
$medicines = sb_fetch($staff_conn, "SELECT * FROM sickbay_medicine_stock ORDER BY medicine_name ASC");
$medicine_transactions = sb_fetch($staff_conn, "SELECT smt.*, sms.medicine_name FROM sickbay_medicine_transactions smt LEFT JOIN sickbay_medicine_stock sms ON smt.medicine_id = sms.id ORDER BY smt.created_at DESC LIMIT 50");
$sickbay_visits = sb_fetch($staff_conn, "SELECT * FROM sickbay_visits ORDER BY visit_date DESC, id DESC LIMIT 200");
$sickbay_medicine_stock = sb_fetch($staff_conn, "SELECT * FROM sickbay_medicine_stock ORDER BY medicine_name ASC");
$sickbay_medicine_transactions = sb_fetch($staff_conn, "SELECT smt.*, sms.medicine_name FROM sickbay_medicine_transactions smt LEFT JOIN sickbay_medicine_stock sms ON smt.medicine_id = sms.id ORDER BY smt.id DESC LIMIT 100");
$sb_settings_rows = sb_fetch($staff_conn, "SELECT setting_key, setting_value FROM sickbay_settings");
$sb_settings = [];
foreach ($sb_settings_rows as $row) { $sb_settings[$row['setting_key']] = $row['setting_value']; }

$health_records_list = []; $health_incidents_list = [];
if ($staff_conn) {
    $health_records_list = sb_fetch($staff_conn, "SELECT shr.*, s.full_name, s.student_number, s.program FROM student_health_records shr LEFT JOIN igangaschoolofl_students_db.students s ON shr.student_id = s.id ORDER BY s.full_name ASC LIMIT 200");
    $health_incidents_list = sb_fetch($staff_conn, "SELECT hi.*, s.full_name, s.student_number, s.program FROM health_incidents hi LEFT JOIN igangaschoolofl_students_db.students s ON hi.student_id = s.id ORDER BY hi.created_at DESC LIMIT 200");
    $student_incidents_list = sb_fetch($staff_conn, "SELECT hi.*, s.full_name, s.student_number, s.program FROM student_health_incidents hi LEFT JOIN igangaschoolofl_students_db.students s ON hi.student_id = s.id ORDER BY hi.created_at DESC LIMIT 200");
    $emergency_contacts_list = sb_fetch($staff_conn, "SELECT * FROM emergency_contacts WHERE is_active = 1 ORDER BY priority ASC");
    $student_contacts_list = sb_fetch($staff_conn, "SELECT sec.*, s.full_name FROM student_emergency_contacts sec LEFT JOIN igangaschoolofl_students_db.students s ON sec.student_id = s.id ORDER BY s.full_name LIMIT 200");
}
$recent_activities = [];
if ($staff_conn) {
    try {
        $result = $staff_conn->query("SELECT activity_description as activity, created_at FROM staff_activity_log ORDER BY created_at DESC LIMIT 10");
        if ($result) { while ($row = $result->fetch_assoc()) { $recent_activities[] = $row; } }
    } catch (Exception $e) {}
}

$staff_on_duty = sb_q($staff_conn, "SELECT COUNT(*) FROM staff s LEFT JOIN staff_roles sr ON s.role_id=sr.id WHERE (s.department LIKE '%Sickbay%' OR s.department LIKE '%Health%' OR sr.role_name LIKE '%Sickbay%') AND s.status = 'Active'");
if ($staff_on_duty < 1) $staff_on_duty = 1;

$student_search_results = [];
$search_query = trim($_GET['q'] ?? '');
if ($search_query && $students_conn) {
    $like = "%$search_query%";
    $stmt = $students_conn->prepare("SELECT id, full_name, student_id, student_number, program, phone FROM students WHERE full_name LIKE ? OR student_id LIKE ? OR student_number LIKE ? OR phone LIKE ? LIMIT 20");
    if ($stmt) { $stmt->bind_param('ssss', $like, $like, $like, $like); $r = $stmt->execute() ? $stmt->get_result() : null; $stmt->close(); }
    else $r = null;
    $student_search_results = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
}

$pageTitle = 'Sickbay Management System';?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
.sickbay-section { display: none; }
.sickbay-section.active { display: block; }
.health-card { background: #fff; border-radius: 16px; padding: 20px; margin-bottom: 20px; border: 1px solid rgba(148,163,184,0.16); box-shadow: 0 1px 2px rgba(15,23,42,0.03), 0 4px 12px rgba(15,23,42,0.04); }
.health-card h2 { font-size: 1rem; font-weight: 600; margin-bottom: 16px; color: #0f172a; display: flex; align-items: center; gap: 8px; }
.stat-card { background: #fff; border-radius: 16px; padding: 18px; border: 1px solid rgba(148,163,184,0.16); box-shadow: 0 1px 2px rgba(15,23,42,0.03), 0 4px 12px rgba(15,23,42,0.04); display: flex; align-items: center; gap: 14px; transition: all 0.25s ease; }
.stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,0,0,0.08); }
.si { width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
.si-blue { background: #eef2ff; color: #2563eb; }
.si-green { background: #ecfdf5; color: #059669; }
.si-orange { background: #fff7ed; color: #d97706; }
.si-red { background: #fef2f2; color: #dc2626; }
.si-purple { background: #f5f3ff; color: #7c3aed; }
.si-teal { background: #f0fdfa; color: #0d9488; }
.stat-content h3 { font-size: 1.5rem; font-weight: 700; margin: 0; color: #0f172a; line-height: 1.2; }
.stat-content p { font-size: 0.75rem; color: #64748b; margin: 0; text-transform: uppercase; letter-spacing: 0.5px; }
.section-card { background: #fff; border-radius: 16px; padding: 20px; margin-bottom: 20px; border: 1px solid rgba(148,163,184,0.16); }
.form-control, .form-select { border-radius: 10px; border: 1.5px solid #e2e8f0; padding: 8px 14px; font-size: 0.875rem; }
.form-control:focus, .form-select:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
.btn { border-radius: 10px; font-weight: 500; padding: 8px 18px; }
.table th { font-weight: 600; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; }
.table td { vertical-align: middle; }
.stock-critical { background: #fee2e2 !important; }
.stock-warning { background: #fef3c7 !important; }
.stock-ok { background: #d1fae5 !important; }
.top-bar { background: #fff; border-radius: 16px; padding: 16px 20px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; border: 1px solid rgba(148,163,184,0.16); }
@media print { .no-print { display: none !important; } }
</style>
</head>
<body class="ent-layout">
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>
<div class="page-content">
<div class="top-bar"><div><strong><i class="fas fa-hospital-user me-2 text-danger"></i>Sickbay Management System</strong><div class="text-muted small">Iganga School of Nursing &amp; Midwifery</div></div><div class="d-flex align-items-center gap-3"><span class="text-muted small d-none d-md-block"><?=date('D, d M Y')?></span><a href="inventory-reports.php" class="btn btn-sm btn-outline-info no-print" title="Reports"><i class="fas fa-chart-bar me-1"></i></a><button class="btn btn-sm btn-outline-success no-print" onclick="window.print()"><i class="fas fa-print me-1"></i></button><a href="../auth-handler.php?action=logout" class="btn btn-sm btn-outline-danger no-print"><i class="fas fa-sign-out-alt me-1"></i>Logout</a></div></div>
<div class="content-area">
<?php if(!empty($_SESSION['success'])):?><div class="alert alert-success alert-dismissible fade show py-2"><?=htmlspecialchars($_SESSION['success'])?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php unset($_SESSION['success']);endif;?>
<?php if(!empty($_SESSION['error'])):?><div class="alert alert-danger alert-dismissible fade show py-2"><?=htmlspecialchars($_SESSION['error'])?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php unset($_SESSION['error']);endif;?>
<div class="section-tabs mb-4 no-print">
<a class="section-tab <?=$active_section==='dashboard'?'active':''?>" href="sickbay.php?section=dashboard"><i class="fas fa-tachometer-alt me-1"></i>Dashboard</a>
<a class="section-tab <?=$active_section==='daily-records'?'active':''?>" href="sickbay.php?section=daily-records"><i class="fas fa-notes-medical me-1"></i>Daily Sick Records</a>
<a class="section-tab <?=$active_section==='sickness'?'active':''?>" href="sickbay.php?section=sickness"><i class="fas fa-disease me-1"></i>Sickness Directory</a>
<a class="section-tab <?=$active_section==='leave'?'active':''?>" href="sickbay.php?section=leave"><i class="fas fa-file-medical me-1"></i>Leave Sheet</a>
<a class="section-tab <?=$active_section==='medicine'?'active':''?>" href="sickbay.php?section=medicine"><i class="fas fa-capsules me-1"></i>Medicine Stock</a>
<a class="section-tab <?=$active_section==='recycle-bin'?'active':''?>" href="sickbay.php?section=recycle-bin"><i class="fas fa-trash-restore me-1"></i>Recycle Bin</a>
<a class="section-tab <?=$active_section==='audit'?'active':''?>" href="sickbay.php?section=audit"><i class="fas fa-history me-1"></i>Audit Trail</a>
<a class="section-tab <?=$active_section==='settings'?'active':''?>" href="sickbay.php?section=settings"><i class="fas fa-cog me-1"></i>Settings</a>
<a class="section-tab <?=$active_section==='health-records'?'active':''?>" href="sickbay.php?section=health-records"><i class="fas fa-notes-medical me-1"></i>Health Records</a>
<a class="section-tab <?=$active_section==='health-incidents'?'active':''?>" href="sickbay.php?section=health-incidents"><i class="fas fa-exclamation-triangle me-1"></i>Health Incidents</a>
<a class="section-tab <?=$active_section==='visits'?'active':''?>" href="sickbay.php?section=visits"><i class="fas fa-clipboard-list me-1"></i>Sickbay Visits</a>
</div><!-- DASHBOARD -->
<div class="sickbay-section <?=$active_section==='dashboard'?'active':''?>" id="sec-dashboard">
<div class="row g-3 mb-4">
<div class="col-6 col-md-4 col-lg-2"><div class="stat-card"><div class="si si-blue"><i class="fas fa-user-graduate"></i></div><div class="stat-content"><h3><?=number_format($total_students_db)?></h3><p>Active Students</p></div></div></div>
<div class="col-6 col-md-4 col-lg-2"><div class="stat-card"><div class="si si-green"><i class="fas fa-calendar-check"></i></div><div class="stat-content"><h3><?=$today_visits?></h3><p>Today's Visits</p></div></div></div>
<div class="col-6 col-md-4 col-lg-2"><div class="stat-card"><div class="si si-orange"><i class="fas fa-clock"></i></div><div class="stat-content"><h3><?=$pending_leave?></h3><p>Pending Leave</p></div></div></div>
<div class="col-6 col-md-4 col-lg-2"><div class="stat-card"><div class="si si-red"><i class="fas fa-exclamation-triangle"></i></div><div class="stat-content"><h3><?=$critical_cases?></h3><p>Critical (7d)</p></div></div></div>
<div class="col-6 col-md-4 col-lg-2"><div class="stat-card"><div class="si si-purple"><i class="fas fa-boxes"></i></div><div class="stat-content"><h3><?=$low_stock_count?></h3><p>Low Stock Items</p></div></div></div>
<div class="col-6 col-md-4 col-lg-2"><div class="stat-card"><div class="si si-teal"><i class="fas fa-user-md"></i></div><div class="stat-content"><h3><?=$staff_on_duty?></h3><p>Staff on Duty</p></div></div></div>
</div>
<div class="row g-4">
<div class="col-lg-8">
<div class="section-card"><h5 class="fw-bold mb-3"><i class="fas fa-search me-2"></i>Student Search</h5><form method="GET" action="sickbay.php" class="mb-3"><input type="hidden" name="section" value="dashboard"><div class="input-group"><input type="text" name="q" class="form-control" placeholder="Search by name, ID, or phone..." value="<?=htmlspecialchars($search_query)?>"><button class="btn btn-primary" type="submit"><i class="fas fa-search me-1"></i>Search</button></div></form>
<?php if($search_query):?><div><?php if(empty($student_search_results)):?><p class="text-muted small">No students found.</p><?php else:?><p class="text-muted small">Found <?=count($student_search_results)?>:</p><?php foreach($student_search_results as $s):?><div class="d-flex justify-content-between align-items-center border-bottom py-2"><div><strong><?=htmlspecialchars($s['full_name'])?></strong><small class="d-block text-muted"><?=htmlspecialchars($s['student_id']??$s['student_number']??'')?> | <?=htmlspecialchars($s['program']??'')?></small></div><div class="d-flex gap-1"><a href="sickbay.php?section=daily-records&sid=<?=$s['id']?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-notes-medical"></i></a><a href="sickbay.php?section=leave&sid=<?=$s['id']?>" class="btn btn-sm btn-outline-warning"><i class="fas fa-file-medical"></i></a></div></div><?php endforeach;?></div><?php endif;?></div><?php endif;?>
</div>
<div class="health-card"><div class="d-flex justify-content-between align-items-center mb-3"><h2 class="mb-0"><i class="fas fa-notes-medical me-2 text-danger"></i>Recent Sick Records</h2><span class="badge bg-secondary"><?=$total_sick_visits?> Total</span></div>
<?php if(empty($recent_records)):?><div class="text-center py-4 text-muted"><i class="fas fa-notes-medical fa-3x mb-3"></i><p>No sick records yet.</p></div>
<?php else:?><div class="table-responsive"><table class="table table-sm table-hover align-middle"><thead class="table-light"><tr><th>Student</th><th>Diagnosis</th><th>Severity</th><th>Status</th><th>Date</th></tr></thead><tbody>
<?php foreach($recent_records as $r):
    switch($r['severity']??''){case'Mild':$sc='bg-success';break;case'Moderate':$sc='bg-warning text-dark';break;case'Severe':$sc='bg-orange';break;case'Critical':$sc='bg-danger';break;default:$sc='bg-secondary';}
    switch($r['status']??''){case'Treated':case'Discharged':$st='bg-success';break;case'Referred':$st='bg-info text-dark';break;case'Admitted':$st='bg-warning text-dark';break;case'Critical':$st='bg-danger';break;case'Follow-up':$st='bg-primary';break;default:$st='bg-secondary';}
?>
<tr><td><strong><?=htmlspecialchars($r['student_name']??$r['student_full_name']??'Unknown')?></strong><small class="d-block text-muted"><?=htmlspecialchars($r['student_number']??'')?></small></td><td><?=htmlspecialchars(substr($r['diagnosis']??$r['sickness_name']??'N/A',0,60))?></td><td><span class="badge <?=$sc?>"><?=htmlspecialchars($r['severity']??'N/A')?></span></td><td><span class="badge <?=$st?>"><?=htmlspecialchars($r['status']??'N/A')?></span></td><td><small class="text-muted"><?=date('d M Y',strtotime($r['visit_date']))?></small></td></tr>
<?php endforeach;?></tbody></table></div><?php endif;?>
</div>
</div>
<div class="col-lg-4">
<div class="health-card"><h2><i class="fas fa-bolt me-2"></i>Quick Actions</h2><div class="d-flex flex-wrap gap-2"><a href="sickbay.php?section=daily-records" class="btn btn-primary btn-sm"><i class="fas fa-plus-circle me-1"></i>New Sick Record</a><a href="sickbay.php?section=leave" class="btn btn-warning btn-sm"><i class="fas fa-file-medical me-1"></i>Issue Sick Leave</a><a href="sickbay.php?section=sickness" class="btn btn-info btn-sm"><i class="fas fa-disease me-1"></i>Sicknesses</a><a href="sickbay.php?section=medicine" class="btn btn-success btn-sm"><i class="fas fa-capsules me-1"></i>Medicine Stock</a><button class="btn btn-outline-success btn-sm no-print" onclick="window.print()"><i class="fas fa-print me-1"></i>Print</button></div></div>
<div class="health-card"><h2><i class="fas fa-capsules me-2 text-warning"></i>Stock Alerts</h2>
<?php $alert_meds=sb_fetch($staff_conn,"SELECT medicine_name,quantity,status,expiry_date FROM sickbay_medicine_stock WHERE status IN('Low Stock','Out of Stock') OR (expiry_date<=DATE_ADD(CURDATE(),INTERVAL 3 MONTH) AND expiry_date>=CURDATE()) ORDER BY expiry_date ASC LIMIT 8");?>
<?php if(empty($alert_meds)):?><p class="text-muted small py-2"><i class="fas fa-check-circle text-success me-1"></i>All adequately stocked.</p>
<?php else:?><div class="table-responsive"><table class="table table-sm table-borderless mb-0"><thead><tr><th>Medicine</th><th>Qty</th><th>Alert</th></tr></thead><tbody>
<?php foreach($alert_meds as $m):$at=$m['status']==='Out of Stock'?'danger':($m['status']==='Low Stock'?'warning':'info');$txt=$m['status']==='Out of Stock'?'Out':($m['status']==='Low Stock'?'Low':'Expiring');?>
<tr class="<?=$at==='danger'?'stock-critical':($at==='warning'?'stock-warning':'')?>"><td><small><?=htmlspecialchars($m['medicine_name'])?></small></td><td><small><?=(int)$m['quantity']?></small></td><td><span class="badge bg-<?=$at?>"><?=$txt?></span></td></tr>
<?php endforeach;?></tbody></table></div><div class="text-center mt-2"><a href="sickbay.php?section=medicine" class="btn btn-sm btn-outline-warning"><i class="fas fa-warehouse me-1"></i>Manage Stock</a></div><?php endif;?>
</div>
<div class="health-card"><h2><i class="fas fa-history me-2"></i>Recent Activity</h2><?php if(empty($recent_activities)):?><p class="text-muted small">No recent activities.</p><?php else:?><ul class="list-unstyled mb-0"><?php foreach($recent_activities as $act):?><li class="border-bottom py-2 d-flex gap-3 align-items-start"><span class="badge bg-primary mt-1">Activity</span><div><div class="small"><?=htmlspecialchars($act['activity']??$act['activity_description']??'')?></div><small class="text-muted"><?=isset($act['created_at'])?date('d M H:i',strtotime($act['created_at'])):''?></small></div></li><?php endforeach;?></ul><?php endif;?></div>

</div></div></div><!-- DAILY SICK RECORDS -->
<div class="sickbay-section <?=$active_section==='daily-records'?'active':''?>" id="sec-daily-records">
<div class="row g-4">
<div class="col-lg-5">
<div class="health-card"><h2><i class="fas fa-plus-circle me-2 text-danger"></i>Record Daily Sick Visit</h2>
<form method="POST" action="sickbay.php"><input type="hidden" name="action" value="save_sick_record">
<div class="mb-2"><label class="form-label fw-semibold">Student <span class="text-danger">*</span></label><input type="text" name="student_name" class="form-control" required placeholder="Full name" value="<?=htmlspecialchars($_GET['sname']??'')?>" id="sr-name"><input type="hidden" name="student_id" id="sr-sid" value="<?=(int)($_GET['sid']??0)?>"><input type="hidden" name="student_number" id="sr-num"></div>
<div class="row g-2 mb-2"><div class="col-6"><label class="form-label fw-semibold">Program</label><input type="text" name="program" class="form-control" id="sr-prog"></div><div class="col-3"><label class="form-label fw-semibold">Year</label><input type="number" name="year_of_study" class="form-control" id="sr-year" min="1" max="6"></div><div class="col-3"><label class="form-label fw-semibold">Date</label><input type="date" name="visit_date" class="form-control" required value="<?=date('Y-m-d')?>"></div></div>
<div class="row g-2 mb-2"><div class="col-6"><label class="form-label fw-semibold">Temperature (C)</label><input type="text" name="temperature" class="form-control" placeholder="37.5"></div><div class="col-6"><label class="form-label fw-semibold">Blood Pressure</label><input type="text" name="blood_pressure" class="form-control" placeholder="120/80"></div></div>
<div class="mb-2"><label class="form-label fw-semibold">Sickness</label><select name="sickness_id" class="form-select" onchange="document.getElementById('sr-sn').value=this.options[this.selectedIndex]?.text||''"><option value="">-- Select --</option><?php foreach($sickness_list as $sk):?><option value="<?=$sk['id']?>"><?=htmlspecialchars($sk['sickness_name'])?></option><?php endforeach;?></select><input type="hidden" name="sickness_name" id="sr-sn"><input type="text" class="form-control mt-1" placeholder="Or type manually" oninput="document.getElementById('sr-sn').value=this.value"></div>
<div class="mb-2"><label class="form-label fw-semibold">Symptoms</label><textarea name="symptoms" class="form-control" rows="2"></textarea></div>
<div class="mb-2"><label class="form-label fw-semibold">Diagnosis</label><textarea name="diagnosis" class="form-control" rows="2"></textarea></div>
<div class="mb-2"><label class="form-label fw-semibold">Treatment Given</label><textarea name="treatment_given" class="form-control" rows="2"></textarea></div>
<div class="mb-2"><label class="form-label fw-semibold">Medicines Prescribed</label><textarea name="medicines_prescribed" class="form-control" rows="2"></textarea></div>
<div class="row g-2 mb-2"><div class="col-4"><label class="form-label fw-semibold">Severity</label><select name="severity" class="form-select"><option value="Mild">Mild</option><option value="Moderate">Moderate</option><option value="Severe">Severe</option><option value="Critical">Critical</option></select></div><div class="col-4"><label class="form-label fw-semibold">Status</label><select name="status" class="form-select"><option value="Treated">Treated</option><option value="Referred">Referred</option><option value="Admitted">Admitted</option><option value="Discharged">Discharged</option><option value="Follow-up">Follow-up</option><option value="Critical">Critical</option></select></div><div class="col-4"><label class="form-label fw-semibold">Time</label><input type="time" name="visit_time" class="form-control" value="<?=date('H:i')?>"></div></div>
<div class="mb-2"><label class="form-label fw-semibold">Referred To</label><input type="text" name="referred_to" class="form-control"></div>
<div class="mb-2"><label class="form-label fw-semibold">Follow-up Date</label><input type="date" name="follow_up_date" class="form-control"></div>
<div class="mb-3"><label class="form-label fw-semibold">Notes</label><textarea name="notes" class="form-control" rows="1"></textarea></div>
<button type="submit" class="btn btn-danger w-100"><i class="fas fa-save me-1"></i>Save Sick Record</button>
</form></div></div>
<div class="col-lg-7">
<div class="health-card"><h2><i class="fas fa-list me-2 text-danger"></i>Daily Sick Records</h2>
<div class="mb-2 d-flex gap-2 flex-wrap"><input type="text" class="form-control form-control-sm" style="width:180px" placeholder="Filter by name..." onkeyup="filterTable('sr-tbl',1,this.value)"><input type="date" class="form-control form-control-sm" style="width:160px" onchange="filterTableByDate('sr-tbl',5,this.value)"><select class="form-select form-select-sm" style="width:140px" onchange="filterTable('sr-tbl',3,this.value)"><option value="">All</option><option value="Mild">Mild</option><option value="Moderate">Moderate</option><option value="Severe">Severe</option><option value="Critical">Critical</option></select></div>
<div class="table-responsive" style="max-height:600px;overflow-y:auto"><table class="table table-sm table-hover align-middle" id="sr-tbl"><thead class="table-light sticky-top"><tr><th>#</th><th>Student</th><th>Sickness</th><th>Severity</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead><tbody>
<?php if(empty($daily_records)):?><tr><td colspan="7" class="text-center text-muted py-4">No sick records found.</td></tr>
<?php else:$i=1;foreach($daily_records as $r):
    switch($r['severity']){case'Mild':$dsc='bg-success';break;case'Moderate':$dsc='bg-warning text-dark';break;case'Severe':$dsc='bg-orange';break;case'Critical':$dsc='bg-danger';break;default:$dsc='bg-secondary';}
    switch($r['status']){case'Treated':case'Discharged':$dst='bg-success';break;case'Referred':$dst='bg-info text-dark';break;case'Admitted':$dst='bg-warning text-dark';break;case'Critical':$dst='bg-danger';break;case'Follow-up':$dst='bg-primary';break;default:$dst='bg-secondary';}
?>
<tr><td><small><?=$i++?></small></td><td><strong><small><?=htmlspecialchars($r['student_name']??$r['student_full_name']??'Unknown')?></small></strong><small class="d-block text-muted"><?=htmlspecialchars($r['student_number']??'')?></small></td><td><small><?=htmlspecialchars($r['sickness_name']??substr($r['diagnosis']??'N/A',0,40))?></small></td><td><span class="badge <?=$dsc?>"><?=htmlspecialchars($r['severity']??'N/A')?></span></td><td><span class="badge <?=$dst?>"><?=htmlspecialchars($r['status']??'N/A')?></span></td><td><small><?=date('d M Y',strtotime($r['visit_date']))?></small></td><td><form method="POST" class="d-inline" onsubmit="return confirm('Delete?')"><input type="hidden" name="action" value="delete_sick_record"><input type="hidden" name="record_id" value="<?=$r['id']?>"><button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash-alt"></i></button></form></td></tr>
<?php endforeach;endif;?></tbody></table></div></div></div></div></div><!-- SICKNESS DIRECTORY -->
<div class="sickbay-section <?=$active_section==='sickness'?'active':''?>" id="sec-sickness">
<div class="row g-4">
<div class="col-lg-5">
<div class="health-card"><h2><i class="fas fa-plus-circle me-2 text-success"></i>Add / Edit Sickness</h2>
<form method="POST" action="sickbay.php"><input type="hidden" name="action" value="save_sickness"><input type="hidden" name="id" id="ed-sk-id" value="0">
<div class="row g-2 mb-2"><div class="col-4"><label class="form-label fw-semibold">Code <span class="text-danger">*</span></label><input type="text" name="sickness_code" class="form-control" required placeholder="MLR" id="ed-sk-code"></div><div class="col-8"><label class="form-label fw-semibold">Name <span class="text-danger">*</span></label><input type="text" name="sickness_name" class="form-control" required placeholder="Malaria" id="ed-sk-name"></div></div>
<div class="mb-2"><label class="form-label fw-semibold">Category</label><select name="category" class="form-select" id="ed-sk-cat"><option value="Infectious">Infectious</option><option value="Non-Infectious">Non-Infectious</option><option value="Chronic">Chronic</option><option value="Injury">Injury</option><option value="Mental Health">Mental Health</option><option value="Nutritional">Nutritional</option><option value="Other">Other</option></select></div>
<div class="mb-2"><label class="form-label fw-semibold">Common Symptoms</label><textarea name="common_symptoms" class="form-control" rows="2" id="ed-sk-symp"></textarea></div>
<div class="mb-2"><label class="form-label fw-semibold">Description</label><textarea name="description" class="form-control" rows="2" id="ed-sk-desc"></textarea></div>
<div class="mb-2"><label class="form-label fw-semibold">Typical Treatment</label><textarea name="typical_treatment" class="form-control" rows="2" id="ed-sk-treat"></textarea></div>
<div class="mb-2 form-check"><input type="checkbox" name="is_contagious" class="form-check-input" id="ed-sk-cont"><label class="form-check-label" for="ed-sk-cont">Contagious</label></div>
<div class="mb-3"><label class="form-label fw-semibold">Status</label><select name="status" class="form-select" id="ed-sk-stat"><option value="Active">Active</option><option value="Inactive">Inactive</option></select></div>
<div class="d-flex gap-2"><button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Save</button><button type="button" class="btn btn-secondary" onclick="resetSickness()"><i class="fas fa-undo me-1"></i>Reset</button></div>
</form></div></div>
<div class="col-lg-7">
<div class="health-card"><h2><i class="fas fa-list me-2 text-success"></i>Sickness Directory</h2>
<div class="mb-2"><input type="text" class="form-control form-control-sm" style="width:280px" placeholder="Filter..." onkeyup="filterTable('sk-tbl',1,this.value)"></div>
<div class="table-responsive" style="max-height:600px;overflow-y:auto"><table class="table table-sm table-hover align-middle" id="sk-tbl"><thead class="table-light sticky-top"><tr><th>Code</th><th>Name</th><th>Category</th><th>Contagious</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php if(empty($sicknesses)):?><tr><td colspan="6" class="text-center text-muted py-4">No sicknesses.</td></tr>
<?php else:foreach($sicknesses as $sk):?>
<tr><td><span class="badge bg-secondary"><?=htmlspecialchars($sk['sickness_code'])?></span></td><td><strong><?=htmlspecialchars($sk['sickness_name'])?></strong></td><td><small><?=htmlspecialchars($sk['category']??'Other')?></small></td><td><?=$sk['is_contagious']?'<span class="badge bg-danger">Yes</span>':'<span class="badge bg-success">No</span>'?></td><td><span class="badge <?=$sk['status']==='Active'?'bg-success':'bg-secondary'?>"><?=htmlspecialchars($sk['status']??'Active')?></span></td><td><button class="btn btn-sm btn-outline-primary" onclick="editSickness(<?=$sk['id']?>,'<?=htmlspecialchars($sk['sickness_code'],ENT_QUOTES)?>','<?=htmlspecialchars($sk['sickness_name'],ENT_QUOTES)?>','<?=htmlspecialchars($sk['category']??'Other',ENT_QUOTES)?>','<?=htmlspecialchars($sk['common_symptoms']??'',ENT_QUOTES)?>','<?=htmlspecialchars($sk['description']??'',ENT_QUOTES)?>','<?=htmlspecialchars($sk['typical_treatment']??'',ENT_QUOTES)?>',<?=$sk['is_contagious']?'true':'false'?>,'<?=htmlspecialchars($sk['status']??'Active',ENT_QUOTES)?>')"><i class="fas fa-edit"></i></button><form method="POST" class="d-inline" onsubmit="return confirm('Deactivate?')"><input type="hidden" name="action" value="delete_sickness"><input type="hidden" name="id" value="<?=$sk['id']?>"><button class="btn btn-sm btn-outline-danger"><i class="fas fa-times-circle"></i></button></form></td></tr>
<?php endforeach;endif;?></tbody></table></div></div></div></div></div><!-- LEAVE SHEET -->
<div class="sickbay-section <?=$active_section==='leave'?'active':''?>" id="sec-leave">
<div class="row g-4">
<div class="col-lg-5">
<div class="health-card"><h2><i class="fas fa-plus-circle me-2 text-warning"></i>Issue / Edit Sick Leave</h2>
<form method="POST" action="sickbay.php"><input type="hidden" name="action" value="save_leave"><input type="hidden" name="id" id="ed-lv-id" value="0">
<div class="mb-2"><label class="form-label fw-semibold">Student <span class="text-danger">*</span></label><input type="text" name="student_name" class="form-control" required placeholder="Full name" value="<?=htmlspecialchars($_GET['sname']??'')?>" id="lv-name"><input type="hidden" name="student_id" id="lv-sid" value="<?=(int)($_GET['sid']??0)?>"><input type="hidden" name="student_number" id="lv-num"></div>
<div class="row g-2 mb-2"><div class="col-8"><label class="form-label fw-semibold">Program</label><input type="text" name="program" class="form-control" id="lv-prog"></div><div class="col-4"><label class="form-label fw-semibold">Year</label><input type="number" name="year_of_study" class="form-control" id="lv-year" min="1" max="6"></div></div>
<div class="mb-2"><label class="form-label fw-semibold">Sickness <span class="text-danger">*</span></label><select name="sickness_id" class="form-select"><option value="">-- Select --</option><?php foreach($sickness_list as $sk):?><option value="<?=$sk['id']?>"><?=htmlspecialchars($sk['sickness_name'])?></option><?php endforeach;?></select><input type="text" class="form-control mt-1" name="other_sickness" placeholder="Or type manually"></div>
<div class="row g-2 mb-2"><div class="col-6"><label class="form-label fw-semibold">Start Date <span class="text-danger">*</span></label><input type="date" name="start_date" class="form-control" required value="<?=date('Y-m-d')?>"></div><div class="col-6"><label class="form-label fw-semibold">End Date <span class="text-danger">*</span></label><input type="date" name="end_date" class="form-control" required></div></div>
<div class="row g-2 mb-2"><div class="col-6"><label class="form-label fw-semibold">Bed Rest Required</label><select name="bed_rest_required" class="form-select"><option value="Yes">Yes</option><option value="No">No</option></select></div><div class="col-6"><label class="form-label fw-semibold">Recommended By</label><input type="text" name="recommended_by" class="form-control" placeholder="Doctor/Nurse name" value="<?=htmlspecialchars($user_name)?>"></div></div>
<div class="mb-3"><label class="form-label fw-semibold">Recommendations</label><textarea name="recommendations" class="form-control" rows="2"></textarea></div>
<div class="d-flex gap-2"><button type="submit" class="btn btn-warning"><i class="fas fa-save me-1"></i>Save Leave</button><button type="button" class="btn btn-secondary" onclick="resetLeave()"><i class="fas fa-undo me-1"></i>Reset</button></div>
</form></div></div>
<div class="col-lg-7">
<div class="health-card"><h2><i class="fas fa-list me-2 text-warning"></i>Student Sick Leaves</h2>
<div class="mb-2 d-flex gap-2 flex-wrap"><input type="text" class="form-control form-control-sm" style="width:200px" placeholder="Filter name..." onkeyup="filterTable('lv-tbl',1,this.value)"><select class="form-select form-select-sm" style="width:160px" onchange="filterTable('lv-tbl',5,this.value)"><option value="">All Status</option><option value="Active">Active</option><option value="Expired">Expired</option></select></div>
<div class="table-responsive" style="max-height:600px;overflow-y:auto"><table class="table table-sm table-hover align-middle" id="lv-tbl"><thead class="table-light sticky-top"><tr><th>Student</th><th>Sickness</th><th>Duration</th><th>Period</th><th>Bed Rest</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php if(empty($leave_records)):?><tr><td colspan="7" class="text-center text-muted py-4">No leave records.</td></tr>
<?php else:foreach($leave_records as $lv):$lend=$lv['leave_to']??'';$lstat=$lend<date('Y-m-d')?'text-danger':'text-success';?>
<tr><td><strong><small><?=htmlspecialchars($lv['student_name'])?></small></strong><small class="d-block text-muted"><?=htmlspecialchars($lv['student_number']??'')?></small></td><td><small><?=htmlspecialchars($lv['sickness_name']??'N/A')?></small></td><td><span class="badge bg-info"><?=$lv['total_days']??''?> days</span></td><td><small><?=date('d M',strtotime($lv['leave_from']))?> - <?=date('d M',strtotime($lv['leave_to']))?></small></td><td><small><?=$lv['bed_rest_required']?'Yes':'No'?></small></td><td><span class="badge <?=$lstat?>"><?=$lend<date('Y-m-d')?'Expired':'Active'?></span></td><td><button class="btn btn-sm btn-outline-primary" onclick="editLeave(<?=$lv['id']?>,'<?=htmlspecialchars($lv['student_name'],ENT_QUOTES)?>','<?=htmlspecialchars($lv['student_number']??'',ENT_QUOTES)?>','<?=htmlspecialchars($lv['program']??'',ENT_QUOTES)?>','<?=(int)($lv['year_of_study']??0)?>','<?=$lv['leave_from']?>','<?=$lv['leave_to']?>','<?=$lv['bed_rest_required']?>','<?=htmlspecialchars($lv['doctor_notes']??'',ENT_QUOTES)?>')"><i class="fas fa-edit"></i></button><a href="sickbay.php?section=leave&print=<?=$lv['id']?>" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="fas fa-print"></i></a><form method="POST" class="d-inline" onsubmit="return confirm('Delete?')"><input type="hidden" name="action" value="delete_leave"><input type="hidden" name="id" value="<?=$lv['id']?>"><button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash-alt"></i></button></form></td></tr>
<?php endforeach;endif;?></tbody></table></div></div></div></div></div><!-- MEDICINE STOCK -->
<div class="sickbay-section <?=$active_section==='medicine'?'active':''?>" id="sec-medicine">
<div class="row g-4">
<div class="col-lg-5">
<div class="health-card"><h2><i class="fas fa-plus-circle me-2 text-info"></i>Add Medicine to Stock</h2>
<form method="POST" action="sickbay.php"><input type="hidden" name="action" value="save_medicine">
<div class="row g-2 mb-2"><div class="col-8"><label class="form-label fw-semibold">Medicine Name <span class="text-danger">*</span></label><input type="text" name="medicine_name" class="form-control" required placeholder="e.g., Coartem"></div><div class="col-4"><label class="form-label fw-semibold">Dosage</label><input type="text" name="dosage" class="form-control" placeholder="500mg"></div></div>
<div class="row g-2 mb-2"><div class="col-3"><label class="form-label fw-semibold">Quantity</label><input type="number" name="quantity_in_stock" class="form-control" value="0" min="0"></div><div class="col-3"><label class="form-label fw-semibold">Unit</label><select name="unit" class="form-select"><option>Tablets</option><option>Bottles</option><option>Packs</option><option>Vials</option><option>Ampules</option><option>Sachets</option><option>Inhalers</option><option>Tubes</option></select></div><div class="col-3"><label class="form-label fw-semibold">Reorder Level</label><input type="number" name="reorder_level" class="form-control" value="10" min="0"></div><div class="col-3"><label class="form-label fw-semibold">Min Stock</label><input type="number" name="minimum_stock" class="form-control" value="5" min="0"></div></div>
<div class="row g-2 mb-2"><div class="col-6"><label class="form-label fw-semibold">Expiry Date</label><input type="date" name="expiry_date" class="form-control"></div><div class="col-6"><label class="form-label fw-semibold">Category</label><select name="category" class="form-select"><option>General</option><option>Antimalarial</option><option>Antibiotic</option><option>Antipyretic</option><option>Analgesic</option><option>Antifungal</option><option>Antiviral</option><option>Antiseptic</option><option>Vitamin</option><option>First Aid</option><option>Other</option></select></div></div>
<div class="mb-3"><label class="form-label fw-semibold">Description</label><textarea name="description" class="form-control" rows="1"></textarea></div>
<button type="submit" class="btn btn-info w-100"><i class="fas fa-box me-1"></i>Add Medicine</button>
</form></div>
<div class="health-card"><h2><i class="fas fa-exchange-alt me-2 text-secondary"></i>Stock Transaction</h2>
<form method="POST" action="sickbay.php"><input type="hidden" name="action" value="stock_transaction">
<div class="mb-2"><label class="form-label fw-semibold">Medicine <span class="text-danger">*</span></label><select name="medicine_id" class="form-select" required><option value="">-- Select --</option><?php foreach($medicines as $md):?><option value="<?=$md['id']?>"><?=htmlspecialchars($md['medicine_name'])?> (<?=(int)$md['quantity_in_stock']?> <?=htmlspecialchars($md['unit']??'units')?>)</option><?php endforeach;?></select></div>
<div class="row g-2 mb-2"><div class="col-4"><label class="form-label fw-semibold">Type</label><select name="transaction_type" class="form-select" required><option value="Purchase">Purchase</option><option value="Issue">Issue</option><option value="Return">Return</option><option value="Damage">Damage</option><option value="Expired">Expired</option></select></div><div class="col-4"><label class="form-label fw-semibold">Quantity <span class="text-danger">*</span></label><input type="number" name="quantity" class="form-control" required min="1"></div><div class="col-4"><label class="form-label fw-semibold">Date</label><input type="date" name="transaction_date" class="form-control" value="<?=date('Y-m-d')?>"></div></div>
<div class="mb-2"><label class="form-label fw-semibold">Notes</label><input type="text" name="notes" class="form-control"></div>
<button type="submit" class="btn btn-secondary w-100"><i class="fas fa-exchange-alt me-1"></i>Record Transaction</button>
</form></div></div>
<div class="col-lg-7">
<div class="health-card"><h2><i class="fas fa-capsules me-2 text-info"></i>Medicine Stock</h2>
<div class="mb-2 d-flex gap-2 flex-wrap"><input type="text" class="form-control form-control-sm" style="width:200px" placeholder="Filter..." onkeyup="filterTable('md-tbl',1,this.value)"><select class="form-select form-select-sm" style="width:160px" onchange="filterTable('md-tbl',3,this.value)"><option value="">All Status</option><option value="In Stock">In Stock</option><option value="Low Stock">Low Stock</option><option value="Out of Stock">Out of Stock</option><option value="Expired">Expired</option></select></div>
<div class="table-responsive" style="max-height:600px;overflow-y:auto"><table class="table table-sm table-hover align-middle" id="md-tbl"><thead class="table-light sticky-top"><tr><th>Medicine</th><th>Qty</th><th>Unit</th><th>Status</th><th>Expiry</th><th>Actions</th></tr></thead><tbody>
<?php if(empty($medicines)):?><tr><td colspan="6" class="text-center text-muted py-4">No medicines.</td></tr>
<?php else:foreach($medicines as $md):
    switch($md['status']){case'In Stock':$msc='bg-success';break;case'Low Stock':$msc='bg-warning text-dark';break;case'Out of Stock':$msc='bg-danger';break;case'Expired':$msc='bg-secondary';break;default:$msc='bg-secondary';}
    $mcs=$md['status']==='Out of Stock'?'stock-critical':($md['status']==='Low Stock'?'stock-warning':($md['status']==='Expired'?'stock-critical':''));
?>
<tr class="<?=$mcs?>"><td><strong><small><?=htmlspecialchars($md['medicine_name'])?></small></strong><small class="d-block text-muted"><?=htmlspecialchars($md['dosage']??'')?></small></td><td><strong><?=(int)$md['quantity_in_stock']?></strong></td><td><small><?=htmlspecialchars($md['unit']??'units')?></small></td><td><span class="badge <?=$msc?>"><?=htmlspecialchars($md['status']??'In Stock')?></span></td><td><small><?=$md['expiry_date']?date('d M Y',strtotime($md['expiry_date'])):'�'?></small></td><td><form method="POST" class="d-inline" onsubmit="return confirm('Delete?')"><input type="hidden" name="action" value="delete_medicine"><input type="hidden" name="id" value="<?=$md['id']?>"><button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash-alt"></i></button></form><button class="btn btn-sm btn-outline-info" onclick="viewTransactions(<?=$md['id']?>,'<?=htmlspecialchars($md['medicine_name'],ENT_QUOTES)?>')"><i class="fas fa-history"></i></button></td></tr>
<?php endforeach;endif;?></tbody></table></div></div></div></div></div><!-- RECYCLE BIN -->
<div class="sickbay-section <?=$active_section==='recycle-bin'?'active':''?>" id="sec-recycle-bin">
<div class="health-card"><h2><i class="fas fa-trash-restore me-2 text-secondary"></i>Recycle Bin</h2>
<?php $deleted_records = sb_fetch($staff_conn, "SELECT dsr.*, s.sickness_name, 'Sick Record' AS source FROM daily_sick_records dsr LEFT JOIN sickness_directory s ON dsr.sickness_id = s.id WHERE dsr.is_deleted = 1 ORDER BY dsr.deleted_at DESC LIMIT 50"); ?>
<?php $deleted_leaves = sb_fetch($staff_conn, "SELECT sl.*, 'Sick Leave' AS source FROM student_sick_leave sl WHERE sl.is_deleted = 1 ORDER BY sl.deleted_at DESC LIMIT 50"); ?>
<?php $all_deleted = array_merge($deleted_records, $deleted_leaves); usort($all_deleted, function($a,$b){return strtotime($b['deleted_at']??$b['updated_at']??'')-strtotime($a['deleted_at']??$a['updated_at']??'');}); ?>
<?php if (empty($all_deleted)): ?>
<p class="text-muted small py-3"><i class="fas fa-info-circle me-1"></i>No deleted records found.</p>
<?php else: ?>
<div class="table-responsive" style="max-height:500px;overflow-y:auto">
<table class="table table-sm table-hover align-middle"><thead class="table-light sticky-top"><tr><th>Student</th><th>Source</th><th>Diagnosis/Sickness</th><th>Deleted</th><th>Action</th></tr></thead><tbody>
<?php foreach ($all_deleted as $dr): ?>
<tr><td><strong><small><?=htmlspecialchars($dr['student_name'])?></small></strong></td><td><span class="badge bg-secondary"><?=htmlspecialchars($dr['source'])?></span></td><td><small><?=htmlspecialchars($dr['diagnosis']??$dr['sickness_name']??$dr['leave_type']??'N/A')?></small></td><td><small class="text-muted"><?=date('d M Y',strtotime($dr['deleted_at']??$dr['updated_at']))?></small></td>
<td><?php if ($dr['source'] === 'Sick Record'): ?><form method="POST" class="d-inline" onsubmit="return confirm('Restore this record?')"><input type="hidden" name="action" value="restore_record"><input type="hidden" name="id" value="<?=$dr['id']?>"><button class="btn btn-sm btn-outline-success"><i class="fas fa-undo me-1"></i>Restore</button></form>
<form method="POST" class="d-inline" onsubmit="return confirm('Permanently delete? This cannot be undone.')"><input type="hidden" name="action" value="purge_record"><input type="hidden" name="id" value="<?=$dr['id']?>"><button class="btn btn-sm btn-outline-danger"><i class="fas fa-times-circle me-1"></i>Purge</button></form><?php endif; ?></td></tr>
<?php endforeach; ?>
</tbody></table></div><?php endif; ?>
</div></div><!-- AUDIT TRAIL -->
<div class="sickbay-section <?=$active_section==='audit'?'active':''?>" id="sec-audit">
<div class="health-card"><h2><i class="fas fa-history me-2 text-info"></i>Audit Trail</h2>
<?php $audit_logs = sb_fetch($staff_conn, "SELECT a.*, u.full_name AS actor_name FROM (SELECT id, 'daily_sick_records' AS log_source, CONCAT(student_name, ' - ', diagnosis) AS description, 'Record' AS log_type, created_at, created_by FROM daily_sick_records WHERE created_by IS NOT NULL UNION ALL SELECT id, 'student_sick_leave' AS log_source, CONCAT(student_name, ' - ', leave_type, ' leave') AS description, 'Leave' AS log_type, created_at, created_by FROM student_sick_leave WHERE created_by IS NOT NULL UNION ALL SELECT id, 'medicine_stock' AS log_source, medicine_name AS description, 'Stock' AS log_type, created_at, created_by FROM medicine_stock WHERE created_by IS NOT NULL UNION ALL SELECT mst.id, 'medicine_stock_transactions' AS log_source, CONCAT('Transaction: ', mst.transaction_type) AS description, 'Transaction' AS log_type, mst.created_at, mst.performed_by FROM medicine_stock_transactions mst ) a LEFT JOIN staff_profiles u ON a.created_by = u.id ORDER BY a.created_at DESC LIMIT 200"); ?>
<?php if (empty($audit_logs)): ?>
<p class="text-muted small py-3"><i class="fas fa-info-circle me-1"></i>No audit trail entries yet.</p>
<?php else: ?>
<div class="table-responsive" style="max-height:500px;overflow-y:auto">
<table class="table table-sm table-hover align-middle"><thead class="table-light sticky-top"><tr><th>Date</th><th>Type</th><th>Description</th><th>Actor</th><th>Source</th></tr></thead><tbody>
<?php foreach ($audit_logs as $al): ?>
<tr><td><small class="text-muted"><?=date('d M Y H:i',strtotime($al['created_at']))?></small></td><td><span class="badge bg-secondary"><?=htmlspecialchars($al['log_type'])?></span></td><td><small><?=htmlspecialchars(substr($al['description'],0,80))?></small></td><td><small><?=htmlspecialchars($al['actor_name']??'System')?></small></td><td><small class="text-muted"><?=htmlspecialchars($al['log_source'])?></small></td></tr>
<?php endforeach; ?>
</tbody></table></div><?php endif; ?>
</div></div><!-- SETTINGS -->
<div class="sickbay-section <?=$active_section==='settings'?'active':''?>" id="sec-settings">
<div class="row g-4">
<div class="col-lg-6">
<div class="health-card"><h2><i class="fas fa-sliders-h me-2 text-primary"></i>Sickbay Settings</h2>
<form method="POST" action="sickbay.php"><input type="hidden" name="action" value="save_settings">
<div class="mb-2"><label class="form-label fw-semibold">Default Reorder Level</label><input type="number" name="default_reorder" class="form-control" value="<?=(int)($sb_settings['reorder_level']??10)?>"></div>
<div class="mb-2"><label class="form-label fw-semibold">Low Stock Threshold</label><input type="number" name="low_stock_threshold" class="form-control" value="<?=(int)($sb_settings['low_stock_threshold']??10)?>"></div>
<div class="mb-2"><label class="form-label fw-semibold">Auto-Calculate Stock Status</label><select name="auto_status" class="form-select"><option value="1" <?=($sb_settings['auto_status']??1)?'selected':''?>>Enabled</option><option value="0" <?=($sb_settings['auto_status']??1)?'':'selected'?>>Disabled</option></select></div>
<div class="mb-3"><label class="form-label fw-semibold">Notifications</label><select name="notify_low_stock" class="form-select"><option value="1" <?=($sb_settings['notify_low_stock']??1)?'selected':''?>>Alert on low stock</option><option value="0" <?=($sb_settings['notify_low_stock']??1)?'':'selected'?>>No alerts</option></select></div>
<button type="submit" class="btn btn-primary w-100"><i class="fas fa-save me-1"></i>Save Settings</button>
</form></div></div>
<div class="col-lg-6">
<div class="health-card"><h2><i class="fas fa-palette me-2 text-warning"></i>Theme & Display</h2>
<p class="text-muted small">Customize the appearance of the Sickbay module.</p>
<div class="d-flex flex-wrap gap-2 mb-3">
<button class="btn btn-outline-primary" onclick="setTheme('default-blue')" style="border-left:4px solid #0d6efd">Default Blue</button>
<button class="btn btn-outline-success" onclick="setTheme('green')" style="border-left:4px solid #198754">Green</button>
<button class="btn btn-outline-danger" onclick="setTheme('red')" style="border-left:4px solid #dc3545">Red</button>
<button class="btn btn-outline-warning" onclick="setTheme('amber')" style="border-left:4px solid #ffc107">Amber</button>
<button class="btn btn-outline-purple" onclick="setTheme('purple')" style="border-left:4px solid #6f42c1">Purple</button>
</div>
<p class="text-muted small mb-0"><i class="fas fa-info-circle me-1"></i>Current theme: <strong id="currentThemeName">Default Blue</strong></p>
</div></div></div></div><!-- HEALTH RECORDS -->
<div class="sickbay-section <?=$active_section==='health-records'?'active':''?>" id="sec-health-records">
<div class="row g-4">
<div class="col-lg-5">
<div class="health-card"><h2><i class="fas fa-plus-circle me-2 text-primary"></i>Student Health Profile</h2>
<form method="POST" action="sickbay.php"><input type="hidden" name="action" value="save_health_record">
<div class="mb-2"><label class="form-label fw-semibold">Student <span class="text-danger">*</span></label><input type="text" name="student_name" class="form-control" required placeholder="Full name" id="hr-name"><input type="hidden" name="student_id" id="hr-sid" value="0"><input type="hidden" name="student_number" id="hr-num"></div>
<div class="row g-2 mb-2"><div class="col-4"><label class="form-label fw-semibold">Blood Type</label><select name="blood_type" class="form-select"><option value="">--</option><option>A+</option><option>A-</option><option>B+</option><option>B-</option><option>AB+</option><option>AB-</option><option>O+</option><option>O-</option></select></div><div class="col-4"><label class="form-label fw-semibold">Insurance</label><input type="text" name="insurance_provider" class="form-control" placeholder="Provider"></div><div class="col-4"><label class="form-label fw-semibold">Insurance #</label><input type="text" name="insurance_number" class="form-control" placeholder="Number"></div></div>
<div class="mb-2"><label class="form-label fw-semibold">Allergies</label><textarea name="allergies" class="form-control" rows="2" placeholder="List known allergies"></textarea></div>
<div class="mb-2"><label class="form-label fw-semibold">Chronic Conditions</label><textarea name="chronic_conditions" class="form-control" rows="2" placeholder="e.g., Asthma, Diabetes"></textarea></div>
<div class="mb-2"><label class="form-label fw-semibold">Current Medications</label><textarea name="medications" class="form-control" rows="2"></textarea></div>
<div class="row g-2 mb-2"><div class="col-4"><label class="form-label fw-semibold">Emergency Contact</label><input type="text" name="emergency_contact_name" class="form-control" placeholder="Name"></div><div class="col-4"><label class="form-label fw-semibold">Phone</label><input type="text" name="emergency_contact_phone" class="form-control" placeholder="Phone"></div><div class="col-4"><label class="form-label fw-semibold">Relationship</label><input type="text" name="emergency_contact_relationship" class="form-control" placeholder="e.g., Parent"></div></div>
<div class="mb-3"><label class="form-label fw-semibold">Notes</label><textarea name="notes" class="form-control" rows="1"></textarea></div>
<button type="submit" class="btn btn-primary w-100"><i class="fas fa-save me-1"></i>Save Health Record</button>
</form></div></div>
<div class="col-lg-7">
<div class="health-card"><h2><i class="fas fa-list me-2 text-primary"></i>Student Health Records</h2>
<div class="mb-2"><input type="text" class="form-control form-control-sm" style="width:280px" placeholder="Filter by name..." onkeyup="filterTable('hr-tbl',1,this.value)"></div>
<div class="table-responsive" style="max-height:600px;overflow-y:auto"><table class="table table-sm table-hover align-middle" id="hr-tbl"><thead class="table-light sticky-top"><tr><th>Student</th><th>Blood Type</th><th>Allergies</th><th>Chronic</th><th>Insurance</th><th>Emergency Contact</th></tr></thead><tbody>
<?php if(empty($health_records_list)):?><tr><td colspan="6" class="text-center text-muted py-4">No health records.</td></tr>
<?php else:foreach($health_records_list as $hr):?>
<tr><td><strong><small><?=htmlspecialchars($hr['full_name']??'Unknown')?></small></strong><small class="d-block text-muted"><?=htmlspecialchars($hr['student_number']??'')?></small></td><td><span class="badge bg-danger"><?=htmlspecialchars($hr['blood_type']??'-')?></span></td><td><small><?=htmlspecialchars(substr($hr['allergies']??'-',0,40))?></small></td><td><small><?=htmlspecialchars(substr($hr['chronic_conditions']??'-',0,40))?></small></td><td><small><?=htmlspecialchars($hr['insurance_provider']??'-')?></small></td><td><small><?=htmlspecialchars($hr['emergency_contact_name']??'-')?> <?=$hr['emergency_contact_phone']?'('.htmlspecialchars($hr['emergency_contact_phone']).')':''?></small></td></tr>
<?php endforeach;endif;?></tbody></table></div></div></div></div></div><!-- HEALTH INCIDENTS -->
<div class="sickbay-section <?=$active_section==='health-incidents'?'active':''?>" id="sec-health-incidents">
<div class="row g-4">
<div class="col-lg-5">
<div class="health-card"><h2><i class="fas fa-plus-circle me-2 text-danger"></i>Report Health Incident</h2>
<form method="POST" action="sickbay.php"><input type="hidden" name="action" value="save_health_incident">
<div class="mb-2"><label class="form-label fw-semibold">Student <span class="text-danger">*</span></label><input type="text" name="student_name" class="form-control" required placeholder="Full name" id="hi-name"><input type="hidden" name="student_id" id="hi-sid" value="0"><input type="hidden" name="student_number" id="hi-num"></div>
<div class="row g-2 mb-2"><div class="col-6"><label class="form-label fw-semibold">Incident Type</label><select name="incident_type" class="form-select"><option>Illness</option><option>Injury</option><option>Accident</option><option>Allergic Reaction</option><option>Other</option></select></div><div class="col-6"><label class="form-label fw-semibold">Severity</label><select name="severity" class="form-select"><option value="Minor">Minor</option><option value="Moderate">Moderate</option><option value="Severe">Severe</option><option value="Critical">Critical</option></select></div></div>
<div class="mb-2"><label class="form-label fw-semibold">Symptoms</label><textarea name="symptoms" class="form-control" rows="2"></textarea></div>
<div class="mb-2"><label class="form-label fw-semibold">Location</label><input type="text" name="location" class="form-control" placeholder="Where did it occur?"></div>
<div class="mb-2"><label class="form-label fw-semibold">Action Taken</label><textarea name="action_taken" class="form-control" rows="2"></textarea></div>
<div class="mb-2"><label class="form-label fw-semibold">Treatment Given</label><textarea name="treatment_given" class="form-control" rows="2"></textarea></div>
<div class="mb-2"><label class="form-label fw-semibold">Referred To</label><input type="text" name="referred_to" class="form-control" placeholder="Hospital/Clinic name"></div>
<div class="row g-2 mb-2"><div class="col-6"><label class="form-label fw-semibold">Parent Notified</label><select name="parent_notified" class="form-select"><option value="0">No</option><option value="1">Yes</option></select></div><div class="col-6"><label class="form-label fw-semibold">Follow-up Date</label><input type="date" name="follow_up_date" class="form-control"></div></div>
<div class="mb-3"><label class="form-label fw-semibold">Notes</label><textarea name="notes" class="form-control" rows="1"></textarea></div>
<button type="submit" class="btn btn-danger w-100"><i class="fas fa-save me-1"></i>Report Incident</button>
</form></div>
<div class="health-card"><h2><i class="fas fa-phone-alt me-2 text-success"></i>Emergency Contacts</h2>
<?php if(empty($emergency_contacts_list)):?><p class="text-muted small">No emergency contacts configured.</p>
<?php else:?>
<div class="table-responsive"><table class="table table-sm table-hover"><thead><tr><th>Name</th><th>Type</th><th>Phone</th><th>Priority</th></tr></thead><tbody>
<?php foreach($emergency_contacts_list as $ec):?>
<tr><td><strong><small><?=htmlspecialchars($ec['contact_name'])?></small></strong></td><td><span class="badge bg-secondary"><?=htmlspecialchars($ec['contact_type'])?></span></td><td><small><?=htmlspecialchars($ec['phone_number'])?></small></td><td><span class="badge bg-<?=$ec['priority']==='Primary'?'danger':'info'?>"><?=htmlspecialchars($ec['priority'])?></span></td></tr>
<?php endforeach;?>
</tbody></table></div><?php endif;?>
</div></div>
<div class="col-lg-7">
<div class="health-card"><h2><i class="fas fa-list me-2 text-danger"></i>Health Incidents</h2>
<div class="mb-2 d-flex gap-2 flex-wrap"><input type="text" class="form-control form-control-sm" style="width:200px" placeholder="Filter..." onkeyup="filterTable('hi-tbl',1,this.value)"><select class="form-select form-select-sm" style="width:140px" onchange="filterTable('hi-tbl',3,this.value)"><option value="">All Severity</option><option value="Minor">Minor</option><option value="Moderate">Moderate</option><option value="Severe">Severe</option><option value="Critical">Critical</option></select></div>
<div class="table-responsive" style="max-height:600px;overflow-y:auto"><table class="table table-sm table-hover align-middle" id="hi-tbl"><thead class="table-light sticky-top"><tr><th>Student</th><th>Type</th><th>Severity</th><th>Location</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php if(empty($health_incidents_list)):?><tr><td colspan="7" class="text-center text-muted py-4">No incidents reported.</td></tr>
<?php else:foreach($health_incidents_list as $hi):
    switch($hi['severity']){case'Minor':$hisc='bg-success';break;case'Moderate':$hisc='bg-warning text-dark';break;case'Severe':$hisc='bg-orange';break;case'Critical':$hisc='bg-danger';break;default:$hisc='bg-secondary';}
    switch($hi['status']){case'Reported':$hist='bg-warning text-dark';break;case'Under Observation':$hist='bg-info text-dark';break;case'Resolved':$hist='bg-success';break;case'Referred':$hist='bg-primary';break;case'Closed':$hist='bg-secondary';break;default:$hist='bg-secondary';}
?>
<tr><td><strong><small><?=htmlspecialchars($hi['full_name']??'Unknown')?></small></strong><small class="d-block text-muted"><?=htmlspecialchars($hi['student_number']??'')?></small></td><td><small><?=htmlspecialchars($hi['incident_type'])?></small></td><td><span class="badge <?=$hisc?>"><?=htmlspecialchars($hi['severity'])?></span></td><td><small><?=htmlspecialchars($hi['location']??'-')?></small></td><td><small><?=date('d M Y',strtotime($hi['incident_date']))?></small></td><td><span class="badge <?=$hist?>"><?=htmlspecialchars($hi['status'])?></span></td><td><?php if($hi['status']!=='Resolved'&&$hi['status']!=='Closed'):?><form method="POST" class="d-inline" onsubmit="return confirm('Resolve this incident?')"><input type="hidden" name="action" value="resolve_incident"><input type="hidden" name="id" value="<?=$hi['id']?>"><button class="btn btn-sm btn-outline-success"><i class="fas fa-check"></i></button></form><?php endif;?></td></tr>
<?php endforeach;endif;?></tbody></table></div></div></div></div></div><!-- SICKBAY VISITS -->
<div class="sickbay-section <?=$active_section==='visits'?'active':''?>" id="sec-visits">
<div class="row g-4">
<div class="col-lg-5">
<div class="health-card"><h2><i class="fas fa-plus-circle me-2 text-primary"></i>Add / Edit Sickbay Visit</h2>
<form method="POST" action="sickbay.php" id="visit-form"><input type="hidden" name="action" value="add_visit" id="visit-action"><input type="hidden" name="id" id="ed-v-id" value="0">
<div class="mb-2"><label class="form-label fw-semibold">Student <span class="text-danger">*</span></label><input type="text" name="student_name" class="form-control" required placeholder="Full name" id="vb-name"><input type="hidden" name="student_id" id="vb-sid" value="0"><input type="hidden" name="student_number" id="vb-num"></div>
<div class="mb-2"><label class="form-label fw-semibold">Visit Date <span class="text-danger">*</span></label><input type="date" name="visit_date" class="form-control" required value="<?=date('Y-m-d')?>"></div>
<div class="mb-2"><label class="form-label fw-semibold">Symptoms</label><textarea name="symptoms" class="form-control" rows="2" id="ed-v-symp"></textarea></div>
<div class="mb-2"><label class="form-label fw-semibold">Diagnosis</label><textarea name="diagnosis" class="form-control" rows="2" id="ed-v-diag"></textarea></div>
<div class="mb-2"><label class="form-label fw-semibold">Treatment</label><textarea name="treatment" class="form-control" rows="2" id="ed-v-treat"></textarea></div>
<div class="mb-2"><label class="form-label fw-semibold">Medication Given</label><textarea name="medication_given" class="form-control" rows="1" id="ed-v-med"></textarea></div>
<div class="row g-2 mb-2"><div class="col-6"><label class="form-label fw-semibold">Status</label><select name="status" class="form-select" id="ed-v-stat"><option value="Pending">Pending</option><option value="In Progress">In Progress</option><option value="Treated">Treated</option><option value="Referred">Referred</option><option value="Discharged">Discharged</option><option value="Follow-up">Follow-up</option></select></div><div class="col-6"><label class="form-label fw-semibold">Follow-up Date</label><input type="date" name="follow_up_date" class="form-control" id="ed-v-fud"></div></div>
<div class="mb-3"><label class="form-label fw-semibold">Notes</label><textarea name="notes" class="form-control" rows="1" id="ed-v-notes"></textarea></div>
<div class="d-flex gap-2"><button type="submit" class="btn btn-primary" id="visit-submit-btn"><i class="fas fa-save me-1"></i>Add Visit</button><button type="button" class="btn btn-secondary" onclick="resetVisitForm()"><i class="fas fa-undo me-1"></i>Reset</button></div>
</form></div>
<div class="health-card"><h2><i class="fas fa-capsules me-2 text-info"></i>Sickbay Medicine Stock</h2>
<form method="POST" action="sickbay.php" id="sb-med-form"><input type="hidden" name="action" value="add_medicine" id="sb-med-action"><input type="hidden" name="id" id="ed-sm-id" value="0">
<div class="row g-2 mb-2"><div class="col-7"><label class="form-label fw-semibold">Medicine Name <span class="text-danger">*</span></label><input type="text" name="medicine_name" class="form-control" required placeholder="e.g., Paracetamol" id="ed-sm-name"></div><div class="col-5"><label class="form-label fw-semibold">Category</label><select name="category" class="form-select" id="ed-sm-cat"><option>General</option><option>Antimalarial</option><option>Antibiotic</option><option>Antipyretic</option><option>Analgesic</option><option>Antifungal</option><option>Antiseptic</option><option>Vitamin</option><option>First Aid</option><option>Other</option></select></div></div>
<div class="row g-2 mb-2"><div class="col-4"><label class="form-label fw-semibold">Quantity</label><input type="number" name="quantity" class="form-control" value="0" min="0" id="ed-sm-qty"></div><div class="col-4"><label class="form-label fw-semibold">Unit</label><select name="unit" class="form-select" id="ed-sm-unit"><option>Tablets</option><option>Bottles</option><option>Packs</option><option>Vials</option><option>Ampules</option><option>Sachets</option><option>Tubes</option></select></div><div class="col-4"><label class="form-label fw-semibold">Reorder Level</label><input type="number" name="reorder_level" class="form-control" value="10" min="0" id="ed-sm-rol"></div></div>
<div class="mb-3"><label class="form-label fw-semibold">Expiry Date</label><input type="date" name="expiry_date" class="form-control" id="ed-sm-exp"></div>
<div class="d-flex gap-2"><button type="submit" class="btn btn-info" id="sb-med-submit-btn"><i class="fas fa-save me-1"></i>Add Medicine</button><button type="button" class="btn btn-secondary" onclick="resetSbMedForm()"><i class="fas fa-undo me-1"></i>Reset</button></div>
</form></div>
<div class="health-card"><h2><i class="fas fa-exchange-alt me-2 text-warning"></i>Dispense Medicine</h2>
<form method="POST" action="sickbay.php"><input type="hidden" name="action" value="dispense_medicine">
<div class="mb-2"><label class="form-label fw-semibold">Medicine <span class="text-danger">*</span></label><select name="medicine_id" class="form-select" required><option value="">-- Select --</option><?php foreach($sickbay_medicine_stock as $sm):?><option value="<?=$sm['id']?>"><?=htmlspecialchars($sm['medicine_name'])?> (<?=(int)$sm['quantity']?> <?=htmlspecialchars($sm['unit']??'units')?>)</option><?php endforeach;?></select></div>
<div class="row g-2 mb-2"><div class="col-6"><label class="form-label fw-semibold">Quantity <span class="text-danger">*</span></label><input type="number" name="quantity" class="form-control" required min="1"></div><div class="col-6"><label class="form-label fw-semibold">Visit ID (optional)</label><input type="number" name="visit_id" class="form-control" min="0" placeholder="0"></div></div>
<div class="mb-2"><label class="form-label fw-semibold">Notes</label><input type="text" name="notes" class="form-control" placeholder="Reason for dispensing"></div>
<button type="submit" class="btn btn-warning w-100"><i class="fas fa-hand-holding-medical me-1"></i>Dispense</button>
</form></div>
<div class="health-card"><h2><i class="fas fa-file-alt me-2 text-secondary"></i>Add Transaction</h2>
<form method="POST" action="sickbay.php"><input type="hidden" name="action" value="add_medicine_transaction">
<div class="mb-2"><label class="form-label fw-semibold">Medicine <span class="text-danger">*</span></label><select name="medicine_id" class="form-select" required><option value="">-- Select --</option><?php foreach($sickbay_medicine_stock as $sm):?><option value="<?=$sm['id']?>"><?=htmlspecialchars($sm['medicine_name'])?></option><?php endforeach;?></select></div>
<div class="row g-2 mb-2"><div class="col-4"><label class="form-label fw-semibold">Type</label><select name="transaction_type" class="form-select"><option>Purchase</option><option>Dispense</option><option>Return</option><option>Damage</option><option>Expired</option></select></div><div class="col-4"><label class="form-label fw-semibold">Quantity</label><input type="number" name="quantity" class="form-control" required min="1"></div><div class="col-4"><label class="form-label fw-semibold">Visit ID</label><input type="number" name="visit_id" class="form-control" min="0" placeholder="0"></div></div>
<div class="mb-2"><label class="form-label fw-semibold">Notes</label><input type="text" name="notes" class="form-control"></div>
<button type="submit" class="btn btn-secondary w-100"><i class="fas fa-plus me-1"></i>Record Transaction</button>
</form></div></div>
<div class="col-lg-7">
<div class="health-card"><h2><i class="fas fa-clipboard-list me-2 text-primary"></i>Sickbay Visits</h2>
<div class="mb-2 d-flex gap-2 flex-wrap"><input type="text" class="form-control form-control-sm" style="width:200px" placeholder="Filter name..." onkeyup="filterTable('vb-tbl',1,this.value)"><select class="form-select form-select-sm" style="width:140px" onchange="filterTable('vb-tbl',4,this.value)"><option value="">All Status</option><option value="Pending">Pending</option><option value="In Progress">In Progress</option><option value="Treated">Treated</option><option value="Referred">Referred</option><option value="Discharged">Discharged</option><option value="Follow-up">Follow-up</option></select></div>
<div class="table-responsive" style="max-height:500px;overflow-y:auto"><table class="table table-sm table-hover align-middle" id="vb-tbl"><thead class="table-light sticky-top"><tr><th>Student</th><th>Date</th><th>Symptoms</th><th>Diagnosis</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php if(empty($sickbay_visits)):?><tr><td colspan="6" class="text-center text-muted py-4">No visits recorded.</td></tr>
<?php else:foreach($sickbay_visits as $sv):
    switch($sv['status']??''){case'Pending':$svc='bg-warning text-dark';break;case'In Progress':$svc='bg-info text-dark';break;case'Treated':$svc='bg-success';break;case'Referred':$svc='bg-primary';break;case'Discharged':$svc='bg-secondary';break;case'Follow-up':$svc='bg-danger';break;default:$svc='bg-secondary';}
?>
<tr><td><strong><small><?=htmlspecialchars($sv['student_name']??'Unknown')?></small></strong></td><td><small><?=date('d M Y',strtotime($sv['visit_date']))?></small></td><td><small><?=htmlspecialchars(substr($sv['symptoms']??'-',0,40))?></small></td><td><small><?=htmlspecialchars(substr($sv['diagnosis']??'-',0,40))?></small></td><td><span class="badge <?=$svc?>"><?=htmlspecialchars($sv['status']??'Pending')?></span></td><td><button class="btn btn-sm btn-outline-primary" onclick="editVisit(<?=$sv['id']?>,'<?=htmlspecialchars($sv['student_name']??'',ENT_QUOTES)?>','<?=$sv['visit_date']?>','<?=htmlspecialchars($sv['symptoms']??'',ENT_QUOTES)?>','<?=htmlspecialchars($sv['diagnosis']??'',ENT_QUOTES)?>','<?=htmlspecialchars($sv['treatment']??'',ENT_QUOTES)?>','<?=htmlspecialchars($sv['medication_given']??'',ENT_QUOTES)?>','<?=htmlspecialchars($sv['status']??'Pending',ENT_QUOTES)?>','<?=$sv['follow_up_date']??''?>','<?=htmlspecialchars($sv['notes']??'',ENT_QUOTES)?>')"><i class="fas fa-edit"></i></button><form method="POST" class="d-inline" onsubmit="return confirm('Delete this visit?')"><input type="hidden" name="action" value="delete_visit"><input type="hidden" name="id" value="<?=$sv['id']?>"><button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash-alt"></i></button></form></td></tr>
<?php endforeach;endif;?></tbody></table></div></div>
<div class="health-card"><h2><i class="fas fa-pills me-2 text-info"></i>Sickbay Medicine Stock</h2>
<div class="mb-2"><input type="text" class="form-control form-control-sm" style="width:250px" placeholder="Filter medicine..." onkeyup="filterTable('sbmd-tbl',0,this.value)"></div>
<div class="table-responsive" style="max-height:400px;overflow-y:auto"><table class="table table-sm table-hover align-middle" id="sbmd-tbl"><thead class="table-light sticky-top"><tr><th>Medicine</th><th>Category</th><th>Qty</th><th>Unit</th><th>Expiry</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php if(empty($sickbay_medicine_stock)):?><tr><td colspan="7" class="text-center text-muted py-4">No medicines.</td></tr>
<?php else:foreach($sickbay_medicine_stock as $sm):
    switch($sm['status']??''){case'In Stock':$smsc='bg-success';break;case'Low Stock':$smsc='bg-warning text-dark';break;case'Out of Stock':$smsc='bg-danger';break;default:$smsc='bg-secondary';}
?>
<tr><td><strong><small><?=htmlspecialchars($sm['medicine_name'])?></small></strong></td><td><small><?=htmlspecialchars($sm['category']??'-')?></small></td><td><strong><?=(int)$sm['quantity']?></strong></td><td><small><?=htmlspecialchars($sm['unit']??'')?></small></td><td><small><?=$sm['expiry_date']?date('d M Y',strtotime($sm['expiry_date'])):'-'?></small></td><td><span class="badge <?=$smsc?>"><?=htmlspecialchars($sm['status']??'In Stock')?></span></td><td><button class="btn btn-sm btn-outline-primary" onclick="editSbMed(<?=$sm['id']?>,'<?=htmlspecialchars($sm['medicine_name'],ENT_QUOTES)?>','<?=htmlspecialchars($sm['category']??'General',ENT_QUOTES)?>',<?=(int)$sm['quantity']?>,'<?=htmlspecialchars($sm['unit']??'Tablets',ENT_QUOTES)?>','<?=$sm['expiry_date']??''?>',<?=(int)$sm['reorder_level']??10?>)"><i class="fas fa-edit"></i></button><form method="POST" class="d-inline" onsubmit="return confirm('Delete?')"><input type="hidden" name="action" value="delete_sb_medicine"><input type="hidden" name="id" value="<?=$sm['id']?>"><button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash-alt"></i></button></form></td></tr>
<?php endforeach;endif;?></tbody></table></div></div>
<div class="health-card"><h2><i class="fas fa-exchange-alt me-2 text-secondary"></i>Sickbay Medicine Transactions</h2>
<div class="mb-2"><input type="text" class="form-control form-control-sm" style="width:250px" placeholder="Filter..." onkeyup="filterTable('smtx-tbl',1,this.value)"></div>
<div class="table-responsive" style="max-height:400px;overflow-y:auto"><table class="table table-sm table-hover align-middle" id="smtx-tbl"><thead class="table-light sticky-top"><tr><th>#</th><th>Medicine</th><th>Type</th><th>Qty</th><th>Visit</th><th>By</th><th>Notes</th></tr></thead><tbody>
<?php if(empty($sickbay_medicine_transactions)):?><tr><td colspan="7" class="text-center text-muted py-4">No transactions.</td></tr>
<?php else:foreach($sickbay_medicine_transactions as $smt):?>
<tr><td><small><?=(int)$smt['id']?></small></td><td><small><?=htmlspecialchars($smt['medicine_name']??'N/A')?></small></td><td><span class="badge bg-info"><?=htmlspecialchars($smt['transaction_type']??'N/A')?></span></td><td><strong><?=(int)$smt['quantity']?></strong></td><td><small><?=(int)($smt['visit_id']??0)?:'-'?></small></td><td><small><?=(int)($smt['performed_by']??0)?></small></td><td><small><?=htmlspecialchars($smt['notes']??'-')?></small></td></tr>
<?php endforeach;endif;?></tbody></table></div></div>
</div></div></div><!-- end content-area -->
</div><!-- end page-content -->
<script>
window.addEventListener('unhandledrejection',function(e){
  var url = '';
  try { if (e.reason && typeof e.reason === 'object') url = e.reason.url || ''; else if (typeof e.reason === 'string') url = e.reason; } catch(ex) {}
  if (url.indexOf('/writing/') > -1 || url.indexOf('/generate/') > -1 || url.indexOf('/site_integration/') > -1) e.preventDefault();
});
function filterTable(tblId,col,val){val=val.toLowerCase();const tbl=document.getElementById(tblId);if(!tbl||!tbl.tBodies[0])return;const rows=tbl.tBodies[0].rows;for(let i=0;i<rows.length;i++){const cells=rows[i].cells;if(!cells[col])continue;const txt=cells[col].textContent.toLowerCase();rows[i].style.display=txt.indexOf(val)>-1?'':'none';}}
function filterTableByDate(tblId,col,val){const tbl=document.getElementById(tblId);if(!tbl||!tbl.tBodies[0])return;const rows=tbl.tBodies[0].rows;for(let i=0;i<rows.length;i++){const cells=rows[i].cells;if(!cells[col])continue;const cellDate=cells[col].textContent.trim();if(!val){rows[i].style.display='';continue;}const d=new Date(val);const parts=cellDate.split(' ');if(parts.length>=3){const months={Jan:0,Feb:1,Mar:2,Apr:3,May:4,Jun:5,Jul:6,Aug:7,Sep:8,Oct:9,Nov:10,Dec:11};const cd=new Date(parseInt(parts[2]),months[parts[1]],parseInt(parts[0]));rows[i].style.display=cd.toDateString()===d.toDateString()?'':'none';}else rows[i].style.display='';}}
function searchStudents(inputName,hiddenId,hiddenNum,hiddenProg,hiddenYear){const inp=typeof inputName==='string'?document.getElementById(inputName):inputName;const val=inp.value.trim();if(val.length<2){document.getElementById(hiddenId).value='';document.getElementById(hiddenNum).value='';if(hiddenProg)document.getElementById(hiddenProg).value='';if(hiddenYear)document.getElementById(hiddenYear).value='';return;}fetch('sickbay.php?action=search_student&q='+encodeURIComponent(val)).then(r=>r.json()).then(data=>{if(data.length===1){const s=data[0];inp.value=s.full_name;document.getElementById(hiddenId).value=s.id;document.getElementById(hiddenNum).value=s.student_number||s.student_id||'';if(hiddenProg)document.getElementById(hiddenProg).value=s.program||'';if(hiddenYear)document.getElementById(hiddenYear).value=s.year_of_study||'';}else if(data.length>1){let opts=data.map(s=>s.full_name+' ('+(s.student_number||s.student_id||'')+')').join('\n');}}).catch(function(e){ console.warn('[ISNM] Student search failed:', e); });}
function editSickness(id,code,name,category,symp,desc,treat,cont,status){document.getElementById('ed-sk-id').value=id;document.getElementById('ed-sk-code').value=code;document.getElementById('ed-sk-name').value=name;document.getElementById('ed-sk-cat').value=category;document.getElementById('ed-sk-symp').value=symp;document.getElementById('ed-sk-desc').value=desc;document.getElementById('ed-sk-treat').value=treat;document.getElementById('ed-sk-cont').checked=cont;document.getElementById('ed-sk-stat').value=status;document.getElementById('sec-sickness').scrollIntoView({behavior:'smooth'});}
function resetSickness(){document.getElementById('ed-sk-id').value='0';document.querySelectorAll('#sec-sickness form')[0].reset();}
function editLeave(id,name,number,program,year,start,end,bed,rec){document.getElementById('ed-lv-id').value=id;document.getElementById('lv-name').value=name;document.getElementById('lv-num').value=number;document.getElementById('lv-prog').value=program;document.getElementById('lv-year').value=year;document.querySelector('[name="start_date"]').value=start;document.querySelector('[name="end_date"]').value=end;document.querySelector('[name="bed_rest_required"]').value=bed;document.querySelector('[name="recommendations"]').value=rec;document.getElementById('sec-leave').scrollIntoView({behavior:'smooth'});}
function resetLeave(){document.getElementById('ed-lv-id').value='0';document.querySelectorAll('#sec-leave form')[0].reset();}
function editVisit(id,name,date,symptoms,diagnosis,treatment,medication,status,fud,notes){document.getElementById('ed-v-id').value=id;document.getElementById('vb-name').value=name;document.querySelector('[name="visit_date"]').value=date;document.getElementById('ed-v-symp').value=symptoms;document.getElementById('ed-v-diag').value=diagnosis;document.getElementById('ed-v-treat').value=treatment;document.getElementById('ed-v-med').value=medication;document.getElementById('ed-v-stat').value=status;document.getElementById('ed-v-fud').value=fud||'';document.getElementById('ed-v-notes').value=notes;document.getElementById('visit-action').value='update_visit';document.getElementById('visit-submit-btn').innerHTML='<i class="fas fa-save me-1"></i>Update Visit';document.getElementById('sec-visits').scrollIntoView({behavior:'smooth'});}
function resetVisitForm(){document.getElementById('ed-v-id').value='0';document.getElementById('visit-action').value='add_visit';document.getElementById('visit-submit-btn').innerHTML='<i class="fas fa-save me-1"></i>Add Visit';document.getElementById('visit-form').reset();}
function editSbMed(id,name,category,qty,unit,exp,rol){document.getElementById('ed-sm-id').value=id;document.getElementById('ed-sm-name').value=name;document.getElementById('ed-sm-cat').value=category;document.getElementById('ed-sm-qty').value=qty;document.getElementById('ed-sm-unit').value=unit;document.getElementById('ed-sm-exp').value=exp||'';document.getElementById('ed-sm-rol').value=rol;document.getElementById('sb-med-action').value='update_medicine';document.getElementById('sb-med-submit-btn').innerHTML='<i class="fas fa-save me-1"></i>Update Medicine';document.getElementById('sec-visits').scrollIntoView({behavior:'smooth'});}
function resetSbMedForm(){document.getElementById('ed-sm-id').value='0';document.getElementById('sb-med-action').value='add_medicine';document.getElementById('sb-med-submit-btn').innerHTML='<i class="fas fa-save me-1"></i>Add Medicine';document.getElementById('sb-med-form').reset();}
function viewTransactions(id,name){fetch('sickbay.php?action=get_transactions&id='+id).then(r=>r.text()).then(html=>{const w=window.open('','_blank','width=700,height=600');w.document.write('<html><head><title>Transactions: '+name+'</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"></head><body class="p-4"><h4>'+name+' - Stock Transactions</h4>'+html+'<hr><button class="btn btn-sm btn-secondary" onclick="window.close()">Close</button>
</body></html>');}).catch(()=>alert('Could not load transactions.'));}
document.addEventListener('DOMContentLoaded',function(){['sr-name','lv-name','hr-name','hi-name','vb-name'].forEach(function(id){var el=document.getElementById(id);if(!el)return;var map={sr:{sid:'sr-sid',num:'sr-num',prog:'sr-prog',year:'sr-year'},lv:{sid:'lv-sid',num:'lv-num',prog:'lv-prog',year:'lv-year'},hr:{sid:'hr-sid',num:'hr-num'},hi:{sid:'hi-sid',num:'hi-num'},vb:{sid:'vb-sid',num:'vb-num'}};var pfx=id.split('-')[0];var m=map[pfx];if(!m)return;el.addEventListener('blur',function(){searchStudents(el,m.sid,m.num,m.prog,m.year);});});});
</script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>