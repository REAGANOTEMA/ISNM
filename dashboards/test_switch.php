<?php
// Test PHP switch with unclosed PHP blocks before case labels
$x = $_GET['x'] ?? 'home';

echo "<!-- x=$x -->";

switch ($x):
    case 'home': ?>
        <div id="home">HOME CONTENT</div>
    <?php break;
    case 'executive': ?>
        <div id="executive">EXECUTIVE CONTENT</div>
    <?php break;
    case 'financial': ?>
        <div id="financial">FINANCIAL CONTENT</div>
    <?php break;
endswitch;

echo 'AFTER SWITCH';
