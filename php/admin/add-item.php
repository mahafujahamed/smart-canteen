<?php
require_once __DIR__ . '/../headers.php';
require_once __DIR__ . '/../db.php';
session_start();
if (empty($_SESSION['admin_id'])) { http_response_code(401); echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }

$name = $_POST['name'] ?? '';
$desc = $_POST['description'] ?? '';
$price = isset($_POST['price']) ? floatval($_POST['price']) : 0.0;
$imageName = null;

if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $f = $_FILES['image'];
    $ext = pathinfo($f['name'], PATHINFO_EXTENSION);
    $allowed = ['jpg','jpeg','png','webp','gif'];
    if (!in_array(strtolower($ext), $allowed)) {
        echo json_encode(['success'=>false,'message'=>'Invalid image type']); exit;
    }
    $imageName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $f['name']);
    $dest = __DIR__ . '/../../uploads/' . $imageName;
    if (!move_uploaded_file($f['tmp_name'], $dest)) {
        echo json_encode(['success'=>false,'message'=>'Upload failed']); exit;
    }
}

$stmt = $conn->prepare("INSERT INTO menu_items (name, description, price, image) VALUES (?, ?, ?, ?)");
$stmt->bind_param('ssds', $name, $desc, $price, $imageName);
if ($stmt->execute()) echo json_encode(['success'=>true, 'id'=>$stmt->insert_id]);
else { http_response_code(500); echo json_encode(['success'=>false,'message'=>'DB error']); }
$stmt->close();
