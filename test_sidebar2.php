<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$_SERVER['DOCUMENT_ROOT'] = 'C:/xampp/htdocs';
chdir('C:/xampp/htdocs/ISNM');

// Simulate session
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'Director General';
$_SESSION['type'] = 'staff';
$_SESSION['full_name'] = 'Director General';
$_SERVER['PHP_SELF'] = '/ISNM/dashboards/director-general.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'CLI Test';
$_GET['page'] = 'home';

// Include the sidebar like a dashboard does
ob_start();
require_once 'C:/xampp/htdocs/ISNM/includes/sidebar.php';
$html = ob_get_clean();

// Check output
echo "Output length: " . strlen($html) . PHP_EOL;
echo "Has DOCTYPE: " . (strpos($html, '<!DOCTYPE') !== false ? 'YES' : 'NO') . PHP_EOL;
echo "Has <nav>: " . (strpos($html, '<nav') !== false ? 'YES' : 'NO') . PHP_EOL;
echo "Has isnm-sidebar: " . (strpos($html, 'isnm-sidebar') !== false ? 'YES' : 'NO') . PHP_EOL;
echo "Has menu-group: " . (strpos($html, 'menu-group') !== false ? 'YES' : 'NO') . PHP_EOL;
echo "Has child-link: " . (strpos($html, 'child-link') !== false ? 'YES' : 'NO') . PHP_EOL;
echo "Module links: " . substr_count($html, 'child-link') . PHP_EOL;
echo "Group headers: " . substr_count($html, 'menu-group-header') . PHP_EOL;

// Extract first 3 hrefs
preg_match_all('/href="([^"]*)".*?class="child-link/', $html, $matches);
echo "First 5 link hrefs:" . PHP_EOL;
foreach (array_slice($matches[1], 0, 5) as $i => $href) {
    echo "  " . ($i+1) . ". $href" . PHP_EOL;
}
