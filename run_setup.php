<?php
/**
 * ISNM FINAL SETUP - One-click installation
 * Fixes #1813 tablespace errors by cleaning old database folder
 * 
 * USAGE:
 * 1. Stop MySQL in XAMPP Control Panel FIRST
 * 2. Open this page in browser: http://localhost/ISNM/run_setup.php
 * 3. Click the button to run setup
 * 4. Start MySQL again in XAMPP Control Panel
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

$dbName = 'igangaschoolofl_staffs_db';
$dataDir = 'C:\\xampp\\mysql\\data\\' . $dbName;
$setupDone = false;
$errorMsg = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_setup'])) {
    
    // Step 1: Try to delete old database folder
    echo "<h3>Step 1: Cleaning old database files...</h3>\n";
    if (is_dir($dataDir)) {
        // Try using Windows rmdir command
        $cmd = 'rmdir /s /q ' . escapeshellarg($dataDir);
        exec($cmd, $output, $returnCode);
        
        if (file_exists($dataDir)) {
            // Try recursive delete with PHP
            function deleteDir($dir) {
                if (!file_exists($dir)) return true;
                if (!is_dir($dir)) return unlink($dir);
                foreach (scandir($dir) as $item) {
                    if ($item === '.' || $item === '..') continue;
                    if (!deleteDir($dir . DIRECTORY_SEPARATOR . $item)) return false;
                }
                return rmdir($dir);
            }
            if (deleteDir($dataDir)) {
                echo "<p style='color: #0f0;'>Deleted old database folder.</p>\n";
            } else {
                $errorMsg = "Cannot delete $dataDir. Please STOP MySQL in XAMPP Control Panel first, then refresh this page.";
            }
        } else {
            echo "<p style='color: #0f0;'>Deleted old database folder.</p>\n";
        }
    } else {
        echo "<p>No old folder found, starting fresh.</p>\n";
    }
    
    if (!$errorMsg) {
        // Step 2: Create database and tables
        echo "<h3>Step 2: Creating database and tables...</h3>\n";
        
        $conn = new mysqli('localhost', 'root', '', 'mysql');
        if ($conn->connect_error) {
            $errorMsg = "Cannot connect to MySQL: " . $conn->connect_error . ". Make sure MySQL is STARTED in XAMPP.";
        } else {
            // Create database
            if (!$conn->query("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
                $errorMsg = "Error creating database: " . $conn->error;
            } else {
                $conn->select_db($dbName);
                
                // Create tables
                $tables = [
                    "CREATE TABLE IF NOT EXISTS staff_roles (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        role_name VARCHAR(100) UNIQUE NOT NULL,
                        dashboard_path VARCHAR(255)
                    )",
                    "CREATE TABLE IF NOT EXISTS staff (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        staff_id VARCHAR(50) UNIQUE,
                        email VARCHAR(150) UNIQUE NOT NULL,
                        `password` VARCHAR(255) NOT NULL,
                        role_id INT,
                        FOREIGN KEY(role_id) REFERENCES staff_roles(id)
                    )"
                ];
                
                foreach ($tables as $sql) {
                    if (!$conn->query($sql)) {
                        $errorMsg = "Error creating table: " . $conn->error;
                        break;
                    }
                }
                
                if (!$errorMsg) {
                    // Insert roles
                    $stmt = $conn->prepare("INSERT IGNORE INTO staff_roles(role_name, dashboard_path) VALUES (?, ?)");
                    $roles = [
                        ['Director ICT', 'dashboards/director-ict.php'],
                        ['Director General', 'dashboards/director-general.php'],
                        ['CEO', 'dashboards/ceo.php'],
                        ['Director Academics', 'dashboards/director-academics.php'],
                        ['Director Finance', 'dashboards/director-finance.php'],
                        ['School Principal', 'dashboards/school-principal.php'],
                        ['Deputy Principal', 'dashboards/deputy-principal.php'],
                        ['Academic Registrar', 'dashboards/academic-registrar.php'],
                        ['HR Manager', 'dashboards/hr-manager.php'],
                        ['School Secretary', 'dashboards/school-secretary.php'],
                        ['School Librarian', 'dashboards/school-librarian.php'],
                        ['Head Nursing', 'dashboards/head-nursing.php'],
                        ['Head Midwifery', 'dashboards/head-midwifery.php'],
                        ['Senior Lecturers', 'dashboards/senior-lecturers.php'],
                        ['Lecturers', 'dashboards/lecturers.php'],
                        ['Matrons', 'dashboards/matrons.php'],
                        ['Wardens', 'dashboards/wardens.php'],
                        ['Sickbay', 'dashboards/sickbay.php'],
                        ['Drivers', 'dashboards/drivers.php'],
                        ['Security', 'dashboards/security.php'],
                        ['Store Keeper', 'dashboards/storekeeper.php'],
                        ['Guild President', 'dashboards/guild-president.php'],
                        ['Non-Teaching Staff', 'dashboards/non-teaching-staff.php'],
                        ['Computer Lab Manager', 'computer_lab.php'],
                        ['Director Admissions & Requirements', 'dashboards/director-admissions.php'],
                    ];
                    foreach ($roles as [$name, $path]) {
                        $stmt->bind_param('ss', $name, $path);
                        $stmt->execute();
                    }
                    $stmt->close();
                    
                    // Insert staff
                    $staff = [
                        ['ICT001', 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug', 'Techno123', 'Director ICT'],
                        ['DG001', 'directorgeneral@igangaschoolofnursingandmidwifery.ac.ug', 'DorisJoy2026', 'Director General'],
                        ['CEO001', 'ceo@igangaschoolofnursingandmidwifery.ac.ug', 'Lovely2God', 'CEO'],
                        ['DA001', 'directoracademic@igangaschoolofnursingandmidwifery.ac.ug', 'Stephen123', 'Director Academics'],
                        ['FIN001', 'finance@igangaschoolofnursingandmidwifery.ac.ug', 'DorisJoy2026', 'Director Finance'],
                        ['PR001', 'principal@igangaschoolofnursingandmidwifery.ac.ug', 'isnm2026', 'School Principal'],
                        ['DP001', 'dep-principal@igangaschoolofnursingandmidwifery.ac.ug', 'Isnm2026', 'Deputy Principal'],
                        ['REG001', 'academicregistrar@igangaschoolofnursingandmidwifery.ac.ug', 'Lovely2God', 'Academic Registrar'],
                        ['HR001', 'hr-manager@igangaschoolofnursingandmidwifery.ac.ug', 'Alexis2026', 'HR Manager'],
                        ['SEC001', 'secretary@igangaschoolofnursingandmidwifery.ac.ug', 'Lovely2God', 'School Secretary'],
                        ['LIB001', 'library@igangaschoolofnursingandmidwifery.ac.ug', 'isnm2026', 'School Librarian'],
                        ['NUR001', 'nursing-dep@igangaschoolofnursingandmidwifery.ac.ug', 'isnm4life', 'Head Nursing'],
                        ['MID001', 'midwifery-dep@igangaschoolofnursingandmidwifery.ac.ug', 'Life2save', 'Head Midwifery'],
                        ['SLE001', 'senior-lecturers@igangaschoolofnursingandmidwifery.ac.ug', 'isnm2026', 'Senior Lecturers'],
                        ['LEC001', 'lecturers@igangaschoolofnursingandmidwifery.ac.ug', 'Isnm4life', 'Lecturers'],
                        ['MAT001', 'matron@igangaschoolofnursingandmidwifery.ac.ug', 'Isnm2026', 'Matrons'],
                        ['WD001', 'warden@igangaschoolofnursingandmidwifery.ac.ug', 'Lovely2God', 'Wardens'],
                        ['SICK001', 'sickbay@igangaschoolofnursingandmidwifery.ac.ug', 'isnm2026', 'Sickbay'],
                        ['DRV001', 'drivers@igangaschoolofnursingandmidwifery.ac.ug', 'isnm4life', 'Drivers'],
                        ['SEC002', 'security@igangaschoolofnursingandmidwifery.ac.ug', 'safty1st', 'Security'],
                        ['STK001', 'store@igangaschoolofnursingandmidwifery.ac.ug', 'Isnm4life', 'Store Keeper'],
                        ['GUILD001', 'guildpresident@igangaschoolofnursingandmidwifery.ac.ug', 'isnm4life', 'Guild President'],
                        ['AD001', 'admissions@igangaschoolofnursingandmidwifery.ac.ug', '2268926931', 'Director Admissions & Requirements'],
                        ['ICT002', 'dannybict@igangaschoolofnursingandmidwifery.ac.ug', 'Lovely2God', 'Director ICT'],
                        ['BUR001', 'bursar@igangaschoolofnursingandmidwifery.ac.ug', 'DorisJoy2026', 'School Bursar'],
                        ['NTS001', 'nonteaching@isnm.ac.ug', 'DorisJoy2026', 'Non-Teaching Staff'],
                    ];
                    
                    $stmt = $conn->prepare("INSERT IGNORE INTO staff(staff_id, email, `password`, role_id) VALUES (?, ?, ?, (SELECT id FROM staff_roles WHERE role_name = ?))");
                    $count = 0;
                    foreach ($staff as [$sid, $email, $pass, $role]) {
                        if ($stmt->execute()) {
                            $count++;
                        }
                    }
                    $stmt->close();
                    
                    echo "<p style='color: #0f0;'>$count staff accounts created.</p>\n";
                    $setupDone = true;
                }
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
    <title>ISNM Setup</title>
    <style>
        body { background: #1a1a2e; color: #eee; font-family: 'Courier New', monospace; padding: 20px; }
        .box { background: #16213e; padding: 25px; border-radius: 10px; max-width: 700px; margin: 0 auto; border: 1px solid #0f3460; }
        h1 { color: #e94560; }
        h3 { color: #0ea5e9; }
        .btn { background: #e94560; color: white; border: none; padding: 12px 30px; font-size: 16px; border-radius: 6px; cursor: pointer; font-family: inherit; }
        .btn:hover { background: #c73650; }
        a { color: #0ea5e9; }
        ul { line-height: 1.8; }
        .success { color: #22c55e; }
        .error { color: #ef4444; }
    </style>
</head>
<body>
    <div class="box">
        <h1>ISNM Login Database Setup</h1>
        
        <?php if ($setupDone): ?>
            <h3 class="success">SUCCESS!</h3>
            <p class="success">Database created and all login accounts are ready.</p>
            <p><strong>Next steps:</strong></p>
            <ol>
                <li>Make sure MySQL is <strong>STARTED</strong> in XAMPP Control Panel</li>
                <li>Go to <a href="staff-login.php">staff-login.php</a> and login with any email/password below</li>
            </ol>
            <h3>All Login Credentials:</h3>
            <ul>
                <?php foreach ($staff as [$sid, $email, $pass, $role]): ?>
                    <li><strong><?php echo htmlspecialchars($email); ?></strong> / <?php echo htmlspecialchars($pass); ?></li>
                <?php endforeach; ?>
            </ul>
            <p><a href="staff-login.php" class="btn">Go to Staff Login</a></p>
        
        <?php elseif ($errorMsg): ?>
            <h3 class="error">Error</h3>
            <p class="error"><?php echo htmlspecialchars($errorMsg); ?></p>
            <p>After fixing, <a href="">refresh this page</a>.</p>
        
        <?php else: ?>
            <h3>Instructions:</h3>
            <ol>
                <li><strong>STOP MySQL</strong> in XAMPP Control Panel (click Stop next to MySQL)</li>
                <li>Click the button below</li>
                <li><strong>START MySQL</strong> again in XAMPP Control Panel</li>
                <li>Login at <a href="staff-login.php">staff-login.php</a></li>
            </ol>
            <br>
            <form method="POST">
                <button type="submit" name="run_setup" value="1" class="btn">Run Setup (creates DB + all accounts)</button>
            </form>
            <br>
            <p><small>This will delete old database files and create fresh ones with correct passwords.</small></p>
        <?php endif; ?>
    </div>
</body>
</html>
