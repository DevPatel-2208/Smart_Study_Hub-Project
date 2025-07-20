<?php
session_start();
include '../db.php';
include 'activity_helper.php'; // ✅ Helper for logActivity()

if (isset($_SESSION['user'])) {
    $userId = $_SESSION['user']['id'];

    // ✅ Log logout activity
    logActivity($conn, $userId, 'logout', null, 'User logged out');

    // ✅ Update last_logout in users table
    $conn->query("UPDATE users SET last_login = NOW() WHERE id = $userId");
}

// Destroy session
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// ✅ Redirect to login with success message
header("Location: Home.php");
exit();
?>
