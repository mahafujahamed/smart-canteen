<?php
require_once '../db.php';
$result = $conn->query("SELECT * FROM menu_items");
$menu = [];
while($row = $result->fetch_assoc()) {
    $menu[] = $row;
}
echo json_encode($menu);
?>
