<?php
include("config.php");

$response = array();
$response["status"] = "";
$response['content'] = '<option selected disabled value="">--select--</option>';

if ($_SERVER['REQUEST_METHOD'] == "POST") {

    $json_data = file_get_contents("php://input");
    $data = json_decode($json_data, true);

    $course = trim($data["class"] ?? '');
    $year = intval($data["section"] ?? 0);

    $where = ["status != 'deleted'"];
    $params = [];
    $types = '';

    if (!empty($course)) {
        $where[] = "course = ?";
        $params[] = $course;
        $types .= 's';
    }
    if ($year > 0) {
        $where[] = "year = ?";
        $params[] = $year;
        $types .= 'i';
    }

    $whereClause = implode(" AND ", $where);
    $query = "SELECT * FROM students WHERE $whereClause ORDER BY first_name ASC, surname ASC";

    if (!empty($params)) {
        $stmt = $conn->prepare($query);
        if ($stmt) {
            if (!empty($types)) { $stmt->bind_param($types, ...$params); }
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = null;
        }
    } else {
        $result = $conn->query($query);
    }

    if ($result && $result->num_rows > 0) {
        $response["status"] = "success";
        while ($row = $result->fetch_assoc()) {
            $name = $row['full_name'] ?: trim($row['first_name'] . ' ' . $row['other_name'] . ' ' . $row['surname']);
            $response['content'] .= "<option value='" . $row['id'] . "'>" . htmlspecialchars($name) . "</option>";
        }
    } else {
        $response['status'] = "NO_DATA";
    }
} else {
    $response['status'] = "Invalid request";
}
echo json_encode($response);
?>
