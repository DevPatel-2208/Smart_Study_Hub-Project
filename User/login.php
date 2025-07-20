<?php
session_start();
include '../db.php';
include 'activity_helper.php';
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT id, name, email, password, image, last_login FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            $_SESSION['user'] = [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'image' => $user['image']
            ];

            logActivity($conn, $user['id'], 'login', null, 'User logged in');
            $conn->query("UPDATE users SET last_login = NOW() WHERE id = {$user['id']}");
            $res = $conn->query("SELECT last_login FROM users WHERE id = {$user['id']}");
            $_SESSION['user']['last_login'] = $res->fetch_assoc()['last_login'];

            header("Location: dashboard.php");
            exit();
        } else {
            $message = "Invalid password!";
        }
    } else {
        $message = "No user found with this email.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>SmartStudyHub - User Login</title><link rel="icon" type="image/x-icon" href="../uploads/unnamed.png">
    <link rel="stylesheet" href="../Bootstrap/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6a11cb;
            --primary-dark: #2575fc;
            --header-color: #4a00e0;
            --header-gradient: linear-gradient(135deg, var(--header-color) 0%, #8e2de2 100%);
        }
        
        body {
          
            background: linear-gradient(135deg, #e0c3fc 0%, #8ec5fc 100%);
            font-family: 'Poppins', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .login-container {
            width: 100%;
            max-width: 450px;
            animation: fadeIn 0.5s ease-in-out;
        }
        
        .login-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border: none;
        }
        
        .login-header {
            background: var(--header-gradient);
            color: white;
            padding: 25px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .login-header::before {
            content: "";
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 70%);
            transform: rotate(30deg);
        }
        
        .login-body {
            padding: 30px;
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
        
        .input-group-text {
            background: transparent;
            border-right: none;
        }
        
        .form-floating>.form-control:not(:placeholder-shown)~label {
            color: var(--primary);
        }
        
        .btn-login {
            background: var(--header-gradient);
            border: none;
            padding: 12px;
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.3s;
            color: white;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(106, 17, 203, 0.3);
            color: white;
        }
        
        .password-toggle {
            cursor: pointer;
            background: transparent;
            border-left: none;
            border-color: #e0e0e0;
        }
        
        .password-toggle:hover {
            color: var(--primary);
        }
        
        .divider {
            display: flex;
            align-items: center;
            margin: 20px 0;
        }
        
        .divider::before, .divider::after {
            content: "";
            flex: 1;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .divider-text {
            padding: 0 10px;
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .alert-danger {
            border-left: 4px solid #dc3545;
        }
        
        /* New styles for better form field alignment */
        .form-floating {
            position: relative;
        }
        
        .form-floating > .form-control {
            height: calc(3.5rem + 2px);
            padding: 1rem 0.75rem;
        }
        
        .form-floating > label {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            padding: 1rem 3rem; /* Adjusted padding to account for icons */
            pointer-events: none;
            border: 1px solid transparent;
            transform-origin: 0 0;
            transition: opacity .1s ease-in-out, transform .1s ease-in-out;
        }
        
        .form-floating > .form-control:focus~label,
        .form-floating > .form-control:not(:placeholder-shown)~label {
            transform: scale(.85) translateY(-.8rem) translateX(.5rem);
            opacity: .65;
            padding-left: 3.5rem; /* Adjusted for icon spacing */
        }
        
        .input-group.has-floating-label {
            position: relative;
        }
        
        .input-group-text.icon-container {
            position: absolute;
            z-index: 5;
            top: 50%;
            left: 12px;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            padding: 0;
        }
        
        .input-group.has-floating-label .form-control {
            padding-left: 40px;
        }
        
        @media (max-width: 576px) {
            .login-body {
                padding: 20px;
            }
            
            .login-header {
                padding: 20px 15px;
            }
            
            .login-header h3 {
                font-size: 1.5rem;
            }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="card login-card">
            <div class="login-header">
                <h3><i class="bi bi-journal-bookmark-fill me-2"></i> SmartStudyHub</h3>
                <p class="mb-0">Sign in to access your dashboard</p>
            </div>
            
            <div class="card-body login-body">
                <?php if (!empty($message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <div><?= htmlspecialchars($message) ?></div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
    <!-- Email Field -->
    <div class="mb-3">
        <label for="floatingEmail" class="form-label fw-semibold">Email address</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-envelope-fill text-primary"></i></span>
            <input type="email" name="email" class="form-control" id="floatingEmail" placeholder="Enter your email" required>
        </div>
    </div>

    <!-- Password Field -->
    <div class="mb-4">
        <label for="floatingPassword" class="form-label fw-semibold">Password</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-lock-fill text-primary"></i></span>
            <input type="password" name="password" class="form-control" id="floatingPassword" placeholder="Enter your password" required>
            <span class="input-group-text password-toggle" id="togglePassword">
                <i class="bi bi-eye-fill"></i>
            </span>
        </div>
    </div>

    <!-- Login Button -->
    <div class="d-grid mb-3">
        <button type="submit" class="btn btn-login btn-lg">
            <i class="bi bi-box-arrow-in-right me-2"></i> Login
        </button>
    </div>

    <!-- Forgot Password -->
    <div class="text-center">
        <a href="Home.php" class="text-decoration-none text-muted small">
            <i class="bi bi-question-circle me-1"></i> Back To Home
        </a>
    </div>

    <!-- Divider -->
    <div class="divider">
        <span class="divider-text">OR</span>
    </div>

    <!-- Register -->
    <div class="text-center">
        <p class="mb-2">Don't have an account?</p>
        <a href="register.php" class="btn btn-outline-primary">
            <i class="bi bi-person-plus me-1"></i> Create Account
        </a>
    </div>
</form>

            </div>
        </div>
    </div>

    <script src="../Bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.querySelector('#togglePassword');
            const password = document.querySelector('#floatingPassword');
            
            togglePassword.addEventListener('click', function() {
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                this.querySelector('i').classList.toggle('bi-eye-fill');
                this.querySelector('i').classList.toggle('bi-eye-slash-fill');
            });
        });
    </script>
</body>
</html>