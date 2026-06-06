<?php

require_once(__DIR__ . '/auth-helpers.php');
require_once(__DIR__ . '/security-helpers.php');

if (!function_exists('auth_login_path')) {
    function auth_login_path(string $role): string
    {
        return match ($role) {
            'admin' => 'index.php',
            'warden' => 'index.php',
            default => '../index.php',
        };
    }
}

if (!function_exists('check_login')) {
    function check_login(string $role = 'student'): void
    {
        if (!is_logged_in_as($role)) {
            safe_redirect(auth_login_path($role));
        }
    }
}

if (!function_exists('check_any_role')) {
    function check_any_role(array $roles): void
    {
        $currentRole = current_user_role();

        if (!$currentRole || !in_array($currentRole, $roles, true)) {
            safe_redirect('../index.php');
        }
    }
}
