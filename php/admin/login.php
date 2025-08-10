<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../db.php';
session_start();

if (isset($_GET['logout'])) {
    session_destroy();
    echo json_encode(['success'=>true,'message'=>'logged out']); exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success'=>false,'message'=>'Use POST']); exit;
}
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
if (!$username || !$password) {
    echo json_encode(['success'=>false,'message'=>'Missing fields']); exit;
}

// Matching database.sql that used MD5 for initial password
$hash = md5($password);

$stmt = $conn->prepare("SELECT id FROM admin_users WHERE username = ? AND password = ?");
$stmt->bind_param('ss',$username, $hash);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 1) {
    $_SESSION['admin'] = $username;
    echo json_encode(['success'=>true]);
} else {
    http_response_code(401);
    echo json_encode(['success'=>false,'message'=>'Invalid credentials']);
}
$stmt->close();
