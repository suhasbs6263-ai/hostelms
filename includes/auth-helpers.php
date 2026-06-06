<?php

require_once(__DIR__ . '/../config/app.php');
require_once(__DIR__ . '/security-helpers.php');

if (!function_exists('session_fingerprint')) {
    function session_fingerprint(): string
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'cli';
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        return hash('sha256', $userAgent . '|' . $ipAddress);
    }
}

if (!function_exists('initialize_auth_session')) {
    function initialize_auth_session(): void
    {
        ensure_session_started();

        if (!isset($_SESSION['auth'])) {
            $_SESSION['auth'] = [];
        }

        if (!empty($_SESSION['auth']['last_active']) && (time() - (int) $_SESSION['auth']['last_active']) > SESSION_TIMEOUT_SECONDS) {
            logout_current_user();
        }

        if (!empty($_SESSION['auth']['fingerprint']) && $_SESSION['auth']['fingerprint'] !== session_fingerprint()) {
            logout_current_user();
        }

        if (!empty($_SESSION['auth'])) {
            $_SESSION['auth']['last_active'] = time();
        }
    }
}

if (!function_exists('login_user')) {
    function login_user(string $role, array $user): void
    {
        ensure_session_started();
        session_regenerate_id(true);

        $_SESSION['auth'] = [
            'role' => $role,
            'user_id' => (int) ($user['id'] ?? 0),
            'name' => trim((string) ($user['name'] ?? $user['username'] ?? $user['full_name'] ?? $user['email'] ?? 'User')),
            'email' => (string) ($user['email'] ?? ''),
            'status' => (string) ($user['status'] ?? 'active'),
            'fingerprint' => session_fingerprint(),
            'last_active' => time(),
        ];

        if ($role === 'student') {
            $_SESSION['id'] = (int) ($user['id'] ?? 0);
            $_SESSION['login'] = (string) ($user['email'] ?? '');
        } elseif ($role === 'admin') {
            $_SESSION['admin'] = (string) ($user['username'] ?? $user['email'] ?? 'admin');
        } elseif ($role === 'warden') {
            $_SESSION['warden'] = (string) ($user['email'] ?? $user['username'] ?? 'warden');
        }
    }
}

if (!function_exists('logout_current_user')) {
    function logout_current_user(): void
    {
        ensure_session_started();
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }

        session_destroy();
    }
}

if (!function_exists('current_user_role')) {
    function current_user_role(): ?string
    {
        initialize_auth_session();
        return $_SESSION['auth']['role'] ?? null;
    }
}

if (!function_exists('current_user_id')) {
    function current_user_id(): int
    {
        initialize_auth_session();
        return (int) ($_SESSION['auth']['user_id'] ?? 0);
    }
}

if (!function_exists('current_user_name')) {
    function current_user_name(): string
    {
        initialize_auth_session();
        return (string) ($_SESSION['auth']['name'] ?? 'User');
    }
}

if (!function_exists('current_user_email')) {
    function current_user_email(): string
    {
        initialize_auth_session();
        return (string) ($_SESSION['auth']['email'] ?? '');
    }
}

if (!function_exists('is_logged_in_as')) {
    function is_logged_in_as(string $role): bool
    {
        return current_user_role() === $role && current_user_id() > 0;
    }
}

if (!function_exists('hash_app_password')) {
    function hash_app_password(string $plainPassword): string
    {
        return password_hash($plainPassword, PASSWORD_DEFAULT);
    }
}

if (!function_exists('verify_app_password')) {
    function verify_app_password(string $plainPassword, string $storedPassword): bool
    {
        if ($storedPassword === '') {
            return false;
        }

        $info = password_get_info($storedPassword);
        if (($info['algo'] ?? 0) !== 0) {
            return password_verify($plainPassword, $storedPassword);
        }

        return hash_equals($storedPassword, md5($plainPassword));
    }
}

if (!function_exists('password_hash_needs_upgrade')) {
    function password_hash_needs_upgrade(string $storedPassword): bool
    {
        $info = password_get_info($storedPassword);
        if (($info['algo'] ?? 0) === 0) {
            return true;
        }

        return password_needs_rehash($storedPassword, PASSWORD_DEFAULT);
    }
}

if (!function_exists('upgrade_password_hash')) {
    function upgrade_password_hash(mysqli $mysqli, string $table, string $idColumn, $idValue, string $plainPassword): void
    {
        $sql = "UPDATE `{$table}` SET `password` = ? WHERE `{$idColumn}` = ? LIMIT 1";
        $stmt = $mysqli->prepare($sql);

        if (!$stmt) {
            return;
        }

        $newHash = hash_app_password($plainPassword);
        $idValue = (string) $idValue;
        $stmt->bind_param('ss', $newHash, $idValue);
        $stmt->execute();
        $stmt->close();
    }
}

initialize_auth_session();
