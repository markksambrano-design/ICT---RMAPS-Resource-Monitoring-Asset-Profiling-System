<?php
// Authentication check - redirect to login if not logged in
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit;
}

// Role check - only admin can access admin pages
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // If logged in but not an admin, clear session and redirect to login
    session_destroy();
    header("Location: login.php?error=unauthorized");
    exit;
}

// Get admin name from session (stored during login)
$adminName = isset($_SESSION['name']) ? $_SESSION['name'] : (isset($_SESSION['email']) ? explode('@', $_SESSION['email'])[0] : 'Admin');
?>

