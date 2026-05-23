<?php
require_once 'config/database.php';

$sql = file_get_contents('create_databases.sql');
// Split by semicolon and trim
$queries = array_map('trim', explode(';', $sql));
$queries = array_filter($queries, function($q) { return !empty($q); });

// We need to connect without selecting a database to run CREATE DATABASE.
$host = DB_HOST;
$user = DB_USER;
$pass = DB_PASS;
$charset = DB_CHARSET;

$setupConn = new mysqli($host, $user, $pass, '', 3306); // Leave database empty
if ($setupConn->connect_error) {
    die("Connection failed: " . $setupConn->connect_error);
}
$setupConn->set_charset($charset);

echo "Setting up databases...<br>";
foreach ($queries as $query) {
    if ($setupConn->query($query) === FALSE) {
        echo "Error executing query: " . $setupConn->error . "<br>";
        echo "Query: " . htmlspecialchars($query) . "<br>";
    } else {
        echo "Executed: " . htmlspecialchars($query) . "<br>";
    }
}

$setupConn->close();
echo "Done.";
?>