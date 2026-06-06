<?php
/**
 * ISNM FORCE SETUP - Handles #1813 tablespace errors
 * 
 * INSTRUCTIONS:
 * 1. STOP MySQL in XAMPP Control Panel FIRST
 * 2. Open browser: http://localhost/ISNM/force_setup.php
 * 3. Click "FIX AND SETUP EVERYTHING"
 * 4. START MySQL again in XAMPP Control Panel
 * 5. Login at staff-login.php
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

$dbName = 'igangaschoolofl_staffs_db';
$dataDir = 'C:\\xampp\\mysql\\data\\' . $dbName;
$setupDone = false;
$logs = [];
$errorMsg = '';

function addLog($msg, $type = 'info') {
    global $logs;
    $logs[] = ['msg' => $msg, 'type' => $type];
}
function deleteDir($dir) {
    if (!file_exists($dir)) return true;
    if (!is_dir($dir)) return unlink($dir);
    foreach (scandir($dir) as $item) {
        if ($item === '.' || $item === '..') continue;
        if (!deleteDir($dir . DIRECTORY_SEPARATOR . $item)) return false;
    }
    return rmdir($dir);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fix_setup'])) {

    // STEP 1: Try EVERY method to delete old folder
    addLog("STEP 1: Attempting to delete old database folder...", "info");

    if (is_dir($dataDir) || file_exists($dataDir)) {
        // Method 1: Windows rmdir command
        exec('rmdir /s /q ' . escapeshellarg($dataDir), $out1, $rc1);
        addLog("Method 1 (rmdir): " . ($rc1 === 0 ? "SUCCESS" : "FAILED (code $rc1)"), $rc1 === 0 ? "success" : "warn");

        // Method 2: PHP recursive delete
        if (file_exists($dataDir)) {
            $rc2 = deleteDir($dataDir) ? 0 : 1;
            addLog("Method 2 (PHP deleteDir): " . ($rc2 === 0 ? "SUCCESS" : "FAILED"), $rc2 === 0 ? "success" : "warn");
        }

        // Method 3: Force delete all files individually
        if (file_exists($dataDir)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dataDir, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($files as $file) {
                $file->isDir() ? @rmdir($file->getPathname()) : @unlink($file->getPathname());
            }
            @rmdir($dataDir);
            addLog("Method 3 (force delete files): " . (file_exists($dataDir) ? "FAILED" : "SUCCESS"), file_exists($dataDir) ? "warn" : "success");
        }

        if (file_exists($dataDir)) {
            $errorMsg = "CRITICAL: Cannot delete $dataDir — folder still exists.<br><br>"
                . "<strong>MANUAL STEPS REQUIRED:</strong><br>"
                . "1. STOP MySQL in XAMPP Control Panel<br>"
                . "2. Open File Explorer, go to: C:\\xampp\\mysql\\data\\<br>"
                . "3. Delete the folder: <strong>igangaschoolofl_staffs_db</strong><br>"
                . "4. Refresh this page and try again";
        } else {
            addLog("Old database folder DELETED successfully!", "success");
        }
    } else {
        addLog("No old folder found — starting fresh.", "info");
    }

    if (!$errorMsg) {
        // STEP 2: Create database
        addLog("STEP 2: Creating database and tables...", "info");
        $conn = @new mysqli('localhost', 'root', '', 'mysql');
        if ($conn->connect_error) {
            $errorMsg = "Cannot connect to MySQL: " . $conn->connect_error
                . "<br><br>Make sure MySQL is STARTED in XAMPP Control Panel, then <a href=''>refresh</a>.";
        } else {
            // Drop database completely if it exists (safe now that folder is gone)
            $conn->query("DROP DATABASE IF EXISTS `$dbName`");
            if (!$conn->query("CREATE DATABASE `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
                $errorMsg = "Error creating database: " . $conn->error;
            } else {
                $conn->select_db($dbName);
                addLog("Database '$dbName' created.", "success");

                // Create tables
                $sqls = [
                    "CREATE TABLE staff_roles (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        role_name VARCHAR(100) UNIQUE NOT NULL,
                        role_description TEXT,
                        role_level ENUM('Executive','Management','Academic','Support','Administrative','Student') DEFAULT 'Academic',
                        dashboard_path VARCHAR(255),
                        permissions JSON,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    )",
                    "CREATE TABLE IF NOT EXISTS staff (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        staff_id VARCHAR(50) UNIQUE,
                        email VARCHAR(150) UNIQUE NOT NULL,
                        `password` VARCHAR(255) NOT NULL,
                        role_id INT,
                        FOREIGN KEY(role_id) REFERENCES staff_roles(id)
                    )",
                ];

                foreach ($sqls as $sql) {
                    if (!$conn->query($sql)) {
                        $errorMsg = "Table error: " . $conn->error;
                        break;
                    }
                }
                addLog("Tables created: staff_roles, staff", "success");

                // Insert roles
                $rolesStmt = $conn->prepare("INSERT IGNORE INTO staff_roles(role_name, dashboard_path) VALUES (?, ?)");
                $roles = [
                    ['Director ICT','dashboards/director-ict.php'],
                    ['Director General','dashboards/director-general.php'],
                    ['CEO','dashboards/ceo.php'],
                    ['Director Academics','dashboards/director-academics.php'],
                    ['Director Finance','dashboards/director-finance.php'],
                    ['School Principal','dashboards/school-principal.php'],
                    ['Deputy Principal','dashboards/deputy-principal.php'],
                    ['Academic Registrar','dashboards/academic-registrar.php'],
                    ['HR Manager','dashboards/hr-manager.php'],
                    ['School Secretary','dashboards/school-secretary.php'],
                    ['School Librarian','dashboards/school-librarian.php'],
                    ['Head Nursing','dashboards/head-nursing.php'],
                    ['Head Midwifery','dashboards/head-midwifery.php'],
                    ['Senior Lecturers','dashboards/senior-lecturers.php'],
                    ['Lecturers','dashboards/lecturers.php'],
                    ['Matrons','dashboards/matrons.php'],
                    ['Wardens','dashboards/wardens.php'],
                    ['Sickbay','dashboards/sickbay.php'],
                    ['Drivers','dashboards/drivers.php'],
                    ['Security','dashboards/security.php'],
                    ['Store Keeper','dashboards/storekeeper.php'],
                    ['Guild President','dashboards/guild-president.php'],
                    ['Director Admissions & Requirements','dashboards/director-admissions.php'],
                    ['Non-Teaching Staff','dashboards/non-teaching-staff.php'],
                    ['Computer Lab Manager','computer_lab.php'],
                ];
                foreach ($roles as [$n, $p]) { $rolesStmt->bind_param('ss', $n, $p); $rolesStmt->execute(); }
                $rolesStmt->close();
                addLog(count($roles) . " roles inserted.", "success");

                // Insert staff
                $staff = [
                    ['ICT001','computer-lab@igangaschoolofnursingandmidwifery.ac.ug','Techno123','Director ICT'],
                    ['DG001','directorgeneral@igangaschoolofnursingandmidwifery.ac.ug','DorisJoy2026','Director General'],
                    ['CEO001','ceo@igangaschoolofnursingandmidwifery.ac.ug','Lovely2God','CEO'],
                    ['DA001','directoracademic@igangaschoolofnursingandmidwifery.ac.ug','Stephen123','Director Academics'],
                    ['FIN001','finance@igangaschoolofnursingandmidwifery.ac.ug','DorisJoy2026','Director Finance'],
                    ['PR001','principal@igangaschoolofnursingandmidwifery.ac.ug','isnm2026','School Principal'],
                    ['DP001','dep-principal@igangaschoolofnursingandmidwifery.ac.ug','Isnm2026','Deputy Principal'],
                    ['REG001','academicregistrar@igangaschoolofnursingandmidwifery.ac.ug','Lovely2God','Academic Registrar'],
                    ['HR001','hr-manager@igangaschoolofnursingandmidwifery.ac.ug','Alexis2026','HR Manager'],
                    ['SEC001','secretary@igangaschoolofnursingandmidwifery.ac.ug','Lovely2God','School Secretary'],
                    ['LIB001','library@igangaschoolofnursingandmidwifery.ac.ug','isnm2026','School Librarian'],
                    ['NUR001','nursing-dep@igangaschoolofnursingandmidwifery.ac.ug','isnm4life','Head Nursing'],
                    ['MID001','midwifery-dep@igangaschoolofnursingandmidwifery.ac.ug','Life2save','Head Midwifery'],
                    ['SLE001','senior-lecturers@igangaschoolofnursingandmidwifery.ac.ug','isnm2026','Senior Lecturers'],
                    ['LEC001','lecturers@igangaschoolofnursingandmidwifery.ac.ug','Isnm4life','Lecturers'],
                    ['MAT001','matron@igangaschoolofnursingandmidwifery.ac.ug','Isnm2026','Matrons'],
                    ['WD001','warden@igangaschoolofnursingandmidwifery.ac.ug','Lovely2God','Wardens'],
                    ['SICK001','sickbay@igangaschoolofnursingandmidwifery.ac.ug','isnm2026','Sickbay'],
                    ['DRV001','drivers@igangaschoolofnursingandmidwifery.ac.ug','isnm4life','Drivers'],
                    ['SEC002','security@igangaschoolofnursingandmidwifery.ac.ug','safty1st','Security'],
                    ['STK001','store@igangaschoolofnursingandmidwifery.ac.ug','Isnm4life','Store Keeper'],
                    ['GUILD001','guildpresident@igangaschoolofnursingandmidwifery.ac.ug','isnm4life','Guild President'],
                    ['AD001','admissions@igangaschoolofnursingandmidwifery.ac.ug','2268926931','Director Admissions & Requirements'],
                    ['ICT002','dannybict@igangaschoolofnursingandmidwifery.ac.ug','Lovely2God','Director ICT'],
                    ['BUR001','bursar@igangaschoolofnursingandmidwifery.ac.ug','DorisJoy2026','School Bursar'],
                    ['NTS001','nonteaching@isnm.ac.ug','DorisJoy2026','Non-Teaching Staff'],
                ];

                $stmt = $conn->prepare("INSERT IGNORE INTO staff(staff_id, email, `password`, role_id) VALUES (?, ?, ?, (SELECT id FROM staff_roles WHERE role_name = ?))");
                $count = 0;
                foreach ($staff as [$sid, $email, $pass, $role]) {
                    if ($stmt->execute()) $count++;
                }
                $stmt->close();
                addLog("$count staff accounts inserted.", "success");

                $rc = $conn->query("SELECT COUNT(*) AS c FROM staff_roles");
                $rolesCount = $rc->fetch_assoc()['c'] ?? 0;
                $rc = $conn->query("SELECT COUNT(*) AS c FROM staff");
                $staffCount = $rc->fetch_assoc()['c'] ?? 0;

                addLog("VERIFICATION: $rolesCount roles, $staffCount staff in database.", "success");
                $setupDone = true;
            }
            $conn->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ISNM Force Setup</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: #0f172a; color: #e2e8f0; font-family: 'Courier New', monospace; padding: 20px; min-height: 100vh; }
        .box { background: #1e293b; padding: 30px; border-radius: 12px; max-width: 750px; margin: 0 auto; border: 2px solid #334155; }
        h1 { color: #f87171; margin-bottom: 20px; font-size: 1.5rem; }
        h2 { color: #38bdf8; font-size: 1.1rem; margin: 15px 0 8px; }
        .log { background: #0f172a; padding: 12px; border-radius: 6px; margin: 6px 0; font-size: 0.9rem; border-left: 3px solid #475569; }
        .log.success { border-left-color: #22c55e; color: #86efac; }
        .log.warn { border-left-color: #f59e0b; color: #fcd34d; }
        .log.error { border-left-color: #ef4444; color: #fca5a5; }
        .log.info { border-left-color: #3b82f6; color: #93c5fd; }
        .btn { background: #ef4444; color: white; border: none; padding: 14px 40px; font-size: 16px; border-radius: 8px; cursor: pointer; font-family: inherit; font-weight: bold; display: block; margin: 20px auto; }
        .btn:hover { background: #dc2626; }
        .btn-green { background: #22c55e; }
        .btn-green:hover { background: #16a34a; }
        ol { margin: 10px 0 10px 20px; line-height: 2; }
        a { color: #38bdf8; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .success-box { background: #14532d; border: 2px solid #22c55e; padding: 20px; border-radius: 10px; margin-top: 20px; }
        .success-box h2 { color: #4ade80; }
        .error-box { background: #450a0a; border: 2px solid #ef4444; padding: 20px; border-radius: 10px; margin-top: 20px; }
        .error-box h2 { color: #fca5a5; }
        .creds { background: #0f172a; padding: 10px 15px; border-radius: 6px; margin: 4px 0; font-size: 0.85rem; }
    </style>
</head>
<body>
    <div class="box">
        <h1>ISNM Force Setup — Fixes #1813 Tablespace Error</h1>

        <?php if ($setupDone): ?>
            <div class="success-box">
                <h2>SUCCESS! Database is ready.</h2>
                <p>Roles: <strong><?php echo $rolesCount ?? 0; ?></strong> | Staff: <strong><?php echo $staffCount ?? 0; ?></strong></p>
                <h2>Login Credentials (email / password):</h2>
                <?php foreach ($staff ?? [] as [$sid, $email, $pass, $role]): ?>
                    <div class="creds"><?php echo htmlspecialchars($email); ?> / <strong><?php echo htmlspecialchars($pass); ?></strong></div>
                <?php endforeach; ?>
                <br>
                <a href="staff-login.php" class="btn btn-green">Go to Staff Login →</a>
            </div>

        <?php elseif ($errorMsg): ?>
            <div class="error-box">
                <h2>Action Required</h2>
                <p><?php echo $errorMsg; ?></p>
            </div>
            <br>
            <a href="">Refresh this page after fixing</a>

        <?php else: ?>
            <h2>Steps to fix #1813 tablespace error:</h2>
            <ol>
                <li><strong>STOP MySQL</strong> in XAMPP Control Panel (click Stop button)</li>
                <li>Click the red button below</li>
                <li><strong>START MySQL</strong> again in XAMPP Control Panel</li>
                <li>Login at <a href="staff-login.php">staff-login.php</a></li>
            </ol>
            <br>
            <form method="POST">
                <button type="submit" name="fix_setup" value="1" class="btn">FIX AND SETUP EVERYTHING</button>
            </form>
            <p style="text-align:center; margin-top:10px; color:#94a3b8;">
                This deletes old corrupted files and creates fresh DB with all login accounts.
            </p>
        <?php endif; ?>

        <?php if (!empty($logs)): ?>
            <h2 style="margin-top:30px;">Setup Log:</h2>
            <?php foreach ($logs as $log): ?>
                <div class="log <?php echo $log['type']; ?>">
                    <?php echo htmlspecialchars($log['msg']); ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
