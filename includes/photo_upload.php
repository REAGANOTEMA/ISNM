<?php
// Photo upload functionality for student passport photos

function uploadPassportPhoto($file, $student_id) {
    // Validate file
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Error uploading file'];
    }
    
    // Check file type
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $file_type = mime_content_type($file['tmp_name']);
    
    if (!in_array($file_type, $allowed_types)) {
        return ['success' => false, 'message' => 'Only JPG, PNG, and GIF images are allowed'];
    }
    
    // Check file size (max 5MB)
    $max_size = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $max_size) {
        return ['success' => false, 'message' => 'File size must be less than 5MB'];
    }
    
    // Create upload directory if it doesn't exist
    $upload_dir = 'uploads/passport_photos/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Generate unique filename
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $student_id . '_' . time() . '.' . $file_extension;
    $upload_path = $upload_dir . $filename;
    
    // Process image - resize to passport size
    $processed_image = processPassportPhoto($file['tmp_name'], $file_type);
    
    if ($processed_image) {
        // Save processed image
        if (file_put_contents($upload_path, $processed_image)) {
            return [
                'success' => true, 
                'filename' => $filename,
                'path' => $upload_path,
                'message' => 'Photo uploaded successfully'
            ];
        } else {
            return ['success' => false, 'message' => 'Error saving processed image'];
        }
    } else {
        return ['success' => false, 'message' => 'Error processing image'];
    }
}

function processPassportPhoto($image_path, $mime_type) {
    // Target passport photo dimensions
    $target_width = 350;
    $target_height = 450;
    
    // Create image resource based on file type
    switch ($mime_type) {
        case 'image/jpeg':
        case 'image/jpg':
            $source_image = imagecreatefromjpeg($image_path);
            break;
        case 'image/png':
            $source_image = imagecreatefrompng($image_path);
            break;
        case 'image/gif':
            $source_image = imagecreatefromgif($image_path);
            break;
        default:
            return false;
    }
    
    if (!$source_image) {
        return false;
    }
    
    // Get original dimensions
    $original_width = imagesx($source_image);
    $original_height = imagesy($source_image);
    
    // Calculate scaling to fit passport dimensions while maintaining aspect ratio
    $scale_w = $target_width / $original_width;
    $scale_h = $target_height / $original_height;
    $scale = min($scale_w, $scale_h);
    
    $new_width = (int)($original_width * $scale);
    $new_height = (int)($original_height * $scale);
    
    // Create new image with target dimensions
    $new_image = imagecreatetruecolor($target_width, $target_height);
    
    // Fill with white background
    $white = imagecolorallocate($new_image, 255, 255, 255);
    imagefill($new_image, 0, 0, $white);
    
    // Calculate position to center the image
    $x = ($target_width - $new_width) / 2;
    $y = ($target_height - $new_height) / 2;
    
    // Copy and resize the image
    if (imagecopyresampled($new_image, $source_image, $x, $y, 0, 0, $new_width, $new_height, $original_width, $original_height)) {
        // Convert to JPEG for consistency
        ob_start();
        imagejpeg($new_image, null, 85); // 85% quality
        $image_data = ob_get_contents();
        ob_end_clean();
        
        // Clean up
        imagedestroy($source_image);
        imagedestroy($new_image);
        
        return $image_data;
    }
    
    // Clean up on error
    imagedestroy($source_image);
    imagedestroy($new_image);
    
    return false;
}

function deletePassportPhoto($filename) {
    $file_path = 'uploads/passport_photos/' . $filename;
    
    if (file_exists($file_path)) {
        return unlink($file_path);
    }
    
    return true; // File doesn't exist, consider it deleted
}

function getPassportPhotoUrl($filename) {
    if (empty($filename) || $filename === 'default-student.png') {
        return 'images/default-avatar.png';
    }
    
    $file_path = 'uploads/passport_photos/' . $filename;
    if (file_exists($file_path)) {
        return $file_path;
    }
    
    return 'images/default-avatar.png';
}

function updateStudentPhoto($student_id, $filename) {
    global $conn;
    
    // Get current photo to delete old one
    $current_photo_sql = "SELECT profile_image FROM students WHERE student_id = ?";
    $current_result = executeQuery($current_photo_sql, [$student_id], 's');
    
    if (!empty($current_result)) {
        $current_photo = $current_result[0]['profile_image'];
        
        // Delete old photo if it's not the default
        if ($current_photo !== 'default-student.png') {
            deletePassportPhoto($current_photo);
        }
    }
    
    // Update database with new photo
    $update_sql = "UPDATE students SET profile_image = ? WHERE student_id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("ss", $filename, $student_id);
    
    if ($stmt->execute()) {
        logActivity($_SESSION['user_id'], $_SESSION['role'], 'Photo Updated', "Updated passport photo for student: $student_id", 'students', $student_id);
        return true;
    }
    
    return false;
}

function validatePassportPhotoRequirements($file) {
    $requirements = [];
    
    // Check image dimensions
    $image_info = getimagesize($file['tmp_name']);
    if ($image_info) {
        $width = $image_info[0];
        $height = $image_info[1];
        
        // Passport photos should be roughly portrait orientation
        if ($width > $height) {
            $requirements[] = 'Photo should be in portrait orientation (height > width)';
        }
        
        // Minimum dimensions
        if ($width < 200 || $height < 250) {
            $requirements[] = 'Photo dimensions too small. Minimum: 200x250 pixels';
        }
        
        // Maximum dimensions
        if ($width > 800 || $height > 1000) {
            $requirements[] = 'Photo dimensions too large. Maximum: 800x1000 pixels';
        }
    }
    
    // Check file size
    $file_size_mb = $file['size'] / (1024 * 1024);
    if ($file_size_mb > 2) {
        $requirements[] = 'File size too large. Maximum: 2MB for optimal upload';
    }
    
    return $requirements;
}

function createPhotoGallery($student_id) {
    // Create a gallery view for student photos
    $photo_sql = "SELECT profile_image FROM students WHERE student_id = ?";
    $result = executeQuery($photo_sql, [$student_id], 's');
    
    if (!empty($result)) {
        $photo = $result[0]['profile_image'];
        $photo_url = getPassportPhotoUrl($photo);
        
        return [
            'current_photo' => $photo,
            'photo_url' => $photo_url,
            'upload_date' => filemtime('uploads/passport_photos/' . $photo) ?? null
        ];
    }
    
    return null;
}
?>
