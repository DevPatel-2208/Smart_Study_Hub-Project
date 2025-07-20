<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include '../db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Get user from session
$user = $_SESSION['user'];
$userId = $user['id'];
$userName = htmlspecialchars($user['name']);
$userImage = $user['image'] ? $user['image'] : 'default.png';

// ✅ Get current session login time from DB
$stmt = $conn->prepare("SELECT last_login FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$loginResult = $stmt->get_result();
$currentLoginRow = $loginResult->fetch_assoc();
$currentSessionLogin = $currentLoginRow['last_login'] ?? null;

// ✅ Get actual last login (excluding current session)
$lastLoginStmt = $conn->prepare("SELECT activity_time FROM user_activities WHERE user_id = ? AND activity_type = 'login' ORDER BY activity_time DESC LIMIT 2");
$lastLoginStmt->bind_param("i", $userId);
$lastLoginStmt->execute();
$lastLoginResult = $lastLoginStmt->get_result();

$loginTimestamps = [];
while ($row = $lastLoginResult->fetch_assoc()) {
    $loginTimestamps[] = $row['activity_time'];
}
$actualLastLogin = $loginTimestamps[1] ?? null;

// ✅ Get user stats
$unreadCount = $conn->query("SELECT COUNT(*) AS total FROM notifications WHERE user_id = $userId AND is_read = 0")->fetch_assoc()['total'];
$downloadCount = $conn->query("SELECT COUNT(*) AS total FROM downloads WHERE user_id = $userId")->fetch_assoc()['total'];
$reviewCount = $conn->query("SELECT COUNT(*) AS total FROM file_reviews WHERE user_id = $userId")->fetch_assoc()['total'];

// ✅ Get recent downloads
$recentDownloads = $conn->query("
    SELECT d.*, sm.title, smt.type_name 
    FROM downloads d
    JOIN study_materials sm ON d.material_id = sm.id
    JOIN material_types smt ON sm.material_type_id = smt.id
    WHERE d.user_id = $userId
    ORDER BY d.downloaded_at DESC
    LIMIT 5
");

// ✅ Get recent notifications
$recentNotifications = $conn->query("
    SELECT * FROM notifications 
    WHERE user_id = $userId
    ORDER BY created_at DESC
    LIMIT 5
");

// ✅ Get popular materials
$popularMaterials = $conn->query("
    SELECT sm.id, sm.title, COUNT(d.id) as download_count
    FROM study_materials sm
    JOIN downloads d ON sm.id = d.material_id
    GROUP BY sm.id
    ORDER BY download_count DESC
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Dashboard - SmartStudy Hub</title><link rel="icon" type="image/x-icon" href="../uploads/unnamed.png">
  <link rel="stylesheet" href="../Bootstrap/css/bootstrap.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    :root {
      --primary: #6a11cb;
      --primary-dark: #2575fc;
      --secondary: #f8f9fa;
      --text-dark: #2d3748;
      --text-light: #718096;
    }
    
    body {
      font-family: 'Poppins', sans-serif;
      background: #f9fafb;
      color: var(--text-dark);
    }
    
    /* Enhanced Header */
    .dashboard-header {
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
      padding: 2rem 0 3rem;
      margin-bottom: -2rem;
      position: relative;
      overflow: hidden;
      z-index: 1;
    }
    
    .dashboard-header::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 100%;
      background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="rgba(255,255,255,0.1)" d="M0,192L48,197.3C96,203,192,213,288,229.3C384,245,480,267,576,250.7C672,235,768,181,864,181.3C960,181,1056,235,1152,234.7C1248,235,1344,181,1392,154.7L1440,128L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') bottom center no-repeat;
      background-size: cover;
      z-index: -1;
    }
    
    .user-avatar {
      width: 140px;
      height: 140px;
      border: 4px solid black;
      border-radius: 15px !important;
    
     
      box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    }
    
    .welcome-text {
      text-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    /* Login Info Card */
    .login-info-card {
      background: white;
      border-radius: 1rem;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
      padding: 1.5rem;
      margin-top: -3rem;
      position: relative;
      z-index: 2;
      border: none;
    }
    
    .login-info-item {
      padding: 0.75rem 1rem;
      border-radius: 0.5rem;
      transition: all 0.3s ease;
    }
    
    .login-info-item:hover {
      background: rgba(106, 17, 203, 0.05);
    }
    
    /* Stats Cards */
    .stat-card {
      border: none;
      border-radius: 1rem;
      box-shadow: 0 5px 15px rgba(0,0,0,0.05);
      transition: all 0.3s ease;
      height: 100%;
      overflow: hidden;
      position: relative;
      z-index: 1;
    }
    
    .stat-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(90deg, var(--primary) 0%, var(--primary-dark) 100%);
    }
    
    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }
    
    .stat-icon {
      font-size: 2.5rem;
      margin-bottom: 1rem;
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
      -webkit-background-clip: text;
      background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    
    /* Content Cards */
    .content-card {
      border: none;
      border-radius: 1rem;
      box-shadow: 0 5px 15px rgba(0,0,0,0.05);
      transition: all 0.3s ease;
      height: 100%;
    }
    
    .content-card:hover {
      box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }
    
    .content-card .card-header {
      background: white;
      border-bottom: 1px solid rgba(0,0,0,0.05);
      font-weight: 600;
      padding: 1.25rem 1.5rem;
      border-radius: 1rem 1rem 0 0 !important;
    }
    
    /* Activity Items */
    .activity-item {
      position: relative;
      padding-left: 1.5rem;
      margin-bottom: 1rem;
    }
    
    .activity-item::before {
      content: '';
      position: absolute;
      left: 0;
      top: 0;
      bottom: 0;
      width: 3px;
      background: linear-gradient(to bottom, var(--primary), var(--primary-dark));
      border-radius: 3px;
    }
    
    /* Material Badge */
    .material-badge {
      font-size: 0.7rem;
      padding: 0.25rem 0.75rem;
      border-radius: 1rem;
      background: rgba(106, 17, 203, 0.1);
      color: var(--primary);
      font-weight: 500;
    }
    
    /* Responsive Adjustments */
    @media (max-width: 768px) {
      .dashboard-header {
        padding: 1.5rem 0 2.5rem;
        text-align: center;
      }
      
      .user-avatar {
        width: 130px;
        height: 130px;
        margin: 0 auto 1rem;
      }
      
      .welcome-text {
        text-align: center;
      }
      
      .login-info-card {
        margin-top: -2rem;
      }
      
      .stat-card, .content-card {
        margin-bottom: 1.5rem;
      }
    }
    
    @media (max-width: 576px) {
      .dashboard-header {
        padding: 1rem 0 2rem;
      }
      
      .login-info-item {
        padding: 0.5rem;
      }
      
      .stat-icon {
        font-size: 2rem;
      }
    }
  </style>
</head>
<body>
<?php include '../include/header.php'; ?>

<!-- Enhanced Dashboard Header -->
<div class="dashboard-header">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-md-2 text-center text-md-start mb-3 mb-md-0">
        <img src="../uploads/profiles/<?= $userImage ?>" class="user-avatar">
      </div>
      <div class="col-md-10">
        <h1 class="welcome-text text-white mb-2">Welcome Back👋,🤩<?= $userName ?>😊✌️</h1>
        <p class="text-white-50 mb-0">Your personalized learning dashboard</p>
      </div>
    </div>
  </div>
</div>

<!-- Login Info Card -->
<div class="container">
  <div class="login-info-card mb-4">
    <div class="row g-3">
      <div class="col-md-6">
        <div class="login-info-item">
          <div class="d-flex align-items-center">
            <i class="bi bi-person-fill text-primary fs-4 me-3"></i>
            <div>
              <small class="text-muted d-block">Current session</small>
              <strong><?= $currentSessionLogin ? date('d M Y, h:i A', strtotime($currentSessionLogin)) : 'Not recorded' ?></strong>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="login-info-item">
          <div class="d-flex align-items-center">
            <i class="bi bi-clock-history text-success fs-4 me-3"></i>
            <div>
              <small class="text-muted d-block">Previous login</small>
              <strong><?= $actualLastLogin ? date('d M Y, h:i A', strtotime($actualLastLogin)) : 'First login' ?></strong>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Main Content -->
<div class="container mb-5">
  <!-- Stats Cards -->
  <div class="row g-4 mb-4">
    <div class="col-md-4">
      <div class="stat-card text-center p-4">
        <i class="bi bi-download stat-icon"></i>
        <h3 class="mb-1"><?= $downloadCount ?></h3>
        <p class="mb-0 text-muted">Total Downloads</p>
        <a href="my_down.php" class="stretched-link"></a>
      </div>
    </div>
    <div class="col-md-4">
      <div class="stat-card text-center p-4">
        <i class="bi bi-bell stat-icon"></i>
        <h3 class="mb-1"><?= $unreadCount ?></h3>
        <p class="mb-0 text-muted">Unread Notifications</p>
        <a href="notifications.php" class="stretched-link"></a>
      </div>
    </div>
    <div class="col-md-4">
      <div class="stat-card text-center p-4">
        <i class="bi bi-star stat-icon"></i>
        <h3 class="mb-1"><?= $reviewCount ?></h3>
        <p class="mb-0 text-muted">Your Reviews</p>
        <a href="myreview.php" class="stretched-link"></a>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <!-- Recent Downloads -->
    <div class="col-lg-6">
      <div class="content-card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0"><i class="bi bi-download text-primary me-2"></i>Recent Downloads</h5>
          <a href="my_down.php" class="btn btn-sm btn-outline-primary">View All</a>
        </div>
        <div class="card-body">
          <?php if ($recentDownloads->num_rows > 0): ?>
            <div class="list-group list-group-flush">
              <?php while($download = $recentDownloads->fetch_assoc()): ?>
                <div class="list-group-item border-0 px-0 py-3">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <h6 class="mb-1"><?= htmlspecialchars($download['title']) ?></h6>
                      <span class="material-badge"><?= htmlspecialchars($download['type_name']) ?></span>
                    </div>
                    <small class="text-muted"><?= date("d M, H:i", strtotime($download['downloaded_at'])) ?></small>
                  </div>
                </div>
              <?php endwhile; ?>
            </div>
          <?php else: ?>
            <div class="text-center py-4 text-muted">
              <i class="bi bi-download display-5 opacity-25"></i>
              <p class="mt-3 mb-0">No downloads yet</p>
              <a href="mat.php" class="btn btn-primary mt-3">Browse Materials</a>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Recent Notifications -->
    <div class="col-lg-6">
      <div class="content-card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0"><i class="bi bi-bell text-primary me-2"></i>Recent Notifications</h5>
          <a href="notifications.php" class="btn btn-sm btn-outline-primary">View All</a>
        </div>
        <div class="card-body">
          <?php if ($recentNotifications->num_rows > 0): ?>
            <div class="list-group list-group-flush">
              <?php while($notification = $recentNotifications->fetch_assoc()): ?>
                <div class="list-group-item border-0 px-0 py-3 <?= $notification['is_read'] ? '' : 'bg-light' ?>">
                  <div class="d-flex justify-content-between align-items-start">
                    <div>
                      <p class="mb-1"><?= htmlspecialchars($notification['message']) ?></p>
                      <?php if (!$notification['is_read']): ?>
                        <span class="badge bg-primary">New</span>
                      <?php endif; ?>
                    </div>
                    <small class="text-muted"><?= date("d M, H:i", strtotime($notification['created_at'])) ?></small>
                  </div>
                </div>
              <?php endwhile; ?>
            </div>
          <?php else: ?>
            <div class="text-center py-4 text-muted">
              <i class="bi bi-bell display-5 opacity-25"></i>
              <p class="mt-3 mb-0">No notifications yet</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Popular Materials & Recent Activities -->
  <div class="row g-4 mt-4">
    <!-- Popular Materials -->
    <div class="col-lg-6">
      <div class="content-card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0"><i class="bi bi-graph-up text-primary me-2"></i>Popular Materials</h5>
          <a href="mat.php" class="btn btn-sm btn-outline-primary">Browse All</a>
        </div>
        <div class="card-body">
          <?php if ($popularMaterials->num_rows > 0): ?>
            <div class="table-responsive">
              <table class="table table-hover align-middle">
                <thead>
                  <tr>
                    <th>Material</th>
                    <th class="text-end">Downloads</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <?php while($material = $popularMaterials->fetch_assoc()): ?>
                    <tr>
                      <td><?= htmlspecialchars($material['title']) ?></td>
                      <td class="text-end"><span class="badge bg-primary rounded-pill"><?= $material['download_count'] ?></span></td>
                    </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="text-center py-4 text-muted">
              <i class="bi bi-file-earmark-text display-5 opacity-25"></i>
              <p class="mt-3 mb-0">No materials available</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Recent Activities -->
    <div class="col-lg-6">
      <div class="content-card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0"><i class="bi bi-activity text-primary me-2"></i>Recent Activities</h5>
          <a href="my_act.php" class="btn btn-sm btn-outline-primary">View All</a>
        </div>
        <div class="card-body">
          <?php 
          $activities = $conn->query("
              SELECT * FROM user_activities 
              WHERE user_id = $userId 
              ORDER BY activity_time DESC 
              LIMIT 5
          ");
          if ($activities->num_rows > 0): ?>
            <ul class="list-unstyled">
              <?php while($activity = $activities->fetch_assoc()): ?>
                <li class="activity-item mb-3">
                  <div class="d-flex justify-content-between align-items-start">
                    <div>
                      <p class="mb-1"><?= htmlspecialchars($activity['description']) ?></p>
                      <small class="text-muted"><?= date("d M Y", strtotime($activity['activity_time'])) ?></small>
                    </div>
                    <small class="text-muted"><?= date("h:i A", strtotime($activity['activity_time'])) ?></small>
                  </div>
                </li>
              <?php endwhile; ?>
            </ul>
          <?php else: ?>
            <div class="text-center py-4 text-muted">
              <i class="bi bi-activity display-5 opacity-25"></i>
              <p class="mt-3 mb-0">No activities yet</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include '../include/footer.php'; ?>   
<script src="../Bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>