<?php
include '../db.php';

header('Content-Type: application/json');

$semester_id = isset($_GET['semester_id']) ? intval($_GET['semester_id']) : 0;
$data = [];

if ($semester_id > 0) {
    $result = $conn->query("SELECT id, name FROM subjects WHERE semester_id = $semester_id");
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

echo json_encode($data);
?>
