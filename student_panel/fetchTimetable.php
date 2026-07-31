<?php
include('../assets/config.php');
$response = array();
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = (int)($_SESSION['uid'] ?? 0);

    $stmt = mysqli_prepare($conn, "SELECT `class`,`section` FROM `students` WHERE `id` = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$row) {
        $response['status'] = "error";
        echo json_encode($response);
        exit;
    }

    $class = $row['class'];
    $section = $row['section'];

    $stmt = mysqli_prepare($conn, "SELECT * FROM `time_table` WHERE `class` = ? AND `section` = ?");
    mysqli_stmt_bind_param($stmt, 'ss', $class, $section);
    mysqli_stmt_execute($stmt);
    $result2 = mysqli_stmt_get_result($stmt);

    $daysOfWeek = array('mon', 'tue', 'wed', 'thu', 'fri', 'sat');
    $response['status'] = "success";
    $timetable = array();

    while ($row2 = $result2->fetch_assoc()) {
        foreach ($daysOfWeek as $day) {
            $timetable[$day][] = array(
                "start_time" => $row2['start_time'],
                "subject" => $row2[$day],
                "end_time" => $row2['end_time']
            );
        }
    }

    $response['data'] = $timetable;
} else {
    $response['status'] = "error";
}

echo json_encode($response);
?>
