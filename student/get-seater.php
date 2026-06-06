<?php
require_once('../includes/dbconn.php');
require_once('../includes/room-helpers.php');

if (isset($_POST['roomid'])) {
    $roomNo = normalize_text($_POST['roomid']);
    $room = fetch_room_by_number($mysqli, $roomNo);
    echo $room['capacity'] ?? '';
    exit;
}

if (isset($_POST['rid'])) {
    $roomNo = normalize_text($_POST['rid']);
    $room = fetch_room_by_number($mysqli, $roomNo);
    echo $room['fees'] ?? '';
}
?>
