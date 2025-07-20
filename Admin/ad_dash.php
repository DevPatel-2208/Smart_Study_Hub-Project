<?php
session_start();
include '../db.php';

// Admin login check
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch statistics from all tables
$usersCount = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
$materialsCount = $conn->query("SELECT COUNT(*) FROM study_materials")->fetch_row()[0];
$downloadsCount = $conn->query("SELECT COUNT(*) FROM downloads")->fetch_row()[0];
$reviewsCount = $conn->query("SELECT COUNT(*) FROM file_reviews")->fetch_row()[0];

// Get recent activities
$recentActivities = $conn->query("
    SELECT * FROM user_activities 
    ORDER BY activity_time DESC 
    LIMIT 5
");

// Get popular materials
$popularMaterials = $conn->query("
    SELECT sm.title, COUNT(d.id) as download_count 
    FROM study_materials sm
    LEFT JOIN downloads d ON sm.id = d.material_id
    GROUP BY sm.id
    ORDER BY download_count DESC
    LIMIT 5
");

// Get recent reviews
$recentReviews = $conn->query("
    SELECT fr.*, u.name as user_name, sm.title as material_title
    FROM file_reviews fr
    JOIN users u ON fr.user_id = u.id
    JOIN study_materials sm ON fr.material_id = sm.id
    ORDER BY fr.created_at DESC
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Smart Study Hub</title>
    <link rel="stylesheet" href="../Bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
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
            padding: 20px;
            margin-top:     -35px;
            transition: margin-left 0.3s;
        }
        
        body.sidebar-collapsed .main-content {
            margin-left: 80px;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            border-left: 4px solid var(--primary-color);
            margin-bottom: 2rem;
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card .card-icon {
            font-size: 2rem;
            opacity: 0.3;
        }
        
        .dashboard-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            border: 1px solid var(--accent-color);
            margin-bottom: 2rem;
        }
        
        .activity-item {
            border-left: 3px solid var(--primary-color);
            padding-left: 15px;
            margin-bottom: 15px;
        }
        
        .activity-item.login {
            border-left-color: #28a745 !important;
        }
        
        .activity-item.logout {
            border-left-color: #dc3545 !important;
        }
        
        .activity-item.download {
            border-left-color: #ffc107 !important;
        }
        
        .review-item {
            border-left: 3px solid #ffc107 !important;
            padding-left: 15px;
            margin-bottom: 15px;
        }
        
        .rating-stars i {
            color: #ffc107;
        }
        
        @media (max-width: 992px) {
            .main-content {
                margin-left: 0;
            }
            
            .stat-card {
                margin-bottom: 1rem;
            }
        }
        
        @media (max-width: 768px) {
            .dashboard-card {
                border-radius: 0;
                margin-left: -15px;
                margin-right: -15px;
            }
            
            .stat-card .card-body {
                padding: 1rem;
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
            
            .stat-card .card-title {
                font-size: 0.9rem;
            }
            
            .stat-card .h4 {
                font-size: 1.25rem;
            }
        }
        .page-header {
    background-color: #ffffff;
    border-radius: 0.5rem;
    box-shadow: 0 0.15rem 1.75rem rgba(0, 0, 0, 0.05);
    border-left: 4px solid #4e73df;
}

    </style>
</head>
<body>
    <?php include 'ad.php'; ?>
    
    <div class="main-content">
        <!-- Page Header -->
       <div class="page-header bg-white p-3 px-4 rounded shadow-sm d-flex justify-content-between align-items-center flex-wrap mb-4 border-start border-4 border-primary">
    <div class="d-flex align-items-center gap-2">
        <i class="bi bi-speedometer2 text-primary fs-4"></i>
        <h4 class="mb-0 fw-semibold text-dark">Admin Dashboard</h4>
    </div>
    <small class="text-muted"><i class="bi bi-clock"></i> Last updated: <?= date('d M Y h:i A') ?></small>
</div>

        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="stat-card card shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                 <div class="text-uppercase text-primary fw-bold xx-large mb-2">Total Users</div>
            <div class="h4 fw-bold text-dark"><?= $usersCount ?></div>
        </div>
        <div class="col-auto">
            <i class="bi bi-people-fill fs-1 text-primary"></i>
        </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="stat-card card shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-uppercase fw-bold xx-large mb-2 text-success">
                                    Study Materials</div>
                                <div class="h4 font-weight-bold text-dark"><?= $materialsCount ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-file-earmark-text fs-1 text-success"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="stat-card card shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-uppercase fw-bold xx-large mb-2 text-info">
                                    Total Downloads</div>
                                <div class="h4 mb-0 font-weight-bold text-gray-800"><?= $downloadsCount ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-download fs-1 text-info"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="stat-card card shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-uppercase fw-bold xx-large mb-2 text-warning">
                                    File Reviews</div>
                                <div class="h4 mb-0 font-weight-bold text-gray-800"><?= $reviewsCount ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-star fs-1 text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Row -->
        <div class="row">
            <!-- Recent Activities -->
            <div class="col-lg-6 mb-4">
                <div class="dashboard-card p-4">
                    <h5 class="mb-3"><i class="bi bi-activity text-primary"></i> Recent Activities</h5>
                    <?php if ($recentActivities->num_rows > 0): ?>
                        <?php while($activity = $recentActivities->fetch_assoc()): ?>
                            <div class="activity-item <?= $activity['activity_type'] ?>">
                                <div class="d-flex justify-content-between">
                                    <strong><?= htmlspecialchars($activity['title'] ?? 'System Activity') ?></strong>
                                    <small><?= date('h:i A', strtotime($activity['activity_time'])) ?></small>
                                </div>
                                <p class="mb-1"><?= htmlspecialchars($activity['description']) ?></p>
                                <small class="text-muted"><?= date('d M Y', strtotime($activity['activity_time'])) ?></small>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center text-muted py-3">
                            <i class="bi bi-activity" style="font-size: 2rem;"></i>
                            <p>No recent activities found</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Popular Materials -->
            <div class="col-lg-6 mb-4">
                <div class="dashboard-card p-4">
                    <h5 class="mb-3"><i class="bi bi-graph-up text-primary"></i> Popular Materials</h5>
                    <?php if ($popularMaterials->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Material</th>
                                        <th>Downloads</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($material = $popularMaterials->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($material['title']) ?></td>
                                            <td><?= $material['download_count'] ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted py-3">
                            <i class="bi bi-file-earmark-text" style="font-size: 2rem;"></i>
                            <p>No download data available</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Reviews -->
        <div class="row">
            <div class="col-12">
                <div class="dashboard-card p-4">
                    <h5 class="mb-3"><i class="bi bi-star-fill text-primary"></i> Recent Reviews</h5>
                    <?php if ($recentReviews->num_rows > 0): ?>
                        <?php while($review = $recentReviews->fetch_assoc()): ?>
                            <div class="review-item mb-3">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <strong><?= htmlspecialchars($review['user_name']) ?></strong> reviewed 
                                        <strong><?= htmlspecialchars($review['material_title']) ?></strong>
                                    </div>
                                    <div class="rating-stars">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="bi <?= $i <= $review['rating'] ? 'bi-star-fill' : 'bi-star' ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <p class="mb-1"><?= htmlspecialchars($review['review']) ?></p>
                                <small class="text-muted"><?= date('d M Y h:i A', strtotime($review['created_at'])) ?></small>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center text-muted py-3">
                            <i class="bi bi-star" style="font-size: 2rem;"></i>
                            <p>No reviews available</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="../Bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            // Handle sidebar toggle
            $('.sidebar-toggler').click(function() {
                $('body').toggleClass('sidebar-collapsed');
            });
            
            // Initialize any tables if needed
            $('table').DataTable({
                responsive: true,
                paging: false,
                searching: false,
                info: false
            });
        });
    </script>
</body>
</html>