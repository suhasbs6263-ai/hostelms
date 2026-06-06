<?php

require_once(__DIR__ . '/notification-helpers.php');
require_once(__DIR__ . '/activity-helpers.php');
require_once(__DIR__ . '/auth-helpers.php');
require_once(__DIR__ . '/security-helpers.php');
require_once(__DIR__ . '/upload-helpers.php');

if (!function_exists('normalize_contact_value')) {
    function normalize_contact_value(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
    }
}

if (!function_exists('student_full_name')) {
    function student_full_name(array $student): string
    {
        return trim(($student['first_name'] ?? '') . ' ' . ($student['middle_name'] ?? '') . ' ' . ($student['last_name'] ?? ''));
    }
}

if (!function_exists('fetch_student_by_email')) {
    function fetch_student_by_email(mysqli $mysqli, string $email): ?array
    {
        $stmt = $mysqli->prepare("SELECT * FROM students WHERE email = ? LIMIT 1");

        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $student = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        return $student ?: null;
    }
}

if (!function_exists('fetch_student_by_id')) {
    function fetch_student_by_id(mysqli $mysqli, int $studentId): ?array
    {
        $stmt = $mysqli->prepare(
            "SELECT s.*, c.course_fn, c.course_sn
             FROM students s
             LEFT JOIN courses c ON c.id = s.course_id
             WHERE s.id = ?
             LIMIT 1"
        );

        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('i', $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $student = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        return $student ?: null;
    }
}

if (!function_exists('fetch_student_by_registration_number')) {
    function fetch_student_by_registration_number(mysqli $mysqli, string $registrationNumber): ?array
    {
        $stmt = $mysqli->prepare("SELECT * FROM students WHERE registration_number = ? LIMIT 1");

        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('s', $registrationNumber);
        $stmt->execute();
        $result = $stmt->get_result();
        $student = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        return $student ?: null;
    }
}

if (!function_exists('fetch_all_students')) {
    function fetch_all_students(mysqli $mysqli): array
    {
        $students = [];
        $result = $mysqli->query(
            "SELECT s.*, c.course_fn, c.course_sn, a.full_name AS approved_by_name
             FROM students s
             LEFT JOIN courses c ON c.id = s.course_id
             LEFT JOIN admins a ON a.id = s.approved_by_admin_id
             ORDER BY s.created_at DESC, s.first_name ASC"
        );

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $students[] = $row;
            }
            $result->close();
        }

        return $students;
    }
}

if (!function_exists('register_student_account')) {
    function register_student_account(mysqli $mysqli, array $data): array
    {
        $registrationNumber = normalize_text($data['registration_number'] ?? '');
        $firstName = normalize_text($data['first_name'] ?? '');
        $middleName = normalize_text($data['middle_name'] ?? '');
        $lastName = normalize_text($data['last_name'] ?? '');
        $gender = normalize_text($data['gender'] ?? '');
        $phone = normalize_contact_value($data['phone'] ?? '');
        $email = strtolower(normalize_text($data['email'] ?? ''));
        $password = (string) ($data['password'] ?? '');
        $courseId = isset($data['course_id']) && ctype_digit((string) $data['course_id']) ? (int) $data['course_id'] : null;
        $emergencyContact = normalize_contact_value($data['emergency_contact'] ?? '');
        $guardianName = normalize_text($data['guardian_name'] ?? '');
        $guardianRelation = normalize_text($data['guardian_relation'] ?? '');
        $guardianPhone = normalize_contact_value($data['guardian_phone'] ?? '');
        $addressLine = normalize_text($data['address_line'] ?? '');
        $city = normalize_text($data['city'] ?? '');
        $pincode = normalize_text($data['pincode'] ?? '');
        $profilePhoto = normalize_text($data['profile_photo'] ?? '');
        $idDocumentPath = normalize_text($data['id_document_path'] ?? '');

        $errors = validate_required_fields(
            [
                'registration_number' => $registrationNumber,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'gender' => $gender,
                'phone' => $phone,
                'email' => $email,
                'password' => $password,
            ],
            [
                'registration_number' => 'Registration number',
                'first_name' => 'First name',
                'last_name' => 'Last name',
                'gender' => 'Gender',
                'phone' => 'Phone number',
                'email' => 'Email address',
                'password' => 'Password',
            ]
        );

        if ($email !== '' && !validate_email_address($email)) {
            $errors['email'] = 'Enter a valid email address.';
        }

        if ($phone !== '' && !validate_phone_number($phone)) {
            $errors['phone'] = 'Enter a valid phone number.';
        }

        if ($emergencyContact !== '' && !validate_phone_number($emergencyContact)) {
            $errors['emergency_contact'] = 'Enter a valid emergency contact number.';
        }

        if ($guardianPhone !== '' && !validate_phone_number($guardianPhone)) {
            $errors['guardian_phone'] = 'Enter a valid guardian phone number.';
        }

        if ($password !== '' && !validate_password_strength($password)) {
            $errors['password'] = 'Password must be at least 8 characters.';
        }

        if (fetch_student_by_email($mysqli, $email)) {
            $errors['email'] = 'This email is already registered.';
        }

        if (fetch_student_by_registration_number($mysqli, $registrationNumber)) {
            $errors['registration_number'] = 'This registration number is already registered.';
        }

        if ($errors) {
            return ['ok' => false, 'errors' => $errors, 'student_id' => 0];
        }

        $stmt = $mysqli->prepare(
            "INSERT INTO students (
                registration_number, first_name, middle_name, last_name, gender, phone, email, password,
                status, course_id, profile_photo, id_document_path, emergency_contact, guardian_name, guardian_relation,
                guardian_phone, address_line, city, pincode, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())"
        );

        if (!$stmt) {
            return ['ok' => false, 'errors' => ['general' => 'Unable to prepare student registration.'], 'student_id' => 0];
        }

        $passwordHash = hash_app_password($password);
        $courseId = $courseId ?: null;
        $stmt->bind_param(
            'ssssssssisssssssss',
            $registrationNumber,
            $firstName,
            $middleName,
            $lastName,
            $gender,
            $phone,
            $email,
            $passwordHash,
            $courseId,
            $profilePhoto,
            $idDocumentPath,
            $emergencyContact,
            $guardianName,
            $guardianRelation,
            $guardianPhone,
            $addressLine,
            $city,
            $pincode
        );
        $ok = $stmt->execute();
        $studentId = (int) $stmt->insert_id;
        $stmt->close();

        if (!$ok) {
            return ['ok' => false, 'errors' => ['general' => 'Unable to create the student account right now.'], 'student_id' => 0];
        }

        log_activity($mysqli, 'student', $studentId, 'student_registered', 'Student account created with pending approval.');
        create_notification($mysqli, 'student', $studentId, 'Registration received', 'Your hostel account is pending admin approval.', '../student/dashboard.php');
        create_notifications_for_role($mysqli, 'admin', 'New student registration', $firstName . ' ' . $lastName . ' has registered and is pending approval.', '../admin/register-student.php');

        return ['ok' => true, 'errors' => [], 'student_id' => $studentId];
    }
}

if (!function_exists('update_student_approval_status')) {
    function update_student_approval_status(mysqli $mysqli, int $studentId, string $status, int $adminId, string $remarks = ''): bool
    {
        if (!in_array($status, ['approved', 'rejected'], true)) {
            return false;
        }

        $stmt = $mysqli->prepare(
            "UPDATE students
             SET status = ?, approved_by_admin_id = ?, approval_remarks = ?, approved_at = NOW(), updated_at = NOW()
             WHERE id = ?"
        );

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('sisi', $status, $adminId, $remarks, $studentId);
        $ok = $stmt->execute();
        $stmt->close();

        if (!$ok) {
            return false;
        }

        $student = fetch_student_by_id($mysqli, $studentId);
        if ($student) {
            $title = $status === 'approved' ? 'Account approved' : 'Account rejected';
            $message = $status === 'approved'
                ? 'Your student account has been approved. You can now log in and apply for hostel accommodation.'
                : 'Your student account has been rejected. Please contact the admin for details.';
            create_notification($mysqli, 'student', $studentId, $title, $message, '../index.php');
        }

        log_activity($mysqli, 'admin', $adminId, 'student_' . $status, 'Student #' . $studentId . ' was ' . $status . '.');
        return true;
    }
}

if (!function_exists('update_student_profile')) {
    function update_student_profile(mysqli $mysqli, int $studentId, array $data): array
    {
        $student = fetch_student_by_id($mysqli, $studentId);
        if (!$student) {
            return ['ok' => false, 'errors' => ['general' => 'Student not found.']];
        }

        $firstName = normalize_text($data['first_name'] ?? $student['first_name']);
        $middleName = normalize_text($data['middle_name'] ?? $student['middle_name']);
        $lastName = normalize_text($data['last_name'] ?? $student['last_name']);
        $phone = normalize_contact_value($data['phone'] ?? $student['phone']);
        $emergencyContact = normalize_contact_value($data['emergency_contact'] ?? $student['emergency_contact']);
        $guardianName = normalize_text($data['guardian_name'] ?? $student['guardian_name']);
        $guardianRelation = normalize_text($data['guardian_relation'] ?? $student['guardian_relation']);
        $guardianPhone = normalize_contact_value($data['guardian_phone'] ?? $student['guardian_phone']);
        $addressLine = normalize_text($data['address_line'] ?? $student['address_line']);
        $city = normalize_text($data['city'] ?? $student['city']);
        $pincode = normalize_text($data['pincode'] ?? $student['pincode']);
        $courseId = isset($data['course_id']) && ctype_digit((string) $data['course_id']) ? (int) $data['course_id'] : null;

        $errors = [];
        if (!validate_phone_number($phone)) {
            $errors['phone'] = 'Enter a valid phone number.';
        }

        if ($emergencyContact !== '' && !validate_phone_number($emergencyContact)) {
            $errors['emergency_contact'] = 'Enter a valid emergency contact.';
        }

        if ($guardianPhone !== '' && !validate_phone_number($guardianPhone)) {
            $errors['guardian_phone'] = 'Enter a valid guardian phone number.';
        }

        if ($errors) {
            return ['ok' => false, 'errors' => $errors];
        }

        $stmt = $mysqli->prepare(
            "UPDATE students
             SET first_name = ?, middle_name = ?, last_name = ?, phone = ?, emergency_contact = ?,
                 guardian_name = ?, guardian_relation = ?, guardian_phone = ?, address_line = ?, city = ?, pincode = ?,
                 course_id = ?, updated_at = NOW()
             WHERE id = ?"
        );

        if (!$stmt) {
            return ['ok' => false, 'errors' => ['general' => 'Unable to update profile right now.']];
        }

        $stmt->bind_param(
            'sssssssssssii',
            $firstName,
            $middleName,
            $lastName,
            $phone,
            $emergencyContact,
            $guardianName,
            $guardianRelation,
            $guardianPhone,
            $addressLine,
            $city,
            $pincode,
            $courseId,
            $studentId
        );

        $ok = $stmt->execute();
        $stmt->close();

        if ($ok) {
            log_activity($mysqli, 'student', $studentId, 'profile_updated', 'Student profile updated.');
        }

        return ['ok' => $ok, 'errors' => $ok ? [] : ['general' => 'Unable to update profile right now.']];
    }
}

if (!function_exists('count_students_by_status')) {
    function count_students_by_status(mysqli $mysqli, string $status): int
    {
        $stmt = $mysqli->prepare("SELECT COUNT(*) AS total FROM students WHERE status = ?");

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
