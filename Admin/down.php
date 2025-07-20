<?php
session_start();
include '../db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$selectedSemester = isset($_GET['semester']) ? intval($_GET['semester']) : 0;
$selectedType = isset($_GET['material_type']) ? intval($_GET['material_type']) : 0;

$semesters = $conn->query("SELECT id, name FROM semesters ORDER BY name ASC");
$materialTypes = $conn->query("SELECT id, type_name FROM material_types ORDER BY type_name ASC");

$data = [];
if ($selectedSemester && $selectedType) {
    $userStmt = $conn->prepare("SELECT DISTINCT u.id, u.name 
        FROM downloads d 
        JOIN users u ON d.user_id = u.id 
        JOIN study_materials sm ON d.material_id = sm.id 
        WHERE sm.semester_id = ? AND sm.material_type_id = ?
        ORDER BY u.name ASC");
    $userStmt->bind_param("ii", $selectedSemester, $selectedType);
    $userStmt->execute();
    $users = $userStmt->get_result()->fetch_all(MYSQLI_ASSOC);

    foreach ($users as $user) {
        $stmt = $conn->prepare("SELECT sm.title, d.downloaded_at 
            FROM downloads d 
            JOIN study_materials sm ON d.material_id = sm.id 
            WHERE d.user_id = ? AND sm.semester_id = ? AND sm.material_type_id = ?
            ORDER BY d.downloaded_at DESC");
        $stmt->bind_param("iii", $user['id'], $selectedSemester, $selectedType);
        $stmt->execute();
        $downloads = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $data[] = [
            'user' => $user['name'],
            'downloads' => $downloads
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Grouped Download Report</title>
  <link rel="stylesheet" href="../Bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
  <style>
    :root {
      --sidebar-width: 250px;
    }
    
    body {
      background-color: #f8f9fa;
      overflow-x: hidden;
    }
    
    .main-content {
      margin-left: var(--sidebar-width);
      padding: -20px;
      transition: all 0.3s;
    }
    
    @media (max-width: 768px) {
      .main-content {
        margin-left: 0;
      }
      
      .sidebar-collapsed .main-content {
        margin-left: 0;
      }
    }
    
    .user-section {
      background: #fff;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
      margin-bottom: 25px;
      padding: 20px;
    }
    
    .user-title {
      font-weight: bold;
      font-size: 18px;
      margin-bottom: 10px;
      color: #0d6efd;
    }
    
    .card-header {
      background-color: #f8f9fa;
      border-bottom: 1px solid #eee;
    }
    
    @media (max-width: 576px) {
      .user-title {
        font-size: 16px;
      }
      
      .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
      }
      
      .main-content {
        padding: 15px;
      }
    }
    
    /* Print styles */
    @media print {
      .no-print {
        display: none !important;
      }
      
      body, .main-content {
        margin: 0;
        padding: 0;
        background: white;
      }
      
      .user-section {
        box-shadow: none;
        border: 1px solid #ddd;
        page-break-inside: avoid;
      }
    }
  </style>
</head>
<body>
<?php include 'ad.php'; ?>

<div class="main-content">
  <div class="container-fluid">
    <!-- Top Bar with Toggle Button (for mobile) -->

    <div class="row mb-4">
      <div class="col-12">
        <div class="card no-print">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <h4 class="card-title mb-0">
                <i class="bi bi-journal-bookmark"></i> Grouped Download Report
              </h4>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row no-print">
      <div class="col-12">
        <div class="card">
          <div class="card-body">
            <form method="GET" class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Select Semester</label>
                <select name="semester" class="form-select" required>
                  <option value="">-- Select Semester --</option>
                  <?php while($s = $semesters->fetch_assoc()): ?>
                    <option value="<?= $s['id'] ?>" <?= $selectedSemester == $s['id'] ? 'selected' : '' ?>>
                      <?= htmlspecialchars($s['name']) ?>
                    </option>
                  <?php endwhile; ?>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">Select Material Type</label>
                <select name="material_type" class="form-select" required>
                  <option value="">-- Select Material Type --</option>
                  <?php while($mt = $materialTypes->fetch_assoc()): ?>
                    <option value="<?= $mt['id'] ?>" <?= $selectedType == $mt['id'] ? 'selected' : '' ?>>
                      <?= htmlspecialchars($mt['type_name']) ?>
                    </option>
                  <?php endwhile; ?>
                </select>
              </div>
              <div class="col-12 text-end">
                <button class="btn btn-success"><i class="bi bi-funnel-fill"></i> Filter</button>
                <?php if ($selectedSemester && $selectedType): ?>
                  <button type="button" class="btn btn-primary ms-2" onclick="window.print()">
                    <i class="bi bi-printer-fill"></i> Print
                  </button>
                <?php endif; ?>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <?php if ($selectedSemester && $selectedType): ?>
      <?php if (!empty($data)): ?>
        <?php foreach ($data as $entry): ?>
          <div class="row">
            <div class="col-12">
              <div class="user-section">
                <div class="user-title"><i class="bi bi-person-fill"></i> <?= htmlspecialchars($entry['user']) ?></div>
                <?php if (count($entry['downloads']) > 0): ?>
                  <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                      <thead>
                        <tr>
                          <th>#</th>
                          <th>File Title</th>
                          <th>Date</th>
                          <th>Time</th>
                          <th>Day</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php $i=1; foreach ($entry['downloads'] as $d): ?>
                          <?php
                            $date = date("d M Y", strtotime($d['downloaded_at']));
                            $time = date("h:i A", strtotime($d['downloaded_at']));
                            $day = date("l", strtotime($d['downloaded_at']));
                          ?>
                          <tr>
                            <td><?= $i++ ?></td>
                            <td><?= htmlspecialchars($d['title']) ?></td>
                            <td><?= $date ?></td>
                            <td><?= $time ?></td>
                            <td><?= $day ?></td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                <?php else: ?>
                  <p class="text-muted">No downloads found.</p>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="row">
          <div class="col-12">
            <div class="alert alert-info">
              <i class="bi bi-info-circle-fill"></i> No download records found for the selected criteria.
            </div>
          </div>
        </div>
      <?php endif; ?>
    <?php else: ?>
      <div class="row">
        <div class="col-12">
          <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle-fill"></i> Please select both semester and material type to view the report.
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<script src="../Bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
  // Sidebar toggle functionality
  document.getElementById('sidebarToggle').addEventListener('click', function() {
    document.body.classList.toggle('sidebar-collapsed');
  });
  
  // Auto-close sidebar on mobile when clicking a link
  if (window.innerWidth < 768) {
    document.querySelectorAll('.sidebar-nav a').forEach(link => {
      link.addEventListener('click', () => {
        document.body.classList.add('sidebar-collapsed');
      });
    });
  }
</script>
</body>
</html>