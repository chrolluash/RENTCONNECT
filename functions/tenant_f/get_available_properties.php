<?php
// functions/tenant_f/get_available_properties.php - Get All Available Properties for Tenants

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

// Check if user is logged in and is a tenant
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'tenant') {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized. Please login as a tenant.',
        'debug' => [
            'session_exists' => isset($_SESSION['user_id']),
            'user_role' => $_SESSION['user_role'] ?? 'none'
        ]
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
    
    // Get all AVAILABLE properties from ALL landlords
    $stmt = $conn->prepare("
        SELECT 
            p.id, 
            p.title, 
            p.type, 
            p.rent, 
            p.bedrooms, 
            p.bathrooms, 
            p.area, 
            p.address, 
            p.latitude, 
            p.longitude, 
            p.description, 
            p.status, 
            p.landlord_id,
            p.created_at, 
            p.updated_at,
            u.name as landlord_name,
            u.email as landlord_email
        FROM properties p
        LEFT JOIN users u ON p.landlord_id = u.id
        WHERE p.status = 'available'
        ORDER BY p.created_at DESC
    ");
    
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    
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
        
        // Add property with photos and landlord info
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
            'landlordId' => $row['landlord_id'],
            'landlordName' => $row['landlord_name'],
            'landlordEmail' => $row['landlord_email'],
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