<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../db.php';
session_start();
if (empty($_SESSION['admin'])) {
    http_response_code(401); echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit;
}

$res = $conn->query("SELECT id, items, total, order_time FROM orders ORDER BY id DESC LIMIT 200");
$data = [];
while($r = $res->fetch_assoc()) {
    $r['items'] = json_encode(json_decode($r['items']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    $data[] = $r;
}
echo json_encode(['success'=>true,'data'=>$data]);
