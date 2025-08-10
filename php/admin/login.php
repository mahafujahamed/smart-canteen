<?php
require_once __DIR__ . '/../headers.php';
require_once __DIR__ . '/../db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['logout'])) {
    session_destroy();
    echo json_encode(['success'=>true]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success'=>false,'message'=>'Use POST']);
    exit;
}

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
if (!$username || !$password) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'Missing fields']);
    exit;
}

$stmt = $conn->prepare("SELECT id, password_hash FROM admin_users WHERE username = ?");
$stmt->bind_param('s', $username);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    if (password_verify($password, $row['password_hash'])) {
        $_SESSION['admin_id'] = $row['id'];
        echo json_encode(['success'=>true]);
    } else {
        http_response_code(401);
        echo json_encode(['success'=>false,'message'=>'Invalid credentials']);
    }
} else {
    http_response_code(401);
    echo json_encode(['success'=>false,'message'=>'Invalid credentials']);
}
$stmt->close();
