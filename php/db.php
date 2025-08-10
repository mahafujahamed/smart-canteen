<?php
// DB connection
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "smart_canteen";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'DB connect error']);
    exit;
}
$conn->set_charset('utf8mb4');
