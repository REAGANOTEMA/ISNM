<?php
include("../assets/noSessionRedirect.php"); 
include('./fetch-data/verfyRoleRedirect.php');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/x-icon" href="../images/school-logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
    <link rel="stylesheet" type="text/css" href="style.css">
    <link rel="stylesheet" type="text/css" href="css/style.css">
    <title>ERP</title>
    <link rel="stylesheet" href="../css/oranbyte-google-translator.css">
    <script src="../js/oranbyte-google-translator.js"></script>
    <style type="text/css">
         .card{
                position: absolute;
                margin-top: 5%;
         }
         .detail{
         	height: auto;
         	width: 100%;
         	display: flex;
         	justify-content: center;
         	flex-direction: row;
         }
         .card{
         	width: 40%;
         }
         @media (max-width: 700px){
         	.card{
         		width: 80%;
         	}
         }
    </style>
</head>
<body>
	<div class="header">
        <nav class="navbar navbar-expand-lg navbar-light bg-light">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.php">SCHOOL MANAGEMENT</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarSupportedContent">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link active" aria-current="page" href="index.php">Home</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="notices.php">Notice</a>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Fee Pay
          </a>
          <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
            <li><a class="dropdown-item" href="make-payment.php">Make Payment</a></li>
            <li><a class="dropdown-item" href="see-payment.php">See Payment</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="#">Something else here</a></li>
          </ul>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="change-password.php">Change-Password</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#" onclick="event.preventDefault();var f=document.createElement('form');f.method='POST';f.action='logout.php';document.body.appendChild(f);f.submit();">Logout</a>
        </li>
      </ul>
    </div>
  </div>
</nav>
    </div>
	<div class="detail">
    <?php
  $data = "";
  $id = intval($_GET['id'] ?? 0);
  if ($id > 0) {
      $stmt = $conn->prepare("SELECT * FROM students WHERE id = ? AND status != 'deleted'");
      if ($stmt) {
          $stmt->bind_param("i", $id);
          $stmt->execute();
          $result = $stmt->get_result();
          if ($result && $result->num_rows > 0) {
              while ($row = $result->fetch_assoc()) {
                  $displayName = $row['full_name'] ?: trim($row['first_name'] . ' ' . $row['other_name'] . ' ' . $row['surname']);
                  $image = '../studentUploads/' . ($row['profile_picture'] ?? $row['passport_photo'] ?? '');
                  $image = (!empty($row['profile_picture']) && file_exists($image)) ? $image : "../images/user.png";
                  $data .= "<div class='card'>
                      <img src='" . $image . "' class='card-img-top' alt='profile image'/>
                      <div class='card-body'>
                          <h5 class='card-title'>" . htmlspecialchars($displayName) . "</h5>
                          <p class='card-text'>Student No: " . htmlspecialchars($row['student_number'] ?? '') . "</p>
                      </div>
                      <ul class='list-group list-group-light list-group-small'>
                          <li class='list-group-item px-4'>Name: " . htmlspecialchars($displayName) . "</li>
                          <li class='list-group-item px-4'>Email: " . htmlspecialchars($row['email'] ?? '') . "</li>
                          <li class='list-group-item px-4'>Course: " . htmlspecialchars($row['course'] ?? $row['program'] ?? '') . "</li>
                          <li class='list-group-item px-4'>Year: " . htmlspecialchars($row['year'] ?? $row['current_year'] ?? '') . "</li>
                          <li class='list-group-item px-4'>Gender: " . htmlspecialchars($row['gender'] ?? '') . "</li>
                          <li class='list-group-item px-4'>Phone: " . htmlspecialchars($row['phone'] ?? $row['mobile_number'] ?? '') . "</li>
                          <li class='list-group-item px-4'>D-O-B: " . htmlspecialchars($row['date_of_birth'] ?? '') . "</li>
                          <li class='list-group-item px-4'>Address: " . htmlspecialchars($row['address'] ?? '') . "</li>
                          <li class='list-group-item px-4'>Status: " . htmlspecialchars($row['status'] ?? '') . "</li>
                      </ul>
                  </div>";
              }
          }
          $stmt->close();
      }
  }
  echo $data;
?>
</div>
<br><br>
</body>
</html>
