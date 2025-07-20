<?php
include '../db.php';
// Check if admin is logged in
// if (!isset($_SESSION['admin_id'])) {
//     header("Location: login.php");
//     exit();
// }

$message = "";

// Add Material Type
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_material_type'])) {
    $type_name = trim($_POST['type_name']);

    if (!empty($type_name)) {
        // Check if material type already exists
        $check_stmt = $conn->prepare("SELECT id FROM material_types WHERE type_name = ?");
        $check_stmt->bind_param("s", $type_name);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            $message = "warning|Material type already exists!";
        } else {
            $stmt = $conn->prepare("INSERT INTO material_types (type_name) VALUES (?)");
            $stmt->bind_param("s", $type_name);

            if ($stmt->execute()) {
                $message = "success|Material type added successfully!";
            } else {
                $message = "error|Failed to add material type!";
            }
            $stmt->close();
        }
        $check_stmt->close();
    } else {
        $message = "warning|Please enter a material type name!";
    }
}

// Update Material Type
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_material_type'])) {
    $type_id = intval($_POST['type_id']);
    $type_name = trim($_POST['edit_type_name']);

    if ($type_id > 0 && !empty($type_name)) {
        // Check if material type already exists (excluding current one)
        $check_stmt = $conn->prepare("SELECT id FROM material_types WHERE type_name = ? AND id != ?");
        $check_stmt->bind_param("si", $type_name, $type_id);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            $message = "warning|Material type already exists!";
        } else {
            $stmt = $conn->prepare("UPDATE material_types SET type_name = ? WHERE id = ?");
            $stmt->bind_param("si", $type_name, $type_id);

            if ($stmt->execute()) {
                $message = "success|Material type updated successfully!";
            } else {
                $message = "error|Failed to update material type!";
            }
            $stmt->close();
        }
        $check_stmt->close();
    } else {
        $message = "warning|Please fill all fields!";
    }
}

// Delete Material Type
if (isset($_GET['delete_id'])) {
    $type_id = intval($_GET['delete_id']);

    // First check if this material type is being used in study_materials
    $check_stmt = $conn->prepare("SELECT id FROM study_materials WHERE material_type_id = ?");
    $check_stmt->bind_param("i", $type_id);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        $message = "error|Cannot delete! This material type is in use.";
    } else {
        $stmt = $conn->prepare("DELETE FROM material_types WHERE id = ?");
        $stmt->bind_param("i", $type_id);

        if ($stmt->execute()) {
            $message = "success|Material type deleted successfully!";
        } else {
            $message = "error|Failed to delete material type!";
        }
        $stmt->close();
    }
    $check_stmt->close();
}

// Fetch all material types
$material_types = [];
$result = $conn->query("SELECT * FROM material_types ORDER BY type_name ASC");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $material_types[] = $row;
    }
}

// Handle message from processing
if (!empty($message)) {
    list($type, $text) = explode('|', $message, 2);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Material Types - Admin Panel</title>
    <link rel="stylesheet" href="../Bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 20px;
        }
        .form-container {
            max-width: 800px;
            margin: 0 auto 30px;
            background: #fff;
            padding: 25px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            border-radius: 10px;
        }
        .table-responsive {
            overflow-x: auto;
        }
        .action-btns .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            margin: 2px;
        }
        .add-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border: 1px solid #dee2e6;
        }
        @media (max-width: 768px) {
            .form-container {
                padding: 15px;
                margin: 0 10px 20px;
            }
            .action-btns .btn {
                display: block;
                width: 100%;
                margin-bottom: 5px;
            }
        }
    </style>
</head>
<body>

<?php include 'ad.php'; ?>

<div class="container-fluid">
    <div class="form-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="text-primary"><i class="bi bi-tags"></i> Manage Material Types</h4>
        </div>

        <!-- Add Material Type Form -->
        <div class="add-form mb-4">
            <h5 class="text-primary mb-3"><i class="bi bi-plus-circle"></i> Add New Material Type</h5>
            <form method="POST" action="">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label for="type_name" class="form-label">
                            <i class="bi bi-card-text"></i> Material Type Name
                        </label>
                        <input type="text" name="type_name" class="form-control" id="type_name" 
                               placeholder="e.g. Assignment, Journal, Lecture Notes" required>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" name="add_material_type" class="btn btn-success w-100">
                            <i class="bi bi-save"></i> Add Type
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Material Types Table -->
        <div class="table-responsive">
            <table class="table table-hover table-striped">
                <thead class="table-primary">
                    <tr>
                        <th>#</th>
                        <th>Material Type</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($material_types)): ?>
                        <tr>
                            <td colspan="3" class="text-center text-muted py-4">
                                <i class="bi bi-tag" style="font-size: 2rem;"></i><br>
                                No material types found
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($material_types as $index => $type): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= htmlspecialchars($type['type_name']) ?></td>
                                <td class="action-btns">
                                    <button class="btn btn-sm btn-warning edit-btn" 
                                            data-id="<?= $type['id'] ?>"
                                            data-name="<?= htmlspecialchars($type['type_name']) ?>">
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </button>
                                    <button class="btn btn-sm btn-danger delete-btn" 
                                            data-id="<?= $type['id'] ?>"
                                            data-name="<?= htmlspecialchars($type['type_name']) ?>">
                                        <i class="bi bi-trash"></i> Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Edit Material Type Modal -->
<div class="modal fade" id="editMaterialTypeModal" tabindex="-1" aria-labelledby="editMaterialTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title" id="editMaterialTypeModalLabel">
                    <i class="bi bi-pencil-square"></i> Edit Material Type
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="type_id" id="edit_type_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_type_name" class="form-label">
                            <i class="bi bi-card-text"></i> Material Type Name
                        </label>
                        <input type="text" name="edit_type_name" class="form-control" id="edit_type_name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Close
                    </button>
                    <button type="submit" name="update_material_type" class="btn btn-success">
                        <i class="bi bi-save"></i> Update
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="../Bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Show SweetAlert message if exists
    <?php if (!empty($message)): ?>
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
        
        // Remove message from URL if page was refreshed
        if(window.history.replaceState && window.location.search.includes('message')) {
            window.history.replaceState(null, null, window.location.pathname);
        }
    <?php endif; ?>

    // Edit button click handler
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            
            document.getElementById('edit_type_id').value = id;
            document.getElementById('edit_type_name').value = name;
            
            const editModal = new bootstrap.Modal(document.getElementById('editMaterialTypeModal'));
            editModal.show();
        });
    });

    // Delete button click handler with SweetAlert confirmation
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            
            Swal.fire({
                title: 'Are you sure?',
                html: `You are about to delete the material type: <strong>${name}</strong><br>This action cannot be undone!`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel',
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading indicator
                    Swal.fire({
                        title: 'Deleting...',
                        html: 'Please wait while we delete the material type',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    // Redirect to delete action
                    window.location.href = `add_mat.php?delete_id=${id}`;
                }
            });
        });
    });
</script>
</body>
</html>