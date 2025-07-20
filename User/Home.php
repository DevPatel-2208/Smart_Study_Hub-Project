<?php
require_once '../db.php';
include '../include/header.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_SESSION['user'])) {
  header("Location: dashboard.php");
  exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>SmartStudy Hub - MCA Study Portal</title>
  <link rel="stylesheet" href="../Bootstrap/css/bootstrap.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary: #6a11cb;
      --primary-dark: #2575fc;
      --secondary: #ff6b6b;
      --light: #f8f9fa;
      --dark: #212529;
    }

    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #f9fafb 0%, #e9ecef 100%);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    .hero-section {
      background: linear-gradient(135deg, rgba(106,17,203,0.1) 0%, rgba(37,117,252,0.1) 100%);
      padding: 5rem 0;
      position: relative;
      overflow: hidden;
    }

    .hero-section::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiPjxkZWZzPjxwYXR0ZXJuIGlkPSJwYXR0ZXJuIiB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHBhdHRlcm5Vbml0cz0idXNlclNwYWNlT25Vc2UiIHBhdHRlcm5UcmFuc2Zvcm09InJvdGF0ZSg0NSkiPjxyZWN0IHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCIgZmlsbD0icmdiYSgxMDYsMTcsMjAzLDAuMDUpIj48L3JlY3Q+PC9wYXR0ZXJuPjwvZGVmcz48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSJ1cmwoI3BhdHRlcm4pIj48L3JlY3Q+PC9zdmc+') repeat;
      opacity: 0.3;
    }

    .btn-primary-custom {
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
      color: white;
      border-radius: 50px;
      padding: 12px 30px;
      font-size: 1rem;
      font-weight: 500;
      border: none;
      box-shadow: 0 4px 15px rgba(106, 17, 203, 0.3);
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }

    .btn-primary-custom:hover {
      color: white;
      transform: translateY(-3px);
      box-shadow: 0 8px 20px rgba(106, 17, 203, 0.4);
    }

    .btn-primary-custom::after {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
      opacity: 0;
      transition: opacity 0.3s ease;
      z-index: -1;
    }

    .btn-primary-custom:hover::after {
      opacity: 1;
    }

    .feature-card {
      background: white;
      border-radius: 15px;
      padding: 30px 20px;
      height: 100%;
      transition: all 0.3s ease;
      box-shadow: 0 5px 15px rgba(0,0,0,0.05);
      border: 1px solid rgba(0,0,0,0.05);
    }

    .feature-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 15px 30px rgba(106, 17, 203, 0.1);
      border-color: rgba(106, 17, 203, 0.2);
    }

    .feature-icon {
      font-size: 2.5rem;
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
      -webkit-background-clip: text;
      background-clip: text;
      -webkit-text-fill-color: transparent;
      margin-bottom: 1rem;
    }

    .section-title {
      position: relative;
      display: inline-block;
      margin-bottom: 3rem;
    }

    .section-title::after {
      content: '';
      position: absolute;
      bottom: -10px;
      left: 50%;
      transform: translateX(-50%);
      width: 50px;
      height: 3px;
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
      border-radius: 3px;
    }

    @media (max-width: 768px) {
      .hero-section {
        padding: 3rem 0;
      }
      
      .display-5 {
        font-size: 2.5rem;
      }
      
      .feature-card {
        margin-bottom: 1.5rem;
      }
    }
  </style>
</head>
<body>

<!-- Hero Section -->
<section class="hero-section">
  <div class="container position-relative">
    <div class="row justify-content-center">
      <div class="col-lg-8 text-center">
        <h1 class="display-5 fw-bold mb-4" style="color: var(--primary);">
          Welcome to <span style="color: var(--primary-dark);">SmartStudy Hub</span>
        </h1>
        <p class="lead mb-5 fs-4" style="color: var(--dark);">
          Your comprehensive platform for MCA study materials, assignments, journals, and exam preparation.
        </p>
        <a href="User/login.php" class="btn btn-primary-custom">
          Get Started <i class="bi bi-arrow-right-circle ms-2"></i>
        </a>
      </div>
    </div>
  </div>
</section>

<!-- Features Section -->
<section class="py-5 my-5">
  <div class="container">
    <h2 class="text-center section-title mb-5">Key Features</h2>
    <div class="row g-4">
      <div class="col-md-4">
        <div class="feature-card text-center">
          <i class="bi bi-journal-text feature-icon"></i>
          <h4 class="mb-3">Assignments</h4>
          <p class="mb-0">
            Access well-structured semester-wise assignments with proper formatting guidelines and submission tracking.
          </p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="feature-card text-center">
          <i class="bi bi-journals feature-icon"></i>
          <h4 class="mb-3">Practical Journals</h4>
          <p class="mb-0">
            Download complete practical journals with code samples, outputs, and viva questions for all labs.
          </p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="feature-card text-center">
          <i class="bi bi-file-earmark-pdf feature-icon"></i>
          <h4 class="mb-3">Study Resources</h4>
          <p class="mb-0">
            Get curated notes, previous year papers, reference books, and important questions for each subject.
          </p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Additional Info Section -->
<section class="py-5 bg-light">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-lg-6 mb-4 mb-lg-0">
        <img src="../uploads/unnamed.png" alt="SmartStudy Hub" class="img-fluid rounded-3 shadow">
      </div>
      <div class="col-lg-6">
        <h2 class="mb-4">Why Choose SmartStudy Hub?</h2>
        <ul class="list-unstyled">
          <li class="mb-3 d-flex">
            <i class="bi bi-check-circle-fill text-primary me-2 mt-1"></i>
            <span>MCA-focused content curated by toppers and professors</span>
          </li>
          <li class="mb-3 d-flex">
            <i class="bi bi-check-circle-fill text-primary me-2 mt-1"></i>
            <span>Regular updates with latest syllabus materials</span>
          </li>
          <li class="mb-3 d-flex">
            <i class="bi bi-check-circle-fill text-primary me-2 mt-1"></i>
            <span>Mobile-friendly access from any device</span>
          </li>
          <li class="mb-3 d-flex">
            <i class="bi bi-check-circle-fill text-primary me-2 mt-1"></i>
            <span>Time-saving organized resources</span>
          </li>
          <li class="d-flex">
            <i class="bi bi-check-circle-fill text-primary me-2 mt-1"></i>
            <span>Secure platform with regular backups</span>
          </li>
        </ul>
      </div>
    </div>
  </div>
</section>

<script src="../Bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php include '../include/footer.php'; ?>