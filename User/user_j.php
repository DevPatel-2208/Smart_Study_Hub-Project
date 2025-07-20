<?php
session_start();
include '../db.php';
include 'material_helper.php'; 
include 'activity_helper.php';// ✅ Helper added

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user']);
$userId = $isLoggedIn ? $_SESSION['user']['id'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review']) && $isLoggedIn) {
    $journalId = intval($_POST['journal_id']);
    $rating = intval($_POST['rating']);
    $reviewText = trim($_POST['review_text']);

    if ($rating >= 1 && $rating <= 5 && !empty($reviewText)) {

        // 1️⃣ Insert Review
        $stmt = $conn->prepare("INSERT INTO file_reviews (material_id, user_id, rating, review) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiis", $journalId, $userId, $rating, $reviewText);
        $stmt->execute();

        // 2️⃣ Fetch Journal Title
        $titleStmt = $conn->prepare("SELECT title FROM study_materials WHERE id = ?");
        $titleStmt->bind_param("i", $journalId);
        $titleStmt->execute();
        $titleStmt->bind_result($materialTitle);
        $titleStmt->fetch();
        $titleStmt->close();

        // 3️⃣ Log Review Activity
        logActivity($conn, $userId, 'review', $materialTitle, "Rated $rating stars on journal");

        // 4️⃣ Redirect
        header("Location: ".$_SERVER['PHP_SELF']."?semester=".$_GET['semester']."&subject=".$_GET['subject']);
        exit();
    }
}


// Handle download tracking
if (isset($_GET['download']) && $isLoggedIn) {
    $journalId = intval($_GET['download']);

    // 1️⃣ Fetch Journal Title
    $titleStmt = $conn->prepare("SELECT title FROM study_materials WHERE id = ?");
    $titleStmt->bind_param("i", $journalId);
    $titleStmt->execute();
    $titleStmt->bind_result($materialTitle);
    $titleStmt->fetch();
    $titleStmt->close();

    // 2️⃣ Record Download
    $stmt = $conn->prepare("INSERT INTO downloads (user_id, material_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $userId, $journalId);
    $stmt->execute();

    $stmt = $conn->prepare("
        INSERT INTO downloads_summary (material_id, download_count) 
        VALUES (?, 1) 
        ON DUPLICATE KEY UPDATE download_count = download_count + 1
    ");
    $stmt->bind_param("i", $journalId);
    $stmt->execute();

    // 3️⃣ Log Activity
    logActivity($conn, $userId, 'download', $materialTitle, 'Downloaded Journal');

    // 4️⃣ Proceed to file
    $fileStmt = $conn->prepare("SELECT file_name FROM study_materials WHERE id = ?");
    $fileStmt->bind_param("i", $journalId);
    $fileStmt->execute();
    $fileResult = $fileStmt->get_result();
    if ($fileResult->num_rows > 0) {
        $file = $fileResult->fetch_assoc();
        header("Location: ../uploads/" . $file['file_name']);
        exit();
    }
}

// Fetch semesters
$semResult = $conn->query("SELECT id, name FROM semesters ORDER BY name ASC");
$semesters = $semResult->fetch_all(MYSQLI_ASSOC);

// Filters
$selectedSemester = isset($_GET['semester']) ? intval($_GET['semester']) : 0;
$selectedSubject = isset($_GET['subject']) ? intval($_GET['subject']) : 0;

// Fetch subjects
$subjectStmt = $conn->prepare("SELECT id, name FROM subjects WHERE semester_id = ?");
$subjectStmt->bind_param("i", $selectedSemester);
$subjectStmt->execute();
$subjects = $subjectStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch journals with visibility condition
$journals = [];
if ($selectedSemester && $selectedSubject) {
    $visibleCondition = getVisibleMaterialCondition($userId); // ✅ use helper function here

    $query = "
        SELECT sm.*, s.name AS subject_name, se.name AS semester_name,
        (SELECT COUNT(*) FROM downloads WHERE material_id = sm.id) AS download_count,
        (SELECT AVG(rating) FROM file_reviews WHERE material_id = sm.id) AS avg_rating,
        (SELECT COUNT(*) FROM file_reviews WHERE material_id = sm.id) AS review_count,
        (SELECT COUNT(*) FROM downloads WHERE material_id = sm.id AND user_id = ?) AS user_downloaded
        FROM study_materials sm
        JOIN subjects s ON sm.subject_id = s.id
        JOIN semesters se ON sm.semester_id = se.id
        WHERE sm.material_type_id = (SELECT id FROM material_types WHERE type_name = 'Journal')
        AND sm.semester_id = ? AND sm.subject_id = ?
        AND $visibleCondition
        ORDER BY sm.uploaded_at DESC
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("iii", $userId, $selectedSemester, $selectedSubject);
    $stmt->execute();
    $journals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Star rating
function generateStars($rating, $editable = false, $name = 'rating') {
    $html = '';
    for ($i = 5; $i >= 1; $i--) {
        if ($editable) {
            $html .= '<input type="radio" id="'.$name.'_'.$i.'" name="'.$name.'" value="'.$i.'" '.($rating == $i ? 'checked' : '').'>
                      <label for="'.$name.'_'.$i.'" class="star-label"><i class="bi bi-star-fill"></i></label>';
        } else {
            $fullStars = floor($rating);
            $halfStar = ($rating - $fullStars) >= 0.5 ? 1 : 0;
            $emptyStars = 5 - $fullStars - $halfStar;
            
            for ($j = 0; $j < $fullStars; $j++) {
                $html .= '<i class="bi bi-star-fill text-warning"></i>';
            }
            if ($halfStar) {
                $html .= '<i class="bi bi-star-half text-warning"></i>';
            }
            for ($j = 0; $j < $emptyStars; $j++) {
                $html .= '<i class="bi bi-star text-warning"></i>';
            }
            break;
        }
    }
    return $html;
}
?>
<?php include '../include/header.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Journals - SmartStudy Hub</title>
  <link rel="stylesheet" href="../Bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
  <style>
    :root {
      --primary-color: #2575fc;
      --secondary-color: #6a11cb;
      --journal-color: #28a745;
    }
    
    .main-content {
      background-color: #f8f9fa;
      min-height: calc(100vh - 120px);
      padding-top: 20px;
      padding-bottom: 40px;
    }
    
    .page-header {
      color: var(--journal-color);
      border-bottom: 2px solid var(--journal-color);
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
    
    .journal-card {
      background: #fff; 
      padding: 20px; 
      border-radius: 10px; 
      box-shadow: 0 2px 15px rgba(0,0,0,0.05);
      height: 100%;
      transition: transform 0.3s ease;
      border-left: 4px solid var(--journal-color);
      margin-bottom: 25px;
    }
    
    .journal-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }
    
    .journal-title {
      font-size: 1.2rem; 
      font-weight: 600;
      color: var(--journal-color);
    }
    
    .stats-icon {
      font-size: 0.9rem;
      margin-right: 5px;
      color: var(--journal-color);
    }
    
    .review-card {
      background: #f8f9fa;
      border-radius: 8px;
      padding: 12px;
      margin-top: 15px;
      border-left: 3px solid var(--journal-color);
    }
    
    .review-author {
      font-weight: 600;
      font-size: 0.9rem;
    }
    
    .review-text {
      font-size: 0.85rem;
      color: #555;
    }
    
    .download-btn {
      background: linear-gradient(to right, var(--journal-color), #5cb85c);
      color: white;
      border: none;
      font-weight: 500;
    }
    
    .download-btn:hover {
      color: white;
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
    }
    
    /* Star rating styles */
    .star-rating {
      direction: rtl;
      display: inline-block;
    }
    
    .star-rating input[type="radio"] {
      display: none;
    }
    
    .star-rating label {
      color: #ddd;
      font-size: 1.2rem;
      padding: 0 2px;
      cursor: pointer;
      transition: color 0.2s;
    }
    
    .star-rating input[type="radio"]:checked ~ label,
    .star-rating label:hover,
    .star-rating label:hover ~ label {
      color: #ffc107;
    }
    
    .star-rating input[type="radio"]:checked + label {
      color: #ffc107;
    }
    
    /* Review form styles */
    .review-form {
      background: #f1f3f5;
      border-radius: 8px;
      padding: 15px;
      margin-top: 15px;
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
      
      .journal-card {
        padding: 15px;
      }
      
      .journal-title {
        font-size: 1.1rem;
      }
      
      .star-rating label {
        font-size: 1.5rem;
      }
      
      .review-card {
        padding: 10px;
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
        <h2><i class="bi bi-journals"></i> Browse Journals</h2>
        <p class="text-muted">Find and download research journals for your selected subject</p>
      </div>

      <form method="GET" class="row g-3 filter-box">
        <div class="col-md-6">
          <label class="form-label fw-semibold"><i class="bi bi-collection me-2"></i>Select Semester</label>
          <select name="semester" class="form-select" onchange="this.form.submit()">
            <option value="">-- Select Semester --</option>
            <?php foreach ($semesters as $sem): ?>
              <option value="<?= $sem['id'] ?>" <?= $sem['id'] == $selectedSemester ? 'selected' : '' ?>><?= htmlspecialchars($sem['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold"><i class="bi bi-book me-2"></i>Select Subject</label>
          <select name="subject" class="form-select" onchange="this.form.submit()">
            <option value="">-- Select Subject --</option>
            <?php foreach ($subjects as $sub): ?>
              <option value="<?= $sub['id'] ?>" <?= $sub['id'] == $selectedSubject ? 'selected' : '' ?>><?= htmlspecialchars($sub['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </form>

      <?php if ($selectedSemester && $selectedSubject && count($journals) > 0): ?>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
          <?php foreach ($journals as $j): 
            // Fetch reviews for this journal
            $reviewStmt = $conn->prepare("
              SELECT fr.*, u.name AS user_name, u.image 
              FROM file_reviews fr 
              JOIN users u ON fr.user_id = u.id 
              WHERE fr.material_id = ? 
              ORDER BY fr.created_at DESC 
              LIMIT 2
            ");
            $reviewStmt->bind_param("i", $j['id']);
            $reviewStmt->execute();
            $reviews = $reviewStmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            // Check if user has already reviewed this journal
            $userReview = null;
            if ($isLoggedIn) {
                $userReviewStmt = $conn->prepare("SELECT * FROM file_reviews WHERE material_id = ? AND user_id = ?");
                $userReviewStmt->bind_param("ii", $j['id'], $userId);
                $userReviewStmt->execute();
                $userReview = $userReviewStmt->get_result()->fetch_assoc();
            }
          ?>
            <div class="col">
              <div class="journal-card">
                <div class="d-flex justify-content-between align-items-start mb-3">
                  <h5 class="journal-title mb-0">
                    <i class="bi bi-journal-text me-2"></i><?= htmlspecialchars($j['title']) ?>
                  </h5>
                  <span class="badge bg-success">Journal</span>
                </div>
                
                <p class="mb-2"><i class="bi bi-book stats-icon"></i> <?= htmlspecialchars($j['subject_name']) ?></p>
                <p class="mb-2"><i class="bi bi-calendar stats-icon"></i> <?= date('d M Y, h:i A', strtotime($j['uploaded_at'])) ?></p>
                
                <div class="d-flex justify-content-between mb-3">
                  <div>
                    <i class="bi bi-download stats-icon"></i> 
                    <span><?= $j['download_count'] ?> downloads</span>
                    <?php if ($j['user_downloaded'] > 0): ?>
                      <span class="badge bg-success ms-2">Downloaded</span>
                    <?php endif; ?>
                  </div>
                  <?php if ($j['avg_rating']): ?>
                    <div class="text-warning">
                      <?= generateStars($j['avg_rating']) ?>
                      <small class="text-muted">(<?= $j['review_count'] ?>)</small>
                    </div>
                  <?php else: ?>
                    <div class="text-muted">No ratings yet</div>
                  <?php endif; ?>
                </div>
                
                <a href="?semester=<?= $selectedSemester ?>&subject=<?= $selectedSubject ?>&download=<?= $j['id'] ?>" class="btn download-btn w-100 mb-3">
                  <i class="bi bi-download"></i> Download Journal
                </a>
                
                <?php if (!empty($reviews)): ?>
                  <div class="reviews-section">
                    <h6 class="fw-semibold mb-2"><i class="bi bi-chat-square-text me-2"></i>Recent Reviews</h6>
                    <?php foreach ($reviews as $review): ?>
                      <div class="review-card mb-2">
                        <div class="d-flex align-items-center mb-1">
                          <img src="../uploads/profiles/<?= htmlspecialchars($review['image'] ?? 'default.png') ?>" width="24" height="24" class="rounded-circle me-2">
                          <span class="review-author"><?= htmlspecialchars($review['user_name']) ?></span>
                          <div class="ms-auto">
                            <?= generateStars($review['rating']) ?>
                          </div>
                        </div>
                        <p class="review-text mb-0"><?= htmlspecialchars($review['review']) ?></p>
                        <small class="text-muted"><?= date('d M Y', strtotime($review['created_at'])) ?></small>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
                
                <?php if ($isLoggedIn && $j['user_downloaded'] > 0 && !$userReview): ?>
                  <div class="review-form">
                    <h6 class="fw-semibold mb-2"><i class="bi bi-pencil-square me-2"></i>Write a Review</h6>
                    <form method="POST">
                      <input type="hidden" name="journal_id" value="<?= $j['id'] ?>">
                      
                      <div class="mb-3">
                        <label class="form-label">Your Rating</label>
                        <div class="star-rating">
                          <?= generateStars(0, true) ?>
                        </div>
                      </div>
                      
                      <div class="mb-3">
                        <label for="review_text_<?= $j['id'] ?>" class="form-label">Your Review</label>
                        <textarea class="form-control" id="review_text_<?= $j['id'] ?>" name="review_text" rows="2" required></textarea>
                      </div>
                      
                      <button type="submit" name="submit_review" class="btn btn-success btn-sm w-100">
                        <i class="bi bi-send-fill me-1"></i> Submit Review
                      </button>
                    </form>
                  </div>
                <?php elseif ($userReview): ?>
                  <div class="alert alert-info p-2 mt-2 text-center">
                    <i class="bi bi-check-circle-fill me-1"></i> You've already reviewed this journal
                  </div>
                <?php elseif (!$isLoggedIn): ?>
                  <div class="alert alert-warning p-2 mt-2 text-center">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> Login and download to review
                  </div>
                <?php endif; ?>
                
                <a href="journal_reviews.php?material_id=<?= $j['id'] ?>" class="btn btn-sm btn-outline-success w-100 mt-2">
                  <i class="bi bi-chat-left-text"></i> View All Reviews
                </a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php elseif ($selectedSemester && $selectedSubject): ?>
        <div class="alert alert-info text-center py-4">
          <i class="bi bi-info-circle-fill fs-4"></i>
          <h5 class="mt-2">No journals found</h5>
          <p class="mb-0">There are no journals available for the selected subject.</p>
        </div>
      <?php elseif ($selectedSemester || $selectedSubject): ?>
        <div class="alert alert-warning text-center py-4">
          <i class="bi bi-exclamation-triangle-fill fs-4"></i>
          <h5 class="mt-2">Please select both semester and subject</h5>
          <p class="mb-0">You need to select both semester and subject to view journals.</p>
        </div>
      <?php else: ?>
        <div class="alert alert-secondary text-center py-4">
          <i class="bi bi-journals fs-4"></i>
          <h5 class="mt-2">Get started with journals</h5>
          <p class="mb-0">Select your semester and subject to browse available journals.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <?php include '../include/footer.php'; ?>

  <script src="../Bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>