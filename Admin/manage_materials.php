<?php
include '../db.php';
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Handle message from other pages
if (isset($_GET['message'])) {
    list($type, $text) = explode('|', $_GET['message'], 2);
}

// Pagination variables
$perPage = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page > 1) ? ($page * $perPage) - $perPage : 0;

// Get total count of materials
$total = $conn->query("SELECT COUNT(*) as total FROM study_materials")->fetch_assoc()['total'];
$pages = ceil($total / $perPage);

// Get materials with pagination
$query = "SELECT sm.id, sm.title, sm.file_name, sm.uploaded_at, 
                 s.name as semester_name, sub.name as subject_name, 
                 mt.type_name, a.name as uploaded_by
          FROM study_materials sm
          JOIN semesters s ON sm.semester_id = s.id
          JOIN subjects sub ON sm.subject_id = sub.id
          JOIN material_types mt ON sm.material_type_id = mt.id
          JOIN admins a ON sm.uploaded_by = a.id
          ORDER BY sm.uploaded_at DESC
          LIMIT $start, $perPage";

$materials = $conn->query($query);

// Search functionality
if (isset($_GET['search'])) {
    $search = $conn->real_escape_string($_GET['search']);
    $query = "SELECT sm.id, sm.title, sm.file_name, sm.uploaded_at, 
                     s.name as semester_name, sub.name as subject_name, 
                     mt.type_name, a.name as uploaded_by
              FROM study_materials sm
              JOIN semesters s ON sm.semester_id = s.id
              JOIN subjects sub ON sm.subject_id = sub.id
              JOIN material_types mt ON sm.material_type_id = mt.id
              JOIN admins a ON sm.uploaded_by = a.id
              WHERE sm.title LIKE '%$search%' 
                 OR s.name LIKE '%$search%' 
                 OR sub.name LIKE '%$search%'
                 OR mt.type_name LIKE '%$search%'
              ORDER BY sm.uploaded_at DESC";
    
    $materials = $conn->query($query);
    $total = $materials->num_rows;
    $pages = 1; // Reset pagination for search results
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Study Materials - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        :root {
            --sidebar-width: 250px;
        }
        
        body {
            background-color: #f8f9fa;
            overflow-x: hidden;
        }
        /* Main content area */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: -20px;
            width: calc(100% - var(--sidebar-width));
            min-height: 100vh;
            transition: all 0.3s;
        }
        
        /* Card and table styles */
        .card {
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
        }
        
        .table th {
            background-color: #f1f5fd;
            border-bottom-width: 1px;
        }
        
        .badge-file {
            background-color:rgb(231, 196, 98) !important;
            color:black !important;
            font-weight: normal;
        }
        
        .action-btns .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        
        .pagination .page-item.active .page-link {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
        
        .search-box {
            max-width: 400px;
        }
        
        .file-icon {
            font-size: 1.2rem;
            margin-right: 5px;
        }
        
        .material-title {
            font-weight: 500;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .sidebar {
                margin-left: -var(--sidebar-width);
            }
            
            .main-content {
                width: 100%;
                margin-left: 0;
            }
            
            .sidebar.active {
                margin-left: 0;
            }
            
            .main-content.active {
                margin-left: var(--sidebar-width);
            }
        }
    </style>
</head>
<body>
<?php include 'ad.php'; ?>  
    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">          

            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <h4 class="card-title mb-0">
                                    <i class="bi bi-journal-bookmark"></i> Manage Study Materials
                                </h4>
                                <a href="add_material.php" class="btn btn-primary">
                                    <i class="bi bi-plus-circle"></i> Add New Material
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (isset($type)): ?>
            <div class="row mb-4">
                <div class="col-12">
                    <div class="alert alert-<?= $type === 'success' ? 'success' : ($type === 'error' ? 'danger' : 'warning') ?> alert-dismissible fade show">
                        <i class="bi bi-<?= $type === 'success' ? 'check-circle' : ($type === 'error' ? 'exclamation-triangle' : 'info-circle') ?>"></i>
                        <?= htmlspecialchars($text) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <form method="GET" action="">
                                <div class="input-group search-box">
                                    <input type="text" name="search" class="form-control" placeholder="Search materials..." 
                                           value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                                    <button class="btn btn-outline-secondary" type="submit">
                                        <i class="bi bi-search"></i> Search
                                    </button>
                                    <?php if (isset($_GET['search'])): ?>
                                        <a href="manage_materials.php" class="btn btn-outline-danger">
                                            <i class="bi bi-x-circle"></i> Clear
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Title</th>
                                            <th>File</th>
                                            <th>Semester</th>
                                            <th>Subject</th>
                                            <th>Type</th>
                                            <th>Uploaded By</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($materials->num_rows > 0): ?>
                                            <?php $counter = $start + 1; ?>
                                            <?php while($material = $materials->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?= $counter ?></td>
                                                    <td class="material-title"><?= htmlspecialchars($material['title']) ?></td>
                                                    <td>
                                                        <?php
                                                        $fileExt = pathinfo($material['file_name'], PATHINFO_EXTENSION);
                                                        $iconClass = '';
                                                        if (in_array($fileExt, ['pdf'])) {
                                                            $iconClass = 'bi-file-earmark-pdf text-danger';
                                                        } elseif (in_array($fileExt, ['doc', 'docx'])) {
                                                            $iconClass = 'bi-file-earmark-word text-primary';
                                                        } elseif (in_array($fileExt, ['ppt', 'pptx'])) {
                                                            $iconClass = 'bi-file-earmark-ppt text-warning';
                                                        } elseif (in_array($fileExt, ['jpg', 'jpeg', 'png'])) {
                                                            $iconClass = 'bi-file-earmark-image text-success';
                                                        } else {
                                                            $iconClass = 'bi-file-earmark';
                                                        }
                                                        ?>
                                                        <span class="badge badge-file">
                                                            <i class="bi <?= $iconClass ?> file-icon"></i>
                                                            <?= htmlspecialchars($material['file_name']) ?>
                                                        </span>
                                                    </td>
                                                    <td><?= htmlspecialchars($material['semester_name']) ?></td>
                                                    <td><?= htmlspecialchars($material['subject_name']) ?></td>
                                                    <td><?= htmlspecialchars($material['type_name']) ?></td>
                                                    <td><?= htmlspecialchars($material['uploaded_by']) ?></td>
                                                    <td><?= date('M d, Y', strtotime($material['uploaded_at'])) ?></td>
                                                    <td class="action-btns">
                                                        <a href="update_material.php?id=<?= $material['id'] ?>" class="btn btn-sm btn-primary" title="Edit">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <button class="btn btn-sm btn-danger delete-btn" data-id="<?= $material['id'] ?>" title="Delete">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                        <a href="../uploads/<?= $material['file_name'] ?>" class="btn btn-sm btn-success" title="Download" download>
                                                            <i class="bi bi-download"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                                <?php $counter++; ?>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="9" class="text-center py-4">
                                                    <i class="bi bi-journal-x" style="font-size: 2rem;"></i>
                                                    <h5 class="mt-2">No study materials found</h5>
                                                    <?php if (isset($_GET['search'])): ?>
                                                        <p>Try a different search term</p>
                                                    <?php else: ?>
                                                        <p>Add your first study material</p>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <?php if ($pages > 1 && !isset($_GET['search'])): ?>
                            <nav aria-label="Page navigation" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?= $page - 1 ?>" aria-label="Previous">
                                                <span aria-hidden="true">&laquo;</span>
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <?php for($i = 1; $i <= $pages; $i++): ?>
                                        <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($page < $pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?= $page + 1 ?>" aria-label="Next">
                                                <span aria-hidden="true">&raquo;</span>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Sidebar toggle for mobile
        document.getElementById('sidebarToggle')?.addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
            document.querySelector('.main-content').classList.toggle('active');
        });

        // Delete button click handler
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const materialId = this.getAttribute('data-id');
                
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = `update_material.php?delete_id=${materialId}`;
                    }
                });
            });
        });

        // Show SweetAlert message if exists
        <?php if (isset($type)): ?>
            const messageType = '<?= $type ?>';
            const messageText = '<?= addslashes($text) ?>';
            
            let icon = 'info';
            if (messageType === 'success') icon = 'success';
            if (messageType === 'error') icon = 'error';
            if (messageType === 'warning') icon = 'warning';
            
            Swal.fire({
                icon: icon,
                title: messageText,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        <?php endif; ?>
    </script>
</body>
</html>