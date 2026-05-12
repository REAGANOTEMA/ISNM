<?php
// ISNM Database Connection Test
// Professional database connection testing for all three databases

require_once 'includes/config_enhanced.php';
require_once 'includes/auth-service.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Connection Test - ISNM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .test-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .test-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .test-header h1 {
            color: #3E2723;
            font-weight: 600;
        }
        .test-header p {
            color: #666;
            margin-top: 10px;
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
        .test-section {
            margin-bottom: 40px;
        }
        .test-section h3 {
            color: #3E2723;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .connection-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 10px;
        }
        .connection-info pre {
            background: #e9ecef;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .btn-test {
            background: #3E2723;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-test:hover {
            background: #1A237E;
            transform: translateY(-2px);
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        .stat-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
        }
        .stat-card h4 {
            color: #3E2723;
            margin-bottom: 10px;
        }
        .stat-card .number {
            font-size: 2rem;
            font-weight: bold;
            color: #28a745;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <div class="test-header">
            <h1><i class="fas fa-database"></i> ISNM Database Connection Test</h1>
            <p>Testing connectivity to all three databases: staffs_db, students_db, website_db</p>
        </div>

        <?php
        // Test all database connections
        $connection_tests = DatabaseConnection::testAllConnections();
        $connection_info = [];
        
        foreach (['staffs', 'students', 'website'] as $db) {
            $connection_info[$db] = DatabaseConnection::getConnectionInfo($db);
        }
        
        // Test authentication service
        $auth_connections = $auth_service->testConnections();
        $auth_info = $auth_service->getConnectionInfo();
        ?>

        <div class="test-section">
            <h3><i class="fas fa-plug"></i> Database Connection Status</h3>
            
            <?php foreach ($connection_tests as $db => $status): ?>
                <div class="status-card <?php echo $status ? 'status-success' : 'status-error'; ?>">
                    <h4><i class="fas fa-<?php echo $status ? 'check-circle' : 'times-circle'; ?>"></i> 
                        <?php echo ucfirst($db); ?> Database</h4>
                    <p><strong>Status:</strong> <?php echo $status ? 'Connected' : 'Failed'; ?></p>
                    
                    <?php if ($status && isset($connection_info[$db])): ?>
                        <div class="connection-info">
                            <strong>Connection Details:</strong>
                            <pre><?php print_r($connection_info[$db]); ?></pre>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="test-section">
            <h3><i class="fas fa-shield-alt"></i> Authentication Service Status</h3>
            
            <?php foreach ($auth_connections as $db => $status): ?>
                <div class="status-card <?php echo $status ? 'status-success' : 'status-error'; ?>">
                    <h4><i class="fas fa-<?php echo $status ? 'check-circle' : 'times-circle'; ?>"></i> 
                        Auth Service - <?php echo ucfirst($db); ?></h4>
                    <p><strong>Status:</strong> <?php echo $status ? 'Connected' : 'Failed'; ?></p>
                </div>
            <?php endforeach; ?>
            
            <div class="connection-info">
                <strong>Authentication Service Info:</strong>
                <pre><?php print_r($auth_info); ?></pre>
            </div>
        </div>

        <div class="test-section">
            <h3><i class="fas fa-heartbeat"></i> System Health Check</h3>
            
            <?php
            $health = checkSystemHealth();
            $overall_status = $health['overall_status'];
            ?>
            
            <div class="status-card <?php echo $overall_status === 'healthy' ? 'status-success' : 'status-warning'; ?>">
                <h4><i class="fas fa-<?php echo $overall_status === 'healthy' ? 'check-circle' : 'exclamation-triangle'; ?>"></i> 
                    Overall System Status</h4>
                <p><strong>Status:</strong> <?php echo ucfirst($overall_status); ?></p>
                <p><strong>Timestamp:</strong> <?php echo $health['timestamp']; ?></p>
            </div>
            
            <div class="stats-grid">
                <?php foreach ($health['databases'] as $db => $info): ?>
                    <div class="stat-card">
                        <h4><?php echo ucfirst($db); ?></h4>
                        <div class="number">
                            <i class="fas fa-<?php echo $info['connected'] ? 'check' : 'times'; ?>"></i>
                            <?php echo $info['connected'] ? 'Connected' : 'Failed'; ?>
                        </div>
                        <?php if (isset($info['server_info'])): ?>
                            <small>Server: <?php echo $info['server_info']; ?></small>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="test-section">
            <h3><i class="fas fa-cogs"></i> Test Operations</h3>
            
            <div class="row">
                <div class="col-md-6">
                    <button class="btn-test w-100 mb-3" onclick="testQuery('staffs')">
                        <i class="fas fa-play"></i> Test Staffs Query
                    </button>
                </div>
                <div class="col-md-6">
                    <button class="btn-test w-100 mb-3" onclick="testQuery('students')">
                        <i class="fas fa-play"></i> Test Students Query
                    </button>
                </div>
                <div class="col-md-6">
                    <button class="btn-test w-100 mb-3" onclick="testQuery('website')">
                        <i class="fas fa-play"></i> Test Website Query
                    </button>
                </div>
                <div class="col-md-6">
                    <button class="btn-test w-100 mb-3" onclick="testCrossQuery()">
                        <i class="fas fa-play"></i> Test Cross-Database Query
                    </button>
                </div>
            </div>
            
            <div id="query-results" class="mt-4"></div>
        </div>

        <div class="test-section">
            <h3><i class="fas fa-info-circle"></i> Configuration Summary</h3>
            
            <div class="connection-info">
                <strong>Database Configuration:</strong>
                <pre>
Host: <?php echo $config['host']; ?>
Username: <?php echo $config['username']; ?>
Charset: <?php echo $config['charset']; ?>
Databases: staffs_db, students_db, website_db
                </pre>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function testQuery(database) {
            const resultsDiv = document.getElementById('query-results');
            resultsDiv.innerHTML = '<div class="alert alert-info"><i class="fas fa-spinner fa-spin"></i> Testing ' + database + ' database...</div>';
            
            fetch('database_test_ajax.php?test=' + database)
                .then(response => response.json())
                .then(data => {
                    let html = '<div class="alert ' + (data.success ? 'alert-success' : 'alert-danger') + '">';
                    html += '<h5><i class="fas fa-' + (data.success ? 'check' : 'times') + '-circle"></i> ' + database.toUpperCase() + ' Database Test</h5>';
                    html += '<p><strong>Result:</strong> ' + (data.success ? 'Success' : 'Failed') + '</p>';
                    if (data.message) {
                        html += '<p><strong>Message:</strong> ' + data.message + '</p>';
                    }
                    if (data.data) {
                        html += '<pre>' + JSON.stringify(data.data, null, 2) + '</pre>';
                    }
                    html += '</div>';
                    resultsDiv.innerHTML = html;
                })
                .catch(error => {
                    resultsDiv.innerHTML = '<div class="alert alert-danger"><h5><i class="fas fa-times-circle"></i> Error</h5><p>' + error.message + '</p></div>';
                });
        }
        
        function testCrossQuery() {
            const resultsDiv = document.getElementById('query-results');
            resultsDiv.innerHTML = '<div class="alert alert-info"><i class="fas fa-spinner fa-spin"></i> Testing cross-database query...</div>';
            
            fetch('database_test_ajax.php?test=cross')
                .then(response => response.json())
                .then(data => {
                    let html = '<div class="alert ' + (data.success ? 'alert-success' : 'alert-danger') + '">';
                    html += '<h5><i class="fas fa-' + (data.success ? 'check' : 'times') + '-circle"></i> Cross-Database Query Test</h5>';
                    html += '<p><strong>Result:</strong> ' + (data.success ? 'Success' : 'Failed') + '</p>';
                    if (data.data) {
                        html += '<pre>' + JSON.stringify(data.data, null, 2) + '</pre>';
                    }
                    html += '</div>';
                    resultsDiv.innerHTML = html;
                })
                .catch(error => {
                    resultsDiv.innerHTML = '<div class="alert alert-danger"><h5><i class="fas fa-times-circle"></i> Error</h5><p>' + error.message + '</p></div>';
                });
        }
    </script>
</body>
</html>
