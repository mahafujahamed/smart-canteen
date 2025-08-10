<?php
require_once __DIR__ . '/../headers.php';
require_once __DIR__ . '/../db.php';
session_start();
if (empty($_SESSION['admin_id'])) {
    http_response_code(401); echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit;
}

$limit = 200;
$res = $conn->query("SELECT id, student_id, items, total, status, pickup_time, created_at FROM orders ORDER BY id DESC LIMIT $limit");
$data = [];
while ($r = $res->fetch_assoc()) {
    $r['items'] = json_decode($r['items'], true);
    $data[] = $r;
}
echo json_encode(['success'=>true,'data'=>$data]);
