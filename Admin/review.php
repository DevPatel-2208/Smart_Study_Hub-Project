<?php
session_start();
include '../db.php';

// Admin login check
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch Semesters and Material Types for dropdowns
$semesters = $conn->query("SELECT id, name FROM semesters ORDER BY name ASC");
$materialTypes = $conn->query("SELECT id, type_name FROM material_types ORDER BY type_name ASC");

// Filters
$selectedSemester = isset($_GET['semester']) ? intval($_GET['semester']) : 0;
$selectedType = isset($_GET['material_type']) ? intval($_GET['material_type']) : 0;

// Fetch reviews based on selected filters
$reviews = [];
if ($selectedSemester && $selectedType) {
    $stmt = $conn->prepare("SELECT fr.*, u.name AS user_name, sm.title AS file_title, s.name AS subject_name, fr.created_at
        FROM file_reviews fr
        JOIN users u ON fr.user_id = u.id
        JOIN study_materials sm ON fr.material_id = sm.id
        JOIN subjects s ON sm.subject_id = s.id
        WHERE sm.semester_id = ? AND sm.material_type_id = ?
        ORDER BY fr.created_at DESC");
    $stmt->bind_param("ii", $selectedSemester, $selectedType);
    $stmt->execute();
    $reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>File Reviews | Smart Study Hub - Admin</title>
  <link rel="stylesheet" href="../Bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
  <style>
    :root {
      --primary-color: #4e73df;
      --primary-dark: #2e59d9;
      --secondary-color: #f8f9fc;
      --accent-color: #dddfeb;
      --text-color: #5a5c69;
      --header-bg: #ffffff;
      --star-color: #ffc107;
    }
    
    body {
      background-color: var(--secondary-color);
      color: var(--text-color);
      font-family: 'Poppins', sans-serif;
    }
    
    /* Main Content */
    .main-content {
      padding: 20px;
      margin-top: -30px; /* Space for topbar */
      margin-left: 250px; /* Adjusted for sidebar width */
      transition: margin-left 0.3s;
    }
    
    /* When sidebar is collapsed */
    body.sidebar-collapsed .main-content {
      margin-left: 80px;
    }
    
    /* Page Header */
    .page-header {
      background-color: var(--header-bg);
      padding: 1.5rem;
      margin-bottom: 1.5rem;
      border-radius: 0.5rem;
      box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
    }
    
    .page-header .card-title {
      margin-bottom: 0;
      font-weight: 600;
    }
    
    .page-header .card-title i {
      margin-right: 10px;
      color: var(--primary-color);
    }
    
    /* Review Card */
    .review-card {
      background: #fff;
      border-radius: 0.75rem;
      box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
      border: 1px solid var(--accent-color);
      padding: 1.5rem;
      margin-bottom: 2rem;
    }
    
    /* Filter Form */
    .filter-form {
      background-color: rgba(78, 115, 223, 0.05);
      border-radius: 0.5rem;
      padding: 1.5rem;
      margin-bottom: 2rem;
      border: 1px solid var(--accent-color);
    }
    
    /* Table Styles */
    .table thead {
      background-color: var(--primary-color);
      color: white;
    }
    
    .table th {
      text-align: center;
      vertical-align: middle;
      font-weight: 600;
    }
    
    .table td {
      vertical-align: middle;
    }
    
    .rating-stars i {
      color: var(--star-color);
      font-size: 1.2rem;
    }
    
    /* Review Text */
    .review-text {
      max-width: 300px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    
    /* Responsive Adjustments */
    @media (max-width: 992px) {
      .main-content {
        margin-left: 0;
      }
      
      .review-card {
        padding: 1.25rem;
      }
      
      .table td, .table th {
        font-size: 0.9rem;
      }
      
      .rating-stars i {
        font-size: 1.1rem;
      }
    }
    
    @media (max-width: 768px) {
      .page-header {
        padding: 1rem;
      }
      
      .review-card {
        padding: 1rem;
      }
      
      /* Hide time column on tablets */
      .time-col {
        display: none;
      }
      
      /* Truncate longer text */
      .review-text {
        max-width: 200px;
      }
    }
    
    @media (max-width: 576px) {
      .page-header {
        flex-direction: column;
        align-items: flex-start;
      }
      
      .page-header .btn {
        margin-top: 1rem;
        width: 100%;
      }
      
      .review-card {
        padding: 1rem;
        margin-left: -10px;
        margin-right: -10px;
        border-radius: 0;
      }
      
      /* Stack date and time in mobile */
      .time-col {
        display: none;
      }
      
      .date-col::after {
        content: " " attr(data-time);
        display: block;
        font-size: 0.7rem;
        color: #6c757d;
        font-weight: normal;
      }
      
      /* Hide subject column on mobile */
      .subject-col {
        display: none;
      }
      
      /* Make rating stars smaller */
      .rating-stars i {
        font-size: 1rem;
      }
      
      /* Adjust filter form */
      .filter-form {
        padding: 1rem;
      }
      
      /* Make table more compact */
      .table td, .table th {
        font-size: 0.8rem;
        padding: 0.5rem;
      }
    }
  </style>
</head>
<body>
  <?php include 'ad.php'; ?>
  
  <!-- Main Content -->
  <div class="main-content">
    <!-- Page Header -->
    <div class="page-header">
      <div class="d-flex justify-content-between align-items-center">
        <h4 class="card-title mb-0">
          <i class="bi bi-star-half"></i> File Reviews
        </h4>
        <a href="dashboard.php" class="btn btn-primary">
          <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a>
      </div>
    </div>

    <!-- Review Card -->
    <div class="review-card">
      <form method="GET" class="filter-form">
        <div class="row g-3">
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
              <option value="">-- Select Type --</option>
              <?php while($mt = $materialTypes->fetch_assoc()): ?>
                <option value="<?= $mt['id'] ?>" <?= $selectedType == $mt['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($mt['type_name']) ?>
                </option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="col-12 text-end">
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-funnel"></i> Apply Filters
            </button>
          </div>
        </div>
      </form>

      <div class="table-responsive">
        <table id="reviewTable" class="table table-hover">
          <thead>
            <tr>
              <th>#</th>
              <th>User</th>
              <th>File</th>
              <th class="subject-col">Subject</th>
              <th>Rating</th>
              <th>Review</th>
              <th class="date-col">Date</th>
              <th class="time-col">Time</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($reviews)): ?>
              <tr>
                <td colspan="8" class="text-center py-4">No reviews found. Please select semester and material type to view reviews.</td>
              </tr>
            <?php else: ?>
              <?php $i = 1; foreach ($reviews as $r): ?>
                <?php
                  $date = date("d M Y", strtotime($r['created_at']));
                  $time = date("h:i A", strtotime($r['created_at']));
                ?>
                <tr>
                  <td><?= $i++ ?></td>
                  <td><?= htmlspecialchars($r['user_name']) ?></td>
                  <td><?= htmlspecialchars($r['file_title']) ?></td>
                  <td class="subject-col"><?= htmlspecialchars($r['subject_name']) ?></td>
                  <td class="rating-stars text-center">
                    <?php for ($s = 1; $s <= 5; $s++): ?>
                      <i class="bi <?= $s <= $r['rating'] ? 'bi-star-fill' : 'bi-star' ?>"></i>
                    <?php endfor; ?>
                  </td>
                  <td class="review-text" title="<?= htmlspecialchars($r['review']) ?>">
                    <?= htmlspecialchars($r['review']) ?>
                  </td>
                  <td class="date-col review-date" data-time="<?= $time ?>"><?= $date ?></td>
                  <td class="time-col review-time"><?= $time ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <script src="../Bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
  <script>
    $(document).ready(function () {
      var table = $('#reviewTable').DataTable({
        responsive: {
          details: {
            display: $.fn.dataTable.Responsive.display.modal({
              header: function (row) {
                var data = row.data();
                return 'Details for ' + data[1]; // User name
              }
            }),
            renderer: $.fn.dataTable.Responsive.renderer.tableAll({
              tableClass: 'table'
            })
          }
        },
        pageLength: 10,
        lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]],
        language: {
          search: "_INPUT_",
          searchPlaceholder: "Search reviews...",
          lengthMenu: "Show _MENU_ reviews per page",
          zeroRecords: "No matching reviews found",
          info: "Showing _START_ to _END_ of _TOTAL_ reviews",
          infoEmpty: "No reviews available",
          infoFiltered: "(filtered from _MAX_ total reviews)",
          paginate: {
            first: "First",
            last: "Last",
            next: "<i class='bi bi-chevron-right'></i>",
            previous: "<i class='bi bi-chevron-left'></i>"
          }
        },
        dom: '<"top"<"row"<"col-md-6"l><"col-md-6"f>>>rt<"bottom"<"row"<"col-md-6"i><"col-md-6"p>>>',
        initComplete: function() {
          $('.dataTables_filter input').addClass('form-control form-control-sm');
          $('.dataTables_length select').addClass('form-select form-select-sm');
        }
      });
      
      // Handle responsive columns
      function handleResponsiveView() {
        if ($(window).width() < 576) {
          $('.time-col, .subject-col').hide();
          $('.date-col').attr('data-time', function() {
            return $(this).next('.time-col').text();
          });
        } else if ($(window).width() < 768) {
          $('.time-col').hide();
          $('.subject-col').show();
          $('.date-col').removeAttr('data-time');
        } else {
          $('.time-col, .subject-col').show();
          $('.date-col').removeAttr('data-time');
        }
      }
      
      handleResponsiveView();
      $(window).resize(handleResponsiveView);
      
      // Re-draw table when columns are shown/hidden
      $(window).on('resize', function() {
        table.columns.adjust().responsive.recalc();
      });
      
      // Handle sidebar collapse/expand
      $('.sidebar-toggler').click(function() {
        $('body').toggleClass('sidebar-collapsed');
      });
    });
  </script>
</body>
</html>