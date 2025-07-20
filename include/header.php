<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include '../db.php';

$isLoggedIn = isset($_SESSION['user']);
$userName = $isLoggedIn ? $_SESSION['user']['name'] : 'Guest';
$userImage = $isLoggedIn && $_SESSION['user']['image'] ? $_SESSION['user']['image'] : 'default.png';

$unreadCount = 0;
if (isset($_SESSION['user']['id'])) {
    $uid = $_SESSION['user']['id'];
    $stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $stmt->bind_result($unreadCount);
    $stmt->fetch();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SmartStudy Hub</title>
  <link rel="icon" type="image/x-icon" href="../uploads/unnamed.png">
  <link rel="stylesheet" href="../Bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

  <style>
    body {
      font-weight: 600;
      color: #212529; /* Dark text */
    }
    .navbar-nav .nav-link {
      color: #212529 !important;
    }
    .navbar-nav .nav-link:hover {
      background-color: #e3f2fd;
      border-radius: 5px;
      color: #0d6efd !important;
    }
    .badge-mca {
      background-color:rgb(29, 13, 253);
      color: white;
      font-size: 0.7rem;
      vertical-align: top;
      margin-left: 5px;
    }
    .dropdown-menu .dropdown-item.custom-hover:hover {
  background-color: #e3f2fd;
  color: #0d6efd;
  font-weight: 600;
}
.dropdown-menu .dropdown-item.custom-hover i {
  font-weight: 600;
}

  </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm sticky-top">
  <div class="container">
    <a class="navbar-brand fw-bold text-primary d-flex align-items-center">
      <i class="bi bi-journal-bookmark-fill me-2"></i> 
      SmartStudy<span class="text-secondary fw-bold">Hub</span>
      <span class="badge badge-mca ms-2" data-bs-toggle="tooltip" data-bs-placement="right" title="Master of Computer Applications">MCA</span>
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="mainNavbar">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0 fw-semibold">
        <?php if ($isLoggedIn): ?>
          <li class="nav-item">
            <a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="mat.php"><i class="bi bi-collection me-2"></i>Materials</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="user_ass.php"><i class="bi bi-journal-text me-2"></i>Assignments</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="user_j.php"><i class="bi bi-journals me-2"></i>Journals</a>
          </li>
       <li class="nav-item position-relative">
  <a class="nav-link" href="notifications.php">
    <i class="bi bi-bell-fill me-2 text-dark fs-5"></i>
    <?php if ($unreadCount > 0): ?>
      <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
        <?= $unreadCount ?>
        <span class="visually-hidden">unread notifications</span>
      </span>
    <?php endif; ?>
  </a>
</li>


       <li class="nav-item dropdown">
  <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
    <img src="../uploads/profiles/<?= htmlspecialchars($userImage) ?>" class="rounded-circle me-2" width="30" height="30" alt="Profile">
    <span class="fw-bold"><?= htmlspecialchars($userName) ?></span>
  </a>
  <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
    <li>
      <a class="dropdown-item fw-semibold d-flex align-items-center custom-hover" href="my_profile.php">
        <i class="bi bi-person me-2"></i> My Profile
      </a>
    </li>
    <li>
      <a class="dropdown-item fw-semibold d-flex align-items-center custom-hover" href="my_down.php">
        <i class="bi bi-download me-2"></i> My Downloads
      </a>
    </li>
    <li>
      <a class="dropdown-item fw-semibold d-flex align-items-center custom-hover" href="my_act.php">
        <i class="bi bi-clock-history me-2"></i> My Activity
      </a>
    </li>
    <li>
      <a class="dropdown-item fw-semibold d-flex align-items-center custom-hover" href="myreview.php">
        <i class="bi bi-star-fill me-2"></i> My Reviews
      </a>
    </li>
    <li>
      <a class="dropdown-item fw-semibold d-flex align-items-center custom-hover" href="logout_con.php">
        <i class="bi bi-box-arrow-right me-2"></i> Logout
      </a>
    </li>
  </ul>
</li>

        <?php else: ?>
          <li class="nav-item">
            <a href="../User/login.php" class="btn btn-primary ms-2 fw-semibold">
              <i class="bi bi-box-arrow-in-right"></i> Login
            </a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<script src="../Bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
  // Enable tooltips
  var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
  });
</script>
</body>
</html>
