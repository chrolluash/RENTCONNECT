<?php
// logout.php - Logout Handler
session_start();

// Destroy all session data
session_unset();
session_destroy();

// Clear the session cookie
if (isset($_COOKIE['PHPSESSID'])) {
    setcookie('PHPSESSID', '', time() - 3600, '/');
}

// Redirect to index page
header("Location: index.php");
exit;
?>