<?php
include '../db.php';
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$message = "";

// Maximum file size (200MB in bytes)
$maxFileSize = 200 * 1024 * 1024;

// Allowed file types
$allowedExtensions = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png'];

// Fetch data for dropdowns
$semesters = $conn->query("SELECT id, name FROM semesters ORDER BY name ASC");
$subjects = $conn->query("SELECT id, name, semester_id FROM subjects ORDER BY name ASC");
$material_types = $conn->query("SELECT id, type_name FROM material_types ORDER BY type_name ASC");
$users = $conn->query("SELECT id, name FROM users ORDER BY name ASC"); // Added users query

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_material'])) {
    $title = trim($_POST['title']);
    $semester_id = intval($_POST['semester_id']);
    $subject_id = intval($_POST['subject_id']);
    $material_type_id = intval($_POST['material_type_id']);
    $uploaded_by = $_SESSION['admin_id'];
    $visible_to_user_id = !empty($_POST['visible_to_user_id']) ? intval($_POST['visible_to_user_id']) : NULL; // New field

    // Validate inputs
    if (empty($title) || $semester_id <= 0 || $subject_id <= 0 || $material_type_id <= 0) {
        $message = "warning|Please fill all required fields!";
    } elseif (empty($_FILES['study_file']['name'])) {
        $message = "warning|Please select a file to upload!";
    } else {
        // File upload handling
        $file = $_FILES['study_file'];
        $fileName = $file['name'];
        $fileTmpName = $file['tmp_name'];
        $fileSize = $file['size'];
        $fileError = $file['error'];
        
        // Get file extension
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        // Validate file
        if (!in_array($fileExt, $allowedExtensions)) {
            $message = "error|File type not allowed! Allowed types: " . implode(', ', $allowedExtensions);
        } elseif ($fileSize > $maxFileSize) {
            $message = "error|File is too large! Maximum size is 200MB.";
        } elseif ($fileError !== 0) {
            $message = "error|Error uploading file!";
        } else {
            // Generate unique filename
            $newFileName = uniqid('', true) . '.' . $fileExt;
            $uploadPath = '../uploads/' . $newFileName;
            
            // Move uploaded file
            if (move_uploaded_file($fileTmpName, $uploadPath)) {
                // Insert into database with visible_to_user_id
                $stmt = $conn->prepare("INSERT INTO study_materials (title, semester_id, subject_id, material_type_id, file_name, uploaded_by, visible_to_user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("siiisii", $title, $semester_id, $subject_id, $material_type_id, $newFileName, $uploaded_by, $visible_to_user_id);
                
                if ($stmt->execute()) {
                    $message = "success|Study material uploaded successfully!";
                } else {
                    // Delete the uploaded file if DB insert fails
                    unlink($uploadPath);
                    $message = "error|Failed to save material details!";
                }
                $stmt->close();
            } else {
                $message = "error|Failed to upload file!";
            }
        }
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
    <title>Add Study Material - Admin Panel</title>
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
        .file-upload-wrapper {
            position: relative;
            margin-bottom: 1rem;
        }
        .file-upload-input {
            width: 100%;
            height: calc(2.25rem + 2px);
            margin: 0;
            opacity: 0;
        }
        .file-upload-label {
            position: absolute;
            top: 0;
            right: 0;
            left: 0;
            z-index: 1;
            height: calc(2.25rem + 2px);
            padding: .375rem .75rem;
            line-height: 1.5;
            color: #495057;
            background-color: #fff;
            border: 1px solid #ced4da;
            border-radius: .25rem;
        }
        .file-upload-button {
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            z-index: 2;
            height: calc(2.25rem + 2px);
            padding: .375rem .75rem;
            line-height: 1.5;
            color: #fff;
            background-color: #007bff;
            border: 1px solid #007bff;
            border-radius: 0 .25rem .25rem 0;
        }
        .preview-container {
            margin-top: 20px;
            border: 1px dashed #ccc;
            padding: 15px;
            border-radius: 5px;
            display: none;
        }
        .preview-image {
            max-width: 100%;
            max-height: 300px;
            margin-bottom: 10px;
        }
        .preview-document {
            width: 100%;
            height: 500px;
            border: none;
        }
        @media (max-width: 768px) {
            .form-container {
                padding: 15px;
                margin: 0 10px 20px;
            }
            .preview-document {
                height: 300px;
            }
            .col-md-6, .col-md-4, .col-md-3 {
                margin-bottom: 15px;
            }
        }
    </style>
</head>
<body>

<?php include 'ad.php'; ?>

<div class="container-fluid">
    <div class="form-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="text-primary"><i class="bi bi-journal-plus"></i> Add Study Material</h4>
        </div>

        <?php if (isset($type)): ?>
            <div class="alert alert-<?= $type === 'success' ? 'success' : ($type === 'error' ? 'danger' : 'warning') ?> alert-dismissible fade show">
                <i class="bi bi-<?= $type === 'success' ? 'check-circle' : ($type === 'error' ? 'exclamation-triangle' : 'info-circle') ?>"></i>
                <?= htmlspecialchars($text) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data">
            <div class="row g-3">
                <div class="col-md-12">
                    <label for="title" class="form-label">
                        <i class="bi bi-card-heading"></i> Material Title
                    </label>
                    <input type="text" name="title" class="form-control" id="title" required>
                </div>

                <div class="col-md-4">
                    <label for="semester_id" class="form-label">
                        <i class="bi bi-journal-text"></i> Semester
                    </label>
                    <select name="semester_id" id="semester_id" class="form-select" required>
                        <option value="">-- Select Semester --</option>
                        <?php while($semester = $semesters->fetch_assoc()): ?>
                            <option value="<?= $semester['id'] ?>"><?= htmlspecialchars($semester['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="subject_id" class="form-label">
                        <i class="bi bi-book"></i> Subject
                    </label>
                    <select name="subject_id" id="subject_id" class="form-select" required>
                        <option value="">-- Select Subject --</option>
                        <?php while($subject = $subjects->fetch_assoc()): ?>
                            <option value="<?= $subject['id'] ?>" data-semester="<?= $subject['semester_id'] ?>">
                                <?= htmlspecialchars($subject['name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="material_type_id" class="form-label">
                        <i class="bi bi-tag"></i> Material Type
                    </label>
                    <select name="material_type_id" id="material_type_id" class="form-select" required>
                        <option value="">-- Select Type --</option>
                        <?php while($type = $material_types->fetch_assoc()): ?>
                            <option value="<?= $type['id'] ?>"><?= htmlspecialchars($type['type_name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- New User Visibility Dropdown -->
                <div class="col-md-6">
                    <label for="visible_to_user_id" class="form-label">
                        <i class="bi bi-person"></i> Visible To (Optional)
                    </label>
                    <select name="visible_to_user_id" id="visible_to_user_id" class="form-select">
                        <option value="">All Users (Default)</option>
                        <?php while($user = $users->fetch_assoc()): ?>
                            <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                    <small class="text-muted">Leave blank to make visible to all users</small>
                </div>

                <div class="col-md-6">
                    <label class="form-label">
                        <i class="bi bi-file-earmark-arrow-up"></i> Study File (Max 200MB)
                    </label>
                    <div class="file-upload-wrapper">
                        <input type="file" name="study_file" id="study_file" class="file-upload-input" required
                               accept=".pdf,.doc,.docx,.ppt,.pptx,.jpg,.jpeg,.png">
                        <label for="study_file" class="file-upload-label" id="file-label">
                            <span id="file-name">Choose file...</span>
                        </label>
                        <button type="button" class="file-upload-button">
                            <i class="bi bi-folder2-open"></i> Browse
                        </button>
                    </div>
                    <small class="text-muted">Allowed file types: PDF, DOC, DOCX, PPT, PPTX, JPG, JPEG, PNG</small>
                </div>

                <!-- Preview Container -->
                <div class="col-12">
                    <div class="preview-container mt-3" id="previewContainer">
                        <h5><i class="bi bi-eye"></i> File Preview</h5>
                        <div id="imagePreview">
                            <img src="" class="preview-image img-fluid" id="previewImage">
                        </div>
                        <div id="pdfPreview">
                            <iframe src="" class="preview-document" id="previewPdf"></iframe>
                        </div>
                        <div id="docPreview" class="text-center py-4">
                            <i class="bi bi-file-earmark-text" style="font-size: 5rem;"></i>
                            <p class="mt-2">Document preview not available</p>
                        </div>
                        <div id="pptPreview" class="text-center py-4">
                            <i class="bi bi-file-earmark-ppt" style="font-size: 5rem;"></i>
                            <p class="mt-2">Presentation preview not available</p>
                        </div>
                    </div>
                </div>

                <div class="col-12 mt-3">
                    <button type="submit" name="add_material" class="btn btn-success w-100">
                        <i class="bi bi-upload"></i> Upload Material
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="../Bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Show selected file name and preview
    document.getElementById('study_file').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;
        
        const fileName = file.name;
        document.getElementById('file-name').textContent = fileName;
        
        // Show preview container
        const previewContainer = document.getElementById('previewContainer');
        previewContainer.style.display = 'block';
        
        // Hide all preview sections first
        document.getElementById('imagePreview').style.display = 'none';
        document.getElementById('pdfPreview').style.display = 'none';
        document.getElementById('docPreview').style.display = 'none';
        document.getElementById('pptPreview').style.display = 'none';
        
        // Get file extension
        const fileExt = fileName.split('.').pop().toLowerCase();
        
        // Show appropriate preview based on file type
        if (['jpg', 'jpeg', 'png'].includes(fileExt)) {
            // Image preview
            const previewImage = document.getElementById('previewImage');
            const reader = new FileReader();
            
            reader.onload = function(e) {
                previewImage.src = e.target.result;
                document.getElementById('imagePreview').style.display = 'block';
            }
            
            reader.readAsDataURL(file);
        } else if (fileExt === 'pdf') {
            // PDF preview
            const previewPdf = document.getElementById('previewPdf');
            const reader = new FileReader();
            
            reader.onload = function(e) {
                previewPdf.src = e.target.result;
                document.getElementById('pdfPreview').style.display = 'block';
            }
            
            reader.readAsDataURL(file);
        } else if (['doc', 'docx'].includes(fileExt)) {
            // Document preview (can't show actual content, just icon)
            document.getElementById('docPreview').style.display = 'block';
        } else if (['ppt', 'pptx'].includes(fileExt)) {
            // Presentation preview (can't show actual content, just icon)
            document.getElementById('pptPreview').style.display = 'block';
        }
    });

    // Filter subjects based on selected semester
    document.getElementById('semester_id').addEventListener('change', function() {
        const semesterId = this.value;
        const subjectOptions = document.querySelectorAll('#subject_id option');
        
        subjectOptions.forEach(option => {
            if (option.value === "") {
                option.style.display = 'block';
            } else {
                if (option.dataset.semester === semesterId) {
                    option.style.display = 'block';
                } else {
                    option.style.display = 'none';
                }
            }
        });
        
        // Reset subject selection
        document.getElementById('subject_id').value = '';
    });

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
    <?php endif; ?>
</script>
</body>
</html>