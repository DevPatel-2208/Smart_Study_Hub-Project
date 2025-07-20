<?php
include '../db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $imageName = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $fileType = $_FILES['image']['type'];
        $maxFileSize = 2 * 1024 * 1024;

        if (in_array($fileType, $allowedTypes) && $_FILES['image']['size'] <= $maxFileSize) {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $imageName = uniqid() . '.' . $ext;
            $uploadPath = '../uploads/profiles/' . $imageName;
            move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath);
        }
    }

    $query = "INSERT INTO users (name, email, password, image) VALUES ('$name', '$email', '$password', '$imageName')";
    if ($conn->query($query)) {
        $message = "success|Registration successful!";
        header("Location: login.php?message=$message");
        exit();
    } else {
        $message = "error|Registration failed: " . $conn->error;
        header("Location: register.php?message=$message");
        exit();
    }
}
if (isset($_GET['message'])) {
    list($type, $text) = explode('|', $_GET['message'], 2);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Registration</title><link rel="icon" type="image/x-icon" href="../uploads/unnamed.png">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    :root {
      --primary: #6a11cb;
      --secondary: #2575fc;
      --gradient: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    }
    
    body {
      background: linear-gradient(to right, #f5f7fa, #e4e8f0);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Poppins', sans-serif;
    }
    
    .card {
      border-radius: 15px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
      border: none;
      overflow: hidden;
    }
    
    .card-header {
      background: var(--gradient);
      color: white;
      padding: 1.5rem;
      text-align: center;
      position: relative;
      overflow: hidden;
    }
    
    .card-header::before {
      content: "";
      position: absolute;
      top: -50%;
      left: -50%;
      width: 200%;
      height: 200%;
      background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 70%);
      transform: rotate(30deg);
    }
    
    .card-title {
      font-weight: 600;
      margin-bottom: 0;
    }
    
    .form-control {
      border-radius: 8px;
      padding: 12px 15px;
      border: 1px solid #e0e0e0;
      transition: all 0.3s;
    }
    
    .form-control:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 0.25rem rgba(106, 17, 203, 0.25);
    }
    
    .form-label {
      font-weight: 500;
      color: #495057;
    }
    
    .image-container {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      height: 100%;
      padding: 20px;
    }
    
    .image-preview {
      width: 180px;
      height: 180px;
      object-fit: cover;
      border-radius: 50%;
      border: 3px solid #e0e0e0;
      margin-bottom: 20px;
      display: none;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .btn-upload {
      position: relative;
      overflow: hidden;
      background: white;
      border: 2px dashed #dee2e6;
      border-radius: 8px;
      padding: 40px 20px;
      width: 100%;
      text-align: center;
      transition: all 0.3s;
    }
    
    .btn-upload:hover {
      border-color: var(--primary);
      background: rgba(106, 17, 203, 0.05);
    }
    
    .btn-upload i {
      font-size: 2rem;
      color: var(--primary);
      margin-bottom: 10px;
    }
    
    .btn-upload span {
      display: block;
      font-weight: 500;
      color: var(--primary);
    }
    
    .btn-upload input[type="file"] {
      position: absolute;
      top: 0;
      left: 0;
      opacity: 0;
      width: 100%;
      height: 100%;
      cursor: pointer;
    }
    
    .btn-primary {
      background: var(--gradient);
      border: none;
      padding: 10px 20px;
      font-weight: 500;
      transition: all 0.3s;
    }
    
    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(106, 17, 203, 0.3);
    }
    
    .invalid-feedback {
      color: #dc3545;
      font-size: 0.85rem;
    }
    
    .alert {
      border-left: 4px solid;
    }
    
    .alert-success {
      border-left-color: #28a745;
    }
    
    .alert-danger {
      border-left-color: #dc3545;
    }
    
    @media (max-width: 768px) {
      .image-container {
        margin-top: 20px;
        padding: 0;
      }
      
      .image-preview {
        width: 120px;
        height: 120px;
      }
    }
  </style>
</head>
<body>
<div class="container">
  <div class="col-lg-10 col-xl-8 mx-auto">
    <div class="card">
      <div class="card-header">
        <h3 class="card-title"><i class="bi bi-person-plus-fill me-2"></i>Create Your Account</h3>
        <p class="mb-0 mt-2">Join our community today</p>
      </div>
      <div class="card-body p-4 p-md-5">
        <?php if (isset($type)): ?>
          <div class="alert alert-<?= $type === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show d-flex align-items-center">
            <i class="bi <?= $type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?> me-2"></i>
            <div><?= htmlspecialchars($text) ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data" id="registerForm">
          <div class="row">
            <div class="col-md-7">
              <div class="mb-4">
                <label class="form-label" for="name"><i class="bi bi-person-fill me-2"></i>Full Name</label>
                <input type="text" class="form-control" id="name" name="name" placeholder="Enter your full name" required>
              </div>
              <div class="mb-4">
                <label class="form-label" for="email"><i class="bi bi-envelope-fill me-2"></i>Email Address</label>
                <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
              </div>
              <div class="mb-4">
                <label class="form-label" for="password"><i class="bi bi-lock-fill me-2"></i>Password</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Create a password" required>
              </div>
              <div class="mb-4">
                <label class="form-label" for="confirmPassword"><i class="bi bi-lock-fill me-2"></i>Confirm Password</label>
                <input type="password" class="form-control" id="confirmPassword" placeholder="Confirm your password" required>
                <div class="invalid-feedback">Passwords do not match!</div>
              </div>
            </div>
            <div class="col-md-5">
              <div class="image-container">
                <img id="imagePreview" class="image-preview" alt="Profile Preview">
                <label class="btn-upload">
                  <input type="file" name="image" id="imageInput" accept="image/*">
                  <i class="bi bi-cloud-arrow-up"></i>
                  <span>Upload Profile Picture</span>
                  <small class="text-muted">JPG, PNG (Max 2MB)</small>
                </label>
                <div class="invalid-feedback mt-2" id="imageError"></div>
              </div>
            </div>
          </div>
          <div class="d-flex justify-content-end gap-3 mt-4">
             <a href="login.php"class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise me-2"></i>Already Have An Account?</a>
           
            <button type="reset" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise me-2"></i>Reset</button>
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-2"></i>Register</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const imageInput = document.getElementById('imageInput');
  const imagePreview = document.getElementById('imagePreview');
  const imageError = document.getElementById('imageError');

  imageInput.addEventListener('change', function () {
    const file = this.files[0];
    imageError.textContent = '';
    this.classList.remove('is-invalid');

    if (file) {
      const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
      if (!validTypes.includes(file.type)) {
        imageError.textContent = 'Only JPG, PNG and GIF allowed';
        this.classList.add('is-invalid');
        return;
      }
      if (file.size > 2 * 1024 * 1024) {
        imageError.textContent = 'File must be less than 2MB';
        this.classList.add('is-invalid');
        return;
      }
      const reader = new FileReader();
      reader.onload = function (e) {
        imagePreview.src = e.target.result;
        imagePreview.style.display = 'block';
      };
      reader.readAsDataURL(file);
    }
  });

  const form = document.getElementById('registerForm');
  form.addEventListener('submit', function (e) {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    if (password !== confirmPassword) {
      e.preventDefault();
      const confirmInput = document.getElementById('confirmPassword');
      confirmInput.classList.add('is-invalid');
    }
  });

  document.getElementById('confirmPassword').addEventListener('input', function () {
    this.classList.remove('is-invalid');
  });
</script>
</body>
</html>