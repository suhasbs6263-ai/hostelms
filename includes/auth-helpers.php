<?php

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
