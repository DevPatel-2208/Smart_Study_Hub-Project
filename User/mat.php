<?php
session_start();
include '../db.php';
include 'material_helper.php'; 
include 'activity_helper.php';// ✅ helper file include

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user']);
$userId = $isLoggedIn ? $_SESSION['user']['id'] : null;

// Handle download tracking
if (isset($_GET['download']) && $isLoggedIn) {
    $materialId = intval($_GET['download']);
    
    // Record download
    $stmt = $conn->prepare("INSERT INTO downloads (user_id, material_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $userId, $materialId);
    $stmt->execute();
    
    // Update download summary
    $stmt = $conn->prepare("
        INSERT INTO downloads_summary (material_id, download_count) 
        VALUES (?, 1) 
        ON DUPLICATE KEY UPDATE download_count = download_count + 1
    ");
    $stmt->bind_param("i", $materialId);
    $stmt->execute();
    
    // Log download activity
// Log download activity with title
$titleStmt = $conn->prepare("SELECT title FROM study_materials WHERE id = ?");
$titleStmt->bind_param("i", $materialId);
$titleStmt->execute();
$titleStmt->bind_result($materialTitle);
$titleStmt->fetch();
$titleStmt->close();

logActivity($conn, $userId, 'download', $materialTitle, 'Downloaded Material');
  
    // Redirect to actual file download
    $fileStmt = $conn->prepare("SELECT file_name FROM study_materials WHERE id = ?");
    $fileStmt->bind_param("i", $materialId);
    $fileStmt->execute();
    $fileResult = $fileStmt->get_result();
    if ($fileResult->num_rows > 0) {
        $file = $fileResult->fetch_assoc();
        header("Location: ../uploads/" . $file['file_name']);
        exit();
    }
}

// Fetch all semesters
$semResult = $conn->query("SELECT id, name FROM semesters ORDER BY name ASC");
$semesters = $semResult->fetch_all(MYSQLI_ASSOC);

// Handle filters
$selectedSemester = isset($_GET['semester']) ? intval($_GET['semester']) : 0;
$selectedSubject = isset($_GET['subject']) ? intval($_GET['subject']) : 0;

// Fetch subjects for selected semester
$subjectQuery = "SELECT id, name FROM subjects WHERE semester_id = ?";
$subjectStmt = $conn->prepare($subjectQuery);
$subjectStmt->bind_param("i", $selectedSemester);
$subjectStmt->execute();
$subjects = $subjectStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch materials with download data
$materials = [];
if ($selectedSemester && $selectedSubject) {
    $visibleCondition = getVisibleMaterialCondition($userId); // ✅ helper use

    $query = "
        SELECT sm.*, s.name AS subject_name, se.name AS semester_name,
        (SELECT COUNT(*) FROM downloads WHERE material_id = sm.id) AS download_count,
        (SELECT COUNT(*) FROM downloads WHERE material_id = sm.id AND user_id = ?) AS user_downloaded
        FROM study_materials sm 
        JOIN subjects s ON sm.subject_id = s.id 
        JOIN semesters se ON sm.semester_id = se.id 
        WHERE sm.material_type_id = 4
        AND sm.semester_id = ? AND sm.subject_id = ?
        AND $visibleCondition
        ORDER BY sm.uploaded_at DESC
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("iii", $userId, $selectedSemester, $selectedSubject);
    $stmt->execute();
    $materials = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<?php include '../include/header.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Study Materials - SmartStudy Hub</title>
  <link rel="stylesheet" href="../Bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
  <style>
    :root {
      --primary-color: #6a11cb;
      --secondary-color: #2575fc;
    }
    
    .main-content {
      background-color: #f8f9fa;
      min-height: calc(100vh - 120px);
      padding-top: 20px;
      padding-bottom: 40px;
    }
    
    .page-header {
      color: var(--primary-color);
      border-bottom: 2px solid var(--primary-color);
      padding-bottom: 10px;
      margin-bottom: 25px;
    }
    
    .filter-box {
      background: white; 
      padding: 20px; 
      border-radius: 12px; 
      box-shadow: 0 0 15px rgba(0,0,0,0.08);
      margin-bottom: 25px;
    }
    
    .material-card {
      background: #fff; 
      padding: 20px; 
      border-radius: 10px; 
      box-shadow: 0 2px 15px rgba(0,0,0,0.05);
      height: 100%;
      transition: transform 0.3s ease;
      border-left: 4px solid var(--secondary-color);
      margin-bottom: 25px;
    }
    
    .material-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }
    
    .material-title {
      font-size: 1.2rem; 
      font-weight: 600;
      color: var(--primary-color);
    }
    
    .stats-icon {
      font-size: 0.9rem;
      margin-right: 5px;
      color: var(--primary-color);
    }
    
    .download-btn {
      background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
      color: white;
      border: none;
      font-weight: 500;
    }
    
    .download-btn:hover {
      color: white;
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(106, 17, 203, 0.3);
    }
    
    /* Mobile specific styles */
    @media (max-width: 767.98px) {
      .main-content {
        padding-top: 15px;
        padding-bottom: 30px;
      }
      
      .filter-box {
        padding: 15px;
        margin-bottom: 20px;
      }
      
      .material-card {
        padding: 15px;
      }
      
      .material-title {
        font-size: 1.1rem;
      }
      
      .page-header h2 {
        font-size: 1.5rem;
      }
      
      .download-btn {
        padding: 8px;
        font-size: 0.9rem;
      }
    }
  </style>
</head>

<body>
  <div class="main-content">
    <div class="container">
      <div class="page-header">
        <h2><i class="bi bi-collection"></i> Study Materials</h2>
        <p class="text-muted">Find and download study materials (Question Banks, Notes, Solutions)</p>
      </div>

      <form method="GET" class="row g-3 filter-box">
        <div class="col-md-6">
          <label class="form-label fw-semibold"><i class="bi bi-collection me-2"></i>Semester</label>
          <select name="semester" class="form-select" onchange="this.form.submit()">
            <option value="">-- Select Semester --</option>
            <?php foreach ($semesters as $sem): ?>
              <option value="<?= $sem['id'] ?>" <?= $sem['id'] == $selectedSemester ? 'selected' : '' ?>><?= htmlspecialchars($sem['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold"><i class="bi bi-book me-2"></i>Subject</label>
          <select name="subject" class="form-select" onchange="this.form.submit()">
            <option value="">-- Select Subject --</option>
            <?php foreach ($subjects as $sub): ?>
              <option value="<?= $sub['id'] ?>" <?= $sub['id'] == $selectedSubject ? 'selected' : '' ?>><?= htmlspecialchars($sub['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </form>

      <?php if ($selectedSemester && $selectedSubject && count($materials) > 0): ?>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
          <?php foreach ($materials as $m): ?>
            <div class="col">
              <div class="material-card">
                <div class="d-flex justify-content-between align-items-start mb-3">
                  <h5 class="material-title mb-0">
                    <i class="bi bi-file-earmark-text me-2"></i><?= htmlspecialchars($m['title']) ?>
                  </h5>
                  <span class="badge bg-primary">Material</span>
                </div>
                
                <p class="mb-2"><i class="bi bi-book stats-icon"></i> <?= htmlspecialchars($m['subject_name']) ?></p>
                <p class="mb-2"><i class="bi bi-calendar stats-icon"></i> <?= date('d M Y, h:i A', strtotime($m['uploaded_at'])) ?></p>
                
                <div class="d-flex justify-content-between mb-3">
                  <div>
                    <i class="bi bi-download stats-icon"></i> 
                    <span><?= $m['download_count'] ?> downloads</span>
                    <?php if ($m['user_downloaded'] > 0): ?>
                      <span class="badge bg-success ms-2">Downloaded</span>
                    <?php endif; ?>
                  </div>
                </div>
                
                <a href="?semester=<?= $selectedSemester ?>&subject=<?= $selectedSubject ?>&download=<?= $m['id'] ?>" class="btn download-btn w-100">
                  <i class="bi bi-download"></i> Download Material
                </a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php elseif ($selectedSemester && $selectedSubject): ?>
        <div class="alert alert-info text-center py-4">
          <i class="bi bi-info-circle-fill fs-4"></i>
          <h5 class="mt-2">No study materials found</h5>
          <p class="mb-0">There are no study materials (Question Banks/Notes/Solutions) available for the selected subject.</p>
        </div>
      <?php elseif ($selectedSemester || $selectedSubject): ?>
        <div class="alert alert-warning text-center py-4">
          <i class="bi bi-exclamation-triangle-fill fs-4"></i>
          <h5 class="mt-2">Please select both semester and subject</h5>
          <p class="mb-0">You need to select both semester and subject to view materials.</p>
        </div>
      <?php else: ?>
        <div class="alert alert-secondary text-center py-4">
          <i class="bi bi-collection fs-4"></i>
          <h5 class="mt-2">Get started with study materials</h5>
          <p class="mb-0">Select your semester and subject to browse available materials (Question Banks, Notes, Solutions).</p>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <?php include '../include/footer.php'; ?>

  <script src="../Bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>