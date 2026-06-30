<?php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'Director General';
$_SESSION['type'] = 'staff';
$_SESSION['full_name'] = 'Director General';
$_SERVER['PHP_SELF'] = '/ISNM/dashboards/director-general.php';
$_GET['page'] = 'home';

require_once 'C:/xampp/htdocs/ISNM/config/database.php';
require_once 'C:/xampp/htdocs/ISNM/includes/module_registry.php';
require_once 'C:/xampp/htdocs/ISNM/includes/dynamic_sidebar.php';

ob_start();
renderDynamicSidebar();
$html = ob_get_clean();

echo "HTML length: " . strlen($html) . PHP_EOL;
echo "Has nav tag: " . (strpos($html, '<nav') !== false ? 'YES' : 'NO') . PHP_EOL;
echo "Has sidebar class: " . (strpos($html, 'isnm-sidebar') !== false ? 'YES' : 'NO') . PHP_EOL;
echo "Has menu-group: " . (strpos($html, 'menu-group') !== false ? 'YES' : 'NO') . PHP_EOL;
echo "Has child-link: " . (strpos($html, 'child-link') !== false ? 'YES' : 'NO') . PHP_EOL;
echo "Module count: " . substr_count($html, 'child-link') . PHP_EOL;
echo "Group count: " . substr_count($html, 'menu-group-header') . PHP_EOL;
echo "---FIRST 2000---" . PHP_EOL;
echo substr($html, 0, 2000) . PHP_EOL;
echo "---END---" . PHP_EOL;
