<?php
/**
 * Script to set up the ISNM database infrastructure:
 * 1. Create and populate the three databases (students, staffs, website)
 * 2. Import student data from the students_data folder
 * 3. Fix any remaining issues
 */

$host = '127.0.0.1';
$user = 'root';
$pass = 'ReagaN23#';
$port = 3307;

$databases = [
    'igangaschoolofl_students_db',
    'igangaschoolofl_staffs_db',
    'igangaschoolofl_website_db'
];

$mysql_exe = "C:/xampp/mysql/bin/mysql.exe";

echo "Setting up databases...<br>";

foreach ($databases as $db) {
    echo "Processing database: $db<br>";
    
    // Drop and create database using mysql command line
    // Note: We don't use backticks because the database names are simple and don't require them.
    $cmd = "\"$mysql_exe\" -u $user -p$pass --host=$host --port=$port -e \"DROP DATABASE IF EXISTS $db; CREATE DATABASE $db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\"";
    exec($cmd, $output, $return_var);
    if ($return_var !== 0) {
        echo "  Error creating database: " . implode('<br>', $output) . "<br>";
        continue;
    }
    echo "  Database created successfully.<br>";
    
    // Import the appropriate SQL file
    if ($db === 'igangaschoolofl_students_db') {
        $sql_file = 'sql/students/01_create_students_database.sql';
        $data_import = 'sql/students/02_student_data_import.sql';
    } elseif ($db === 'igangaschoolofl_staffs_db') {
        $sql_file = 'sql/staffs/04_final_complete_staffs_database.sql';
    } elseif ($db === 'igangaschoolofl_website_db') {
        $sql_file = 'sql/website/01_create_website_database.sql';
    }
    
    if (file_exists($sql_file)) {
        echo "  Importing schema from $sql_file<br>";
        // For Windows, we can use the mysql command line with input redirection via cmd
        $sql_file_path = str_replace('/', '\\', realpath($sql_file));
        $cmd = "cmd /c \"$mysql_exe\" -u $user -p$pass --host=$host --port=$port $db < \"$sql_file_path\"";
        exec($cmd, $output, $return_var);
        if ($return_var !== 0) {
            echo "  Error importing schema: " . implode('<br>', $output) . "<br>";
        } else {
            echo "  Schema imported successfully.<br>";
        }
    } else {
        echo "  SQL file not found: $sql_file<br>";
    }
    
    // For students, also import the data
    if ($db === 'igangaschoolofl_students_db' && file_exists($data_import)) {
        echo "  Importing student data from $data_import<br>";
        $data_file_path = str_replace('/', '\\', realpath($data_import));
        $cmd = "cmd /c \"$mysql_exe\" -u $user -p$pass --host=$host --port=$port $db < \"$data_file_path\"";
        exec($cmd, $output, $return_var);
        if ($return_var !== 0) {
            echo "  Error importing student data: " . implode('<br>', $output) . "<br>";
        } else {
            echo "  Student data imported successfully.<br>";
        }
    }
    
    echo "<br>";
}

// Now, run the PHP-based student import from the Excel files (if needed)
echo "Running PHP-based student data import from Excel files...<br>";
if (file_exists('import_student_data.php')) {
    // Suppress output buffering issues by using output buffering
    ob_start();
    include 'import_student_data.php';
    $output = ob_get_clean();
    echo $output;
} else {
    echo "import_student_data.php not found.<br>";
}

echo "<br>Setup complete.";
?>