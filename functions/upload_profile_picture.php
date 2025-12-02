<?php
// functions/upload_profile_picture.php - Upload Profile Picture for Users

// Start output buffering
ob_start();

// Start session
session_start();

// Set headers for JSON response
header('Content-Type: application/json; charset=utf-8');

// Include database configuration
require_once 'config.php';

// Clear any previous output buffer
ob_clean();

// Log for debugging
error_log("=== Profile Picture Upload Debug ===");
error_log("User ID: " . ($_SESSION['user_id'] ?? 'not set'));
error_log("POST data: " . print_r($_POST, true));
error_log("FILES data: " . print_r($_FILES, true));

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Please log in first'
    ]);
    ob_end_flush();
    exit;
}

$userId = $_SESSION['user_id'];

// Check if file was uploaded
if (!isset($_FILES['profile_picture'])) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'No file uploaded - FILES array empty'
    ]);
    ob_end_flush();
    exit;
}

if ($_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
    $errorMessage = 'Upload error: ';
    switch ($_FILES['profile_picture']['error']) {
        case UPLOAD_ERR_INI_SIZE:
            $errorMessage .= 'File exceeds upload_max_filesize';
            break;
        case UPLOAD_ERR_FORM_SIZE:
            $errorMessage .= 'File exceeds MAX_FILE_SIZE';
            break;
        case UPLOAD_ERR_PARTIAL:
            $errorMessage .= 'File was only partially uploaded';
            break;
        case UPLOAD_ERR_NO_FILE:
            $errorMessage .= 'No file was uploaded';
            break;
        default:
            $errorMessage .= 'Unknown error code ' . $_FILES['profile_picture']['error'];
    }
    
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => $errorMessage
    ]);
    ob_end_flush();
    exit;
}

$file = $_FILES['profile_picture'];

// Validate file type
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
$fileType = mime_content_type($file['tmp_name']);

if (!in_array($fileType, $allowedTypes)) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Invalid file type. Only JPG, PNG, and GIF are allowed. Got: ' . $fileType
    ]);
    ob_end_flush();
    exit;
}

// Validate file size (max 5MB)
if ($file['size'] > 5 * 1024 * 1024) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'File is too large. Maximum size is 5MB.'
    ]);
    ob_end_flush();
    exit;
}

try {
    // Create database connection
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    
    // Create uploads directory if it doesn't exist
    $uploadDir = '../uploads/profiles/';
    error_log("Upload directory: " . realpath($uploadDir));
    
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            throw new Exception('Failed to create upload directory');
        }
        error_log("Created directory: " . $uploadDir);
    }
    
    if (!is_writable($uploadDir)) {
        throw new Exception('Upload directory is not writable: ' . $uploadDir);
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'profile_' . $userId . '_' . uniqid() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    $dbPath = 'uploads/profiles/' . $filename;
    
    error_log("Attempting to save to: " . $filepath);
    
    // Get old profile picture to delete it
    $stmt = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $oldPicture = $user['profile_picture'] ?? null;
    $stmt->close();
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Failed to move uploaded file from ' . $file['tmp_name'] . ' to ' . $filepath);
    }
    
    error_log("File saved successfully to: " . $filepath);
    
    // Update database
    $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
    $stmt->bind_param("si", $dbPath, $userId);
    
    if (!$stmt->execute()) {
        // If database update fails, delete the uploaded file
        unlink($filepath);
        throw new Exception('Failed to update database: ' . $stmt->error);
    }
    
    error_log("Database updated successfully");
    
    // Delete old profile picture if it exists
    if ($oldPicture && file_exists('../' . $oldPicture)) {
        unlink('../' . $oldPicture);
        error_log("Deleted old picture: " . $oldPicture);
    }
    
    // Update session variable
    $_SESSION['profile_picture'] = $dbPath;
    
    $stmt->close();
    $conn->close();
    
    ob_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Profile picture updated successfully',
        'profile_picture' => $dbPath
    ]);
    ob_end_flush();
    exit;
    
} catch (Exception $e) {
    error_log("ERROR: " . $e->getMessage());
    
    if (isset($filepath) && file_exists($filepath)) {
        unlink($filepath);
    }
    
    if (isset($stmt)) $stmt->close();
    if (isset($conn)) $conn->close();
    
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    ob_end_flush();
    exit;
}
?><?php
// functions/upload_profile_picture.php - Upload Profile Picture for Users

// Start output buffering
ob_start();

// Start session
session_start();

// Set headers for JSON response
header('Content-Type: application/json; charset=utf-8');

// Include database configuration
require_once 'config.php';

// Clear any previous output buffer
ob_clean();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Please log in first'
    ]);
    ob_end_flush();
    exit;
}

$userId = $_SESSION['user_id'];

// Check if file was uploaded
if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'No file uploaded or upload error occurred'
    ]);
    ob_end_flush();
    exit;
}

$file = $_FILES['profile_picture'];

// Validate file type
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
$fileType = mime_content_type($file['tmp_name']);

if (!in_array($fileType, $allowedTypes)) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Invalid file type. Only JPG, PNG, and GIF are allowed.'
    ]);
    ob_end_flush();
    exit;
}

// Validate file size (max 5MB)
if ($file['size'] > 5 * 1024 * 1024) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'File is too large. Maximum size is 5MB.'
    ]);
    ob_end_flush();
    exit;
}

try {
    // Create database connection
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception('Database connection failed');
    }
    
    $conn->set_charset("utf8mb4");
    
    // Create uploads directory if it doesn't exist
    $uploadDir = '../uploads/profiles/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'profile_' . $userId . '_' . uniqid() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    $dbPath = 'uploads/profiles/' . $filename;
    
    // Get old profile picture to delete it
    $stmt = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $oldPicture = $user['profile_picture'] ?? null;
    $stmt->close();
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Failed to save uploaded file');
    }
    
    // Update database
    $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
    $stmt->bind_param("si", $dbPath, $userId);
    
    if (!$stmt->execute()) {
        // If database update fails, delete the uploaded file
        unlink($filepath);
        throw new Exception('Failed to update database');
    }
    
    // Delete old profile picture if it exists
    if ($oldPicture && file_exists('../' . $oldPicture)) {
        unlink('../' . $oldPicture);
    }
    
    // Update session variable
    $_SESSION['profile_picture'] = $dbPath;
    
    $stmt->close();
    $conn->close();
    
    ob_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Profile picture updated successfully',
        'profile_picture' => $dbPath
    ]);
    ob_end_flush();
    exit;
    
} catch (Exception $e) {
    if (isset($filepath) && file_exists($filepath)) {
        unlink($filepath);
    }
    
    if (isset($stmt)) $stmt->close();
    if (isset($conn)) $conn->close();
    
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    ob_end_flush();
    exit;
}
?>