<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../db.php';
session_start();
if (empty($_SESSION['admin'])) { http_response_code(401); echo json_encode(['success'=>false]); exit; }

$res = $conn->query("SELECT id, name, price FROM menu_items ORDER BY id DESC");
$rows = [];
while($r = $res->fetch_assoc()) $rows[] = $r;
echo json_encode(['success'=>true,'data'=>$rows]);
