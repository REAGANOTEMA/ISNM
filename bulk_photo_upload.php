<?php
session_start();
include_once 'includes/config.php';
include_once 'includes/functions.php';
include_once 'includes/photo_upload.php';

// Check if user is logged in and has appropriate access level
if (!isset($_SESSION['user_id']) || $_SESSION['access_level'] < 8) {
    header("Location: login.php");
    exit();
}

// Handle bulk photo upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_upload'])) {
    $uploaded_count = 0;
    $error_count = 0;
    $results = [];
    
    if (isset($_FILES['photos']) && !empty($_FILES['photos']['name'][0])) {
        $photo_count = count($_FILES['photos']['name']);
        
        for ($i = 0; $i < $photo_count; $i++) {
            $student_id = $_POST['student_ids'][$i] ?? '';
            
            if (empty($student_id)) {
                $results[] = ['success' => false, 'message' => 'Student ID missing for photo ' . ($i + 1)];
                $error_count++;
                continue;
            }
            
            $photo_file = [
                'name' => $_FILES['photos']['name'][$i],
                'type' => $_FILES['photos']['type'][$i],
                'tmp_name' => $_FILES['photos']['tmp_name'][$i],
                'error' => $_FILES['photos']['error'][$i],
                'size' => $_FILES['photos']['size'][$i]
            ];
            
            if ($photo_file['error'] === UPLOAD_ERR_OK) {
                $upload_result = uploadPassportPhoto($photo_file, $student_id);
                
                if ($upload_result['success']) {
                    if (updateStudentPhoto($student_id, $upload_result['filename'])) {
                        $results[] = ['success' => true, 'message' => "Photo uploaded for $student_id", 'student_id' => $student_id];
                        $uploaded_count++;
                    } else {
                        $results[] = ['success' => false, 'message' => "Database update failed for $student_id", 'student_id' => $student_id];
                        $error_count++;
                    }
                } else {
                    $results[] = ['success' => false, 'message' => $upload_result['message'], 'student_id' => $student_id];
                    $error_count++;
                }
            } else {
                $results[] = ['success' => false, 'message' => 'Upload error for photo ' . ($i + 1), 'student_id' => $student_id];
                $error_count++;
            }
        }
        
        $_SESSION['bulk_upload_results'] = [
            'uploaded' => $uploaded_count,
            'errors' => $error_count,
            'results' => $results
        ];
        
        logActivity($_SESSION['user_id'], $_SESSION['role'], 'Bulk Photo Upload', "Uploaded $uploaded_count photos with $error_count errors", 'students', null);
        
        header("Location: bulk_photo_upload.php");
        exit();
    } else {
        $_SESSION['error'] = "Please select photos to upload";
    }
}

// Get all students for dropdown
$students_sql = "SELECT student_id, first_name, surname, profile_image FROM students ORDER BY surname, first_name";
$students = executeQuery($students_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Photo Upload - ISNM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1a237e;
            --secondary-color: #3949ab;
            --accent-color: #ffd700;
            --success-color: #28a745;
            --danger-color: #dc3545;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        .navbar {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .main-container {
            padding: 2rem;
        }

        .page-header {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            border-left: 5px solid var(--primary-color);
        }

        .upload-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .photo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .photo-item {
            border: 2px solid #ddd;
            border-radius: 10px;
            padding: 1rem;
            text-align: center;
            transition: border-color 0.3s ease;
        }

        .photo-item:hover {
            border-color: var(--primary-color);
        }

        .photo-item img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 0.5rem;
            border: 2px solid #ddd;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 8px;
            padding: 0.75rem 2rem;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(26, 35, 126, 0.3);
        }

        .result-item {
            padding: 0.5rem;
            margin: 0.25rem 0;
            border-radius: 5px;
            font-size: 0.875rem;
        }

        .result-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .result-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }

        .file-input-wrapper input[type=file] {
            position: absolute;
            left: -9999px;
        }

        .file-input-label {
            display: block;
            padding: 1rem;
            background: #f8f9fa;
            border: 2px dashed #ddd;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .file-input-label:hover {
            border-color: var(--primary-color);
            background: #e3f2fd;
        }

        .student-select {
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-graduation-cap"></i> ISNM Student Management
            </a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="student_accounts_management.php">
                            <i class="fas fa-users"></i> Student Accounts
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="bulk_photo_upload.php">
                            <i class="fas fa-camera"></i> Photo Upload
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="logout.php">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid main-container">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="h3 mb-3">
                <i class="fas fa-camera text-primary"></i> Bulk Passport Photo Upload
            </h1>
            <p class="text-muted mb-0">Upload passport photos for multiple students at once</p>
        </div>

        <!-- Upload Results -->
        <?php if (isset($_SESSION['bulk_upload_results'])): ?>
            <div class="upload-card">
                <h4><i class="fas fa-check-circle text-success"></i> Upload Results</h4>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="text-center">
                            <h3 class="text-success"><?php echo $_SESSION['bulk_upload_results']['uploaded']; ?></h3>
                            <p class="text-muted">Successful Uploads</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center">
                            <h3 class="text-danger"><?php echo $_SESSION['bulk_upload_results']['errors']; ?></h3>
                            <p class="text-muted">Errors</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center">
                            <h3 class="text-primary"><?php echo count($_SESSION['bulk_upload_results']['results']); ?></h3>
                            <p class="text-muted">Total Processed</p>
                        </div>
                    </div>
                </div>
                
                <h5>Detailed Results:</h5>
                <div style="max-height: 300px; overflow-y: auto;">
                    <?php foreach ($_SESSION['bulk_upload_results']['results'] as $result): ?>
                        <div class="result-item <?php echo $result['success'] ? 'result-success' : 'result-error'; ?>">
                            <i class="fas fa-<?php echo $result['success'] ? 'check' : 'times'; ?>"></i>
                            <?php echo htmlspecialchars($result['message']); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php unset($_SESSION['bulk_upload_results']); ?>
        <?php endif; ?>

        <!-- Upload Form -->
        <div class="upload-card">
            <h4><i class="fas fa-upload text-primary"></i> Upload Photos</h4>
            <form method="POST" enctype="multipart/form-data" id="bulkUploadForm">
                <input type="hidden" name="bulk_upload" value="1">
                
                <div class="mb-3">
                    <label class="form-label">Select Photos *</label>
                    <div class="file-input-wrapper">
                        <input type="file" name="photos[]" id="photos" multiple accept="image/*" required>
                        <label for="photos" class="file-input-label">
                            <i class="fas fa-cloud-upload-alt fa-2x mb-2"></i>
                            <h5>Click to select photos or drag and drop</h5>
                            <p class="text-muted">Select multiple passport photos (JPG, PNG, GIF)</p>
                        </label>
                    </div>
                </div>

                <div id="photoPreviewContainer"></div>

                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-upload"></i> Upload All Photos
                    </button>
                </div>
            </form>
        </div>

        <!-- Students Without Photos -->
        <div class="upload-card">
            <h4><i class="fas fa-users text-info"></i> Students Without Photos</h4>
            <div class="photo-grid">
                <?php foreach ($students as $student): ?>
                    <?php if ($student['profile_image'] === 'default-student.png' || empty($student['profile_image'])): ?>
                        <div class="photo-item">
                            <img src="images/default-avatar.png" alt="No Photo">
                            <h6><?php echo htmlspecialchars($student['surname'] . ', ' . $student['first_name']); ?></h6>
                            <small class="text-muted"><?php echo htmlspecialchars($student['student_id']); ?></small>
                            <button type="button" class="btn btn-sm btn-primary mt-2" onclick="uploadSinglePhoto('<?php echo $student['student_id']; ?>', '<?php echo htmlspecialchars($student['first_name'] . ' ' . $student['surname']); ?>')">
                                <i class="fas fa-camera"></i> Upload
                            </button>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Students With Photos -->
        <div class="upload-card">
            <h4><i class="fas fa-check-circle text-success"></i> Students With Photos</h4>
            <div class="photo-grid">
                <?php foreach ($students as $student): ?>
                    <?php if ($student['profile_image'] !== 'default-student.png' && !empty($student['profile_image'])): ?>
                        <div class="photo-item">
                            <img src="<?php echo getPassportPhotoUrl($student['profile_image']); ?>" alt="Student Photo">
                            <h6><?php echo htmlspecialchars($student['surname'] . ', ' . $student['first_name']); ?></h6>
                            <small class="text-muted"><?php echo htmlspecialchars($student['student_id']); ?></small>
                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-warning" onclick="uploadSinglePhoto('<?php echo $student['student_id']; ?>', '<?php echo htmlspecialchars($student['first_name'] . ' ' . $student['surname']); ?>')">
                                    <i class="fas fa-sync"></i> Replace
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Hidden Single Photo Upload Modal (redirects to main page) -->
    <script>
        function uploadSinglePhoto(studentId, studentName) {
            // Redirect to main student management page with photo upload modal
            window.location.href = 'student_accounts_management.php?upload_photo=' + studentId + '&name=' + encodeURIComponent(studentName);
        }

        // Photo preview functionality
        document.getElementById('photos').addEventListener('change', function(e) {
            const files = e.target.files;
            const container = document.getElementById('photoPreviewContainer');
            container.innerHTML = '';
            
            if (files.length > 0) {
                const title = document.createElement('h5');
                title.innerHTML = '<i class="fas fa-images"></i> Selected Photos (' + files.length + ')';
                title.className = 'mb-3';
                container.appendChild(title);
                
                const grid = document.createElement('div');
                grid.className = 'photo-grid';
                
                for (let i = 0; i < files.length; i++) {
                    const file = files[i];
                    const item = document.createElement('div');
                    item.className = 'photo-item';
                    
                    const img = document.createElement('img');
                    img.src = URL.createObjectURL(file);
                    
                    const select = document.createElement('select');
                    select.className = 'form-select student-select';
                    select.name = 'student_ids[]';
                    select.required = true;
                    
                    // Add student options
                    <?php foreach ($students as $student): ?>
                    const option<?php echo $student['student_id']; ?> = document.createElement('option');
                    option<?php echo $student['student_id']; ?>.value = '<?php echo $student['student_id']; ?>';
                    option<?php echo $student['student_id']; ?>.textContent = '<?php echo htmlspecialchars($student['student_id'] . ' - ' . $student['surname'] . ', ' . $student['first_name']); ?>';
                    select.appendChild(option<?php echo $student['student_id']; ?>);
                    <?php endforeach; ?>
                    
                    const label = document.createElement('label');
                    label.textContent = 'Match with student:';
                    label.className = 'form-label small';
                    
                    item.appendChild(img);
                    item.appendChild(document.createElement('br'));
                    item.appendChild(label);
                    item.appendChild(select);
                    
                    grid.appendChild(item);
                }
                
                container.appendChild(grid);
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
