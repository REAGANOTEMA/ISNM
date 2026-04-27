<?php
// Quick database setup for ISNM
$host = 'localhost';
$dbname = 'isnm_db';
$username = 'root';
$password = 'ReagaN23#';

try {
    $conn = new mysqli($host, $username, $password);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Select database
    $conn->select_db($dbname);
    
    // Create users table
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id int(11) NOT NULL AUTO_INCREMENT,
        user_id varchar(20) NOT NULL,
        username varchar(50) NOT NULL,
        first_name varchar(100) NOT NULL,
        last_name varchar(100) NOT NULL,
        email varchar(150) NOT NULL,
        password varchar(255) NOT NULL,
        role varchar(50) NOT NULL,
        status enum('active','inactive','suspended') DEFAULT 'active',
        login_attempts int(11) DEFAULT 0,
        account_locked tinyint(1) DEFAULT 0,
        locked_until datetime DEFAULT NULL,
        last_login datetime DEFAULT NULL,
        created_at timestamp DEFAULT CURRENT_TIMESTAMP,
        updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY user_id (user_id),
        UNIQUE KEY username (username),
        UNIQUE KEY email (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($sql)) {
        echo "Users table created successfully!<br>";
    } else {
        echo "Error creating users table: " . $conn->error . "<br>";
    }
    
    // Create students table
    $studentsSQL = "CREATE TABLE IF NOT EXISTS students (
        id int(11) NOT NULL AUTO_INCREMENT,
        student_id varchar(20) NOT NULL,
        application_id varchar(20) DEFAULT NULL,
        first_name varchar(100) NOT NULL,
        surname varchar(100) NOT NULL,
        other_name varchar(100) DEFAULT NULL,
        date_of_birth date NOT NULL,
        gender varchar(10) NOT NULL,
        nationality varchar(50) NOT NULL,
        address text DEFAULT NULL,
        phone varchar(20) NOT NULL,
        email varchar(150) NOT NULL,
        program varchar(100) NOT NULL,
        level varchar(50) NOT NULL,
        intake_year varchar(4) NOT NULL,
        intake_period varchar(50) DEFAULT NULL,
        current_year int(2) DEFAULT 1,
        current_semester int(2) DEFAULT 1,
        enrollment_date date NOT NULL,
        expected_graduation_date date DEFAULT NULL,
        status enum('active','suspended','graduated','withdrawn') DEFAULT 'active',
        login_attempts int(11) DEFAULT 0,
        account_locked tinyint(1) DEFAULT 0,
        locked_until datetime DEFAULT NULL,
        last_login datetime DEFAULT NULL,
        guardian_name varchar(200) DEFAULT NULL,
        guardian_phone varchar(20) DEFAULT NULL,
        guardian_email varchar(150) DEFAULT NULL,
        medical_conditions text DEFAULT NULL,
        emergency_contact_name varchar(100) DEFAULT NULL,
        emergency_contact_phone varchar(20) DEFAULT NULL,
        profile_image varchar(255) DEFAULT 'default-student.png',
        created_at timestamp DEFAULT CURRENT_TIMESTAMP,
        updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY student_id (student_id),
        UNIQUE KEY email (email),
        KEY application_id (application_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($studentsSQL)) {
        echo "Students table created successfully!<br>";
    } else {
        echo "Error creating students table: " . $conn->error . "<br>";
    }
    
    // Insert sample staff users
    $users = [
        ['DG001', 'john.mugisha', 'John', 'Mugisha', 'john.mugisha@isnm.ac.ug', 'password', 'Director General'],
        ['BUR001', 'patience.nabasumba', 'Patience', 'Nabasumba', 'patience.nabasumba@isnm.ac.ug', 'password', 'School Bursar'],
        ['AR001', 'henry.mugisha', 'Henry', 'Mugisha', 'henry.mugisha@isnm.ac.ug', 'password', 'Academic Registrar']
    ];
    
    foreach ($users as $user) {
        $hashedPassword = password_hash($user[5], PASSWORD_DEFAULT);
        $sql = "INSERT IGNORE INTO users (user_id, username, first_name, last_name, email, password, role) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssss", $user[0], $user[1], $user[2], $user[3], $user[4], $hashedPassword, $user[6]);
        $stmt->execute();
    }
    
    // Insert sample students
    $students = [
        ['STU001', 'Aisha', 'Nakato', 'CM1234567890123', 'aisha.nakato@isnm.ac.ug', '256771234567'],
        ['STU002', 'Brian', 'Mugisha', 'CM1234567890124', 'brian.mugisha@isnm.ac.ug', '256772345678'],
        ['STU003', 'Catherine', 'Nabwire', 'CM1234567890125', 'catherine.nabwire@isnm.ac.ug', '256773456789'],
        ['STU004', 'David', 'Ssekandi', 'CM1234567890126', 'david.ssekandi@isnm.ac.ug', '256774567890'],
        ['STU005', 'Esther', 'Nakasumba', 'CM1234567890127', 'esther.nakasumba@isnm.ac.ug', '256775678901']
    ];
    
    foreach ($students as $student) {
        $sql = "INSERT IGNORE INTO students (student_id, first_name, surname, application_id, email, phone, program, level, intake_year, enrollment_date) VALUES (?, ?, ?, ?, ?, ?, 'Nursing', 'Year 1', '2024', CURDATE())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssss", $student[0], $student[1], $student[2], $student[3], $student[4], $student[5]);
        $stmt->execute();
    }
    
    echo "Sample users and students inserted successfully!<br>";
    echo "<h3>Staff Login Credentials:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Username</th><th>Password</th><th>Role</th></tr>";
    foreach ($users as $user) {
        echo "<tr><td>{$user[1]}</td><td>password</td><td>{$user[6]}</td></tr>";
    }
    echo "</table><br>";
    
    echo "<h3>Student Login Credentials:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>NSIN Number</th><th>First Name</th><th>Contact Number</th></tr>";
    foreach ($students as $student) {
        echo "<tr><td>{$student[3]}</td><td>{$student[1]}</td><td>{$student[5]}</td></tr>";
    }
    echo "</table><br>";
    
    echo "<a href='staff-login.php'>Staff Login</a> | <a href='student-login.php'>Student Login</a>";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
