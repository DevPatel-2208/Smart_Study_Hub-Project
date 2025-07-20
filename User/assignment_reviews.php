<?php
session_start();
include '../db.php';

// Check if material_id is provided
$materialId = isset($_GET['material_id']) ? intval($_GET['material_id']) : 0;

if (!$materialId) {
    header("Location: assignments.php");
    exit();
}

// Fetch assignment details
$stmt = $conn->prepare("
    SELECT sm.*, s.name AS subject_name, se.name AS semester_name,
    (SELECT COUNT(*) FROM downloads WHERE material_id = sm.id) AS download_count,
    (SELECT AVG(rating) FROM file_reviews WHERE material_id = sm.id) AS avg_rating,
    (SELECT COUNT(*) FROM file_reviews WHERE material_id = sm.id) AS review_count
    FROM study_materials sm 
    JOIN subjects s ON sm.subject_id = s.id 
    JOIN semesters se ON sm.semester_id = se.id 
    WHERE sm.id = ?
");
$stmt->bind_param("i", $materialId);
$stmt->execute();
$assignment = $stmt->get_result()->fetch_assoc();

if (!$assignment) {
    header("Location: assignments.php");
    exit();
}

// Fetch all reviews for this material
$reviewStmt = $conn->prepare("
    SELECT fr.*, u.name AS user_name, u.image 
    FROM file_reviews fr 
    JOIN users u ON fr.user_id = u.id 
    WHERE fr.material_id = ? 
    ORDER BY fr.created_at DESC
");
$reviewStmt->bind_param("i", $materialId);
$reviewStmt->execute();
$reviews = $reviewStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Function to generate star ratings
function generateStars($rating) {
    $fullStars = floor($rating);
    $halfStar = ($rating - $fullStars) >= 0.5 ? 1 : 0;
    $emptyStars = 5 - $fullStars - $halfStar;
    
    $html = '';
    for ($i = 0; $i < $fullStars; $i++) {
        $html .= '<i class="bi bi-star-fill text-warning"></i>';
    }
    if ($halfStar) {
        $html .= '<i class="bi bi-star-half text-warning"></i>';
    }
    for ($i = 0; $i < $emptyStars; $i++) {
        $html .= '<i class="bi bi-star text-warning"></i>';
    }
    return $html;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reviews - <?= htmlspecialchars($assignment['title']) ?> | SmartStudy Hub</title>
  <link rel="stylesheet" href="../Bootstrap/css/bootstrap.min.css"> <link rel="icon" type="image/x-icon" href="../uploads/unnamed.png">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
  <style>
    :root {
      --primary-color: #6a11cb;
      --secondary-color: #2575fc;
      --dark-color: #343a40;
      --light-color: #f8f9fa;
    }
    
    body {
      background-color: var(--light-color);
      color: var(--dark-color);
    }
    
    .header-section {
      background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
      color: white;
      padding: 3rem 0;
      border-radius: 0 0 20px 20px;
      margin-bottom: 3rem;
      box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }
    
    .assignment-title {
      font-size: 2.2rem;
      font-weight: 700;
      margin-bottom: 1rem;
    }
    
    .assignment-meta {
      font-size: 1.1rem;
      opacity: 0.9;
    }
    
    .stats-badge {
      background: rgba(255,255,255,0.2);
      border-radius: 50px;
      padding: 8px 15px;
      font-size: 0.9rem;
      margin-right: 10px;
    }
    
    .stats-icon {
      margin-right: 5px;
    }
    
    .review-card {
      background: white;
      border-radius: 15px;
      padding: 25px;
      margin-bottom: 25px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.05);
      border-left: 5px solid var(--primary-color);
      transition: transform 0.3s ease;
    }
    
    .review-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }
    
    .user-avatar {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      object-fit: cover;
      border: 3px solid white;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .user-name {
      font-size: 1.3rem;
      font-weight: 600;
      margin-top: 15px;
    }
    
    .review-date {
      font-size: 0.9rem;
      color: #6c757d;
    }
    
    .review-rating {
      font-size: 1.5rem;
      margin: 15px 0;
    }
    
    .review-content {
      font-size: 1.1rem;
      line-height: 1.6;
    }
    
    .back-btn {
      background: white;
      color: var(--primary-color);
      border: none;
      border-radius: 50px;
      padding: 10px 25px;
      font-weight: 600;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
      transition: all 0.3s;
    }
    
    .back-btn:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    }
    
    .no-reviews {
      background: white;
      border-radius: 15px;
      padding: 3rem;
      text-align: center;
      box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }
    
    .no-reviews-icon {
      font-size: 4rem;
      color: #6c757d;
      margin-bottom: 1rem;
    }
    
    @media (max-width: 768px) {
      .assignment-title {
        font-size: 1.8rem;
      }
      
      .assignment-meta {
        font-size: 1rem;
      }
      
      .user-avatar {
        width: 70px;
        height: 70px;
      }
      
      .review-card {
        padding: 20px;
      }
    }
  </style>
</head>
<body>
  <div class="header-section">
    <div class="container">
      <div class="row align-items-center">
        <div class="col-md-8">
          <h1 class="assignment-title"><?= htmlspecialchars($assignment['title']) ?></h1>
          <div class="assignment-meta">
            <span><i class="bi bi-book stats-icon"></i> <?= htmlspecialchars($assignment['subject_name']) ?></span> • 
            <span><i class="bi bi-collection stats-icon"></i> <?= htmlspecialchars($assignment['semester_name']) ?></span>
          </div>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
          <span class="stats-badge">
            <i class="bi bi-star-fill"></i> 
            <?= number_format($assignment['avg_rating'] ?? 0, 1) ?> (<?= $assignment['review_count'] ?> reviews)
          </span>
          <span class="stats-badge">
            <i class="bi bi-download"></i> <?= $assignment['download_count'] ?> downloads
          </span>
        </div>
      </div>
    </div>
  </div>

  <div class="container">
    <div class="mb-4">
      <a href="user_ass.php" class="back-btn">
        <i class="bi bi-arrow-left"></i> Back to Assignments
      </a>
    </div>
    
    <h2 class="mb-4">
      <i class="bi bi-chat-square-text"></i> 
      <?= $assignment['review_count'] ?> Reviews
      <?php if ($assignment['avg_rating']): ?>
        <span class="text-warning ms-2">
          <?= generateStars($assignment['avg_rating']) ?>
        </span>
      <?php endif; ?>
    </h2>
    
    <?php if (count($reviews) > 0): ?>
      <div class="row">
        <?php foreach ($reviews as $review): ?>
          <div class="col-lg-12 mb-4">
            <div class="review-card">
              <div class="row">
                <div class="col-md-2 text-center">
                  <img src="../uploads/profiles/<?= htmlspecialchars($review['image'] ?? 'default.png') ?>" class="user-avatar">
                  <div class="user-name"><?= htmlspecialchars($review['user_name']) ?></div>
                  <div class="review-date">
                   <b>Date:</b> <?= date('d M Y', strtotime($review['created_at'])) ?>
                  </div>
                </div>
                <div class="col-md-10">
                  <div class="review-rating">
                   <b>Rating:</b>   <?= generateStars($review['rating']) ?>
                  </div>
                  <div class="review-content">
                  <b>Response:</b>  <?= nl2br(htmlspecialchars($review['review'])) ?>
                  </div>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="no-reviews">
        <div class="no-reviews-icon">
          <i class="bi bi-chat-square-text"></i>
        </div>
        <h3>No Reviews Yet</h3>
        <p class="text-muted">Be the first to review this assignment after downloading it.</p>
        <a href="assignments.php" class="btn btn-primary mt-3">
          <i class="bi bi-arrow-left"></i> Back to Assignments
        </a>
      </div>
    <?php endif; ?>
  </div>

  <script src="../Bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>