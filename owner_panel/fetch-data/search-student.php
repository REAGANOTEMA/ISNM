<?php
 include("../../assets/config.php");

$search = $_POST['search'] ?? '';
$searchPattern = "%" . $search . "%";

$sql = "SELECT id, first_name, surname, other_name, full_name, student_number, course, year, status FROM students WHERE status != 'deleted' AND (first_name LIKE ? OR surname LIKE ? OR full_name LIKE ? OR student_number LIKE ? OR course LIKE ?) ORDER BY first_name, surname ASC LIMIT 50";
$stmt = $conn->prepare($sql);
if (!$stmt) { exit; }
$stmt->bind_param("sssss", $searchPattern, $searchPattern, $searchPattern, $searchPattern, $searchPattern);
$stmt->execute();
$result = $stmt->get_result();

$count = 1;
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $displayName = $row['full_name'] ?: trim($row['first_name'] . ' ' . $row['other_name'] . ' ' . $row['surname']);
        echo "<tr>
            <th scope='row'>" . $count . "</th>
            <td>" . htmlspecialchars($displayName) . "</td>
            <td>" . htmlspecialchars($row['course'] ?? '') . " - Year " . htmlspecialchars($row['year'] ?? '') . "</td>
            <td><a href='modal-student.php?id=". $row['id'] ."'><button id='view-more' data-id='".$row['id']."' style='height: 35px; width: 100px; background-color: skyblue; color: white; border: none; border-radius: 8px; text-decoration: none;'>View More</button>
          </a></td>
        </tr>";
        $count++;
    }
}
$stmt->close();
?>
