<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

<!-- Footer Starts -->
<footer class="text-white pt-5 pb-3" style="background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);">
  <div class="container">
    <div class="row gy-4">

      <!-- Branding Column -->
      <div class="col-lg-4 col-md-6">
        <div class="d-flex align-items-center mb-3">
          <i class="bi bi-journal-bookmark-fill fs-3 me-2 text-white"></i>
          <h5 class="fw-bold text-white mb-0">Smart<span class="text-warning">Study</span>Hub</h5>
          <span class="badge bg-white text-primary ms-2">MCA</span>
        </div>
        <p class="small text-white-80">Your all-in-one platform to manage MCA study materials, assignments & journals easily.</p>
      </div>

      <!-- Quick Links Column -->
      <div class="col-lg-4 col-md-6">
        <h6 class="fw-semibold text-white mb-3 border-bottom border-white border-opacity-25 pb-2 d-inline-block">
          <i class="bi bi-link-45deg me-2"></i>Quick Links
        </h6>
        <div class="row">
          <div class="col-6">
            <ul class="list-unstyled">
              <li class="mb-2">
                <a href="my_profile.php" class="footer-link text-white text-decoration-none">
                  <i class="bi bi-person me-2"></i> Profile
                </a>
              </li>
              <li class="mb-2">
                <a href="dashboard.php" class="footer-link text-white text-decoration-none">
                  <i class="bi bi-speedometer2 me-2"></i> Dashboard
                </a>
              </li>
              <li class="mb-2">
                <a href="mat.php" class="footer-link text-white text-decoration-none">
                  <i class="bi bi-collection me-2"></i> Materials
                </a>
              </li>
            </ul>
          </div>
          <div class="col-6">
            <ul class="list-unstyled">
              <li class="mb-2">
                <a href="user_ass.php" class="footer-link text-white text-decoration-none">
                  <i class="bi bi-journal-text me-2"></i> Assignments
                </a>
              </li>
              <li class="mb-2">
                <a href="user_j.php" class="footer-link text-white text-decoration-none">
                  <i class="bi bi-journals me-2"></i> Journals
                </a>
              </li>
              <li class="mb-2">
                <a href="notifications.php" class="footer-link text-white text-decoration-none">
                  <i class="bi bi-bell-fill me-2"></i> Notifications
                </a>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Connect Column -->
      <div class="col-lg-4">
        <h6 class="fw-semibold text-white mb-3 border-bottom border-white border-opacity-25 pb-2 d-inline-block">
          <i class="bi bi-people-fill me-2"></i>Connect With Us
        </h6>
        <div class="d-flex flex-column gap-2 mb-3">
          <a href="https://linkedin.com/in/dev-patel-0a3166346" target="_blank" class="footer-link text-white text-decoration-none">
            <i class="bi bi-linkedin me-2"></i> LinkedIn
          </a>
          <a href="mailto:dev825169@gmail.com" class="footer-link text-white text-decoration-none">
            <i class="bi bi-envelope-fill me-2"></i> dev825169@gmail.com
          </a>
          <a href="tel:+917016989212" class="footer-link text-white text-decoration-none">
            <i class="bi bi-telephone-fill me-2"></i> +91 7016982912
          </a>
           <a  class="footer-link text-white text-decoration-none">
            <i class="bi bi-geo-alt-fill me-1"></i> Opp Jalaram Temple, Sarsa, Anand, Gujarat
          </a>
        </div>         
      </div>

    </div>

   <!-- ======= Footer Start ======= -->

    <hr class="my-4 border-white border-opacity-505">

<div class="container-fluid">
  <div class="row align-items-center">
    <div class="col-12 col-md-6 order-md-2 text-center text-md-end">
      <p class="small text-white-50 mb-0">
        &copy; 2025 <strong class="text-white">SmartStudyHub</strong> | Made with 
        <i class="bi bi-heart-fill text-danger"></i> by 
         <a href="https://linkedin.com/in/dev-patel-0a3166346" 
   target="_blank" 
   class="text-warning fw-bold text-decoration-none custom-hover-name">
   Dev K Patel
</a>
 </p>
    </div>
    <div class="col-12 col-md-6 order-md-1 text-center text-md-start mt-2 mt-md-0">
      <!-- Optional: Add additional content on the left side if needed -->
      <!-- <p class="small text-white-50 mb-0">Your additional content here</p> -->
    </div>
  </div>
</div>
  </div>
</footer>
<!-- ======= Footer End ======= -->

  </div>
</footer>
<!-- Footer Ends -->

<!-- Footer Custom Styling -->
<style>
  .custom-hover-name {
  transition: all 0.3s ease;
  font-size: 1rem;
}

.custom-hover-name:hover {
  color: #fff !important;        /* Bright white on hover */
  text-shadow: 0 0 5px #ffc107;  /* Golden glow effect */
  text-decoration: underline;
}

  .text-white-80 {
    color: rgba(255, 255, 255, 0.8);
  }
  .text-white-70 {
    color: rgba(255, 255, 255, 0.7);
  }
  .footer-link {
    color: white;
    text-decoration: none;
    transition: all 0.3s ease;
    display: inline-block;
  }
  .footer-link:hover {
    color: white !important;
    transform: translateX(5px);
    text-decoration: none;
  }
  .footer-link:hover i {
    color: var(--bs-warning);
    transform: scale(1.2);
  }
  footer i {
    transition: all 0.3s ease;
  }
  
  /* Responsive adjustments */
  @media (max-width: 767.98px) {
    .col-md-6 {
      text-align: center !important;
    }
    footer {
      text-align: center;
    }
    .row.gy-4 > div {
      padding-bottom: 1.5rem;
      border-bottom: 1px solid rgba(255,255,255,0.1);
      margin-bottom: 1.5rem;
    }
    .row.gy-4 > div:last-child {
      border-bottom: none;
      margin-bottom: 0;
    }
  }
</style>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>