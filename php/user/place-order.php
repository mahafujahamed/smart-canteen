<?php
require_once __DIR__ . '/../headers.php';
require_once __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success'=>false,'message'=>'Method Not Allowed']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
if (!$body || !isset($body['items']) || !is_array($body['items'])) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'Bad request']);
    exit;
}

$student_id = isset($body['student_id']) ? trim($body['student_id']) : null;
$pickup_time = isset($body['pickup_time']) && $body['pickup_time'] ? trim($body['pickup_time']) : null; // expected ISO or datetime-local string
$items = $body['items'];

// Security: validate each item against DB and compute total server-side
$total = 0.0;
$final_items = [];

$stmt = $conn->prepare("SELECT id, name, price FROM menu_items WHERE id = ? AND active = 1");

foreach ($items as $it) {
    $pid = intval($it['id'] ?? 0);
    $qty = intval($it['qty'] ?? 0);
    if ($qty <= 0 || $pid <= 0) {
        continue;
    }
    $stmt->bind_param('i', $pid);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $price = floatval($row['price']);
        $subtotal = $price * $qty;
        $total += $subtotal;
        $final_items[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'price' => $price,
            'qty' => $qty
        ];
    } else {
        // invalid product id
        http_response_code(400);
        echo json_encode(['success'=>false,'message'=>'Invalid product id: ' . $pid]);
        exit;
    }
}
$stmt->close();

if (count($final_items) === 0) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'No valid items in order']);
    exit;
}

// convert pickup_time to MySQL DATETIME if provided
$pickup_time_sql = null;
if ($pickup_time) {
    // Accept ISO 8601 or datetime-local format and try to convert
    $ts = strtotime($pickup_time);
    if ($ts === false) $pickup_time_sql = null;
    else $pickup_time_sql = date('Y-m-d H:i:s', $ts);
}

// insert order
$items_json = json_encode($final_items, JSON_UNESCAPED_UNICODE);
$ins = $conn->prepare("INSERT INTO orders (student_id, items, total, pickup_time) VALUES (?, ?, ?, ?)");
$ins->bind_param('ssds', $student_id, $items_json, $total, $pickup_time_sql);
if ($ins->execute()) {
    echo json_encode(['success'=>true,'order_id'=>$ins->insert_id, 'total'=>$total]);
} else {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Failed to store order']);
}
$ins->close();
