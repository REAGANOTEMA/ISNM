#!/usr/bin/env python3
"""insert 16 modal cases into JS switch and PHP POST handlers + tables"""

path = r'C:\xampp\htdocs\ISNM\dashboards\lecturers.php'

with open(path, 'r', encoding='utf-8') as f:
    orig = f.read()

# === PHP section: insert after the POST block, before "Set statistics" ===
idx_set = orig.find('// Set statistics')
# Find the last brace before Set statistics
idx_p = orig.rfind('}', 0, idx_set)
# That brace closes the outer POST block, the one before it closes the add_announcement
# We insert new PHP handlers and table creation between the two braces
idx_p2 = orig.rfind('}', 0, idx_p)

# The region from idx_p2 to idx_set is: }\n}\n\n
# We'll replace this entirely

php_handlers = '''    // courseMaterials - store as teaching_resource
    if ($action === 'add_course_material' && $conn) {
        $stmt = $conn->prepare("INSERT INTO teaching_resources (lecturer_id, title, resource_type, description) VALUES (?,?,?,?)");
        $stmt->bind_param("isss", $user_id, $_POST['title'], $_POST['file_type'], $_POST['description']);
        $ok = $stmt->execute(); if (!$ok) { error_log('material: ' . ($stmt->error ?? 'u')); } echo json_encode(['success' => $ok, 'message' => $stmt->error ?: 'Material added']); $stmt->close(); exit;
    }
    // syllabus - store in course_syllabi
    if ($action === 'add_syllabus' && $conn) {
        $stmt = $conn->prepare("INSERT INTO course_syllabi (lecturer_id, course_id, course_name, semester, topics, learning_outcomes) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param("isssss", $user_id, $_POST['course_id'], $_POST['course_name'], $_POST['semester'], $_POST['topics'], $_POST['learning_outcomes']);
        $ok = $stmt->execute(); if (!$ok) { error_log('syllabus: ' . ($stmt->error ?? 'u')); } echo json_encode(['success' => $ok, 'message' => $stmt->error ?: 'Syllabus added']); $stmt->close(); exit;
    }
    // lessonPlan - store in lesson_plans
    if ($action === 'add_lesson_plan' && $conn) {
        $stmt = $conn->prepare("INSERT INTO lesson_plans (lecturer_id, course_id, week_number, topic, objectives, activities) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param("isisss", $user_id, $_POST['course_id'], $_POST['week_number'], $_POST['topic'], $_POST['objectives'], $_POST['activities']);
        $ok = $stmt->execute(); if (!$ok) { error_log('lesson_plan: ' . ($stmt->error ?? 'u')); } echo json_encode(['success' => $ok, 'message' => $stmt->error ?: 'Lesson plan added']); $stmt->close(); exit;
    }
    // courseEvaluation - store in course_evaluations
    if ($action === 'add_evaluation' && $conn) {
        $stmt = $conn->prepare("INSERT INTO course_evaluations (lecturer_id, course_id, course_name, semester, questions, feedback) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param("isssss", $user_id, $_POST['course_id'], $_POST['course_c_name'], $_POST['semester'], $_POST['questions'], $_POST['feedback']);
        $ok = $stmt->execute(); if (!$ok) { error_log('evaluation: ' . ($stmt->error ?? 'u')); } echo json_encode(['success' => $ok, 'message' => $stmt->error ?: 'Evaluation added']); $stmt->close(); exit;
    }
    // addLecture - store in lecture_schedule
    if ($action === 'add_lecture' && $conn) {
        $stmt = $conn->prepare("INSERT INTO lecture_schedule (lecturer_id, course_id, topic, lecture_date, start_time, end_time, venue, status) VALUES (?,?,?,?,?,?,?,'scheduled')");
        $stmt->bind_param("issssss", $user_id, $_POST['course_id'], $_POST['topic'], $_POST['lecture_date'], $_POST['start_time'], $_POST['end_time'], $_POST['venue']);
        $ok = $stmt->execute(); if (!$ok) { error_log('lecture: ' . ($stmt->error ?? 'u')); } echo json_encode(['success' => $ok, 'message' => $stmt->error ?: 'Lecture added']); $stmt->close(); exit;
    }
    // rescheduleLecture - update lecture_schedule
    if ($action === 'reschedule_lecture' && $conn) {
        $stmt = $conn->prepare("UPDATE lecture_schedule SET lecture_date=?, start_time=?, end_time=?, reason=? WHERE id=? AND lecturer_id=?");
        $stmt->bind_param("ssssii", $_POST['new_date'], $_POST['start_time'], $_POST['end_time'], $_POST['reason'], $_POST['lecture_id'], $user_id);
        $ok = $stmt->execute(); if (!$ok) { error_log('reschedule: ' . ($stmt->error ?? 'u')); } echo json_encode(['success' => $ok, 'message' => $stmt->error ?: 'Lecture rescheduled']); $stmt->close(); exit;
    }
    // cancelLecture - update lecture_schedule
    if ($action === 'cancel_lecture' && $conn) {
        $stmt = $conn->prepare("UPDATE lecture_schedule SET status='cancelled', reason=? WHERE id=? AND lecturer_id=?");
        $stmt->bind_param("sii", $_POST['reason'], $_POST['lecture_id'], $user_id);
        $ok = $stmt->execute(); if (!$ok) { error_log('cancel: ' . ($stmt->error ?? 'u')); } echo json_encode(['success' => $ok, 'message' => $stmt->error ?: 'Lecture cancelled']); $stmt->close(); exit;
    }
    // attendance - store in student_attendance
    if ($action === 'add_attendance' && $conn) {
        $stmt = $conn->prepare("INSERT INTO student_attendance (course_id, student_id, date, status, notes) VALUES (?,?,?,?,?)");
        $stmt->bind_param("ssiss", $_POST['course_name'], $_POST['student_id'], $_POST['date'], $_POST['status'], $_POST['notes']);
        $ok = $stmt->execute(); if (!$ok) { error_log('attendance: ' . ($stmt->error ?? 'u')); } echo json_encode(['success' => $ok, 'error' => $stmt->error ?? null]); $stmt->close(); exit;
    }
    // studentCounseling - store in lecturer_counseling
    if ($action === 'add_counseling' && $conn) {
        $stmt = $conn->prepare("INSERT INTO lecturer_counseling (lecturer_id, student_id, concern, action_taken, follow_up) VALUES (?,?,?,?,?)");
        $stmt->bind_param("iisss", $user_id, $_POST['student_id'], $_POST['concern'], $_POST['action_taken'], $_POST['follow_up']);
        $ok = $stmt->execute(); if (!$ok) { error_log('counseling: ' . ($stmt->error ?? 'u')); } echo json_encode(['success' => $ok, 'message' => $stmt->error ?: 'Counseling record added']); $stmt->close(); exit;
    }
    // gradeSubmission - store in academic_records
    if ($action === 'submit_grade' && $conn) {
        $stmt = $conn->prepare("INSERT INTO academic_records (lecturer_id, student_id, course_id, assessment_type, marks, grade) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param("iissss", $user_id, $_POST['student_id'], $_POST['course_name'], $_POST['assessment_type'], $_POST['score'], $_POST['grade']);
        $ok = $stmt->execute(); if (!$ok) { error_log('grade: ' . ($stmt->error ?? 'u')); } echo json_encode(['success' => $ok, 'message' => $stmt->error ?: 'Grade submitted']); $stmt->close(); exit;
    }
'''

table_creation = f'''{php_handlers}}}\n
// Ensure needed tables exist
$create_table_sql = [
    "CREATE TABLE IF NOT EXISTS course_syllabi (
        id INT AUTO_INCREMENT PRIMARY KEY,
        lecturer_id INT NOT NULL,
        course_id VARCHAR(50) DEFAULT NULL,
        course_name VARCHAR(255) DEFAULT NULL,
        semester VARCHAR(100) DEFAULT NULL,
        topics TEXT DEFAULT NULL,
        learning_outcomes TEXT CLEAR DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS course_evaluations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        lecturer_id INT NOT NULL,
        course_id VARCHAR(50) course_id DEFAULT NULL,
        course_name VARCHAR(255) DEFAULT NULL,
        semester VARCHAR(100) DEFAULT NULL,
        questions TEXT DEFAULT NULL,
        feedback TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS lecture_schedule (
        id INT AUTO_INCREMENT PRIMARY KEY,
        lecturer_id INT NOT NULL,
        course_id VARCHAR(50) DEFAULT NULL,
        topic VARCHAR(255) DEFAULT NULL,
        lecture_date DATE DEFAULT NULL,
        start_time TIME DEFAULT NULL,
        end_time TIME DEFAULT TIME,
        venue VARCHAR(255) DEFAULT NULL,
        status VARCHAR(50) DEFAULT ''scheduled'',
        reason TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS lecturer_counseling (
        id INT s AUTO_INCREMENT PRIMARY KEY,
        lecturer_id INT NOT NULL,
        student_id INT DEFAULT NULL,
        concern TEXT DEFAULT NULL,
        action_outcome TEXT DEFAULT NULL,
        follow_up TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )"
];
foreach ($create_table_sql as $sql) {{
    if ($conn) {{
        try {{
            $conn->query($sql);
        }} catch (Exception $e) {{
            error_log('lecturers table create: ' . $e->getMessage());
        }}
    }}
}}\n
'''

# old segment: from the second-to-last }} to the Set line
old_php = orig[idx_p2:idx_set]
new_php = '    }' + table_creation + '// Set statistics from database'

content = orig.replace(old_php, new_php, 1)

# === JS section: insert new switch cases after the addAnnouncement break ===
curl_idx = content.find("case 'addAnnouncement':")
break_idx = content.find('break;', curl_idx)
# Right after the break, before the switch close }
close_idx = content.find('\n            }', break_idx)

js_cases = '''
                case 'courseMaterials':
                    modalTitle.textContent = 'Course Materials';
                    modalBody.innerHTML = '<form id=\"materialForm\"><input type=\"hidden\" name=\"action\" value=\"add_course_material\"><div class=\"mb-3\"><label class=\"form-label\">Title</label><input name=\"title\" type=\"text\" class=\"form-control\"></div><div class=\"mb-3\"><label class=\"form-label\">File Type</label><select name=\"file_type\" class=\"form-control\"><option value=\"\">Select</option><option value=\"PDF\">PDF</option><option value=\"Video\">Video</option></select></div><div class=\"mb-3\"><label class=\"form-label\">Description</label><textarea name=\"description\" class=\"form-control\" rows=\"3\"></textarea></div></form>';
                    break;

                case 'syllabus':
                    modalTitle.textContent = 'Course Syllabus';
                    modalBody.innerHTML = '<form id=\"syllabusForm\"><input type=\"hidden\" name=\"action\" value=\"add_syllabus\"><div class=\"mb-3\"><label class=\"form-label\">Course ID</label><input name=\"course_id\" class=\"form-control\"></div><div class=\"mb-3\"><label class=\"form-label\">Course Name</label><input name=\"course_name\" class=\"form-control\"></div><div style=\"mb-3\"><label class=\"form-label\">Semester</label><input name=\"semester\" class=\"form-control\"></div><div class=\"mb-3\"><label class=\"form-label\">Topics</label><textarea name=\"topics\" class=\"form-control\" rows=\"3\"></textarea></div><div class=\"mb-3\"><label class=\"form-label\">Learning Outcomes</label><textarea name=\"learning_outcomes\" class=\"form-control\" rows=\"3\"></textarea></div></form>';
                    break;

                case 'lessonPlan':
                    modalTitle.textContext = 'Letson Plan';
                    modalBody.innerHTML = '<form id=\"lessonPlanForm\"><input type=\"hidden\" name=\"action\" value=\"add_lesson_plan\"><div class=\"md-3\"><label class=\"form-label\">Course ID</label><input name=\"course_id\" class=\"form-control\"></div><div class=\"mb-3\"><label class=\"form-label\">Week Number</label><input name=\"week_number\" type=\"number\" class=\"form-control\"></div><div class=\"mb-3\"><label class=\"form-label\">Topic</label><input name=\"topic\" class=\"form-control\"></div><div class=\"mb-3\"><label class=\"form-label\">objectives</label><textarea name=\"objectives\" class=\"form-control\" rows=\"3\"></textarea></div><div class=\"mb-3\"><label class=\"form-label\">Activities</label><textarea name=\"activities\" class=\"form-control\" rows=\"3\"></textarea></div></form>';
                    break;

                case 'courseEvaluation':
                    modalTitle.textContent = 'Course Evalution';
                    modalBody.innerHTML = '<form id=\"evalForm\"><input type=\"hidden\" action=\"add_evaluation\"><div class=\"mb-3\"><label class=\"form-label\">Course ID</label><input name=\"course_id\" class=\"form-control\"></div><div class=\"mb-3\"><label class=\"form-label\">Course Name</label><input name=\"course_name\" class=\"form-control\"></div><div class=\"mb-3\"><label class=\"form-label\">Semester</label><input name=\"semester\" class=\"form-control\"></div><div class=\"mb-3\"><label class=\"form-label\">Questions</label><textarea name=\"questions\" class=\"form-control\" rows=\"3\"></textarea></div><div class=\"mb-3\"><label class=\"form-label\">Feedback</label><textarea name=\"feedback\" class=\"form-control\" rows=\"3\"></textarea></div></form>';
                    break;

                case 'addLecture':
                    modalTitle.textContent = 'Add Lecture';
                    modalBody.innerHTML = '<form id=\"lectureForm\"><input type=\"hidden\" name=\"action\" value=\"add_lecture\"><div class=\"mb-3\"><label class=\"form-label\">Course ID</label><input name=\"course_id\" class=\"form-control\"></div><div class=\"mb-3\"><label class=\"form-label\">Topic</label><input name=\"topic\" class=\"form-control\"></div><div class=\"mb-3\"><label class=\"form-label\">Date</label><input name=\"lecture_date\" type=\"date\" class=\"form-control\"></div><div class=\"mb-3\"><label class=\"form-label\">Start Time</label><input name=\"start_time\" type=\"time\" class=\"form-control\"></div><div class=\"mb-3\"><label class=\"form-label\">End Time</label><input name=\"end_time\" type=\"time\" class=\"form-control\"></div><div class=\"mb-3\"><label class=\"form-label\">Venue</label><input name=\"venue\" class=\"form-control\"></div></form>';
                    break;

                case 'weeklySchedule':
                    modalTitle.textContent = 'Weekly Schedule';
                    modalBody.innerHTML = '<div id=\"scheduleRes\">Loading schedule...</div>';
                    modalActionBtn.style.display = 'none';
                    fetch('endpoints/lecturer_schedule.php').then(function(r) { return r.json(); }).then(function(data) {
                        var h = '<table class=\"table\"><thead><tr><th>Course</th><th>Day</th><th>Time</th></tr></thead><tbody>';
                        data.forEach(function(s) { h += '<tr><td>' + s.course_name + '</td><td>' + s.day_of_week + '</td><td>' + s.start_time + '-' + s.end_time + '</td></tr>'; });
                        h += '</tbody></table>';
                        if (!data.length) { h = '<p class=\"text-muted\">No schedule.</p>'; }
                        document.getElementById('scheduleRes').innerHTML = h;
                    });
                    break;

                case 'rescheduleLecture':
                    modalTitle.textContent = 'Reschedule Lecture';
                    modalBody.innerHTML = '<form id=\"rescheduleForm\"><input type=\"hidden\" name=\"action\" value=\"reschedule_lecture\"><div class=\"mb-3\"><label class=\"form-label\">Lecture ID</label><input name=\"lecture_id\" type=\"number\" class=\"form-control\" required></div><div class=\"mb-3\"><label class=\"form-label\">New Date</label><input name=\"new_date\" type=\"date\" class=\"form-control\" required></div><div class=\"mb-3\"><label class=\"form-label\">Start Time</label><input name=\"new_time\" type=\"time\" class=\"form-control\" required></div><div class=\"mb-3\"><label class=\"form-label\">End Time</label><input name=\"end_time\" type=\"time\" class=\"form-control\"></div><div class=\"mb-3\"><label class=\"form-label\">Reason</label><textarea name=\"reason\" class=\"form-control\" rows=\"3\"></textarea></div></form>';
                    break;

                case 'cancelLecture':
                    modalBody = 'Cancel Lecture';
                    modalBody.innerHTML = '<form id=\"cancelLectureForm\"><input type=\"hidden\" name=\"action\" value=\"cancel_lecture\"><div class=\"mb-3\"><label class=\"form-label\">Lecture ID</label><input name=\"lecture_id\" type=\"number\" class=\"form-control\" required></div><div class=\"mb-3\"><label class=\"form-label\">Reason</label><textarea name=\"reason\" class=\"form-control\" rows=\"3\"></textarea></div></form>';
                    break;

                case 'studentList':
                    modalTitle.textContent = 'Enrolled Students';
                    modalBody.innerHTML = '<div id=\"studlistRes\">Loading...</div>';
                    modalActionBtn.style.display = 'none';
                    fetch('endpoints/enrolled_students.php').then(function(r) { return r.json(); }).then(function(d) {
                        var h = '<table class=\"table\"><thead><tr><th>ID</th><th>Name</th><th>Course</th></tr></thead><tbody>';
                        d.forEach(function(s) { h += '<tr><td>' + (s.student_number || s.id) + '</td><td>' + s.full_name + '</td><td>' + s.course_name + '</td></tr>'; });
                        h += '</tbody></table>';
                        if (!d.length) { h = '<p class=\"text-muted\">No students.</p>'; }
                        document.getElementById('studlistRes').innerHTML = h;
                    });
                    break;

                case 'attendance':
                    modalTitle.textContent = 'Attendance';
                    modalBody.innerHTML = '<form id=\"attendanceForm\"><input type=\"hidden\" name=\"action\" value=\"add_attendance\"><div class=\"mb-3\"><label class=\"form-label\">Course</label><input name=\"course_name\" class=\"form-control\"></div><div class=\"mb-3\"><label class=\"form-label\">Student ID</label><input name=\"student_id\" type=\"number\" class=\"form-control\"></div><div class=\"mb-3\"><label class=\"form-label\">Date</label><input name=\"date\" type=\"date\" class=\"form-control\"></div><div class=\"mb-3\"><label class=\"form-label\">Status</label><select name=\"status\" class=\"form-control\"><option value=\"Present\">Present</option><option value=\"Absent\">Absent</option></select></div><div class=\"mb-3\"><label class=\"form-label\">Notes</label><textarea name=\"notes\" class=\"form-control\" rows=\"3\"></textarea></div></form>';
                    break;

                case 'studentProgress':
                    modalTitle = 'Student Progress';
                    modalBody = '<div id=\"progressRes\">Loading progress...</div>';
                    modalActionBtn.style.display = 'none';
                    fetch('endpoints/student_progress.php').then(function(r) { return r.json(); }).then(function(d) {
                        var h = '<table class=\"table\"><thead><tr><th>Student</th><th>Marks</th><th>Grade</th></tr></thead><tbody>';
                        d.forEach(function(p) { h += '<tr><td>' + p.student_name + '</td><td>' + (p.marks || p.score) + '</td><td>' + p.grade + '</td></tr>'; });
                        h += '</tbody></table>';
                        document.getElementById('progressRes').innerHTML = h;
                    });
                    break;

                case 'studentCounseling':
                    modalTitle = 'Student Counseling';
                    modalBody = '<form id=\"counselForm\"><input type=\"hidden\" name=\"action\" value=\"add_counseling\"><div class=\"mb-3\"><label class=\"form-label\">Student ID</label><input name=\"student_id\" type=\"number\" class=\"form-control\"></div><div class=\"mb-3\"><label class=\"form-label\">Concern</label><textarea name=\"concern\" class=\"form-control\" rows=\"3\"></textarea></div><div class=\"mb-3\"><label class=\"form-label\">Action Taken</label><textarea name=\"action_taken\" class=\"form-control\" rows=\"3\"></textarea></div><div class=\"mb-3\"><label class=\"form-label\">Follow Up</label><textarea name=\"follow_up\" class=\"form-control\" rows=\"3\"></textarea></div></form>';
                    break;

                case 'gradebook':
                    modalTitle = 'Gradebook';
                    modalBody = '<div id=\"gradeRes\">Loading...</div>';
                    modalActionBtn.style.display = 'none';
                    fetch('endpoints/gradebook.php').then(function(r) { return r.json(); }).then(function(d) {
                        var h = '<table class=\"table\"><thead><tr><th>Student</th><th>Assessment</th><th>Marks</th><th>Grade</th></tr></thead><tbody>';
                        d.forEach(function(g) { h += '<tr><td>' + g.student_name + '</td><td>' + g.assessment_type + '</td><td>' + g.marks + '</td><td>' + g.grade + '</td></tr>'; });
                        h += '</tbody></table>';
                        document.getElementById('gradeRes').innerHTML = h;
                    });
                    break;

                case 'gradeSubmission':
                    modalTitle = 'Submit Grade';
                    modalBody = '<form id=\"gradeForm\"><input type=\"hidden\" name=\"action\" value=\"submit_grade\"><div class=\"mb-3\"><label class=\"form-label\">Course</label><input name=\"course_name\" class=\"form-control\"></div><div class=\"mb-3\"><label class=\"form-label\">Student ID</label><input name=\"student_id\" type=\"number\" class=\"form-control\"></div><div class=\"mb-3\"><label class=\"form-label\">Assessment Type</label><select name=\"assessment_type\" class=\"form-control\"><option value=\"CAT\">CAT</option><option value=\"Exam\">Exam</option></select></div><div class=\"mb-3\"><label class=\"form-label\">Score</label><input name=\"text\" step=\"0.01\" class=\"form-control\"></div><div class=\"mb-3\"><label class=\"form-label\">Grade</label><select name=\"grade\" class=\"form-control\"><option value=\"\">Select</option><option>A</option><option>B</option><option>C</option><option>F</option></select></div></form>';
                    break;

                case 'gradeAnalysis':
                    modalTitle = 'Grade Analysis';
                    modalBody = '<div id=\"analysisRes\">Loading grade distribution...</div>';
                    modalActionBtn.style.display = 'none';
                    fetch('endpoints/grade_analysis.php').then(function(r) { return r.json(); }).then(function(d) {
                        var h = '<p>Distribution</p><table class=\"table\"><thead><tr><th>Grade</th><th>Count</th></tr></thead><tbody>';
                        for (var k in (d.distibution || d)) { h += '<tr><td>' + k + '</td><td>' + d[k] + '</td></tr>'; }
                        h += '</tbody></table>';
                        document.getElementById('analysisRes').innerHTML = h;
                    });
                    break;

                case 'gradeAppeals':
                    modalTitle = 'Grade Appeals';
                    modalBody = '<div id=\"appealRes\">Loading appeals...</div>';
                    modalActionBtn.style.display = 'none';
                    fetch('endpoints/grade_appeals.php').then(function(r) { return r.json(); }).then(function(d) {
                        var l = d.appeals || d;
                        var h = '<table class=\"table\"><thead><tr><th>Student</th><th>Reason</th><th>Status</th></tr></thead><tbody>';
                        l.forEach(function(v) { h += '<tr><td>' + v.student_name + '</td><td>' + (v.reason || v.notes).substring(0, 50) + '</td><td>' + (v.status||'Pending') + '</td></tr>'; });
                        h += '</tbody></table>';
                        document.getElementById('appealRes').innerHTML = h;
                    });
                    break;
'''

# Insert the new JS cases before the switch close
content = content[:close_idx] + js_cases + content[close_idx:]

with open(path, 'w', encoding='utf-8') as f:
    f.write(content)

print(f"Done. Original: {len(orig)}, New: {len(content)}")
print(f"look at first 200 chars: {content[:200]}")
