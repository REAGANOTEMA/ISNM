<?php include('partials/_header.php') ?>

<!-- confirm edit alert modal-->
<div class="modal fade" id="edit-confirmation-modal" tabindex="-1" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog  modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
            </div>
            <div class="modal-body">
                <strong>Do you really want to Edit Student Details?</strong>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-danger" id="confirm-edit-btn">Edit</button>
            </div>
        </div>
    </div>
</div>
<!-- end of onfirm edit alert modal-->

<!-- alert to delete teacher  -->
<div class="modal fade" id="delete-confirmation-modal" tabindex="-1" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog  modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
            </div>
            <div class="modal-body">
                <strong>Do you really want to delete Student?</strong>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-danger" onclick="deleteTeacherWithIdSeted()">Delete</button>
            </div>
        </div>
    </div>
</div>
<!-- end of alert to delete teacher -->
<!--add new student model -->


<div class="modal" style="z-index: 2000;" id="addTeacherModal" tabindex="-1" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Student Details</h1>
                <button type="button" class="close mr-2" data-bs-dismiss="modal" aria-label="Close"><i
                        class='bx bx-x'></i></button>
            </div>
            <form class="needs-validation" id="general-form" novalidate>
                <div class="modal-body">
                    <div class="container my-3">
                        <div class="mb-3">
                            <label for="fname" class="form-label">First Name</label>
                            <input type="text" class="form-control" placeholder="First name"
                                id="fname" name="fname" required>
                            <div class="invalid-feedback">
                                required!
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="row">
                                <div class="col">
                                    <label for="lname" class="form-label">Surname</label>
                                    <input type="text" class="form-control" placeholder="Surname"
                                        id="lname" name="lname" required>
                                    <div class="invalid-feedback">
                                        required!
                                    </div>
                                </div>
                                <div class="col">
                                    <label for="other_name" class="form-label">Other Name</label>
                                    <input type="text" class="form-control" placeholder="Other name"
                                        id="other_name" name="other_name">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="row">
                                <div class="col-6">
                                    <label for="dob" class="form-label">Date of Birth</label>
                                    <input type="date" class="form-control" id="dob"
                                        name="dob">
                                </div>
                                <div class="col-6">
                                    <label for="gender" class="form-label">Gender</label>
                                    <select class="form-select" id="gender" name="gender" style="width:100%;" required>
                                        <option selected disabled value="">--select--</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                    </select>
                                    <div class="invalid-feedback">
                                        required!
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="row">
                                <div class="col-6">
                                    <label for="course" class="form-label">Course / Program</label>
                                    <select class="form-select" id="course" name="course" style="width:100%;" required>
                                        <option selected disabled value="">--select--</option>
                                        <?php include('partials/select_courses.php') ?>
                                    </select>
                                    <div class="invalid-feedback">
                                        required!
                                    </div>
                                </div>
                                <div class="col-6">
                                    <label for="current_year" class="form-label">Year</label>
                                    <select class="form-select" id="current_year" name="current_year" style="width:100%;" required>
                                        <option selected disabled value="">--select--</option>
                                        <option value="1">Year 1</option>
                                        <option value="2">Year 2</option>
                                        <option value="3">Year 3</option>
                                        <option value="4">Year 4</option>
                                    </select>
                                    <div class="invalid-feedback">
                                        required!
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="level" class="form-label">Level</label>
                            <select class="form-select" id="level" name="level" style="width:100%;">
                                <option selected disabled value="">--select--</option>
                                <option value="Certificate">Certificate</option>
                                <option value="Diploma">Diploma</option>
                                <option value="Degree">Degree</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email"
                                name="email">
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone"
                                placeholder="e.g. 0770000000">
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">Address / District</label>
                            <input type="text" class="form-control" id="address" name="address">
                        </div>

                        <div class="mb-3">
                            <div class="row">
                                <div class="col">
                                    <label for="guardian" class="form-label">Guardian Name</label>
                                    <input type="text" class="form-control" id="guardian" name="guardian">
                                </div>
                                <div class="col">
                                    <label for="gphone" class="form-label">Guardian Phone</label>
                                    <input type="tel" class="form-control" id="gphone" name="gphone">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3" id="uploadImageField">
                            <label for="image" class="form-label">Photo</label>
                            <input class="form-control" type="file" id="uploadImage" name="image"
                                accept=".png, .jpeg, .jpg">
                        </div>

                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="general-info-btn">
                        <i class='bx bx-check'></i> <span>Save Student</span>
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

<!-- end of add new student model -->

<!-- Remove student model -->
<div class="modal removeTeacherModal" style="z-index: 2000;" id="removeStudentModel" tabindex="-1"
    aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title text-danger fs-5" id="exampleModalLabel">Delete Student</h1>
                <button type="button" class="close mr-2" data-bs-dismiss="modal" aria-label="Close"><i
                        class='bx bx-x'></i></button>
            </div>
            <form class="needs-validation" id="remove-student-form" novalidate>
                <div class="modal-body">
                    <div class="container my-3">

                        <div class="mb-3">
                            <label for="student-id" class="form-label">Student ID</label>
                            <input type="text" class="form-control remove_student_id" id="student-id" aria-describedby="" required>
                            <div class="invalid-feedback">
                                Please provide a valid Student ID.
                            </div>
                        </div>


                    </div>
                </div>
                <div class="modal-footer">

                    <button type="button" class="btn btn-danger" id="remove-student-btn">
                        <div><span>Delete Student</span></div>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- end of Remove student model -->

<!-- Sidebar -->
<?php include('partials/_sidebar.php') ?>
<input type="hidden" value="3" id="checkFileName">
<!-- End of Sidebar -->

<!-- Main Content -->
<div class="content">
    <!-- Navbar -->
    <?php include("partials/_navbar.php"); ?>

    <!-- End of Navbar -->

    <main>
        <div class="header">
            <div class="left">
                <h1>Students</h1>
                <ul class="breadcrumb">
                    <li><a href="#">

                        </a></li>

                </ul>
            </div>

        </div>
        <div class="bottom-data">

            <div class="orders">

                <!-- Nav tabs -->
                  <ul class="nav nav-tabs" id="myTab" role="tablist">
                    <li class="nav-item me-1" role="presentation">
                        <button class="nav-link active " id="addStudentTab" data-bs-toggle="tab" data-bs-target="#home" onclick="AddStudentBtnClick()" type="button" role="tab" aria-controls="home" aria-selected="true">Add Students</button>
                    </li>
                    <li class="nav-item me-1" role="presentation">
                        <button class="nav-link" id="view-students-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab" aria-controls="profile" aria-selected="false" onclick="showStudents()">Show
                            Students</button>
                    </li>
                    <li class="nav-item me-1" role="presentation">
                        <button class="nav-link" id="feedback-students-tab" data-bs-toggle="tab" data-bs-target="#feedback" type="button" role="tab" aria-controls="feedback" aria-selected="false">Feedback</button>
                    </li>



                </ul>

                <!-- Tab panes -->
                <div class="tab-content">
                    <div class="tab-pane active" id="home" role="tabpanel" aria-labelledby="home-tab" tabindex="0">
                        <br>
                        <!-- Take attendence -->
                        <div class="attendenceTable" style="display: block;">
                            <div class="header">
                                <i class='bx bx-receipt'></i>
                                <h3>Students</h3>
                                <div class="student-btns">

                                    <!-- <a class="add-btns"> <i class='bx bx-filter'></i></a> -->
                                    
                                    <div class="dropdown dropdown-center">
                                    <a class="notif" data-bs-toggle="dropdown" aria-expanded="false" id="dropDownListForSubmit">
                                        <i class='bx bx-filter'></i>
                                    </a>

                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item reset-attendence" id="add_student_dropdown" data-bs-toggle="modal" data-bs-target="#addTeacherModal">Add Student</a></li>
                                        <li><a class="dropdown-item submit-attendence" id="remove_student_dropdown" data-bs-toggle="modal" data-bs-target="#removeStudentModel">Remove Student</a></li>
                                    </ul>
                                </div>

                                </div>
                            </div>
                            <hr>
                            <br>

                            <div class="container add-remove">
                                <ul class="insights">
                                    <a class="addlink" data-bs-toggle="modal" data-bs-target="#addTeacherModal"
                                        id="addTeacherButton">
                                        <li class="additem">
                                            <!-- <i class='bx bx-calendar-check'></i> -->
                                            <i class='bx bxs-user-plus'></i>
                                            <span class="info">
                                                <h3>
                                                    Add
                                                </h3>
                                                <h3>Student</h3>
                                            </span>
                                        </li>
                                    </a>
                                    <!-- model add student -->





                                    <!-- end of model add student -->

                                    <a class="removelink" id="remove-student-jumbo-btn" data-bs-toggle="modal" data-bs-target="#removeStudentModel">
                                        <li class="removeitem">
                                            <i class='bx bxs-user-minus'></i>
                                            <span class="info">
                                                <h3>
                                                    Remove
                                                </h3>
                                                <h3>Student</h3>
                                            </span>
                                        </li>
                                    </a>
                                </ul>
                            </div>

                            <br>
                            <hr>
                        </div>

                        <!-- end of Take attendence -->
                    </div>
                    <br>
                    <div class="tab-pane" id="profile" role="tabpanel" aria-labelledby="profile-tab" tabindex="0">
                        <div class="showAttendence">
                            <br>
                            <div class="header">
                                <i class='bx bx-list-ul'></i>
                                <h3>Students List</h3>

                            </div>

                            <hr>
                            <br>
                            <div class="container" style="display: flex;">

                                <div class="row g-3 align-items-center">
                                    <div class="col-auto">
                                        <label class="col-form-label">&nbsp;Course&nbsp; </label>
                                    </div>
                                    <div class="col-auto">
                                        <select class="form-select" aria-label="Default select example"
                                            id="search-class">
                                            <option value="">All Courses</option>
                                            <option value="Certificate in Midwifery">Certificate in Midwifery</option>
                                            <option value="Certificate in Nursing">Certificate in Nursing</option>
                                            <option value="Diploma in Nursing">Diploma in Nursing</option>
                                            <option value="Diploma in Midwifery">Diploma in Midwifery</option>
                                            <option value="Diploma in Nursing Education">Diploma in Nursing Education</option>
                                            <option value="Bachelor of Science in Nursing">Bachelor of Science in Nursing</option>
                                        </select>
                                    </div>
                                </div>


                            </div>
                            <br>
                            <div class="container" style="display: flex;">
                                <div class="row g-3 align-items-center">
                                    <div class="col-auto">
                                        <label class="col-form-label">Year </label>
                                    </div>
                                    <div class="col-auto">
                                        <select class="form-select" aria-label="Default select example"
                                            id="search-section">
                                            <option value="">All Years</option>
                                            <option value="1">Year 1</option>
                                            <option value="2">Year 2</option>
                                            <option value="3">Year 3</option>
                                            <option value="4">Year 4</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <br>

                            <div class="container">
                                 <a class="find" onclick="findAndshowStudents()">
                                <i class='bx bx-search-alt'></i>
                                    <span>Find</span>
                                </a>

                            </div>
                            <br>
                            <hr>



                            <!-- Attendence on Specific date  -->
                            <div class="container">
                                <br>
                                <!-- Take attendence -->
                                <div class="attendenceTable" style="display: block;">
                                    <div class="header">
                                        <i class='bx bx-list-ul'></i>
                                        <h3>Students List</h3>

                                        <!-- <a href="#" class="excel">
                                            <i class="fa fa-file-excel-o" aria-hidden="true"></i>
                                            <span>EXCEL</span>
                                        </a>

                                        <a href="#" class="report">
                                            <i class='bx bxs-file-pdf'></i>
                                            <span>PDF</span>
                                        </a> -->

                                        <div class="_flex-container">
                                        <input class="form-control me-2" type="search" placeholder="Search by name, student number, or index..." style="max-width: 300px;height: 40px;" id="search-teacher-name"
                                            aria-label="Search">
                                        <button class="btn btn-success" type="button" id="searchTeacherByNameBtn"><i class='bx bx-search-alt'></i></button>
                                    </div>

                                    </div>
                                    <hr class="text-danger">

                                    <div class="container" style="display: flex;">



                                    </div>
                                 

                                    <!--table-->
                                    <div class="students-table">
                                        <table class="remove-cursor-pointer">
                                            <thead>
                                                <tr>
                                                    <th scope="col" class="thead col-1">#</th>
                                                    <th scope="col" class="thead col-2">Student No.</th>
                                                    <th scope="col" class="thead col-4">Name</th>
                                                    <th scope="col" class="thead col-2">Course</th>
                                                    <th scope="col" class="thead col-1">Year</th>
                                                    <th scope="col" class="thead col-2">Action</th>
                                                </tr>
                                            </thead>

                                            <tbody id="teacher-table-body">
                                                <!-- content come form database -->
                                            </tbody>

                                        </table>
                                    </div>
                                    <div id="dataNotAvailable">

                                        <div class="_flex-container box-hide">

                                            <div class="no-data-box">
                                                <div class="no-dataicon">
                                                    <i class='bx bx-data'></i>
                                                </div>
                                                <p>No Data</p>
                                            </div>
                                        </div>

                                    </div>
                                    <!--END table-->
                                </div>
                                <hr class="text-danger">

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <div class="btn-group" role="group" aria-label="Basic example">
                                        <button type="button" class="btn btn-secondary" id="prev-page-btn">prev</button>
                                        <a class="btn btn-secondary disabled" role="button" aria-disabled="true"
                                            id="page-number">1</a>
                                        <button type="button" class="btn btn-secondary" id="next-page-btn">next</button>
                                    </div>
                                </div>


                            </div>
                            <!-- Attendence on Specific date  -->

                        </div>
                    </div>
                    <br>
                    <div class="tab-pane" id="feedback" role="tabpanel" aria-labelledby="feedback-tab" tabindex="0">

                        <?php include('partials/student-shared/feedback-tab.php')  ?>

                    </div>


                </div>

            </div>


        </div>

    </main>


</div>


<script src="../assets/js/student.js"></script>
<?php include('partials/_footer.php'); ?>