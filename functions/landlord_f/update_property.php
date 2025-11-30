<?php
// landlord_f/update_property.php - Update Property (COMPLETE FIX)

// Start output buffering
ob_start();

// Start session
session_start();

// Set headers for JSON response
header('Content-Type: application/json; charset=utf-8');

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
    // Get form data - use mysqli_real_escape_string for address
    $propertyId = intval($_POST['property_id'] ?? 0);
    
    // Validation first
    if ($propertyId <= 0) {
        throw new Exception('Invalid property ID');
    }
    
    // Create database connection EARLY
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    
    // Now get and sanitize all form data
    $title = isset($_POST['title']) ? $conn->real_escape_string(trim($_POST['title'])) : '';
    $type = isset($_POST['type']) ? $conn->real_escape_string(trim($_POST['type'])) : '';
    $rent = isset($_POST['rent']) ? floatval($_POST['rent']) : 0;
    $bedrooms = isset($_POST['bedrooms']) ? intval($_POST['bedrooms']) : 0;
    $bathrooms = isset($_POST['bathrooms']) ? intval($_POST['bathrooms']) : 0;
    $area = isset($_POST['area']) ? floatval($_POST['area']) : 0;
    
    // CRITICAL FIX: Properly handle address
    $address = isset($_POST['address']) ? $conn->real_escape_string(trim($_POST['address'])) : '';
    
    $latitude = (!empty($_POST['latitude']) && $_POST['latitude'] !== 'null') ? floatval($_POST['latitude']) : null;
    $longitude = (!empty($_POST['longitude']) && $_POST['longitude'] !== 'null') ? floatval($_POST['longitude']) : null;
    $description = isset($_POST['description']) ? $conn->real_escape_string(trim($_POST['description'])) : '';
    $status = isset($_POST['status']) ? $conn->real_escape_string(trim($_POST['status'])) : 'available';
    
    // Debug logging
    error_log('=== UPDATE PROPERTY DEBUG ===');
    error_log('Property ID: ' . $propertyId);
    error_log('Address received: [' . $address . ']');
    error_log('Address length: ' . strlen($address));
    
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
    
    // Verify property belongs to this landlord
    $verifyStmt = $conn->prepare("SELECT id FROM properties WHERE id = ? AND landlord_id = ?");
    $verifyStmt->bind_param("ii", $propertyId, $landlordId);
    $verifyStmt->execute();
    $verifyResult = $verifyStmt->get_result();
    
    if ($verifyResult->num_rows === 0) {
        $verifyStmt->close();
        $conn->close();
        throw new Exception('Property not found or you do not have permission to edit it');
    }
    $verifyStmt->close();
    
    // Start transaction
    $conn->begin_transaction();
    
    // Build UPDATE query using escaped strings
    $updateQuery = "UPDATE properties SET 
        title = '$title', 
        type = '$type', 
        rent = $rent, 
        bedrooms = $bedrooms, 
        bathrooms = $bathrooms, 
        area = $area, 
        address = '$address', 
        latitude = " . ($latitude !== null ? $latitude : "NULL") . ", 
        longitude = " . ($longitude !== null ? $longitude : "NULL") . ", 
        description = '$description', 
        status = '$status', 
        updated_at = NOW() 
        WHERE id = $propertyId AND landlord_id = $landlordId";
    
    error_log('Executing query with address: [' . $address . ']');
    
    if (!$conn->query($updateQuery)) {
        throw new Exception('Failed to update property: ' . $conn->error);
    }
    
    $affectedRows = $conn->affected_rows;
    error_log('Update executed. Rows affected: ' . $affectedRows);
    
    // Verify the update by reading back the address
    $verifyStmt = $conn->prepare("SELECT address FROM properties WHERE id = ?");
    $verifyStmt->bind_param("i", $propertyId);
    $verifyStmt->execute();
    $verifyResult = $verifyStmt->get_result();
    $verifyData = $verifyResult->fetch_assoc();
    error_log('Address after update (from DB): [' . ($verifyData['address'] ?? 'NULL') . ']');
    $verifyStmt->close();
    
    // Handle new photo uploads if any
    $uploadedPhotos = [];
    
    if (isset($_FILES['photos']) && !empty($_FILES['photos']['name'][0])) {
        $uploadDir = '../uploads/properties/';
        
        // Create upload directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Get current photo count
        $countStmt = $conn->prepare("SELECT COUNT(*) as count FROM property_photos WHERE property_id = ?");
        $countStmt->bind_param("i", $propertyId);
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $currentCount = $countResult->fetch_assoc()['count'];
        $countStmt->close();
        
        $photoCount = count($_FILES['photos']['name']);
        
        for ($i = 0; $i < $photoCount; $i++) {
            // Check if adding this photo would exceed 8 photos
            if ($currentCount + count($uploadedPhotos) >= 8) {
                break;
            }
            
            // Check if file was uploaded without errors
            if ($_FILES['photos']['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }
            
            $fileName = $_FILES['photos']['name'][$i];
            $fileTmpName = $_FILES['photos']['tmp_name'][$i];
            $fileSize = $_FILES['photos']['size'][$i];
            $fileType = $_FILES['photos']['type'][$i];
            
            // Validate file size (max 5MB)
            if ($fileSize > 5 * 1024 * 1024) {
                continue;
            }
            
            // Validate file type
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            if (!in_array($fileType, $allowedTypes)) {
                continue;
            }
            
            // Generate unique filename
            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
            $newFileName = 'property_' . $propertyId . '_' . uniqid() . '.' . $fileExtension;
            $uploadPath = $uploadDir . $newFileName;
            
            // Move uploaded file
            if (move_uploaded_file($fileTmpName, $uploadPath)) {
                // Save to database
                $photoPath = 'uploads/properties/' . $newFileName;
                $photoOrder = $currentCount + count($uploadedPhotos);
                $photoStmt = $conn->prepare("INSERT INTO property_photos (property_id, photo_path, photo_order) VALUES (?, ?, ?)");
                $photoStmt->bind_param("isi", $propertyId, $photoPath, $photoOrder);
                
                if ($photoStmt->execute()) {
                    $uploadedPhotos[] = $photoPath;
                }
                
                $photoStmt->close();
            }
        }
    }
    
    // Commit transaction
    $conn->commit();
    $conn->close();
    
    ob_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Property updated successfully!',
        'property_id' => $propertyId,
        'new_photos' => $uploadedPhotos,
        'updated_address' => $address
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    ob_end_flush();
    exit;
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
        $conn->close();
    }
    
    error_log('Update property error: ' . $e->getMessage());
    
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    ob_end_flush();
    exit;
}
?>