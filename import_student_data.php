<?php
/**
 * Import Student Data from Excel Files
 * Updated to work with current ISNM database structure
 */

require_once 'config/database.php';
require_once 'auth-service.php';

// Check if user is logged in and has admin access
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'academic' && $_SESSION['role'] !== 'registrar')) {
    header("Location: student-login.php");
    exit();
}

// Function to create students table
function createStudentsTable($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS students (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id VARCHAR(50) UNIQUE,
        index_number VARCHAR(50) UNIQUE,
        surname VARCHAR(100) NOT NULL,
        first_name VARCHAR(100) NOT NULL,
        other_name VARCHAR(100),
        full_name VARCHAR(255) GENERATED ALWAYS AS (CONCAT(first_name, ' ', IFNULL(other_name, ''), ' ', surname)) STORED,
        gender ENUM('Male', 'Female') NOT NULL,
        date_of_birth DATE,
        nationality VARCHAR(100) DEFAULT 'Uganda',
        district VARCHAR(100),
        address TEXT,
        phone VARCHAR(20),
        email VARCHAR(255) UNIQUE,
        program VARCHAR(200),
        level ENUM('Certificate', 'Diploma', 'Degree') NOT NULL,
        intake_year INT,
        intake_period VARCHAR(20),
        enrollment_date DATE,
        graduation_date DATE,
        status ENUM('active', 'inactive', 'graduated', 'suspended', 'withdrawn') DEFAULT 'active',
        gpa DECIMAL(3,2) DEFAULT 0.00,
        academic_status ENUM('good', 'probation', 'warning') DEFAULT 'good',
        fees_balance DECIMAL(10,2) DEFAULT 0.00,
        last_login TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX (student_id),
        INDEX (index_number),
        INDEX (email),
        INDEX (surname),
        INDEX (first_name),
        INDEX (program),
        INDEX (level),
        INDEX (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if (!$conn->query($sql)) {
        throw new Exception("Error creating students table: " . $conn->error);
    }
}

// Function to import all student data sets
function importAllStudentData() {
    $conn = getConnection();
    
    $imported_count = 0;
    $error_count = 0;
    $errors = [];
    
    // First, create students table if it doesn't exist
    createStudentsTable($conn);
    
    // SET 25 MIDWIVES Data
    $set25_midwives = [
        ['TUMUSIIME', 'VICKY', '', 'Female', 'JUL24/U041/CM/074', '2002-02-11', 'JINJA', 'Uganda', '077230147', 'vicky.tumusiime@isnm.ac.ug', 'Certificate Midwifery', 'Certificate', '2024', 'July'],
        ['TONDO', 'PATIENCE', '', 'Female', 'JUL24/U041/CM/073', '2005-02-22', 'BUYENDE', 'Uganda', '077157707', 'patience.tondo@isnm.ac.ug', 'Certificate Midwifery', 'Certificate', '2024', 'July'],
        ['SHABANO', 'SHABRA', '', 'Female', 'JUL24/U041/CM/072', '2004-07-15', 'IGANGA', 'Uganda', '075250421', 'shabra.shabano@isnm.ac.ug', 'Certificate Midwifery', 'Certificate', '2024', 'July'],
        ['OWORI', 'MARION', 'JANE', 'Female', 'JUL24/U041/CM/071', '2004-09-18', 'JINJA', 'Uganda', '074599571', 'marion.owori@isnm.ac.ug', 'Certificate Midwifery', 'Certificate', '2024', 'July'],
        ['NYANGWESO', 'SHARON', '', 'Female', 'JUL24/U041/CM/070', '2002-06-20', 'BUSIA', 'Uganda', '077306961', 'sharon.nyangweso@isnm.ac.ug', 'Certificate Midwifery', 'Certificate', '2024', 'July'],
        ['NEKESA', 'CHRISTINE', 'JOYCE', 'Female', 'JUL24/U041/CM/069', '2003-07-25', 'BUSIA', 'Uganda', '070114681', 'christine.nekesa@isnm.ac.ug', 'Certificate Midwifery', 'Certificate', '2024', 'July'],
        ['NDEGEMU', 'SARAH', '', 'Female', 'JUL24/U041/CM/068', '2003-09-12', 'KALIRO', 'Uganda', '074425021', 'sarah.ndegemu@isnm.ac.ug', 'Certificate Midwifery', 'Certificate', '2024', 'July'],
        ['NASIRUMBI', 'PATRICIA', 'LYDIA', 'Female', 'JUL24/U041/CM/067', '2004-07-27', 'BUSIA', 'Uganda', '078340481', 'patricia.nasirumbi@isnm.ac.ug', 'Certificate Midwifery', 'Certificate', '2024', 'July'],
        ['NAPERYA', 'MOUREEN', '', 'Female', 'JUL24/U041/CM/066', '2004-12-15', 'KALIRO', 'Uganda', '075198211', 'moureen.naperya@isnm.ac.ug', 'Certificate Midwifery', 'Certificate', '2024', 'July'],
        ['NANKWANGA', 'SPECIOZA', '', 'Female', 'JUL24/U041/CM/065', '2004-11-08', 'MAYUGE', 'Uganda', '070356521', 'specioza.nankwanga@isnm.ac.ug', 'Certificate Midwifery', 'Certificate', '2024', 'July'],
        ['NANGOBI', 'GLORIA', '', 'Female', 'JUL24/U041/CM/064', '2005-07-04', 'JINJA', 'Uganda', '074533521', 'gloria.nangobi@isnm.ac.ug', 'Certificate Midwifery', 'Certificate', '2024', 'July'],
        ['NANDUTU', 'GLADYS', '', 'Female', 'JUL24/U041/CM/063', '2004-10-20', 'BUGIRI', 'Uganda', '078612821', 'gladys.nandutu@isnm.ac.ug', 'Certificate Midwifery', 'Certificate', '2024', 'July'],
        ['NANDERA', 'JOVIA', '', 'Female', 'JUL24/UD41/CM/062', '2005-10-22', 'BUSIA', 'Uganda', '078625211', 'jovia.nandera@isnm.ac.ug', 'Certificate Midwifery', 'Certificate', '2024', 'July'],
        ['NAMWENDERAKI', 'EVERLYN', '', 'Female', 'JUL24/U041/CM/061', '2004-02-10', 'PALLISA', 'Uganda', '074508721', 'everlyn.namwenderaki@isnm.ac.ug', 'Certificate Midwifery', 'Certificate', '2024', 'July'],
        ['NAMWASE', 'LEAH', '', 'Female', 'JUL24/U041/CM/060', '2005-01-14', 'NAMUTUMBA', 'Uganda', '076241391', 'leah.namwase@isnm.ac.ug', 'Certificate Midwifery', 'Certificate', '2024', 'July'],
        ['NAMUMBYA', 'SHAMINAH', '', 'Female', 'JUL24/U041/CM/059', '2005-11-16', 'IGANGA', 'Uganda', '074510471', 'shaminah.namumbya@isnm.ac.ug', 'Certificate Midwifery', 'Certificate', '2024', 'July'],
        ['NAMULUNGU', 'MAXENSIA', '', 'Female', 'JUL24/U041/CM/058', '2003-08-21', 'JINJA', 'Uganda', '075754881', 'maxensia.namulungu@isnm.ac.ug', 'Certificate Midwifery', 'Certificate', '2024', 'July'],
        ['NAMULONDO', 'RASHIDA', '', 'Female', 'JUL24/UD41/CM/057', '2005-08-31', 'IGANGA', 'Uganda', '075847421', 'rashida.namulondo@isnm.ac.ug', 'Certificate Midwifery', 'Certificate', '2024', 'July'],
        ['NAMUKOSE', 'SARAH', '', 'Female', 'JUL24/U041/CM/056', '2004-11-01', 'MAYUGE', 'Uganda', '070597101', 'sarah.namukose@isnm.ac.ug', 'Certificate Midwifery', 'Certificate', '2024', 'July'],
        ['NAMILIPO', 'PATIENCE', 'CYNTHIA', 'Female', 'JUL24/U041/CM/055', '2005-09-30', 'LUWERO', 'Uganda', '077772591', 'patience.namilipo@isnm.ac.ug', 'Certificate Midwifery', 'Certificate', '2024', 'July'],
        ['NAMBOZO', 'SARAH', '', 'Female', 'JUL24/U041/CM/054', '2006-06-02', 'SIRONKO', 'Uganda', '070629881', 'sarah.nambozo@isnm.ac.ug', 'Certificate Midwifery', 'Certificate', '2024', 'July'],
        ['NAMAGEMBE', 'EVALYN', '', 'Female', 'JUL24/U041/CM/053', '2005-06-02', 'KALUNGU', 'Uganda', '070023651', 'evalyn.namagembe@isnm.ac.ug', 'Certificate Midwifery', 'Certificate', '2024', 'July'],
        ['NAMAGANDA', 'HASIFA', '', 'Female', 'JUL24/U041/CM/052', '2002-05-02', 'BUGIRI', 'Uganda', '070579381', 'hasifa.namaganda@isnm.ac.ug', 'Certificate Midwifery', 'Certificate', '2024', 'July'],
        ['NAMAGANDA', 'SIIDAH', '', 'Female', 'JULL24/U041/CM/051', '2004-10-01', 'KAMULI', 'Uganda', '075432141', 'siidah.namaganda@isnm.ac.ug', 'Certificate Midwifery', 'Certificate', '2024', 'July'],
        ['ASUMART', '', '', 'Female', 'JUL14/U041/CM/050', '2006-04-02', 'BUDAKA', 'Uganda', '075507781', 'asumart@isnm.ac.ug', 'Certificate Midwifery', 'Certificate', '2014', 'July']
    ];
    
    // DME (Diploma Midwifery Extension) Data
    $dme_students = [
        ['ACHIENG', 'JOYCE', '', 'Female', 'U1511/002', 'U042/CM/92/2/23254', '1992-01-15', 'MBARARA', 'Uganda', '078234567', 'joyce.achieng@isnm.ac.ug', 'Diploma Midwifery Extension', 'Diploma', '1992', 'January'],
        ['ACHIENG', 'SYLIVIA', '', 'Female', 'U1768/003', 'N17/U041/CM/002', '2017-03-20', 'KAMPALA', 'Uganda', '078345678', 'sylivia.achieng@isnm.ac.ug', 'Diploma Midwifery Extension', 'Diploma', '2017', 'September'],
        ['AKOTH', 'MILLIE', '', 'Female', 'U2182/001', 'N16/U041/CM/005', '2016-05-10', 'JINJA', 'Uganda', '077456789', 'millie.akoth@isnm.ac.ug', 'Diploma Midwifery Extension', 'Diploma', '2016', 'May'],
        ['AMODING', 'BRENDA', '', 'Female', 'U0317/026', 'N14/U019/CM/018', '2014-08-15', 'TORORO', 'Uganda', '075567890', 'brenda.amoding@isnm.ac.ug', 'Diploma Midwifery Extension', 'Diploma', '2014', 'August'],
        ['KATIKE', 'JOANITA', '', 'Female', 'U2877/021', 'M15/U039/CM/013', '2015-06-25', 'IGANGA', 'Uganda', '074678901', 'joanita.katike@isnm.ac.ug', 'Diploma Midwifery Extension', 'Diploma', '2015', 'June'],
        ['KWIIKIRIZA', 'MARTHA', '', 'Female', 'U1529/085', 'JUL18/U041/CM/042', '2018-07-30', 'MBUYE', 'Uganda', '073789012', 'martha.kwiikiriza@isnm.ac.ug', 'Diploma Midwifery Extension', 'Diploma', '2018', 'July'],
        ['MUDONDO', 'HARRIET', '', 'Female', 'U0238/116', 'N13/U041/CM/029', '2013-11-20', 'BUTALEJA', 'Uganda', '072890123', 'harriet.mudondo@isnm.ac.ug', 'Diploma Midwifery Extension', 'Diploma', '2013', 'November'],
        ['MUDONDO', 'JANE', 'SANDRA', 'Female', 'U0904/022', 'N17/U041/CM/039', '2017-09-05', 'KAMULI', 'Uganda', '076901234', 'jane.mudondo@isnm.ac.ug', 'Diploma Midwifery Extension', 'Diploma', '2017', 'September'],
        ['MUGALA', 'SCOVIA', '', 'Female', 'U2747/033', 'N13/U039/CM/009', '2013-12-10', 'BUGWERI', 'Uganda', '075012345', 'scovia.mugala@isnm.ac.ug', 'Diploma Midwifery Extension', 'Diploma', '2013', 'December'],
        ['NABIRYE', 'SAIDA', '', 'Female', 'U1361/042', 'JUL18/U041/CM/064', '2018-04-15', 'BUDAKA', 'Uganda', '074123456', 'saida.nabirye@isnm.ac.ug', 'Diploma Midwifery Extension', 'Diploma', '2018', 'April']
    ];
    
    // Certificate Nursing Data (Sample from Set 26 & 27)
    $cn_students = [
        ['AMONT', 'JOLLY', 'MIKALINE', 'Female', 'U3397/007', '2004-09-11', 'TORORO', 'Uganda', '762770369', 'jolly.amont@isnm.ac.ug', 'Certificate Nursing', 'Certificate', '2025', 'January'],
        ['ATHIENO', 'JANE', 'GLORIA', 'Female', 'U2623/018', '2004-02-17', 'TORORO', 'Uganda', '776026219', 'jane.athieno@isnm.ac.ug', 'Certificate Nursing', 'Certificate', '2025', 'January'],
        ['BAGALA', 'EVA', 'KISAKYE', 'Female', 'U1841/007', '2006-10-11', 'BUGIRI', 'Uganda', '751807659', 'eva.bagala@isnm.ac.ug', 'Certificate Nursing', 'Certificate', '2025', 'January'],
        ['BUYINZA', 'ENOCK', 'ODEKE', 'Male', 'U1696/042', '2000-02-10', 'NAMAYINGO', 'Uganda', '703645862', 'enock.buyinza@isnm.ac.ug', 'Certificate Nursing', 'Certificate', '2025', 'January'],
        ['CHANCY', '', '', 'Female', 'U0078/139', '2005-02-14', 'TORORO', 'Uganda', '741901721', 'chancy@isnm.ac.ug', 'Certificate Nursing', 'Certificate', '2025', 'January'],
        ['KAFUKO', 'ANISHA', '', 'Female', 'U0078/139', '2005-10-12', 'IGANGA', 'Uganda', '755353254', 'anisha.kafuko@isnm.ac.ug', 'Certificate Nursing', 'Certificate', '2025', 'January'],
        ['KAISU', 'MOUREEN', '', 'Female', 'U3756/032', '2004-07-10', 'IGANGA', 'Uganda', '704887896', 'moureen.kaisu@isnm.ac.ug', 'Certificate Nursing', 'Certificate', '2025', 'January']
    ];
    
    // Certificate Midwifery Data (Sample from Set 27)
    $cm_students = [
        ['ABASERET', 'BELINDA', '', 'Female', '10520/070', '2005-05-02', 'PALLISA', 'Uganda', '767673960', 'belinda.abaseret@isnm.ac.ug', 'Certificate Midwifery', 'Certificate', '2025', 'July'],
        ['ABEJA', 'CAROLINE', '', 'Female', '01095/051', '2000-07-08', 'PALLISA', 'Uganda', '766373740', 'caroline.abeja@isnm.ac.ug', 'Certificate Midwifery', 'Certificate', '2025', 'July'],
        ['ABONGUT', 'DERICK', '', 'Male', '00215014', '2002-06-08', 'TORORO', 'Uganda', '748547815', 'derick.abongut@isnm.ac.ug', 'Certificate Midwifery', 'Certificate', '2025', 'July'],
        ['ABONWAKU', 'RACHEAL', '', 'Female', '219002', '2006-02-26', 'JINJA', 'Uganda', '755553165', 'racheal.abonwaku@isnm.ac.ug', 'Certificate Midwifery', 'Certificate', '2025', 'July'],
        ['ACHOM', 'CHRISTINE', '', 'Female', '12512056', '2001-12-22', 'PALLISA', 'Uganda', '769983031', 'christine.achom@isnm.ac.ug', 'Certificate Midwifery', 'Certificate', '2025', 'July']
    ];
    
    // Combine all datasets
    $all_students = array_merge($set25_midwives, $dme_students, $cn_students, $cm_students);
    
    foreach ($all_students as $student_data) {
        try {
            $student_id = generateStudentId();
            $surname = $student_data[0];
            $first_name = $student_data[1];
            $other_name = $student_data[2];
            $gender = $student_data[3];
            $index_number = $student_data[4];
            $date_of_birth = $student_data[5];
            $district = $student_data[6];
            $nationality = $student_data[7];
            $phone = $student_data[8];
            $email = $student_data[9];
            $program = $student_data[10];
            $level = $student_data[11];
            $intake_year = $student_data[12];
            $intake_period = $student_data[13];
            
            // Check if student already exists
            $check_sql = "SELECT COUNT(*) as count FROM students WHERE index_number = ? OR email = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("ss", $index_number, $email);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->fetch_assoc()['count'] > 0) {
                continue; // Skip existing student
            }
            
            // Also add to users table for login access
            $username = strtolower(str_replace(['/', ' '], '', $index_number));
            $password = password_hash('password123', PASSWORD_DEFAULT); // Default password
            
            $user_sql = "INSERT INTO users (user_id, username, first_name, last_name, full_name, index_number, phone, email, password, role, type, status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'student', 'student', 'active')";
            $user_stmt = $conn->prepare($user_sql);
            $full_name = trim("$first_name $other_name $surname");
            $user_stmt->bind_param("sssssssss", $index_number, $username, $first_name, $surname, $full_name, $index_number, $phone, $email, $password);
            
            if (!$user_stmt->execute()) {
                $error_count++;
                $errors[] = "Error creating user account: $first_name $surname - " . $user_stmt->error;
                continue;
            }
            
            $sql = "INSERT INTO students (student_id, index_number, surname, first_name, other_name, gender, date_of_birth, nationality, district, phone, email, program, level, intake_year, intake_period, enrollment_date, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), 'active')";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssssssssssss", $student_id, $index_number, $surname, $first_name, $other_name, $gender, $date_of_birth, $nationality, $district, $phone, $email, $program, $level, $intake_year, $intake_period);
            
            if ($stmt->execute()) {
                $imported_count++;
            } else {
                $error_count++;
                $errors[] = "Error importing student record: $first_name $surname - " . $stmt->error;
            }
        } catch (Exception $e) {
            $error_count++;
            $errors[] = "Exception processing: $first_name $surname - " . $e->getMessage();
        }
    }
    
    return [
        'imported' => $imported_count,
        'errors' => $error_count,
        'error_details' => $errors
    ];
}

// Function to generate unique student ID
function generateStudentId() {
    $conn = getConnection();
    
    do {
        $year = date('Y');
        $random = mt_rand(1000, 9999);
        $student_id = "ISNM/$year/$random";
        
        $check_sql = "SELECT COUNT(*) as count FROM students WHERE student_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $student_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
    } while ($check_result->fetch_assoc()['count'] > 0);
    
    return $student_id;
}

// Function to create staff roles and accounts
function createStaffRoles($conn) {
    $staff_accounts = [
        // Academic Office
        ['academic.registrar@isnm.ac.ug', 'Academic Registrar', 'academic', 'registrar', '256774123456'],
        
        // Finance/Accounting Office
        ['accountant@isnm.ac.ug', 'Chief Accountant', 'accountant', 'finance', '256775123456'],
        ['finance.officer@isnm.ac.ug', 'Finance Officer', 'finance', 'finance', '256776123456'],
        
        // Directors
        ['director.academic@isnm.ac.ug', 'Director of Academics', 'director', 'academic', '256777123456'],
        ['director.admin@isnm.ac.ug', 'Director of Administration', 'director', 'admin', '256778123456'],
        ['director.medical@isnm.ac.ug', 'Director of Medical Services', 'director', 'medical', '256779123456'],
        
        // Human Resources
        ['hr.manager@isnm.ac.ug', 'HR Manager', 'hr', 'admin', '256770123456'],
        ['hr.officer@isnm.ac.ug', 'HR Officer', 'hr', 'admin', '256771123456'],
        
        // Principal Office
        ['principal@isnm.ac.ug', 'Principal', 'principal', 'admin', '256772123456'],
        ['deputy.principal@isnm.ac.ug', 'Deputy Principal', 'deputy_principal', 'admin', '256773123456'],
        
        // Secretary
        ['secretary@isnm.ac.ug', 'Secretary', 'secretary', 'admin', '256780123456'],
        ['receptionist@isnm.ac.ug', 'Receptionist', 'receptionist', 'admin', '256781123456'],
        
        // Other Offices
        ['librarian@isnm.ac.ug', 'Librarian', 'librarian', 'academic', '256782123456'],
        ['bursar@isnm.ac.ug', 'Bursar', 'bursar', 'finance', '256783123456'],
        ['counselor@isnm.ac.ug', 'Student Counselor', 'counselor', 'student_affairs', '256784123456'],
        ['nurse.tutor@isnm.ac.ug', 'Nurse Tutor', 'tutor', 'academic', '256785123456'],
        ['midwife.tutor@isnm.ac.ug', 'Midwife Tutor', 'tutor', 'academic', '256786123456']
    ];
    
    foreach ($staff_accounts as $staff) {
        $email = $staff[0];
        $full_name = $staff[1];
        $role = $staff[2];
        $department = $staff[3];
        $phone = $staff[4];
        
        // Check if staff already exists
        $check_sql = "SELECT COUNT(*) as count FROM users WHERE email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->fetch_assoc()['count'] > 0) {
            continue; // Skip existing staff
        }
        
        // Create staff account
        $username = explode('@', $email)[0];
        $password = password_hash('password123', PASSWORD_DEFAULT);
        $user_id = strtoupper(substr($role, 0, 3)) . date('Y') . rand(100, 999);
        
        $sql = "INSERT INTO users (user_id, username, first_name, last_name, full_name, email, phone, password, role, type, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'staff', 'active')";
        
        $name_parts = explode(' ', $full_name);
        $first_name = $name_parts[0];
        $last_name = isset($name_parts[1]) ? implode(' ', array_slice($name_parts, 1)) : '';
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssss", $user_id, $username, $first_name, $last_name, $full_name, $email, $phone, $password, $role);
        
        if (!$stmt->execute()) {
            error_log("Error creating staff account: $email - " . $stmt->error);
        }
    }
}

// Function to log activity
function logActivity($user_id, $user_role, $action, $details, $module, $record_id) {
    $conn = getConnection();
    
    $sql = "INSERT INTO activity_log (user_id, user_role, action, details, module, record_id, ip_address, user_agent, timestamp) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $conn->prepare($sql);
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    $stmt->bind_param("ssssssss", $user_id, $user_role, $action, $details, $module, $record_id, $ip_address, $user_agent);
    $stmt->execute();
}

// Function to create activity log table
function createActivityLogTable($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS activity_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id VARCHAR(50),
        user_role VARCHAR(50),
        action VARCHAR(100),
        details TEXT,
        module VARCHAR(50),
        record_id VARCHAR(50),
        ip_address VARCHAR(45),
        user_agent TEXT,
        timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (user_id),
        INDEX (user_role),
        INDEX (action),
        INDEX (module),
        INDEX (timestamp)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if (!$conn->query($sql)) {
        throw new Exception("Error creating activity_log table: " . $conn->error);
    }
}

// Handle import request
if (isset($_POST['import_all']) || (php_sapi_name() === 'cli' && isset($argv[1]) && $argv[1] === 'import')) {
    $result = importAllStudentData();
    createStaffRoles(getConnection());
    createActivityLogTable(getConnection());
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    logActivity($_SESSION['user_id'] ?? 'system', $_SESSION['role'] ?? 'system', 'Bulk Student Import', "Imported {$result['imported']} students with {$result['errors']} errors", 'students', null);
    
    $_SESSION['import_result'] = $result;
    
    if (php_sapi_name() !== 'cli') {
        header("Location: import_student_data.php");
        exit();
    }
}

// Get current statistics
$conn = getConnection();
$total_students_sql = "SELECT COUNT(*) as total FROM students";
$total_result = $conn->query($total_students_sql);
$total_students = $total_result->fetch_assoc()['total'];

$by_program_sql = "SELECT program, COUNT(*) as count FROM students GROUP BY program ORDER BY count DESC";
$programs_stats = $conn->query($by_program_sql);

$by_year_sql = "SELECT intake_year, COUNT(*) as count FROM students GROUP BY intake_year ORDER BY intake_year DESC";
$year_stats = $conn->query($by_year_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Student Data - ISNM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1a237e;
            --secondary-color: #3949ab;
            --accent-color: #ffd700;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        .navbar {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .main-container {
            padding: 2rem;
        }

        .page-header {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            border-left: 5px solid var(--primary-color);
        }

        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 3px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            border-left: 4px solid var(--primary-color);
        }

        .stats-card:hover {
            transform: translateY(-5px);
        }

        .import-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 8px;
            padding: 0.75rem 2rem;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(26, 35, 126, 0.3);
        }

        .btn-success {
            background: linear-gradient(135deg, var(--success-color), #218838);
            border: none;
            border-radius: 8px;
        }

        .progress {
            height: 25px;
            border-radius: 15px;
        }

        .data-preview {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            max-height: 400px;
            overflow-y: auto;
        }

        .data-preview table {
            font-size: 0.875rem;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .pulse {
            animation: pulse 2s infinite;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-graduation-cap"></i> ISNM Student Management
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="student_accounts_management.php">
                            <i class="fas fa-users"></i> Student Accounts
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="import_student_data.php">
                            <i class="fas fa-database"></i> Import Data
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php">
                                <i class="fas fa-user"></i> Profile
                            </a></li>
                            <li><a class="dropdown-item" href="logout.php">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid main-container">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="h3 mb-3">
                <i class="fas fa-database text-primary"></i> Student Data Import System
            </h1>
            <p class="text-muted">Import and manage student records from various data sets</p>
        </div>

        <!-- Current Statistics -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <h4><?php echo number_format($total_students); ?></h4>
                    <p class="text-muted mb-0">Total Students</p>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <h4><?php echo count($programs_stats); ?></h4>
                    <p class="text-muted mb-0">Programs</p>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <h4><?php echo count($year_stats); ?></h4>
                    <p class="text-muted mb-0">Intake Years</p>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <h4>40+</h4>
                    <p class="text-muted mb-0">Ready to Import</p>
                </div>
            </div>
        </div>

        <!-- Import Section -->
        <div class="import-card">
            <h3 class="h4 mb-4">
                <i class="fas fa-upload text-primary"></i> Import Student Data Sets
            </h3>
            
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <strong>Ready to Import:</strong> Multiple student data sets including:
                <ul class="mb-0 mt-2">
                    <li>SET 25 MIDWIVES (25 students)</li>
                    <li>DME - Diploma Midwifery Extension (10 students)</li>
                    <li>Certificate Nursing (7 students)</li>
                    <li>Certificate Midwifery (5+ students)</li>
                </ul>
            </div>

            <?php if (isset($_SESSION['import_result'])): ?>
                <div class="alert alert-<?php echo $_SESSION['import_result']['errors'] > 0 ? 'warning' : 'success'; ?>">
                    <h5><i class="fas fa-<?php echo $_SESSION['import_result']['errors'] > 0 ? 'exclamation-triangle' : 'check-circle'; ?>"></i> Import Results</h5>
                    <p><strong><?php echo $_SESSION['import_result']['imported']; ?></strong> students successfully imported</p>
                    <?php if ($_SESSION['import_result']['errors'] > 0): ?>
                        <p><strong><?php echo $_SESSION['import_result']['errors']; ?></strong> errors encountered</p>
                    <?php endif; ?>
                </div>
                <?php unset($_SESSION['import_result']); ?>
            <?php endif; ?>

            <form method="POST" onsubmit="return confirmImport()">
                <input type="hidden" name="import_all" value="1">
                <div class="text-center">
                    <button type="submit" class="btn btn-primary btn-lg pulse">
                        <i class="fas fa-database"></i> Import All Student Data
                    </button>
                    <p class="text-muted mt-3">This will import all available student data sets into the database</p>
                </div>
            </form>
        </div>

        <!-- Data Preview -->
        <div class="import-card">
            <h3 class="h4 mb-4">
                <i class="fas fa-eye text-primary"></i> Data Preview
            </h3>
            
            <ul class="nav nav-tabs" id="previewTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="set25-tab" data-bs-toggle="tab" data-bs-target="#set25" type="button">
                        SET 25 MIDWIVES
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="dme-tab" data-bs-toggle="tab" data-bs-target="#dme" type="button">
                        DME Students
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="cn-tab" data-bs-toggle="tab" data-bs-target="#cn" type="button">
                        Certificate Nursing
                    </button>
                </li>
            </ul>
            
            <div class="tab-content mt-3" id="previewTabsContent">
                <div class="tab-pane fade show active" id="set25">
                    <div class="data-preview">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Surname</th>
                                    <th>First Name</th>
                                    <th>Other Name</th>
                                    <th>Gender</th>
                                    <th>NSIN</th>
                                    <th>Intake</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td>TUMUSIIME</td><td>VICKY</td><td>-</td><td>Female</td><td>JUL24/U041/CM/074</td><td>2024 July</td></tr>
                                <tr><td>TONDO</td><td>PATIENCE</td><td>-</td><td>Female</td><td>JUL24/U041/CM/073</td><td>2024 July</td></tr>
                                <tr><td>SHABANO</td><td>SHABRA</td><td>-</td><td>Female</td><td>JUL24/U041/CM/072</td><td>2024 July</td></tr>
                                <tr><td>OWORI</td><td>MARION</td><td>JANE</td><td>Female</td><td>JUL24/U041/CM/071</td><td>2024 July</td></tr>
                                <tr><td>NYANGWESO</td><td>SHARON</td><td>-</td><td>Female</td><td>JUL24/U041/CM/070</td><td>2024 July</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="tab-pane fade" id="dme">
                    <div class="data-preview">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Surname</th>
                                    <th>First Name</th>
                                    <th>Index No.</th>
                                    <th>Registration</th>
                                    <th>Year</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td>ACHIENG</td><td>JOYCE</td><td>U1511/002</td><td>U042/CM/92/2/23254</td><td>1992</td></tr>
                                <tr><td>ACHIENG</td><td>SYLIVIA</td><td>U1768/003</td><td>N17/U041/CM/002</td><td>2017</td></tr>
                                <tr><td>AKOTH</td><td>MILLIE</td><td>U2182/001</td><td>N16/U041/CM/005</td><td>2016</td></tr>
                                <tr><td>AMODING</td><td>BRENDA</td><td>U0317/026</td><td>N14/U019/CM/018</td><td>2014</td></tr>
                                <tr><td>KATIKE</td><td>JOANITA</td><td>U2877/021</td><td>M15/U039/CM/013</td><td>2015</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="tab-pane fade" id="cn">
                    <div class="data-preview">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Surname</th>
                                    <th>First Name</th>
                                    <th>Other Name</th>
                                    <th>Index No.</th>
                                    <th>DOB</th>
                                    <th>District</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td>ABASERET</td><td>BELINDA</td><td>-</td><td>U3397/007</td><td>2004-09-11</td><td>TORORO</td></tr>
                                <tr><td>ABEJA</td><td>CAROLINE</td><td>-</td><td>01095/051</td><td>2000-07-08</td><td>PALLISA</td></tr>
                                <tr><td>ABONGUT</td><td>DERICK</td><td>-</td><td>00215014</td><td>2002-06-08</td><td>TORORO</td></tr>
                                <tr><td>ACHOM</td><td>CHRISTINE</td><td>-</td><td>12512056</td><td>2001-12-22</td><td>PALLISA</td></tr>
                                <tr><td>ADONGO</td><td>PRICILLA</td><td>LUCKY</td><td>744778338</td><td>2001-07-17</td><td>TORORO</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Program Statistics -->
        <div class="import-card">
            <h3 class="h4 mb-4">
                <i class="fas fa-chart-bar text-primary"></i> Current Database Statistics
            </h3>
            
            <div class="row">
                <div class="col-md-6">
                    <h5>Students by Program</h5>
                    <div class="data-preview">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Program</th>
                                    <th>Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($programs_stats as $stat): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($stat['program']); ?></td>
                                        <td><span class="badge bg-primary"><?php echo $stat['count']; ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-md-6">
                    <h5>Students by Intake Year</h5>
                    <div class="data-preview">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Year</th>
                                    <th>Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($year_stats as $stat): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($stat['intake_year']); ?></td>
                                        <td><span class="badge bg-info"><?php echo $stat['count']; ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function confirmImport() {
            return confirm('Are you sure you want to import all student data? This action cannot be undone.');
        }

        // Auto-refresh statistics after import
        function refreshStats() {
            // Implementation for real-time stats update
            console.log('Statistics refreshed');
        }
    </script>
</body>
</html>
