<?php
/**
 * Setup all staff accounts with proper hashed passwords
 * Run this script once to create all department accounts
 */

require_once 'config/database.php';

$conn = getStaffConnection();

// Create all required roles
$roles = [
    ["Director General", "Overall school administration", "Executive", "dashboards/director-general.php"],
    ["CEO", "Chief Executive Officer", "Executive", "dashboards/ceo.php"],
    ["Director Academics", "Academic programs oversight", "Management", "dashboards/director-academics.php"],
    ["Director ICT", "Information Technology management", "Management", "dashboards/director-ict.php"],
    ["Director Finance", "Financial management", "Management", "dashboards/director-finance.php"],
    ["School Principal", "School leadership", "Executive", "dashboards/school-principal.php"],
    ["Deputy Principal", "Assistant principal", "Management", "dashboards/deputy-principal.php"],
    ["School Bursar", "Fee management", "Administrative", "bursar_dashboard.php"],
    ["Director Admissions & Requirements", "Admissions management", "Management", "dashboards/director-admissions.php"],
    ["Academic Registrar", "Student records", "Academic", "dashboards/academic-registrar.php"],
    ["HR Manager", "Human resources", "Management", "dashboards/hr-manager.php"],
    ["School Secretary", "Administrative support", "Administrative", "dashboards/school-secretary.php"],
    ["School Librarian", "Library services", "Support", "dashboards/school-librarian.php"],
    ["Head Nursing", "Nursing department", "Academic", "dashboards/head-nursing.php"],
    ["Head Midwifery", "Midwifery department", "Academic", "dashboards/head-midwifery.php"],
    ["Lecturers", "Teaching staff", "Academic", "dashboards/lecturers.php"],
    ["Senior Lecturers", "Senior teaching staff", "Academic", "dashboards/senior-lecturers.php"],
    ["Matrons", "Student welfare", "Support", "dashboards/matrons.php"],
    ["Wardens", "Student discipline", "Support", "dashboards/wardens.php"],
    ["Sickbay", "Medical support", "Support", "dashboards/sickbay.php"],
    ["Drivers", "Transportation", "Support", "dashboards/drivers.php"],
    ["Security", "Campus security", "Support", "dashboards/security.php"],
    ["Store Keeper", "Inventory management", "Support", "dashboards/storekeeper.php"],
    ["Guild President", "Student leadership", "Student", "dashboards/guild-president.php"],
    ["Computer Lab Manager", "Computer lab operations", "Support", "computer_lab.php"],
];

foreach ($roles as $role) {
    $stmt = $conn->prepare("INSERT IGNORE INTO staff_roles (role_name, role_description, role_level, dashboard_path) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $role[0], $role[1], $role[2], $role[3]);
    $stmt->execute();
}

echo "Roles created successfully.\n";

// Create all staff accounts
$staff = [
    ["computer-lab@igangaschoolofnursingandmidwifery.ac.ug", "Techno123", "Computer Lab Manager", "Information Technology"],
    ["directorgeneral@igangaschoolofnursingandmidwifery.ac.ug", "DorisJoy2026", "Director General", "Executive Office"],
    ["ceo@igangaschoolofnursingandmidwifery.ac.ug", "Lovely2God", "Chief Executive Officer", "Executive Office"],
    ["directoracademic@igangaschoolofnursingandmidwifery.ac.ug", "Stephen123", "Director Academics", "Academic Affairs"],
    ["dannybict@igangaschoolofnursingandmidwifery.ac.ug", "Lovely2God", "Director ICT", "Information Technology"],
    ["finance@igangaschoolofnursingandmidwifery.ac.ug", "DorisJoy2026", "Director Finance", "Finance Department"],
    ["principal@igangaschoolofnursingandmidwifery.ac.ug", "isnm2026", "School Principal", "Academic Affairs"],
    ["dep-principal@igangaschoolofnursingandmidwifery.ac.ug", "Isnm2026", "Deputy Principal", "Academic Affairs"],
    ["bursar@igangaschoolofnursingandmidwifery.ac.ug", "DorisJoy2026", "School Bursar", "Finance Department"],
    ["admissions@igangaschoolofnursingandmidwifery.ac.ug", "2268926931", "Director Admissions & Requirements", "Academic Affairs"],
    ["academicregistrar@igangaschoolofnursingandmidwifery.ac.ug", "Lovely2God", "Academic Registrar", "Academic Affairs"],
    ["hr-manager@igangaschoolofnursingandmidwifery.ac.ug", "Alexis2026", "HR Manager", "Human Resources"],
    ["secretary@igangaschoolofnursingandmidwifery.ac.ug", "Lovely2God", "School Secretary", "Administrative Support"],
    ["library@igangaschoolofnursingandmidwifery.ac.ug", "isnm2026", "School Librarian", "Library Services"],
    ["nursing-dep@igangaschoolofnursingandmidwifery.ac.ug", "isnm4life", "Head of Nursing", "Nursing Department"],
    ["midwifery-dep@igangaschoolofnursingandmidwifery.ac.ug", "Life2save", "Head of Midwifery", "Midwifery Department"],
    ["senior-lecturers@igangaschoolofnursingandmidwifery.ac.ug", "isnm2026", "Senior Lecturers", "Academic Affairs"],
    ["lecturers@igangaschoolofnursingandmidwifery.ac.ug", "Isnm4life", "Lecturers", "Academic Affairs"],
    ["matron@igangaschoolofnursingandmidwifery.ac.ug", "Isnm2026", "Matrons", "Student Affairs"],
    ["warden@igangaschoolofnursingandmidwifery.ac.ug", "Lovely2God", "Warden", "Student Affairs"],
    ["sickbay@igangaschoolofnursingandmidwifery.ac.ug", "isnm2026", "Sickbay", "Support"],
    ["drivers@igangaschoolofnursingandmidwifery.ac.ug", "isnm4life", "Drivers", "Support"],
    ["security@igangaschoolofnursingandmidwifery.ac.ug", "safty1st", "Security", "Security Services"],
    ["store@igangaschoolofnursingandmidwifery.ac.ug", "Isnm4life", "Store Keeper", "Support"],
    ["guildpresident@igangaschoolofnursingandmidwifery.ac.ug", "isnm4life", "Guild President", "Student Affairs"],
];

foreach ($staff as $account) {
    $hashedPassword = password_hash($account[1], PASSWORD_DEFAULT);
    
    $checkStmt = $conn->prepare("SELECT id FROM staff WHERE email = ? LIMIT 1");
    $checkStmt->bind_param("s", $account[0]);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows > 0) {
        $updateStmt = $conn->prepare("UPDATE staff SET password = ?, position = ?, department = ?, status = 'Active', password_changed = FALSE, is_first_login = TRUE WHERE email = ?");
        $updateStmt->bind_param("ssss", $hashedPassword, $account[2], $account[3], $account[0]);
        $updateStmt->execute();
        echo "Updated: " . $account[0] . "\n";
    } else {
        $roleStmt = $conn->prepare("SELECT id FROM staff_roles WHERE role_name = ? LIMIT 1");
        $roleStmt->bind_param("s", $account[2]);
        $roleStmt->execute();
        $roleResult = $roleStmt->get_result();
        
        if ($roleRow = $roleResult->fetch_assoc()) {
            $insertStmt = $conn->prepare("INSERT INTO staff (staff_id, full_name, email, password, phone, position, department, role_id, status, password_changed, is_first_login, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Active', FALSE, TRUE, NOW())");
            $staffId = str_replace("@", "", $account[0]);
            $phone = "+256701000001";
            $insertStmt->bind_param("sssssssi", $staffId, $account[2], $account[0], $hashedPassword, $phone, $account[2], $account[3], $roleRow["id"]);
            $insertStmt->execute();
            echo "Created: " . $account[0] . "\n";
        }
    }
}

echo "\nAll staff accounts set up successfully!\n";
$conn->close();
?>