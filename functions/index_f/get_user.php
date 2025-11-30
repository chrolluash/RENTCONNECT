<?php
// ==================== get_user.php ====================
// Get user details by UID

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET') {
    $uid = null;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $uid = $data['uid'] ?? null;
    } else {
        $uid = $_GET['uid'] ?? null;
    }
    
    if (!$uid) {
        echo json_encode([
            'success' => false,
            'message' => 'UID is required'
        ]);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                id,
                uid,
                email,
                first_name,
                last_name,
                contact_number,
                role,
                auth_provider,
                photo_url,
                created_at
            FROM users 
            WHERE uid = ?
        ");
        $stmt->execute([$uid]);
        $user = $stmt->fetch();
        
        if ($user) {
            echo json_encode([
                'success' => true,
                'user' => $user
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'User not found'
            ]);
        }
        
    } catch(PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?>