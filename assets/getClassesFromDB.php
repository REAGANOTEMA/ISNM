<?php
include("config.php");
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode([]); exit; }

try {
    $result = $conn->query("SELECT DISTINCT class FROM students WHERE class IS NOT NULL AND class != '' ORDER BY class");
    $classes = [];
    if ($result) while ($row = $result->fetch_assoc()) $classes[] = $row['class'];
    echo json_encode($classes);
} catch (Exception $e) { error_log('getClassesFromDB error: ' . $e->getMessage()); echo json_encode([]); }
