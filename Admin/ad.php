<?php
// Sample admin name
$adminName = isset($_SESSION['admin']) ? $_SESSION['admin']['name'] : 'Admin';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    echo "<script>window.location.href = 'login.php';</script>";
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>SmartStudy Hub - Admin Dashboard</title>

  <!-- Bootstrap CSS & Icons -->
  <link rel="stylesheet" href="../Bootstrap/css/bootstrap.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
  
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
  
<style>
  :root {
    --sidebar-bg: #1a237e;
    --sidebar-hover: #303f9f;
    --sidebar-active: #3949ab;
    --topbar-bg: #ffffff;
    --text-light: #f5f5f5;
    --text-dark: #212121;
    --accent-color: #00acc1;
  }
  
  body {
    font-family: 'Poppins', sans-serif;
    background-color: #f5f7fa;
    margin: 0;
    padding: 0;
    min-height: 100vh;
  }
  
  .admin-sidebar {
    min-height: 100vh;
    background-color: var(--sidebar-bg);
    color: var(--text-light);
    position: fixed;
    top: 0;
    left: 0;
    width: 260px;
    z-index: 1030;
    transition: all 0.3s ease;
    box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
  }
  
  .admin-sidebar .sidebar-header {
    padding: 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    text-align: center;
  }
  
  .admin-sidebar .sidebar-header h5 {
    color: white;
    font-weight: 600;
    margin: 0;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  
  .admin-sidebar .sidebar-header h5 i {
    color: var(--accent-color);
    margin-right: 10px;
    font-size: 1.5rem;
  }
  
  .admin-sidebar .nav {
    padding: 10px 0;
  }
  
  .admin-sidebar .nav-link {
    color: var(--text-light);
    font-weight: 400;
    padding: 12px 25px;
    margin: 2px 10px;
    border-radius: 5px;
    transition: all 0.3s;
    display: flex;
    align-items: center;
  }
  
  .admin-sidebar .nav-link i {
    margin-right: 12px;
    font-size: 1.1rem;
    width: 24px;
    text-align: center;
  }
  
  .admin-sidebar .nav-link:hover {
    background-color: var(--sidebar-hover);
    color: white;
    transform: translateX(5px);
  }
  
  .admin-sidebar .nav-link.active {
    background-color: var(--sidebar-active);
    color: white;
    font-weight: 500;
  }
  
  .admin-sidebar .nav-link.logout {
    color: #ff6b6b;
  }
  
  .admin-sidebar .nav-link.logout:hover {
    background-color: rgba(255, 107, 107, 0.1);
  }
  
  .admin-topbar {
    margin-left: 260px;
    background-color: var(--topbar-bg);
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    padding: 15px 30px;
    position: sticky;
    top: 0;
    z-index: 1020;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: all 0.3s ease;
  }
  
  .admin-topbar .welcome-text {
    font-weight: 500;
    color: var(--text-dark);
  }
  
  .admin-topbar .welcome-text span {
    color: var(--accent-color);
    font-weight: 600;
  }
  
  .admin-content {
    margin-left: 260px;
    padding: 30px;
    transition: all 0.3s ease;
  }
  
  .sidebar-toggle {
    background: none;
    border: none;
    color: var(--text-dark);
    font-size: 1.5rem;
    cursor: pointer;
    display: none;
  }
  
  .admin-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1025;
    opacity: 0;
    transition: opacity 0.3s ease;
  }
  
  .admin-overlay.active {
    display: block;
    opacity: 1;
  }
  
  @media (max-width: 991.98px) {
    .admin-sidebar {
      left: -260px;
    }
    
    .admin-sidebar.show {
      left: 0;
    }
    
    .admin-topbar {
      margin-left: 0;
    }
    
    .admin-content {
      margin-left: 0;
    }
    
    .sidebar-toggle {
      display: block;
    }
  }
  
  /* Animation for sidebar items */
  @keyframes fadeIn {
    from { opacity: 0; transform: translateX(-20px); }
    to { opacity: 1; transform: translateX(0); }
  }
  
  .admin-sidebar .nav-link {
    animation: fadeIn 0.3s ease forwards;
    opacity: 0;
  }
  
  .admin-sidebar .nav-link:nth-child(1) { animation-delay: 0.1s; }
  .admin-sidebar .nav-link:nth-child(2) { animation-delay: 0.15s; }
  .admin-sidebar .nav-link:nth-child(3) { animation-delay: 0.2s; }
  .admin-sidebar .nav-link:nth-child(4) { animation-delay: 0.25s; }
  .admin-sidebar .nav-link:nth-child(5) { animation-delay: 0.3s; }
  .admin-sidebar .nav-link:nth-child(6) { animation-delay: 0.35s; }
  .admin-sidebar .nav-link:nth-child(7) { animation-delay: 0.4s; }
  .admin-sidebar .nav-link:nth-child(8) { animation-delay: 0.45s; }
  .admin-sidebar .nav-link:nth-child(9) { animation-delay: 0.5s; }
  .admin-sidebar .nav-link:nth-child(10) { animation-delay: 0.55s; }
  .admin-sidebar .nav-link:nth-child(11) { animation-delay: 0.6s; }
</style>
</head>
<body>
<form id="logoutForm" method="POST" action="" style="display: none;">
  <input type="hidden" name="logout" value="1">
</form>

<!-- Sidebar -->
<div class="admin-sidebar d-flex flex-column" id="adminSidebar">
  <div class="sidebar-header">
    <h5><i class="bi bi-shield-lock-fill"></i> Admin Panel</h5>
  </div>
  <nav class="nav flex-column">
    <a href="ad_dash.php" class="nav-link"><i class="bi bi-speedometer2"></i> Dashboard</a>
    <a href="manage_user.php" class="nav-link"><i class="bi bi-people-fill"></i> Manage Users</a>
    <a href="manage_materials.php" class="nav-link"><i class="bi bi-file-earmark-text-fill"></i> Study Materials</a>
   <a href="Ass.php" class="nav-link text-white"><i class="bi bi-collection"></i> All Materials</a>
    <a href="add_sem.php" class="nav-link"><i class="bi bi-calendar2-range-fill"></i> Semesters</a>
    <a href="add_sub.php" class="nav-link"><i class="bi bi-book-half"></i> Subjects</a>
     <a href="upload_mat.php" class="nav-link"><i class="bi bi-tags-fill"></i> Add Material</a>
    <a href="add_mat.php" class="nav-link"><i class="bi bi-tags-fill"></i> Add Material Type</a>
    <a href="review.php" class="nav-link"><i class="bi bi-star-half"></i> File Reviews</a>
    <a href="down.php" class="nav-link"><i class="bi bi-activity"></i> User Activities</a>
    <a href="send_notification.php" class="nav-link"><i class="bi bi-bell-fill"></i> Notifications</a>
    <a href="logout.php" class="nav-link logout"><i class="bi bi-box-arrow-right"></i> Logout</a>
  </nav>
</div>

<!-- Overlay for Mobile -->
<div class="admin-overlay" id="adminOverlay"></div>

<!-- Topbar -->
<div class="admin-topbar">
  <button class="sidebar-toggle" id="sidebarToggle">
    <i class="bi bi-list"></i>
  </button>
  <div class="welcome-text">
    Welcome, <span><?php echo htmlspecialchars($adminName); ?></span>
  </div><a href="#" class="nav-link logout text-danger" id="logoutBtn">
  <i class="bi bi-box-arrow-right"></i> Logout
</a>

</div>

<!-- Main Content -->
<div class="admin-content">
  <!-- Your page content will go here -->
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.getElementById("logoutBtn").addEventListener("click", function(e) {
  e.preventDefault();
  Swal.fire({
    title: "Are you sure?",
    text: "You will be logged out from the admin panel.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#d33",
    cancelButtonColor: "#6c757d",
    confirmButtonText: "Yes, Logout",
    cancelButtonText: "Cancel"
  }).then((result) => {
    if (result.isConfirmed) {
      document.getElementById("logoutForm").submit();
    }
  });
});
</script>

<!-- JS for Sidebar Toggle -->
<script>
  const sidebar = document.getElementById('adminSidebar');
  const overlay = document.getElementById('adminOverlay');
  const toggleBtn = document.getElementById('sidebarToggle');
  
  function toggleSidebar() {
    sidebar.classList.toggle('show');
    overlay.classList.toggle('active');
  }
  
  toggleBtn.addEventListener('click', toggleSidebar);
  overlay.addEventListener('click', toggleSidebar);
  
  // Close sidebar when clicking outside on mobile
  document.addEventListener('click', function(event) {
    const isClickInsideSidebar = sidebar.contains(event.target);
    const isClickOnToggleBtn = toggleBtn.contains(event.target);
    
    if (window.innerWidth <= 991.98 && !isClickInsideSidebar && !isClickOnToggleBtn && sidebar.classList.contains('show')) {
      toggleSidebar();
    }
  });
  
  // Add animation class when page loads
  document.addEventListener('DOMContentLoaded', function() {
    const navLinks = document.querySelectorAll('.admin-sidebar .nav-link');
    navLinks.forEach(link => {
      link.style.opacity = '1';
    });
  });
</script>

</body>
</html>