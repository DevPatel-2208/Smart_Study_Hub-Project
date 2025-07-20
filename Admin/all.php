<?php
session_start();
include '../db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$stmt = $conn->query("
    SELECT n.*, u.name AS user_name
    FROM notifications n
    JOIN users u ON n.user_id = u.id
    ORDER BY n.created_at DESC
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>All Notifications - Admin</title>
    <link rel="stylesheet" href="../Bootstrap/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-4">
    <h4 class="text-primary"><i class="bi bi-bell"></i> All Notifications Sent</h4>
    <table class="table table-bordered table-striped">
        <thead class="table-dark text-center">
            <tr>
                <th>#</th>
                <th>User</th>
                <th>Message</th>
                <th>Status</th>
                <th>Sent On</th>
            </tr>
        </thead>
        <tbody>
        <?php $i = 1; while($row = $stmt->fetch_assoc()): ?>
            <tr class="text-center">
                <td><?= $i++ ?></td>
                <td><?= htmlspecialchars($row['user_name']) ?></td>
                <td><?= htmlspecialchars($row['message']) ?></td>
                <td>
                    <?php if ($row['is_read']): ?>
                        <span class="badge bg-success">Read</span>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark">Unread</span>
                    <?php endif; ?>
                </td>
                <td><?= date("d M Y h:i A", strtotime($row['created_at'])) ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>
