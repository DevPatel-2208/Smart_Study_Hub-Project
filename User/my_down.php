<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user']['id'];

// Fetch downloads for the user
$stmt = $conn->prepare("SELECT d.downloaded_at, sm.title, sm.file_name, mt.type_name, s.name AS subject_name, se.name AS semester_name
    FROM downloads d
    JOIN study_materials sm ON d.material_id = sm.id
    JOIN material_types mt ON sm.material_type_id = mt.id
    JOIN subjects s ON sm.subject_id = s.id
    JOIN semesters se ON sm.semester_id = se.id
    WHERE d.user_id = ?
    ORDER BY d.downloaded_at DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$downloads = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Downloads | Smart Study Hub</title><link rel="icon" type="image/x-icon" href="../uploads/unnamed.png">
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
        }
        
        body {
            background-color: var(--secondary-color);
            color: var(--text-color);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Header Styles */
        .download-header {
            background: var(--header-bg);
            color: white;
            padding: 1.5rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        
        .download-header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .download-title {
            display: flex;
            align-items: center;
            font-size: 1.8rem;
            font-weight: 600;
        }
        
        .download-title i {
            margin-right: 15px;
            font-size: 2.5rem;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            padding-bottom: 3rem;
        }
        
        /* Download Card */
        .download-card {
            background: #fff;
            border-radius: 0.75rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            border: 1px solid var(--accent-color);
            padding: 2rem;
            margin-bottom: 3rem;
        }
        
        /* Table Styles */
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
        
        .badge-type {
            font-size: 0.85rem;
            padding: 0.5rem 0.75rem;
            border-radius: 0.5rem;
            font-weight: 600;
        }
        
        .btn-download {
            background-color: rgb(67, 236, 194) !important;
            color: white;
            font-weight: 500;
        }
        
        .btn-download:hover {
            background-color: white !important;
            color: white;
        }
        
        /* Date/Time Columns */
        .download-date {
            font-weight: 600;
            color: var(--primary-dark);
        }
        
        .download-time {
            font-weight: 500;
            color: var(--text-color);
        }
        
        .download-day {
            font-weight: 500;
            color: #6c757d;
        }
        
        /* Responsive Adjustments */
        @media (max-width: 992px) {
            .download-title {
                font-size: 1.5rem;
            }
            
            .download-title i {
                font-size: 2rem;
            }
            
            .download-card {
                padding: 1.5rem;
            }
            
            .table td, .table th {
                font-size: 0.9rem;
            }
        }
        
        @media (max-width: 768px) {
            .download-header {
                padding: 1rem 0;
            }
            
            .download-title {
                font-size: 1.3rem;
            }
            
            .download-card {
                padding: 1.25rem;
            }
            
            /* Hide day column on tablets */
            .day-col {
                display: none;
            }
        }
        
        @media (max-width: 576px) {
            .download-header-content {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .back-btn {
                margin-top: 1rem;
                align-self: flex-end;
            }
            
            .download-card {
                padding: 1rem;
                margin-left: -10px;
                margin-right: -10px;
                border-radius: 0;
            }
            
            /* Stack date and time in mobile */
            .time-col {
                display: none;
            }
            
            .date-col::after {
                content: " " attr(data-time);
                display: block;
                font-size: 0.7rem;
                color: #6c757d;
                font-weight: normal;
            }
            
            /* Hide subject column on mobile */
            .subject-col {
                display: none;
            }
            
            /* Make download button smaller */
            .btn-download {
                padding: 0.25rem 0.5rem;
                font-size: 0.8rem;
            }
            
            .badge-type {
                font-size: 0.75rem;
                padding: 0.3rem 0.6rem;
            }
        }
    </style>
</head>
<body>
    <?php include '../include/header.php'; ?>
    
    <!-- Download Header -->
    <div class="download-header">
        <div class="container">
            <div class="download-header-content">
                <div class="download-title">
                    <i class="bi bi-cloud-arrow-down"></i>
                    <span>My Downloads</span>
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
            <div class="download-card">
                <div class="table-responsive">
                    <table id="downloadsTable" class="table table-hover">
                        <thead>
                            <tr>
                                <th width="5%">#</th>
                                <th width="25%">Title</th>
                                <th width="15%" class="subject-col">Subject</th>
                                <th width="10%">Type</th>
                                <th width="15%" class="date-col">Date</th>
                                <th width="10%" class="time-col">Time</th>
                                <th width="10%" class="day-col">Day</th>
                                <th width="10%">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1; foreach ($downloads as $d): 
                                $date = date("d M Y", strtotime($d['downloaded_at']));
                                $time = date("h:i A", strtotime($d['downloaded_at']));
                                $day = date("l", strtotime($d['downloaded_at']));
                            ?>
                                <tr>
                                    <td><?= $i++ ?></td>
                                    <td><?= htmlspecialchars($d['title']) ?></td>
                                    <td class="subject-col"><?= htmlspecialchars($d['subject_name']) ?></td>
                                    <td><span class="badge badge-type bg-info"><?= htmlspecialchars($d['type_name']) ?></span></td>
                                    <td class="date-col download-date" data-time="<?= $time ?>"><?= $date ?></td>
                                    <td class="time-col download-time"><?= $time ?></td>
                                    <td class="day-col download-day"><?= $day ?></td>
                                    <td>
                                        <a href="../uploads/<?= htmlspecialchars($d['file_name']) ?>" class="btn btn-sm btn-download" download>
                                            <i class="bi bi-cloud-arrow-down"></i> <span class="d-none d-sm-inline">Download</span>
                                        </a>
                                    </td>
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
            $('#downloadsTable').DataTable({
                responsive: true,
                pageLength: 10,
                lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]],
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search downloads...",
                    lengthMenu: "Show _MENU_ downloads per page",
                    zeroRecords: "No matching downloads found",
                    info: "Showing _START_ to _END_ of _TOTAL_ downloads",
                    infoEmpty: "No downloads available",
                    infoFiltered: "(filtered from _MAX_ total downloads)",
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
                    $('.time-col, .subject-col, .day-col').hide();
                    $('.date-col').attr('data-time', function() {
                        return $(this).next('.time-col').text();
                    });
                } else if ($(window).width() < 768) {
                    $('.day-col').hide();
                    $('.time-col, .subject-col').show();
                    $('.date-col').removeAttr('data-time');
                } else {
                    $('.time-col, .subject-col, .day-col').show();
                    $('.date-col').removeAttr('data-time');
                }
            }
            
            // Run on load and resize
            handleResponsiveView();
            $(window).resize(handleResponsiveView);
        });
    </script>
</body>
</html>