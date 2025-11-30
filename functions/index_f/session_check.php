<?php
// index_f/session_check.php - Session Management Helper

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Function to check if user has specific role
function hasRole($role) {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

// Function to require login (redirect if not logged in)
function requireLogin($redirectTo = 'index.php') {
    if (!isLoggedIn()) {
        header('Location: ' . $redirectTo);
        exit;
    }
}

// Function to require specific role
function requireRole($role, $redirectTo = 'index.php') {
    if (!hasRole($role)) {
        header('Location: ' . $redirectTo);
        exit;
    }
}

// Function to get current user data
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'email' => $_SESSION['user_email'] ?? null,
        'name' => $_SESSION['user_name'] ?? null,
        'role' => $_SESSION['user_role'] ?? null
    ];
}

// Function to get user role
function getUserRole() {
    return $_SESSION['user_role'] ?? null;
}

// Function to redirect based on role
function redirectToDashboard() {
    $role = getUserRole();
    
    if ($role === 'tenant') {
        header('Location: tenant-dashboard.php');
        exit;
    } elseif ($role === 'landlord') {
        header('Location: landlord-dashboard.php');
        exit;
    } else {
        header('Location: index.php');
        exit;
    }
}
?>