<?php
// index_f/save_user.php - User Registration Handler (FIXED)

// NO WHITESPACE BEFORE THIS LINE!

// Start output buffering to catch any accidental output
ob_start();

// Start session
session_start();

// Set headers for JSON response BEFORE any output
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include database configuration
require_once '../config.php';

// Clear any previous output buffer
ob_clean();

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Validate input
if (!$data) {
    ob_clean(); // Clear buffer before output
    echo json_encode([
        'success' => false,
        'message' => 'Invalid input data'
    ]);
    ob_end_flush();
    exit;
}

// Extract and sanitize data
$firstName = trim($data['firstName'] ?? '');
$lastName = trim($data['lastName'] ?? '');
$email = trim($data['email'] ?? '');
$contact = trim($data['contact'] ?? '');
$role = trim($data['role'] ?? '');
$password = $data['password'] ?? '';
$authProvider = $data['authProvider'] ?? 'email';
$uid = $data['uid'] ?? null; // For backward compatibility with Firebase

// Validation
$errors = [];

if (empty($firstName)) {
    $errors[] = 'First name is required';
}

if (empty($lastName)) {
    $errors[] = 'Last name is required';
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Valid email is required';
}

if (empty($contact)) {
    $errors[] = 'Contact number is required';
}

if (empty($role) || !in_array($role, ['tenant', 'landlord'])) {
    $errors[] = 'Valid role is required';
}

// Password validation (only for email auth)
if ($authProvider === 'email') {
    if (empty($password) || strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters';
    }
}

// Check for validation errors
if (!empty($errors)) {
    ob_clean(); // Clear buffer before output
    echo json_encode([
        'success' => false,
        'message' => implode(', ', $errors)
    ]);
    ob_end_flush();
    exit;
}

try {
    // Create database connection
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    // Check connection
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }

    // Set charset to UTF-8
    $conn->set_charset("utf8mb4");

    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        ob_clean(); // Clear buffer before output
        echo json_encode([
            'success' => false,
            'message' => 'This email already exists. Please login instead.'
        ]);
        $stmt->close();
        $conn->close();
        ob_end_flush();
        exit;
    }
    $stmt->close();

    // Hash the password (for email auth)
    $hashedPassword = null;
    if ($authProvider === 'email' && !empty($password)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    }

    // Generate UID if not provided (for standard auth)
    if (empty($uid)) {
        $uid = uniqid('user_', true);
    }

    // Insert new user (using your actual column name: contact_number)
    $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, contact_number, role, password, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param("ssssss", $firstName, $lastName, $email, $contact, $role, $hashedPassword);

    if ($stmt->execute()) {
        $userId = $conn->insert_id;

        // DON'T create session on signup - user must login first
        // Session will be created in check_user.php when they login

        ob_clean(); // Clear buffer before output
        echo json_encode([
            'success' => true,
            'message' => 'Registration successful. Please log in.',
            'user' => [
                'id' => $userId,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'role' => $role
            ]
        ], JSON_UNESCAPED_UNICODE);
        
        $stmt->close();
        $conn->close();
        ob_end_flush();
        exit;
    } else {
        throw new Exception('Failed to create account: ' . $stmt->error);
    }

} catch (Exception $e) {
    ob_clean(); // Clear buffer before output
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    if (isset($stmt)) $stmt->close();
    if (isset($conn)) $conn->close();
    ob_end_flush();
    exit;
}
?>