<?php
include("config.php");
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode([]); exit; }

try {
    $result = $conn->query("SELECT DISTINCT course FROM students WHERE course IS NOT NULL AND course != '' AND status != 'deleted' ORDER BY course");
    $courses = [];
    if ($result) while ($row = $result->fetch_assoc()) $courses[] = $row['course'];
    echo json_encode($courses);
} catch (Exception $e) { error_log('getClassesFromDB error: ' . $e->getMessage()); echo json_encode([]); }
