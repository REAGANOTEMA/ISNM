<?php
/**
 * Student Controller for ISNM Student Management System
 */

require_once __DIR__ . '/../models/Student.php';
require_once __DIR__ . '/../config/config.php';

class StudentController {
    private $student;
    
    public function __construct() {
        $this->student = new Student();
    }
    
    /**
     * Handle student creation
     */
    public function create() {
        if (!hasPermission('create')) {
            flashMessage('error', 'You do not have permission to create students');
            redirect('students.php');
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('students.php?action=create');
        }
        
        // Validate required fields
        $requiredFields = ['full_name', 'registration_number', 'national_student_id_number', 
                          'index_number', 'mobile_number', 'course', 'year', 'set_name', 'gender'];
        
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                flashMessage('error', "Field '$field' is required");
                redirect('students.php?action=create');
            }
        }
        
        // Sanitize input data
        $data = [
            'full_name' => sanitizeInput($_POST['full_name']),
            'registration_number' => sanitizeInput($_POST['registration_number']),
            'national_student_id_number' => sanitizeInput($_POST['national_student_id_number']),
            'index_number' => sanitizeInput($_POST['index_number']),
            'mobile_number' => sanitizeInput($_POST['mobile_number']),
            'course' => sanitizeInput($_POST['course']),
            'year' => (int)$_POST['year'],
            'set_name' => sanitizeInput($_POST['set_name']),
            'gender' => sanitizeInput($_POST['gender'])
        ];
        
        // Handle photo upload
        if (!empty($_FILES['passport_photo']['name'])) {
            $uploadResult = uploadImage($_FILES['passport_photo'], STUDENT_PHOTO_PATH);
            if ($uploadResult['success']) {
                $data['passport_photo'] = $uploadResult['filename'];
            } else {
                flashMessage('error', 'Photo upload failed: ' . $uploadResult['error']);
                redirect('students.php?action=create');
            }
        }
        
        // Create student
        $result = $this->student->create($data);
        
        if ($result['success']) {
            flashMessage('success', 'Student created successfully');
            redirect('students.php');
        } else {
            flashMessage('error', $result['error']);
            redirect('students.php?action=create');
        }
    }
    
    /**
     * Handle student update
     */
    public function update() {
        if (!hasPermission('update')) {
            flashMessage('error', 'You do not have permission to update students');
            redirect('students.php');
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id'])) {
            redirect('students.php');
        }
        
        $id = (int)$_POST['id'];
        
        // Validate required fields
        $requiredFields = ['full_name', 'national_student_id_number', 
                          'index_number', 'mobile_number', 'course', 'year', 'set_name', 'gender'];
        
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                flashMessage('error', "Field '$field' is required");
                redirect("students.php?action=edit&id=$id");
            }
        }
        
        // Sanitize input data
        $data = [
            'full_name' => sanitizeInput($_POST['full_name']),
            'national_student_id_number' => sanitizeInput($_POST['national_student_id_number']),
            'index_number' => sanitizeInput($_POST['index_number']),
            'mobile_number' => sanitizeInput($_POST['mobile_number']),
            'course' => sanitizeInput($_POST['course']),
            'year' => (int)$_POST['year'],
            'set_name' => sanitizeInput($_POST['set_name']),
            'gender' => sanitizeInput($_POST['gender'])
        ];
        
        // Handle photo upload if new photo provided
        if (!empty($_FILES['passport_photo']['name'])) {
            $uploadResult = uploadImage($_FILES['passport_photo'], STUDENT_PHOTO_PATH);
            if ($uploadResult['success']) {
                $data['passport_photo'] = $uploadResult['filename'];
                
                // Delete old photo if exists
                $oldStudent = $this->student->getById($id);
                if ($oldStudent['success'] && !empty($oldStudent['student']['passport_photo'])) {
                    $oldPhotoPath = STUDENT_PHOTO_PATH . $oldStudent['student']['passport_photo'];
                    if (file_exists($oldPhotoPath)) {
                        unlink($oldPhotoPath);
                    }
                }
            } else {
                flashMessage('error', 'Photo upload failed: ' . $uploadResult['error']);
                redirect("students.php?action=edit&id=$id");
            }
        }
        
        // Update student
        $result = $this->student->update($id, $data);
        
        if ($result['success']) {
            flashMessage('success', 'Student updated successfully');
            redirect('students.php');
        } else {
            flashMessage('error', $result['error']);
            redirect("students.php?action=edit&id=$id");
        }
    }
    
    /**
     * Handle student deletion
     */
    public function delete() {
        if (!hasPermission('delete')) {
            flashMessage('error', 'You do not have permission to delete students');
            redirect('students.php');
        }
        
        if (empty($_GET['id'])) {
            redirect('students.php');
        }
        
        $id = (int)$_GET['id'];
        
        // Delete student
        $result = $this->student->softDelete($id);
        
        if ($result['success']) {
            flashMessage('success', 'Student deleted successfully');
        } else {
            flashMessage('error', $result['error']);
        }
        
        redirect('students.php');
    }
    
    /**
     * Display student list
     */
    public function index() {
        if (!hasPermission('read')) {
            flashMessage('error', 'You do not have permission to view students');
            redirect('dashboard.php');
        }
        
        $page = (int)($_GET['page'] ?? 1);
        $search = sanitizeInput($_GET['search'] ?? '');
        $course = sanitizeInput($_GET['course'] ?? '');
        $year = sanitizeInput($_GET['year'] ?? '');
        $setName = sanitizeInput($_GET['set'] ?? '');
        
        // Get students
        $result = $this->student->getAll($page, $search, $course, $year, $setName);
        
        if (!$result['success']) {
            flashMessage('error', $result['error']);
            $students = [];
            $pagination = ['current_page' => 1, 'total_pages' => 1, 'total_records' => 0];
        } else {
            $students = $result['students'];
            $pagination = $result['pagination'];
        }
        
        // Get filter options
        $coursesResult = $this->student->getCourses();
        $courses = $coursesResult['success'] ? $coursesResult['courses'] : [];
        
        $yearsResult = $this->student->getYears();
        $years = $yearsResult['success'] ? $yearsResult['years'] : [];
        
        $setsResult = $this->student->getSets();
        $sets = $setsResult['success'] ? $setsResult['sets'] : [];
        
        // Get statistics
        $statsResult = $this->student->getStatistics();
        $statistics = $statsResult['success'] ? $statsResult['statistics'] : [];
        
        return [
            'students' => $students,
            'pagination' => $pagination,
            'courses' => $courses,
            'years' => $years,
            'sets' => $sets,
            'statistics' => $statistics,
            'filters' => [
                'search' => $search,
                'course' => $course,
                'year' => $year,
                'set' => $setName
            ]
        ];
    }
    
    /**
     * Display student edit form
     */
    public function edit($id) {
        if (!hasPermission('update')) {
            flashMessage('error', 'You do not have permission to update students');
            redirect('students.php');
        }
        
        $result = $this->student->getById($id);
        
        if (!$result['success'] || empty($result['student'])) {
            flashMessage('error', 'Student not found');
            redirect('students.php');
        }
        
        return $result['student'];
    }
}
?>
