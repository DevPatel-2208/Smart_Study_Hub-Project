<?php
session_start();
include '../db.php';
require_once 'notification_helper.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user']['id'];
$notifications = getUserNotifications($conn, $userId);
// Mark all as read for that user
$conn->query("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = $userId AND is_read = 0");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Notifications | User Dashboard</title>
    <link rel="stylesheet" href="../Bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #f8f9fc;
            --accent-color: #dddfeb;
            --text-color: #5a5c69;
        }
        
        body {
            background-color: var(--secondary-color);
            font-family: 'Poppins', sans-serif;
        }
        
        .notification-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            border: 1px solid var(--accent-color);
            margin-bottom: 1.5rem;
        }
        
        .notification-item {
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        
        .notification-item.unread {
            border-left-color: var(--primary-color);
            background-color: rgba(78, 115, 223, 0.05);
        }
        
        .notification-time {
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        .page-header {
            background-color: white;
            padding: 1.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            margin-bottom: 1.5rem;
        }
        
        @media (max-width: 768px) {
            .page-header {
                padding: 1rem;
            }
            
            .notification-card {
                border-radius: 0;
                margin-left: -15px;
                margin-right: -15px;
            }
            
            .notification-item {
                padding: 0.75rem;
            }
        }
        
        @media (max-width: 576px) {
            .page-header h4 {
                font-size: 1.25rem;
            }
            
            .notification-time {
                display: block;
                margin-top: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include '../include/header.php'; ?>
    
    <div class="container mt-4 mb-5">
        <!-- Page Header -->
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="bi bi-bell-fill text-primary"></i> My Notifications
                </h4>
                <a href="dashboard.php" class="btn btn-outline-primary">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Notifications Card -->
        <div class="notification-card">
            <?php if ($notifications->num_rows > 0): ?>
                <ul class="list-group list-group-flush">
                    <?php while ($row = $notifications->fetch_assoc()): ?>
                        <li class="list-group-item notification-item <?= $row['is_read'] ? '' : 'unread' ?>">
                            <div class="d-flex justify-content-between align-items-center flex-wrap">
                                <div class="mb-1 mb-md-0">
                                    <i class="bi <?= $row['is_read'] ? 'bi-check-circle text-success' : 'bi-exclamation-circle text-primary' ?>"></i>
                                    <?= htmlspecialchars($row['message']) ?>
                                </div>
                                <div class="notification-time">
                                    <?php if (!empty($row['read_at'])): ?>
                                        <i class="bi bi-clock-history"></i> <?= date('d M Y h:i A', strtotime($row['read_at'])) ?>
                                    <?php else: ?>
                                        <i class="bi bi-dash-circle"></i> Not opened yet
                                    <?php endif; ?>
                                </div>
                            </div>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <div class="p-4 text-center text-muted">
                    <i class="bi bi-bell-slash" style="font-size: 2rem;"></i>
                    <p class="mt-2 mb-0">You don't have any notifications yet</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <?php include '../include/footer.php'; ?>

    <script src="../Bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add animation when page loads
        document.addEventListener('DOMContentLoaded', function() {
            const notifications = document.querySelectorAll('.notification-item');
            notifications.forEach((item, index) => {
                item.style.opacity = '0';
                item.style.transform = 'translateY(20px)';
                item.style.transitionDelay = `${index * 0.1}s`;
                
                setTimeout(() => {
                    item.style.opacity = '1';
                    item.style.transform = 'translateY(0)';
                }, 100);
            });
        });
    </script>
</body>
</html>