<?php

if (!function_exists('create_notification')) {
    function create_notification(mysqli $mysqli, string $userType, int $userId, string $title, string $message, string $link = ''): void
    {
        $stmt = $mysqli->prepare(
            "INSERT INTO notifications (user_type, user_id, title, message, link, is_read, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, 0, NOW(), NOW())"
        );

        if (!$stmt) {
            return;
        }

        $stmt->bind_param('sisss', $userType, $userId, $title, $message, $link);
        $stmt->execute();
        $stmt->close();
    }
}

if (!function_exists('create_notifications_for_role')) {
    function create_notifications_for_role(mysqli $mysqli, string $role, string $title, string $message, string $link = ''): void
    {
        $table = match ($role) {
            'admin' => 'admins',
            'warden' => 'wardens',
            default => '',
        };

        if ($table === '') {
            return;
        }

        $result = $mysqli->query("SELECT id FROM {$table} WHERE status = 'active'");
        if (!$result) {
            return;
        }

        while ($row = $result->fetch_assoc()) {
            create_notification($mysqli, $role, (int) $row['id'], $title, $message, $link);
        }

        $result->close();
    }
}

if (!function_exists('fetch_notifications')) {
    function fetch_notifications(mysqli $mysqli, string $userType, int $userId, int $limit = 25): array
    {
        $limit = max(1, min($limit, 100));
        $notifications = [];
        $stmt = $mysqli->prepare(
            "SELECT id, title, message, link, is_read, created_at
             FROM notifications
             WHERE user_type = ? AND user_id = ?
             ORDER BY id DESC
             LIMIT {$limit}"
        );

        if (!$stmt) {
            return $notifications;
        }

        $stmt->bind_param('si', $userType, $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $notifications[] = $row;
            }
        }

        $stmt->close();
        return $notifications;
    }
}

if (!function_exists('count_unread_notifications')) {
    function count_unread_notifications(mysqli $mysqli, string $userType, int $userId): int
    {
        $stmt = $mysqli->prepare(
            "SELECT COUNT(*) AS total
             FROM notifications
             WHERE user_type = ? AND user_id = ? AND is_read = 0"
        );

        if (!$stmt) {
            return 0;
        }

        $stmt->bind_param('si', $userType, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result ? (int) ($result->fetch_assoc()['total'] ?? 0) : 0;
        $stmt->close();

        return $count;
    }
}

if (!function_exists('mark_notification_read')) {
    function mark_notification_read(mysqli $mysqli, int $notificationId, string $userType, int $userId): void
    {
        $stmt = $mysqli->prepare(
            "UPDATE notifications
             SET is_read = 1, updated_at = NOW()
             WHERE id = ? AND user_type = ? AND user_id = ?"
        );

        if (!$stmt) {
            return;
        }

        $stmt->bind_param('isi', $notificationId, $userType, $userId);
        $stmt->execute();
        $stmt->close();
    }
}
