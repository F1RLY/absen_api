<?php
date_default_timezone_set('Asia/Jakarta');
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

$user_id = $input['user_id'] ?? 0;
$type = $input['type'] ?? '';

if ($user_id == 0 || empty($type)) {
    echo json_encode(['success' => false, 'message' => 'User ID dan type wajib diisi']);
    exit;
}

if (!in_array($type, ['masuk', 'keluar'])) {
    echo json_encode(['success' => false, 'message' => 'Type tidak valid']);
    exit;
}

$checkUser = $conn->prepare("SELECT id FROM users WHERE id = ?");
$checkUser->bind_param("i", $user_id);
$checkUser->execute();
$checkUser->store_result();
if ($checkUser->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'User tidak ditemukan']);
    $checkUser->close();
    $conn->close();
    exit;
}
$checkUser->close();

$today = date('Y-m-d');
$checkExisting = $conn->prepare(
    "SELECT id FROM attendance WHERE user_id = ? AND type = ? AND DATE(timestamp) = ?"
);
$checkExisting->bind_param("iss", $user_id, $type, $today);
$checkExisting->execute();
$checkExisting->store_result();
if ($checkExisting->num_rows > 0) {
    $label = $type === 'masuk' ? 'masuk' : 'keluar';
    echo json_encode(['success' => false, 'message' => "Anda sudah absen $label hari ini"]);
    $checkExisting->close();
    $conn->close();
    exit;
}
$checkExisting->close();

if ($type === 'keluar') {
    $checkMasuk = $conn->prepare(
        "SELECT id FROM attendance WHERE user_id = ? AND type = 'masuk' AND DATE(timestamp) = ?"
    );
    $checkMasuk->bind_param("is", $user_id, $today);
    $checkMasuk->execute();
    $checkMasuk->store_result();
    if ($checkMasuk->num_rows == 0) {
        echo json_encode(['success' => false, 'message' => 'Anda belum absen masuk hari ini']);
        $checkMasuk->close();
        $conn->close();
        exit;
    }
    $checkMasuk->close();
}

$timestamp = date('Y-m-d H:i:s');
$stmt = $conn->prepare("INSERT INTO attendance (user_id, type, timestamp) VALUES (?, ?, ?)");
$stmt->bind_param("iss", $user_id, $type, $timestamp);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Absen berhasil', 'id' => $stmt->insert_id]);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan absen: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>