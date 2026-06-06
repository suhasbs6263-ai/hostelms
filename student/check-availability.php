<?php
require_once('../includes/dbconn.php');
require_once('../includes/room-helpers.php');
require_once('../includes/student-helpers.php');

if (isset($_POST['emailid'])) {
    $email = strtolower(normalize_text($_POST['emailid']));
    $exists = fetch_student_by_email($mysqli, $email);

    if ($exists) {
        echo '<span class="text-danger">Email already registered.</span>';
    } else {
        echo '<span class="text-success">Email available.</span>';
    }

    exit;
}

if (isset($_POST['roomno'])) {
    $roomNo = normalize_text($_POST['roomno']);
    $room = fetch_room_by_number($mysqli, $roomNo);

    if (!$room) {
        echo '<span class="text-danger">Room does not exist.</span>';
        exit;
    }

    if (($room['available_beds'] ?? 0) <= 0 || ($room['status'] ?? '') !== 'available') {
        echo '<span class="text-danger">Room is full or unavailable.</span>';
    } else {
        echo '<span class="text-success">Room available. ' . e((string) $room['occupied_beds']) . '/' . e((string) $room['capacity']) . ' occupied, ' . e((string) $room['available_beds']) . ' bed(s) left.</span>';
    }
}
?>
