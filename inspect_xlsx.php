<?php
$dir = __DIR__ . '/students_data/';
foreach (glob($dir . '*.xlsx') as $f) {
    echo "\n=== " . basename($f) . " ===\n";
    $zip = new ZipArchive();
    if ($zip->open($f) !== true) { echo "Cannot open ZIP\n"; continue; }
    echo "Files in ZIP:\n";
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        $size = $zip->statIndex($i)['size'];
        echo "  $name ($size bytes)\n";
    }
    // Try reading sheet1
    $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
    if ($sheet) {
        echo "sheet1.xml first 500 chars:\n";
        echo substr($sheet, 0, 500) . "\n";
    } else {
        echo "No sheet1.xml found\n";
        // Check for encryption marker
        $enc = $zip->getFromName('EncryptionInfo');
        if ($enc !== false) echo "*** FILE IS ENCRYPTED ***\n";
    }
    $zip->close();
    break; // just first file
}
unlink(__FILE__);
