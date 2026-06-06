<?php

require_once(__DIR__ . '/activity-helpers.php');
require_once(__DIR__ . '/notification-helpers.php');
require_once(__DIR__ . '/student-helpers.php');
require_once(__DIR__ . '/security-helpers.php');

if (!function_exists('sync_room_capacity_columns')) {
    function sync_room_capacity_columns(mysqli $mysqli): void
    {
        $mysqli->query("UPDATE rooms SET capacity = CASE WHEN capacity <= 0 THEN seater ELSE capacity END");
        $mysqli->query("UPDATE rooms SET seater = CASE WHEN seater <= 0 THEN capacity ELSE seater END");
        $mysqli->query(
            "UPDATE rooms
             SET status = CASE
                WHEN status = 'maintenance' THEN 'maintenance'
                WHEN capacity <= 0 THEN 'inactive'
                WHEN occupied_beds >= capacity THEN 'full'
                ELSE 'available'
             END"
        );
    }
}

if (!function_exists('fetch_rooms')) {
    function fetch_rooms(mysqli $mysqli, bool $includeInactive = true): array
    {
        sync_room_capacity_columns($mysqli);
        recalculate_room_occupancy($mysqli);

        $rooms = [];
        $sql = "SELECT id, room_no, room_type, capacity, occupied_beds, fees, status, created_at, updated_at
                FROM rooms";

        if (!$includeInactive) {
            $sql .= " WHERE status <> 'inactive'";
        }

        $sql .= " ORDER BY room_no ASC";

        $result = $mysqli->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $row['capacity'] = (int) $row['capacity'];
                $row['occupied_beds'] = (int) $row['occupied_beds'];
                $row['available_beds'] = max($row['capacity'] - $row['occupied_beds'], 0);
                $rooms[] = $row;
            }
            $result->close();
        }

        return $rooms;
    }
}

if (!function_exists('fetch_available_rooms')) {
    function fetch_available_rooms(mysqli $mysqli): array
    {
        $rooms = fetch_rooms($mysqli, false);

        return array_values(array_filter($rooms, static function (array $room): bool {
            return $room['status'] === 'available' && $room['available_beds'] > 0;
        }));
    }
}

if (!function_exists('fetch_room_by_id')) {
    function fetch_room_by_id(mysqli $mysqli, int $roomId): ?array
    {
        $stmt = $mysqli->prepare(
            "SELECT id, room_no, room_type, capacity, occupied_beds, fees, status, created_at, updated_at
             FROM rooms
             WHERE id = ?
             LIMIT 1"
        );

        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('i', $roomId);
        $stmt->execute();
        $result = $stmt->get_result();
        $room = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        if (!$room) {
            return null;
        }

        $room['capacity'] = (int) $room['capacity'];
        $room['occupied_beds'] = (int) $room['occupied_beds'];
        $room['available_beds'] = max($room['capacity'] - $room['occupied_beds'], 0);

        return $room;
    }
}

if (!function_exists('fetch_room_by_number')) {
    function fetch_room_by_number(mysqli $mysqli, string $roomNumber): ?array
    {
        $stmt = $mysqli->prepare(
            "SELECT id, room_no, room_type, capacity, occupied_beds, fees, status, created_at, updated_at
             FROM rooms
             WHERE room_no = ?
             LIMIT 1"
        );

        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('s', $roomNumber);
        $stmt->execute();
        $result = $stmt->get_result();
        $room = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        if (!$room) {
            return null;
        }

        $room['capacity'] = (int) $room['capacity'];
        $room['occupied_beds'] = (int) $room['occupied_beds'];
        $room['available_beds'] = max($room['capacity'] - $room['occupied_beds'], 0);

        return $room;
    }
}

if (!function_exists('create_room_record')) {
    function create_room_record(mysqli $mysqli, array $data): array
    {
        $roomNumber = normalize_text($data['room_no'] ?? '');
        $roomType = normalize_text($data['room_type'] ?? 'Standard');
        $capacity = isset($data['capacity']) ? (int) $data['capacity'] : 0;
        $fees = isset($data['fees']) ? (float) $data['fees'] : 0.0;
        $status = normalize_text($data['status'] ?? 'available');

        $errors = [];
        if ($roomNumber === '') {
            $errors['room_no'] = 'Room number is required.';
        }
        if ($capacity <= 0) {
            $errors['capacity'] = 'Capacity must be greater than zero.';
        }
        if ($fees < 0) {
            $errors['fees'] = 'Fees cannot be negative.';
        }
        if (!in_array($status, ['available', 'full', 'maintenance', 'inactive'], true)) {
            $status = 'available';
        }

        if (fetch_room_by_number($mysqli, $roomNumber)) {
            $errors['room_no'] = 'Room number already exists.';
        }

        if ($errors) {
            return ['ok' => false, 'errors' => $errors];
        }

        $stmt = $mysqli->prepare(
            "INSERT INTO rooms (room_no, room_type, capacity, occupied_beds, fees, status, seater, created_at, updated_at)
             VALUES (?, ?, ?, 0, ?, ?, ?, NOW(), NOW())"
        );

        if (!$stmt) {
            return ['ok' => false, 'errors' => ['general' => 'Unable to save the room.']];
        }

        $seater = $capacity;
        $stmt->bind_param('ssidsi', $roomNumber, $roomType, $capacity, $fees, $status, $seater);
        $ok = $stmt->execute();
        $stmt->close();

        return ['ok' => $ok, 'errors' => $ok ? [] : ['general' => 'Unable to save the room.']];
    }
}

if (!function_exists('fetch_pending_room_requests')) {
    function fetch_pending_room_requests(mysqli $mysqli): array
    {
        $requests = [];
        $result = $mysqli->query(
            "SELECT ra.*, s.registration_number, s.first_name, s.middle_name, s.last_name, s.email, s.phone,
                    pr.room_no AS preferred_room_no, pr.room_type AS preferred_room_type
             FROM room_allocations ra
             INNER JOIN students s ON s.id = ra.student_id
             LEFT JOIN rooms pr ON pr.id = ra.preferred_room_id
             WHERE ra.status = 'pending'
             ORDER BY ra.created_at ASC"
        );

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $requests[] = $row;
            }
            $result->close();
        }

        return $requests;
    }
}

if (!function_exists('fetch_all_allocations')) {
    function fetch_all_allocations(mysqli $mysqli): array
    {
        $allocations = [];
        $result = $mysqli->query(
            "SELECT ra.*, s.registration_number, s.first_name, s.middle_name, s.last_name, s.email, s.phone,
                    r.room_no, r.room_type
             FROM room_allocations ra
             INNER JOIN students s ON s.id = ra.student_id
             LEFT JOIN rooms r ON r.id = ra.assigned_room_id
             ORDER BY ra.created_at DESC"
        );

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $allocations[] = $row;
            }
            $result->close();
        }

        return $allocations;
    }
}

if (!function_exists('count_active_allocations_for_student')) {
    function count_active_allocations_for_student(mysqli $mysqli, int $studentId, int $excludeRequestId = 0): int
    {
        $stmt = $mysqli->prepare(
            "SELECT COUNT(*) AS total
             FROM room_allocations
             WHERE student_id = ?
             AND status IN ('pending', 'allocated')
             AND id <> ?"
        );

        if (!$stmt) {
            return 0;
        }

        $stmt->bind_param('ii', $studentId, $excludeRequestId);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result ? (int) ($result->fetch_assoc()['total'] ?? 0) : 0;
        $stmt->close();

        return $count;
    }
}

if (!function_exists('fetch_student_allocation')) {
    function fetch_student_allocation(mysqli $mysqli, int $studentId): ?array
    {
        $stmt = $mysqli->prepare(
            "SELECT ra.*, r.room_no, r.room_type, r.capacity, r.occupied_beds, r.fees
             FROM room_allocations ra
             LEFT JOIN rooms r ON r.id = ra.assigned_room_id
             WHERE ra.student_id = ? AND ra.status = 'allocated'
             ORDER BY ra.allocated_at DESC, ra.id DESC
             LIMIT 1"
        );

        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('i', $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $allocation = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        return $allocation ?: null;
    }
}

if (!function_exists('fetch_latest_student_request')) {
    function fetch_latest_student_request(mysqli $mysqli, int $studentId): ?array
    {
        $stmt = $mysqli->prepare(
            "SELECT ra.*, pr.room_no AS preferred_room_no, pr.room_type AS preferred_room_type
             FROM room_allocations ra
             LEFT JOIN rooms pr ON pr.id = ra.preferred_room_id
             WHERE ra.student_id = ?
             ORDER BY ra.id DESC
             LIMIT 1"
        );

        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('i', $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $request = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        return $request ?: null;
    }
}

if (!function_exists('submit_room_request')) {
    function submit_room_request(mysqli $mysqli, int $studentId, array $data): array
    {
        $preferredRoomId = (int) ($data['preferred_room_id'] ?? 0);
        $stayFrom = (string) ($data['stay_from'] ?? '');
        $durationMonths = (int) ($data['duration_months'] ?? 0);
        $foodStatus = (int) ($data['food_status'] ?? 0);
        $notes = normalize_text($data['requested_notes'] ?? '');
        $foodStatus = $foodStatus === 1 ? 1 : 0;

        $student = fetch_student_by_id($mysqli, $studentId);
        if (!$student) {
            return ['ok' => false, 'errors' => ['general' => 'Student record not found.']];
        }

        if (($student['status'] ?? 'pending') !== 'approved') {
            return ['ok' => false, 'errors' => ['general' => 'Only approved students can apply for a hostel room.']];
        }

        $existingAllocation = fetch_student_allocation($mysqli, $studentId);
        if ($existingAllocation) {
            return ['ok' => false, 'errors' => ['general' => 'You already have a room allocation.']];
        }

        $existingRequest = fetch_latest_student_request($mysqli, $studentId);
        if ($existingRequest && in_array($existingRequest['status'], ['pending', 'allocated'], true)) {
            return ['ok' => false, 'errors' => ['general' => 'You already have an active hostel request.']];
        }

        $room = fetch_room_by_id($mysqli, $preferredRoomId);
        if (!$room) {
            return ['ok' => false, 'errors' => ['preferred_room_id' => 'Please choose a valid room.']];
        }

        if ($room['status'] !== 'available' || $room['available_beds'] <= 0) {
            return ['ok' => false, 'errors' => ['preferred_room_id' => 'The selected room has no available beds.']];
        }

        if ($stayFrom === '') {
            return ['ok' => false, 'errors' => ['stay_from' => 'Stay from date is required.']];
        }

        $stayDate = DateTime::createFromFormat('!Y-m-d', $stayFrom);
        if (!$stayDate || $stayDate->format('Y-m-d') !== $stayFrom) {
            return ['ok' => false, 'errors' => ['stay_from' => 'Choose a valid stay from date.']];
        }

        $today = new DateTime('today');
        if ($stayDate < $today) {
            return ['ok' => false, 'errors' => ['stay_from' => 'Stay from date cannot be in the past.']];
        }

        if ($durationMonths <= 0 || $durationMonths > 24) {
            return ['ok' => false, 'errors' => ['duration_months' => 'Duration must be between 1 and 24 months.']];
        }

        $monthlyFee = (float) $room['fees'];
        $stmt = $mysqli->prepare(
            "INSERT INTO room_allocations (
                student_id, preferred_room_id, stay_from, duration_months, food_status, monthly_fee, status, requested_notes, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, NOW(), NOW())"
        );

        if (!$stmt) {
            return ['ok' => false, 'errors' => ['general' => 'Unable to submit the hostel request.']];
        }

        $stmt->bind_param('iisiids', $studentId, $preferredRoomId, $stayFrom, $durationMonths, $foodStatus, $monthlyFee, $notes);
        $ok = $stmt->execute();
        $requestId = (int) $stmt->insert_id;
        $stmt->close();

        if (!$ok) {
            return ['ok' => false, 'errors' => ['general' => 'Unable to submit the hostel request.']];
        }

        create_notification($mysqli, 'student', $studentId, 'Room request submitted', 'Your hostel room application has been submitted for admin review.', '../student/book-hostel.php');
        create_notifications_for_role($mysqli, 'admin', 'New room request', 'A new hostel room request is waiting for allocation review.', '../admin/book-hostel.php');
        log_activity($mysqli, 'student', $studentId, 'room_request_submitted', 'Student requested room #' . $room['room_no'] . '.');

        return ['ok' => true, 'errors' => [], 'request_id' => $requestId];
    }
}

if (!function_exists('allocate_room_request')) {
    function allocate_room_request(mysqli $mysqli, int $requestId, int $roomId, int $adminId, string $remarks = ''): array
    {
        recalculate_room_occupancy($mysqli);

        $stmt = $mysqli->prepare("SELECT * FROM room_allocations WHERE id = ? LIMIT 1");
        if (!$stmt) {
            return ['ok' => false, 'error' => 'Unable to load request.'];
        }
        $stmt->bind_param('i', $requestId);
        $stmt->execute();
        $result = $stmt->get_result();
        $request = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        if (!$request || $request['status'] !== 'pending') {
            return ['ok' => false, 'error' => 'The selected request is no longer pending.'];
        }

        if (count_active_allocations_for_student($mysqli, (int) $request['student_id'], $requestId) > 0) {
            return ['ok' => false, 'error' => 'This student already has another active hostel request or allocation.'];
        }

        $room = fetch_room_by_id($mysqli, $roomId);
        if (!$room || $room['available_beds'] <= 0 || $room['status'] !== 'available') {
            return ['ok' => false, 'error' => 'The selected room is not available for allocation.'];
        }

        $update = $mysqli->prepare(
            "UPDATE room_allocations
             SET assigned_room_id = ?, monthly_fee = ?, status = 'allocated', admin_remarks = ?, allocated_by_admin_id = ?, allocated_at = NOW(), updated_at = NOW()
             WHERE id = ?"
        );

        if (!$update) {
            return ['ok' => false, 'error' => 'Unable to allocate the room.'];
        }

        $monthlyFee = (float) $room['fees'];
        $update->bind_param('idsii', $roomId, $monthlyFee, $remarks, $adminId, $requestId);
        $ok = $update->execute();
        $update->close();

        if (!$ok) {
            return ['ok' => false, 'error' => 'Unable to allocate the room.'];
        }

        recalculate_room_occupancy($mysqli);

        create_notification($mysqli, 'student', (int) $request['student_id'], 'Room allocated', 'Your hostel room has been allocated successfully.', '../student/room-details.php');
        log_activity($mysqli, 'admin', $adminId, 'room_allocated', 'Allocation #' . $requestId . ' was assigned to room #' . $room['room_no'] . '.');

        return ['ok' => true, 'error' => ''];
    }
}

if (!function_exists('reject_room_request')) {
    function reject_room_request(mysqli $mysqli, int $requestId, int $adminId, string $remarks = ''): bool
    {
        $stmt = $mysqli->prepare(
            "UPDATE room_allocations
             SET status = 'rejected', admin_remarks = ?, allocated_by_admin_id = ?, updated_at = NOW()
             WHERE id = ? AND status = 'pending'"
        );

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('sii', $remarks, $adminId, $requestId);
        $ok = $stmt->execute();
        $stmt->close();

        if ($ok) {
            $lookup = $mysqli->prepare("SELECT student_id FROM room_allocations WHERE id = ? LIMIT 1");
            if ($lookup) {
                $lookup->bind_param('i', $requestId);
                $lookup->execute();
                $result = $lookup->get_result();
                $request = $result ? $result->fetch_assoc() : null;
                $lookup->close();

                if ($request) {
                    create_notification($mysqli, 'student', (int) $request['student_id'], 'Room request rejected', 'Your hostel room application was rejected. Please review the admin remarks and apply again.', '../student/book-hostel.php');
                }
            }

            log_activity($mysqli, 'admin', $adminId, 'room_request_rejected', 'Allocation request #' . $requestId . ' was rejected.');
        }

        return $ok;
    }
}
