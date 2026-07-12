<?php
/**
 * ISNM PRODUCTION SETUP â€” self-contained, no includes needed
 * Run ONCE from browser, then DELETE this file.
 */
error_reporting(E_ERROR | E_PARSE);
echo "<!DOCTYPE html><html><head><title>ISNM Setup</title>";
echo "<style>body{font-family:monospace;padding:20px;background:#1e293b;color:#e2e8f0;}";
echo "h1{color:#f59e0b} .ok{color:#10b981} .fail{color:#ef4444} .info{color:#60a5fa}</style></head><body>";
echo "<h1>ISNM Production Setup</h1>";

// â”€â”€ Connect using config â”€â”€
require_once __DIR__ . '/config/database.php';
$conn = getStaffConnection();
if (!$conn) {
    die("<p class='fail'>FATAL: Cannot connect to staff database. Check that the database exists in your hosting control panel.</p>");
}
$conn->set_charset('utf8mb4');
echo "<p class='ok'>Connected to staff database.</p>";

// â”€â”€ Step 1: Create staff table â”€â”€
echo "<h2>Step 1: staff table</h2>";
$r = $conn->query("SHOW TABLES LIKE 'staff'");
if ($r && $r->num_rows > 0) {
    echo "<p class='ok'>Already exists.</p>";
} else {
    $conn->query("CREATE TABLE IF NOT EXISTS `staff` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `staff_id` VARCHAR(50) DEFAULT NULL,
        `full_name` VARCHAR(255) NOT NULL,
        `email` VARCHAR(255) NOT NULL,
        `password` VARCHAR(255) NOT NULL,
        `position` VARCHAR(255) DEFAULT NULL,
        `department` VARCHAR(255) DEFAULT NULL,
        `role_id` INT DEFAULT 0,
        `phone` VARCHAR(50) DEFAULT NULL,
        `profile_picture` VARCHAR(500) DEFAULT NULL,
        `status` VARCHAR(50) DEFAULT 'Active',
        `hire_date` DATE DEFAULT NULL,
        `login_attempts` INT DEFAULT 0,
        `locked_until` DATETIME DEFAULT NULL,
        `last_login` DATETIME DEFAULT NULL,
        `password_changed` TINYINT(1) DEFAULT 0,
        `is_first_login` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY `unique_email` (`email`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "<p class='ok'>Created.</p>";
}

// â”€â”€ Step 2: Insert staff records â”€â”€
echo "<h2>Step 2: staff records</h2>";
$cnt = $conn->query("SELECT COUNT(*) as c FROM staff")->fetch_assoc()['c'];
echo "<p class='info'>Current: $cnt</p>";
if ($cnt == 0) {
    $placeholder = password_hash('x', PASSWORD_BCRYPT);
    $staff = [
        ['computer-lab@igangaschoolofnursingandmidwifery.ac.ug','Computer Lab Manager','Computer Lab Manager','ICT','Computer Lab Manager','CLB-001'],
        ['directorgeneral@igangaschoolofnursingandmidwifery.ac.ug','Director General','Director General','Executive','Director General','DG-001'],
        ['ceo@igangaschoolofnursingandmidwifery.ac.ug','Chief Executive Officer','CEO','Executive','CEO','CEO-001'],
        ['directoracademic@igangaschoolofnursingandmidwifery.ac.ug','Director Academics','Director Academics','Academic Affairs','Director Academics','DA-001'],
        ['finance@igangaschoolofnursingandmidwifery.ac.ug','Director Finance','Director Finance','Finance','Director Finance','DF-001'],
        ['principal@igangaschoolofnursingandmidwifery.ac.ug','School Principal','School Principal','Administration','School Principal','PRIN-001'],
        ['dep-principal@igangaschoolofnursingandmidwifery.ac.ug','Deputy Principal','Deputy Principal','Administration','Deputy Principal','DP-001'],
        ['academicregistrar@igangaschoolofnursingandmidwifery.ac.ug','Academic Registrar','Academic Registrar','Academic Registrar','Academic Registrar','AR-001'],
        ['hr-manager@igangaschoolofnursingandmidwifery.ac.ug','HR Manager','HR Manager','Human Resources','HR Manager','HR-001'],
        ['secretary@igangaschoolofnursingandmidwifery.ac.ug','School Secretary','School Secretary','Administration','School Secretary','SEC-001'],
        ['library@igangaschoolofnursingandmidwifery.ac.ug','School Librarian','School Librarian','Library','School Librarian','LIB-001'],
        ['nursing-dep@igangaschoolofnursingandmidwifery.ac.ug','Head of Nursing','Head of Nursing','Nursing','Head Nursing','NUR-001'],
        ['midwifery-dep@igangaschoolofnursingandmidwifery.ac.ug','Head of Midwifery','Head of Midwifery','Midwifery','Head Midwifery','MID-001'],
        ['senior-lecturers@igangaschoolofnursingandmidwifery.ac.ug','Senior Lecturer','Senior Lecturer','Academic Affairs','Senior Lecturer','SL-001'],
        ['lecturers@igangaschoolofnursingandmidwifery.ac.ug','Lecturer','Lecturer','Academic Affairs','Lecturer','LEC-001'],
        ['matron@igangaschoolofnursingandmidwifery.ac.ug','Matron','Matron','Student Welfare','Matron','MAT-001'],
        ['warden@igangaschoolofnursingandmidwifery.ac.ug','Warden','Warden','Student Welfare','Warden','WAR-001'],
        ['sickbay@igangaschoolofnursingandmidwifery.ac.ug','Sickbay Nurse','Sickbay Nurse','Student Welfare','Sickbay','SKB-001'],
        ['drivers@igangaschoolofnursingandmidwifery.ac.ug','Driver','Driver','Transport','Driver','DRV-001'],
        ['security@igangaschoolofnursingandmidwifery.ac.ug','Security Officer','Security Officer','Security','Security','SEC-001'],
        ['store@igangaschoolofnursingandmidwifery.ac.ug','Storekeeper','Storekeeper','Store','Storekeeper','STO-001'],
        ['guildpresident@igangaschoolofnursingandmidwifery.ac.ug','Guild President','Guild President','Student Government','Guild President','G-001'],
        ['admissions@igangaschoolofnursingandmidwifery.ac.ug','Director Admissions','Director Admissions','Admissions','Director Admissions','ADM-001'],
        ['dannybict@igangaschoolofnursingandmidwifery.ac.ug','Director ICT','Director ICT','ICT','Director ICT','ICT-001'],
        ['skills-lab@igangaschoolofnursingandmidwifery.ac.ug','Skills Lab Technician','Skills Lab Technician','Skills Laboratory','Skills Lab','SKL-001'],
        ['bursar@igangaschoolofnursingandmidwifery.ac.ug','School Bursar','School Bursar','Finance','School Bursar','BUR-001'],
        ['admissions-req@igangaschoolofnursingandmidwifery.ac.ug','Director Admissions & Requirements','Director Admissions','Admissions','Director Admissions','ADM-002'],
        ['directorict@igangaschoolofnursingandmidwifery.ac.ug','Director ICT (Alt)','Director ICT','ICT','Director ICT','ICT-002'],
        ['computerlab@igangaschoolofnursingandmidwifery.ac.ug','Computer Lab Manager','Computer Lab Manager','ICT','Computer Lab Manager','CLB-002'],
        ['skillslab@igangaschoolofnursingandmidwifery.ac.ug','Skills Lab Manager','Skills Lab Manager','Skills Laboratory','Skills Lab','SKL-002'],
    ];
    $ins = $conn->prepare("INSERT IGNORE INTO staff (email,full_name,position,department,role_id,staff_id,password,status,hire_date) VALUES (?,?,?,?,0,?,?,'Active',CURDATE())");
    $n = 0;
    foreach ($staff as $s) {
        $ins->bind_param('ssssss', $s[0],$s[1],$s[2],$s[3],$s[5],$placeholder);
        if ($ins->execute() && $ins->affected_rows > 0) $n++;
    }
    $ins->close();
    echo "<p class='ok'>Inserted $n records.</p>";
} else {
    echo "<p class='info'>Skipped â€” table has data.</p>";
}

// â”€â”€ Step 3: Create staff_roles â”€â”€
echo "<h2>Step 3: staff_roles table</h2>";
$conn->query("CREATE TABLE IF NOT EXISTS `staff_roles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `role_name` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `role_level` VARCHAR(50) DEFAULT 'staff',
    `dashboard_path` VARCHAR(255) DEFAULT NULL,
    `permissions` JSON DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_role_name` (`role_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "<p class='ok'>Ready.</p>";

// â”€â”€ Step 4: Populate roles â”€â”€
echo "<h2>Step 4: Roles</h2>";
$roles = [
    [1,'Director General','Executive leadership','executive','dashboards/director-general.php'],
    [2,'CEO','Chief Executive Officer','executive','dashboards/ceo.php'],
    [3,'Director Academics','Academic affairs','director','dashboards/director-academics.php'],
    [4,'Director Finance','Financial oversight','director','dashboards/director-finance.php'],
    [5,'Director ICT','ICT department head','director','dashboards/director-ict.php'],
    [6,'School Principal','School head','director','dashboards/school-principal.php'],
    [7,'Deputy Principal','Deputy school admin','manager','dashboards/deputy-principal.php'],
    [8,'Academic Registrar','Student records','manager','dashboards/academic-registrar.php'],
    [9,'HR Manager','Human resources','manager','dashboards/hr-manager.php'],
    [10,'School Secretary','Admin support','staff','dashboards/school-secretary.php'],
    [11,'School Librarian','Library','staff','dashboards/school-librarian.php'],
    [12,'Head Nursing','Nursing dept','manager','dashboards/head-nursing.php'],
    [13,'Head Midwifery','Midwifery dept','manager','dashboards/head-midwifery.php'],
    [14,'Senior Lecturer','Senior teaching','staff','dashboards/senior-lecturers.php'],
    [15,'Lecturer','Teaching','staff','dashboards/lecturers.php'],
    [16,'Matron','Hostel','staff','dashboards/matrons.php'],
    [17,'Warden','Hostel warden','staff','dashboards/wardens.php'],
    [18,'Sickbay','Health services','staff','dashboards/sickbay.php'],
    [19,'Driver','Transport','staff','dashboards/drivers.php'],
    [20,'Security','Security','staff','dashboards/security.php'],
    [21,'Storekeeper','Store','staff','dashboards/storekeeper.php'],
    [22,'Guild President','Student governance','student','dashboards/guild-president.php'],
    [23,'Computer Lab Manager','Computer lab','staff','dashboards/computer_lab.php'],
    [24,'School Bursar','Finance ops','manager','dashboards/school-bursar.php'],
    [25,'Store Keeper','Store ops','staff','dashboards/storekeeper.php'],
    [26,'Director Admissions','Admissions','director','dashboards/director-admissions.php'],
    [27,'Bursar','Bursar ops','manager','dashboards/school-bursar.php'],
    [28,'Director Admissions & Requirements','Admissions & req','director','dashboards/director-admissions.php'],
    [29,'Head of Nursing','Nursing dept','manager','dashboards/head-nursing.php'],
    [30,'Head of Midwifery','Midwifery dept','manager','dashboards/head-midwifery.php'],
    [31,'Senior Lecturers','Senior teaching','staff','dashboards/senior-lecturers.php'],
    [32,'Lecturers','Teaching','staff','dashboards/lecturers.php'],
    [33,'Security Officer','Security','staff','dashboards/security.php'],
    [34,'Drivers','Transport','staff','dashboards/drivers.php'],
    [35,'Matrons','Hostel','staff','dashboards/matrons.php'],
    [36,'Wardens','Hostel warden','staff','dashboards/wardens.php'],
    [37,'Sickbay Nurse','Health','staff','dashboards/sickbay.php'],
    [38,'System Administrator','System admin','admin','dashboards/director-general.php'],
    [39,'Computer Lab','Computer lab','staff','dashboards/computer_lab.php'],
    [40,'Skills Lab','Skills lab','staff','dashboards/skills-lab.php'],
];
$ins = $conn->prepare("INSERT INTO staff_roles (id,role_name,description,role_level,dashboard_path) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE role_name=VALUES(role_name),dashboard_path=VALUES(dashboard_path)");
$n = 0;
foreach ($roles as $r) {
    $ins->bind_param('issss', $r[0],$r[1],$r[2],$r[3],$r[4]);
    if (!$ins->execute()) { error_log('$ins execute failed: ' . ($ins->error ?? 'unknown')); };
    $n++;
}
$ins->close();
echo "<p class='ok'>$n roles configured.</p>";

// â”€â”€ Step 5: Link staff to roles â”€â”€
echo "<h2>Step 5: Link staff to roles</h2>";
$map = [
    ['computer-lab@igangaschoolofnursingandmidwifery.ac.ug','Computer Lab Manager'],
    ['directorgeneral@igangaschoolofnursingandmidwifery.ac.ug','Director General'],
    ['ceo@igangaschoolofnursingandmidwifery.ac.ug','CEO'],
    ['directoracademic@igangaschoolofnursingandmidwifery.ac.ug','Director Academics'],
    ['finance@igangaschoolofnursingandmidwifery.ac.ug','Director Finance'],
    ['principal@igangaschoolofnursingandmidwifery.ac.ug','School Principal'],
    ['dep-principal@igangaschoolofnursingandmidwifery.ac.ug','Deputy Principal'],
    ['academicregistrar@igangaschoolofnursingandmidwifery.ac.ug','Academic Registrar'],
    ['hr-manager@igangaschoolofnursingandmidwifery.ac.ug','HR Manager'],
    ['secretary@igangaschoolofnursingandmidwifery.ac.ug','School Secretary'],
    ['library@igangaschoolofnursingandmidwifery.ac.ug','School Librarian'],
    ['nursing-dep@igangaschoolofnursingandmidwifery.ac.ug','Head of Nursing'],
    ['midwifery-dep@igangaschoolofnursingandmidwifery.ac.ug','Head of Midwifery'],
    ['senior-lecturers@igangaschoolofnursingandmidwifery.ac.ug','Senior Lecturer'],
    ['lecturers@igangaschoolofnursingandmidwifery.ac.ug','Lecturer'],
    ['matron@igangaschoolofnursingandmidwifery.ac.ug','Matron'],
    ['warden@igangaschoolofnursingandmidwifery.ac.ug','Warden'],
    ['sickbay@igangaschoolofnursingandmidwifery.ac.ug','Sickbay Nurse'],
    ['drivers@igangaschoolofnursingandmidwifery.ac.ug','Driver'],
    ['security@igangaschoolofnursingandmidwifery.ac.ug','Security Officer'],
    ['store@igangaschoolofnursingandmidwifery.ac.ug','Storekeeper'],
    ['guildpresident@igangaschoolofnursingandmidwifery.ac.ug','Guild President'],
    ['admissions@igangaschoolofnursingandmidwifery.ac.ug','Director Admissions'],
    ['dannybict@igangaschoolofnursingandmidwifery.ac.ug','Director ICT'],
    ['skills-lab@igangaschoolofnursingandmidwifery.ac.ug','Skills Lab'],
    ['bursar@igangaschoolofnursingandmidwifery.ac.ug','School Bursar'],
    ['admissions-req@igangaschoolofnursingandmidwifery.ac.ug','Director Admissions'],
    ['directorict@igangaschoolofnursingandmidwifery.ac.ug','Director ICT'],
    ['computerlab@igangaschoolofnursingandmidwifery.ac.ug','Computer Lab Manager'],
    ['skillslab@igangaschoolofnursingandmidwifery.ac.ug','Skills Lab'],
];
$upd = $conn->prepare("UPDATE staff s JOIN staff_roles sr ON sr.role_name=? SET s.role_id=sr.id WHERE s.email=?");
$n = 0;
foreach ($map as $m) {
    $upd->bind_param('ss', $m[1], $m[0]);
    if ($upd->execute() && $upd->affected_rows > 0) $n++;
}
$upd->close();
echo "<p class='ok'>$n linked.</p>";

// â”€â”€ Step 6: Set passwords â”€â”€
echo "<h2>Step 6: Passwords</h2>";
$pwds = [
    ['computer-lab@igangaschoolofnursingandmidwifery.ac.ug','Techno123'],
    ['directorgeneral@igangaschoolofnursingandmidwifery.ac.ug','DorisJoy2026'],
    ['ceo@igangaschoolofnursingandmidwifery.ac.ug','Lovely2God'],
    ['directoracademic@igangaschoolofnursingandmidwifery.ac.ug','Stephen123'],
    ['finance@igangaschoolofnursingandmidwifery.ac.ug','DorisJoy2026'],
    ['principal@igangaschoolofnursingandmidwifery.ac.ug','isnm2026'],
    ['dep-principal@igangaschoolofnursingandmidwifery.ac.ug','Isnm2026'],
    ['academicregistrar@igangaschoolofnursingandmidwifery.ac.ug','Lovely2God'],
    ['hr-manager@igangaschoolofnursingandmidwifery.ac.ug','Alexis2026'],
    ['secretary@igangaschoolofnursingandmidwifery.ac.ug','Lovely2God'],
    ['library@igangaschoolofnursingandmidwifery.ac.ug','isnm2026'],
    ['nursing-dep@igangaschoolofnursingandmidwifery.ac.ug','isnm4life'],
    ['midwifery-dep@igangaschoolofnursingandmidwifery.ac.ug','Life2save'],
    ['senior-lecturers@igangaschoolofnursingandmidwifery.ac.ug','isnm2026'],
    ['lecturers@igangaschoolofnursingandmidwifery.ac.ug','Isnm4life'],
    ['matron@igangaschoolofnursingandmidwifery.ac.ug','Isnm2026'],
    ['warden@igangaschoolofnursingandmidwifery.ac.ug','Lovely2God'],
    ['sickbay@igangaschoolofnursingandmidwifery.ac.ug','isnm2026'],
    ['drivers@igangaschoolofnursingandmidwifery.ac.ug','isnm4life'],
    ['security@igangaschoolofnursingandmidwifery.ac.ug','safty1st'],
    ['store@igangaschoolofnursingandmidwifery.ac.ug','Isnm4life'],
    ['guildpresident@igangaschoolofnursingandmidwifery.ac.ug','isnm4life'],
    ['admissions@igangaschoolofnursingandmidwifery.ac.ug','2268926931'],
    ['dannybict@igangaschoolofnursingandmidwifery.ac.ug','Lovely2God'],
    ['skills-lab@igangaschoolofnursingandmidwifery.ac.ug','Lovely2God'],
    ['bursar@igangaschoolofnursingandmidwifery.ac.ug','bursar@isnm'],
    ['admissions-req@igangaschoolofnursingandmidwifery.ac.ug','2268926931'],
    ['directorict@igangaschoolofnursingandmidwifery.ac.ug','Lovely2God'],
    ['computerlab@igangaschoolofnursingandmidwifery.ac.ug','Techno123'],
    ['skillslab@igangaschoolofnursingandmidwifery.ac.ug','Lovely2God'],
];
$upd = $conn->prepare("UPDATE staff SET password=?,password_changed=1,is_first_login=0,login_attempts=0,locked_until=NULL,updated_at=NOW() WHERE LOWER(email)=?");
$ok = 0;
$fail = 0;
foreach ($pwds as $p) {
    $hash = password_hash($p[1], PASSWORD_BCRYPT);
    $upd->bind_param('ss', $hash, $p[0]);
    if ($upd->execute()) { echo "<p class='ok'>OK: {$p[0]}</p>"; $ok++; }
    else { echo "<p class='fail'>FAIL: {$p[0]}</p>"; $fail++; }
}
$upd->close();

// â”€â”€ Step 7: Unlock â”€â”€
echo "<h2>Step 7: Unlock</h2>";
$conn->query("UPDATE staff SET login_attempts=0,locked_until=NULL");
echo "<p class='ok'>Done.</p>";

// â”€â”€ Verify â”€â”€
echo "<h2>Verify</h2>";
$v = $conn->query("SELECT COUNT(*) as c FROM staff")->fetch_assoc();
echo "<p class='info'>Staff: {$v['c']}</p>";
$v = $conn->query("SELECT COUNT(*) as c FROM staff WHERE password_changed=1")->fetch_assoc();
echo "<p class='info'>Passwords set: {$v['c']}</p>";
$v = $conn->query("SELECT COUNT(*) as c FROM staff WHERE role_id>0")->fetch_assoc();
echo "<p class='info'>Linked to role: {$v['c']}</p>";
$v = $conn->query("SELECT COUNT(*) as c FROM staff_roles")->fetch_assoc();
echo "<p class='info'>Roles: {$v['c']}</p>";
$conn->close();

echo "<hr><h1 class='ok'>SETUP COMPLETE</h1>";
echo "<p><a href='staff-login.php' style='color:#60a5fa'>Go to Login</a></p>";
echo "<p style='color:#f59e0b;font-weight:bold'>DELETE THIS FILE NOW.</p></body></html>";
