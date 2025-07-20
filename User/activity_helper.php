<?php
function logActivity($conn, $userId, $activityType, $title = null, $description = null) {
    $stmt = $conn->prepare("INSERT INTO user_activities (user_id, activity_type, title, description) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $userId, $activityType, $title, $description);
    $stmt->execute();
    $stmt->close();
}
?>
