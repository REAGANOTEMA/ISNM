<?php
/**
 * ISNM Database Migration: Add responsive system tables
 * Creates tables for:
 * - Notifications system
 * - Form submissions and routing
 * - News/announcements publishing
 * - Running this will set up all required tables
 */

require_once __DIR__ . '/config/database.php';

class DatabaseMigration {
    private $studentConn;
    private $staffConn;
    private $websiteConn;
    
    public function __construct() {
        $this->studentConn = getStudentsConnection();
        $this->staffConn = getStaffConnection();
        $this->websiteConn = getWebsiteConnection();
    }
    
    /**
     * Run all migrations
     */
    public function runAll() {
        echo "[INFO] Starting database migrations...\n";
        
        try {
            $this->createNotificationsTable();
            $this->createFormSubmissionsTable();
            $this->createApplicationsTable();
            $this->createContactSubmissionsTable();
            $this->createFeedbackSubmissionsTable();
            $this->createComplaintSubmissionsTable();
            $this->createVolunteerApplicationsTable();
            $this->createWebsiteAnnouncementsTable();
            $this->createStudentFeeAccountsTable();
            
            echo "[SUCCESS] All migrations completed successfully!\n";
            return true;
        } catch (Exception $e) {
            echo "[ERROR] Migration failed: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Create notifications table
     */
    private function createNotificationsTable() {
        echo "[MIGRATING] Notifications table...\n";
        
        $sql = "
        CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            staff_id INT NOT NULL,
            type VARCHAR(50) NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            related_id INT,
            from_email VARCHAR(255),
            is_read BOOLEAN DEFAULT FALSE,
            read_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE,
            INDEX idx_staff_unread (staff_id, is_read),
            INDEX idx_created (created_at),
            INDEX idx_type (type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        if (!$this->staffConn->query($sql)) {
            throw new Exception("Notifications table: " . $this->staffConn->error);
        }
        
        echo "[OK] Notifications table created/verified\n";
    }
    
    /**
     * Create form submissions table
     */
    private function createFormSubmissionsTable() {
        echo "[MIGRATING] Form submissions table...\n";
        
        $sql = "
        CREATE TABLE IF NOT EXISTS form_submissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            form_type VARCHAR(50) NOT NULL,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            phone VARCHAR(20),
            subject VARCHAR(255),
            message TEXT,
            status VARCHAR(50) DEFAULT 'pending',
            assigned_to INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            INDEX idx_type (form_type),
            INDEX idx_email (email),
            INDEX idx_status (status),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        if (!$this->studentConn->query($sql)) {
            throw new Exception("Form submissions table: " . $this->studentConn->error);
        }
        
        echo "[OK] Form submissions table created/verified\n";
    }
    
    /**
     * Create applications table
     */
    private function createApplicationsTable() {
        echo "[MIGRATING] Applications table...\n";
        
        $sql = "
        CREATE TABLE IF NOT EXISTS applications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(100) NOT NULL,
            surname VARCHAR(100) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            phone VARCHAR(20) NOT NULL,
            program VARCHAR(255) NOT NULL,
            qualifications TEXT,
            experience TEXT,
            personal_statement TEXT,
            status VARCHAR(50) DEFAULT 'received',
            reviewed_at TIMESTAMP NULL,
            reviewed_by INT,
            decision VARCHAR(50),
            decision_date TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            INDEX idx_email (email),
            INDEX idx_status (status),
            INDEX idx_program (program),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        if (!$this->studentConn->query($sql)) {
            throw new Exception("Applications table: " . $this->studentConn->error);
        }
        
        echo "[OK] Applications table created/verified\n";
    }
    
    /**
     * Create contact submissions table
     */
    private function createContactSubmissionsTable() {
        echo "[MIGRATING] Contact submissions table...\n";
        
        $sql = "
        CREATE TABLE IF NOT EXISTS contact_submissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            phone VARCHAR(20),
            subject VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            category VARCHAR(50),
            status VARCHAR(50) DEFAULT 'pending',
            assigned_to INT,
            response TEXT,
            responded_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            INDEX idx_email (email),
            INDEX idx_status (status),
            INDEX idx_category (category),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        if (!$this->studentConn->query($sql)) {
            throw new Exception("Contact submissions table: " . $this->studentConn->error);
        }
        
        echo "[OK] Contact submissions table created/verified\n";
    }
    
    /**
     * Create feedback submissions table
     */
    private function createFeedbackSubmissionsTable() {
        echo "[MIGRATING] Feedback submissions table...\n";
        
        $sql = "
        CREATE TABLE IF NOT EXISTS feedback_submissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            rating INT,
            subject VARCHAR(255),
            feedback TEXT NOT NULL,
            category VARCHAR(100),
            status VARCHAR(50) DEFAULT 'received',
            reviewed_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            INDEX idx_email (email),
            INDEX idx_rating (rating),
            INDEX idx_category (category),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        if (!$this->studentConn->query($sql)) {
            throw new Exception("Feedback submissions table: " . $this->studentConn->error);
        }
        
        echo "[OK] Feedback submissions table created/verified\n";
    }
    
    /**
     * Create complaint submissions table
     */
    private function createComplaintSubmissionsTable() {
        echo "[MIGRATING] Complaint submissions table...\n";
        
        $sql = "
        CREATE TABLE IF NOT EXISTS complaint_submissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            complainant_name VARCHAR(255) NOT NULL,
            complainant_email VARCHAR(255) NOT NULL,
            complainant_phone VARCHAR(20),
            subject VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            department VARCHAR(100),
            severity VARCHAR(50) DEFAULT 'medium',
            status VARCHAR(50) DEFAULT 'filed',
            assigned_to INT,
            resolution TEXT,
            resolved_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            INDEX idx_email (complainant_email),
            INDEX idx_status (status),
            INDEX idx_severity (severity),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        if (!$this->studentConn->query($sql)) {
            throw new Exception("Complaint submissions table: " . $this->studentConn->error);
        }
        
        echo "[OK] Complaint submissions table created/verified\n";
    }
    
    /**
     * Create volunteer applications table
     */
    private function createVolunteerApplicationsTable() {
        echo "[MIGRATING] Volunteer applications table...\n";
        
        $sql = "
        CREATE TABLE IF NOT EXISTS volunteer_applications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(100) NOT NULL,
            surname VARCHAR(100) NOT NULL,
            email VARCHAR(255) NOT NULL,
            phone VARCHAR(20) NOT NULL,
            skills TEXT,
            availability TEXT,
            motivation TEXT,
            experience TEXT,
            status VARCHAR(50) DEFAULT 'pending',
            reviewed_by INT,
            review_date TIMESTAMP NULL,
            decision VARCHAR(50),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            INDEX idx_email (email),
            INDEX idx_status (status),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        if (!$this->studentConn->query($sql)) {
            throw new Exception("Volunteer applications table: " . $this->studentConn->error);
        }
        
        echo "[OK] Volunteer applications table created/verified\n";
    }
    
    /**
     * Create website announcements table
     */
    private function createWebsiteAnnouncementsTable() {
        echo "[MIGRATING] Website announcements table...\n";
        
        $sql = "
        CREATE TABLE IF NOT EXISTS website_announcements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            content LONGTEXT NOT NULL,
            category VARCHAR(100),
            author VARCHAR(255),
            image_url VARCHAR(500),
            featured BOOLEAN DEFAULT FALSE,
            status VARCHAR(50) DEFAULT 'published',
            views INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            FULLTEXT INDEX idx_search (title, content),
            INDEX idx_status (status),
            INDEX idx_featured (featured),
            INDEX idx_category (category),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        if (!$this->websiteConn->query($sql)) {
            throw new Exception("Website announcements table: " . $this->websiteConn->error);
        }
        
        echo "[OK] Website announcements table created/verified\n";
    }
    
    /**
     * Create student fee accounts table
     */
    private function createStudentFeeAccountsTable() {
        echo "[MIGRATING] Student fee accounts table...\n";
        
        $sql = "
        CREATE TABLE IF NOT EXISTS student_fee_accounts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            academic_year VARCHAR(20) NOT NULL,
            semester INT,
            total_fees DECIMAL(10, 2),
            paid_fees DECIMAL(10, 2) DEFAULT 0,
            balance DECIMAL(10, 2),
            due_date DATE,
            status VARCHAR(50) DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
            UNIQUE KEY unique_student_year (student_id, academic_year, semester),
            INDEX idx_status (status),
            INDEX idx_year (academic_year),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        if (!$this->studentConn->query($sql)) {
            throw new Exception("Student fee accounts table: " . $this->studentConn->error);
        }
        
        echo "[OK] Student fee accounts table created/verified\n";
    }
}

// Run migrations if executed from command line
if (php_sapi_name() === 'cli' || (isset($_GET['migrate']) && $_GET['migrate'] === 'run')) {
    $migration = new DatabaseMigration();
    $success = $migration->runAll();
    exit($success ? 0 : 1);
}

// Return migration class for use in other files
return DatabaseMigration::class;

?>
