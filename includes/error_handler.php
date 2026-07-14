<?php
/**
 * Enhanced Error Handler for ISNM System
 * Provides comprehensive error reporting and database diagnostics
 */

if (class_exists('ErrorHandler', false)) return;

require_once __DIR__ . '/../config/database.php';

class ErrorHandler {
    private static $initialized = false;
    
    public static function initialize() {
        if (self::$initialized) return;
        
        // Set error handler
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);
        
        self::$initialized = true;
    }
    
    public static function handleError($errno, $errstr, $errfile, $errline) {
        // Respect @ operator suppression
        if (!(error_reporting() & $errno)) {
            return false;
        }
        
        $isProduction = defined('APP_ENV') && APP_ENV === 'production';
        
        error_log("Error [$errno]: $errstr in $errfile on line $errline");
        
        if (!$isProduction) {
            echo "Error [$errno]: $errstr in $errfile on line $errline";
        }
        
        return false;
    }
    
    public static function handleException($exception) {
        error_log("Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine());
        error_log("Stack trace: " . $exception->getTraceAsString());
        
        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, "Fatal: " . $exception->getMessage() . "\n");
            exit(1);
        }

        http_response_code(500);
        if (ob_get_level()) ob_clean();
        $msg = htmlspecialchars($exception->getMessage());
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>System Error</title>';
        echo '<style>body{font-family:sans-serif;background:#fef2f2;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}';
        echo '.card{background:#fff;border-radius:12px;padding:32px;border:1px solid #fecaca;max-width:600px;text-align:center}';
        echo 'h2{color:#dc2626;margin:0 0 10px}p{color:#64748b}a{color:#2563eb;text-decoration:none}</style></head>';
        echo '<body><div class="card"><h2>Internal Server Error</h2>';
        echo '<p>The system encountered an internal error. Our team has been notified.</p>';
        $base = (basename(dirname($_SERVER['SCRIPT_NAME'] ?? '')) === 'dashboards') ? '..' : '.';
        echo '<p><a href="' . $base . '/health-check.php">Run Health Check</a></p></div></body></html>';
        exit(1);
    }
    
    public static function handleShutdown() {
        $error = error_get_last();
        
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            error_log("Fatal Error: " . $error['message'] . " in " . $error['file'] . " on line " . $error['line']);
        }
    }
    
    /**
     * Get diagnostic information about database connections
     */
    public static function getDatabaseDiagnostics() {
        $diagnostics = [
            'timestamp' => date('Y-m-d H:i:s'),
            'environment' => defined('APP_ENV') ? APP_ENV : 'unknown',
            'databases' => []
        ];
        
        $dbFunctions = [
            'Students' => 'getStudentsConnection',
            'Staff' => 'getStaffConnection',
            'Website' => 'getWebsiteConnection',
            'ICT' => 'getICTConnection',
        ];
        
        foreach ($dbFunctions as $name => $func) {
            if (function_exists($func)) {
                $conn = @$func();
                if ($conn && !$conn->connect_error) {
                    $diagnostics['databases'][$name] = [
                        'status' => 'connected',
                        'host' => $conn->get_server_info(),
                    ];
                    $conn->close();
                } else {
                    $diagnostics['databases'][$name] = [
                        'status' => 'failed',
                        'error' => $conn ? $conn->connect_error : 'Function returned null',
                        'last_error' => $GLOBALS['isnm_last_db_error'] ?? 'None recorded',
                    ];
                }
            } else {
                $diagnostics['databases'][$name] = [
                    'status' => 'unavailable',
                    'error' => 'Function not found'
                ];
            }
        }
        
        return $diagnostics;
    }
    
    /**
     * Render a user-friendly error page
     */
    public static function renderDatabaseUnavailableError() {
        $diagnostics = self::getDatabaseDiagnostics();
        $isDebug = defined('APP_DEBUG') && APP_DEBUG;
        
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Database Unavailable</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 20px;
                }
                .container {
                    background: white;
                    border-radius: 12px;
                    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                    max-width: 600px;
                    width: 100%;
                    padding: 40px;
                    text-align: center;
                }
                .icon {
                    font-size: 60px;
                    margin-bottom: 20px;
                }
                h1 {
                    color: #1f2937;
                    font-size: 28px;
                    margin-bottom: 10px;
                }
                .message {
                    color: #6b7280;
                    font-size: 16px;
                    line-height: 1.6;
                    margin-bottom: 30px;
                }
                .action {
                    display: flex;
                    gap: 10px;
                    justify-content: center;
                    margin-bottom: 30px;
                }
                .button {
                    display: inline-block;
                    padding: 12px 24px;
                    border-radius: 8px;
                    text-decoration: none;
                    font-weight: 500;
                    transition: all 0.3s ease;
                    cursor: pointer;
                    border: none;
                    font-size: 14px;
                }
                .button-primary {
                    background: #667eea;
                    color: white;
                }
                .button-primary:hover {
                    background: #5568d3;
                }
                .button-secondary {
                    background: #e5e7eb;
                    color: #374151;
                }
                .button-secondary:hover {
                    background: #d1d5db;
                }
                .info {
                    background: #f3f4f6;
                    border-left: 4px solid #667eea;
                    padding: 15px;
                    text-align: left;
                    margin-top: 20px;
                    border-radius: 4px;
                }
                .info-title {
                    font-weight: 600;
                    color: #1f2937;
                    margin-bottom: 10px;
                }
                .info-text {
                    color: #6b7280;
                    font-size: 13px;
                    line-height: 1.6;
                    font-family: 'Courier New', monospace;
                }
                .diagnostics {
                    background: #fef2f2;
                    border-left: 4px solid #dc2626;
                    padding: 15px;
                    text-align: left;
                    margin-top: 20px;
                    border-radius: 4px;
                    display: none;
                }
                .diagnostics.show {
                    display: block;
                }
                .diagnostics-title {
                    font-weight: 600;
                    color: #7f1d1d;
                    margin-bottom: 10px;
                    cursor: pointer;
                }
                .diagnostics-content {
                    color: #7f1d1d;
                    font-size: 12px;
                    line-height: 1.5;
                    font-family: 'Courier New', monospace;
                    max-height: 300px;
                    overflow-y: auto;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="icon">🔴</div>
                <h1>Database Connection Failed</h1>
                <p class="message">
                    The system is currently unable to connect to the database. Please try again in a few moments, or contact the system administrator if the problem persists.
                </p>
                
                <div class="action">
                    <button class="button button-primary" onclick="location.reload()">Retry</button>
                    <button class="button button-secondary" onclick="openDiagnostics()">Diagnostics</button>
                </div>
                
                <div class="info">
                    <div class="info-title">What might have happened:</div>
                    <div class="info-text">
                        • MySQL/MariaDB service is not running<br>
                        • Database credentials are incorrect<br>
                        • Database server is overloaded<br>
                        • Network connectivity issue<br>
                        • Database user permissions issue
                    </div>
                </div>
                
                <?php if ($isDebug): ?>
                <div class="diagnostics">
                    <div class="diagnostics-title" onclick="toggleDiagnostics()">📊 Debug Information (Click to expand)</div>
                    <div class="diagnostics-content">
                        <pre><?php echo htmlspecialchars(json_encode($diagnostics, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)); ?></pre>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <script>
                function openDiagnostics() {
                    toggleDiagnostics();
                }
                
                function toggleDiagnostics() {
                    const diagnostics = document.querySelector('.diagnostics');
                    if (diagnostics) {
                        diagnostics.classList.toggle('show');
                    }
                }
            </script>
        </body>
        </html>
        <?php
        exit;
    }
}

// Initialize error handler
ErrorHandler::initialize();
?>
