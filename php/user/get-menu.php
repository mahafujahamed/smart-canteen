<?php
require_once __DIR__ . '/../headers.php';
require_once __DIR__ . '/../db.php';

$stmt = $conn->prepare("SELECT id, name, description, price, image FROM menu_items WHERE active = 1 ORDER BY id DESC");
$stmt->execute();
$res = $stmt->get_result();
$data = [];
while ($row = $res->fetch_assoc()) $data[] = $row;
echo json_encode($data);
$stmt->close();
