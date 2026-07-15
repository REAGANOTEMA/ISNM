<?php
 include("../../assets/config.php");
 $sql = "SELECT id, first_name, surname, other_name, full_name, student_number, course, year, status FROM students WHERE status != 'deleted' ORDER BY first_name, surname ASC";
 $result = $conn->query($sql);
 $count = 1;
 if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $displayName = $row['full_name'] ?: trim($row['first_name'] . ' ' . $row['other_name'] . ' ' . $row['surname']);
        echo "<tr>
      <th scope='row'>" . $count . "</th>
      <td>" . htmlspecialchars($displayName) . "</td>
      <td>" . htmlspecialchars($row['course'] ?? '') . " - Year " . htmlspecialchars($row['year'] ?? '') . "</td>
      <td><a href='modal-student.php?id=". $row['id'] ."'><button id='view-more' data-id='".$row['id']."' style='height: 35px; width: 100px; background-color: skyblue; color: white; border: none; border-radius: 8px; text-decoration: none;'>View More</button>
          </a>
      </td>
    </tr>";
    $count++;
    }
 }
?>
