<?php
require_once __DIR__ . '/../headers.php';
require_once __DIR__ . '/../db.php';
session_start();
if (empty($_SESSION['admin_id'])) {
    http_response_code(401); echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['success'=>false,'message'=>'Use POST']); exit;
}

$body = json_decode(file_get_contents('php://input'), true);
$id = isset($body['id']) ? intval($body['id']) : 0;
$status = isset($body['status']) ? $conn->real_escape_string($body['status']) : '';

$allowed = ['pending','accepted','processing','ready','picked_up','cancelled'];
if (!$id || !in_array($status, $allowed)) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'Invalid parameters']);
    exit;
}

$stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
$stmt->bind_param('si', $status, $id);
if ($stmt->execute()) {
    echo json_encode(['success'=>true]);
} else {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Update failed']);
}
$stmt->close();
