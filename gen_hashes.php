<?php
$passwords = [
    'Techno123','DorisJoy2026','Lovely2God','Stephen123',
    'isnm2026','Isnm2026','Alexis2026','isnm4life',
    'Life2save','Isnm4life','safty1st','2268926931','bursar@isnm'
];
foreach ($passwords as $p) {
    echo $p . ' => ' . password_hash($p, PASSWORD_BCRYPT) . "\n";
}
