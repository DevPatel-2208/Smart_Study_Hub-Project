<?php
session_start();
include '../db.php';
include '../User/notification_helper.php';

// Admin login check
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Handle notification sending
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_notification'])) {
    $message = trim($_POST['message']);
    $userIds = isset($_POST['user_ids']) ? $_POST['user_ids'] : [];

    if (!empty($message) && !empty($userIds)) {
        foreach ($userIds as $uid) {
            $uid = intval($uid);
            sendNotification($conn, $uid, $message);
        }
        $success = "Notification sent successfully to selected users!";
    } else {
        $success = "error|Please fill all fields and select at least one user!";
    }
}

// Fetch all users
$users = $conn->query("SELECT id, name FROM users ORDER BY name ASC");

// Fetch notifications
$notifications = $conn->query("
    SELECT n.*, u.name AS user_name
    FROM notifications n
    JOIN users u ON n.user_id = u.id
    ORDER BY n.created_at DESC
    LIMIT 200
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification Management | Admin Panel</title>
    <link rel="stylesheet" href="../Bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
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
        
        .main-content {
            margin-left: 250px;
            padding: -90px;
            margin-top: -10px;
            transition: margin-left 0.3s;
        }
        
        body.sidebar-collapsed .main-content {
            margin-left: 80px;
        }
        
        .card-custom {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            border: 1px solid var(--accent-color);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }
        
        .page-header {
            background-color: white;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
        }
        
        .table thead {
            background-color: var(--primary-color);
            color: white;
        }
        
        .badge-read {
            background-color: #28a745;
            font-size: xx-large;
            font-weight: 700;
        }
        
        .badge-unread {
            background-color: #ffc107;
            color: #212529;
        }
        
        /* Mobile optimizations */
        @media (max-width: 992px) {
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
            
            .page-header {
                padding: 0.75rem;
            }
            
            .card-custom {
                border-radius: 0;
                margin-left: -15px;
                margin-right: -15px;
            }
        }
        
        @media (max-width: 768px) {
            .main-content {
                padding: 10px;
            }
            
            #notificationsTable th:nth-child(3),
            #notificationsTable td:nth-child(3) {
                max-width: 150px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            
            .select2-container {
                width: 100% !important;
            }
        }
        
        @media (max-width: 576px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .page-header .btn {
                margin-top: 10px;
                width: 100%;
            }
            
            #notificationsTable th:nth-child(1),
            #notificationsTable td:nth-child(1) {
                display: none;
            }
            
            #notificationsTable th:nth-child(4),
            #notificationsTable td:nth-child(4) {
                text-align: center;
                min-width: 80px;
            }
            
            #notificationsTable th:nth-child(5),
            #notificationsTable td:nth-child(5) {
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'ad.php'; ?>
    
    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header d-flex justify-content-between align-items-center flex-wrap">
            <h4 class="mb-0"><i class="bi bi-bell-fill text-primary"></i> Notification Management</h4>
            <a href="dashboard.php" class="btn btn-primary btn-sm">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <!-- Send Notification Card -->
        <div class="card-custom p-3 p-md-4">
            <h5 class="mb-3"><i class="bi bi-send text-primary"></i> Send New Notification</h5>
            <form method="POST">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label"><i class="bi bi-people"></i> Select Users</label>
                        <select name="user_ids[]" class="form-select select2" multiple="multiple" required>
                            <?php $users->data_seek(0); while ($u = $users->fetch_assoc()): ?>
                                <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label"><i class="bi bi-chat-text"></i> Message</label>
                        <textarea name="message" class="form-control" rows="3" 
                                  placeholder="Enter your notification message here..." required></textarea>
                    </div>
                    <div class="col-12">
                        <button type="submit" name="send_notification" class="btn btn-primary w-20">
                            <i class="bi bi-send-fill"></i> Send Notification
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Notifications List Card -->
<div class="card-custom p-3 p-md-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0"><i class="bi bi-list-check text-primary"></i> Sent Notifications</h5>
        <small class="text-muted">Showing last 200 notifications</small>
    </div>
    <div class="table-responsive">
        <table id="notificationsTable" class="table table-hover table-sm" style="width:100%">
            <thead>
                <tr>
                    <th>#</th>
                    <th><i class="bi bi-person"></i> User</th>
                    <th><i class="bi bi-chat-text"></i> Message</th>
                    <th><i class="bi bi-info-circle"></i> Status</th>
                    <th><i class="bi bi-clock"></i> Sent On</th>
                    <th><i class="bi bi-eye"></i> Seen On</th> <!-- ✅ NEW COLUMN -->
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; while($row = $notifications->fetch_assoc()): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><?= htmlspecialchars($row['user_name']) ?></td>
                        <td><?= htmlspecialchars($row['message']) ?></td>
                        <td>
                            <?php if ($row['is_read']): ?>
                                <span class="badge badge-read"><i class="bi bi-check-circle"></i> Read</span>
                            <?php else: ?>
                                <span class="badge badge-unread"><i class="bi bi-clock"></i> Unread</span>
                            <?php endif; ?>
                        </td>
                        <td><?= date("d M Y h:i ", strtotime($row['created_at'])) ?></td>
                        <td>
                            <?php if ($row['is_read']): ?>
                                <span class="badge bg-success">
                                    <?= date("d M Y h:i ", strtotime($row['read_at'])) ?>
                                </span>
                            <?php else: ?>
                                <span class="badge bg-warning"><i class="bi bi-clock"></i> Unread</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

    </div>

    <script src="../Bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize DataTable with responsive settings
            $('#notificationsTable').DataTable({
                responsive: true,
                pageLength: 10,
                lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]],
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search notifications...",
                    lengthMenu: "Show _MENU_ notifications",
                    zeroRecords: "No matching notifications found",
                    info: "Showing _START_ to _END_ of _TOTAL_ notifications",
                    infoEmpty: "No notifications available",
                    infoFiltered: "(filtered from _MAX_ total notifications)",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "<i class='bi bi-chevron-right'></i>",
                        previous: "<i class='bi bi-chevron-left'></i>"
                    }
                },
                columnDefs: [
                    { responsivePriority: 1, targets: 1 }, // User
                    { responsivePriority: 2, targets: 2 }, // Message
                    { responsivePriority: 3, targets: 4 }, // Date
                    { responsivePriority: 4, targets: 3 }, // Status
                    { responsivePriority: 5, targets: 0 }  // #
                ]
            });
            
            // Initialize Select2
            $('.select2').select2({
                width: '100%',
                placeholder: "Select one or more users",
                allowClear: true,
                dropdownParent: $('.card-custom')
            });
            
            // Show SweetAlert for success/error messages
            <?php if (!empty($success)): ?>
                const messageType = '<?= strpos($success, 'error') === 0 ? 'error' : 'success' ?>';
                const messageText = '<?= strpos($success, 'error') === 0 ? substr($success, 6) : $success ?>';
                
                Swal.fire({
                    icon: messageType,
                    title: messageType === 'success' ? 'Success!' : 'Error!',
                    text: messageText,
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000
                });
            <?php endif; ?>
            
            // Handle sidebar toggle
            $('.sidebar-toggler').click(function() {
                $('body').toggleClass('sidebar-collapsed');
            });
            
            // Adjust Select2 dropdown on mobile
            $(window).on('resize', function() {
                $('.select2-container').css('width', '100%');
            });
        });
    </script>
</body>
</html>