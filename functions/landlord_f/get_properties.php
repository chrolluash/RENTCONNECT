<?php
// landlord_f/get_properties.php - Get Landlord's Properties

// Start output buffering
ob_start();

// Start session
session_start();

// Set headers for JSON response
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
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
    // Create database connection
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    
    // Get all properties for this landlord
    $stmt = $conn->prepare("SELECT id, title, type, rent, bedrooms, bathrooms, area, address, latitude, longitude, description, status, created_at, updated_at FROM properties WHERE landlord_id = ? ORDER BY created_at DESC");
    
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $landlordId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $properties = [];
    
    while ($row = $result->fetch_assoc()) {
        $propertyId = $row['id'];
        
        // Get photos for this property
        $photoStmt = $conn->prepare("SELECT photo_path FROM property_photos WHERE property_id = ? ORDER BY photo_order ASC");
        $photoStmt->bind_param("i", $propertyId);
        $photoStmt->execute();
        $photoResult = $photoStmt->get_result();
        
        $photos = [];
        while ($photoRow = $photoResult->fetch_assoc()) {
            $photos[] = $photoRow['photo_path'];
        }
        $photoStmt->close();
        
        // Add property with photos and coordinates
        $properties[] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'type' => $row['type'],
            'rent' => floatval($row['rent']),
            'bedrooms' => intval($row['bedrooms']),
            'bathrooms' => intval($row['bathrooms']),
            'area' => floatval($row['area']),
            'address' => $row['address'],
            'latitude' => $row['latitude'] ? floatval($row['latitude']) : null,
            'longitude' => $row['longitude'] ? floatval($row['longitude']) : null,
            'description' => $row['description'],
            'status' => $row['status'],
            'photos' => $photos,
            'createdAt' => $row['created_at'],
            'updatedAt' => $row['updated_at']
        ];
    }
    
    $stmt->close();
    $conn->close();
    
    ob_clean();
    echo json_encode([
        'success' => true,
        'properties' => $properties,
        'count' => count($properties)
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
    exit;
    
} catch (Exception $e) {
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