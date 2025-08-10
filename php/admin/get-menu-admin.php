<?php
require_once __DIR__ . '/../headers.php';
require_once __DIR__ . '/../db.php';
session_start();
if (empty($_SESSION['admin_id'])) { http_response_code(401); echo json_encode(['success'=>false]); exit; }

$res = $conn->query("SELECT id, name, price, image FROM menu_items ORDER BY id DESC");
$rows = [];
while($r = $res->fetch_assoc()) $rows[] = $r;
echo json_encode(['success'=>true,'data'=>$rows]);
