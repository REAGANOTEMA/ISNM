<?php
include("config.php");
require_once __DIR__ . '/require_admin.php';

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8');

$sql = "SELECT * FROM students WHERE bus_request_status = 'pending' ORDER BY bus_request_date DESC LIMIT 50";
$result = mysqli_query($conn, $sql);

if ($result) {
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $displayName = $row['full_name'] ?: trim($row['first_name'] . ' ' . $row['other_name'] . ' ' . $row['surname']);
            echo '<div class="alert alert-primary" role="alert">
                    ' . htmlspecialchars($displayName) . ' - ' . htmlspecialchars($row['course'] ?? '') . ' Year ' . htmlspecialchars($row['year'] ?? '') . '  <br>
                    <form method="POST" action="partials/Accept-request.php" style="display:inline">
                        <input type="hidden" name="csrf_token" value="' . $csrf . '">
                        <input type="hidden" name="id" value="' . (int)$row['id'] . '">
                        <button type="submit" class="btn btn-success">Accept Request</button>
                    </form>
                  </div>';
        }
    } else {
        echo "No pending requests.";
    }
} else {
    echo "Error loading requests.";
}
