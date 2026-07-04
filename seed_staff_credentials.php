<?php
/**
 * ISNM Staff Credentials Seeder
 * Inserts all staff accounts with their credentials into the staff table
 * Usage: php seed_staff_credentials.php
 */

require_once __DIR__ . '/config/database.php';

class StaffCredentialsSeeder {
    private $conn;
    private $verbose = true;
    
    // Complete staff credentials database
    private $staffCredentials = [
        // LEADERSHIP & STRATEGY
        [
            'email' => 'directorgeneral@igangaschoolofnursingandmidwifery.ac.ug',
            'password' => 'DorisJoy2026',
            'full_name' => 'Director General',
            'position' => 'Director General',
            'department' => 'Leadership',
            'role' => 'admin',
        ],
        [
            'email' => 'ceo@igangaschoolofnursingandmidwifery.ac.ug',
            'password' => 'Lovely2God',
            'full_name' => 'Chief Executive Officer',
            'position' => 'CEO',
            'department' => 'Leadership',
            'role' => 'admin',
        ],
        // ACADEMIC AFFAIRS
        [
            'email' => 'directoracademic@igangaschoolofnursingandmidwifery.ac.ug',
            'password' => 'Stephen123',
            'full_name' => 'Director Academics',
            'position' => 'Director Academics',
            'department' => 'Academic Affairs',
            'role' => 'director',
        ],
        [
            'email' => 'principal@igangaschoolofnursingandmidwifery.ac.ug',
            'password' => 'isnm2026',
            'full_name' => 'School Principal',
            'position' => 'Principal',
            'department' => 'Academic Affairs',
            'role' => 'director',
        ],
        [
            'email' => 'dep-principal@igangaschoolofnursingandmidwifery.ac.ug',
            'password' => 'Isnm2026',
            'full_name' => 'Deputy Principal',
            'position' => 'Deputy Principal',
            'department' => 'Academic Affairs',
            'role' => 'director',
        ],
        [
            'email' => 'academicregistrar@igangaschoolofnursingandmidwifery.ac.ug',
            'password' => 'Lovely2God',
            'full_name' => 'Academic Registrar',
            'position' => 'Academic Registrar',
            'department' => 'Academic Affairs',
            'role' => 'registrar',
        ],
        [
            'email' => 'nursing-dep@igangaschoolofnursingandmidwifery.ac.ug',
            'password' => 'isnm4life',
            'full_name' => 'Head of Nursing',
            'position' => 'Head of Nursing',
            'department' => 'Nursing',
            'role' => 'lecturer',
        ],
        [
            'email' => 'midwifery-dep@igangaschoolofnursingandmidwifery.ac.ug',
            'password' => 'Life2save',
            'full_name' => 'Head of Midwifery',
            'position' => 'Head of Midwifery',
            'department' => 'Midwifery',
            'role' => 'lecturer',
        ],
        [
            'email' => 'senior-lecturers@igangaschoolofnursingandmidwifery.ac.ug',
            'password' => 'isnm2026',
            'full_name' => 'Senior Lecturer',
            'position' => 'Senior Lecturer',
            'department' => 'Academic Affairs',
            'role' => 'lecturer',
        ],
        [
            'email' => 'lecturers@igangaschoolofnursingandmidwifery.ac.ug',
            'password' => 'Isnm4life',
            'full_name' => 'Lecturer',
            'position' => 'Lecturer',
            'department' => 'Academic Affairs',
            'role' => 'lecturer',
        ],
        // FINANCE & ACCOUNTS
        [
            'email' => 'finance@igangaschoolofnursingandmidwifery.ac.ug',
            'password' => 'DorisJoy2026',
            'full_name' => 'Director Finance',
            'position' => 'Director Finance',
            'department' => 'Finance',
            'role' => 'bursar',
        ],
        [
            'email' => 'bursar@igangaschoolofnursingandmidwifery.ac.ug',
            'password' => 'bursar@isnm',
            'full_name' => 'School Bursar',
            'position' => 'School Bursar',
            'department' => 'Finance',
            'role' => 'bursar',
        ],
        // HR & ADMINISTRATION
        [
            'email' => 'hr-manager@igangaschoolofnursingandmidwifery.ac.ug',
            'password' => 'Alexis2026',
            'full_name' => 'HR Manager',
            'position' => 'HR Manager',
            'department' => 'Human Resources',
            'role' => 'hr_manager',
        ],
        [
            'email' => 'secretary@igangaschoolofnursingandmidwifery.ac.ug',
            'password' => 'Lovely2God',
            'full_name' => 'School Secretary',
            'position' => 'School Secretary',
            'department' => 'Administration',
            'role' => 'staff',
        ],
        // STUDENT SERVICES
        [
            'email' => 'admissions@igangaschoolofnursingandmidwifery.ac.ug',
            'password' => '2268926931',
            'full_name' => 'Director Admissions',
            'position' => 'Director Admissions',
            'department' => 'Admissions',
            'role' => 'admissions',
        ],
        [
            'email' => 'admissions-req@igangaschoolofnursingandmidwifery.ac.ug',
            'password' => '2268926931',
            'full_name' => 'Admissions Requirements Officer',
            'position' => 'Admissions Officer',
            'department' => 'Admissions',
            'role' => 'admissions',
        ],
        [
            'email' => 'library@igangaschoolofnursingandmidwifery.ac.ug',
            'password' => 'isnm2026',
            'full_name' => 'School Librarian',
            'position' => 'Librarian',
            'department' => 'Library',
            'role' => 'staff',
        ],
        [
            'email' => 'matron@igangaschoolofnursingandmidwifery.ac.ug',
            'password' => 'Isnm2026',
            'full_name' => 'Matron',
            'position' => 'Matron',
            'department' => 'Student Services',
            'role' => 'staff',
        ],
        [
            'email' => 'warden@igangaschoolofnursingandmidwifery.ac.ug',
            'password' => 'Lovely2God',
            'full_name' => 'Warden',
            'position' => 'Warden',
            'department' => 'Student Services',
            'role' => 'staff',
        ],
        [
            'email' => 'sickbay@igangaschoolofnursingandmidwifery.ac.ug',
            'password' => 'isnm2026',
            'full_name' => 'Sickbay Nurse',
            'position' => 'Sickbay Nurse',
            'department' => 'Health Services',
            'role' => 'staff',
        ],
        [
            'email' => 'guildpresident@igangaschoolofnursingandmidwifery.ac.ug',
            'password' => 'isnm4life',
            'full_name' => 'Guild President',
            'position' => 'Guild President',
            'department' => 'Student Services',
            'role' => 'staff',
        ],
        // OPERATIONS & LOGISTICS
        [
            'email' => 'dannybict@igangaschoolofnursingandmidwifery.ac.ug',
            'password' => 'Lovely2God',
            'full_name' => 'Director ICT',
            'position' => 'Director ICT',
            'department' => 'ICT',
            'role' => 'director',
        ],
        [
            'email' => 'directorict@igangaschoolofnursingandmidwifery.ac.ug',
            'password' => 'Lovely2God',
            'full_name' => 'Director ICT (Alternative)',
            'position' => 'Director ICT',
            'department' => 'ICT',
            'role' => 'director',
        ],
        [
            'email' => 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug',
            'password' => 'Techno123',
            'full_name' => 'Computer Lab Manager',
            'position' => 'Computer Lab Manager',
            'department' => 'ICT',
            'role' => 'staff',
        ],
        [
            'email' => 'computerlab@igangaschoolofnursingandmidwifery.ac.ug',
            'password' => 'Techno123',
            'full_name' => 'Computer Lab Technician',
            'position' => 'Computer Lab Technician',
            'department' => 'ICT',
            'role' => 'staff',
        ],
        [
            'email' => 'skillslab@igangaschoolofnursingandmidwifery.ac.ug',
            'password' => 'Lovely2God',
            'full_name' => 'Skills Lab Manager',
            'position' => 'Skills Lab Manager',
            'department' => 'ICT',
            'role' => 'staff',
        ],
        [
            'email' => 'skills-lab@igangaschoolofnursingandmidwifery.ac.ug',
            'password' => 'Lovely2God',
            'full_name' => 'Skills Lab Technician',
            'position' => 'Skills Lab Technician',
            'department' => 'ICT',
            'role' => 'staff',
        ],
        [
            'email' => 'store@igangaschoolofnursingandmidwifery.ac.ug',
            'password' => 'Isnm4life',
            'full_name' => 'Storekeeper',
            'position' => 'Storekeeper',
            'department' => 'Logistics',
            'role' => 'staff',
        ],
        [
            'email' => 'drivers@igangaschoolofnursingandmidwifery.ac.ug',
            'password' => 'isnm4life',
            'full_name' => 'Driver',
            'position' => 'Driver',
            'department' => 'Logistics',
            'role' => 'staff',
        ],
        [
            'email' => 'security@igangaschoolofnursingandmidwifery.ac.ug',
            'password' => 'safty1st',
            'full_name' => 'Security Officer',
            'position' => 'Security Officer',
            'department' => 'Security',
            'role' => 'staff',
        ],
    ];
    
    public function __construct() {
        $this->conn = getStaffConnection();
        if (!$this->conn) {
            echo "ERROR: Cannot connect to staff database\n";
            exit(1);
        }
    }
    
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        echo "[$timestamp] $message\n";
        error_log($message);
    }
    
    /**
     * Check if staff table exists and has the right structure
     */
    private function validateTable() {
        $result = $this->conn->query("SHOW TABLES LIKE 'staff'");
        
        if ($result->num_rows === 0) {
            $this->log("ERROR: Staff table does not exist");
            return false;
        }
        
        return true;
    }
    
    /**
     * Seed staff credentials
     */
    public function seedCredentials() {
        $this->log("Starting staff credentials seeding...");
        
        // Validate table exists
        if (!$this->validateTable()) {
            $this->log("ERROR: Cannot proceed without staff table");
            return false;
        }
        
        $inserted = 0;
        $updated = 0;
        $errors = 0;
        
        foreach ($this->staffCredentials as $staff) {
            $email = $staff['email'];
            $password = $staff['password'];
            $full_name = $staff['full_name'];
            $position = $staff['position'];
            $department = $staff['department'];
            $role = $staff['role'];
            
            // Check if staff already exists
            $check = $this->conn->prepare("SELECT id FROM staff WHERE email = ? LIMIT 1");
            $check->bind_param('s', $email);
            $check->execute();
            $checkResult = $check->get_result();
            
            if ($checkResult->num_rows > 0) {
                // Update existing
                $row = $checkResult->fetch_assoc();
                $id = $row['id'];
                
                $password_hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $this->conn->prepare(
                    "UPDATE staff SET password_hash = ?, full_name = ?, position = ?, department = ?, role = ?, status = 'active' WHERE id = ?"
                );
                
                if (!$stmt) {
                    $this->log("ERROR preparing update for $email: " . $this->conn->error);
                    $errors++;
                    continue;
                }
                
                $stmt->bind_param('sssssi', $password_hash, $full_name, $position, $department, $role, $id);
                
                if ($stmt->execute()) {
                    $this->log("✓ Updated: $email ($position)");
                    $updated++;
                } else {
                    $this->log("ERROR updating $email: " . $stmt->error);
                    $errors++;
                }
                $stmt->close();
            } else {
                // Insert new
                $password_hash = password_hash($password, PASSWORD_BCRYPT);
                $status = 'active';
                $login_attempts = 0;
                $locked_until = null;
                
                $stmt = $this->conn->prepare(
                    "INSERT INTO staff (email, password_hash, full_name, position, department, role, status, login_attempts, locked_until, created_at) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
                );
                
                if (!$stmt) {
                    $this->log("ERROR preparing insert for $email: " . $this->conn->error);
                    $errors++;
                    continue;
                }
                
                $stmt->bind_param('sssssssss', $email, $password_hash, $full_name, $position, $department, $role, $status, $login_attempts, $locked_until);
                
                if ($stmt->execute()) {
                    $this->log("✓ Inserted: $email ($position)");
                    $inserted++;
                } else {
                    $this->log("ERROR inserting $email: " . $stmt->error);
                    $errors++;
                }
                $stmt->close();
            }
            
            $check->close();
        }
        
        $this->log("");
        $this->log("=== Seeding Summary ===");
        $this->log("Inserted: $inserted");
        $this->log("Updated: $updated");
        $this->log("Errors: $errors");
        $this->log("Total Processed: " . count($this->staffCredentials));
        
        $this->conn->close();
        
        return $errors === 0;
    }
}

// Run the seeder
$seeder = new StaffCredentialsSeeder();
$success = $seeder->seedCredentials();

exit($success ? 0 : 1);
?>
