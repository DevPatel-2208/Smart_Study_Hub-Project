<?php
include '../db.php'; // database connection
$message = "";

// Add Semester
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_semester'])) {
    $semester_name = trim($_POST['semester_name']);

    if (!empty($semester_name)) {
        $stmt = $conn->prepare("INSERT INTO semesters (name) VALUES (?)");
        $stmt->bind_param("s", $semester_name);

        if ($stmt->execute()) {
            $message = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>✅ Semester added successfully!
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>';
        } else {
            $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>❌ Failed to add semester.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>';
        }
        $stmt->close();
    } else {
        $message = '<div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>⚠️ Please enter a semester name.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>';
    }
}

// Update Semester
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_semester'])) {
    $semester_id = $_POST['semester_id'];
    $semester_name = trim($_POST['edit_semester_name']);

    if (!empty($semester_name)) {
        $stmt = $conn->prepare("UPDATE semesters SET name = ? WHERE id = ?");
        $stmt->bind_param("si", $semester_name, $semester_id);

        if ($stmt->execute()) {
            $message = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>✅ Semester updated successfully!
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>';
        } else {
            $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>❌ Failed to update semester.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>';
        }
        $stmt->close();
    } else {
        $message = '<div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>⚠️ Please enter a semester name.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>';
    }
}

// Delete Semester
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM semesters WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    
    if ($stmt->execute()) {
        $message = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>✅ Semester deleted successfully!
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>';
    } else {
        $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>❌ Failed to delete semester.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>';
    }
    $stmt->close();
}

// Fetch all semesters
$semesters = [];
$result = $conn->query("SELECT * FROM semesters ORDER BY id DESC");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $semesters[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Semesters - Admin Panel</title>
  <link rel="stylesheet" href="../Bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <style>
    body {
      background-color: #f8f9fa;
    }
    .form-container {
      max-width: 800px;
      margin: 30px auto;
      background: #fff;
      padding: 30px;
      box-shadow: 0 0 15px rgba(0,0,0,0.1);
      border-radius: 12px;
    }
    .table-responsive {
      overflow-x: auto;
    }
    .action-btns .btn {
      padding: 0.25rem 0.5rem;
      font-size: 0.875rem;
    }
    @media (max-width: 768px) {
      .form-container {
        padding: 20px;
        margin: 15px;
      }
    }
  </style>
</head>
<body>

<?php include 'ad.php'; ?>

<div class="container-fluid">
  <div class="form-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="text-primary"><i class="fas fa-calendar-alt me-2"></i>Manage Semesters</h4>
      <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addSemesterModal">
        <i class="fas fa-plus me-1"></i> Add Semester
      </button>
    </div>

    <?= $message ?>

    <div class="table-responsive">
      <table class="table table-hover table-striped">
        <thead class="table-primary">
          <tr>
            <th>#</th>
            <th>Semester Name</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($semesters)): ?>
            <tr>
              <td colspan="3" class="text-center text-muted">No semesters found</td>
            </tr>
          <?php else: ?>
            <?php foreach ($semesters as $index => $semester): ?>
              <tr>
                <td><?= $index + 1 ?></td>
                <td><?= htmlspecialchars($semester['name']) ?></td>
                <td class="action-btns">
                  <button class="btn btn-sm btn-warning edit-btn" 
                          data-id="<?= $semester['id'] ?>" 
                          data-name="<?= htmlspecialchars($semester['name']) ?>">
                    <i class="fas fa-edit"></i> Edit
                  </button>
                  <a href="#" class="btn btn-sm btn-danger delete-btn" 
                     data-id="<?= $semester['id'] ?>">
                    <i class="fas fa-trash-alt"></i> Delete
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Add Semester Modal -->
<div class="modal fade" id="addSemesterModal" tabindex="-1" aria-labelledby="addSemesterModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="addSemesterModalLabel">
          <i class="fas fa-plus-circle me-2"></i>Add New Semester
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="">
        <div class="modal-body">
          <div class="mb-3">
            <label for="semester_name" class="form-label">
              <i class="fas fa-book me-1"></i>Semester Name
            </label>
            <input type="text" name="semester_name" class="form-control" id="semester_name" 
                   placeholder="e.g. Semester 1" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="fas fa-times me-1"></i> Close
          </button>
          <button type="submit" name="add_semester" class="btn btn-success">
            <i class="fas fa-save me-1"></i> Save
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Semester Modal -->
<div class="modal fade" id="editSemesterModal" tabindex="-1" aria-labelledby="editSemesterModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-warning text-white">
        <h5 class="modal-title" id="editSemesterModalLabel">
          <i class="fas fa-edit me-2"></i>Edit Semester
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="">
        <input type="hidden" name="semester_id" id="edit_semester_id">
        <div class="modal-body">
          <div class="mb-3">
            <label for="edit_semester_name" class="form-label">
              <i class="fas fa-book me-1"></i>Semester Name
            </label>
            <input type="text" name="edit_semester_name" class="form-control" id="edit_semester_name" 
                   placeholder="e.g. Semester 1" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="fas fa-times me-1"></i> Close
          </button>
          <button type="submit" name="update_semester" class="btn btn-success">
            <i class="fas fa-save me-1"></i> Update
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="../Bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  // Edit button click handler
  document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      const id = this.getAttribute('data-id');
      const name = this.getAttribute('data-name');
      
      document.getElementById('edit_semester_id').value = id;
      document.getElementById('edit_semester_name').value = name;
      
      const editModal = new bootstrap.Modal(document.getElementById('editSemesterModal'));
      editModal.show();
    });
  });

  // Delete button click handler
  document.querySelectorAll('.delete-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      const id = this.getAttribute('data-id');
      
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
          window.location.href = `?delete_id=${id}`;
        }
      });
    });
  });

  // Auto-close alerts after 5 seconds
  setTimeout(() => {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
      const bsAlert = new bootstrap.Alert(alert);
      bsAlert.close();
    });
  }, 5000);
</script>
</body>
</html>