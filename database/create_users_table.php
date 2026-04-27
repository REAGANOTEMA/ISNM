<?php
// Direct database setup script for ISNM School Management System
// This script creates the missing users table and other required tables

// Database configuration
$host = 'localhost';
$dbname = 'isnm_db';
$username = 'root';
$password = 'ReagaN23#';

try {
    // Connect to MySQL
    $conn = new mysqli($host, $username, $password);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    echo "Connected to MySQL successfully.<br>";
    
    // Create database if it doesn't exist
    $sql = "CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    if ($conn->query($sql)) {
        echo "Database '$dbname' created or already exists.<br>";
    } else {
        echo "Error creating database: " . $conn->error . "<br>";
    }
    
    // Select the database
    $conn->select_db($dbname);
    
    // Create users table
    $usersTableSQL = "
    CREATE TABLE IF NOT EXISTS `users` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `user_id` varchar(20) NOT NULL,
      `username` varchar(50) NOT NULL,
      `first_name` varchar(100) NOT NULL,
      `last_name` varchar(100) NOT NULL,
      `email` varchar(150) NOT NULL,
      `password` varchar(255) NOT NULL,
      `phone` varchar(20) DEFAULT NULL,
      `role` varchar(50) NOT NULL,
      `department` varchar(100) DEFAULT NULL,
      `profile_image` varchar(255) DEFAULT 'default-avatar.png',
      `date_of_birth` date DEFAULT NULL,
      `gender` varchar(10) DEFAULT NULL,
      `address` text DEFAULT NULL,
      `nationality` varchar(50) DEFAULT NULL,
      `religion` varchar(50) DEFAULT NULL,
      `marital_status` varchar(20) DEFAULT NULL,
      `status` enum('active','inactive','suspended') DEFAULT 'active',
      `login_attempts` int(11) DEFAULT 0,
      `account_locked` tinyint(1) DEFAULT 0,
      `locked_until` datetime DEFAULT NULL,
      `last_login` datetime DEFAULT NULL,
      `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `user_id` (`user_id`),
      UNIQUE KEY `username` (`username`),
      UNIQUE KEY `email` (`email`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    if ($conn->query($usersTableSQL)) {
        echo "Users table created successfully.<br>";
    } else {
        echo "Error creating users table: " . $conn->error . "<br>";
    }
    
    // Create students table
    $studentsTableSQL = "
    CREATE TABLE IF NOT EXISTS `students` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `student_id` varchar(20) NOT NULL,
      `application_id` varchar(20) DEFAULT NULL,
      `first_name` varchar(100) NOT NULL,
      `surname` varchar(100) NOT NULL,
      `other_name` varchar(100) DEFAULT NULL,
      `date_of_birth` date NOT NULL,
      `gender` varchar(10) NOT NULL,
      `nationality` varchar(50) NOT NULL,
      `address` text DEFAULT NULL,
      `phone` varchar(20) NOT NULL,
      `email` varchar(150) NOT NULL,
      `program` varchar(100) NOT NULL,
      `level` varchar(50) NOT NULL,
      `intake_year` varchar(4) NOT NULL,
      `intake_period` varchar(50) DEFAULT NULL,
      `current_year` int(2) DEFAULT 1,
      `current_semester` int(2) DEFAULT 1,
      `enrollment_date` date NOT NULL,
      `expected_graduation_date` date DEFAULT NULL,
      `status` enum('active','suspended','graduated','withdrawn') DEFAULT 'active',
      `guardian_name` varchar(200) DEFAULT NULL,
      `guardian_phone` varchar(20) DEFAULT NULL,
      `guardian_email` varchar(150) DEFAULT NULL,
      `medical_conditions` text DEFAULT NULL,
      `emergency_contact_name` varchar(100) DEFAULT NULL,
      `emergency_contact_phone` varchar(20) DEFAULT NULL,
      `profile_image` varchar(255) DEFAULT 'default-student.png',
      `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `student_id` (`student_id`),
      UNIQUE KEY `email` (`email`),
      KEY `application_id` (`application_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    if ($conn->query($studentsTableSQL)) {
        echo "Students table created successfully.<br>";
    } else {
        echo "Error creating students table: " . $conn->error . "<br>";
    }
    
    // Insert sample staff users
    $staffUsers = [
        ['DG001', 'john.mugisha', 'John', 'Mugisha', 'john.mugisha@isnm.ac.ug', 'password', 'Director General'],
        ['CEO001', 'sarah.nakato', 'Sarah', 'Nakato', 'sarah.nakato@isnm.ac.ug', 'password', 'Chief Executive Officer'],
        ['SP001', 'peter.lutaaya', 'Peter', 'Lutaaya', 'peter.lutaaya@isnm.ac.ug', 'password', 'School Principal'],
        ['SEC001', 'joy.nabwire', 'Joy', 'Nabwire', 'joy.nabwire@isnm.ac.ug', 'password', 'School Secretary'],
        ['AR001', 'henry.mugisha', 'Henry', 'Mugisha', 'henry.mugisha@isnm.ac.ug', 'password', 'Academic Registrar'],
        ['BUR001', 'patience.nabasumba', 'Patience', 'Nabasumba', 'patience.nabasumba@isnm.ac.ug', 'password', 'School Bursar'],
        ['HR001', 'robert.ssewanyana', 'Robert', 'Ssewanyana', 'robert.ssewanyana@isnm.ac.ug', 'password', 'HR Manager'],
        ['DA001', 'michael.mukasa', 'Michael', 'Mukasa', 'michael.mukasa@isnm.ac.ug', 'password', 'Director Academics'],
        ['DI001', 'david.ssekandi', 'David', 'Ssekandi', 'david.ssekandi@isnm.ac.ug', 'password', 'Director ICT'],
        ['DF001', 'grace.namulinda', 'Grace', 'Namulinda', 'grace.namulinda@isnm.ac.ug', 'password', 'Director Finance']
    ];
    
    foreach ($staffUsers as $user) {
        $hashedPassword = password_hash($user[6], PASSWORD_DEFAULT);
        $sql = "INSERT IGNORE INTO users (user_id, username, first_name, last_name, email, password, role) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssss", $user[0], $user[1], $user[2], $user[3], $user[4], $hashedPassword, $user[5]);
        $stmt->execute();
    }
    
    echo "Sample staff users inserted successfully.<br>";
    
    // Insert sample students
    $sampleStudents = [
        ['STU001', 'Aisha', 'Nakato', 'CM1234567890123', 'aisha.nakato@isnm.ac.ug', '256771234567'],
        ['STU002', 'Brian', 'Mugisha', 'CM1234567890124', 'brian.mugisha@isnm.ac.ug', '256772345678'],
        ['STU003', 'Catherine', 'Nabwire', 'CM1234567890125', 'catherine.nabwire@isnm.ac.ug', '256773456789'],
        ['STU004', 'David', 'Ssekandi', 'CM1234567890126', 'david.ssekandi@isnm.ac.ug', '256774567890'],
        ['STU005', 'Esther', 'Nakasumba', 'CM1234567890127', 'esther.nakasumba@isnm.ac.ug', '256775678901']
    ];
    
    foreach ($sampleStudents as $student) {
        $sql = "INSERT IGNORE INTO students (student_id, first_name, surname, application_id, email, phone, program, level, intake_year, enrollment_date) VALUES (?, ?, ?, ?, ?, ?, 'Nursing', 'Year 1', '2024', CURDATE())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssss", $student[0], $student[1], $student[2], $student[3], $student[4], $student[5]);
        $stmt->execute();
    }
    
    echo "Sample student records inserted successfully.<br>";
    
    // Create activity_logs table
    $activityLogsSQL = "
    CREATE TABLE IF NOT EXISTS `activity_logs` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `user_id` varchar(20) NOT NULL,
      `user_role` varchar(50) NOT NULL,
      `activity_type` varchar(50) NOT NULL,
      `activity_description` text NOT NULL,
      `module_affected` varchar(100) DEFAULT NULL,
      `record_id` varchar(50) DEFAULT NULL,
      `ip_address` varchar(45) DEFAULT NULL,
      `user_agent` text DEFAULT NULL,
      `activity_date` datetime NOT NULL,
      `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `user_id` (`user_id`),
      KEY `activity_date` (`activity_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    if ($conn->query($activityLogsSQL)) {
        echo "Activity logs table created successfully.<br>";
    } else {
        echo "Error creating activity logs table: " . $conn->error . "<br>";
    }
    
    echo "<br><strong>Database setup completed successfully!</strong><br>";
    echo "<h3>Staff Login Credentials:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Username</th><th>Password</th><th>Role</th></tr>";
    foreach ($staffUsers as $user) {
        echo "<tr><td>{$user[1]}</td><td>password</td><td>{$user[5]}</td></tr>";
    }
    echo "</table><br>";
    
    echo "<h3>Student Login Credentials:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>NSIN Number</th><th>First Name</th><th>Contact Number</th></tr>";
    foreach ($sampleStudents as $student) {
        echo "<tr><td>{$student[3]}</td><td>{$student[1]}</td><td>{$student[5]}</td></tr>";
    }
    echo "</table><br>";
    
    echo "<h3>Next Steps:</h3>";
    echo "1. <a href='../staff-login.php'>Staff Login</a><br>";
    echo "2. <a href='../student-login.php'>Student Login</a><br>";
    echo "3. <a href='../organogram.php'>Organogram</a><br>";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}
?>
