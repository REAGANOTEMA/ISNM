<?php
$teachers_salary_query = "SELECT t.*, COALESCE(ss.basic_salary, 0) AS basic_salary FROM teachers t LEFT JOIN staff_salaries ss ON t.id = ss.staff_id ORDER BY t.fname";
$teachers_salary_result = mysqli_query($conn, $teachers_salary_query);
if (!$teachers_salary_result) {
    $teachers_salary_result = mysqli_query($conn, "SELECT t.*, 0 AS basic_salary FROM teachers t ORDER BY t.fname");
}
?>

<div class="showAttendence">

    <div class="container">
        <br>

        <div class="attendenceTable" style="display: block;">
            <div class="header">
                <i class='bx bx-credit-card'></i>
                <h3>Teachers Salary</h3>

                <div class="_flex-container">
                    <input class="form-control me-2" type="search" placeholder="Search" style="max-width: 225px;height: 40px;" id="search-teacher-name" aria-label="Search">
                    <button class="btn btn-success" type="button" id="searchTeacherByNameBtn" disabled><i class='bx bx-search-alt'></i></button>
                </div>

            </div>
            <hr class="text-danger">

            <div class="students-table">
                <table class="remove-cursor-pointer">
                    <thead>
                        <tr>
                            <th scope="col" class="thead col-2">#</th>
                            <th scope="col" class="thead col-3">Teacher ID</th>
                            <th scope="col" class="thead col-5">Name</th>
                            <th class="thead col-2">Action</th>
                        </tr>
                    </thead>

                    <tbody id="teacher-salary-table-body">
                        <?php if ($teachers_salary_result && mysqli_num_rows($teachers_salary_result) > 0) {
                            $i = 1;
                            while ($trow = mysqli_fetch_assoc($teachers_salary_result)) {
                                $salary_display = $trow['basic_salary'] > 0 ? number_format($trow['basic_salary']) : 'N/A';
                                echo "<tr>
                                    <td class='pe-1'>&nbsp;&nbsp;{$i}&nbsp;&nbsp;</td>
                                    <td>" . htmlspecialchars($trow['id']) . "</td>
                                    <td class='user'>
                                        <img src='../teacherUploads/" . htmlspecialchars($trow['image'] ?? 'default.png') . "'>
                                        <p>" . htmlspecialchars($trow['fname'] . ' ' . $trow['lname']) . "</p>
                                    </td>
                                    <td class='flex-center p-3'>
                                        <div class='btn-group-vertical' role='group' aria-label='Large button group'>
                                            <button type='button' class='btn content-center btn-outline-success text-center' data-bs-toggle='modal' data-bs-target='#show-monthly-salary'>
                                                {$salary_display}
                                                <i class='bx ms-1 bx-show-alt'></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>";
                                $i++;
                            }
                        } ?>
                    </tbody>

                </table>
            </div>
            <div class="dataNotAvailable" style="display: block;">

                <div class="_flex-container box-hide">

                    <div class="no-data-box">
                        <div class="no-dataicon">
                            <i class='bx bx-data'></i>
                        </div>
                        <p>No Data</p>
                    </div>
                </div>

            </div>



        </div>
        <hr class="text-danger">

        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
            <div class="btn-group" role="group" aria-label="Basic example">
                <button type="button" class="btn btn-secondary" id="salary-prev-btn">prev</button>
                <a class="btn btn-secondary disabled" role="button" aria-disabled="true" id="salary-page-number">1</a>
                <button type="button" class="btn btn-secondary" id="salary-next-btn">next</button>
            </div>
        </div>


    </div>


</div>






<!-- Modal -->
<div class="modal fade" id="show-monthly-salary" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                <h1 class="modal-title fs-5" id="staticBackdropLabel">Teacher Details</h1>
                </div>
                <button type="button" class="close mr-2" data-bs-dismiss="modal" aria-label="Close"><i
                        class='bx bx-x'></i></button>
            </div>
            <div class="modal-body">
                <table class="table table-stripped">
                    <thead class="table-dark">
                        <tr>
                            <th scope="col">Month</th>
                            <th scope="col">Salary</th>
                            <th scope="col">Bonus</th>
                            <th scope="col">Advance</th>
                            <th scope="col">Pay</th>
                        </tr>
                    </thead>
                    <tbody class="no-hover">
                        <?php
                        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                        foreach ($months as $m) {
                            echo "<tr>
                                <td class='p-1 m-0'>{$m}</td>
                                <td class='p-1 m-0'><input class='form-control tableInput' type='text' value='--' style='max-width: 100px;' disabled></td>
                                <td class='p-1 m-0'><input class='form-control tableInput' type='number' value='0' style='max-width: 100px;' disabled></td>
                                <td class='p-1 m-0'><input class='form-control tableInput' type='number' value='0' style='max-width: 100px;' disabled></td>
                                <td class='p-1 m-0'><button class='btn btn-success content-center' style='max-width: 100px'><i class='fa-solid fa-question me-1'></i>Paid</button></td>
                            </tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary">Understood</button>
            </div>
        </div>
    </div>
</div>