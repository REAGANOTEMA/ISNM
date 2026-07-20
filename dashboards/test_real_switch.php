<?php
// Simulate staff_dashboard_access.php behavior
if (ob_get_level() === 0) {
    ob_start();
}

// Suppress display errors like the real file
ini_set('display_errors', '0');

$_GET['page'] = $_GET['page'] ?? 'home';

// Simulate the page routing from director-general.php
$dgPageToSection = [
    'home' => 'home',
    'executive' => 'executive',
    'financial' => 'financial', 
    'departments' => 'departments',
];
$dgPage  = $_GET['page'] ?? 'home';
$dgSection = $dgPageToSection[$dgPage] ?? 'executive';
?>
<!DOCTYPE html>
<html>
<head><title>Test - <?= htmlspecialchars($dgSection) ?></title></head>
<body>
<h1>Testing Section: <?= htmlspecialchars($dgSection) ?></h1>
<!-- dgSection=<?= $dgSection ?> -->

<div class="dg-content">
<?php
switch ($dgSection):
    case 'home': ?>
        <div id="home" class="content-section active" data-section="home">
            HOME CONTENT
        </div>
    <?php break;
    case 'services': ?>
        <div id="services" class="content-section active" data-section="services">
            SERVICES CONTENT
        </div>
    <?php break; ?>
    <?php case 'executive': ?>
        <div id="executive" class="content-section active" data-section="executive">
            EXECUTIVE CONTENT
        </div>
    <?php break; ?>
    <?php case 'financial': ?>
        <div id="financial" class="content-section active" data-section="financial">
            FINANCIAL CONTENT
        </div>
    <?php break; ?>
    <?php case 'departments': ?>
        <div id="departments" class="content-section active" data-section="departments">
            DEPARTMENTS CONTENT
        </div>
    <?php break;
endswitch; ?>
</div>

<p>AFTER SWITCH</p>
</body>
</html>
<?php
if (ob_get_level()) ob_end_flush();
