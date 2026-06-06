<?php

require_once(__DIR__ . '/activity-helpers.php');
require_once(__DIR__ . '/notification-helpers.php');
require_once(__DIR__ . '/security-helpers.php');

if (!function_exists('fetch_student_complaints')) {
    function fetch_student_complaints(mysqli $mysqli, int $studentId): array
    {
        $complaints = [];
        $stmt = $mysqli->prepare(
            "SELECT c.*, w.full_name AS warden_name
             FROM complaints c
             LEFT JOIN wardens w ON w.id = c.assigned_warden_id
             WHERE c.student_id = ?
             ORDER BY c.created_at DESC, c.id DESC"
        );

        if (!$stmt) {
            return $complaints;
        }

        $stmt->bind_param('i', $studentId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $complaints[] = $row;
            }
        }

        $stmt->close();
        return $complaints;
    }
}

if (!function_exists('fetch_all_complaints')) {
    function fetch_all_complaints(mysqli $mysqli, ?int $wardenId = null): array
    {
        $complaints = [];
        $sql = "SELECT c.*, s.registration_number, s.first_name, s.middle_name, s.last_name, w.full_name AS warden_name
                FROM complaints c
                INNER JOIN students s ON s.id = c.student_id
                LEFT JOIN wardens w ON w.id = c.assigned_warden_id";

        if ($wardenId !== null) {
            $sql .= " WHERE c.assigned_warden_id = ? OR c.assigned_warden_id IS NULL";
        }

        $sql .= " ORDER BY c.created_at DESC, c.id DESC";

        if ($wardenId !== null) {
            $stmt = $mysqli->prepare($sql);

            if (!$stmt) {
                return $complaints;
            }

            $stmt->bind_param('i', $wardenId);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $mysqli->query($sql);
            $stmt = null;
        }

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $complaints[] = $row;
            }
        }

        if ($stmt) {
            $stmt->close();
        } elseif ($result) {
            $result->close();
        }

        return $complaints;
    }
}

if (!function_exists('fetch_complaint_by_id')) {
    function fetch_complaint_by_id(mysqli $mysqli, int $complaintId): ?array
    {
        $stmt = $mysqli->prepare("SELECT * FROM complaints WHERE id = ? LIMIT 1");

        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('i', $complaintId);
        $stmt->execute();
        $result = $stmt->get_result();
        $complaint = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        return $complaint ?: null;
    }
}

if (!function_exists('create_complaint_record')) {
    function create_complaint_record(mysqli $mysqli, int $studentId, array $data): array
    {
        $subject = normalize_text($data['subject'] ?? '');
        $description = normalize_text($data['description'] ?? '');
        $priority = normalize_text($data['priority'] ?? 'medium');

        $errors = validate_required_fields(
            ['subject' => $subject, 'description' => $description],
            ['subject' => 'Subject', 'description' => 'Complaint description']
        );

        if (!in_array($priority, ['low', 'medium', 'high'], true)) {
            $priority = 'medium';
        }

        if ($errors) {
            return ['ok' => false, 'errors' => $errors];
        }

        $stmt = $mysqli->prepare(
            "INSERT INTO complaints (student_id, subject, description, priority, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, 'pending', NOW(), NOW())"
        );

        if (!$stmt) {
            return ['ok' => false, 'errors' => ['general' => 'Unable to submit complaint right now.']];
        }

        $stmt->bind_param('isss', $studentId, $subject, $description, $priority);
        $ok = $stmt->execute();
        $complaintId = (int) $stmt->insert_id;
        $stmt->close();

        if (!$ok) {
            return ['ok' => false, 'errors' => ['general' => 'Unable to submit complaint right now.']];
        }

        log_activity($mysqli, 'student', $studentId, 'complaint_submitted', 'Complaint #' . $complaintId . ' submitted.');
        create_notification($mysqli, 'student', $studentId, 'Complaint submitted', 'Your complaint has been recorded and is pending review.', '../student/complaints.php');
        create_notifications_for_role($mysqli, 'admin', 'New complaint submitted', 'A student complaint needs review or warden assignment.', '../admin/complaints.php');

        return ['ok' => true, 'errors' => [], 'complaint_id' => $complaintId];
    }
}

if (!function_exists('update_complaint_record')) {
    function update_complaint_record(mysqli $mysqli, int $complaintId, string $status, string $remarks = '', ?int $wardenId = null): bool
    {
        if (!in_array($status, ['pending', 'in_progress', 'resolved'], true)) {
            return false;
        }

        if ($wardenId !== null) {
            $wardenCheck = $mysqli->prepare("SELECT id FROM wardens WHERE id = ? AND status = 'active' LIMIT 1");

            if (!$wardenCheck) {
                return false;
            }

            $wardenCheck->bind_param('i', $wardenId);
            $wardenCheck->execute();
            $warden = $wardenCheck->get_result()->fetch_assoc();
            $wardenCheck->close();

            if (!$warden) {
                return false;
            }
        }

        $stmt = $mysqli->prepare(
            "UPDATE complaints
             SET status = ?, remarks = ?, assigned_warden_id = ?, resolved_at = CASE WHEN ? = 'resolved' THEN NOW() ELSE resolved_at END, updated_at = NOW()
             WHERE id = ?"
        );

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('ssisi', $status, $remarks, $wardenId, $status, $complaintId);
        $ok = $stmt->execute();
        $stmt->close();

        if (!$ok) {
            return false;
        }

        $lookup = $mysqli->prepare("SELECT student_id FROM complaints WHERE id = ? LIMIT 1");
        if ($lookup) {
            $lookup->bind_param('i', $complaintId);
            $lookup->execute();
            $result = $lookup->get_result();
            $complaint = $result ? $result->fetch_assoc() : null;
            $lookup->close();

            if ($complaint) {
                create_notification(
                    $mysqli,
                    'student',
                    (int) $complaint['student_id'],
                    'Complaint updated',
                    'Your complaint status is now ' . str_replace('_', ' ', ucfirst($status)) . '.',
                    '../student/complaints.php'
                );
            }
        }

        if ($wardenId) {
            create_notification(
                $mysqli,
                'warden',
                $wardenId,
                'Complaint assigned/updated',
                'A complaint has been assigned to you or updated for action.',
                '../warden/complaints.php'
            );
        }

        return true;
    }
}

if (!function_exists('count_complaints_by_status')) {
    function count_complaints_by_status(mysqli $mysqli, string $status): int
    {
        $stmt = $mysqli->prepare("SELECT COUNT(*) AS total FROM complaints WHERE status = ?");

        if (!$stmt) {
            return 0;
        }

        $stmt->bind_param('s', $status);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result ? (int) ($result->fetch_assoc()['total'] ?? 0) : 0;
        $stmt->close();

        return $count;
    }
}
