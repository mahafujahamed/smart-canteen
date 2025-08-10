<?php
// Run this once (via browser or CLI) to create initial admin user.
// After running, delete this file or protect it.

require_once __DIR__ . '/db.php';

$username = 'admin';
$password = 'admin123'; // change immediately after first login

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO admin_users (username, password_hash) VALUES (?, ?)");
$stmt->bind_param('ss', $username, $hash);
if ($stmt->execute()) {
    echo json_encode(['success'=>true, 'message'=>'Admin created', 'username'=>$username]);
} else {
    header("HTTP/1.1 500 Internal Server Error");
    echo json_encode(['success'=>false, 'error'=>$stmt->error]);
}
$stmt->close();
