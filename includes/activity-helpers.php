<?php

if (!function_exists('log_activity')) {
    function log_activity(mysqli $mysqli, string $actorType, ?int $actorId, string $action, string $description = ''): void
    {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        $stmt = $mysqli->prepare(
            "INSERT INTO activity_logs (actor_type, actor_id, action, description, ip_address, created_at)
             VALUES (?, ?, ?, ?, ?, NOW())"
        );

        if (!$stmt) {
            return;
        }

        $stmt->bind_param('sisss', $actorType, $actorId, $action, $description, $ipAddress);
        $stmt->execute();
        $stmt->close();
    }
}

if (!function_exists('fetch_recent_activity_logs')) {
    function fetch_recent_activity_logs(mysqli $mysqli, int $limit = 10): array
    {
        $logs = [];
        $limit = max(1, min($limit, 100));
        $result = $mysqli->query(
            "SELECT actor_type, actor_id, action, description, ip_address, created_at
             FROM activity_logs
             ORDER BY id DESC
             LIMIT {$limit}"
        );

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $logs[] = $row;
            }

            $result->close();
        }

        return $logs;
    }
}
