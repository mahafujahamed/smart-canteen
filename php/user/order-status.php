<?php
require_once __DIR__ . '/../headers.php';
require_once __DIR__ . '/../db.php';

$id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
if (!$id) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'Missing order_id']);
    exit;
}

$stmt = $conn->prepare("SELECT id, student_id, items, total, status, pickup_time, created_at, updated_at FROM orders WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    // make items pretty JSON for frontend
    $row['items'] = json_decode($row['items'], true);
    echo json_encode(['success'=>true,'order'=>$row]);
} else {
    http_response_code(404);
    echo json_encode(['success'=>false,'message'=>'Order not found']);
}
$stmt->close();
