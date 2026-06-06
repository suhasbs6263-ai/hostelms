<?php

require_once(__DIR__ . '/auth-helpers.php');
require_once(__DIR__ . '/activity-helpers.php');
require_once(__DIR__ . '/security-helpers.php');

if (!function_exists('fetch_wardens')) {
    function fetch_wardens(mysqli $mysqli, bool $activeOnly = false): array
    {
        $wardens = [];
        $sql = "SELECT * FROM wardens";

        if ($activeOnly) {
            $sql .= " WHERE status = 'active'";
        }

        $sql .= " ORDER BY full_name ASC, id DESC";
        $result = $mysqli->query($sql);

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $wardens[] = $row;
            }
            $result->close();
        }

        return $wardens;
    }
}

if (!function_exists('fetch_active_wardens')) {
    function fetch_active_wardens(mysqli $mysqli): array
    {
        return fetch_wardens($mysqli, true);
    }
}

if (!function_exists('fetch_warden_by_identity')) {
    function fetch_warden_by_identity(mysqli $mysqli, string $identity): ?array
    {
        $stmt = $mysqli->prepare(
            "SELECT * FROM wardens
             WHERE (username = ? OR email = ?) AND status = 'active'
             LIMIT 1"
        );

        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('ss', $identity, $identity);
        $stmt->execute();
        $result = $stmt->get_result();
        $warden = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        return $warden ?: null;
    }
}

if (!function_exists('fetch_warden_by_id')) {
    function fetch_warden_by_id(mysqli $mysqli, int $wardenId): ?array
    {
        $stmt = $mysqli->prepare("SELECT * FROM wardens WHERE id = ? LIMIT 1");

        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('i', $wardenId);
        $stmt->execute();
        $result = $stmt->get_result();
        $warden = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        return $warden ?: null;
    }
}

if (!function_exists('warden_identity_exists')) {
    function warden_identity_exists(mysqli $mysqli, string $username, string $email, int $excludeId = 0): bool
    {
        $stmt = $mysqli->prepare(
            "SELECT id FROM wardens
             WHERE (username = ? OR email = ?)
             AND id <> ?
             LIMIT 1"
        );

        if (!$stmt) {
            return true;
        }

        $stmt->bind_param('ssi', $username, $email, $excludeId);
        $stmt->execute();
        $exists = (bool) $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $exists;
    }
}

if (!function_exists('validate_warden_payload')) {
    function validate_warden_payload(mysqli $mysqli, array $data, int $excludeId = 0, bool $passwordRequired = true): array
    {
        $fullName = normalize_text($data['full_name'] ?? '');
        $username = strtolower(normalize_text($data['username'] ?? ''));
        $email = strtolower(normalize_text($data['email'] ?? ''));
        $phone = normalize_text($data['phone'] ?? '');
        $password = (string) ($data['password'] ?? '');
        $status = normalize_text($data['status'] ?? 'active');

        $errors = validate_required_fields(
            [
                'full_name' => $fullName,
                'username' => $username,
                'email' => $email,
            ],
            [
                'full_name' => 'Warden name',
                'username' => 'Username',
                'email' => 'Email address',
            ]
        );

        if ($email !== '' && !validate_email_address($email)) {
            $errors['email'] = 'Enter a valid email address.';
        }

        if ($phone !== '' && !validate_phone_number($phone)) {
            $errors['phone'] = 'Enter a valid phone number.';
        }

        if (!in_array($status, ['active', 'inactive'], true)) {
            $errors['status'] = 'Choose a valid status.';
        }

        if ($passwordRequired && $password === '') {
            $errors['password'] = 'Password is required.';
        } elseif ($password !== '' && !validate_password_strength($password)) {
            $errors['password'] = 'Password must be at least 8 characters.';
        }

        if ($username !== '' && $email !== '' && warden_identity_exists($mysqli, $username, $email, $excludeId)) {
            $errors['identity'] = 'This warden username or email already exists.';
        }

        return [
            'errors' => $errors,
            'data' => [
                'full_name' => $fullName,
                'username' => $username,
                'email' => $email,
                'phone' => $phone,
                'password' => $password,
                'status' => $status,
            ],
        ];
    }
}

if (!function_exists('create_warden_account')) {
    function create_warden_account(mysqli $mysqli, array $data): array
    {
        $validated = validate_warden_payload($mysqli, $data, 0, true);

        if ($validated['errors']) {
            return ['ok' => false, 'errors' => $validated['errors'], 'warden_id' => 0];
        }

        $warden = $validated['data'];
        $stmt = $mysqli->prepare(
            "INSERT INTO wardens (full_name, username, email, phone, password, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())"
        );

        if (!$stmt) {
            return ['ok' => false, 'errors' => ['general' => 'Unable to prepare warden account.'], 'warden_id' => 0];
        }

        $passwordHash = hash_app_password($warden['password']);
        $stmt->bind_param(
            'ssssss',
            $warden['full_name'],
            $warden['username'],
            $warden['email'],
            $warden['phone'],
            $passwordHash,
            $warden['status']
        );
        $ok = $stmt->execute();
        $wardenId = (int) $stmt->insert_id;
        $stmt->close();

        return ['ok' => $ok, 'errors' => $ok ? [] : ['general' => 'Unable to create warden account.'], 'warden_id' => $wardenId];
    }
}

if (!function_exists('update_warden_account')) {
    function update_warden_account(mysqli $mysqli, int $wardenId, array $data): array
    {
        $validated = validate_warden_payload($mysqli, $data, $wardenId, false);

        if ($validated['errors']) {
            return ['ok' => false, 'errors' => $validated['errors']];
        }

        $warden = $validated['data'];

        if ($warden['password'] !== '') {
            $stmt = $mysqli->prepare(
                "UPDATE wardens
                 SET full_name = ?, username = ?, email = ?, phone = ?, password = ?, status = ?, updated_at = NOW()
                 WHERE id = ?"
            );

            if (!$stmt) {
                return ['ok' => false, 'errors' => ['general' => 'Unable to prepare warden update.']];
            }

            $passwordHash = hash_app_password($warden['password']);
            $stmt->bind_param(
                'ssssssi',
                $warden['full_name'],
                $warden['username'],
                $warden['email'],
                $warden['phone'],
                $passwordHash,
                $warden['status'],
                $wardenId
            );
        } else {
            $stmt = $mysqli->prepare(
                "UPDATE wardens
                 SET full_name = ?, username = ?, email = ?, phone = ?, status = ?, updated_at = NOW()
                 WHERE id = ?"
            );

            if (!$stmt) {
                return ['ok' => false, 'errors' => ['general' => 'Unable to prepare warden update.']];
            }

            $stmt->bind_param(
                'sssssi',
                $warden['full_name'],
                $warden['username'],
                $warden['email'],
                $warden['phone'],
                $warden['status'],
                $wardenId
            );
        }

        $ok = $stmt->execute();
        $stmt->close();

        return ['ok' => $ok, 'errors' => $ok ? [] : ['general' => 'Unable to update warden account.']];
    }
}

if (!function_exists('authenticate_warden')) {
    function authenticate_warden(mysqli $mysqli, string $identity, string $password): array
    {
        $warden = fetch_warden_by_identity($mysqli, $identity);

        if (!$warden || !verify_app_password($password, (string) $warden['password'])) {
            return ['ok' => false, 'message' => 'Invalid warden username/email or password.'];
        }

        if (password_hash_needs_upgrade((string) $warden['password'])) {
            $stmt = $mysqli->prepare("UPDATE wardens SET password = ?, updated_at = NOW() WHERE id = ?");

            if ($stmt) {
                $newHash = hash_app_password($password);
                $stmt->bind_param('si', $newHash, $warden['id']);
                $stmt->execute();
                $stmt->close();
            }
        }

        login_user('warden', [
            'id' => (int) $warden['id'],
            'username' => $warden['username'],
            'full_name' => $warden['full_name'],
            'email' => $warden['email'],
            'status' => $warden['status'],
        ]);

        log_activity($mysqli, 'warden', (int) $warden['id'], 'warden_login', 'Warden signed in successfully.');

        return ['ok' => true, 'message' => ''];
    }
}
