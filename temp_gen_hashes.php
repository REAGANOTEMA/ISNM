<?php
$passwords = [
    'DorisJoy2026',
    'Lovely2God',
    'Stephen123',
    'isnm2026',
    'Isnm2026',
    'isnm4life',
    'Life2save',
    'Alexis2026',
    '2268926931',
    'Techno123',
    'safty1st',
    'bursar@isnm',
    'student@isnm',
];
foreach ($passwords as $pw) {
    $hash = password_hash($pw, PASSWORD_BCRYPT);
    $valid = password_verify($pw, $hash);
    echo "$pw => $hash (valid: " . ($valid ? 'YES' : 'NO') . ")\n";
}
