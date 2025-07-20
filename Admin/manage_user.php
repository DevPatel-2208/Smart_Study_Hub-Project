<?php
include '../db.php';
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Handle delete action
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    
    // First, check if user exists
    $check_query = "SELECT image FROM users WHERE id = $delete_id";
    $result = $conn->query($check_query);
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Delete user from database
        $delete_query = "DELETE FROM users WHERE id = $delete_id";
        if ($conn->query($delete_query)) {
            // Delete user image if exists
            if (!empty($user['image'])) {
                $image_path = '../uploads/profiles/' . $user['image'];
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }
            $message = "success|User deleted successfully!";
        } else {
            $message = "error|Error deleting user: " . $conn->error;
        }
    } else {
        $message = "error|User not found!";
    }
    
    header("Location: manage_user.php?message=$message");
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

// Get total count of users
$total = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];
$pages = ceil($total / $perPage);

// Get users with pagination
$query = "SELECT id, name, email, image, created_at FROM users ORDER BY created_at DESC LIMIT $start, $perPage";
$users = $conn->query($query);

// Search functionality
if (isset($_GET['search'])) {
    $search = $conn->real_escape_string($_GET['search']);
    $query = "SELECT id, name, email, image, created_at FROM users 
              WHERE name LIKE '%$search%' OR email LIKE '%$search%' 
              ORDER BY created_at DESC";
    
    $users = $conn->query($query);
    $total = $users->num_rows;
    $pages = 1; // Reset pagination for search results
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .main-content {
            margin-left: 250px;
            padding: -20px;
            transition: margin-left 0.3s;
        }
        @media (max-width: 992px) {
            .main-content {
                margin-left: 0;
            }
        }
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
        .user-avatar {
            width: 120px;
            height: 120px;
            border-radius:10%;
            object-fit: cover;
            border: 2px solid #dee2e6;
        }
        .action-btns .btn {
            padding: 0.35rem 0.5rem;
            font-size: 0.875rem;
            margin: 2px;
        }
        .search-box {
            max-width: 400px;
        }
        @media (max-width: 768px) {
            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            .action-btns .btn {
                display: block;
                width: 100%;
                margin-bottom: 5px;
            }
            .search-box {
                max-width: 100%;
            }
        }
        .status-badge {
            font-size: 0.75rem;
            font-weight: 500;
        }
    </style>
</head>
<body>

<?php include 'ad.php'; ?>

<div class="main-content">
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="card-title mb-0">
                                <i class="bi bi-people-fill"></i> Manage Users
                            </h4>
                            <a href="add_student.php" class="btn btn-primary">
                                <i class="bi bi-plus-circle-fill"></i> Add New User
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
                    <i class="bi bi-<?= $type === 'success' ? 'check-circle-fill' : ($type === 'error' ? 'exclamation-triangle-fill' : 'info-circle-fill') ?> me-2"></i>
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
                                <input type="text" name="search" class="form-control" placeholder="Search users..." 
                                       value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                                <button class="btn btn-outline-secondary" type="submit">
                                    <i class="bi bi-search"></i> Search
                                </button>
                                <?php if (isset($_GET['search'])): ?>
                                    <a href="manage_user.php" class="btn btn-outline-danger">
                                        <i class="bi bi-x-circle-fill"></i> Clear
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
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>User</th>
                                        <th>Email</th>
                                        <th>Joined</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($users->num_rows > 0): ?>
                                        <?php $counter = $start + 1; ?>
                                        <?php while($user = $users->fetch_assoc()): ?>
                                            <tr>
                                                <td><?= $counter ?></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <?php if (!empty($user['image'])): ?>
                                                            <img src="../uploads/profiles/<?= htmlspecialchars($user['image']) ?>" 
                                                                 class="user-avatar me-2" 
                                                                 alt="<?= htmlspecialchars($user['name']) ?>">
                                                        <?php else: ?>
                                                            <div class="user-avatar bg-secondary text-white d-flex align-items-center justify-content-center me-2">
                                                                <i class="bi bi-person-fill"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                        <div>
                                                            <div class="fw-medium"><?= htmlspecialchars($user['name']) ?></div>
                                                            <small class="text-muted">ID: <?= $user['id'] ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?= htmlspecialchars($user['email']) ?></td>
                                                <td>
                                                    <?= date('M d, Y', strtotime($user['created_at'])) ?>
                                                    <div class="text-muted small">
                                                        <?= date('h:i A', strtotime($user['created_at'])) ?>
                                                    </div>
                                                </td>
                                                <td class="action-btns">
                                                    <a href="update_user.php?id=<?= $user['id'] ?>" 
                                                       class="btn btn-sm btn-primary" 
                                                       title="Edit">
                                                        <i class="bi bi-pencil-fill"></i> Edit
                                                    </a>
                                                    <button class="btn btn-sm btn-danger delete-btn" 
                                                            data-id="<?= $user['id'] ?>" 
                                                            title="Delete">
                                                        <i class="bi bi-trash-fill"></i> Delete
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php $counter++; ?>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-4">
                                                <i class="bi bi-people-slash" style="font-size: 2rem;"></i>
                                                <h5 class="mt-2">No users found</h5>
                                                <?php if (isset($_GET['search'])): ?>
                                                    <p>Try a different search term</p>
                                                <?php else: ?>
                                                    <p>Add your first user</p>
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
    // Delete button click handler
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const userId = this.getAttribute('data-id');
            
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
                    window.location.href = `manage_user.php?delete_id=${userId}`;
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