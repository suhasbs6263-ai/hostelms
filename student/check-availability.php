<?php
require_once('../includes/dbconn.php');
require_once('../includes/room-helpers.php');

if (isset($_POST['emailid'])) {
    $email = trim($_POST['emailid']);
    $stmt = $mysqli->prepare("SELECT id FROM userregistration WHERE email = ? LIMIT 1");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $exists = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($exists) {
        echo '<span class="text-danger">Email already registered.</span>';
    } else {
        echo '<span class="text-success">Email available.</span>';
    }

    exit;
}

if (isset($_POST['roomno'])) {
    $roomNo = trim($_POST['roomno']);
    $room = get_room_capacity($mysqli, $roomNo);

    if (!$room) {
        echo '<span class="text-danger">Room does not exist.</span>';
        exit;
    }

    if ($room['available_seats'] <= 0) {
        echo '<span class="text-danger">Room is full.</span>';
    } else {
        echo '<span class="text-success">Room available. ' . htmlspecialchars((string) $room['booked_count'], ENT_QUOTES, 'UTF-8') . '/' . htmlspecialchars((string) $room['seater'], ENT_QUOTES, 'UTF-8') . ' occupied, ' . htmlspecialchars((string) $room['available_seats'], ENT_QUOTES, 'UTF-8') . ' seat(s) left.</span>';
    }
}
?>
