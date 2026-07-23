<?php
date_default_timezone_set('Asia/Jakarta');
define('BASE_URL', 'https://noncharacterized-hauriant-jerilyn.ngrok-free.dev/absen_api/');
define('UPLOAD_DIR', __DIR__ . '/uploads/');

$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'db_absen';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    header('Content-Type: application/json');
    die(json_encode(['success' => false, 'message' => 'Koneksi database gagal']));
}