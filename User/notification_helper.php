<?php
if (!function_exists('sendNotification')) {
    function sendNotification($conn, $userId, $message) {
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        $stmt->bind_param("is", $userId, $message);
        $stmt->execute();
        $stmt->close();
    }
}

if (!function_exists('getUserNotifications')) {
    function getUserNotifications($conn, $userId) {
        $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        return $stmt->get_result();
    }
}

if (!function_exists('markNotificationRead')) {
    function markNotificationRead($conn, $notificationId) {
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $notificationId);
        $stmt->execute();
        $stmt->close();
    }
}
?>
