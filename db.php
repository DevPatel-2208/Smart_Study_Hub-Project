<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'smart_study_material_db';

$conn = mysqli_connect(hostname: $host, username: $user, password: $pass, database: $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?> 