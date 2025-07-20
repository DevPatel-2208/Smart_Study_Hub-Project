<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user']['id'];

// Fetch last 50 activities
$stmt = $conn->prepare("SELECT activity_type, title, description, activity_time FROM user_activities WHERE user_id = ? ORDER BY activity_time DESC LIMIT 50");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$activities = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Activities | Smart Study Hub</title><link rel="icon" type="image/x-icon" href="../uploads/unnamed.png">
    <link rel="stylesheet" href="../Bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <style>
        :root {
            --primary-color: #4e73df;
            --primary-dark: #2e59d9;
            --secondary-color: #f8f9fc;
            --accent-color: #dddfeb;
            --text-color: #5a5c69;
            --header-bg: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            
            /* Activity type colors */
            --login-color: #198754;
            --logout-color: #dc3545;
            --download-color: #0d6efd;
            --review-color: #ffc107;
            --profile-color: #0dcaf0;
        }
        
        body {
            background-color: var(--secondary-color);
            color: var(--text-color);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Header Styles */
        .activity-header {
            background: var(--header-bg);
            color: white;
            padding: 1.5rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        
        .activity-header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .activity-title {
            display: flex;
            align-items: center;
            font-size: 1.8rem;
            font-weight: 600;
        }
        
        .activity-title i {
            margin-right: 15px;
            font-size: 2.5rem;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            padding-bottom: 3rem;
        }
        
        /* Activity Card */
        .activity-card {
            background: #fff;
            border-radius: 0.75rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            border: 1px solid var(--accent-color);
            padding: 2rem;
            margin-bottom: 3rem;
        }
        
        /* Activity Badges */
        .activity-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 50rem;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: capitalize;
            white-space: nowrap;
        }
        
        .badge-login {
            background-color: rgba(25, 135, 84, 0.1);
            color: var(--login-color);
            border: 1px solid var(--login-color);
        }
        
        .badge-logout {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--logout-color);
            border: 1px solid var(--logout-color);
        }
        
        .badge-download {
            background-color: rgba(13, 110, 253, 0.1);
            color: var(--download-color);
            border: 1px solid var(--download-color);
        }
        
        .badge-review {
            background-color: rgba(255, 193, 7, 0.1);
            color: var(--review-color);
            border: 1px solid var(--review-color);
        }
        
        .badge-profile {
            background-color: rgba(13, 202, 240, 0.1);
            color: var(--profile-color);
            border: 1px solid var(--profile-color);
        }
        
        .activity-icon {
            font-size: 1.2rem;
            margin-right: 8px;
            vertical-align: middle;
        }
        
        .table thead {
            background-color: var(--primary-color);
            color: white;
        }
        
        .table th {
            text-align: center;
            vertical-align: middle;
            font-weight: 600;
        }
        
        .table td {
            vertical-align: middle;
        }
        
        /* Activity Description */
        .activity-desc {
            font-weight: 500;
            line-height: 1.4;
        }
        
        /* Date/Time Columns */
        .activity-date {
            font-weight: 600;
            color: var(--primary-dark);
        }
        
        .activity-time {
            font-weight: 500;
            color: var(--text-color);
        }
        
        /* Responsive Adjustments */
        @media (max-width: 992px) {
            .activity-title {
                font-size: 1.5rem;
            }
            
            .activity-title i {
                font-size: 2rem;
            }
            
            .activity-card {
                padding: 1.5rem;
            }
            
            .activity-badge {
                font-size: 0.85rem;
                padding: 0.4rem 0.8rem;
            }
        }
        
        @media (max-width: 768px) {
            .activity-header {
                padding: 1rem 0;
            }
            
            .activity-title {
                font-size: 1.3rem;
            }
            
            .activity-card {
                padding: 1.25rem;
            }
            
            .table td, .table th {
                font-size: 0.9rem;
            }
            
            .activity-badge {
                font-size: 0.8rem;
                padding: 0.35rem 0.7rem;
            }
        }
        
        @media (max-width: 576px) {
            .activity-header-content {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .back-btn {
                margin-top: 1rem;
                align-self: flex-end;
            }
            
            .activity-card {
                padding: 1rem;
                margin-left: -10px;
                margin-right: -10px;
                border-radius: 0;
            }
            
            .table td, .table th {
                font-size: 0.8rem;
                padding: 0.5rem;
            }
            
            /* Stack date and time in mobile */
            .activity-time-col {
                display: none;
            }
            
            .activity-date-col::after {
                content: " " attr(data-time);
                display: block;
                font-size: 0.7rem;
                color: #6c757d;
                font-weight: normal;
            }
            
            .activity-badge {
                font-size: 0.75rem;
                padding: 0.3rem 0.6rem;
            }
            
            .activity-icon {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <?php include '../include/header.php'; ?>
    
    <!-- Activity Header -->
    <div class="activity-header">
        <div class="container">
            <div class="activity-header-content">
                <div class="activity-title">
                    <i class="bi bi-activity"></i>
                    <span>My Activities</span>
                </div>
                <div class="back-btn">
                    <a href="dashboard.php" class="btn btn-outline-light">
                        <i class="bi bi-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <div class="activity-card">
                <div class="table-responsive">
                    <table id="activityTable" class="table table-hover">
                        <thead>
                            <tr>
                                <th width="5%">#</th>
                                <th width="20%">Activity Type</th>
                                <th width="45%">Description</th>
                                <th width="15%" class="activity-date-col">Date</th>
                                <th width="15%" class="activity-time-col">Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1; foreach ($activities as $a): 
                                $date = date("d M Y", strtotime($a['activity_time']));
                                $time = date("h:i A", strtotime($a['activity_time']));
                                $type = $a['activity_type'];
                                $desc = $a['description'];

                                $icon = 'bi-clock';
                                $badgeClass = '';
                                if ($type === 'login') { $icon = 'bi-box-arrow-in-right'; $badgeClass = 'badge-login'; }
                                elseif ($type === 'logout') { $icon = 'bi-box-arrow-left'; $badgeClass = 'badge-logout'; }
                                elseif ($type === 'download') { $icon = 'bi-cloud-arrow-down'; $badgeClass = 'badge-download'; }
                                elseif ($type === 'review') { $icon = 'bi-star-fill'; $badgeClass = 'badge-review'; }
                                elseif ($type === 'profile_update') { $icon = 'bi-person-lines-fill'; $badgeClass = 'badge-profile'; }
                            ?>
                                <tr>
                                    <td><?= $i++ ?></td>
                                    <td>
                                        <span class="activity-badge <?= $badgeClass ?>">
                                            <i class="bi <?= $icon ?> activity-icon"></i>
                                            <?= ucwords(str_replace('_', ' ', $type)) ?>
                                        </span>
                                    </td>
                                    <td class="activity-desc"><?= htmlspecialchars($desc) ?></td>
                                    <td class="activity-date-col activity-date" data-time="<?= $time ?>"><?= $date ?></td>
                                    <td class="activity-time-col activity-time"><?= $time ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php include '../include/footer.php'; ?>

    <script src="../Bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#activityTable').DataTable({
                responsive: true,
                pageLength: 10,
                lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]],
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search activities...",
                    lengthMenu: "Show _MENU_ activities per page",
                    zeroRecords: "No matching activities found",
                    info: "Showing _START_ to _END_ of _TOTAL_ activities",
                    infoEmpty: "No activities available",
                    infoFiltered: "(filtered from _MAX_ total activities)",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "<i class='bi bi-chevron-right'></i>",
                        previous: "<i class='bi bi-chevron-left'></i>"
                    }
                },
                dom: '<"top"<"row"<"col-md-6"l><"col-md-6"f>>>rt<"bottom"<"row"<"col-md-6"i><"col-md-6"p>>>',
                initComplete: function() {
                    // Adjust table header on responsive resize
                    $('.dataTables_filter input').addClass('form-control form-control-sm');
                    $('.dataTables_length select').addClass('form-select form-select-sm');
                }
            });
            
            // Handle window resize for mobile view
            function handleResponsiveView() {
                if ($(window).width() < 576) {
                    $('.activity-time-col').hide();
                    $('.activity-date-col').attr('data-time', function() {
                        return $(this).next('.activity-time-col').text();
                    });
                } else {
                    $('.activity-time-col').show();
                    $('.activity-date-col').removeAttr('data-time');
                }
            }
            
            // Run on load and resize
            handleResponsiveView();
            $(window).resize(handleResponsiveView);
        });
    </script>
</body>
</html>