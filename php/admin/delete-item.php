<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../db.php';
session_start();
if (empty($_SESSION['admin'])) { http_response_code(401); echo json_encode(['success'=>false]); exit; }

$body = json_decode(file_get_contents('php://input'), true);
$id = isset($body['id']) ? intval($body['id']) : 0;
if (!$id) { echo json_encode(['success'=>false,'message'=>'Bad id']); exit; }

// optional: fetch image name to unlink
$stmt = $conn->prepare("SELECT image FROM menu_items WHERE id = ?");
$stmt->bind_param('i',$id);
$stmt->execute();
$stmt->bind_result($img); $stmt->fetch(); $stmt->close();

$stmt2 = $conn->prepare("DELETE FROM menu_items WHERE id = ?");
$stmt2->bind_param('i',$id);
if ($stmt2->execute()) {
    if ($img) {
        $p = __DIR__ . '/../../uploads/' . $img;
        if (file_exists($p)) @unlink($p);
    }
    echo json_encode(['success'=>true]);
} else {
    echo json_encode(['success'=>false,'message'=>'Delete failed']);
}
$stmt2->close();
