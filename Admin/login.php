<?php
session_start();
include '../db.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT id, name, password FROM admins WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $admin = $result->fetch_assoc();

        if ($password === $admin['password']) { // ✅ Plain text compare
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['name'];

            header("Location: upload_mat.php");
            exit();
        } else {
            $message = "Invalid password!";
        }
    } else {
        $message = "No account found with that email.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Login</title>
  <link rel="stylesheet" href="../Bootstrap/css/bootstrap.min.css">
  <style>
    body { background-color: #f0f2f5; }
    .login-box {
      max-width: 400px;
      margin: 100px auto;
      background: white;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 0 15px rgba(0,0,0,0.1);
    }
  </style>
</head>
<body>
<div class="login-box">
  <h4 class="mb-4 text-primary">Admin Login</h4>
  <?php if (!empty($message)) echo "<div class='alert alert-danger'>$message</div>"; ?>
  <form method="POST">
    <div class="mb-3">
      <label>Email</label>
      <input type="email" name="email" class="form-control" required>
    </div>
    <div class="mb-3">
      <label>Password</label>
      <input type="password" name="password" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-primary w-100">Login</button>
  </form>
</div>
</body>
</html>