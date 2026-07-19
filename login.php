<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'db_absen';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Koneksi database gagal']));
}

$input = json_decode(file_get_contents('php://input'), true);

$nik = $input['nik'] ?? '';
$password = $input['password'] ?? '';

if (empty($nik) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'NIK dan password wajib diisi']);
    exit;
}

$stmt = $conn->prepare("SELECT id, nik, name FROM users WHERE nik = ? AND password = MD5(?)");
$stmt->bind_param("ss", $nik, $password);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $row['id'],
            'nik' => $row['nik'],
            'name' => $row['name']
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'NIK atau password salah']);
}

$stmt->close();
$conn->close();
?>