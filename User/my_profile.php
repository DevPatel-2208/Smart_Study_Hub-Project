<?php
// profile.php
session_start();
include '../db.php';
include 'activity_helper.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
$userId = $_SESSION['user']['id'];

$message = '';

// Fetch user data with last_login
$userQuery = $conn->prepare("SELECT id, name, email, image, last_login FROM users WHERE id = ?");
$userQuery->bind_param("i", $userId);
$userQuery->execute();
$userResult = $userQuery->get_result();
$user = $userResult->fetch_assoc();

// ✅ Get actual last login (excluding current session)
$lastLoginStmt = $conn->prepare("
  SELECT activity_time 
  FROM user_activities 
  WHERE user_id = ? AND activity_type = 'login' 
  ORDER BY activity_time DESC 
  LIMIT 2
");
$lastLoginStmt->bind_param("i", $userId);
$lastLoginStmt->execute();
$lastLoginResult = $lastLoginStmt->get_result();

$loginTimestamps = [];
while ($row = $lastLoginResult->fetch_assoc()) {
    $loginTimestamps[] = $row['activity_time'];
}

// Second = actual last login before current one
$actualLastLogin = isset($loginTimestamps[1]) ? $loginTimestamps[1] : null;


// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $updatePassword = isset($_POST['update_password']) && $_POST['update_password'] == '1';
    $currentPassword = $updatePassword ? $_POST['current_password'] : '';
    $newPassword = $updatePassword ? $_POST['new_password'] : '';
    $confirmPassword = $updatePassword ? $_POST['confirm_password'] : '';
    
    // Validate inputs
    if (empty($name) || empty($email)) {
        $message = "warning|Please fill all required fields!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "error|Invalid email format!";
    } elseif ($updatePassword) {
        // Validate passwords only if update is requested
        if (empty($currentPassword)) {
            $message = "warning|Current password is required!";
        } elseif (empty($newPassword)) {
            $message = "warning|New password is required!";
        } elseif (empty($confirmPassword)) {
            $message = "warning|Please confirm your new password!";
        } elseif ($newPassword !== $confirmPassword) {
            $message = "error|New passwords don't match!";
        } elseif (strlen($newPassword) < 6) {
            $message = "error|Password must be at least 6 characters!";
        } else {
            // Verify current password
            $checkPass = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $checkPass->bind_param("i", $userId);
            $checkPass->execute();
            $checkPass->bind_result($hashedPassword);
            $checkPass->fetch();
            $checkPass->close();
            
            if (!password_verify($currentPassword, $hashedPassword)) {
                $message = "error|Current password is incorrect!";
            }
        }
    }
    
    // Handle image upload if no errors
    $imageName = $user['image'];
    if (empty($message) && isset($_FILES['profile_image']['name']) && !empty($_FILES['profile_image']['name'])) {
        $image = $_FILES['profile_image'];
        $imageName = uniqid() . '.' . pathinfo($image['name'], PATHINFO_EXTENSION);
        $uploadPath = '../uploads/profiles/' . $imageName;
        
        // Validate image
        $allowedTypes = ['jpg', 'jpeg', 'png'];
        $imageExt = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));
        
        if (!in_array($imageExt, $allowedTypes)) {
            $message = "error|Only JPG, JPEG & PNG files are allowed!";
        } elseif ($image['size'] > 2 * 1024 * 1024) { // 2MB max
            $message = "error|Image size must be less than 2MB!";
        } elseif (move_uploaded_file($image['tmp_name'], $uploadPath)) {
            // Delete old image if it exists
            if (!empty($user['image'])) {
                $oldImagePath = '../uploads/profiles/' . $user['image'];
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }
        } else {
            $message = "error|Failed to upload image!";
        }
    }
    
    // Update database if no errors
    if (empty($message)) {
        if ($updatePassword) {
            $newHashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, password = ?, image = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $name, $email, $newHashedPassword, $imageName, $userId);
        } else {
            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, image = ? WHERE id = ?");
            $stmt->bind_param("sssi", $name, $email, $imageName, $userId);
        }
        
        if ($stmt->execute()) {
            $message = "success|Profile updated successfully!";

             logActivity($conn, $userId, 'profile_update', null, 'User updated profile');

            // Refresh user data
            $user['name'] = $name;
            $user['email'] = $email;
            $user['image'] = $imageName;
        } else {
            $message = "error|Failed to update profile!";
        }
        $stmt->close();
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
    <title>My Profile</title><link rel="icon" type="image/x-icon" href="../uploads/unnamed.png">
    <link rel="stylesheet" href="../Bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        :root {
            --primary-color: #4e73df;
            --primary-dark: #2e59d9;
            --secondary-color: #f8f9fc;
            --accent-color: #dddfeb;
            --text-color: #5a5c69;
            --header-bg: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
        }
        
        body {
            background-color: var(--secondary-color);
            color: var(--text-color);
            font-size: 1.05rem; /* Increased base font size */
        }
        
        /* Header Styles */
        .profile-header {
            background: var(--header-bg);
            color: white;
            padding: 1.5rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        
        .profile-header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .profile-title {
            display: flex;
            align-items: center;
            font-size: 1.8rem; /* Larger font size */
            font-weight: 600;
        }
        
        .profile-title i {
            margin-right: 15px;
            font-size: 2.5rem; /* Larger icon */
        }
        
        /* Profile Container */
        .profile-container {
            max-width: 800px;
            margin: 0 auto 3rem;
            background: #fff;
            padding: 2.5rem; /* More padding */
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            border-radius: 0.75rem; /* More rounded corners */
            border: 1px solid var(--accent-color);
        }
        
        /* Profile Image */
        .profile-img-container {
            width: 220px; /* Larger image */
            height: 220px;
            margin: 0 auto 2.5rem;
            position: relative;
            overflow: hidden;
            border-radius: 15%; /* Slightly rounded */
            border: 3px solid white;
            box-shadow: 0 0.25rem 2rem 0 rgba(58, 59, 69, 0.2); /* Deeper shadow */
        }
        
        .profile-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .profile-img-edit {
            position: absolute;
            bottom: 0;
            right: 0;
            left: 0;
            background: rgba(0,0,0,0.6);
            color: white;
            text-align: center;
            padding: 10px;
            font-size: 1rem; /* Larger font */
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .profile-img-edit:hover {
            background: rgba(0,0,0,0.8);
        }
        
        /* Form Elements */
        .form-label {
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 0.75rem;
            font-size: 1.1rem; /* Larger labels */
        }
        
        .form-control {
            border-radius: 0.5rem;
            padding: 0.85rem 1.2rem;
            border: 1px solid var(--accent-color);
            font-size: 1.05rem; /* Larger input text */
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 0.85rem;
            font-weight: 600;
            font-size: 1.1rem;
            border-radius: 0.5rem;
        }
        
        .btn-outline-light {
            font-size: 1.1rem;
            padding: 0.6rem 1.2rem;
        }
        
        /* Last Login Styles */
        .last-login-card {
            background: rgba(78, 115, 223, 0.1);
            border-left: 4px solid var(--primary-color);
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .last-login-text {
            font-size: 1.1rem;
            color: var(--text-color);
            margin-bottom: 0;
        }
        
        .last-login-icon {
            font-size: 1.3rem;
            margin-right: 0.5rem;
            color: var(--primary-color);
        }
        
        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .profile-container {
                padding: 1.75rem;
                margin: 0 1rem 2.5rem;
            }
            
            .profile-header {
                padding: 1.25rem 0;
            }
            
            .profile-title {
                font-size: 1.5rem;
            }
            
            .profile-img-container {
                width: 180px;
                height: 180px;
            }
            
            .form-label, .btn-primary, .btn-outline-light {
                font-size: 1rem;
            }
        }
        
        @media (max-width: 576px) {
            .profile-container {
                padding: 1.5rem;
            }
            
            .profile-img-container {
                width: 160px;
                height: 160px;
            }
            
            .profile-title {
                font-size: 1.3rem;
            }
            
            .profile-header-content {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .back-btn {
                margin-top: 1rem;
                align-self: flex-end;
            }
        }
    </style>
</head>
<body>

<!-- Profile Header -->
<div class="profile-header">
    <div class="container">
        <div class="profile-header-content">
            <div class="profile-title">
                <i class="bi bi-person-circle"></i>
                <span>My Profile</span>
            </div>
            <div class="back-btn">
                <a href="dashboard.php" class="btn btn-outline-light">
                    <i class="bi bi-arrow-left"></i> Back to Home
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <div class="profile-container">
        <?php if (isset($type)): ?>
            <div class="alert alert-<?= $type === 'success' ? 'success' : ($type === 'error' ? 'danger' : 'warning') ?> alert-dismissible fade show">
                <i class="bi bi-<?= $type === 'success' ? 'check-circle' : ($type === 'error' ? 'exclamation-triangle' : 'info-circle') ?>"></i>
                <?= htmlspecialchars($text) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Option 1: Last Login Card (Top of Form) -->
       <div class="last-login-card mb-4">
  <div class="d-flex flex-column">
    <p class="mb-1"><i class="bi bi-person-fill text-primary"></i> Current session started at: 
      <strong><?= !empty($user['last_login']) ? date('d M Y, h:i A', strtotime($user['last_login'])) : 'Not recorded' ?></strong>
    </p>
    <p class="mb-0"><i class="bi bi-clock-history text-success"></i> Last login (before this): 
      <strong><?= $actualLastLogin ? date('d M Y, h:i A', strtotime($actualLastLogin)) : 'First login or no previous data' ?></strong>
    </p>
  </div>
</div>


        <form method="POST" action="" enctype="multipart/form-data">
            <div class="row">
                <!-- Profile Image with Option 2: Last Login Below -->
                <div class="col-12 text-center mb-4">
                    <div class="profile-img-container">
                        <?php if (!empty($user['image'])): ?>
                            <img src="../uploads/profiles/<?= htmlspecialchars($user['image']) ?>" class="profile-img" id="profileImagePreview">
                        <?php else: ?>
                            <div class="profile-img d-flex align-items-center justify-content-center bg-light">
                                <i class="bi bi-person" style="font-size: 4rem; color: var(--text-color);"></i>
                            </div>
                        <?php endif; ?>
                        <div class="profile-img-edit">
                            <i class="bi bi-camera"></i> Change Photo
                        </div>
                    </div>

                    <!-- Option 2: Last Login Below Profile Image -->
                    <div class="mt-3">
                        <span class="badge bg-primary">
                            <i class="bi bi-clock-history"></i> 
                            Last login: <?= $actualLastLogin ? date('d M Y, h:i A', strtotime($actualLastLogin)) : 'First login or no previous data' ?></strong>
                        </span>
                    </div>
                    <input type="file" name="profile_image" id="profileImageInput" accept="image/*" class="d-none">
                </div>

                <!-- Name -->
                <div class="col-md-6 mb-4"> <!-- Increased margin -->
                    <label for="name" class="form-label"><i class="bi bi-person-fill"></i> Full Name</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                </div>

                <!-- Email -->
                <div class="col-md-6 mb-4">
                    <label for="email" class="form-label"><i class="bi bi-envelope-fill"></i> Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>

                <!-- Password Change Section -->
                <div class="col-12 mb-4">
                    <div class="card">
                        <div class="card-header bg-light">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" 
                                       id="updatePassword" name="update_password" value="1">
                                <label class="form-check-label" for="updatePassword" style="font-size: 1.1rem;">
                                    <i class="bi bi-key-fill"></i> Change Password
                                </label>
                            </div>
                        </div>
                        <div class="card-body password-fields" style="display: none;">
                            <div class="row">
                                <!-- Current Password -->
                                <div class="col-md-4 mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <div class="password-input-group">
                                        <input type="password" class="form-control" id="current_password" name="current_password">
                                        <i class="bi bi-eye-fill password-toggle" onclick="togglePassword('current_password', this)"></i>
                                    </div>
                                </div>
                                
                                <!-- New Password -->
                                <div class="col-md-4 mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <div class="password-input-group">
                                        <input type="password" class="form-control" id="new_password" name="new_password">
                                        <i class="bi bi-eye-fill password-toggle" onclick="togglePassword('new_password', this)"></i>
                                    </div>
                                </div>
                                
                                <!-- Confirm Password -->
                                <div class="col-md-4 mb-3">
                                    <label for="confirm_password" class="form-label">Confirm Password</label>
                                    <div class="password-input-group">
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                        <i class="bi bi-eye-fill password-toggle" onclick="togglePassword('confirm_password', this)"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="col-12 mt-4">
                    <button type="submit" class="btn btn-primary w-100 py-3">
                        <i class="bi bi-save-fill"></i> Update Profile
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="../Bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Profile image preview
    document.getElementById('profileImageInput').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;
        
        const reader = new FileReader();
        reader.onload = function(e) {
            let preview = document.getElementById('profileImagePreview');
            if (!preview) {
                preview = document.createElement('img');
                preview.id = 'profileImagePreview';
                preview.className = 'profile-img';
                document.querySelector('.profile-img-container').insertBefore(preview, document.querySelector('.profile-img-edit'));
                const placeholder = document.querySelector('.profile-img-container .profile-img:not(img)');
                if (placeholder) placeholder.remove();
            }
            preview.src = e.target.result;
        }
        reader.readAsDataURL(file);
    });

    // Click on image container to trigger file input
    document.querySelector('.profile-img-edit').addEventListener('click', function() {
        document.getElementById('profileImageInput').click();
    });

    // Password toggle function
    function togglePassword(id, icon) {
        const input = document.getElementById(id);
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('bi-eye-fill');
            icon.classList.add('bi-eye-slash-fill');
        } else {
            input.type = 'password';
            icon.classList.remove('bi-eye-slash-fill');
            icon.classList.add('bi-eye-fill');
        }
    }

    // Toggle password fields with animation
    document.getElementById('updatePassword').addEventListener('change', function() {
        const passwordFields = document.querySelector('.password-fields');
        if (this.checked) {
            passwordFields.style.display = 'block';
            passwordFields.style.height = passwordFields.scrollHeight + 'px';
        } else {
            passwordFields.style.height = '0';
            setTimeout(() => {
                passwordFields.style.display = 'none';
            }, 300);
        }
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