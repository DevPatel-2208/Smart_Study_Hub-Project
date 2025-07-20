<?php
session_start();
include '../db.php';

$userId = $_SESSION['user']['id'] ?? 0;

$sql = "SELECT r.*, sm.title, sm.file_name, sm.uploaded_at, 
               s.name AS subject_name,
               sem.name AS semester_name,
               mt.type_name AS material_type
        FROM file_reviews r
        JOIN study_materials sm ON r.material_id = sm.id
        JOIN subjects s ON sm.subject_id = s.id
        JOIN semesters sem ON sm.semester_id = sem.id
        JOIN material_types mt ON sm.material_type_id = mt.id
        WHERE r.user_id = ?
        ORDER BY r.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Reviews - SmartStudyHub</title>
  <link rel="stylesheet" href="../Bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <style>
    /* Add this to your existing styles */
    .header-actions {
      position: absolute;
      right: 20px;
      top: 50%;
      transform: translateY(-50%);
    }
    @media (max-width: 768px) {
      .header-actions {
        position: static;
        transform: none;
        margin-top: 15px;
        text-align: center;
      }
    }
    :root {
      --primary: #6a11cb;
      --secondary: #2575fc;
      --header-gradient: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    }
    
    body {
      background-color: #f8f9fa;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    .page-header {
      background: var(--header-gradient);
      color: white;
      padding: 1.5rem;
      border-radius: 0 0 15px 15px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.1);
      margin-bottom: 2rem;
    }
    
    .page-header h2 {
      font-weight: 600;
      margin-bottom: 0;
    }
    
    .review-card {
      border-radius: 12px;
      border: none;
      box-shadow: 0 5px 15px rgba(0,0,0,0.05);
      transition: all 0.3s ease;
      border-left: 4px solid var(--primary);
      height: 100%;
    }
    
    .review-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }
    
    .card-title {
      font-weight: 600;
      color: var(--primary);
    }
    
    .material-badge {
      background: var(--header-gradient);
      font-size: 0.7rem;
      font-weight: 500;
    }
    
    .star-filled {
      color: #ffc107;
    }
    
    .star-empty {
      color: #e0e0e0;
    }
    
    .review-meta {
      font-size: 0.85rem;
      color: #6c757d;
    }
    
    .review-meta i {
      width: 20px;
      text-align: center;
    }
    
    .review-comment {
      background: #f8f9fa;
      border-radius: 8px;
      padding: 10px;
      font-size: 0.9rem;
    }
    
    .btn-download {
      background: var(--header-gradient);
      color: white;
      border: none;
      font-weight: 500;
    }
    
    .btn-download:hover {
      color: white;
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(106, 17, 203, 0.3);
    }
    
    .empty-state {
      text-align: center;
      padding: 3rem;
      background: white;
      border-radius: 12px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }
    
    .empty-state i {
      font-size: 3rem;
      color: #e0e0e0;
      margin-bottom: 1rem;
    }
    
    @media (max-width: 768px) {
      .page-header {
        border-radius: 0;
        margin-bottom: 1rem;
      }
      
      .review-card {
        margin-bottom: 1rem;
      }
      
      .card-title {
        font-size: 1.1rem;
      }
    }
  </style>
</head>
<body>
  <header class="page-header position-relative">
    <div class="container">
      <div class="d-flex align-items-center">
        <i class="bi bi-star-fill me-3" style="font-size: 1.8rem;"></i>
        <div>
          <h2 class="mb-1">My Review History</h2>
          <p class="mb-0">All your submitted material reviews</p>
        </div>
      </div>
      <div class="header-actions">
        <a href="dashboard.php" class="btn btn-light btn-sm">
          <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
        </a>
      </div>
    </div>
  </header>

  <div class="container py-4">
    <?php if ($result->num_rows > 0): ?>
      <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <?php while ($row = $result->fetch_assoc()): ?>
          <div class="col">
            <div class="card review-card h-100">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                  <h5 class="card-title mb-0">
                    <i class="bi bi-file-earmark-text me-2"></i>
                    <?= htmlspecialchars($row['title']) ?>
                  </h5>
                  <span class="badge material-badge rounded-pill"><?= htmlspecialchars($row['material_type']) ?></span>
                </div>
                
                <div class="review-meta mb-3">
                  <p class="mb-1"><i class="bi bi-book me-1"></i> <?= htmlspecialchars($row['subject_name']) ?></p>
                  <p class="mb-1"><i class="bi bi-calendar3 me-1"></i> <?= htmlspecialchars($row['semester_name']) ?></p>
                  <p class="mb-2"><i class="bi bi-clock me-1"></i> <?= date("d M Y, h:i A", strtotime($row['created_at'])) ?></p>
                  
                  <div class="mb-3">
                    <span class="me-2">Rating:</span>
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                      <i class="bi <?= $i <= $row['rating'] ? 'bi-star-fill star-filled' : 'bi-star star-empty' ?>"></i>
                    <?php endfor; ?>
                  </div>
                </div>
                
                <?php if (!empty($row['review'])): ?>
                  <div class="review-comment mb-3">
                    <strong><i class="bi bi-chat-left-text me-1"></i> Comment:</strong>
                    <p class="mb-0"><?= nl2br(htmlspecialchars($row['review'])) ?></p>
                  </div>
                <?php endif; ?>
                
                <a href="../uploads/<?= htmlspecialchars($row['file_name']) ?>" class="btn btn-download btn-sm w-100 mt-auto" download>
                  <i class="bi bi-download me-1"></i> Download Material
                </a>
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    <?php else: ?>
      <div class="empty-state">
        <i class="bi bi-star"></i>
        <h4 class="text-muted">No Reviews Yet</h4>
        <p class="text-muted">You haven't reviewed any study materials yet.</p>
        <a href="../study_materials.php" class="btn btn-primary mt-2">
          <i class="bi bi-book me-1"></i> Browse Materials
        </a>
      </div>
    <?php endif; ?>
  </div>

  <script src="../Bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>