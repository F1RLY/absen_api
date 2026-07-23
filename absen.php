<?php
date_default_timezone_set('Asia/Jakarta');
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

define('BASE_URL', 'https://noncharacterized-hauriant-jerilyn.ngrok-free.dev/absen_api/');
define('UPLOAD_DIR', __DIR__ . '/uploads/');

$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'db_absen';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Koneksi database gagal']));
}

// Sekarang pakai $_POST & $_FILES, bukan php://input, karena multipart/form-data
$user_id = $_POST['user_id'] ?? 0;
$type = $_POST['type'] ?? '';

if ($user_id == 0 || empty($type)) {
    echo json_encode(['success' => false, 'message' => 'User ID dan type wajib diisi']);
    exit;
}

if (!in_array($type, ['masuk', 'keluar'])) {
    echo json_encode(['success' => false, 'message' => 'Type tidak valid']);
    exit;
}

if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Foto verifikasi wajib diisi']);
    exit;
}

$allowedTypes = ['image/jpeg', 'image/png'];
$mime = mime_content_type($_FILES['photo']['tmp_name']);
if (!in_array($mime, $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Format foto tidak didukung']);
    exit;
}

if ($_FILES['photo']['size'] > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'Ukuran foto terlalu besar (maks 5MB)']);
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

if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

$ext = $mime === 'image/png' ? 'png' : 'jpg';
$filename = 'absen_' . $user_id . '_' . $type . '_' . time() . '.' . $ext;
$destination = UPLOAD_DIR . $filename;

if (!move_uploaded_file($_FILES['photo']['tmp_name'], $destination)) {
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan foto']);
    exit;
}

$timestamp = date('Y-m-d H:i:s');
$stmt = $conn->prepare("INSERT INTO attendance (user_id, type, timestamp, photo) VALUES (?, ?, ?, ?)");
$stmt->bind_param("isss", $user_id, $type, $timestamp, $filename);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Absen berhasil',
        'id' => $stmt->insert_id,
        'photo_url' => BASE_URL . 'uploads/' . $filename,
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan absen: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>