<?php
// Update student database to support advanced ID format: U001/CM/056/16
$host = 'localhost';
$dbname = 'isnm_db';
$username = 'root';
$password = 'ReagaN23#';

try {
    $conn = new mysqli($host, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    echo "Connected to database successfully.<br>";
    
    // Add new student_id_format column for advanced ID format
    $check_sql = "SHOW COLUMNS FROM students LIKE 'student_id_format'";
    $result = $conn->query($check_sql);
    
    if ($result && $result->num_rows == 0) {
        $add_sql = "ALTER TABLE students ADD COLUMN student_id_format varchar(50) DEFAULT NULL AFTER application_id";
        if ($conn->query($add_sql)) {
            echo "✅ Column 'student_id_format' added successfully.<br>";
        } else {
            echo "❌ Error adding student_id_format column: " . $conn->error . "<br>";
        }
    } else {
        echo "ℹ️ Column 'student_id_format' already exists.<br>";
    }
    
    // Update existing students with new ID format
    $update_sql = "UPDATE students SET student_id_format = CONCAT('U', LPAD(student_id, 3, '0'), '/CM/', LPAD(student_id, 3, '0'), '/16') WHERE student_id_format IS NULL";
    if ($conn->query($update_sql)) {
        echo "✅ Existing students updated with new ID format.<br>";
    } else {
        echo "❌ Error updating student ID format: " . $conn->error . "<br>";
    }
    
    // Insert sample students with new format
    $sample_students = [
        ['U041/CM/056/16', 'Aisha', 'Nakato', 'U041', '256771234567'],
        ['U042/CM/057/16', 'Brian', 'Mugisha', 'U042', '256772345678'],
        ['U043/CM/058/16', 'Catherine', 'Nabwire', 'U043', '256773456789'],
        ['U044/CM/059/16', 'David', 'Ssekandi', 'U044', '256774567890'],
        ['U045/CM/060/16', 'Esther', 'Nakasumba', 'U045', '256775678901']
    ];
    
    foreach ($sample_students as $student) {
        $check_existing = "SELECT student_id FROM students WHERE student_id_format = ?";
        $check_stmt = $conn->prepare($check_existing);
        $check_stmt->bind_param("s", $student[0]);
        $check_stmt->execute();
        $existing_result = $check_stmt->get_result();
        
        if ($existing_result->num_rows == 0) {
            $insert_sql = "INSERT INTO students (student_id_format, first_name, surname, application_id, phone, program, level, intake_year, enrollment_date) VALUES (?, ?, ?, ?, ?, ?, 'Nursing', 'Year 1', '2024', CURDATE())";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("sssss", $student[0], $student[1], $student[2], $student[3], $student[4]);
            $insert_stmt->execute();
            echo "✅ Added student: " . $student[0] . "<br>";
        } else {
            echo "ℹ️ Student " . $student[0] . " already exists.<br>";
        }
    }
    
    echo "<br><strong>✅ Student ID System Updated Successfully!</strong><br>";
    echo "<h3>Sample Student Login Credentials:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Student ID</th><th>First Name</th><th>Contact</th></tr>";
    foreach ($sample_students as $student) {
        echo "<tr><td>" . $student[0] . "</td><td>" . $student[1] . "</td><td>" . $student[4] . "</td></tr>";
    }
    echo "</table><br>";
    echo "<a href='student-login.php'>Test Advanced Student Login</a>";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
