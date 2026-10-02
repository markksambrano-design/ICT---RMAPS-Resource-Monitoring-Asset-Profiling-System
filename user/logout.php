<?php
include "../config.php";

// Clear all session variables
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie.
// Note: This will destroy the session, and not just the session data!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Use JS to replace history state and redirect to login
echo '<script>
    if (window.history.replaceState) {
        window.history.replaceState(null, null, "login.php");
    }
    window.location.replace("login.php");
</script>';
exit();
?>