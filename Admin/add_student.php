<?php
include '../db.php';
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    // Handle file upload
    $imageName = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $fileType = $_FILES['image']['type'];
        $maxFileSize = 2 * 1024 * 1024; // 2MB
        
        if (in_array($fileType, $allowedTypes) && $_FILES['image']['size'] <= $maxFileSize) {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $imageName = uniqid() . '.' . $ext;
            $uploadPath = '../uploads/profiles/' . $imageName;
            
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                $imageName = '';
            }
        }
    }
    
    // Insert into database
    $query = "INSERT INTO users (name, email, password, image) VALUES ('$name', '$email', '$password', '$imageName')";
    
    if ($conn->query($query)) {
        $message = "success|Student registered successfully!";
        header("Location: add_student.php?message=$message");
        exit();
    } else {
        $message = "error|Error registering student: " . $conn->error;
        header("Location: add_student.php?message=$message");
        exit();
    }
}

// Handle message from other pages
if (isset($_GET['message'])) {
    list($type, $text) = explode('|', $_GET['message'], 2);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Student - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
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
        .form-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .image-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
            border: 1px dashed #dee2e6;
            border-radius: 10px;
            background-color: #f8f9fa;
            height: 100%;
        }
        .image-preview {
            width: 200px;
            height: 200px;
            object-fit: cover;
            border: 3px solid #dee2e6;
            border-radius: 5px;
            display: none;
            margin-bottom: 15px;
        }
        .upload-btn {
            position: relative;
            overflow: hidden;
            width: 100%;
            max-width: 200px;
        }
        .upload-btn input[type="file"] {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }
        .form-label {
            font-weight: 500;
        }
        .form-control, .form-select {
            border-radius: 5px;
            padding: 10px 15px;
        }
        @media (max-width: 768px) {
            .form-container {
                padding: 0 10px;
            }
            .image-preview {
                width: 150px;
                height: 150px;
            }
            .upload-btn {
                max-width: 150px;
            }
        }
        @media (max-width: 576px) {
            .image-preview {
                width: 120px;
                height: 120px;
            }
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
                                <i class="bi bi-person-plus-fill"></i> Add New Student
                            </h4>
                            <a href="manage_students.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left-circle"></i> Back to Students
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

        <div class="row">
            <div class="col-12">
                <div class="card form-container">
                    <div class="card-body">
                        <form id="studentForm" method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">
                                            <i class="bi bi-person-fill"></i> Full Name
                                        </label>
                                        <input type="text" class="form-control" id="name" name="name" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="email" class="form-label">
                                            <i class="bi bi-envelope-fill"></i> Email Address
                                        </label>
                                        <input type="email" class="form-control" id="email" name="email" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="password" class="form-label">
                                            <i class="bi bi-lock-fill"></i> Password
                                        </label>
                                        <input type="password" class="form-control" id="password" name="password" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="confirmPassword" class="form-label">
                                            <i class="bi bi-lock-fill"></i> Confirm Password
                                        </label>
                                        <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required>
                                        <div class="invalid-feedback" id="passwordError">
                                            Passwords do not match!
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="image-section">
                                        <img id="imagePreview" class="image-preview" alt="Student Image Preview">
                                        <label class="btn btn-primary upload-btn">
                                            <i class="bi bi-cloud-arrow-up-fill"></i> Choose Image
                                            <input type="file" id="imageUpload" name="image" accept="image/*">
                                        </label>
                                        <small class="text-muted mt-2">
                                            <i class="bi bi-info-circle-fill"></i> Max size: 2MB (JPEG, PNG)
                                        </small>
                                        <div class="invalid-feedback" id="imageError"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                <button type="reset" class="btn btn-outline-secondary me-md-2">
                                    <i class="bi bi-arrow-counterclockwise"></i> Reset Form
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save-fill"></i> Save Student
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Image preview functionality
    document.getElementById('imageUpload').addEventListener('change', function(e) {
        const file = e.target.files[0];
        const preview = document.getElementById('imagePreview');
        const imageError = document.getElementById('imageError');
        
        // Reset previous state
        preview.style.display = 'none';
        imageError.textContent = '';
        this.classList.remove('is-invalid');
        
        if (file) {
            // Check file type
            const allowedTypes = ['image/jpeg', 'image/png'];
            if (!allowedTypes.includes(file.type)) {
                imageError.textContent = 'Only JPEG and PNG images are allowed';
                this.classList.add('is-invalid');
                return;
            }
            
            // Check file size (2MB max)
            if (file.size > 2 * 1024 * 1024) {
                imageError.textContent = 'Image must be less than 2MB';
                this.classList.add('is-invalid');
                return;
            }
            
            // Create preview
            const reader = new FileReader();
            reader.onload = function(event) {
                preview.src = event.target.result;
                preview.style.display = 'block';
            }
            reader.readAsDataURL(file);
        }
    });

    // Password confirmation validation
    document.getElementById('studentForm').addEventListener('submit', function(e) {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        const passwordError = document.getElementById('passwordError');
        
        if (password !== confirmPassword) {
            e.preventDefault();
            document.getElementById('confirmPassword').classList.add('is-invalid');
            passwordError.style.display = 'block';
        }
    });

    // Reset password validation on input
    document.getElementById('confirmPassword').addEventListener('input', function() {
        this.classList.remove('is-invalid');
        document.getElementById('passwordError').style.display = 'none';
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