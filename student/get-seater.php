<?php
require_once('../includes/dbconn.php');

if (isset($_POST['roomid'])) {
    $roomNo = trim($_POST['roomid']);
    $stmt = $mysqli->prepare("SELECT seater FROM rooms WHERE room_no = ? LIMIT 1");
    $stmt->bind_param('s', $roomNo);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    echo $result['seater'] ?? '';
    exit;
}

if (isset($_POST['rid'])) {
    $roomNo = trim($_POST['rid']);
    $stmt = $mysqli->prepare("SELECT fees FROM rooms WHERE room_no = ? LIMIT 1");
    $stmt->bind_param('s', $roomNo);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    echo $result['fees'] ?? '';
}
?>
