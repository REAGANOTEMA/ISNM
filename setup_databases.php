<?php
// ISNM Database Setup Script
// Creates all three databases and tests connections

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$host = 'localhost';
$username = 'root';
$password = 'ReagaN23#';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>ISNM Database Setup</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css' rel='stylesheet'>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .setup-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 30px;
            max-width: 800px;
            margin: 0 auto;
        }
        .status-card {
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid;
        }
        .status-success {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
        .status-error {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }
        .status-warning {
            background: #fff3cd;
            border-color: #ffc107;
            color: #856404;
        }
        .btn-setup {
            background: #3E2723;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-setup:hover {
            background: #1A237E;
            transform: translateY(-2px);
        }
        .log-output {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            font-family: 'Courier New', monospace;
            white-space: pre-wrap;
            max-height: 300px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class='setup-container'>
        <div class='text-center mb-4'>
            <h1><i class='fas fa-database'></i> ISNM Database Setup</h1>
            <p>Creating and testing all three databases: staffs_db, students_db, website_db</p>
        </div>";

try {
    // Create databases
    $databases = ['staffs_db', 'students_db', 'website_db'];
    $results = [];
    
    foreach ($databases as $database) {
        echo "<div class='status-card status-warning'>";
        echo "<h4><i class='fas fa-spinner fa-spin'></i> Creating {$database}...</h4>";
        echo "</div>";
        
        try {
            $conn = new mysqli($host, $username, $password);
            
            if ($conn->connect_error) {
                throw new Exception("Connection failed: " . $conn->connect_error);
            }
            
            // Create database
            $sql = "CREATE DATABASE IF NOT EXISTS {$database} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
            if (!$conn->query($sql)) {
                throw new Exception("Failed to create {$database}: " . $conn->error);
            }
            
            $results[$database] = [
                'status' => 'success',
                'message' => 'Database created successfully'
            ];
            
            echo "<div class='status-card status-success'>";
            echo "<h4><i class='fas fa-check-circle'></i> {$database} Created Successfully</h4>";
            echo "</div>";
            
            $conn->close();
            
        } catch (Exception $e) {
            $results[$database] = [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
            
            echo "<div class='status-card status-error'>";
            echo "<h4><i class='fas fa-times-circle'></i> Failed to Create {$database}</h4>";
            echo "<p>Error: " . $e->getMessage() . "</p>";
            echo "</div>";
        }
    }
    
    echo "<div class='text-center mt-4'>";
    echo "<button class='btn-setup' onclick='testConnections()'><i class='fas fa-play'></i> Test Database Connections</button>";
    echo "</div>";
    
    echo "<div id='test-results' class='log-output' style='display: none;'></div>";
    
} catch (Exception $e) {
    echo "<div class='status-card status-error'>";
    echo "<h4><i class='fas fa-times-circle'></i> Setup Failed</h4>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<script>
function testConnections() {
    document.getElementById('test-results').style.display = 'block';
    document.getElementById('test-results').innerHTML = '<i class=\"fas fa-spinner fa-spin\"></i> Testing database connections...';
    
    fetch('database_test_ajax.php?test=connection')
        .then(response => response.json())
        .then(data => {
            let html = '<h5><i class=\"fas fa-' + (data.success ? 'check' : 'times') + '-circle\"></i> Connection Test Results</h5>';
            html += '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
            document.getElementById('test-results').innerHTML = html;
        })
        .catch(error => {
            document.getElementById('test-results').innerHTML = '<h5><i class=\"fas fa-times-circle\"></i> Error</h5><p>' + error.message + '</p>';
        });
}
</script>
</div>
</body>
</html>";
?>
