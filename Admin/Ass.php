<?php
session_start();
include '../db.php';

// Check login
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}

// Fetch material types
$type_result = $conn->query("SELECT id, type_name FROM material_types ORDER BY type_name ASC");
$types = [];
while ($row = $type_result->fetch_assoc()) {
    $types[$row['id']] = $row['type_name'];
}

// Filter
$selected_type_id = isset($_GET['type_id']) ? intval($_GET['type_id']) : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 8;
$offset = ($page - 1) * $limit;

$filter_sql = " WHERE 1=1 ";
$params = [];
$typestr = '';

if ($selected_type_id > 0) {
    $filter_sql .= " AND sm.material_type_id = ? ";
    $params[] = $selected_type_id;
    $typestr .= "type_id=$selected_type_id&";
}
if (!empty($search)) {
    $filter_sql .= " AND (sm.title LIKE ? OR s.name LIKE ? OR se.name LIKE ?) ";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $typestr .= "search=$search&";
}

$count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM study_materials sm $filter_sql");
if ($params) {
    $types_param = str_repeat('s', count($params));
    $count_stmt->bind_param($types_param, ...$params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total = $count_result->fetch_assoc()['total'];
$pages = ceil($total / $limit);
$count_stmt->close();

$sql = "SELECT sm.*, s.name AS subject_name, se.name AS semester_name, mt.type_name, a.name AS admin_name
        FROM study_materials sm
        JOIN subjects s ON sm.subject_id = s.id
        JOIN semesters se ON sm.semester_id = se.id
        JOIN material_types mt ON sm.material_type_id = mt.id
        JOIN admins a ON sm.uploaded_by = a.id
        $filter_sql
        ORDER BY sm.uploaded_at DESC
        LIMIT $limit OFFSET $offset";

$stmt = $conn->prepare($sql);
if ($params) {
    $types_param = str_repeat('s', count($params));
    $stmt->bind_param($types_param, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Study Materials - Admin Panel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    body {
      background-color: #f8f9fa;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    .main-content {
      margin-left: 250px;
      padding: -20px;
      transition: margin-left 0.3s;
    }
    @media (max-width: 992px) {
      .main-content {
        margin-left: 0;
      }
    }
    .card {
      border-radius: 12px;
      box-shadow: 0 6px 10px rgba(0, 0, 0, 0.08);
      margin-bottom: 25px;
      border: none;
      transition: all 0.3s ease;
      height: 100%;
    }
    .card:hover {
      transform: translateY(-5px);
      box-shadow: 0 12px 20px rgba(0, 0, 0, 0.15);
    }
    .card-body {
      padding: 1.5rem;
    }
    .card-title {
      color: #2c3e50;
      font-weight: 700;
      font-size: 1.25rem;
      margin-bottom: 1rem;
    }
    .card-text {
      color: #555;
      font-size: 1rem;
      line-height: 1.6;
    }
    .badge {
      font-size: 0.9rem;
      padding: 0.5rem 0.75rem;
      border-radius: 8px;
      font-weight: 600;
      margin-right: 0.5rem;
      margin-bottom: 0.5rem;
    }
    .badge-type {
      background-color: #6c757d;
      color: white;
    }
    .badge-semester {
      background-color: #3498db;
      color: white;
    }
    .badge-subject {
      background-color:rgb(74, 87, 235);
      color: white;
    }
    .badge-admin {
      background-color: #9b59b6;
      color: white;
    }
    .badge-date {
      background-color: #e67e22;
      color: white;
    }
    .material-icon {
      font-size: 1.75rem;
      margin-right: 15px;
      color: #3498db;
    }
    .page-title {
      color: #2c3e50;
      font-weight: 700;
      font-size: 1.75rem;
      margin-bottom: 1.5rem;
      padding-bottom: 0.75rem;
      border-bottom: 3px solid #3498db !important;
    }
    .empty-state {
      text-align: center;
      padding: 3rem;
      background-color: white;
      border-radius: 12px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    .empty-state-icon {
      font-size: 4rem;
      color: #95a5a6;
      margin-bottom: 1.5rem;
    }
    .btn-download {
      background-color:rgb(251, 251, 251) !important;
      color: white;
      border-radius: 8px;
      font-weight: 600;
      font-size: 1rem;
      padding: 0.5rem 1.25rem;
      border: none;
      transition: all 0.3s ease;
    }
    .btn-download:hover {
      background-color: #2980b9;
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
    .btn-primary {
      background-color: #3498db;
      border: none;
      font-weight: 600;
      padding: 0.5rem 1.5rem;
      font-size: 1rem;
    }
    .btn-primary:hover {
      background-color: #2980b9;
    }
    .form-control, .form-select {
      border-radius: 8px;
      padding: 0.75rem 1rem;
      font-size: 1rem;
      border: 1px solid #ced4da;
    }
    .filter-card {
      border-radius: 12px;
      margin-bottom: 2rem;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
      border: none;
    }
    .file-badge {
      background-color:rgb(251, 157, 98);
      color:black!important;
      font-weight: 600;
      font-size: 0.15rem;
      padding: 0.5rem 0.75rem;
      border-radius: 8px;
    }
    .info-item {
      display: flex;
      align-items: center;
      margin-bottom: 0.75rem;
    }
    .info-item i {
      margin-right: 10px;
      color: #7f8c8d;
      font-size: 1.1rem;
    }
  </style>
</head>
<body>

<?php include 'ad.php'; ?>

<div class="main-content">
  <div class="container-fluid">
    <div class="row mb-4">
      <div class="col-12">
        <div class="card">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <h4 class="card-title mb-0">
                <i class="bi bi-collection-fill material-icon"></i> Study Materials Management
              </h4>
              <div>
                <a href="add_material.php" class="btn btn-primary">
                  <i class="bi bi-plus-circle-fill"></i> Add New Material
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filter Card -->
    <div class="card filter-card">
      <div class="card-body">
        <form method="GET" class="row g-3">
          <div class="col-md-4">
            <div class="input-group">
              <span class="input-group-text bg-light"><i class="bi bi-tag-fill"></i></span>
              <select name="type_id" class="form-select">
                <option value="0">All Material Types</option>
                <?php foreach ($types as $id => $name): ?>
                  <option value="<?= $id ?>" <?= $selected_type_id == $id ? 'selected' : '' ?>><?= htmlspecialchars($name) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="col-md-6">
            <div class="input-group">
              <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
              <input type="text" name="search" class="form-control" placeholder="Search by title, subject or semester..." value="<?= htmlspecialchars($search) ?>">
            </div>
          </div>
          <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">
              <i class="bi bi-funnel-fill"></i> Apply Filters
            </button>
          </div>
        </form>
      </div>
    </div>

    <?php if ($result->num_rows > 0): ?>
      <div class="row">
        <?php while ($row = $result->fetch_assoc()): ?>
          <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12 mb-4">
            <div class="card h-100">
              <div class="card-body d-flex flex-column">
                <div class="d-flex align-items-center mb-3">
                  <i class="bi bi-file-earmark-text-fill material-icon"></i>
                  <h5 class="card-title mb-0"><?= htmlspecialchars($row['title']) ?></h5>
                </div>
                
                <div class="d-flex flex-wrap mb-3">
                  <span class="badge badge-type">
                    <i class="bi bi-tag-fill"></i> <?= htmlspecialchars($row['type_name']) ?>
                  </span>
                  <span class="badge badge-semester">
                    <i class="bi bi-calendar-week"></i> <?= htmlspecialchars($row['semester_name']) ?>
                  </span>
                  <span class="badge badge-subject">
                    <i class="bi bi-book-fill"></i> <?= htmlspecialchars($row['subject_name']) ?>
                  </span>
                </div>
                
                <div class="mb-3 flex-grow-1">
                  <div class="info-item">
                    <i class="bi bi-person-fill"></i>
                    <span><?= htmlspecialchars($row['admin_name']) ?></span>
                  </div>
                  <div class="info-item">
                    <i class="bi bi-clock-fill"></i>
                    <span><?= date('d M Y, h:i A', strtotime($row['uploaded_at'])) ?></span>
                  </div>
                </div>
                
                <div class="d-flex justify-content-between align-items-center mt-auto">
                  <a href="../uploads/<?= htmlspecialchars($row['file_name']) ?>" 
                     class="btn btn-download" download>
                    <i class="bi bi-download"></i> Download
                  </a>
                  <span class="badge file-badge">
                    <?= strtoupper(pathinfo($row['file_name'], PATHINFO_EXTENSION)) ?>
                  </span>
                </div>
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      </div>

      <!-- Pagination -->
      <?php if ($pages > 1): ?>
      <nav aria-label="Page navigation" class="mt-4">
        <ul class="pagination justify-content-center">
          <?php if ($page > 1): ?>
            <li class="page-item">
              <a class="page-link" href="?<?= $typestr ?>page=<?= $page - 1 ?>" aria-label="Previous">
                <i class="bi bi-chevron-left"></i>
              </a>
            </li>
          <?php endif; ?>

          <?php for($i = 1; $i <= $pages; $i++): ?>
            <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
              <a class="page-link" href="?<?= $typestr ?>page=<?= $i ?>"><?= $i ?></a>
            </li>
          <?php endfor; ?>

          <?php if ($page < $pages): ?>
            <li class="page-item">
              <a class="page-link" href="?<?= $typestr ?>page=<?= $page + 1 ?>" aria-label="Next">
                <i class="bi bi-chevron-right"></i>
              </a>
            </li>
          <?php endif; ?>
        </ul>
      </nav>
      <?php endif; ?>

    <?php else: ?>
      <div class="card">
        <div class="card-body empty-state">
          <i class="bi bi-journal-x empty-state-icon"></i>
          <h3 class="text-muted mb-3">No Study Materials Found</h3>
          <p class="text-muted mb-4">There are currently no materials matching your criteria.</p>
          <a href="add_material.php" class="btn btn-primary">
            <i class="bi bi-plus-circle-fill"></i> Add New Material
          </a>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>