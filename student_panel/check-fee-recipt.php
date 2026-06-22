<?php include("../assets/noSessionRedirect.php"); ?>

<?php include("./verifyRoleRedirect.php"); ?>

<?php include("../assets/config.php"); ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
    <link rel="stylesheet" type="text/css" href="style.css">
    <!-- <link rel="stylesheet" type="text/css" href="css/style.css"> -->
    <title>Fee Recipt</title>
    <link rel="shortcut icon" href="./images/school-logo.png">
    <link rel="stylesheet" href="../css/oranbyte-google-translator.css">
    <script src="../js/oranbyte-google-translator.js"></script>
    <style type="text/css">
      .see-payment{
  height: auto;
  width: 80%;
  display: flex;
  position: absolute;
  border: .2px solid lightgray;
  flex-direction: column;
  margin-left: 10%;
  margin-top: 3%;
  border-radius: 5px;
  padding: 10px;
  background-color: ghostwhite;

}
#paid{
   height: 50px;
   width: 150px;
   background-color: lightgreen;
   color: black;
   border: none;
   border-radius: 5px;
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
          <a class="nav-link active" aria-current="page" href="fee-payment.php">Home</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="check-fee-recipt.php">Fee Receipt</a>
        </li>
        
        <li class="nav-item">
          <a class="nav-link" href="index.php">Back to Main Page</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="logout.php">Logout</a>
        </li>
      </ul>
      <form class="d-flex">
        <input class="form-control me-2" type="search" placeholder="Search" aria-label="Search">
        <button class="btn btn-outline-success" type="submit">Search</button>
      </form>
    </div>
  </div>
</nav>
    </div>
    <div class="see-payment">
      <div class="notice-body">
        <?php
        $student_id = $_SESSION['uid'];
        $receipt_query = "SELECT si.*, s.fname, s.lname FROM student_invoices si JOIN students s ON si.student_id = s.id WHERE s.id = ? ORDER BY si.issue_date DESC LIMIT 1";
        $stmt = mysqli_prepare($conn, $receipt_query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $student_id);
            mysqli_stmt_execute($stmt);
            $receipt_result = mysqli_stmt_get_result($stmt);
            if ($row = mysqli_fetch_assoc($receipt_result)) {
                echo "<h2>Title: " . htmlspecialchars($row['fee_type']) . "</h2>";
                echo "<h5>Student: " . htmlspecialchars($row['fname'] . " " . $row['lname']) . "</h5>";
                echo "<h5>Amount: " . number_format($row['total_amount'], 2) . "</h5>";
                echo "<p>Date: " . htmlspecialchars($row['issue_date']) . "</p>";
                echo "<p>Status: " . htmlspecialchars($row['status']) . "</p>";
                echo "<button id='paid'>Paid Successfully</button>";
            } else {
                echo "<p>No fee receipt found.</p>";
            }
            mysqli_stmt_close($stmt);
        }
        ?>
      </div>
    </div>
  </body>
  </html>