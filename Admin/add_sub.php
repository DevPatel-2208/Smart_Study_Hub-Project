<?php
include '../db.php';
$message = "";

// Fetch semesters
$semesters = $conn->query("SELECT id, name FROM semesters ORDER BY name ASC");
$semesters_for_select = $conn->query("SELECT id, name FROM semesters ORDER BY name ASC"); // For select dropdown

// Fetch subjects with semester names
$subjects = $conn->query("
    SELECT s.id, s.name as subject_name, sem.name as semester_name 
    FROM subjects s
    JOIN semesters sem ON s.semester_id = sem.id
    ORDER BY sem.name, s.name
");

// Add Subject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_subject'])) {
    $semester_id = intval($_POST['semester_id']);
    $subject_name = trim($_POST['subject_name']);

    if ($semester_id > 0 && !empty($subject_name)) {
        $stmt = $conn->prepare("INSERT INTO subjects (semester_id, name) VALUES (?, ?)");
        $stmt->bind_param("is", $semester_id, $subject_name);

        if ($stmt->execute()) {
            $message = 'success|Subject added successfully!';
        } else {
            $message = 'error|Failed to add subject!';
        }
        $stmt->close();
    } else {
        $message = 'warning|Please select semester and enter subject name';
    }
    header("Location: ".$_SERVER['PHP_SELF']."?message=".urlencode($message));
    exit();
}

// Update Subject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_subject'])) {
    $subject_id = intval($_POST['subject_id']);
    $semester_id = intval($_POST['edit_semester_id']);
    $subject_name = trim($_POST['edit_subject_name']);

    if ($subject_id > 0 && $semester_id > 0 && !empty($subject_name)) {
        $stmt = $conn->prepare("UPDATE subjects SET semester_id = ?, name = ? WHERE id = ?");
        $stmt->bind_param("isi", $semester_id, $subject_name, $subject_id);

        if ($stmt->execute()) {
            $message = 'success|Subject updated successfully!';
        } else {
            $message = 'error|Failed to update subject!';
        }
        $stmt->close();
    } else {
        $message = 'warning|Please fill all fields';
    }
    header("Location: ".$_SERVER['PHP_SELF']."?message=".urlencode($message));
    exit();
}

// Delete Subject
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    $stmt = $conn->prepare("DELETE FROM subjects WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    
    if ($stmt->execute()) {
        $message = 'success|Subject deleted successfully!';
    } else {
        $message = 'error|Failed to delete subject!';
    }
    $stmt->close();
    header("Location: ".$_SERVER['PHP_SELF']."?message=".urlencode($message));
    exit();
}

// Show message if exists
if (isset($_GET['message'])) {
    list($type, $text) = explode('|', $_GET['message'], 2);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Subjects - Admin Panel</title>
  <link rel="stylesheet" href="../Bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <style>
    body {
      background-color: #f8f9fa;
      padding-top: 20px;
    }
    .form-container {
      max-width: 900px;
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
      <h4 class="text-primary"><i class="bi bi-journal-bookmark"></i> Manage Subjects</h4>
    </div>

    <?php if (isset($type)): ?>
      <div class="alert alert-<?= $type === 'success' ? 'success' : ($type === 'error' ? 'danger' : 'warning') ?> alert-dismissible fade show">
        <i class="bi bi-<?= $type === 'success' ? 'check-circle' : ($type === 'error' ? 'exclamation-triangle' : 'info-circle') ?>"></i>
        <?= htmlspecialchars($text) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <!-- Add Subject Form (now directly on page, not in modal) -->
    <div class="add-form mb-4">
      <h5 class="text-primary mb-3"><i class="bi bi-bookmark-plus"></i> Add New Subject</h5>
      <form method="POST" action="">
        <div class="row g-3">
          <div class="col-md-6">
            <label for="semester_id" class="form-label">
              <i class="bi bi-journal-text"></i> Select Semester
            </label>
            <select name="semester_id" id="semester_id" class="form-select" required>
              <option value="">-- Select Semester --</option>
              <?php while($row = $semesters_for_select->fetch_assoc()) { ?>
                <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></option>
              <?php } ?>
            </select>
          </div>
          <div class="col-md-6">
            <label for="subject_name" class="form-label">
              <i class="bi bi-book"></i> Subject Name
            </label>
            <div class="input-group">
              <input type="text" name="subject_name" class="form-control" id="subject_name" 
                     placeholder="e.g. Data Structures" required>
              <button type="submit" name="add_subject" class="btn btn-success">
                <i class="bi bi-save"></i> Add
              </button>
            </div>
          </div>
        </div>
      </form>
    </div>

    <div class="table-responsive">
      <table class="table table-hover table-striped">
        <thead class="table-primary">
          <tr>
            <th>#</th>
            <th>Subject Name</th>
            <th>Semester</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($subjects->num_rows === 0): ?>
            <tr>
              <td colspan="4" class="text-center text-muted py-4">
                <i class="bi bi-journal-x" style="font-size: 2rem;"></i><br>
                No subjects found
              </td>
            </tr>
          <?php else: ?>
            <?php $counter = 1; ?>
            <?php while($subject = $subjects->fetch_assoc()): ?>
              <tr>
                <td><?= $counter++ ?></td>
                <td><?= htmlspecialchars($subject['subject_name']) ?></td>
                <td><?= htmlspecialchars($subject['semester_name']) ?></td>
                <td class="action-btns">
                  <button class="btn btn-sm btn-warning edit-btn" 
                          data-id="<?= $subject['id'] ?>"
                          data-name="<?= htmlspecialchars($subject['subject_name']) ?>"
                          data-semester="<?= $subject['semester_name'] ?>">
                    <i class="bi bi-pencil-square"></i> Edit
                  </button>
                  <a href="#" class="btn btn-sm btn-danger delete-btn" 
                     data-id="<?= $subject['id'] ?>"
                     data-name="<?= htmlspecialchars($subject['subject_name']) ?>">
                    <i class="bi bi-trash"></i> Delete
                  </a>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Edit Subject Modal (only modal remaining) -->
<div class="modal fade" id="editSubjectModal" tabindex="-1" aria-labelledby="editSubjectModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-warning text-white">
        <h5 class="modal-title" id="editSubjectModalLabel">
          <i class="bi bi-pencil-square"></i> Edit Subject
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="">
        <input type="hidden" name="subject_id" id="edit_subject_id">
        <div class="modal-body">
          <div class="mb-3">
            <label for="edit_semester_id" class="form-label">
              <i class="bi bi-journal-text"></i> Select Semester
            </label>
            <select name="edit_semester_id" id="edit_semester_id" class="form-select" required>
              <option value="">-- Select Semester --</option>
              <?php 
              $semesters_for_edit = $conn->query("SELECT id, name FROM semesters ORDER BY name ASC");
              while($row = $semesters_for_edit->fetch_assoc()) { ?>
                <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></option>
              <?php } ?>
            </select>
          </div>
          <div class="mb-3">
            <label for="edit_subject_name" class="form-label">
              <i class="bi bi-book"></i> Subject Name
            </label>
            <input type="text" name="edit_subject_name" class="form-control" id="edit_subject_name" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="bi bi-x-circle"></i> Close
          </button>
          <button type="submit" name="update_subject" class="btn btn-success">
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
  // Edit button click handler
  document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      const id = this.getAttribute('data-id');
      const name = this.getAttribute('data-name');
      const semester = this.getAttribute('data-semester');
      
      document.getElementById('edit_subject_id').value = id;
      document.getElementById('edit_subject_name').value = name;
      
      // Set the selected semester
      const semesterSelect = document.getElementById('edit_semester_id');
      for (let i = 0; i < semesterSelect.options.length; i++) {
        if (semesterSelect.options[i].text === semester) {
          semesterSelect.selectedIndex = i;
          break;
        }
      }
      
      const editModal = new bootstrap.Modal(document.getElementById('editSubjectModal'));
      editModal.show();
    });
  });

  // Delete button click handler
  document.querySelectorAll('.delete-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      const id = this.getAttribute('data-id');
      const name = this.getAttribute('data-name');
      
      Swal.fire({
        title: 'Are you sure?',
        html: `You are about to delete the subject: <strong>${name}</strong><br>This action cannot be undone!`,
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