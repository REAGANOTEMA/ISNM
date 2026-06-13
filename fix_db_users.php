<?php
header('Content-Type: text/plain');

$host = '127.0.0.1';
$port = 3307;
$rootUser = 'root';
$rootPass = '';

$conn = @new mysqli($host, $rootUser, $rootPass, '', $port);
if ($conn->connect_error) {
    die("Root connection failed: " . $conn->connect_error . "\n");
}
echo "Connected to MySQL as root\n";

$users = [
    [
        'user' => 'igangaschoolofl_students_db',
        'pass' => 'hbkKdmMHUfHTHuxWKPRf',
        'db'   => 'igangaschoolofl_students_db',
    ],
    [
        'user' => 'igangaschoolofl_staffs_db',
        'pass' => 'AgKzJjZZnT5q58jCahs8',
        'db'   => 'igangaschoolofl_staffs_db',
    ],
    [
        'user' => 'igangaschoolofl_website_db',
        'pass' => 'AaCH75gXpekcFQj5wPZn',
        'db'   => 'igangaschoolofl_website_db',
    ],
    [
        'user' => 'igangaschoolofl_ict',
        'pass' => 'HHCrQVjr6QNKzSEVtx9J',
        'db'   => 'igangaschoolofl_ict',
    ],
];

foreach ($users as $u) {
    $user = $conn->real_escape_string($u['user']);
    $pass = $conn->real_escape_string($u['pass']);
    $db   = $conn->real_escape_string($u['db']);

    $conn->query("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database '$db' ensured\n";

    $conn->query("CREATE USER IF NOT EXISTS '$user'@'localhost' IDENTIFIED BY '$pass'");
    echo "User '$user'@'localhost' ensured\n";

    $conn->query("GRANT ALL PRIVILEGES ON `$db`.* TO '$user'@'localhost'");
    echo "Grants ensured for '$user'@'localhost' on '$db'\n";

    $conn->query("FLUSH PRIVILEGES");
}

echo "\nDone.\n";
$conn->close();
