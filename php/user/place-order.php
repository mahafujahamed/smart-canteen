<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../db.php';

// Only POST JSON
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success'=>false,'message'=>'Method Not Allowed']); exit;
}

$body = json_decode(file_get_contents('php://input'), true);
if (!$body || !isset($body['items']) || !is_array($body['items'])) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'Bad request']); exit;
}

$items_json = json_encode($body['items'], JSON_UNESCAPED_UNICODE);
$total = 0.0;
foreach($body['items'] as $it) {
    $qty = (int)($it['qty'] ?? 0);
    $price = floatval($it['price'] ?? 0);
    $total += $qty * $price;
}

// basic sanitize
$student_id = isset($body['student_id']) ? $conn->real_escape_string($body['student_id']) : null;

$stmt = $conn->prepare("INSERT INTO orders (items, total) VALUES (?, ?)");
$stmt->bind_param('sd', $items_json, $total);
if ($stmt->execute()) {
    echo json_encode(['success'=>true, 'order_id'=>$stmt->insert_id]);
} else {
    http_response_code(500);
    echo json_encode(['success'=>false, 'message'=>'Failed to save order']);
}
$stmt->close();
