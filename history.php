<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'db_absen';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Koneksi database gagal']));
}

$user_id = $_GET['user_id'] ?? 0;

if ($user_id == 0) {
    echo json_encode(['success' => false, 'message' => 'User ID wajib diisi']);
    $conn->close();
    exit;
}

$query = "SELECT id, user_id, type, timestamp FROM attendance WHERE user_id = ? ORDER BY timestamp DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = [
        'id' => (int)$row['id'],
        'user_id' => (int)$row['user_id'],  // Pasti ada dan integer
        'type' => $row['type'],
        'timestamp' => $row['timestamp']
    ];
}

echo json_encode($data);

$stmt->close();
$conn->close();
?>