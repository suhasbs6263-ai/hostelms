<?php

function fetch_bookable_rooms(mysqli $mysqli, bool $includeFull = false): array
{
    $rooms = [];
    $result = $mysqli->query(
        "SELECT r.room_no, r.seater, r.fees, COUNT(reg.id) AS booked_count
         FROM rooms r
         LEFT JOIN registration reg ON reg.roomno = r.room_no
         GROUP BY r.room_no, r.seater, r.fees
         ORDER BY r.room_no ASC"
    );

    if (!$result) {
        return $rooms;
    }

    while ($room = $result->fetch_assoc()) {
        $room['seater'] = (int) ($room['seater'] ?? 0);
        $room['fees'] = (string) ($room['fees'] ?? '0');
        $room['booked_count'] = (int) ($room['booked_count'] ?? 0);
        $room['available_seats'] = max($room['seater'] - $room['booked_count'], 0);
        $room['is_full'] = $room['available_seats'] <= 0;

        if (!$includeFull && $room['is_full']) {
            continue;
        }

        $rooms[] = $room;
    }

    $result->close();
    return $rooms;
}

function get_room_capacity(mysqli $mysqli, string $roomNo): ?array
{
    $stmt = $mysqli->prepare(
        "SELECT r.room_no, r.seater, r.fees, COUNT(reg.id) AS booked_count
         FROM rooms r
         LEFT JOIN registration reg ON reg.roomno = r.room_no
         WHERE r.room_no = ?
         GROUP BY r.room_no, r.seater, r.fees
         LIMIT 1"
    );

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('s', $roomNo);
    $stmt->execute();
    $result = $stmt->get_result();
    $room = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if (!$room) {
        return null;
    }

    $room['seater'] = (int) ($room['seater'] ?? 0);
    $room['fees'] = (string) ($room['fees'] ?? '0');
    $room['booked_count'] = (int) ($room['booked_count'] ?? 0);
    $room['available_seats'] = max($room['seater'] - $room['booked_count'], 0);
    $room['is_full'] = $room['available_seats'] <= 0;

    return $room;
}
