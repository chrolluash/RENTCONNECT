<?php
// index_f/check_user.php - User Authentication Handler (Updated for Standard Auth)

// Start session
session_start();

// Set headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Include database configuration
require_once '../config.php';

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Validate input
if (!$data) {
    echo json_encode([
        'success' => false,
        'exists' => false,
        'message' => 'Invalid input data'
    ]);
    exit;
}

// Check if this is a login request or just checking if user exists
$action = $data['action'] ?? 'check';

if ($action === 'login') {
    // LOGIN FUNCTIONALITY
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';
    $requestedRole = trim($data['requestedRole'] ?? '');

    // Validation
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            'success' => false,
            'message' => 'Valid email is required'
        ]);
        exit;
    }

    if (empty($password)) {
        echo json_encode([
            'success' => false,
            'message' => 'Password is required'
        ]);
        exit;
    }

    try {
        // Create database connection
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        // Check connection
        if ($conn->connect_error) {
            throw new Exception('Database connection failed: ' . $conn->connect_error);
        }

        // Find user by email (using your actual column name: contact_number)
        $stmt = $conn->prepare("SELECT id, first_name, last_name, email, contact_number, role, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            echo json_encode([
                'success' => false,
                'message' => 'User not found with this email'
            ]);
            $stmt->close();
            $conn->close();
            exit;
        }

        $user = $result->fetch_assoc();
        $stmt->close();

        // Verify password
        if (!password_verify($password, $user['password'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Incorrect password'
            ]);
            $conn->close();
            exit;
        }

        // Check if user is logging in with correct role
        if (!empty($requestedRole) && $user['role'] !== $requestedRole) {
            echo json_encode([
                'success' => false,
                'message' => 'This account is registered as a ' . $user['role'] . '. Please use the correct login option.'
            ]);
            $conn->close();
            exit;
        }

        // Update last login
        $updateStmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $updateStmt->bind_param("i", $user['id']);
        $updateStmt->execute();
        $updateStmt->close();

        // Create session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];

        // Return success with user data (excluding password)
        unset($user['password']);

        echo json_encode([
            'success' => true,
            'message' => 'Login successful',
            'user' => $user
        ]);

        $conn->close();

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }

} else {
    // CHECK IF USER EXISTS (backward compatibility)
    $uid = $data['uid'] ?? '';
    $email = $data['email'] ?? '';

    try {
        // Create database connection
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        // Check connection
        if ($conn->connect_error) {
            throw new Exception('Database connection failed');
        }

        // Check by UID or email
        if (!empty($uid)) {
            $stmt = $conn->prepare("SELECT id FROM users WHERE uid = ?");
            $stmt->bind_param("s", $uid);
        } elseif (!empty($email)) {
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
        } else {
            echo json_encode([
                'success' => false,
                'exists' => false,
                'message' => 'UID or email required'
            ]);
            exit;
        }

        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;

        echo json_encode([
            'success' => true,
            'exists' => $exists
        ]);

        $stmt->close();
        $conn->close();

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'exists' => false,
            'message' => $e->getMessage()
        ]);
    }
}
?>