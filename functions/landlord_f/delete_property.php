<?php
// landlord_f/delete_property.php - Delete Property

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
    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    $propertyId = intval($data['property_id'] ?? 0);
    
    if ($propertyId <= 0) {
        throw new Exception('Invalid property ID');
    }
    
    // Create database connection
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    
    // Verify property belongs to this landlord
    $verifyStmt = $conn->prepare("SELECT id FROM properties WHERE id = ? AND landlord_id = ?");
    $verifyStmt->bind_param("ii", $propertyId, $landlordId);
    $verifyStmt->execute();
    $verifyResult = $verifyStmt->get_result();
    
    if ($verifyResult->num_rows === 0) {
        throw new Exception('Property not found or you do not have permission to delete it');
    }
    $verifyStmt->close();
    
    // Get all photos to delete files
    $photoStmt = $conn->prepare("SELECT photo_path FROM property_photos WHERE property_id = ?");
    $photoStmt->bind_param("i", $propertyId);
    $photoStmt->execute();
    $photoResult = $photoStmt->get_result();
    
    $photosToDelete = [];
    while ($photoRow = $photoResult->fetch_assoc()) {
        $photosToDelete[] = $photoRow['photo_path'];
    }
    $photoStmt->close();
    
    // Start transaction
    $conn->begin_transaction();
    
    // Delete photos from database (will cascade, but doing explicitly for clarity)
    $deletePhotosStmt = $conn->prepare("DELETE FROM property_photos WHERE property_id = ?");
    $deletePhotosStmt->bind_param("i", $propertyId);
    $deletePhotosStmt->execute();
    $deletePhotosStmt->close();
    
    // Delete property
    $deleteStmt = $conn->prepare("DELETE FROM properties WHERE id = ? AND landlord_id = ?");
    $deleteStmt->bind_param("ii", $propertyId, $landlordId);
    
    if (!$deleteStmt->execute()) {
        throw new Exception('Failed to delete property: ' . $deleteStmt->error);
    }
    
    $deleteStmt->close();
    
    // Commit transaction
    $conn->commit();
    $conn->close();
    
    // Delete photo files from server
    foreach ($photosToDelete as $photoPath) {
        $fullPath = '../' . $photoPath;
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }
    
    ob_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Property deleted successfully!',
        'property_id' => $propertyId
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
    exit;
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
        $conn->close();
    }
    
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    ob_end_flush();
    exit;
}
?>