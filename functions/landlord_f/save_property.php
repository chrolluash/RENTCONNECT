<?php
// landlord_f/save_property.php - Save Property (FIXED)

// Start output buffering
ob_start();

// Start session
session_start();

// Set headers for JSON response
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Include database configuration
require_once '../config.php';

// Clear any previous output buffer
ob_clean();

// Check if user is logged in and is a landlord
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'landlord') {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized. Please login as a landlord.'
    ]);
    ob_end_flush();
    exit;
}

$landlordId = $_SESSION['user_id'];

try {
    // Create database connection EARLY
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    
    // Get and sanitize form data using real_escape_string
    $title = isset($_POST['title']) ? $conn->real_escape_string(trim($_POST['title'])) : '';
    $type = isset($_POST['type']) ? $conn->real_escape_string(trim($_POST['type'])) : '';
    $rent = floatval($_POST['rent'] ?? 0);
    $bedrooms = intval($_POST['bedrooms'] ?? 0);
    $bathrooms = intval($_POST['bathrooms'] ?? 0);
    $area = floatval($_POST['area'] ?? 0);
    
    // CRITICAL FIX: Properly handle address
    $address = isset($_POST['address']) ? $conn->real_escape_string(trim($_POST['address'])) : '';
    
    // Get coordinates (can be NULL)
    $latitude = (!empty($_POST['latitude']) && $_POST['latitude'] !== 'null') ? floatval($_POST['latitude']) : null;
    $longitude = (!empty($_POST['longitude']) && $_POST['longitude'] !== 'null') ? floatval($_POST['longitude']) : null;
    
    $description = isset($_POST['description']) ? $conn->real_escape_string(trim($_POST['description'])) : '';
    
    // Debug logging
    error_log('=== SAVE PROPERTY DEBUG ===');
    error_log('Title: ' . $title);
    error_log('Address: [' . $address . ']');
    error_log('Address length: ' . strlen($address));
    error_log('Latitude: ' . ($latitude ?? 'NULL'));
    error_log('Longitude: ' . ($longitude ?? 'NULL'));
    
    // Validation
    $errors = [];
    
    if (empty($title)) $errors[] = 'Property title is required';
    if (empty($type)) $errors[] = 'Property type is required';
    if ($rent <= 0) $errors[] = 'Valid rent amount is required';
    if ($bedrooms < 0) $errors[] = 'Valid number of bedrooms is required';
    if ($bathrooms < 0) $errors[] = 'Valid number of bathrooms is required';
    if ($area <= 0) $errors[] = 'Valid floor area is required';
    if (empty($address)) $errors[] = 'Address is required';
    if (empty($description)) $errors[] = 'Property description is required';
    
    if (!empty($errors)) {
        $conn->close();
        ob_clean();
        echo json_encode([
            'success' => false,
            'message' => implode(', ', $errors)
        ]);
        ob_end_flush();
        exit;
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    // Build INSERT query using escaped strings (same fix as update_property.php)
    $insertQuery = "INSERT INTO properties 
        (landlord_id, title, type, rent, bedrooms, bathrooms, area, address, latitude, longitude, description, status, created_at, updated_at) 
        VALUES 
        ($landlordId, '$title', '$type', $rent, $bedrooms, $bathrooms, $area, '$address', " . 
        ($latitude !== null ? $latitude : "NULL") . ", " . 
        ($longitude !== null ? $longitude : "NULL") . ", " .
        "'$description', 'available', NOW(), NOW())";
    
    error_log('Executing INSERT query...');
    
    if (!$conn->query($insertQuery)) {
        throw new Exception('Failed to save property: ' . $conn->error);
    }
    
    $propertyId = $conn->insert_id;
    error_log('Property saved with ID: ' . $propertyId);
    
    // Verify the insert
    $verifyStmt = $conn->prepare("SELECT address FROM properties WHERE id = ?");
    $verifyStmt->bind_param("i", $propertyId);
    $verifyStmt->execute();
    $verifyResult = $verifyStmt->get_result();
    $verifyData = $verifyResult->fetch_assoc();
    error_log('Address saved in DB: [' . ($verifyData['address'] ?? 'NULL') . ']');
    $verifyStmt->close();
    
    // Handle photo uploads
    $uploadedPhotos = [];
    
    if (isset($_FILES['photos']) && !empty($_FILES['photos']['name'][0])) {
        $uploadDir = '../../uploads/properties/';
        
        // Create upload directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $photoCount = count($_FILES['photos']['name']);
        error_log('Processing ' . $photoCount . ' photos...');
        
        for ($i = 0; $i < $photoCount; $i++) {
            // Check if file was uploaded without errors
            if ($_FILES['photos']['error'][$i] !== UPLOAD_ERR_OK) {
                error_log('Photo ' . $i . ' upload error: ' . $_FILES['photos']['error'][$i]);
                continue;
            }
            
            $fileName = $_FILES['photos']['name'][$i];
            $fileTmpName = $_FILES['photos']['tmp_name'][$i];
            $fileSize = $_FILES['photos']['size'][$i];
            $fileType = $_FILES['photos']['type'][$i];
            
            // Validate file size (max 5MB)
            if ($fileSize > 5 * 1024 * 1024) {
                error_log('Photo ' . $i . ' too large: ' . $fileSize . ' bytes');
                continue;
            }
            
            // Validate file type
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            if (!in_array($fileType, $allowedTypes)) {
                error_log('Photo ' . $i . ' invalid type: ' . $fileType);
                continue;
            }
            
            // Generate unique filename
            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
            $newFileName = 'property_' . $propertyId . '_' . uniqid() . '.' . $fileExtension;
            $uploadPath = $uploadDir . $newFileName;
            
            // Move uploaded file
            if (move_uploaded_file($fileTmpName, $uploadPath)) {
                // Path to save in database (relative to root)
                $photoPath = 'uploads/properties/' . $newFileName;
                
                $photoStmt = $conn->prepare("INSERT INTO property_photos (property_id, photo_path, photo_order) VALUES (?, ?, ?)");
                $photoStmt->bind_param("isi", $propertyId, $photoPath, $i);
                
                if ($photoStmt->execute()) {
                    $uploadedPhotos[] = $photoPath;
                    error_log('Photo saved: ' . $photoPath);
                } else {
                    error_log('Failed to save photo to DB: ' . $photoStmt->error);
                }
                
                $photoStmt->close();
            } else {
                error_log('Failed to move uploaded file: ' . $fileTmpName . ' to ' . $uploadPath);
            }
        }
    }
    
    error_log('Total photos uploaded: ' . count($uploadedPhotos));
    
    // Commit transaction
    $conn->commit();
    $conn->close();
    
    ob_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Property listed successfully!',
        'property' => [
            'id' => $propertyId,
            'title' => $title,
            'address' => $address,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'photos' => $uploadedPhotos
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    ob_end_flush();
    exit;
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
        $conn->close();
    }
    
    error_log('Save property error: ' . $e->getMessage());
    
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    ob_end_flush();
    exit;
}
?>