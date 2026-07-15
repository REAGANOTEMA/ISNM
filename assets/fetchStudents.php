<?php

include("config.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $postData = file_get_contents("php://input");
    $data = json_decode($postData, true);

    $name = trim($data['name'] ?? '');
    $course = trim($data['as'] ?? '');
    $year = trim($data['a'] ?? '');

    $resultOutput = array();
    $query = "";
    $params = [];
    $types = '';

    $where = ["status != 'deleted'"];
    if ($course !== '') { $where[] = "course = ?"; $params[] = $course; $types .= 's'; }
    if ($year !== '') { $where[] = "year = ?"; $params[] = intval($year); $types .= 'i'; }

    $whereClause = implode(" AND ", $where);

    if ($name === '') {
        $query = "SELECT * FROM students WHERE $whereClause ORDER BY first_name, surname ASC";
    } else {
        $search = '%' . $name . '%';
        $query = "SELECT * FROM students WHERE $whereClause AND (first_name LIKE ? OR surname LIKE ? OR full_name LIKE ? OR student_number LIKE ? OR registration_number LIKE ? OR index_number LIKE ?) ORDER BY first_name, surname ASC";
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
        $types .= 'ssssss';
    }

    $stmt = $conn->prepare($query);
    if ($stmt) {
        if (!empty($params)) { $stmt->bind_param($types, ...$params); }
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $count = 1;
            while ($row = $result->fetch_assoc()) {
                $displayName = $row["full_name"] ?: trim($row["first_name"] . " " . $row["other_name"] . " " . $row["surname"]);
                $image = '../studentUploads/' . ($row['profile_picture'] ?: $row['passport_photo'] ?? '');
                $image = (!empty($row['profile_picture']) && file_exists($image)) ? $image : "../images/user.png";
                $tid = $row['id'];

                $resultOutput[$count - 1] = "<tr>
               <td>&nbsp;&nbsp;" . $count . ".&nbsp;&nbsp;</td>
                <td>" . htmlspecialchars($row['student_number'] ?? $tid) . "</td>
                <td class='user'>
                    <img src='" . $image . "'>
                    <p>" . htmlspecialchars($displayName) . "</p>
                </td>
                <td>" . htmlspecialchars($row['course'] ?? '') . "</td>
                <td>" . htmlspecialchars($row['year'] ?? '') . "</td>
                <td class='flex-center'>
                    <div class='edit-delete'>
                        <a onclick='editStudent(`" . $tid . "`)'   class='edit' >
                            <i class='bx bxs-edit'></i>
                            <span>&nbsp;Edit</span>
                        </a>
                        <a onclick='deleteStudentWithId(`" . $tid . "`)'  class='delete'>
                            &nbsp;&nbsp;<i class='bx bxs-trash'></i>
                            <span>&nbsp;Delete</span>
                            &nbsp;&nbsp;
                        </a>
                    </div>
                </td>
            </tr>";

                $count = $count + 1;
            }
        } else {
            $resultOutput[0] = "No_Record";
        }

        $stmt->close();
    } else {
        $resultOutput[0] = "Error in preparing statement: " . $conn->error;
    }
} else {
    $resultOutput[0] = "Error";
}

$jsonData = json_encode($resultOutput);
echo $jsonData;

?>
