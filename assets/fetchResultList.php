<?php

include("config.php");
$response = array();

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    if (isset($_POST["examId"])) {
        $examId = $_POST["examId"] . "";
        $subject = $_POST["subject"] . "";

        // Fetch exam details
        $query = 'SELECT * FROM `exams` WHERE `exam_id`=? LIMIT 1';
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $examId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);

            $totalMarks = $row['total_marks'];
            $passingMarks = $row['passing_marks'];

            // Fetch students' marks with names via JOIN
            $query2 = 'SELECT m.`student_id`, m.`marks`, s.`first_name`, s.`surname`, s.`other_name`, s.`full_name` FROM `marks` m JOIN `students` s ON m.student_id = s.id WHERE m.`exam_id` = ? AND m.`subject` = ?';
            $stmt2 = mysqli_prepare($conn, $query2);
            mysqli_stmt_bind_param($stmt2, "ss", $examId, $subject);
            mysqli_stmt_execute($stmt2);
            $result2 = mysqli_stmt_get_result($stmt2);

            if (mysqli_num_rows($result2) > 0) {
                $count = 1;
                $response['data'] = "";
                while ($marksRow = mysqli_fetch_assoc($result2)) {
                    $obtainedMarks = $marksRow['marks'];

                    $passFail = ((int)($obtainedMarks)) >= ((int)($passingMarks)) ? "style='color:green;'": "style='color:red;'";

                    $Name = $marksRow['full_name'] ?: trim($marksRow['first_name'] . ' ' . $marksRow['other_name'] . ' ' . $marksRow['surname']);
                    $response['status'] = "success";
                    $response['data'] .= '<tr>
                    <th scope="row">'.$count.'</th>
                    <td>'.$marksRow['student_id'].'</td>
                    <td>
                        <p>'. ucfirst(strtolower($Name)).'</p>
                    </td>

                    <td '.$passFail.'>'.$obtainedMarks.' / '.$totalMarks.'</td>
                </tr>';

                    $count++;
                }
                

            } else {
                $response['status'] = "error";
                $response['message'] = "No student data found for this exam!";
            }
            mysqli_stmt_close($stmt2);
        } else {
            $response['status'] = "error";
            $response['message'] = "No exam found with provided ID! ";
        }
        mysqli_stmt_close($stmt);
    } else {
        $response['status'] = "error";
        $response['message'] = "Exam ID not provided!";
    }
} else {
    $response['status'] = "error";
    $response['message'] = "Invalid request method!";
}

echo json_encode($response);

?>