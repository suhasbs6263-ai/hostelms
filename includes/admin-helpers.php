<?php

require_once(__DIR__ . '/auth-helpers.php');
require_once(__DIR__ . '/activity-helpers.php');

if (!function_exists('fetch_admin_by_identity')) {
    function fetch_admin_by_identity(mysqli $mysqli, string $identity): ?array
    {
        $stmt = $mysqli->prepare(
            "SELECT * FROM admins
             WHERE (username = ? OR email = ?) AND status = 'active'
             LIMIT 1"
        );

        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('ss', $identity, $identity);
        $stmt->execute();
        $result = $stmt->get_result();
        $admin = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        return $admin ?: null;
    }
}

if (!function_exists('fetch_admin_by_id')) {
    function fetch_admin_by_id(mysqli $mysqli, int $adminId): ?array
    {
        $stmt = $mysqli->prepare("SELECT * FROM admins WHERE id = ? LIMIT 1");

        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('i', $adminId);
        $stmt->execute();
        $result = $stmt->get_result();
        $admin = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        return $admin ?: null;
    }
}

if (!function_exists('authenticate_admin')) {
    function authenticate_admin(mysqli $mysqli, string $identity, string $password): array
    {
        $admin = fetch_admin_by_identity($mysqli, $identity);

        if (!$admin || !verify_app_password($password, (string) $admin['password'])) {
            return ['ok' => false, 'message' => 'Invalid admin username/email or password.'];
        }

        if (password_hash_needs_upgrade((string) $admin['password'])) {
            $stmt = $mysqli->prepare("UPDATE admins SET password = ?, updated_at = NOW() WHERE id = ?");

            if ($stmt) {
                $newHash = hash_app_password($password);
                $stmt->bind_param('si', $newHash, $admin['id']);
                $stmt->execute();
                $stmt->close();
            }
        }

        login_user('admin', [
            'id' => (int) $admin['id'],
            'username' => $admin['username'],
            'full_name' => $admin['full_name'],
            'email' => $admin['email'],
            'status' => $admin['status'],
        ]);

        log_activity($mysqli, 'admin', (int) $admin['id'], 'admin_login', 'Admin signed in successfully.');

        return ['ok' => true, 'message' => ''];
    }
}
