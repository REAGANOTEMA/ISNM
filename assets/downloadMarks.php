<?php

include("config.php");
$response = "";

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    if (isset($_POST["examId"])) {

        ?>

        <!DOCTYPE html>
        <html lang="en">

        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">


            <!-- Include html2pdf library -->
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
            <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.9.3/html2pdf.bundle.js"></script>

            <style>
                .fullBox {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    height: 100vh;
                    margin: 0;
                    font-family: 'Arial', sans-serif;
                }

                #container {
                    text-align: center;
                    margin: 0;
                }

                h2 {
                    margin-bottom: 7px;
                    color: #333;
                }

                table {
                    border-collapse: collapse;
                    width: 100%;
                    margin: auto;
                }

                th {
                    border: 1px solid #ddd;
                    padding: 10px;
                    text-align: center;
                }

                td {
                    border: 1px solid #ddd;
                    padding: 9px;
                    text-align: center;
                }

                th {
                    background-color: #f2f2f2;
                }

                .cont {
                    display: block;
                    margin: 0;
                }
            </style>
        </head>

        <body>

            <div id="pdfMakerContainer">


                <div class="cont">
                    <div class="fullBox">
                        <div id="container">
                            <h2 style="margin-bottom: 13px;">Students List</h2>

                            <table id="myTable">
                                <thead>
                                    <tr>
                                        <th>Student ID</th>
                                        <th>Name</th>
                                        <th>Marks</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>

                                    <?php
                                    $examId = $_POST["examId"] . "";

                                    $query = 'SELECT * FROM `exams` WHERE `exam_id`=?';
                                    $stmt = mysqli_prepare($conn, $query);
                                    mysqli_stmt_bind_param($stmt, "s", $examId);
                                    mysqli_stmt_execute($stmt);
                                    $result = mysqli_stmt_get_result($stmt);

                                    if (mysqli_num_rows($result) > 0) {
                                        $row = mysqli_fetch_assoc($result);

                                        $totalMarks = $row['total_marks'];
                                        $passingMarks = $row['passing_marks'];

                                        // Fetch students' marks with names via JOIN
                                        $query2 = 'SELECT m.`student_id`, m.`marks`, s.`first_name`, s.`surname`, s.`other_name`, s.`full_name` FROM `marks` m JOIN `students` s ON m.student_id = s.id WHERE m.`exam_id` = ?';
                                        $stmt2 = mysqli_prepare($conn, $query2);
                                        mysqli_stmt_bind_param($stmt2, "s", $examId);
                                        mysqli_stmt_execute($stmt2);
                                        $result2 = mysqli_stmt_get_result($stmt2);

                                        if (mysqli_num_rows($result2) > 0) {
                            
                                            $count = 1;
                                            while ($marksRow = mysqli_fetch_assoc($result2)) {
                                                $obtainedMarks = $marksRow['marks'];

                                                $passFail = ((int) ($obtainedMarks)) >= ((int) ($passingMarks)) ? "PASSED" : "FAIL";

                                                $Name = $marksRow['full_name'] ?: trim($marksRow['first_name'] . ' ' . $marksRow['other_name'] . ' ' . $marksRow['surname']);

                                                echo '<tr>
                                                <td>' . $marksRow['student_id'] . '</td>
                                                <td>' . ucfirst(strtolower($Name)) . '</td>
                                                <td>' . $obtainedMarks . '&nbsp;/&nbsp;' . $totalMarks . '</td>
                                                <td>'.$passFail.'</td>
                                            </tr>';

                                                $count++;
                                            }

                                            // Prepare response
                            

                                        } else {
                                            die("Something went wrong2");
                                        }
                                        mysqli_stmt_close($stmt2);
                                    } else {
                                        die("Something went wrong3");
                                    }
                                    mysqli_stmt_close($stmt);
    } else {
        die("Something went wrong4");
    }


    ?>

                                

                                <!-- Repeat for more rows -->
                            </tbody>
                        </table>

                    </div>
                </div>
            </div>
        </div>
            <input type="hidden" value="<?php echo htmlspecialchars($_POST['examId'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">

        <script>

           
            function printTableToPDF() {
                var element = document.querySelector('#container');

                html2pdf(element, {
                    margin: 13, // Set margin for page breaks
                });
            }
        </script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    </body>

    </html>

    <?php
} else {
    die("Something went wrong5");
}

?>