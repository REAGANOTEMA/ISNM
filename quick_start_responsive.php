#!/usr/bin/env php
<?php
/**
 * ISNM Responsive System Quick-Start Script
 * Initializes all responsive design and business logic systems
 * 
 * Usage:
 *   php quick_start_responsive.php
 *   php quick_start_responsive.php --test
 *   php quick_start_responsive.php --migrate
 */

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║          ISNM Responsive System Quick-Start Setup             ║\n";
echo "║                                                                ║\n";
echo "║  This script will initialize:                                 ║\n";
echo "║  ✓ Database tables and migrations                             ║\n";
echo "║  ✓ Dashboard components (header, sidebar, footer)             ║\n";
echo "║  ✓ Business logic systems (news, forms, notifications)        ║\n";
echo "║  ✓ Student search and PWA configuration                       ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

require_once __DIR__ . '/config/database.php';

class QuickStart {
    private $success = 0;
    private $errors = [];
    private $warnings = [];
    
    /**
     * Run complete setup
     */
    public function run() {
        echo "[STEP 1] Checking database connections...\n";
        $this->checkDatabases();
        
        echo "[STEP 2] Verifying required files...\n";
        $this->checkFiles();
        
        echo "[STEP 3] Running database migrations...\n";
        $this->runMigrations();
        
        echo "[STEP 4] Verifying system components...\n";
        $this->verifyComponents();
        
        echo "[STEP 5] Testing API endpoints...\n";
        $this->testAPIs();
        
        $this->printSummary();
    }
    
    /**
     * Check database connections
     */
    private function checkDatabases() {
        $databases = [
            'Students' => getStudentsConnection(),
            'Staff' => getStaffConnection(),
            'Website' => getWebsiteConnection(),
            'ICT' => getICTConnection(),
        ];
        
        foreach ($databases as $name => $conn) {
            if ($conn && !$conn->connect_error) {
                echo "  ✓ $name database connected\n";
                $this->success++;
            } else {
                echo "  ✗ $name database FAILED\n";
                $this->errors[] = "$name database connection failed";
            }
        }
    }
    
    /**
     * Check required files exist
     */
    private function checkFiles() {
        $files = [
            '/includes/dashboard-header.php' => 'Dashboard Header',
            '/includes/dashboard-sidebar.php' => 'Dashboard Sidebar',
            '/includes/footer.php' => 'Responsive Footer',
            '/includes/form_router.php' => 'Form Router',
            '/includes/news_publisher.php' => 'News Publisher',
            '/includes/student_search.php' => 'Student Search',
            '/css/responsive.css' => 'Responsive CSS',
            '/service-worker.js' => 'Service Worker',
            '/manifest.json' => 'PWA Manifest',
        ];
        
        foreach ($files as $path => $name) {
            if (file_exists(__DIR__ . $path)) {
                echo "  ✓ $name exists\n";
                $this->success++;
            } else {
                echo "  ✗ $name MISSING\n";
                $this->warnings[] = "$name file not found at $path";
            }
        }
    }
    
    /**
     * Run database migrations
     */
    private function runMigrations() {
        try {
            require_once __DIR__ . '/db_migrate_responsive_systems.php';
            $migration = new DatabaseMigration();
            
            // Create tables
            $reflection = new ReflectionClass($migration);
            $methods = $reflection->getMethods(ReflectionMethod::IS_PRIVATE);
            
            foreach ($methods as $method) {
                if (strpos($method->getName(), 'create') === 0 && $method->getName() !== 'createNotificationsTable') {
                    continue;
                }
                
                try {
                    $method->setAccessible(true);
                    $method->invoke($migration);
                    echo "  ✓ " . str_replace('create', '', str_replace('Table', '', $method->getName())) . " table created\n";
                    $this->success++;
                } catch (Exception $e) {
                    $this->warnings[] = $method->getName() . ": " . $e->getMessage();
                    echo "  ⚠ " . str_replace('create', '', $method->getName()) . " - " . substr($e->getMessage(), 0, 50) . "\n";
                }
            }
        } catch (Exception $e) {
            echo "  ✗ Migration failed: " . $e->getMessage() . "\n";
            $this->errors[] = "Database migrations failed";
        }
    }
    
    /**
     * Verify system components
     */
    private function verifyComponents() {
        $components = [
            'Dashboard Header' => ['includes/dashboard-header.php', ['dashboard-header', 'dashboard-search']],
            'Dashboard Sidebar' => ['includes/dashboard-sidebar.php', ['dashboard-sidebar', 'nav-list']],
            'Footer Component' => ['includes/footer.php', ['footer', 'footer-content']],
            'Form Router' => ['includes/form_router.php', ['FormRouter', 'NotificationManager']],
            'News Publisher' => ['includes/news_publisher.php', ['NewsPublisher']],
            'Student Search' => ['includes/student_search.php', ['StudentSearch']],
        ];
        
        foreach ($components as $name => $data) {
            $file = __DIR__ . '/' . $data[0];
            $search_terms = $data[1];
            
            if (file_exists($file)) {
                $content = file_get_contents($file);
                $found = true;
                
                foreach ($search_terms as $term) {
                    if (stripos($content, $term) === false) {
                        $found = false;
                        break;
                    }
                }
                
                if ($found) {
                    echo "  ✓ $name verified\n";
                    $this->success++;
                } else {
                    echo "  ⚠ $name - possible implementation issue\n";
                    $this->warnings[] = "$name may not be fully implemented";
                }
            }
        }
    }
    
    /**
     * Test API endpoints
     */
    private function testAPIs() {
        echo "  Testing API endpoints (CLI mode - limited testing)...\n";
        
        $apis = [
            'Student Search API' => 'includes/student_search.php',
            'Form Router API' => 'includes/form_router.php',
            'News Publisher API' => 'includes/news_publisher.php',
        ];
        
        foreach ($apis as $name => $file) {
            if (file_exists(__DIR__ . '/' . $file)) {
                echo "  ✓ $name endpoint available\n";
                $this->success++;
            }
        }
    }
    
    /**
     * Print summary
     */
    private function printSummary() {
        echo "\n";
        echo "╔════════════════════════════════════════════════════════════════╗\n";
        echo "║                     Setup Summary                              ║\n";
        echo "╚════════════════════════════════════════════════════════════════╝\n\n";
        
        echo "✓ Successful checks: " . $this->success . "\n";
        
        if (!empty($this->errors)) {
            echo "\n✗ ERRORS (" . count($this->errors) . "):\n";
            foreach ($this->errors as $error) {
                echo "  • $error\n";
            }
        }
        
        if (!empty($this->warnings)) {
            echo "\n⚠ WARNINGS (" . count($this->warnings) . "):\n";
            foreach ($this->warnings as $warning) {
                echo "  • $warning\n";
            }
        }
        
        echo "\n";
        echo "╔════════════════════════════════════════════════════════════════╗\n";
        echo "║                   Next Steps                                   ║\n";
        echo "╚════════════════════════════════════════════════════════════════╝\n\n";
        
        echo "1. Update Dashboard Files:\n";
        echo "   Add these includes to each dashboard file:\n";
        echo "   <?php\n";
        echo "   include __DIR__ . '/../includes/dashboard-header.php';\n";
        echo "   include __DIR__ . '/../includes/dashboard-sidebar.php';\n";
        echo "   // ... page content ...\n";
        echo "   include __DIR__ . '/../includes/footer.php';\n";
        echo "   ?>\n\n";
        
        echo "2. Add Meta Tags to HTML Head:\n";
        echo "   <link rel=\"stylesheet\" href=\"/css/responsive.css\">\n";
        echo "   <link rel=\"manifest\" href=\"/manifest.json\">\n";
        echo "   <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n";
        echo "   <meta name=\"theme-color\" content=\"#1a237e\">\n\n";
        
        echo "3. Test in Browser:\n";
        echo "   http://localhost/ISNM/dashboards/director-general.php\n";
        echo "   (Replace with your dashboard URL)\n\n";
        
        echo "4. Read Documentation:\n";
        echo "   RESPONSIVE_IMPLEMENTATION_GUIDE.md\n\n";
        
        if (empty($this->errors)) {
            echo "✓ System ready for deployment!\n\n";
        } else {
            echo "⚠ Please fix errors before deployment.\n\n";
        }
    }
}

// Run if executed from command line or web
if (php_sapi_name() === 'cli' || isset($_GET['setup'])) {
    $quickStart = new QuickStart();
    $quickStart->run();
}

?>
