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

// Fetch all data for dropdowns
$semesters = $conn->query("SELECT id, name FROM semesters ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
$subjects = $conn->query("SELECT id, name, semester_id FROM subjects ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
$material_types = $conn->query("SELECT id, type_name FROM material_types ORDER BY type_name ASC")->fetch_all(MYSQLI_ASSOC);

// Get material details if ID is provided
$material = null;
if (isset($_GET['id'])) {
    $material_id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM study_materials WHERE id = ?");
    $stmt->bind_param("i", $material_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $material = $result->fetch_assoc();
    $stmt->close();
    
    // If material not found, redirect with error
    if (!$material) {
        $message = "error|Study material not found!";
        header("Location: manage_materials.php?message=" . urlencode($message));
        exit();
    }
}

// Handle update form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_material'])) {
    $material_id = intval($_POST['material_id']);
    $title = trim($_POST['title']);
    $semester_id = intval($_POST['semester_id']);
    $subject_id = intval($_POST['subject_id']);
    $material_type_id = intval($_POST['material_type_id']);
    
    // Validate inputs
    if (empty($title) || $semester_id <= 0 || $subject_id <= 0 || $material_type_id <= 0) {
        $message = "warning|Please fill all required fields!";
    } else {
        // Check if new file is uploaded
        if (!empty($_FILES['study_file']['name'])) {
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
                    // Delete old file
                    if (!empty($material['file_name'])) {
                        $oldFilePath = '../uploads/' . $material['file_name'];
                        if (file_exists($oldFilePath)) {
                            unlink($oldFilePath);
                        }
                    }
                    
                    // Update with new file
                    $stmt = $conn->prepare("UPDATE study_materials SET title = ?, semester_id = ?, subject_id = ?, material_type_id = ?, file_name = ? WHERE id = ?");
                    $stmt->bind_param("siiisi", $title, $semester_id, $subject_id, $material_type_id, $newFileName, $material_id);
                } else {
                    $message = "error|Failed to upload file!";
                }
            }
        } else {
            // Update without changing file
            $stmt = $conn->prepare("UPDATE study_materials SET title = ?, semester_id = ?, subject_id = ?, material_type_id = ? WHERE id = ?");
            $stmt->bind_param("siiii", $title, $semester_id, $subject_id, $material_type_id, $material_id);
        }
        
        if (empty($message)) {
            if ($stmt->execute()) {
                $message = "success|Study material updated successfully!";
                // Refresh material data
                $stmt = $conn->prepare("SELECT * FROM study_materials WHERE id = ?");
                $stmt->bind_param("i", $material_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $material = $result->fetch_assoc();
                $stmt->close();
            } else {
                $message = "error|Failed to update material details!";
            }
        }
    }
}

// Handle delete action
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    // Get file name first
    $stmt = $conn->prepare("SELECT file_name FROM study_materials WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $fileToDelete = $result->fetch_assoc();
    $stmt->close();
    
    // Delete from database
    $stmt = $conn->prepare("DELETE FROM study_materials WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    
    if ($stmt->execute()) {
        // Delete the file
        if (!empty($fileToDelete['file_name'])) {
            $filePath = '../uploads/' . $fileToDelete['file_name'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
        $message = "success|Study material deleted successfully!";
        header("Location: manage_materials.php?message=" . urlencode($message));
        exit();
    } else {
        $message = "error|Failed to delete material!";
    }
    $stmt->close();
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
    <title>Update Study Material - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
        .current-file {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        @media (max-width: 768px) {
            .form-container {
                padding: 15px;
                margin: 0 10px 20px;
            }
            .preview-document {
                height: 300px;
            }
        }
    </style>
</head>
<body>

<?php include 'ad.php'; ?>

<div class="container-fluid">
    <div class="form-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="text-primary"><i class="bi bi-journal-plus"></i> Update Study Material</h4>
            <?php if (isset($material)): ?>
                <button class="btn btn-danger delete-btn" data-id="<?= $material['id'] ?>">
                    <i class="bi bi-trash"></i> Delete
                </button>
            <?php endif; ?>
        </div>

        <?php if (isset($type)): ?>
            <div class="alert alert-<?= $type === 'success' ? 'success' : ($type === 'error' ? 'danger' : 'warning') ?> alert-dismissible fade show">
                <i class="bi bi-<?= $type === 'success' ? 'check-circle' : ($type === 'error' ? 'exclamation-triangle' : 'info-circle') ?>"></i>
                <?= htmlspecialchars($text) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($material)): ?>
        <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="material_id" value="<?= $material['id'] ?>">
            <div class="row g-3">
                <div class="col-md-12">
                    <label for="title" class="form-label">
                        <i class="bi bi-card-heading"></i> Material Title
                    </label>
                    <input type="text" name="title" class="form-control" id="title" 
                           value="<?= htmlspecialchars($material['title']) ?>" required>
                </div>

                <div class="col-md-4">
                    <label for="semester_id" class="form-label">
                        <i class="bi bi-journal-text"></i> Semester
                    </label>
                    <select name="semester_id" id="semester_id" class="form-select" required>
                        <option value="">-- Select Semester --</option>
                        <?php foreach($semesters as $semester): ?>
                            <option value="<?= $semester['id'] ?>" <?= $semester['id'] == $material['semester_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($semester['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="subject_id" class="form-label">
                        <i class="bi bi-book"></i> Subject
                    </label>
                    <select name="subject_id" id="subject_id" class="form-select" required>
                        <option value="">-- Select Subject --</option>
                        <?php foreach($subjects as $subject): ?>
                            <option value="<?= $subject['id'] ?>" 
                                    data-semester="<?= $subject['semester_id'] ?>"
                                    <?= $subject['id'] == $material['subject_id'] ? 'selected' : '' ?>
                                    <?= $subject['semester_id'] == $material['semester_id'] ? '' : 'style="display:none"' ?>>
                                <?= htmlspecialchars($subject['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="material_type_id" class="form-label">
                        <i class="bi bi-tag"></i> Material Type
                    </label>
                    <select name="material_type_id" id="material_type_id" class="form-select" required>
                        <option value="">-- Select Type --</option>
                        <?php foreach($material_types as $type): ?>
                            <option value="<?= $type['id'] ?>" <?= $type['id'] == $material['material_type_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($type['type_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-12">
                    <div class="current-file">
                        <h6><i class="bi bi-file-earmark"></i> Current File</h6>
                        <p><?= htmlspecialchars($material['file_name']) ?></p>
                        <?php
                        $fileExt = pathinfo($material['file_name'], PATHINFO_EXTENSION);
                        $filePath = '../uploads/' . $material['file_name'];
                        if (file_exists($filePath)):
                        ?>
                            <?php if (in_array($fileExt, ['jpg', 'jpeg', 'png'])): ?>
                                <img src="<?= $filePath ?>" class="preview-image img-fluid">
                            <?php elseif ($fileExt === 'pdf'): ?>
                                <iframe src="<?= $filePath ?>" class="preview-document"></iframe>
                            <?php elseif (in_array($fileExt, ['doc', 'docx'])): ?>
                                <div class="text-center py-4">
                                    <i class="bi bi-file-earmark-text" style="font-size: 5rem;"></i>
                                    <p class="mt-2">Document preview</p>
                                </div>
                            <?php elseif (in_array($fileExt, ['ppt', 'pptx'])): ?>
                                <div class="text-center py-4">
                                    <i class="bi bi-file-earmark-ppt" style="font-size: 5rem;"></i>
                                    <p class="mt-2">Presentation preview</p>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="alert alert-warning">File not found on server</div>
                        <?php endif; ?>
                    </div>

                    <label class="form-label">
                        <i class="bi bi-file-earmark-arrow-up"></i> Update File (Max 200MB) - Leave blank to keep current file
                    </label>
                    <div class="file-upload-wrapper">
                        <input type="file" name="study_file" id="study_file" class="file-upload-input"
                               accept=".pdf,.doc,.docx,.ppt,.pptx,.jpg,.jpeg,.png">
                        <label for="study_file" class="file-upload-label" id="file-label">
                            <span id="file-name">Choose new file...</span>
                        </label>
                        <button type="button" class="file-upload-button">
                            <i class="bi bi-folder2-open"></i> Browse
                        </button>
                    </div>
                    <small class="text-muted">Allowed file types: PDF, DOC, DOCX, PPT, PPTX, JPG, JPEG, PNG</small>
                    
                    <!-- New File Preview Container -->
                    <div class="preview-container mt-3" id="previewContainer" style="display: none;">
                        <h5><i class="bi bi-eye"></i> New File Preview</h5>
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
                    <button type="submit" name="update_material" class="btn btn-primary">
                        <i class="bi bi-save"></i> Update Material
                    </button>
                    <a href="manage_materials.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>
        </form>
        <?php else: ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle"></i> Material not found!
            </div>
            <a href="manage_materials.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to List
            </a>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
        
        // Reset subject selection if it doesn't belong to selected semester
        const selectedOption = document.querySelector('#subject_id option:checked');
        if (selectedOption && selectedOption.dataset.semester !== semesterId && selectedOption.value !== "") {
            document.getElementById('subject_id').value = '';
        }
    });

    // Delete button click handler
    document.querySelector('.delete-btn')?.addEventListener('click', function() {
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