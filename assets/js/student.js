
var editing = false;
var editingTeacherId = "";

// page settings
var beginIndex = 0;
var limit = 10;
var counter = 1;

document.addEventListener('DOMContentLoaded', function(){
    showStudents();
});

document.getElementById('addTeacherButton').addEventListener('click', function () {
    editing = false;
    cleanForm();
    document.getElementById("uploadImageField").style.display = "block";
});
document.getElementById("add_student_dropdown").addEventListener("click", function(){
    editing = false;
    cleanForm();
    document.getElementById("uploadImageField").style.display = "block";
});
document.getElementById("remove-student-jumbo-btn").addEventListener("click", function(){
    document.querySelector(".remove_student_id").value = "";
});
document.getElementById("remove_student_dropdown").addEventListener("click", function(){
    document.querySelector(".remove_student_id").value = "";
});

// Add/Edit student - single form submit
document.getElementById("general-info-btn").addEventListener("click", function(){
    var form = document.querySelector('#general-form');
    if (!form.checkValidity()) {
        form.classList.add('was-validated');
        return;
    }

    var formElement = document.querySelector('#general-form');
    var formData = new FormData(formElement);

    var imageInput = document.getElementById('uploadImage');
    if (imageInput.files[0]) {
        formData.append('image', imageInput.files[0]);
    }

    if (!editing) {
        sendDataToServer(formData);
    } else {
        formData.append('id', editingTeacherId);
        sendEditToServer(formData);
    }
});

function sendDataToServer(formData) {
    var myToast = new bootstrap.Toast(document.getElementById('liveToast'));
    var liveToast = document.getElementById("liveToast");

    fetch('../assets/addStudent.php', {
        method: 'POST',
        body: formData,
    })
    .then(response => response.text())
    .then(data => {
        if (data.indexOf("success") !== -1) {
            liveToast.style.backgroundColor = "#BBF7D0";
            liveToast.style.color = 'green';
            document.getElementById('toast-alert-message').innerHTML = "Student successfully added";
            cleanForm();
            $('#addTeacherModal').modal('hide');
            showStudents();
        } else {
            liveToast.style.backgroundColor = "#FECDD3";
            liveToast.style.color = 'red';
            document.getElementById('toast-alert-message').innerHTML = data;
        }
        myToast.show();
    })
    .catch(error => {
        console.error("Error:", error);
    });
}

function sendEditToServer(formData) {
    var myToast = new bootstrap.Toast(document.getElementById('liveToast'));
    var liveToast = document.getElementById("liveToast");

    fetch('../assets/editStudent.php', {
        method: 'POST',
        body: formData,
    })
    .then(response => response.text())
    .then(data => {
        if (data.indexOf("success") !== -1) {
            liveToast.style.backgroundColor = "#BBF7D0";
            liveToast.style.color = 'green';
            document.getElementById('toast-alert-message').innerHTML = "Student details updated successfully";
            cleanForm();
            $('#addTeacherModal').modal('hide');
            showStudents();
        } else {
            liveToast.style.backgroundColor = "#FECDD3";
            liveToast.style.color = 'red';
            document.getElementById('toast-alert-message').innerHTML = data;
        }
        myToast.show();
    })
    .catch(error => {
        console.error("Error:", error);
    });
}

function cleanForm() {
    var genForm = document.getElementById('general-form');
    Array.from(genForm.elements).forEach(function (element) {
        element.value = "";
    });
    genForm.classList.remove('was-validated');
    editing = false;
    editingTeacherId = "";
}

// Remove student
(function() {
    'use strict';
    var removeTeacherBtn = document.getElementById('remove-student-btn');
    var removeTeacherForm = document.querySelector('#remove-student-form');

    removeTeacherBtn.addEventListener('click', function(event) {
        event.preventDefault();
        event.stopPropagation();

        if (removeTeacherForm.checkValidity()) {
            var id = document.getElementById('student-id').value;
            var myToast = new bootstrap.Toast(document.getElementById('liveToast'));
            var liveToast = document.getElementById("liveToast");

            fetch('../assets/removeStudent.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'studentid=' + encodeURIComponent(id),
            })
            .then(response => response.text())
            .then(data => {
                if (data.indexOf("success") != -1) {
                    liveToast.style.backgroundColor = "#BBF7D0";
                    liveToast.style.color = 'green';
                    document.getElementById('toast-alert-message').innerHTML = "Student removed successfully";
                } else {
                    liveToast.style.backgroundColor = "#FECDD3";
                    liveToast.style.color = 'red';
                    document.getElementById('toast-alert-message').innerHTML = data;
                }
                document.getElementById("student-id").value = "";
                $(".removeTeacherModal").modal("hide");
                myToast.show();
                showStudents();
            })
            .catch(error => {
                console.error('Error:', error);
            });
        } else {
            removeTeacherForm.classList.add('was-validated');
        }
    });
})();

// Delete student with id from table row
var student_id = "";
function deleteStudentWithId(id) {
    student_id = id;
    $('#delete-confirmation-modal').modal('show');
}

function deleteTeacherWithIdSeted() {
    var myToast = new bootstrap.Toast(document.getElementById('liveToast'));
    var liveToast = document.getElementById("liveToast");

    fetch('../assets/removeStudent.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'studentid=' + encodeURIComponent(student_id),
    })
    .then(response => response.text())
    .then(data => {
        if (data.indexOf("success") != -1) {
            liveToast.style.backgroundColor = "#BBF7D0";
            liveToast.style.color = 'green';
            document.getElementById('toast-alert-message').innerHTML = "Student removed successfully";
        } else {
            liveToast.style.backgroundColor = "#FECDD3";
            liveToast.style.color = 'red';
            document.getElementById('toast-alert-message').innerHTML = data;
        }
        $('#delete-confirmation-modal').modal('hide');
        showStudents();
        myToast.show();
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

// Show students
function findAndshowStudents(){
    beginIndex = 0;
    counter = 1;
    showStudents();
}

function showStudents() {
    document.getElementById("next-page-btn").classList.add('disabled');
    document.getElementById("prev-page-btn").classList.add('disabled');

    var tablebody = document.getElementById("teacher-table-body");
    var name = document.getElementById("search-teacher-name").value;
    var _class = document.getElementById("search-class").value;
    var _section = document.getElementById("search-section").value;

    var requestData = {
        name: name,
        as: _class,
        a: _section
    };
    fetch('../assets/fetchStudents.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(requestData),
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById("next-page-btn").classList.remove('disabled');
        document.getElementById("prev-page-btn").classList.remove('disabled');

        if ((data[0] + "") === "No_Record") {
            tablebody.innerHTML = "";
            document.getElementById("dataNotAvailable").style.display = 'block';
            document.getElementById("next-page-btn").classList.add('disabled');
            document.getElementById("prev-page-btn").classList.add('disabled');
            document.getElementById("page-number").innerHTML = counter + "";
        } else {
            document.getElementById("dataNotAvailable").style.display = 'none';
            document.getElementById("prev-page-btn").classList.remove('disabled');
            document.getElementById("next-page-btn").classList.remove('disabled');
            document.getElementById("page-number").innerHTML = counter + "";

            if ((beginIndex + limit) >= data.length) {
                document.getElementById("next-page-btn").classList.add('disabled');
                document.getElementById("prev-page-btn").classList.remove('disabled');
            } else if (beginIndex <= 0) {
                document.getElementById("prev-page-btn").classList.add('disabled');
                document.getElementById("next-page-btn").classList.remove('disabled');
            }

            if (beginIndex == 0) {
                document.getElementById("prev-page-btn").classList.add('disabled');
            }
            var students = "";
            var flag = 0;
            for (var i = beginIndex; i < data.length; i++) {
                if (flag >= limit) break;
                students += data[i];
                flag += 1;
            }
            tablebody.innerHTML = students;
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

document.getElementById("search-teacher-name").addEventListener("keyup", searchFunction);
document.getElementById("search-teacher-name").addEventListener("search", searchFunction);

function searchFunction() {
    beginIndex = 0;
    counter = 1;
    showStudents();
}

// Edit student
function editStudent(tid) {
    editing = true;
    editingTeacherId = tid;
    cleanForm();
    editing = true;
    editingTeacherId = tid;

    document.getElementById("uploadImageField").style.display = "none";

    fetch('../assets/fetchStudentInfo.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + encodeURIComponent(tid),
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById("fname").value = data['fname'] || '';
        document.getElementById("lname").value = data['lname'] || '';
        document.getElementById("other_name").value = data['other_name'] || '';
        document.getElementById("gender").value = data['gender'] || '';
        document.getElementById("dob").value = data['dob'] || '';
        document.getElementById("course").value = data['course'] || '';
        document.getElementById("current_year").value = data['current_year'] || '';
        document.getElementById("level").value = data['level'] || '';
        document.getElementById("email").value = data['email'] || '';
        document.getElementById("phone").value = data['phone'] || '';
        document.getElementById("address").value = data['address'] || '';
        document.getElementById("guardian").value = data['guardian'] || '';
        document.getElementById("gphone").value = data['gphone'] || '';

        $('#addTeacherModal').modal('show');
    })
    .catch(error => console.error('Error:', error));
}

// Pagination
document.getElementById("prev-page-btn").addEventListener('click', function () {
    beginIndex -= limit;
    showStudents();
    counter -= 1;
});
document.getElementById("next-page-btn").addEventListener('click', function () {
    beginIndex += limit;
    showStudents();
    counter += 1;
});

function AddStudentBtnClick() {
    editing = false;
    cleanForm();
    document.getElementById("uploadImageField").style.display = "block";
}

// Feedback tab
document.getElementById("feedback-search-class").addEventListener('change', function() {
    let classSection = getClassSectionForFeedback();
    getStudents(classSection['class'], classSection['section']);
});
document.getElementById("feedback-search-section").addEventListener('change', function() {
    let classSection = getClassSectionForFeedback();
    getStudents(classSection['class'], classSection['section']);
});
document.getElementById("feedback-students-tab").addEventListener('click', function() {
    let classSection = getClassSectionForFeedback();
    getStudents(classSection['class'], classSection['section']);
});

function getStudents(_class, _section) {
    let classSection = {
        class: _class + "",
        section: _section + ""
    }

    fetch("../assets/getStudentSelection.php", {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(classSection),
    })
    .then(response => response.json())
    .then(data => {
        if (data['status'] === 'success') {
            document.getElementById("feedback-search-student").innerHTML = data['content'];
        } else {
            document.getElementById("feedback-search-student").innerHTML = "<option selected disabled value=''>--select--</option>";
        }
    })
    .catch(error => {
        console.error("Error:", error);
    });
}

function getClassSectionForFeedback() {
    return {
        class: document.getElementById("feedback-search-class").value,
        section: document.getElementById("feedback-search-section").value
    };
}

function findStudentFeedback() {
    let id = document.getElementById("feedback-search-student").value;
    if (id === "") {
        document.getElementById("select-student-first").style.display = "block";
    } else {
        document.getElementById("select-student-first").style.display = "none";
        getStudentsFeedbacks(id);
    }
}

function getStudentsFeedbacks(id) {
    fetch('../assets/getStudentDetailsAndFeedback.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + encodeURIComponent(id),
    })
    .then(response => response.json())
    .then(data => {
        if (data['status'] === 'success') {
            document.querySelector(".student-feedback").style.display = "block";
            document.getElementById("not-selected-feedbacks").style.display = "none";

            document.querySelector(".feedback-student-name").innerHTML = data['name'];
            document.getElementById("feedback-student-id").innerHTML = "<b>ID</b> - " + data['id'];
            document.getElementById("feedback-student-phone").innerHTML = "<b>Phone</b> - " + data['phone'];
            document.getElementById("feedback-student-dob").innerHTML = "<b>DOB</b> - " + data['dob'];

            document.getElementById("feedback-student-pic").src = data['image'];
            document.getElementById("reciver-student-id").value = data['id'];

            let msgbox = document.getElementById("feedback-message-box");
            msgbox.innerHTML = data['feedbacks'];
            msgbox.scrollTop = msgbox.scrollHeight;
        } else {
            document.querySelector(".student-feedback").style.display = "none";
            document.getElementById("not-selected-feedbacks").style.display = "block";
        }
    })
    .catch(error => console.error('Error:', error));
}

document.getElementById('send-feedback-btn').addEventListener("click", function () {
    let msg = document.getElementById('feedback-msg').value + "";
    if (msg.trim() === "") {
        document.getElementById("empty-message-alert").style.display = "block";
    } else {
        let receiver = document.getElementById("reciver-student-id").value;
        sendFeedback(receiver, msg);
    }
});

function sendFeedback(receiver, msg) {
    let messageObject = {
        receiver: receiver + "",
        message: msg + ""
    }

    let myToast = new bootstrap.Toast(document.getElementById('liveToast'));
    let liveToast = document.getElementById("liveToast");

    fetch("../assets/sendFeedback.php", {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(messageObject),
    })
    .then(response => response.json())
    .then(data => {
        if (data['status'] === 'success') {
            document.getElementById('feedback-msg').value = "";
        } else {
            liveToast.style.backgroundColor = "#FECDD3";
            liveToast.style.color = 'red';
            document.getElementById('toast-alert-message').innerHTML = data['msg'];
            myToast.show();
        }
        getStudentsFeedbacks(receiver);
    })
    .catch(error => {
        console.error("Error:", error);
    });
}

function deleteFeedback(feedbackid, receiverID) {
    let myToast = new bootstrap.Toast(document.getElementById('liveToast'));
    let liveToast = document.getElementById("liveToast");

    fetch('../assets/deleteFeedbackWithId.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'feedbackid=' + encodeURIComponent(feedbackid),
    })
    .then(response => response.json())
    .then(data => {
        if (data['status'] === 'success') {
            liveToast.style.backgroundColor = "#BBF7D0";
            liveToast.style.color = 'green';
            document.getElementById('toast-alert-message').innerHTML = data['message'];
        } else {
            liveToast.style.backgroundColor = "#FECDD3";
            liveToast.style.color = 'red';
            document.getElementById('toast-alert-message').innerHTML = data['message'];
        }
        myToast.show();
        getStudentsFeedbacks(receiverID);
    })
    .catch(error => console.error('Error:', error));
}

document.getElementById("feedback-msg").addEventListener('keyup', function(){
    document.getElementById("empty-message-alert").style.display = 'none';
});

document.getElementById("feedback-students-tab").addEventListener("click", function(){
    document.querySelector(".student-feedback").style.display = "none";
    document.getElementById("not-selected-feedbacks").style.display = "block";
});

$(document).ready(function(){
    $("body").scrollTop(0);
});
